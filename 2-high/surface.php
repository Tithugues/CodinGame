<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

fscanf(STDIN, "%d", $width);
fscanf(STDIN, "%d", $height);
$map = array();
for ($rowId = 0; $rowId < $height; $rowId++)
{
    fscanf(STDIN, "%s", $line);
    $map[] = $line;
}

fscanf(STDIN, "%d", $numberCoordinates);
$searchedLakes = array();
for ($rowId = 0; $rowId < $numberCoordinates; $rowId++)
{
    fscanf(STDIN, "%d %d", $x, $y);
    $searchedLakes[] = array('x' => $x, 'y' => $y);
}

function foundUnreadWatersClosedTo($map, &$read, $x, $y, $lakeId) {
    debug('foundUnreadWatersClosedTo: ' . $x . '-' . $y);
    $waters = array();
    if (
        isset($map[$y]{$x-1})
        && 'O' === $map[$y]{$x-1}
        && !isset($read[$x-1][$y])
    ) {
        $read[$x-1][$y] = $lakeId;
        $waters[] = array('x' => $x-1, 'y' => $y);
    }
    if (
        isset($map[$y]{$x+1})
        && 'O' === $map[$y]{$x+1}
        && !isset($read[$x+1][$y])
    ) {
        $read[$x+1][$y] = $lakeId;
        $waters[] = array('x' => $x+1, 'y' => $y);
    }
    if (
        isset($map[$y-1]{$x})
        && 'O' === $map[$y-1]{$x}
        && !isset($read[$x][$y-1])
    ) {
        $read[$x][$y-1] = $lakeId;
        $waters[] = array('x' => $x, 'y' => $y-1);
    }
    if (
        isset($map[$y+1]{$x})
        && 'O' === $map[$y+1]{$x}
        && !isset($read[$x][$y+1])
    ) {
        debug('ajouté');
        $read[$x][$y+1] = $lakeId;
        $waters[] = array('x' => $x, 'y' => $y+1);
    }
    debug($waters);
    return $waters;
}

function exploreLake($map, &$read, $x, $y, $lakeId) {
    $stack = array(array('x' => $x, 'y' => $y));

    $surface = 0;

    while (!empty($stack)) {
        $node = array_shift($stack);
        debug('while: ' . $node['x'] . '-' . $node['y']);

        $read[$node['x']][$node['y']] = $lakeId;
        ++$surface;
        $stack = array_merge($stack, foundUnreadWatersClosedTo($map, $read, $node['x'], $node['y'], $lakeId));
    }

    return $surface;
}

$read = array();
$lakes = array();

foreach ($searchedLakes as $searchedLake) {
    debug('Search for lake in ' . $searchedLake['x'] . '-' . $searchedLake['y']);
    if ('#' === $map[$searchedLake['y']]{$searchedLake['x']}) {
        echo "0\n";
        continue;
    }

    if (isset($read[$searchedLake['x']][$searchedLake['y']])) {
        echo $lakes[$read[$searchedLake['x']][$searchedLake['y']]] . "\n";
        continue;
    }

    $lakeId = count($lakes);
    $lakes[$lakeId] = exploreLake($map, $read, $searchedLake['x'], $searchedLake['y'], $lakeId);
    echo $lakes[$lakeId] . "\n";
}