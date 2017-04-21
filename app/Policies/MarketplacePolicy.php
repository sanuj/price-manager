<?php

namespace App\Policies;

use App\Marketplace;
use App\User;

class MarketplacePolicy
{
    public function read(User $user)
    {
        return trust($user)->to('marketplace.read');
    }

    public function create(User $user)
    {
        return trust($user)->to('marketplace.create');
    }

    public function update(User $user)
    {
        return trust($user)->to('marketplace.update');
    }

    public function delete(User $user)
    {
        return trust($user)->to('marketplace.delete');
    }
}
