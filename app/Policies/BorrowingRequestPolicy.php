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
        return $user->isAdmin() || $borrowingRequest->requested_by === $user->id;
    }

    public function create(User $user, ?int $requestedBy = null): bool
    {
        return $user->isAdmin() || $requestedBy === null || $requestedBy === $user->id;
    }

    public function update(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isAdmin() || $borrowingRequest->requested_by === $user->id;
    }

    public function delete(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isAdmin() || $borrowingRequest->requested_by === $user->id;
    }

    public function approve(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isAdmin();
    }

    public function cancel(User $user, BorrowingRequest $borrowingRequest): bool
    {
        return $user->isAdmin() || $borrowingRequest->requested_by === $user->id;
    }
}
