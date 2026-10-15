<?php

namespace App\Policies;

use App\Models\Banner;
use App\Models\HomepageSection;
use App\Models\User;

class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('access-admin');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, HomepageSection|Banner $content): bool
    {
        return $this->viewAny($user);
    }
}
