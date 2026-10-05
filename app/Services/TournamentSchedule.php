<?php

namespace App\Services;

use InvalidArgumentException;

class TournamentSchedule
{
    /** Every player gets exactly $games games; opponent counts differ by at most one. */
    public function generate(array $players, int $games, int $rounds): array
    {
        $players = array_values(array_unique(array_map('intval', $players)));
        $n = count($players);
        if ($n < 2 || $games < 1 || $games > 100 || ($n * $games) % 2 !== 0 ||
            $rounds < 1 || $rounds > intdiv($n * $games, 2)) {
            throw new InvalidArgumentException('Choose at least two players, an even total of player appearances, and at least one game per round.');
        }
        shuffle($players);
        $roundByEdge = [];
        $perRound = intdiv($games, $rounds);
        if ($games % $rounds === 0 && ($n * $perRound) % 2 === 0) {
            // Build regular factors first. Splitting shuffled individual games
            // only balances round sizes, not each player's appearances.
            $factors = [];
            $degree = $n % 2 === 0 ? 1 : 2;
            if ($degree === 1) {
                $rotation = $players;
                for ($step = 0; $step < $n - 1; $step++) {
                    $factor = [];
                    for ($i = 0; $i < $n / 2; $i++) {
                        $factor[] = [$rotation[$i], $rotation[$n - 1 - $i]];
                    }
                    $factors[] = $factor;
                    $last = array_pop($rotation);
                    array_splice($rotation, 1, 0, [$last]);
                }
            } else {
                for ($distance = 1; $distance <= intdiv($n, 2); $distance++) {
                    $factor = [];
                    for ($i = 0; $i < $n; $i++) {
                        $factor[] = [$players[$i], $players[($i + $distance) % $n]];
                    }
                    $factors[] = $factor;
                }
            }
            shuffle($factors);
            $edges = [];
            for ($i = 0; $i < intdiv($games, $degree); $i++) {
                foreach ($factors[$i % count($factors)] as $edge) {
                    $roundByEdge[] = intdiv($i, intdiv($perRound, $degree)) + 1;
                    $edges[] = $edge;
                }
            }
        } else {
            $edges = [];
            for ($cycle = 0; $cycle < intdiv($games, $n - 1); $cycle++) {
                for ($i = 0; $i < $n; $i++) {
                    for ($j = $i + 1; $j < $n; $j++) {
                        $edges[] = [$players[$i], $players[$j]];
                    }
                }
            }
            $remaining = $games % ($n - 1);
            for ($distance = 1; $distance <= intdiv($remaining, 2); $distance++) {
                for ($i = 0; $i < $n; $i++) {
                    $edges[] = [$players[$i], $players[($i + $distance) % $n]];
                }
            }
            if ($remaining % 2 === 1) {
                for ($i = 0; $i < $n / 2; $i++) {
                    $edges[] = [$players[$i], $players[$i + $n / 2]];
                }
            }
            shuffle($edges);
        }

        // Euler orientation balances each vertex, including partial round-robin cycles.
        $adjacency = [];
        foreach ($edges as $id => [$a, $b]) {
            $adjacency[$a][] = [$id, $b];
            $adjacency[$b][] = [$id, $a];
        }
        $edgeCount = count($edges);
        $dummy = -1;
        $nextId = $edgeCount;
        foreach ($players as $player) {
            if (count($adjacency[$player]) % 2 === 1) {
                $adjacency[$player][] = [$nextId, $dummy];
                $adjacency[$dummy][] = [$nextId++, $player];
            }
        }
        $used = [];
        $oriented = [];
        foreach (array_keys($adjacency) as $start) {
            $stack = [$start];
            while ($stack !== []) {
                $a = end($stack);
                if (empty($adjacency[$a])) {
                    array_pop($stack);

                    continue;
                }
                [$id, $b] = array_pop($adjacency[$a]);
                if (isset($used[$id])) {
                    continue;
                }
                $used[$id] = true;
                if ($id < $edgeCount) {
                    $oriented[$id] = [$a, $b];
                }
                $stack[] = $b;
            }
        }
        ksort($oriented);
        $result = [];
        foreach (array_values($oriented) as $i => [$home, $away]) {
            $result[] = [
                'round' => $roundByEdge[$i] ?? (intdiv($i * $rounds, $edgeCount) + 1),
                'home_user_id' => $home,
                'away_user_id' => $away,
                'home_team_id' => null,
                'away_team_id' => null,
            ];
        }

        return $result;
    }
}
