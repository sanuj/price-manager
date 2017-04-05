<?php

namespace Tests;

use App\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Znck\Trust\Models\Permission;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $sharedUser = null;

    /**
     * @param \App\User|string $userOrPermissionString
     * @param string|null $permission
     *
     * @return $this
     * @throws \ErrorException
     */
    public function givePermissionTo($userOrPermissionString, string $permission = null)
    {
        if (is_null($permission)) {
            $permission = $userOrPermissionString;
            $userOrPermissionString = $this->getUser();
        }

        $permission = new Permission(['slug' => $permission, 'name' => $permission]);

        if (!$permission->save()) {
            throw new \ErrorException('Could not store permission.');
        }

        $userOrPermissionString->grantPermission($permission)->refreshPermissions();

        return $this;
    }

    protected function getUser(): User
    {
        return $this->sharedUser ? $this->sharedUser : $this->sharedUser = factory(User::class)->create();
    }
}
