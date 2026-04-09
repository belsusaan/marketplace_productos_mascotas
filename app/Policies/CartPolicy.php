<?php

namespace App\Policies;

use App\Models\Cart;
use App\Models\User;

class CartPolicy
{
     public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('create order');
    }

    public function view(User $user, Cart $cart): bool
    {
        return $user->hasPermissionTo('create order') && $user->id === $cart->user_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create order');
    }

    public function update(User $user, Cart $cart): bool
    {
        return $user->hasPermissionTo('create order') && $user->id === $cart->user_id;
    }

    public function delete(User $user, Cart $cart): bool
    {
        return $user->hasPermissionTo('create order') && $user->id === $cart->user_id;
    }
}
