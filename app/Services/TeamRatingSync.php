<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class TeamRatingSync
{
    public const FIELDS = ['overall_rating', 'offense_rating', 'defense_rating', 'goaltender_rating'];

    public function fetch(): array
    {
        $reference = json_decode(file_get_contents(database_path('data/nhl27-ratings-2026-09-13.json')), true, 512, JSON_THROW_ON_ERROR);
        $rows = [];
        foreach ($reference['teams'] as $team) {
            $html = Http::connectTimeout(5)->timeout(15)->get($team['source_url'])->throw()->body();
            $text = preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($html)));
            if (! str_contains($text, $team['name'].' on NHL 27') ||
                ! preg_match('/Team Overall Rating of\s+(\d+)\s*\(OFF:\s*(\d+),\s*DEF:\s*(\d+),\s*GOA:\s*(\d+)\)/', $text, $matches)) {
                throw new RuntimeException('Unexpected NHL 27 rating page for '.$team['abbreviation'].'. No ratings changed.');
            }
            $rows[] = ['abbreviation' => $team['abbreviation']] +
                array_combine(self::FIELDS, array_map('intval', array_slice($matches, 1)));
        }

        return ['edition' => 'NHL 27', 'teams' => $rows];
    }

    public function update(array $payload, bool $dryRun = false): int
    {
        $rules = [
            'edition' => 'required|in:NHL 27',
            'teams' => 'required|array|size:32',
            'teams.*.abbreviation' => 'required|string|distinct',
        ];
        foreach (self::FIELDS as $field) {
            $rules['teams.*.'.$field] = 'required|integer|between:1,99';
        }
        Validator::make($payload, $rules)->validate();
        $expected = json_decode(file_get_contents(database_path('data/nhl27-ratings-2026-09-13.json')), true, 512, JSON_THROW_ON_ERROR);
        if (array_diff(array_column($expected['teams'], 'abbreviation'), array_column($payload['teams'], 'abbreviation'))) {
            throw new RuntimeException('The import must contain exactly the 32 expected NHL teams.');
        }

        return DB::transaction(function () use ($payload, $dryRun) {
            // Resolve all IDs before updating. Keep names, IDs, abbreviations and history.
            $updates = [];
            foreach ($payload['teams'] as $row) {
                $aliases = $row['abbreviation'] === 'WIN' ? ['WIN', 'WPG'] : [$row['abbreviation']];
                $matches = DB::table('teams')->whereIn('abbreviation', $aliases)->where('division', '!=', 'NONE')->lockForUpdate()->get();
                if ($matches->count() !== 1) {
                    throw new RuntimeException('Missing or ambiguous NHL team: '.$row['abbreviation']);
                }
                $updates[$matches->first()->id] = array_intersect_key($row, array_flip(self::FIELDS));
            }
            if (! $dryRun) {
                foreach ($updates as $id => $ratings) {
                    DB::table('teams')->where('id', $id)->update($ratings + ['updated_at' => now(), 'ratings_synced_at' => now()]);
                }
            }

            return count($updates);
        }, 3);
    }
}
