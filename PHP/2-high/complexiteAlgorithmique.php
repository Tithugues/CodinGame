<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

/** @var int $duree */

fscanf(STDIN, "%d", $nombreMesures);
$mesures = array();
$min = null;
$max = null;
$minFiveFirst = null;
$maxFiveFirst = null;
for ($i = 0; $i < $nombreMesures; $i++)
{
    fscanf(STDIN, "%d %d", $numItem, $duree);
    $mesures[$numItem] = $duree;
    if (null === $min || $duree < $min) {
        $min = $duree;
    }
    if (null === $max || $max < $duree) {
        $max = $duree;
    }
    if ((null === $minFiveFirst || $duree < $minFiveFirst) && $i < 5) {
        $minFiveFirst = $duree;
    }
    if ((null === $maxFiveFirst || $maxFiveFirst < $duree) && $i < 5) {
        $maxFiveFirst = $duree;
    }
}

$maybe1 = true;
$maybelogn = true;
$mayben = true;
$maybenlogn = true;
$mayben2 = true;
$mayben2logn = true;
$mayben3 = true;
$maybe2n = true;

foreach ($mesures as $numItem => $duree) {
    if ($maybe1) {
        if ($minFiveFirst < $duree && $duree < $maxFiveFirst) {
            $maybe1 = false;
        }
    }
}

echo("answer\n");
