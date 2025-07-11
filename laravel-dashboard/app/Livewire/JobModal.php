<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JobRating;
use App\Models\JobPosting;
use Flux\Flux;

class JobModal extends Component
{
    public $jobPosting = null;
    public $rating = null;
    public $currentIndex = 0;
    public $total = 0;

    // Cache navigation state to avoid repeated queries
    public $canGoToPrevious = false;
    public $canGoToNext = false;

    protected $listeners = [
        'openJobModal' => 'openModal',
        'refreshJobModal' => 'refreshModal',
        'closeJobModal' => 'closeModal'
    ];

    public function openModal($data)
    {
        // Handle both old format (rating object) and new format (jobId)
        if (isset($data['jobId'])) {
            // Load the job posting
            $this->jobPosting = JobPosting::where('job_id', $data['jobId'])
                ->with('company')
                ->first();

            // Try to load existing job rating (optional)
            $this->rating = JobRating::where('job_id', $data['jobId'])->first();

            if ($this->jobPosting) {
                // Calculate current index and total
                $this->total = JobPosting::count();
                $this->currentIndex = JobPosting::where('job_id', '<=', $data['jobId'])->count() - 1;

                // Cache navigation state
                $this->updateNavigationState();
            }
        } else {
            // Old format - handle rating object
            $this->rating = $data;
            $this->jobPosting = $this->rating->jobPosting ?? null;
            $this->currentIndex = $data['currentIndex'] ?? 0;
            $this->total = $data['total'] ?? 0;

            // Cache navigation state
            $this->updateNavigationState();
        }

        if ($this->jobPosting) {
            Flux::modal('job-details-modal')->show();
            // Trigger map initialization if coordinates are available
            if ($this->jobPosting->lat && $this->jobPosting->lon) {
                $this->dispatch('refreshJobModal', $this->jobPosting->job_id, $this->currentIndex, $this->total);
            }
        }
    }

    public function refreshModal($jobId, $currentIndex, $total)
    {
        // Load the job posting
        $this->jobPosting = JobPosting::where('job_id', $jobId)
            ->with('company')
            ->first();

        // Try to load existing job rating (optional)
        $this->rating = JobRating::where('job_id', $jobId)->first();

        $this->currentIndex = $currentIndex;
        $this->total = $total;

        // Update navigation state
        $this->updateNavigationState();
    }

    public function closeModal()
    {
        Flux::modal('job-details-modal')->close();
        $this->jobPosting = null;
        $this->rating = null;
        $this->currentIndex = 0;
        $this->total = 0;
        $this->canGoToPrevious = false;
        $this->canGoToNext = false;
    }

    private function updateNavigationState()
    {
        if (!$this->jobPosting || !$this->jobPosting->job_id) {
            $this->canGoToPrevious = false;
            $this->canGoToNext = false;
            return;
        }

        // Single query to check both directions at once
        $navigationData = JobPosting::selectRaw('
            EXISTS(SELECT 1 FROM job_postings WHERE job_id < ?) as has_previous,
            EXISTS(SELECT 1 FROM job_postings WHERE job_id > ?) as has_next
        ', [$this->jobPosting->job_id, $this->jobPosting->job_id])
        ->first();

        $this->canGoToPrevious = (bool) $navigationData->has_previous;
        $this->canGoToNext = (bool) $navigationData->has_next;
    }

    public function previousRating()
    {
        if (!$this->canGoToPrevious || !$this->jobPosting) {
            return;
        }

        $currentJobId = $this->jobPosting->job_id;

        // Find the previous job posting
        $previousJob = JobPosting::where('job_id', '<', $currentJobId)
            ->with('company')
            ->orderBy('job_id', 'desc')
            ->first();

        if ($previousJob) {
            $this->jobPosting = $previousJob;

            // Try to load rating for this job (optional)
            $this->rating = JobRating::where('job_id', $previousJob->job_id)->first();

            // Update index and navigation state
            $this->currentIndex = max(0, $this->currentIndex - 1);
            $this->updateNavigationState();

            // Trigger map update if coordinates are available
            if ($this->jobPosting->lat && $this->jobPosting->lon) {
                $this->dispatch('refreshJobModal', $this->jobPosting->job_id, $this->currentIndex, $this->total);
            }
        }
    }

    public function nextRating()
    {
        if (!$this->canGoToNext || !$this->jobPosting) {
            return;
        }

        $currentJobId = $this->jobPosting->job_id;

        // Find the next job posting
        $nextJob = JobPosting::where('job_id', '>', $currentJobId)
            ->with('company')
            ->orderBy('job_id', 'asc')
            ->first();

        if ($nextJob) {
            $this->jobPosting = $nextJob;

            // Try to load rating for this job (optional)
            $this->rating = JobRating::where('job_id', $nextJob->job_id)->first();

            // Update index and navigation state
            $this->currentIndex = min($this->total - 1, $this->currentIndex + 1);
            $this->updateNavigationState();

            // Trigger map update if coordinates are available
            if ($this->jobPosting->lat && $this->jobPosting->lon) {
                $this->dispatch('refreshJobModal', $this->jobPosting->job_id, $this->currentIndex, $this->total);
            }
        }
    }

    public function canNavigatePrevious()
    {
        return $this->canGoToPrevious;
    }

    public function canNavigateNext()
    {
        return $this->canGoToNext;
    }

    public function rateJob()
    {
        if (!$this->jobPosting || !$this->jobPosting->job_id) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Unable to rate job - job ID not found.'
            ]);
            return;
        }

        $jobId = $this->jobPosting->job_id;

        // Add your job rating logic here
        // This could trigger an API call to your AI rating service
        // or queue a job to process the rating

        // For now, just show a notification
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => "Job rating request submitted for job ID: {$jobId}. This may take a few moments to process."
        ]);

        // You could also close the modal and refresh the table
        // $this->closeModal();
        // $this->dispatch('refreshJobTable');
    }

    // Helper methods for the view
    public function hasRating()
    {
        return $this->rating && data_get($this->rating, 'overall_score', 0) > 0;
    }

    public function getCriteria()
    {
        $criteria = data_get($this->rating, 'criteria', '');
        return is_string($criteria) ? json_decode($criteria, true) : ($criteria ?: []);
    }

    public function getConfidence()
    {
        $criteria = $this->getCriteria();
        return data_get($criteria, 'confidence', 0);
    }

    public function getConfidenceColor()
    {
        $confidence = $this->getConfidence();
        if ($confidence >= 80) return 'green';
        if ($confidence >= 60) return 'yellow';
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

    public function getJobTitle()
    {
        return data_get($this->jobPosting, 'title', 'N/A');
    }

    public function getCompanyName()
    {
        return data_get($this->jobPosting, 'company.name', 'N/A');
    }

    public function getJobLocation()
    {
        return data_get($this->jobPosting, 'location', 'N/A');
    }

    public function getPostcode()
    {
        return data_get($this->jobPosting, 'postcode');
    }

    public function getPostedDate()
    {
        $postedDate = data_get($this->jobPosting, 'posted_date');
        return $postedDate ? $postedDate->format('M j, Y') : 'N/A';
    }

    public function getJobDescription()
    {
        return data_get($this->jobPosting, 'description');
    }

    public function getApplyUrl()
    {
        return data_get($this->jobPosting, 'apply_url');
    }

    public function getRatedDate()
    {
        $ratedAt = data_get($this->rating, 'rated_at');
        return $ratedAt ? \Carbon\Carbon::parse($ratedAt)->format('F j, Y \a\t g:i A') : 'N/A';
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

    public function render()
    {
        return view('livewire.job-modal');
    }
}
