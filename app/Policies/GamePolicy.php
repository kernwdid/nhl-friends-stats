<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Orchid\Platform\Models\Role;

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
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole) {
            return $user->inRole($adminRole);
        }
        return false;
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
