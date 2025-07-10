<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JobRating;
use Flux\Flux;

class JobModal extends Component
{
    public $rating = null;
    public $currentIndex = 0;
    public $total = 0;

    protected $listeners = [
        'openJobModal' => 'openModal',
        'refreshJobModal' => 'refreshModal',
        'closeJobModal' => 'closeModal'
    ];

    public function openModal($data)
    {
        // Handle both old format (rating object) and new format (jobId)
        if (isset($data['jobId'])) {
            // Load job rating by job ID
            $this->rating = JobRating::where('job_id', $data['jobId'])
                ->with(['jobPosting.company'])
                ->first();

            if ($this->rating) {
                // Calculate current index and total
                $this->total = JobRating::count();
                $this->currentIndex = JobRating::where('job_id', '<=', $data['jobId'])->count() - 1;
            }
        } else {
            // Old format - direct rating object
            $this->rating = $data;
            $this->currentIndex = $data['currentIndex'] ?? 0;
            $this->total = $data['total'] ?? 0;
        }

        if ($this->rating) {
            Flux::modal('job-details-modal')->show();
            // Trigger map initialization if coordinates are available
            if ($this->rating->jobPosting && $this->rating->jobPosting->lat && $this->rating->jobPosting->lon) {
                $this->dispatch('refreshJobModal', $this->rating, $this->currentIndex, $this->total);
            }
        }
    }

    public function refreshModal($rating, $currentIndex, $total)
    {
        // Handle both array and object formats
        if (is_array($rating)) {
            // If it's an array, we need to reconstruct the rating object
            $this->rating = JobRating::where('rating_id', $rating['rating_id'])
                ->with(['jobPosting.company'])
                ->first();
        } else {
            // If it's already an object, use it directly
            $this->rating = $rating;
        }

        $this->currentIndex = $currentIndex;
        $this->total = $total;
    }

    public function closeModal()
    {
        Flux::modal('job-details-modal')->close();
        $this->rating = null;
        $this->currentIndex = 0;
        $this->total = 0;
    }

    public function previousRating()
    {
        if ($this->rating) {
            // Get the current job ID
            $currentJobId = $this->rating->job_id;

            // Find the previous job with a rating
            $previousRating = JobRating::where('job_id', '<', $currentJobId)
                ->with(['jobPosting.company'])
                ->orderBy('job_id', 'desc')
                ->first();

            if ($previousRating) {
                $this->rating = $previousRating;
                $this->currentIndex = max(0, $this->currentIndex - 1);

                // Trigger map update if coordinates are available
                if ($this->rating->jobPosting && $this->rating->jobPosting->lat && $this->rating->jobPosting->lon) {
                    $this->dispatch('refreshJobModal', $this->rating, $this->currentIndex, $this->total);
                }
            }
        }
    }

    public function nextRating()
    {
        if ($this->rating) {
            // Get the current job ID
            $currentJobId = $this->rating->job_id;

            // Find the next job with a rating
            $nextRating = JobRating::where('job_id', '>', $currentJobId)
                ->with(['jobPosting.company'])
                ->orderBy('job_id', 'asc')
                ->first();

            if ($nextRating) {
                $this->rating = $nextRating;
                $this->currentIndex = min($this->total - 1, $this->currentIndex + 1);

                // Trigger map update if coordinates are available
                if ($this->rating->jobPosting && $this->rating->jobPosting->lat && $this->rating->jobPosting->lon) {
                    $this->dispatch('refreshJobModal', $this->rating, $this->currentIndex, $this->total);
                }
            }
        }
    }

    public function canNavigatePrevious()
    {
        if (!$this->rating) {
            return false;
        }

        return JobRating::where('job_id', '<', $this->rating->job_id)->exists();
    }

    public function canNavigateNext()
    {
        if (!$this->rating) {
            return false;
        }

        return JobRating::where('job_id', '>', $this->rating->job_id)->exists();
    }

    public function render()
    {
        return view('livewire.job-modal');
    }
}
