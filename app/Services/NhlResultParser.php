<?php

namespace App\Services;

use Illuminate\Support\Str;

class NhlResultParser
{
    private const LABELS = [
        'shots' => ['shots', 'shots on goal', 'schüsse', 'schuesse'],
        'hits' => ['hits', 'checks'],
        'time_in_offense' => ['time on attack', 'time in offense', 'zeit in offensive', 'zeit in der offensive', 'angriffszeit'],
        'pass_percentage' => ['passing', 'passing percentage', 'pass percentage', 'passgenauigkeit', 'passquote'],
        'faceoffs_won' => ['faceoffs won', 'gewonnene faceoffs', 'gewonnene bully s', 'gewonnene bullys'],
        'penalty_minutes' => ['penalty minutes', 'strafminuten'],
        'powerplays' => ['power plays', 'powerplays', 'überzahlspiele'],
        'powerplay_time' => ['power play minutes', 'power play time', 'powerplay minuten', 'überzahlzeit'],
        'shorthanded_goals' => ['shorthanded goals', 'tore in unterzahl', 'unterzahltore'],
    ];

    public static function fields(): array
    {
        $fields = ['away_team_id', 'home_team_id', 'goals_away', 'goals_home'];
        foreach (array_keys(self::LABELS) as $stat) {
            foreach (['away', 'home'] as $side) {
                if ($stat === 'powerplays') {
                    $fields[] = "powerplays_used_$side";
                    $fields[] = "powerplays_received_$side";
                } else {
                    $fields[] = self::field($stat, $side);
                }
            }
        }

        return $fields;
    }

    /**
     * Map individual Vision annotations, never the aggregate full-text annotation.
     * Coordinates are image-relative; left is away and right is home.
     *
     * @param  array<int, array{text: string, vertices: array}>  $annotations
     * @param  array<string, int>  $teams  Abbreviation => database ID
     */
    public function parse(array $annotations, array $teams = []): array
    {
        $tokens = [];
        foreach ($annotations as $annotation) {
            $points = $annotation['vertices'] ?? [];
            $text = trim($annotation['text'] ?? '');
            if ($text === '' || count($points) < 4) {
                continue;
            }
            $xs = array_column($points, 0);
            $ys = array_column($points, 1);
            $height = max($ys) - min($ys);
            if ($height <= 0 || max($xs) <= min($xs)) {
                continue;
            }
            $tokens[] = ['text' => $text, 'x' => min($xs), 'right' => max($xs),
                'y' => (min($ys) + max($ys)) / 2, 'height' => $height];
        }
        usort($tokens, fn ($a, $b) => [$a['y'], $a['x']] <=> [$b['y'], $b['x']]);
        $rows = [];
        foreach ($tokens as $token) {
            $last = count($rows) - 1;
            // Compare against the row anchor, avoiding chained merges across rows.
            if ($last < 0 || abs($rows[$last][0]['y'] - $token['y']) > min($rows[$last][0]['height'], $token['height']) * 0.6) {
                $rows[] = [$token];
            } else {
                $rows[$last][] = $token;
            }
        }

        $candidates = [];
        $firstStatY = INF;
        $centers = [];
        foreach ($rows as &$row) {
            usort($row, fn ($a, $b) => $a['x'] <=> $b['x']);
            $label = $this->label($row);
            if ($label === null) {
                continue;
            }
            [$stat, $start, $end] = $label;
            $firstStatY = min($firstStatY, $row[$start]['y']);
            $centers[] = ($row[$start]['x'] + $row[$end]['right']) / 2;
            foreach (['away' => array_slice($row, 0, $start), 'home' => array_slice($row, $end + 1)] as $side => $values) {
                $parsed = $this->value($stat, $side, implode('', array_column($values, 'text')));
                foreach ($parsed as $field => $value) {
                    $candidates[$field][] = $value;
                }
            }
        }
        unset($row);

        // Only inspect the header for teams/scores; footer digits and statistics
        // must never become goals. An ambiguous header stays available for review.
        if ($centers !== []) {
            sort($centers);
            $center = $centers[intdiv(count($centers), 2)];
            foreach ($rows as $row) {
                if ($row[0]['y'] >= $firstStatY) {
                    continue;
                }
                $remaining = [];
                foreach ($row as $token) {
                    $abbreviation = strtoupper(trim($token['text']));
                    if (isset($teams[$abbreviation])) {
                        $side = ($token['x'] + $token['right']) / 2 < $center ? 'away' : 'home';
                        $candidates[$side.'_team_id'][] = $teams[$abbreviation];
                    } elseif (! in_array($abbreviation, ['FINAL', 'OT', 'SO'], true)) {
                        $remaining[] = $token['text'];
                    }
                }
                $score = preg_replace('/\s+/u', '', implode('', $remaining));
                if (preg_match('/^(\d{1,2})[-–—](\d{1,2})$/u', $score, $match)
                    && (int) $match[1] <= 50 && (int) $match[2] <= 50) {
                    $candidates['goals_away'][] = (int) $match[1];
                    $candidates['goals_home'][] = (int) $match[2];
                }
            }
        }

        $result = [];
        foreach ($candidates as $field => $values) {
            if (count($values) === 1) {
                $result[$field] = $values[0];
            }
        }
        $result['detection_percentage'] = round(count($result) / count(self::fields()) * 100, 2);
        $result['view_result'] = implode("\n", array_map(fn ($row) => implode(' ', array_column($row, 'text')), $rows));

        return $result;
    }

    private function label(array $row): ?array
    {
        $matches = [];
        foreach ($row as $start => $token) {
            $text = '';
            for ($end = $start; $end < count($row); $end++) {
                $text .= $row[$end]['text'];
                if (preg_match('/\d/', $text)) {
                    break;
                }
                $normalized = $this->normalize($text);
                foreach (self::LABELS as $stat => $aliases) {
                    foreach ($aliases as $alias) {
                        if ($normalized !== '' && $normalized === $this->normalize($alias)) {
                            $matches[] = [$stat, $start, $end];
                            break;
                        }
                    }
                }
            }
        }
        // Prefer the complete label, e.g. "Power Play Time" over "Power Play".
        usort($matches, fn ($a, $b) => ($b[2] - $b[1]) <=> ($a[2] - $a[1]));

        return $matches[0] ?? null;
    }

    private function normalize(string $text): string
    {
        return preg_replace('/[^a-z]/', '', strtolower(Str::ascii($text)));
    }

    private static function field(string $stat, string $side): string
    {
        return $stat.'_'.$side.(in_array($stat, ['time_in_offense', 'penalty_minutes', 'powerplay_time'], true) ? '_in_seconds' : '');
    }

    private function value(string $stat, string $side, string $text): array
    {
        $text = preg_replace('/\s+/u', '', $text);
        $field = self::field($stat, $side);
        if ($stat === 'powerplays') {
            if (preg_match('~^(\d{1,2})[/\\\\](\d{1,2})$~', $text, $match) && (int) $match[1] <= (int) $match[2]) {
                return ["powerplays_used_$side" => (int) $match[1], "powerplays_received_$side" => (int) $match[2]];
            }
        } elseif (str_ends_with($field, '_in_seconds')) {
            if (preg_match('/^(\d{1,2}):([0-5]\d)$/', $text, $match)) {
                return [$field => (int) $match[1] * 60 + (int) $match[2]];
            }
        } elseif ($stat === 'pass_percentage') {
            $number = str_replace(',', '.', rtrim($text, '%'));
            if (preg_match('/^\d{1,3}(?:\.\d{1,2})?$/', $number) && (float) $number <= 100) {
                return [$field => (float) $number];
            }
        } elseif (preg_match('/^\d{1,3}$/', $text)) {
            return [$field => (int) $text];
        }

        return [];
    }
}
