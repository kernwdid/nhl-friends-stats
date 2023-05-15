<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class GamePolicy
{
    use HandlesAuthorization;

    public function viewAny()
    {
        return true;
    }

    public function view()
    {
        return true;
    }

    public function delete(User $user)
    {
        return $user->id === 1;
    }

    public function update()
    {
        return true;
    }

    public function create()
    {
        return true;
    }
}
