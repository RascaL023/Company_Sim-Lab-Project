<?php

namespace App\Policies;

use App\Models\StockOpname;
use App\Models\User;

class StockOpnamePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLaboran() || $user->isKepalaLab() || $user->isAdminSistem();
    }

    public function view(User $user, StockOpname $stockOpname): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Hanya laboran yang melakukan stock opname fisik.
     */
    public function create(User $user): bool
    {
        return $user->isLaboran();
    }
}
