<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Orchid\Attachment\Attachable;
use Orchid\Filters\Filterable;
use Orchid\Screen\AsSource;

class Team extends Model
{
    use HasFactory, AsSource, Filterable, Attachable;

    public const DIVISIONS = ['ATLANTIC', 'PACIFIC', 'CENTRAL', 'METROPOLITAN', 'NONE'];

    /**
     * ORCHID setting to allow HTTP sorting on desired columns
     *
     * @var array<string>
     */
    protected $allowedSorts = [
        'name',
        'division',
        'overall_rating',
        'offense_rating',
        'defense-rating',
        'goaltender_rating',
        'created_at',
        'updated_at',
    ];

    /**
     * ORCHID setting to allow HTTP filtering on desired columns
     *
     * @var array<string>
     */
    protected $allowedFilters = [
        'name',
        'division',
    ];
}
