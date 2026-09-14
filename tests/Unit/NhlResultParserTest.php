<?php

namespace Tests\Unit;

use App\Services\NhlResultParser;
use PHPUnit\Framework\TestCase;

class NhlResultParserTest extends TestCase
{
    private function token(string $text, float $x, float $y, float $width = 80): array
    {
        return ['text' => $text, 'vertices' => [[$x, $y], [$x + $width, $y], [$x + $width, $y + 20], [$x, $y + 20]]];
    }

    private function row(string $label, string $away, string $home, int $y): array
    {
        return [$this->token($away, 100, $y), $this->token($label, 350, $y, 220), $this->token($home, 750, $y)];
    }

    public function test_complete_image_with_shuffled_annotations_and_translated_scaled_coordinates(): void
    {
        $tokens = [$this->token('BOS', 100, 30), $this->token('3 - 2', 430, 30), $this->token('TOR', 750, 30)];
        $values = [
            ['Shots', '24', '30'], ['Hits', '12', '17'], ['Time on Attack', '8:45', '10:02'],
            ['Passing %', '82,5%', '100%'], ['Faceoffs Won', '10', '14'], ['Penalty Minutes', '02:00', '04:00'],
            ['Power Plays', '1/2', '0/1'], ['Power Play Minutes', '01:24', '02:00'], ['Shorthanded Goals', '0', '1'],
        ];
        foreach ($values as $index => [$label, $away, $home]) {
            array_push($tokens, ...$this->row($label, $away, $home, 100 + $index * 50));
        }
        $tokens = array_reverse($tokens);
        foreach ($tokens as &$token) {
            foreach ($token['vertices'] as &$point) {
                $point = [$point[0] * 1.8 + 2000, $point[1] * 1.8 + 600];
            }
            unset($point);
        }
        unset($token);
        $result = (new NhlResultParser)->parse($tokens, ['BOS' => 4, 'TOR' => 8]);
        $this->assertEquals([
            'away_team_id' => 4, 'home_team_id' => 8, 'goals_away' => 3, 'goals_home' => 2,
            'shots_away' => 24, 'shots_home' => 30, 'hits_away' => 12, 'hits_home' => 17,
            'time_in_offense_away_in_seconds' => 525, 'time_in_offense_home_in_seconds' => 602,
            'pass_percentage_away' => 82.5, 'pass_percentage_home' => 100.0,
            'faceoffs_won_away' => 10, 'faceoffs_won_home' => 14,
            'penalty_minutes_away_in_seconds' => 120, 'penalty_minutes_home_in_seconds' => 240,
            'powerplays_used_away' => 1, 'powerplays_received_away' => 2,
            'powerplays_used_home' => 0, 'powerplays_received_home' => 1,
            'powerplay_time_away_in_seconds' => 84, 'powerplay_time_home_in_seconds' => 120,
            'shorthanded_goals_away' => 0, 'shorthanded_goals_home' => 1,
        ], array_intersect_key($result, array_flip(NhlResultParser::fields())));
        $this->assertSame(100.0, $result['detection_percentage']);
    }

    public function test_missing_value_and_extra_footer_do_not_shift_following_statistics(): void
    {
        $tokens = [...$this->row('Schüsse', '22', '', 100), ...$this->row('Checks', '4', '7', 150),
            ...$this->row('Tore in Unterzahl', '0', '1', 200), ...$this->row('Footer', '99', '88', 250),
            $this->token('9-8', 450, 300), $this->token('BOS', 100, 300)];
        $result = (new NhlResultParser)->parse($tokens, ['BOS' => 1]);
        $this->assertSame(22, $result['shots_away']);
        $this->assertArrayNotHasKey('shots_home', $result);
        $this->assertSame(4, $result['hits_away']);
        $this->assertSame(7, $result['hits_home']);
        $this->assertSame(1, $result['shorthanded_goals_home']);
        $this->assertArrayNotHasKey('goals_away', $result);
        $this->assertArrayNotHasKey('away_team_id', $result);
        $this->assertSame(round(5 / 24 * 100, 2), $result['detection_percentage']);
    }

    public function test_split_words_and_numeric_punctuation_are_joined_within_their_row(): void
    {
        $tokens = [$this->token('08', 100, 100, 20), $this->token(':', 125, 102, 8), $this->token('45', 140, 100, 20),
            $this->token('Time', 350, 100, 45), $this->token('on', 405, 100, 25), $this->token('Attack', 440, 100),
            $this->token('1:02', 750, 100),
            ...$this->row('Powerplays', '0 / 0', '1 / 3', 150),
            ...$this->row('Passgenauigkeit', '9,5 %', '85.0 %', 200)];
        $result = (new NhlResultParser)->parse($tokens);
        $this->assertSame(525, $result['time_in_offense_away_in_seconds']);
        $this->assertSame(62, $result['time_in_offense_home_in_seconds']);
        $this->assertSame(0, $result['powerplays_received_away']);
        $this->assertSame(3, $result['powerplays_received_home']);
        $this->assertSame(9.5, $result['pass_percentage_away']);
    }

    public function test_invalid_and_ambiguous_values_are_not_guessed(): void
    {
        $tokens = [...$this->row('Shots', '20', '21', 100), ...$this->row('Shots', '30', '31', 150),
            ...$this->row('Penalty Minutes', '1:99', '02:00', 200),
            ...$this->row('Passing', '101%', '-5', 250), ...$this->row('Power Plays', '3/2', '0/0', 300)];
        $result = (new NhlResultParser)->parse($tokens);
        foreach (['shots_away', 'shots_home', 'penalty_minutes_away_in_seconds', 'pass_percentage_away', 'pass_percentage_home', 'powerplays_used_away'] as $key) {
            $this->assertArrayNotHasKey($key, $result);
        }
        $this->assertSame(120, $result['penalty_minutes_home_in_seconds']);
        $this->assertSame(0, $result['powerplays_used_home']);
    }

    public function test_empty_and_unusable_boxes_return_zero_completion(): void
    {
        foreach ([[], [['text' => '42', 'vertices' => []]], [$this->token('42', 100, 100)]] as $tokens) {
            $result = (new NhlResultParser)->parse($tokens);
            $this->assertSame(0.0, $result['detection_percentage']);
            $this->assertCount(2, $result);
        }
    }

    public function test_photographed_result_with_logo_text_and_total_shots(): void
    {
        $annotations = json_decode(file_get_contents(__DIR__.'/../Fixtures/nhl-photo-annotations.json'), true, 512, JSON_THROW_ON_ERROR);
        $result = (new NhlResultParser)->parse($annotations, ['LAK' => 1, 'EDM' => 2]);
        $this->assertSame(7, $result['goals_away'] ?? null);
        $this->assertSame(6, $result['goals_home'] ?? null);
        $this->assertSame(18, $result['shots_away'] ?? null);
        $this->assertSame(19, $result['shots_home'] ?? null);
        $this->assertSame(100.0, $result['detection_percentage']);
    }
}
