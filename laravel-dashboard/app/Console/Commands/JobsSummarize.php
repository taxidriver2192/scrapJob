<?php

namespace App\Console\Commands;

use App\Models\JobPosting;
use App\Services\OpenAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobsSummarize extends Command
{
    protected $signature = 'jobs:summarize
                            {--limit=0 : How many postings to process}
                            {--dry-run : Don’t save to DB}
                            {--rebuild  : Regenerate even if a summary exists}
                            {--job-id= : Process only a specific job by ID}';


    protected $description = 'Generate brief_summary_of_job for job postings';

    private array $previewRows = [];   // holds [job_id, title, summary] for dry‑run

    public function handle(OpenAiService $ai): int
    {
        $limit   = (int) $this->option('limit');
        $dryRun  = (bool) $this->option('dry-run');
        $rebuild = (bool) $this->option('rebuild');
        $jobId   = $this->option('job-id'); // Add this line

        // Count *all* candidates (before limit) for extrapolation.
        $baseQuery = JobPosting::query()
            ->whereNotNull('description')
            ->whereRaw('LENGTH(description) > 50');

        // Add job-id filter if specified
        if ($jobId) {
            $baseQuery->where('job_id', $jobId);
            // Skip other filters when processing a specific job
            $this->info("Processing specific job ID: {$jobId}");
        } else {
            if (!$rebuild) {
                $baseQuery->whereNull('brief_summary_of_job');
            }
        }

        $totalPending = (int) $baseQuery->count();

        // Now apply limit for the actual run (but skip limit if job-id is specified)
        $query = clone $baseQuery;
        if ($limit > 0 && !$jobId) {
            $query->limit($limit);
        }
        $toProcess = (int) $query->count();

        if ($toProcess === 0) {
            if ($jobId) {
                $this->error("Job with ID {$jobId} not found or doesn't meet criteria.");
            } else {
                $this->info('Nothing to do.');
            }
            return Command::SUCCESS;
        }

        $this->info("Processing {$toProcess} job postings…");
        $bar = $this->output->createProgressBar($toProcess);

        // Cost counters
        $totPrompt   = 0;
        $totComplete = 0;
        $totCost     = 0.0;
        $processed   = 0;

        // Stream in chunks for memory safety
        $query->chunkById(100, function ($posts) use ($ai, $bar, $dryRun, &$totPrompt, &$totComplete, &$totCost, &$processed) {
            foreach ($posts as $job) {
                $prompt = $this->buildPrompt($job);

                // Log the prompt being sent
                Log::info("JobsSummarize: Sending prompt for job {$job->job_id}", [
                    'job_id' => $job->job_id,
                    'job_title' => $job->title,
                    'company' => $job->company?->name ?? 'Unknown',
                    'city' => $job->city,
                    'zipcode' => $job->zipcode,
                    'prompt' => $prompt
                ]);

                try {
                    $resp = $ai->generateChatCompletion(
                        $prompt,
                        'gpt-4o-mini',
                        0.2,
                        120
                    );

                    // Log the response
                    Log::info("JobsSummarize: Received response for job {$job->job_id}", [
                        'job_id' => $job->job_id,
                        'response_content' => $resp['content'] ?? 'No content',
                        'usage' => $resp['usage'] ?? []
                    ]);

                    // Cost accounting
                    $usage  = $resp['usage'] ?? [];
                    $pTok   = $usage['prompt_tokens']     ?? 0;
                    $cTok   = $usage['completion_tokens'] ?? 0;
                    $totPrompt   += $pTok;
                    $totComplete += $cTok;
                    $totCost     += $ai->calculateCost($pTok, $cTok, $resp['model'] ?? 'gpt-4o-mini');

                    // Process the response to get a proper sentence
                    $raw = (string)($resp['content'] ?? '');
                    $sentence = $this->pickOneSentence($raw);

                    // Validate and potentially retry if the sentence doesn't look good
                    if (!$this->looksGood($sentence, $job)) {
                        $companyName = ($job->company ? $job->company->name : null) ?? 'Unknown Company';
                        $retryPrompt = $this->buildPrompt($job) .
                            "\n\nRewrite as exactly ONE sentence (12–35 words, ≤300 chars), in English. " .
                            "Include: title \"{$job->title}\", company \"{$companyName}\", location (infer or omit), seniority, 2–3 key skills, and work arrangement if given. " .
                            "Avoid starting with 'Join'. Vary phrasing. End with a period. Return the sentence only.";

                        try {
                            Log::info("JobsSummarize: Retrying with improved prompt for job {$job->job_id}");
                            $resp2 = $ai->generateChatCompletion($retryPrompt, 'gpt-4o-mini', 0.2, 120);

                            // Update cost accounting for retry
                            $usage2 = $resp2['usage'] ?? [];
                            $pTok2 = $usage2['prompt_tokens'] ?? 0;
                            $cTok2 = $usage2['completion_tokens'] ?? 0;
                            $totPrompt += $pTok2;
                            $totComplete += $cTok2;
                            $totCost += $ai->calculateCost($pTok2, $cTok2, $resp2['model'] ?? 'gpt-4o-mini');

                            $sentence = $this->pickOneSentence((string)($resp2['content'] ?? $sentence));
                        } catch (\Throwable $retryError) {
                            Log::warning("JobsSummarize: Retry failed for job {$job->job_id}: {$retryError->getMessage()}");
                            // Keep the original sentence
                        }
                    }

                    if ($dryRun) {
                        $this->previewRows[] = [
                            'job_id'  => $job->job_id,
                            'title'   => $job->title,
                            'summary' => $sentence,
                        ];
                    }

                    if (!$dryRun) {
                        $job->brief_summary_of_job = $sentence;
                        $job->save();
                    }

                    $processed++;
                } catch (\Throwable $e) {
                    $this->error("Job {$job->job_id} failed: {$e->getMessage()}");

                    // Log the error with detailed information
                    Log::error("JobsSummarize: Failed to process job {$job->job_id}", [
                        'job_id' => $job->job_id,
                        'error_message' => $e->getMessage(),
                        'error_class' => get_class($e),
                        'prompt' => $prompt ?? 'No prompt available'
                    ]);
                }

                usleep(250_000); // rate‑limit ~4 rps
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        // =====  Final cost report  =====
        $this->info(sprintf(
            'Processed %d postings ‑ Prompt: %d tok, Completion: %d tok, Cost: %.4f USD',
            $processed,
            $totPrompt,
            $totComplete,
            $totCost
        ));

        if ($dryRun && !empty($this->previewRows)) {
            $this->newLine();
            $this->info('Preview of generated summaries:');

            $this->table(
                ['Job ID', 'Title', 'Generated brief_summary_of_job'],
                $this->previewRows
            );
        }

        if ($dryRun && $processed > 0 && $totalPending > $processed) {
            $avgCost = $totCost / $processed;
            $remaining = $totalPending - $processed;
            $est = $avgCost * $remaining;
            $this->info(sprintf(
                'Estimated additional cost for the remaining %d postings: ~%.2f USD',
                $remaining,
                $est
            ));
        }

        return Command::SUCCESS;
    }    private function buildPrompt(JobPosting $job): string
    {
        $skills = is_array($job->skills)
            ? implode(', ', $job->skills)
            : (string) $job->skills;

        $companyName = ($job->company ? $job->company->name : null) ?? 'Unknown Company';

        // Format location using city and zipcode, but don't show "N/A"
        $location = '';
        if ($job->city && $job->zipcode) {
            $location = "{$job->city} ({$job->zipcode})";
        } elseif ($job->city) {
            $location = $job->city;
        } elseif ($job->zipcode) {
            $location = $job->zipcode;
        }

        return <<<TXT
Write exactly ONE energetic sentence (12–35 words, ≤300 characters), in English.
Include: title, company, location (infer from description or omit if unknown), seniority, exactly 2–3 key skills/tech, and work arrangement (onsite/hybrid/remote/full-time) if stated.
Avoid starting with "Join". Vary phrasing (e.g., Lead, Drive, Own, Build, Develop).
End with a single period. Return the sentence only.

• Title: {$job->title}
• Company: {$companyName}
• Location: {$location}
• Work‑type: {$job->work_type}
• Key skills: {$skills}
• Description: """{$job->description}"""
TXT;
    }

    /**
     * Pick the best sentence from AI response with proper parsing
     */
    private function pickOneSentence(string $text): string
    {
        // Normalize whitespace and strip wrapping quotes
        $t = trim(preg_replace('/\s+/u', ' ', $text), " \t\n\r\0\x0B\"'");

        // Split on sentence boundaries: punctuation followed by whitespace + next sentence start
        $parts = preg_split('/(?<=[.!?])\s+(?=[\p{Lu}0-9])/u', $t, -1, PREG_SPLIT_NO_EMPTY) ?: [$t];

        // Prefer the first sentence with >=10 words, else the longest by word count
        $candidate = null;
        foreach ($parts as $p) {
            if (str_word_count($p) >= 10) {
                $candidate = $p;
                break;
            }
        }
        if ($candidate === null) {
            usort($parts, fn($a,$b) => str_word_count($b) <=> str_word_count($a));
            $candidate = $parts[0];
        }

        // Enforce 300 chars and end with a single period
        $candidate = mb_substr($candidate, 0, 300);
        $candidate = rtrim($candidate, " \t\n\r\0\x0B.!?") . '.';
        return $candidate;
    }

    /**
     * Validate if the generated sentence looks good
     */
    private function looksGood(string $s, JobPosting $job): bool
    {
        $wc = str_word_count($s);
        $hasTitle = $job->title && stripos($s, $job->title) !== false;
        $hasCompany = $job->company?->name ? stripos($s, $job->company->name) !== false : true;

        return $wc >= 12 && $wc <= 50 && mb_strlen($s) <= 300 && $hasTitle && $hasCompany && preg_match('/[.]$/u', $s);
    }
}
