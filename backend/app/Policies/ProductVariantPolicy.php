<?php

namespace App\Policies;

use App\Enums\ProductType;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

class ProductVariantPolicy
{
    public function view(User $user, ProductVariant $variant): bool
    {
        return $user->can('access-admin');
    }

    public function create(User $user, Product $product): bool
    {
        return $user->can('access-admin') && $product->product_type === ProductType::Variable && ! $product->isExternallyManaged();
    }

    public function update(User $user, ProductVariant $variant): bool
    {
        return $this->view($user, $variant) && ! $variant->isExternallyManaged();
    }

    public function updateCommercial(User $user, ProductVariant $variant): bool
    {
        return $this->update($user, $variant) && $user->role === UserRole::Admin;
    }

    public function delete(User $user, ProductVariant $variant): bool
    {
        return false;
    }
}
