<?php

namespace App\Livewire\Jobs\Modal;

use App\Models\JobPosting;
use App\Services\JobRatingService;
use App\Exceptions\OpenAiException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

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
        return $this->rating && data_get($this->rating, 'overall_score', 0) > 0;
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

        // Prepare scores (0-100 scale)
        $locationScore = data_get($this->rating, 'location_score', 0);
        $techScore = data_get($this->rating, 'tech_score', 0);
        $teamSizeScore = data_get($this->rating, 'team_size_score', 0);
        $leadershipScore = data_get($this->rating, 'leadership_score', 0);

        // Chart dimensions
        $size = 240;
        $center = $size / 2;
        $maxRadius = 90;

        // Calculate positions for 4 axes (top, right, bottom, left)
        $axes = [
            [
                'name' => 'Location',
                'score' => $locationScore,
                'color' => '#3b82f6',
                'icon' => 'map-pin',
                'tooltip' => data_get($criteria, 'location', 'Location analysis not available')
            ],
            [
                'name' => 'Tech Skills',
                'score' => $techScore,
                'color' => '#8b5cf6',
                'icon' => 'code-bracket',
                'tooltip' => data_get($criteria, 'tech_match', 'Technical skills analysis not available')
            ],
            [
                'name' => 'Team Size',
                'score' => $teamSizeScore,
                'color' => '#f97316',
                'icon' => 'user-group',
                'tooltip' => data_get($criteria, 'company_fit', 'Company culture and team size analysis not available')
            ],
            [
                'name' => 'Leadership',
                'score' => $leadershipScore,
                'color' => '#6366f1',
                'icon' => 'academic-cap',
                'tooltip' => data_get($criteria, 'seniority_fit', 'Leadership and seniority level analysis not available')
            ]
        ];

        // Calculate polygon points
        $points = [];
        for ($i = 0; $i < 4; $i++) {
            $angle = ($i * 90 - 90) * pi() / 180; // Start from top and go clockwise
            $radius = ($axes[$i]['score'] / 100) * $maxRadius;
            $x = $center + cos($angle) * $radius;
            $y = $center + sin($angle) * $radius;
            $points[] = "$x,$y";
        }

        return [
            'size' => $size,
            'center' => $center,
            'maxRadius' => $maxRadius,
            'axes' => $axes,
            'polygonPoints' => implode(' ', $points)
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
        // Dispatch event to parent component (SharedJobContent) to handle the AI rating
        $this->dispatch('requestAiRating');
    }

    public function render()
    {
        return view('livewire.jobs.modal.job-rating');
    }
}
