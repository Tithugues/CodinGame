<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

$nbPlaces = false;
$nbTurnsPerDay = false;
$nbGroups = false;
fscanf(STDIN, "%d %d %d", $nbPlaces, $nbTurnsPerDay, $nbGroups);
debug('Nb places: ' . $nbPlaces);
debug($nbTurnsPerDay);
debug($nbGroups);

$groups = array();
for ($i = 0; $i < $nbGroups; $i++)
{
    fscanf(STDIN, "%d", $Pi);
    $groups[] = array('size' => $Pi);
}

$nbDirhams = 0;
$pos = 0;
$nbGroupsPassed = 0;
$nbTurns = $nbTurnsPerDay;
$cycled = false;
while (0 < $nbTurns) {
    debug('Still ' . $nbTurns . " turns", true);
    debug($groups);
    $runningGroups = array();
    $curGroup = array_shift($groups);
    if (!$cycled && isset($curGroup['firstTurn'])) {
        $cycled = true;
        $diffTurns = $curGroup['firstTurn'] - $nbTurns;
        $diffEarned = $nbDirhams - $curGroup['earned'];
        $remainingCycles = floor($nbTurns / $diffTurns);
        $nbDirhams = $diffEarned * ($remainingCycles + 1) + $curGroup['earned'];
        $nbTurns %= $diffTurns;
        if (0 === $nbTurns) {
            break;
        }
    } else {
        $curGroup['firstTurn'] = $nbTurns;
        $curGroup['earned'] = $nbDirhams;
    }

    $curNbPersons = $curGroup['size'];
    $runningGroups[] = $curGroup;

    //Try to add groups to current turn.
    while ($curNbPersons < $nbPlaces && !empty($groups)) {
        $curGroup = array_shift($groups);
        debug('Try to add');
        debug($curGroup);
        if ($curNbPersons + $curGroup['size'] > $nbPlaces) {
            array_unshift($groups, $curGroup);
            break;
        }
        $curNbPersons += $curGroup['size'];
        $runningGroups[] = $curGroup;
    }

    debug($groups);

    $groups = array_merge($groups, $runningGroups);
    $nbDirhams += $curNbPersons;

    --$nbTurns;
}

echo $nbDirhams . "\n";