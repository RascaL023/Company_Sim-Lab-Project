<?php

namespace App\Policies;

use App\Models\Usage;
use App\Models\User;

class UsagePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Usage $usage): bool
    {
        return $user->canViewAllLabRecords() || $usage->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Usage $usage): bool
    {
        return $user->isLaboran() || $usage->user_id === $user->id;
    }

    public function delete(User $user, Usage $usage): bool
    {
        return $user->isLaboran() || $usage->user_id === $user->id;
    }

    public function verify(User $user, Usage $usage): bool
    {
        return $user->isLaboran();
    }

    public function reject(User $user, Usage $usage): bool
    {
        return $user->isLaboran();
    }
}
