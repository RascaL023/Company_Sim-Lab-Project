<?php

namespace App\Policies;

use App\Models\AssetDisposal;
use App\Models\User;

class AssetDisposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isLaboran() || $user->isKepalaLab() || $user->isAdminSistem();
    }

    public function view(User $user, AssetDisposal $assetDisposal): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isLaboran() || $user->isAdminSistem();
    }

    public function approve(User $user, AssetDisposal $assetDisposal): bool
    {
        return $user->isKepalaLab();
    }

    public function reject(User $user, AssetDisposal $assetDisposal): bool
    {
        return $user->isKepalaLab();
    }
}
