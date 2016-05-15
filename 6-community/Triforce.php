<?php

class TriforceGenerator
{
    /**
     * @param int $triangleHeight
     *
     * @return string
     */
    public function generate($triangleHeight)
    {
        $totalHeight = $triangleHeight * 2;
        $totalWidth = $totalHeight * 2 - 1;

        //Initialise triforce array.
        $triforce = [];
        for ($i = 0; $i < $totalHeight; ++$i) {
            $triforce[$i] = str_pad(null, $totalWidth, ' ');
        }
        $triforce[0] = substr_replace($triforce[0], '.', 0, 1);

        //Initialise first triangle.
        for ($i = 0, $mid = ceil($totalWidth / 2) - 1; $i < $triangleHeight; ++$i) {
            for ($j = $mid - $i, $jMax = $mid + $i; $j <= $jMax; ++$j) {
                $triforce[$i] = substr_replace($triforce[$i], '*', $j, 1);
            }
        }

        //Initialise second and third triangles.
        for ($i = $triangleHeight, $mid = ceil($totalWidth / 2) - 1; $i < $totalHeight; ++$i) {
            $top2 = floor($mid / 2);
            for (
                $j = $top2 - ($i - $triangleHeight), $jMax = $top2 + ($i - $triangleHeight);
                $j <= $jMax;
                ++$j
            ) {
                $triforce[$i] = substr_replace($triforce[$i], '*', $j, 1);
            }
            $top3 = $mid * 2 - $top2;
            for (
                $j = $top3 - ($i - $triangleHeight), $jMax = $top3 + ($i - $triangleHeight);
                $j <= $jMax;
                ++$j
            ) {
                $triforce[$i] = substr_replace($triforce[$i], '*', $j, 1);
            }
        }

        for ($i = 0; $i < $totalHeight; ++$i) {
            $triforce[$i] = rtrim($triforce[$i]);
        }

        return implode("\n", $triforce);
    }
}

fscanf(STDIN, "%d", $N);

$triforce = new TriforceGenerator();

echo($triforce->generate($N) . "\n");
