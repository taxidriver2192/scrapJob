<?php

namespace App\Livewire\JobQueue;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\JobQueue;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    use WithPagination;

    public $perPage = 15;
    public $sortField = 'queued_at';
    public $sortDirection = 'desc';
    public $statusFilter = '';
    public $userFilter = '';
    public $search = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'userFilter' => ['except' => ''],
        'sortField' => ['except' => 'queued_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 15],
    ];

    public function mount()
    {
        // No specific mounting logic needed
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedUserFilter()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->reset(['search', 'statusFilter', 'userFilter']);
        $this->resetPage();
    }

    public function retryJob($queueId)
    {
        $queueItem = JobQueue::find($queueId);

        if ($queueItem && $queueItem->status_code === JobQueue::STATUS_ERROR) {
            $queueItem->update(['status_code' => JobQueue::STATUS_PENDING]);

            // Dispatch the job again
            \App\Jobs\ProcessAiJobRating::dispatch($queueId);

            session()->flash('success', 'Job queued for retry.');
        }
    }

    public function cancelJob($queueId)
    {
        $queueItem = JobQueue::find($queueId);

        if ($queueItem && in_array($queueItem->status_code, [JobQueue::STATUS_PENDING, JobQueue::STATUS_ERROR])) {
            $queueItem->delete();
            session()->flash('success', 'Job removed from queue.');
        }
    }

    public function render()
    {
        $query = JobQueue::with(['jobPosting.company', 'user'])
            ->when($this->search, function ($query) {
                $query->whereHas('jobPosting', function ($q) {
                    $q->where('title', 'like', '%' . $this->search . '%');
                })->orWhereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status_code', $this->statusFilter);
            })
            ->when($this->userFilter, function ($query) {
                $query->where('user_id', $this->userFilter);
            });

        $queueItems = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $users = User::select('id', 'name', 'email')
            ->whereHas('jobQueues')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => JobQueue::count(),
            'pending' => JobQueue::where('status_code', JobQueue::STATUS_PENDING)->count(),
            'in_progress' => JobQueue::where('status_code', JobQueue::STATUS_IN_PROGRESS)->count(),
            'completed' => JobQueue::where('status_code', JobQueue::STATUS_DONE)->count(),
            'errors' => JobQueue::where('status_code', JobQueue::STATUS_ERROR)->count(),
        ];

        return view('livewire.job-queue.index', [
            'queueItems' => $queueItems,
            'users' => $users,
            'stats' => $stats,
        ]);
    }
}
