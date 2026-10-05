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

    public function test_42_games_in_seven_rounds_gives_every_player_six_games_each_round(): void
    {
        foreach ([2, 3, 4, 5, 6, 7, 8, 12] as $count) {
            for ($run = 0; $run < 10; $run++) {
                $fixtures = (new TournamentSchedule)->generate(range(1, $count), 42, 7);
                $appearances = array_fill(1, 7, array_fill(1, $count, 0));
                $home = $away = array_fill(1, $count, 0);
                $opponents = array_fill(1, $count, array_fill(1, $count, 0));
                foreach ($fixtures as $fixture) {
                    $a = $fixture['home_user_id'];
                    $b = $fixture['away_user_id'];
                    $appearances[$fixture['round']][$a]++;
                    $appearances[$fixture['round']][$b]++;
                    $home[$a]++;
                    $away[$b]++;
                    $opponents[$a][$b]++;
                    $opponents[$b][$a]++;
                }
                foreach ($appearances as $round) {
                    $this->assertSame(array_fill(1, $count, 6), $round);
                }
                foreach (range(1, $count) as $player) {
                    $this->assertSame(21, $home[$player]);
                    $this->assertSame(21, $away[$player]);
                    unset($opponents[$player][$player]);
                    $this->assertLessThanOrEqual(1, max($opponents[$player]) - min($opponents[$player]));
                }
            }
        }
    }
}
