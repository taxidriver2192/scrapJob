<?php

namespace App\Livewire\Jobs;

use App\Models\JobPosting;
use App\Services\JobRatingService;
use App\Exceptions\OpenAiException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\JobRating;
use App\Models\UserJobFavorite;
use Flux\Flux;


class SharedJobContent extends Component
{
    public $jobPosting;
    public $rating;
    public $currentIndex;
    public $total;
    public $showNavigation;
    public $showBackButton;

    protected $listeners = [
        'openJobModal' => 'openModal',
        'refreshJobModal' => 'refreshModal',
        'closeJobModal' => 'closeModal',
        'updateJobId' => 'handleJobIdUpdate',
        'previousRating' => 'previousRating',
        'nextRating' => 'nextRating',
        'refreshJobRating' => 'refreshRating',
    ];

    public function mount($jobPosting, $rating = null, $currentIndex = null, $total = null, $showNavigation = true, $showBackButton = false)
    {
        $this->jobPosting = $jobPosting;
        $this->rating = $rating;
        $this->currentIndex = $currentIndex;
        $this->total = $total;
        $this->showNavigation = $showNavigation;
        $this->showBackButton = $showBackButton;
    }

    public function previousRating()
    {
        $this->dispatch('previousRating');
    }

    public function nextRating()
    {
        $this->dispatch('nextRating');
    }

    public function goBackToDashboard()
    {
        $this->dispatch('goBackToDashboard');
    }

    public function rateJobWithAi($userId = null)
    {
        if (!Auth::check()) {
            Flux::toast('You must be logged in to rate a job.');
            return;
        }

        $user = Auth::user();

        // Check if user has already rated this job
        $existingRating = JobRating::where('job_id', $this->job->id)
            ->where('user_id', $user->id)
            ->first();

        dd($existingRating, ' IS THERE A RATING? '); // This works

        if ($existingRating) {
            Flux::toast('You have already rated this job.');
            return;
        }

        try {
            // Use AI service to get job rating
            $aiService = new AiService();
            $rating = $aiService->rateJob($this->job);

            // Create new job rating
            JobRating::create([
                'job_id' => $this->job->id,
                'user_id' => $user->id,
                'rating' => $rating,
                'is_ai_generated' => true,
            ]);

            Flux::toast('Job rated successfully with AI!');

            // Refresh the component to show the new rating
            $this->dispatch('$refresh');
        } catch (\Exception $e) {
            Flux::toast('Error rating job: ' . $e->getMessage());
        }
    }

    /**
     * Refresh rating after AI analysis.
     * @param array $payload
     */
    public function refreshRating(array $payload): void
    {
        $jobId = data_get($payload, 'jobId');
        if ($jobId) {
            $this->rating = JobRating::where('job_id', $jobId)
                ->where('user_id', Auth::id())
                ->where('rating_type', 'ai_rating')
                ->first();
        }
    }

    /**
     * Toggle job favorite status
     */
    public function toggleFavorite()
    {
        if (!Auth::check()) {
            Flux::toast('You must be logged in to save jobs.', 'error');
            return;
        }

        if (!$this->jobPosting || !$this->jobPosting->job_id) {
            Flux::toast('Unable to save this job.', 'error');
            return;
        }

        $userId = Auth::id();
        $jobId = $this->jobPosting->job_id;

        if (UserJobFavorite::isFavorited($userId, $jobId)) {
            UserJobFavorite::removeFavorite($userId, $jobId);
            Flux::toast('Job removed from favorites.', 'info');
        } else {
            UserJobFavorite::addFavorite($userId, $jobId);
            Flux::toast('Job saved to favorites!', 'success');
        }

        // Refresh the component to update the button state
        $this->dispatch('$refresh');
    }

    /**
     * Check if the current job is favorited by the authenticated user
     */
    public function isFavorited(): bool
    {
        if (!Auth::check() || !$this->jobPosting || !$this->jobPosting->job_id) {
            return false;
        }

        return UserJobFavorite::isFavorited(Auth::id(), $this->jobPosting->job_id);
    }

    public function canNavigatePrevious()
    {
        // This will be handled by the parent component
        return true;
    }

    public function canNavigateNext()
    {
        // This will be handled by the parent component
        return true;
    }

    /**
     * Get breadcrumb items for this job, only if valid data exists.
     * @return array|null
     */
    public function getBreadcrumbItems()
    {
        // Only return breadcrumbs if we have a valid job posting with a title
        if (!$this->jobPosting || !data_get($this->jobPosting, 'title')) {
            return null;
        }

        // Don't show breadcrumbs if we're on homepage with jobId parameter (modal context)
        $currentRoute = request()->route()->getName();
        $hasJobIdParam = request()->has('jobId');

        if ($currentRoute === 'dashboard' && $hasJobIdParam) {
            return null;
        }

        return [
            ['label' => 'Jobs', 'url' => route('jobs'), 'icon' => 'briefcase'],
            ['label' => \Illuminate\Support\Str::limit(data_get($this->jobPosting, 'title'), 50)]
        ];
    }

    /**
     * Get headline data for the job content.
     * @return array|null
     */
    public function getHeadlineData()
    {
        if (!$this->jobPosting || !data_get($this->jobPosting, 'title')) {
            return null;
        }

        // Use the SEO-optimized title and subtitle from the JobPosting model
        $title = $this->jobPosting->seo_title;
        $subtitle = $this->jobPosting->seo_subtitle;

        // Add rating status to the title if this is a rating view (in Danish)
        if ($this->rating && data_get($this->rating, 'overall_score', 0) > 0) {
            $title = 'Job Vurdering: ' . $title;
        }

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'icon' => 'star',
        ];
    }

    public function render()
    {
        return view('livewire.jobs.shared-job-content');
    }
}
