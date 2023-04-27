<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    use HasFactory;

    const DIVISIONS = ['ATLANTIC','PACIFIC', 'CENTRAL', 'METROPOLITAN', 'NONE'];
}
