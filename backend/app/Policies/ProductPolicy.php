<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('access-admin');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->viewAny($user);
    }

    public function updateIdentity(User $user, Product $product): bool
    {
        return $this->update($user, $product) && ! $product->isExternallyManaged();
    }

    public function updateCommercial(User $user, Product $product): bool
    {
        return $this->updateIdentity($user, $product) && $user->role === UserRole::Admin;
    }

    public function delete(User $user, Product $product): bool
    {
        return false;
    }
}
