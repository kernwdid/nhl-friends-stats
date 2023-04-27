<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $abcPassword = '$2y$10$1kBHrdBhRu.MLkBgW3Oc/uScdTs4Mj9TOHwt7OV3bKW7LzAlqAjRS';

        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => $abcPassword,
            'permissions' => '{"platform.index": true, "platform.systems.roles": true, "platform.systems.users": true, "platform.systems.attachment": true}'
        ]);
    }
}
