<?php

namespace App\Livewire\Jobs\Modal;

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

        $jobId = data_get($this->jobPosting, 'job_id');

        // Add your job rating logic here
        // This could trigger an API call to your AI rating service
        // or queue a job to process the rating

        // For now, just show a notification
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => "Job rating request submitted for job ID: {$jobId}. This may take a few moments to process."
        ]);

        // You could also close the modal and refresh the table
        // $this->dispatch('closeJobModal');
        // $this->dispatch('refreshJobTable');
    }

    public function render()
    {
        return view('livewire.jobs.modal.job-rating');
    }
}
