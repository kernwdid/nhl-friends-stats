<?php

declare(strict_types=1);

namespace App\Orchid;

use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    /**
     * @param Dashboard $dashboard
     */
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);

        // ...
    }

    /**
     * @return array<Menu>
     */
    public function registerMainMenu(): array
    {
        return [

            Menu::make(__('Dashboard'))
                ->icon('chart')
                ->route('platform.dashboard'),

            Menu::make(__('tournaments.title'))->icon('energy')
            ->permission('resource.tournaments')
            ->route('platform.resource.list', ['resource' => 'tournament-resources']),
            Menu::make(__('games.title'))
                ->icon('game-controller')
                ->permission('resource.games')
                ->route('platform.resource.list', ['resource' => 'game-resources']),

            Menu::make(__('teams.title'))
                ->icon('people')
                ->permission('resource.teams')
                ->route('platform.resource.list', ['resource' => 'team-resources']),

            Menu::make(__('Users'))
                ->icon('user')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access rights')),

            Menu::make(__('Roles'))
                ->icon('lock')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),
        ];
    }

    /**
     * @return array<ItemPermission>
     */
    public function registerPermissions(): array
    {
        return [
            ItemPermission::group(__('System'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users')),
        ];
    }
}
