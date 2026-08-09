<?php

namespace App\Observers;

use App\Models\BorrowingRequest;
use App\Notifications\BorrowingStatusChanged;

class BorrowingRequestNotificationObserver
{
    private const array NOTIFIABLE_STATUSES = [
        'disetujui',
        'ditolak',
        'diproses',
        'selesai',
    ];

    public function updated(BorrowingRequest $borrowingRequest): void
    {
        if (! $borrowingRequest->wasChanged('status')) {
            return;
        }

        if (! in_array($borrowingRequest->status, self::NOTIFIABLE_STATUSES, true)) {
            return;
        }

        $requester = $borrowingRequest->requester;

        if ($requester === null) {
            return;
        }

        $requester->notify(new BorrowingStatusChanged($borrowingRequest));
    }
}
