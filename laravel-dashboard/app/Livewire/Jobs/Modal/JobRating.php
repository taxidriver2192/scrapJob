<?php

namespace App\Livewire\Jobs\Modal;

use App\Models\JobPosting;
use App\Services\JobRatingService;
use App\Exceptions\OpenAiException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Flux\Flux;

class JobRating extends Component
{
    public $rating;
    public $jobPosting;

    public function mount($rating, $jobPosting)
    {
        $this->rating = $rating;
        $this->jobPosting = $jobPosting;
    }

    // Helper methods for the view
    public function hasRating()
    {
        return $this->rating;
    }

    public function isAiRating()
    {
        return $this->rating && data_get($this->rating, 'source_type') === 'ai_job_rating';
    }

    public function getAiRatingId()
    {
        if ($this->isAiRating()) {
            return data_get($this->rating, 'source_id');
        }
        return null;
    }

    public function getCriteria()
    {
        $criteria = data_get($this->rating, 'criteria', '');
        return is_string($criteria) ? json_decode($criteria, true) : ($criteria ?: []);
    }

    public function getConfidence()
    {
        // Check if this is an AI rating with direct confidence score
        if (data_get($this->rating, 'ai_confidence')) {
            return data_get($this->rating, 'ai_confidence', 0);
        }

        // Fallback to criteria-based confidence
        $criteria = $this->getCriteria();
        return data_get($criteria, 'confidence', 0);
    }

    public function getConfidenceColor()
    {
        $confidence = $this->getConfidence();
        if ($confidence >= 80) {
            return 'green';
        }
        if ($confidence >= 60) {
            return 'yellow';
        }
        return 'red';
    }

    public function getRadarChartData()
    {
        if (!$this->hasRating()) {
            return null;
        }

        $criteria = $this->getCriteria();

        $axes = [
            [
                'name' => 'Location',
                'score' => $this->getLocationScore(),
                'color' => '#3b82f6',
                'icon' => 'map-pin',
                'tooltip' => data_get($criteria, 'location', 'Location analysis not available')
            ],
            [
                'name' => 'Tech Skills',
                'score' => $this->getTechScore(),
                'color' => '#8b5cf6',
                'icon' => 'code-bracket',
                'tooltip' => data_get($criteria, 'tech_match', 'Technical skills analysis not available')
            ],
            [
                'name' => 'Team Size',
                'score' => $this->getTeamSizeScore(),
                'color' => '#f97316',
                'icon' => 'user-group',
                'tooltip' => data_get($criteria, 'company_fit', 'Company culture and team size analysis not available')
            ],
            [
                'name' => 'Leadership',
                'score' => $this->getLeadershipScore(),
                'color' => '#6366f1',
                'icon' => 'academic-cap',
                'tooltip' => data_get($criteria, 'seniority_fit', 'Leadership and seniority level analysis not available')
            ]
        ];

        return [
            'axes' => $axes,
        ];
    }

    public function getOverallScore()
    {
        return data_get($this->rating, 'overall_score', 0);
    }

    public function getLocationScore()
    {
        return data_get($this->rating, 'location_score', 0);
    }

    public function getTechScore()
    {
        return data_get($this->rating, 'tech_score', 0);
    }

    public function getTeamSizeScore()
    {
        return data_get($this->rating, 'team_size_score', 0);
    }

    public function getLeadershipScore()
    {
        return data_get($this->rating, 'leadership_score', 0);
    }

    public function getRatedDate()
    {
        $ratedAt = data_get($this->rating, 'rated_at');
        return $ratedAt ? \Carbon\Carbon::parse($ratedAt)->format('F j, Y \a\t g:i A') : 'N/A';
    }

    public function rateJob()
    {
        if (!$this->jobPosting || !data_get($this->jobPosting, 'job_id')) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to rate job - job ID not found.'
            ]);
            return;
        }

        if (!Auth::check()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'You must be logged in to rate jobs.'
            ]);
            return;
        }

        try {
            // Show loading state
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'AI is analyzing this job... Please wait a moment.'
            ]);

            // Get the job posting model
            $jobPosting = JobPosting::where('job_id', data_get($this->jobPosting, 'job_id'))->first();

            if (!$jobPosting) {
                throw new OpenAiException('Job posting not found in database');
            }

            // Rate the job using the AI service
            $jobRatingService = app(JobRatingService::class);
            $jobRatingService->rateJobForUser($jobPosting);

            // Success notification
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Job successfully rated! The AI analysis is now available.'
            ]);

            // Refresh the component to show the new rating
            $this->dispatch('refreshJobRating', ['jobId' => data_get($this->jobPosting, 'job_id')]);

        } catch (OpenAiException $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'AI rating failed: ' . $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'An error occurred while rating the job: ' . $e->getMessage()
            ]);
        }
    }

    public function requestRating()
    {
        if (!Auth::check()) {
            Flux::toast(
                heading: 'Authentication Required',
                text: 'You must be logged in to rate a job.',
                variant: 'warning'
            );
            return;
        }

        $userId = Auth::id();
        $jobId = data_get($this->jobPosting, 'job_id');

        if (!$jobId) {
            Flux::toast(
                heading: 'Error',
                text: 'Job ID not found.',
                variant: 'danger'
            );
            return;
        }

        // Check if job already has an AI rating for this user
        $existingRating = \App\Models\JobRating::where('job_id', $jobId)
            ->where('user_id', $userId)
            ->first();

        if ($existingRating) {
            Flux::toast(
                heading: 'Already Rated',
                text: 'You have already rated this job.',
                variant: 'warning'
            );
            return;
        }

        // Check if job is already queued
        $existingQueue = \App\Models\JobQueue::where('job_id', $jobId)
            ->whereIn('status_code', [\App\Models\JobQueue::STATUS_PENDING, \App\Models\JobQueue::STATUS_IN_PROGRESS])
            ->first();

        if ($existingQueue) {
            Flux::toast(
                heading: 'Already Queued',
                text: 'This job is already queued for rating. Check the queue status if needed.',
                variant: 'warning'
            );
            return;
        }

        try {
            // Add to queue
            $queueItem = \App\Models\JobQueue::create([
                'job_id' => $jobId,
                'user_id' => $userId,
                'status_code' => \App\Models\JobQueue::STATUS_PENDING,
                'queued_at' => now(),
            ]);

            // Dispatch the job for background processing
            \App\Jobs\ProcessAiJobRating::dispatch($queueItem->queue_id);

            Flux::toast(
                heading: 'Rating Queued Successfully',
                text: 'Your job rating is being processed in the background.',
                variant: 'success'
            );
        } catch (\Exception $e) {
            Flux::toast(
                heading: 'Queue Error',
                text: 'Failed to queue job for rating. Please try again.',
                variant: 'danger'
            );
        }
    }

    /**
     * Check if the job is closed
     */
    public function isJobClosed(): bool
    {
        return $this->jobPosting && !is_null($this->jobPosting->job_post_closed_date);
    }

    public function render()
    {
        return view('livewire.jobs.modal.job-rating');
    }
}
