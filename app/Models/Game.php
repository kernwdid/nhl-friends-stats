<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Mpociot\Versionable\VersionableTrait;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Game extends Model
{
    use AsSource, Attachable, Filterable, HasFactory, VersionableTrait;

    protected $guarded = [
        'created_at',
        'updated_at',
    ];

    /**
     * ORCHID setting to allow HTTP sorting on desired columns
     *
     * @var array<string>
     */
    protected $allowedSorts = [
        'home_user_id',
        'away_user_id',
        'home_team_id',
        'away_team_id',
        'goals_home',
        'goals_away',
        'win_type',
        'created_at',
    ];

    /**
     * Restrict only dashboard queries; tournament history keeps all games.
     * A derived table also keeps later home/away OR clauses inside this filter.
     */
    public function scopeForDashboard(Builder $query): Builder
    {
        $games = DB::table('games')->whereNotExists(function ($subquery) {
            $subquery->selectRaw('1')->from('rounds')
                ->join('tournaments', 'tournaments.id', '=', 'rounds.tournament_id')
                ->whereColumn('rounds.game_id', 'games.id')
                ->where('tournaments.archived', true);
        });

        return $query->fromSub($games, 'games');
    }

    public function home_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'home_user_id');
    }

    public function away_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'away_user_id');
    }

    public function home_team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function away_team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }
}
