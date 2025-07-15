<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\JobQueue;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Queue extends Component
{
    use WithPagination;

    public $statusFilter = '';
    public $perPage = 20;

    protected $queryString = ['statusFilter'];

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $queueItems = JobQueue::with(['jobPosting.company'])
            ->when($this->statusFilter, function ($query) {
                $query->where('status_code', $this->statusFilter);
            })
            ->orderBy('queued_at', 'desc')
            ->paginate($this->perPage);

        $statusCounts = [
            'pending' => JobQueue::where('status_code', JobQueue::STATUS_PENDING)->count(),
            'in_progress' => JobQueue::where('status_code', JobQueue::STATUS_IN_PROGRESS)->count(),
            'done' => JobQueue::where('status_code', JobQueue::STATUS_DONE)->count(),
            'error' => JobQueue::where('status_code', JobQueue::STATUS_ERROR)->count(),
        ];

        return view('livewire.queue', compact('queueItems', 'statusCounts'));
    }
}
