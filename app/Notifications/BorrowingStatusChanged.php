<?php

namespace App\Notifications;

use App\Models\BorrowingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BorrowingStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public BorrowingRequest $borrowingRequest) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $status = $this->borrowingRequest->status;
        $requestNumber = $this->borrowingRequest->request_number;

        return [
            'message' => "Status peminjaman {$requestNumber} berubah menjadi {$status}.",
            'borrowing_request_id' => $this->borrowingRequest->id,
            'request_number' => $requestNumber,
            'status' => $status,
        ];
    }
}
