<?php

namespace Tests\Feature;

use App\Models\User;
use App\Orchid\Resources\TournamentResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchid\Platform\Models\Role;
use Orchid\Support\Facades\Dashboard;
use Tests\TestCase;

class TournamentPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_tournament_permission_is_visible_and_survives_role_save(): void
    {
        $admin = User::factory()->create(['permissions' => ['platform.index' => true, 'platform.systems.roles' => true]]);
        $role = Role::create(['name' => 'Spieler', 'slug' => 'player', 'permissions' => [
            'platform.index' => true, 'resource.tournaments' => true,
        ]]);
        $this->assertSame('resource.tournaments', TournamentResource::permission());
        $slugs = Dashboard::getPermission()->flatten(1)->pluck('slug');
        $this->assertContains('resource.tournaments', $slugs);
        $this->assertContains('resource.teams', $slugs);
        $this->actingAs($admin)->get('/roles/'.$role->id.'/edit')
            ->assertOk()->assertSee('permissions['.base64_encode('resource.tournaments').']', false);
        $this->post('/roles/'.$role->id.'/edit/save', [
            'role' => ['slug' => 'player', 'name' => 'Spieler'],
            'permissions' => [
                base64_encode('platform.index') => 1,
                base64_encode('resource.tournaments') => 1,
                base64_encode('platform.systems.attachment') => 1,
                base64_encode('resource.teams') => 0,
            ],
        ])->assertRedirect();
        $permissions = $role->fresh()->permissions;
        $this->assertEquals(1, $permissions['resource.tournaments']);
        $this->assertEquals(1, $permissions['platform.systems.attachment']);
        $player = User::factory()->create();
        $player->addRole($role->fresh());
        $this->actingAs($player)->get(route('platform.resource.list', ['resource' => 'tournament-resources']))->assertOk();
        $this->get(route('platform.resource.list', ['resource' => 'team-resources']))->assertForbidden();
    }
}
