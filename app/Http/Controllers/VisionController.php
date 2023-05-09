<?php

namespace App\Http\Controllers;

use Google\Cloud\Core\Exception\GoogleException;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;

class VisionController extends Controller
{

    public function test(): array|string
    {
        try {
            putenv("GOOGLE_APPLICATION_CREDENTIALS=" . base_path("gc_config.json"));

            $result = [];

            $exampleFilePath = base_path("example4.jpeg");
            if (file_exists($exampleFilePath)) {
                $image = file_get_contents($exampleFilePath);
                $imageAnnotator = new ImageAnnotatorClient();
                $response = $imageAnnotator->textDetection($image);
                $texts = $response->getTextAnnotations();
                foreach ($texts as $key => $text) {
                    if ($key == 0) {
                        continue;
                    }
                    $entry = [];

                    $vertices = $text->getBoundingPoly()->getVertices();
                    foreach ($vertices as $vertex) {
                        $entry[] = [$vertex->getX(), $vertex->getY()];
                    }
                    $entry[] = $text->getDescription();
                    $result[] = $entry;
                }
                $imageAnnotator->close();

                $threshold = $this->calculateThresholdValue($result, 3);

                $leftCluster = array();
                $middleCluster = array();
                $rightCluster = array();

                foreach ($result as $coord) {
                    $avg = ($coord[0][0] + $coord[1][0] + $coord[2][0] + $coord[3][0]) / 4;
                    if ($avg < $threshold) {
                        $leftCluster[] = $coord;
                    } else if ($avg < ($threshold * 2)) {
                        $middleCluster[] = $coord;
                    } else {
                        $rightCluster[] = $coord;
                    }
                }

                $sortedLeftCluster = $this->mostLikelyOrder($leftCluster);
                $sortedMiddleCluster = $this->mostLikelyOrder($middleCluster);
                $sortedRightCluster = $this->mostLikelyOrder($rightCluster);

                $mergeSides = [$sortedMiddleCluster[0]]; // push result
                for ($i = 0; $i < max(count($sortedLeftCluster), count($sortedRightCluster)); $i++) {
                    if ($i < count($sortedLeftCluster)) {
                        $mergeSides[] = $sortedLeftCluster[$i];
                    }
                    if ($i < count($sortedRightCluster)) {
                        $mergeSides[] = $sortedRightCluster[$i];
                    }
                }

                return $this->fieldPatternRecognition($mergeSides);
            }
        } catch (GoogleException $googleException) {
            return "Received exception: " . $googleException->getMessage();
        }

        return "Something went wrong";
    }

    /**
     * Calculates threshold value according to X axis
     * @param array $arr
     * @param int $k Number of parts
     * @return float
     */
    private function calculateThresholdValue(array $arr, int $k): float {
        $threshold = 500;

        $min = null;
        $max = null;
        foreach ($arr as $r) {
            if ($min == null && $max == null) {
                $min = $r[0][0];
                $max = $r[0][0];
            }
            for ($i = 0; $i < 4; $i++) {
                if ($r[$i][0] < $min) {
                    $min = $r[$i][0];
                }
                if ($r[$i][0] > $max) {
                    $max = $r[$i][0];
                }
            }
        }
        if ($min != null && $max != null) {
            $threshold = ($max + $min) / $k;
        }
        return $threshold;
    }


    /**
     * Returns the order similarity between two arrays
     *
     * @param $a
     * @param $b
     * @return float
     */
    private function getOrderSimilarity($a, $b): float
    {
        $numMatches = 0;
        $n = count($a);
        for ($i = 0; $i < $n; $i++) {
            if ($a[$i] == $b[$i]) {
                $numMatches++;
            }
        }
        return $numMatches / $n;
    }

    /**
     * Calculates the order of all area points and returns
     * the most similar amongst each other
     *
     * @param array $result
     * @return array
     */
    private
    function mostLikelyOrder(array $result): array
    {
        $sorts = [];

        $x1y1Sort = $result;
        $x2y2Sort = $result;
        $x3y3Sort = $result;
        $x4y4Sort = $result;

        usort($x1y1Sort, function ($a, $b) {
            return [$a[0][1], $a[0][0]] <=> [$b[0][1], $b[0][0]];
        });
        $sorts[] = array_column($x1y1Sort, 4);

        usort($x2y2Sort, function ($a, $b) {
            return [$a[1][1], $a[1][0]] <=> [$b[1][1], $b[1][0]];
        });
        $sorts[] = array_column($x2y2Sort, 4);

        usort($x3y3Sort, function ($a, $b) {
            return [$a[2][1], $a[2][0]] <=> [$b[2][1], $b[2][0]];
        });
        $sorts[] = array_column($x3y3Sort, 4);

        usort($x4y4Sort, function ($a, $b) {
            return [$a[3][1], $a[3][0]] <=> [$b[3][1], $b[3][0]];
        });
        $sorts[] = array_column($x4y4Sort, 4);

        $similarityMatrix = array();
        foreach ($sorts as $i => $arr1) {
            $row = array();
            foreach ($sorts as $j => $arr2) {
                if ($i == $j) {
                    $row[] = 1.0;  // similarity score of an array with itself is 1.0
                } else {
                    $row[] = $this->getOrderSimilarity($arr1, $arr2);
                }
            }
            $similarityMatrix[] = $row;
        }
        $avgSimilarities = array_map(function ($row) {
            return array_sum($row) / count($row);
        }, $similarityMatrix);
        $mostSimilarIndex = array_keys($avgSimilarities, max($avgSimilarities))[0];
        return $sorts[$mostSimilarIndex];
    }

    private function fieldPatternRecognition(array $arr): array
    {
        $res = [];
        $elements = count($arr);
        $replacements = ["%", "\n", "\r", "\r\n", " "];
        $index = 0;

        if (preg_match('/\d\s?-\s?\d/', $arr[$index])) {
            $res['RESULT'] = str_replace($replacements, "", $arr[$index]);
        }

        if (preg_match('/[A-Z]{3}/', $arr[$index + 1])) {
            $res['AWAY_TEAM'] = str_replace($replacements, "", $arr[$index + 1]);
        }

        if ($index + 1 < $elements && preg_match('/[A-Z]{3}/', $arr[$index + 2])) {
            $res['HOME_TEAM'] = str_replace($replacements, "", $arr[$index + 2]);
        }

        if ($index + 2 < $elements && preg_match('/\d+/', $arr[$index + 3])) {
            $res['SHOTS_AWAY'] = str_replace($replacements, "", $arr[$index + 3]);
        }

        $shotsHomeIndex = $this->getNext($index + 4, $arr, '/\d+/');
        if ($shotsHomeIndex === false) {
            return $res;
        }
        $res['SHOTS_HOME'] = str_replace($replacements, "", $arr[$shotsHomeIndex]);

        $hitsAwayIndex = $this->getNext($shotsHomeIndex + 1, $arr, '/\d+/');
        if ($hitsAwayIndex === false) {
            return $res;
        }
        $res['HITS_AWAY'] = str_replace($replacements, "", $arr[$hitsAwayIndex]);

        $hitsHomeIndex = $this->getNext($hitsAwayIndex + 1, $arr, '/\d+/');
        if ($hitsHomeIndex === false) {
            return $res;
        }
        $res['HITS_HOME'] = str_replace($replacements, "", $arr[$hitsHomeIndex]);

        $awayTimeOnAttackIndex = $this->getNext($hitsHomeIndex + 1, $arr, '/\d{2}:\d{2}/');
        if ($awayTimeOnAttackIndex === false) {
            return $res;
        }
        $res['TIME_ON_ATTACK_AWAY'] = str_replace($replacements, "", $arr[$awayTimeOnAttackIndex]);

        $homeTimeOnAttackIndex = $this->getNext($awayTimeOnAttackIndex + 1, $arr, '/\d{2}:\d{2}/');
        if ($homeTimeOnAttackIndex === false) {
            return $res;
        }
        $res['TIME_ON_ATTACK_HOME'] = str_replace($replacements, "", $arr[$homeTimeOnAttackIndex]);

        $passPercentageAwayIndex = $this->getNext($homeTimeOnAttackIndex + 1, $arr, '/\d{2}(?:\.\d)?/');
        if ($passPercentageAwayIndex === false) {
            return $res;
        }
        $res['PASS_PERCENTAGE_AWAY'] = str_replace($replacements, '', $arr[$passPercentageAwayIndex]);

        $passPercentageHomeIndex = $this->getNext($passPercentageAwayIndex + 1, $arr, '/\d{2}(?:\.\d)?/');
        if ($passPercentageHomeIndex === false) {
            return $res;
        }
        $res['PASS_PERCENTAGE_HOME'] = str_replace($replacements, '', $arr[$passPercentageHomeIndex]);

        $faceoffsAwayIndex = $this->getNext($passPercentageHomeIndex + 1, $arr, '/\d+/');
        if ($faceoffsAwayIndex === false) {
            return $res;
        }
        $res['FACEOFFS_AWAY'] = str_replace($replacements, "", $arr[$faceoffsAwayIndex]);

        $faceoffsHomeIndex = $this->getNext($faceoffsAwayIndex + 1, $arr, '/\d+/');
        if ($faceoffsHomeIndex === false) {
            return $res;
        }
        $res['FACEOFFS_HOME'] = str_replace($replacements, "", $arr[$faceoffsHomeIndex]);

        $awayPenaltyMinutesIndex = $this->getNext($faceoffsHomeIndex + 1, $arr, '/\d{2}:\d{2}/');
        if ($awayPenaltyMinutesIndex === false) {
            return $res;
        }
        $res['PENALTY_MINUTES_AWAY'] = str_replace($replacements, "", $arr[$awayPenaltyMinutesIndex]);

        $homePenaltyMinutesIndex = $this->getNext($awayPenaltyMinutesIndex + 1, $arr, '/\d{2}:\d{2}/');
        if ($homePenaltyMinutesIndex === false) {
            return $res;
        }
        $res['PENALTY_MINUTES_HOME'] = str_replace($replacements, "", $arr[$homePenaltyMinutesIndex]);

        $powerplaysAwayIndex = $this->getNext($homePenaltyMinutesIndex + 1, $arr, '/\d[\\\\\/]+\d/');
        if ($powerplaysAwayIndex === false) {
            return $res;
        }
        $res['POWERPLAYS_AWAY'] = str_replace($replacements, "", $arr[$powerplaysAwayIndex]);

        $powerplaysHomeIndex = $this->getNext($powerplaysAwayIndex + 1, $arr, '/\d[\\\\\/]+\d/');
        if ($powerplaysHomeIndex === false) {
            return $res;
        }
        $res['POWERPLAYS_HOME'] = str_replace($replacements, "", $arr[$powerplaysHomeIndex]);

        $awayPowerplayMinutesIndex = $this->getNext($powerplaysHomeIndex + 1, $arr, '/\d{2}:\d{2}/');
        if ($awayPowerplayMinutesIndex === false) {
            return $res;
        }
        $res['POWERPLAY_MINUTES_AWAY'] = str_replace($replacements, "", $arr[$awayPowerplayMinutesIndex]);

        $homePowerplayMinutesIndex = $this->getNext($awayPowerplayMinutesIndex + 1, $arr, '/\d{2}:\d{2}/');
        if ($homePowerplayMinutesIndex === false) {
            return $res;
        }
        $res['POWERPLAY_MINUTES_HOME'] = str_replace($replacements, "", $arr[$homePowerplayMinutesIndex]);

        $shortHandedGoalsAwayIndex = $this->getNext($homePowerplayMinutesIndex + 1, $arr, '/\d+/');
        if ($shortHandedGoalsAwayIndex === false) {
            return $res;
        }
        $res['SHORTHANDED_GOALS_AWAY'] = str_replace($replacements, "", $arr[$shortHandedGoalsAwayIndex]);

        $shortHandedGoalsHomeIndex = $this->getNext($shortHandedGoalsAwayIndex + 1, $arr, '/\d+/');
        if ($shortHandedGoalsHomeIndex === false) {
            return $res;
        }
        $res['SHORTHANDED_GOALS_HOME'] = str_replace($replacements, "", $arr[$shortHandedGoalsHomeIndex]);

        return $res;
    }

    /**
     * Returns the index of the element that matched the given pattern in the array
     *
     * @param int $startIndex
     * @param array $arr
     * @param $pattern
     * @return bool|int
     */
    private function getNext(int $startIndex, array $arr, $pattern): bool|int
    {
        for ($i = $startIndex; $i < count($arr); $i++) {
            if (preg_match($pattern, $arr[$i])) {
                return $i;
            }
        }
        return false;
    }
}
