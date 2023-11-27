<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Tournament extends Model
{
    use HasFactory, Filterable, AsSource;

    public function players(): BelongsToMany {
        return $this->belongsToMany(User::class, 'tournament_players');
    }
}
