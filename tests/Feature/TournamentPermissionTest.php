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

    public function test_edit_requires_administration_permission_even_for_user_one(): void
    {
        $player = User::factory()->create(['permissions' => ['platform.index' => true, 'resource.tournaments' => true]]);
        $admin = User::factory()->create(['permissions' => [
            'platform.index' => true, 'resource.tournaments' => true, 'platform.systems.roles' => true,
        ]]);
        $tournament = new \App\Models\Tournament;
        $tournament->forceFill(['name' => 'Protected', 'rounds' => 1, 'total_games_per_player' => 1,
            'max_team_overall_rating_difference' => 5])->save();
        $url = route('platform.resource.edit', ['resource' => 'tournament-resources', 'id' => $tournament->id]);
        $this->assertFalse($player->can('update', $tournament));
        $this->actingAs($player)->get($url)->assertForbidden();
        $this->post($url.'/update', ['model' => ['name' => 'Tampered']])->assertForbidden();
        $this->assertSame('Protected', $tournament->fresh()->name);
        $this->assertStringNotContainsString('>Bearbeiten</a>', view('tournaments.list-actions', compact('tournament'))->render());
        $this->actingAs($admin);
        $this->assertTrue($admin->can('update', $tournament));
        $this->assertStringContainsString('>Bearbeiten</a>', view('tournaments.list-actions', compact('tournament'))->render());
    }
}
