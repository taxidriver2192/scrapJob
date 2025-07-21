<?php

namespace App\Jobs;

use App\Models\JobQueue;
use App\Models\JobPosting;
use App\Services\JobRatingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessAiJobRating implements ShouldQueue
{
    use Queueable;

    public $timeout = 120; // 2 minutes timeout
    public $tries = 3;

    private int $queueId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $queueId)
    {
        $this->queueId = $queueId;
    }

    /**
     * Execute the job.
     */
    public function handle(JobRatingService $jobRatingService): void
    {
        // Find the queue item
        $queueItem = JobQueue::find($this->queueId);

        if (!$queueItem) {
            Log::error('Queue item not found', ['queue_id' => $this->queueId]);
            return;
        }

        // Update status to in progress
        $queueItem->update(['status_code' => JobQueue::STATUS_IN_PROGRESS]);

        try {
            // Find the job posting
            $jobPosting = JobPosting::where('job_id', $queueItem->job_id)->first();

            if (!$jobPosting) {
                Log::error('Job posting not found', [
                    'queue_id' => $this->queueId,
                    'job_id' => $queueItem->job_id
                ]);
                $queueItem->update(['status_code' => JobQueue::STATUS_ERROR]);
                return;
            }

            // Process the AI rating using the user from the queue
            $user = \App\Models\User::find($queueItem->user_id);
            if (!$user) {
                Log::error('User not found for queue item', [
                    'queue_id' => $this->queueId,
                    'user_id' => $queueItem->user_id
                ]);
                $queueItem->update(['status_code' => JobQueue::STATUS_ERROR]);
                return;
            }

            $rating = $jobRatingService->rateJobForUser($jobPosting, $user);

            // Mark as completed
            $queueItem->update(['status_code' => JobQueue::STATUS_DONE]);

            Log::info('AI job rating processed', [
                'queue_id' => $this->queueId,
                'job_id' => $queueItem->job_id,
                'rating_id' => $rating->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process AI job rating', [
                'queue_id' => $this->queueId,
                'job_id' => $queueItem->job_id,
                'error' => $e->getMessage(),
            ]);

            $queueItem->update(['status_code' => JobQueue::STATUS_ERROR]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $queueItem = JobQueue::find($this->queueId);
        if ($queueItem) {
            $queueItem->update(['status_code' => JobQueue::STATUS_ERROR]);
        }
    }
}
