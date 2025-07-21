<?php

namespace App\Livewire\AiJobRatings;

use App\Models\JobRating;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Show extends Component
{
    public $rating;
    public $jobPosting;

    public function mount(JobRating $jobRating)
    {
        // Check if user owns this AI rating
        if ($jobRating->user_id !== Auth::id() || $jobRating->rating_type !== 'ai_rating') {
            abort(403, 'You can only view your own AI job ratings.');
        }

        $this->rating = $jobRating;
        $this->loadJobPosting();
    }

    public function loadJobPosting()
    {
        // Load the related job posting
        $this->jobPosting = JobPosting::where('job_id', $this->rating->job_id)
            ->with('company')
            ->first();
    }

    public function parseResponse()
    {
        if (!$this->rating || !$this->rating->response) {
            return null;
        }

        // Try to parse the JSON response
        $decoded = json_decode($this->rating->response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // If not JSON, return as text
        return null;
    }

    public function getFormattedPromptProperty()
    {
        if (!$this->rating || !$this->rating->prompt) {
            return '';
        }

        // Format the prompt for better readability
        return nl2br(e($this->rating->prompt));
    }

    public function getFormattedResponseProperty()
    {
        if (!$this->rating || !$this->rating->response) {
            return '';
        }

        $parsed = $this->parseResponse();

        if ($parsed) {
            return json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return $this->rating->response;
    }

    public function render()
    {
        return view('livewire.ai-job-ratings.show', [
            'parsedResponse' => $this->parseResponse(),
        ]);
    }
}
