<?php

namespace App\Policies;

use App\Models\BorrowingRequest;
use App\Models\User;

class BorrowingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->canViewAllLabRecords()
            || $borrowingRequest->requested_by === $user->id;
    }

    /**
     * Hanya peminjam yang membuat pengajuan; harus atas nama sendiri.
     */
    public function create(User $user, ?int $requestedBy = null): bool
    {
        if (! $user->isPeminjam()) {
            return false;
        }

        return $requestedBy === null || $requestedBy === $user->id;
    }

    public function update(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isLaboran()
            || $borrowingRequest->requested_by === $user->id;
    }

    public function delete(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isLaboran()
            || $borrowingRequest->requested_by === $user->id;
    }

    public function approve(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isLaboran();
    }

    public function reject(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isLaboran();
    }

    public function cancel(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isLaboran()
            || $borrowingRequest->requested_by === $user->id;
    }
}
