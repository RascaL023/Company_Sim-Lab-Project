<?php

namespace App\Policies;

use App\Models\BorrowingItem;
use App\Models\User;

class BorrowingItemPolicy
{
    /**
     * Hanya laboran yang menyerahkan barang secara fisik.
     */
    public function checkout(User $user, BorrowingItem $borrowingItem): bool
    {
        return $user->isLaboran();
    }

    /**
     * Hanya laboran yang memeriksa kondisi saat pengembalian.
     */
    public function returnItem(User $user, BorrowingItem $borrowingItem): bool
    {
        return $user->isLaboran();
    }
}
