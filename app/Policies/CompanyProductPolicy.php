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
        return $this->checkPermission($user, $product, 'company_product.update');
    }

    public function delete(User $user, CompanyProduct $product)
    {
        return $this->checkPermission($user, $product, 'company_product.delete');
    }

    public function readListing(User $user, CompanyProduct $product)
    {
        return $this->checkPermission($user, $product, 'company_product.read');
    }

    public function createListing(User $user, CompanyProduct $product)
    {
        return $this->checkPermission($user, $product, 'company_product.create');
    }

    public function updateListing(User $user, CompanyProduct $product)
    {
        return $this->checkPermission($user, $product, 'company_product.update');
    }

    public function deleteListing(User $user, CompanyProduct $product)
    {
        return $this->checkPermission($user, $product, 'company_product.delete');
    }

    /**
     * Verify company & check permission.
     *
     * @param \App\User $user
     * @param \App\CompanyProduct $product
     * @param $permission
     *
     * @return bool
     */
    protected function checkPermission(User $user, CompanyProduct $product, $permission): bool
    {
        return $user->company_id === $product->company_id and trust($user)->to($permission);
    }
}
