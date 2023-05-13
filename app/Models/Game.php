<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;
use Mpociot\Versionable\VersionableTrait;

class Game extends Model
{
    use HasFactory, AsSource, Filterable, Attachable, VersionableTrait;

    protected $guarded = [
        'created_at',
        'updated_at'
    ];

    /**
     * ORCHID setting to allow HTTP sorting on desired columns
     *
     * @var string[]
     */
    protected $allowedSorts = [
        'home_user_id',
        'away_user_id',
        'home_team_id',
        'away_team_id',
        'goals_home',
        'goals_away',
        'created_at',
        'updated_at'
    ];

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
