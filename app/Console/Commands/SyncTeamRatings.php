<?php

namespace App\Console\Commands;

use App\Services\TeamRatingSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncTeamRatings extends Command
{
    protected $signature = 'teams:sync-ratings {--file= : Import a reviewed JSON file instead of fetching the source} {--dry-run : Validate without changing ratings}';

    protected $description = 'Refresh all 32 NHL team ratings atomically';

    public function handle(TeamRatingSync $sync): int
    {
        try {
            $file = $this->option('file');
            if ($file && ! is_readable($file)) {
                throw new \RuntimeException('The rating file is not readable.');
            }
            $payload = $file
                ? json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR)
                : $sync->fetch();
            $count = $sync->update($payload, (bool) $this->option('dry-run'));
            $this->info(($this->option('dry-run') ? 'Validated ' : 'Updated ').$count.' NHL teams.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            Log::error('NHL rating sync failed; previous ratings retained.', ['error' => $exception->getMessage()]);
            $this->error('No ratings changed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
