<?php

namespace App\Console\Commands;

use App\Models\JobPosting;
use App\Services\OpenAiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class JobsSummarize extends Command
{
    protected $signature = 'jobs:summarize
                            {--limit=0 : How many postings to process}
                            {--dry-run : Don’t save to DB}
                            {--rebuild  : Regenerate even if a summary exists}';

    protected $description = 'Generate brief_summary_of_job for job postings';

    private array $previewRows = [];   // holds [job_id, title, summary] for dry‑run

    public function handle(OpenAiService $ai): int
    {
        $limit   = (int) $this->option('limit');
        $dryRun  = (bool) $this->option('dry-run');
        $rebuild = (bool) $this->option('rebuild');

        // Count *all* candidates (before limit) for extrapolation.
        $baseQuery = JobPosting::query()
            ->whereNotNull('description')
            ->whereRaw('LENGTH(description) > 50');

        if (!$rebuild) {
            $baseQuery->whereNull('brief_summary_of_job');
        }

        $totalPending = (int) $baseQuery->count();

        // Now apply limit for the actual run.
        $query = clone $baseQuery;
        if ($limit > 0) {
            $query->limit($limit);
        }
        $toProcess = (int) $query->count();

        if ($toProcess === 0) {
            $this->info('Nothing to do.');
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

                try {
                    $resp = $ai->generateChatCompletion(
                        $prompt,
                        'gpt-4o-mini',
                        0.2,
                        120
                    );

                    // Cost accounting
                    $usage  = $resp['usage'] ?? [];
                    $pTok   = $usage['prompt_tokens']     ?? 0;
                    $cTok   = $usage['completion_tokens'] ?? 0;
                    $totPrompt   += $pTok;
                    $totComplete += $cTok;
                    $totCost     += $ai->calculateCost($pTok, $cTok, $resp['model'] ?? 'gpt-4o-mini');

                    // Summarise text
                    $sentence = trim($resp['content']);
                    $sentence = preg_replace('/\s+/', ' ', $sentence);
                    $sentence = strtok($sentence, '.') . ' .';

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
    }

    private function buildPrompt(JobPosting $job): string
    {
        $skills = is_array($job->skills)
            ? implode(', ', $job->skills)
            : (string) $job->skills;
            
        $companyName = ($job->company ? $job->company->name : null) ?? 'N/A';

        return <<<TXT
Write one energetic sentence (≤50 words, ≤300 characters) that summarizes this role for quick scanning. Mention title, company, location, seniority/leadership level, key tech or domain, and work arrangement if stated. Return the sentence only.

• Title: {$job->title}
• Company: {$companyName}
• Location: {$job->location}
• Work‑type: {$job->work_type}
• Key skills: {$skills}
• Description: """{$job->description}"""
TXT;
    }
}
