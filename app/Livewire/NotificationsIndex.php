<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class NotificationsIndex extends Component
{
    use WithPagination;

    public string $filter = 'all'; // 'all' | 'unread'

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function markAsRead(string $id): void
    {
        auth()->user()->notifications()->where('id', $id)->update(['read_at' => now()]);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();
    }

    public function render()
    {
        $query = auth()->user()->notifications();

        if ($this->filter === 'unread') {
            $query->whereNull('read_at');
        }

        $notifications = $query->orderByDesc('created_at')->paginate(20);

        return view('livewire.notifications-index', [
            'notifications' => $notifications,
        ]);
    }
}
