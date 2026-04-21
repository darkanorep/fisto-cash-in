<?php

namespace App\Events;

use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestNotificationCount implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transactionService;
    public $user;

    public function __construct(User $user, TransactionService $transactionService = null)
    {
        $this->user = $user;
        $this->transactionService = $transactionService ?? app(TransactionService::class);
    }

    public function broadcastOn()
    {
        // Private channel specific to each user - must match client subscription
        return new Channel('request-notifications');
    }

    public function broadcastAs()
    {
        return 'request-notifications-count';
    }

    public function broadcastWith()
    {
        return [
            'status_count' => $this->transactionService->statusCount()
        ];
    }
}
