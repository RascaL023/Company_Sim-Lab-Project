<?php

namespace App\Policies;

use App\Models\ItemUnit;
use App\Models\User;

class ItemUnitPolicy
{
    /**
     * Semua role yang login boleh melihat daftar unit fisik.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Semua role yang login boleh melihat detail unit fisik.
     */
    public function view(User $user, ItemUnit $itemUnit): bool
    {
        return true;
    }

    /**
     * Hanya admin_sistem dan laboran yang menambah unit fisik.
     */
    public function create(User $user): bool
    {
        return $user->isAdminSistem() || $user->isLaboran();
    }

    /**
     * Hanya admin_sistem dan laboran yang mengubah unit fisik.
     */
    public function update(User $user, ItemUnit $itemUnit): bool
    {
        return $user->isAdminSistem() || $user->isLaboran();
    }

    /**
     * Hanya admin_sistem dan laboran yang menghapus unit fisik.
     */
    public function delete(User $user, ItemUnit $itemUnit): bool
    {
        return $user->isAdminSistem() || $user->isLaboran();
    }
}
