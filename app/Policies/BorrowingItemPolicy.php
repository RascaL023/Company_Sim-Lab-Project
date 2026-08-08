<?php

namespace App\Policies;

use App\Models\BorrowingItem;
use App\Models\User;

class BorrowingItemPolicy
{
    /**
     * Lab admin (or warehouse staff) physically hands over equipment.
     */
    public function checkout(User $user, BorrowingItem $borrowingItem): bool
    {
        return $user->isAdmin();
    }

    /**
     * Only admin inspects and records return condition.
     */
    public function returnItem(User $user, BorrowingItem $borrowingItem): bool
    {
        return $user->isAdmin();
    }
}
