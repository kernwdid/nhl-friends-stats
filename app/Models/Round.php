<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Round extends Model
{
    use HasFactory;

    public function home_user(): BelongsTo {
        return $this->belongsTo(User::class, 'home_user_id');
    }
    public function away_user(): BelongsTo {
        return $this->belongsTo(User::class, 'away_user_id');
    }

    public function home_team(): BelongsTo {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function away_team(): BelongsTo {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function game(): BelongsTo {
        return $this->belongsTo(Game::class, 'game_id');
    }
}
