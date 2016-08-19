<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

define('DEBUG', true);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

fscanf(STDIN, "%d",
    $N
);
$powers = array();
for ($i = 0; $i < $N; $i++)
{
    fscanf(STDIN, "%d",
        $Pi
    );

    if (isset($powers[$Pi])) {
        echo "0\n";
        exit;
    }

    $powers[$Pi] = true;
}

ksort($powers);

$prev = false;
$minDiff = false;
foreach ($powers as $power => $tmp) {
    if (false === $prev) {
        $prev = $power;
        continue;
    }

    $currDiff = $power - $prev;
    if (false === $minDiff || $currDiff < $minDiff) {
        $minDiff = $currDiff;
    }

    $prev = $power;
}

echo $minDiff . "\n";
