<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

// Write an action using echo(). DON'T FORGET THE TRAILING \n
// To debug (equivalent to var_dump): error_log(var_export($var, true));

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

fscanf(STDIN, "%d %d %d",
    $nbPlaces,
    $nbTurnsForDay,
    $nbGroups
);
$groups = array();
for ($i = 0; $i < $nbGroups; $i++)
{
    fscanf(STDIN, "%d",
        $Pi
    );
    $groups[] = $Pi;
}
debug($nbPlaces);
debug($nbTurnsForDay);
debug($nbGroups);
//debug($groups);

$nbDirhams = 0;
$pos = 0;
while ($nbTurnsForDay !== 0) {
    debug('Still ' . $nbTurnsForDay . " turns", true);
    $nbPlacesLeft = $nbPlaces;
    $runningGroups = array();
    while (true) {
        $nbPersons = isset($groups[$pos]) ? $groups[$pos] : false;
        debug($nbPersons);
        if (false === $nbPersons || $nbPersons > $nbPlacesLeft) {
            break;
        }
        unset($groups[$pos++]);
        $nbPlacesLeft -= $nbPersons;
        $runningGroups[] = $nbPersons;
    }
    debug($runningGroups);

    //$groups = array_merge($groups, $runningGroups);
    foreach ($runningGroups as $group) {
        $groups[] = $group;
    }
    unset($runningGroups);
    debug($groups);

    $nbDirhams += ($nbPlaces - $nbPlacesLeft);
    $nbTurnsForDay--;
}

echo $nbDirhams . "\n";