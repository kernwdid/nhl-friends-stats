<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Orchid\Platform\Models\Role;

class UserAndAuthSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $abcPassword = '$2y$10$1kBHrdBhRu.MLkBgW3Oc/uScdTs4Mj9TOHwt7OV3bKW7LzAlqAjRS';

        $playerRole = Role::create(['slug' => 'player', 'name' => 'Spieler']);

        User::factory()->create([
            'name' => 'Friend 1',
            'email' => 'friend1@example.com',
            'password' => $abcPassword
        ])->addRole($playerRole);

        User::factory()->create([
            'name' => 'Friend 2',
            'email' => 'friend2@example.com',
            'password' => $abcPassword
        ])->addRole($playerRole);
    }
}
