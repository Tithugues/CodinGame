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

function initGroups(&$groups, $pos) {
    debug('Init group: ' . $pos);
    $groups[$pos] = array(
        'sizes' => array(),
        'sum' => 0,
        'nb' => 0
    );
}

function addGroup(&$groups, $group, &$maxIndex, $nbPlaces) {
    debug('addGroup: ' . $group);
    if (!isset($groups[$maxIndex])) {
        if (empty($groups)) {
            ++$maxIndex;
        }
        initGroups($groups, $maxIndex);
        debug($groups);
    }
    debug('Index: ' . $maxIndex);

    if ($groups[$maxIndex]['sum'] + $group > $nbPlaces) {
        initGroups($groups, ++$maxIndex);
        debug('New index: ' . $maxIndex);
    }

    $groups[$maxIndex]['sizes'][] = $group;
    $groups[$maxIndex]['sum'] += $group;
    $groups[$maxIndex]['nb']++;
    debug($groups[$maxIndex]);
}

fscanf(STDIN, "%d %d %d",
    $nbPlaces,
    $nbTurnsPerDay,
    $nbGroups
);
debug('Nb places: ' . $nbPlaces, true);
debug($nbTurnsPerDay);
debug($nbGroups);

$maxIndex = 0;
initGroups($groups, $maxIndex);
for ($i = 0; $i < $nbGroups; $i++)
{
    fscanf(STDIN, "%d",
        $Pi
    );
    addGroup($groups, $Pi, $maxIndex, $nbPlaces);
}
debug($groups);

$nbDirhams = 0;
$pos = 0;
$nbGroupsPassed = 0;
$nbTurns = $nbTurnsPerDay;
while (0 !== $nbTurns) {
    debug('Still ' . $nbTurns . " turns", true);
    debug($groups);
    $runningGroups = $groups[$pos];
    unset($groups[$pos]);
    debug($runningGroups);
    foreach ($runningGroups['sizes'] as $group) {
        addGroup($groups, $group, $maxIndex, $nbPlaces);
    }
    unset($runningGroups['sizes']);

    $nbDirhams += $runningGroups['sum'];
    $nbGroupsPassed += $runningGroups['nb'];
    unset($runningGroups);

    debug($groups);

    ++$pos;
    --$nbTurns;

    if (0 === $nbGroupsPassed%$nbGroups && 0 !== $nbTurns) {
        $oldDebug = DEBUG;
        define('DEBUG', true);
        debug('cycle');
        debug('nbGroupsPassed: '. $nbGroupsPassed);
        debug('nbGroups: '. $nbGroups);
        debug('nbTurnsPerDay: '. $nbTurnsPerDay);
        debug('nbTurns: '. $nbTurns);
        debug('nbDirhams: '. $nbTurns);
        $nbTurnsForCycle = $nbTurnsPerDay - $nbTurns;
        $ratio = (int)floor($nbTurnsPerDay / $nbTurnsForCycle);
        $nbTurns = $nbTurnsPerDay%$ratio;
        $nbDirhams *= $ratio;
        debug('nbTurnsForCycle: '. $nbTurnsForCycle);
        debug('ratio: '. $ratio);
        debug('nbTurns: '. $nbTurns);
        debug('nbDirhams: '. $nbTurns);
        define('DEBUG', $oldDebug);
    }
}

echo $nbDirhams . "\n";