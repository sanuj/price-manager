<?php

namespace App\Policies;

use App\CompanyProduct;
use App\User;

class CompanyProductPolicy
{
    public function read(User $user)
    {
        return trust($user)->to('company_product.read');
    }

    public function create(User $user)
    {
        return trust($user)->to('company_product.create');
    }

    public function update(User $user, CompanyProduct $product)
    {
        return $user->company_id === $product->company_id
               and trust($user)->to('company_product.update');
    }

    public function delete(User $user, CompanyProduct $product)
    {
        return $user->company_id === $product->company_id
               and trust($user)->to('company_product.update');
    }
}
