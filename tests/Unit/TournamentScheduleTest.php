<?php

namespace Tests\Unit;

use App\Services\TournamentSchedule;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TournamentScheduleTest extends TestCase
{
    public function test_game_counts_opponents_and_home_away_are_balanced(): void
    {
        for ($n = 2; $n <= 12; $n++) {
            for ($games = 1; $games <= 20; $games++) {
                if (($n * $games) % 2) {
                    continue;
                }
                $rounds = min(5, intdiv($n * $games, 2));
                $fixtures = (new TournamentSchedule)->generate(range(1, $n), $games, $rounds);
                $home = $away = array_fill(1, $n, 0);
                $opponents = array_fill(1, $n, array_fill(1, $n, 0));
                foreach ($fixtures as $fixture) {
                    $a = $fixture['home_user_id'];
                    $b = $fixture['away_user_id'];
                    $this->assertNotSame($a, $b);
                    $home[$a]++;
                    $away[$b]++;
                    $opponents[$a][$b]++;
                    $opponents[$b][$a]++;
                    $this->assertNull($fixture['home_team_id']);
                }
                foreach (range(1, $n) as $player) {
                    $this->assertSame($games, $home[$player] + $away[$player]);
                    $this->assertLessThanOrEqual(1, abs($home[$player] - $away[$player]));
                    unset($opponents[$player][$player]);
                    $this->assertLessThanOrEqual(1, max($opponents[$player]) - min($opponents[$player]));
                }
                $sizes = array_count_values(array_column($fixtures, 'round'));
                $this->assertCount($rounds, $sizes);
                $this->assertLessThanOrEqual(1, max($sizes) - min($sizes));
            }
        }
    }

    public function test_impossible_odd_player_appearance_count_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new TournamentSchedule)->generate([1, 2, 3], 3, 1);
    }
}
