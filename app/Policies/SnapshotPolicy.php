<?php

namespace App\Policies;

use App\User;

class SnapshotPolicy
{
    public function read(User $user)
    {
        return trust($user)->to('snapshot.read');
    }

    public function create(User $user)
    {
        return trust($user)->to('snapshot.create');
    }

    public function update(User $user)
    {
        return trust($user)->to('snapshot.update');
    }

    public function delete(User $user)
    {
        return trust($user)->to('snapshot.delete');
    }
}
