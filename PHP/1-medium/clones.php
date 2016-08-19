<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

// Write an action using echo(). DON'T FORGET THE TRAILING \n
// To debug (equivalent to var_dump): error_log(var_export($var, true));

fscanf(STDIN, "%d %d %d %d %d %d %d %d",
    $nbFloors, // number of floors
    $width, // width of the area
    $nbRounds, // maximum number of rounds
    $exitFloor, // floor on which the exit is found
    $exitPos, // position of the exit on its floor
    $nbTotalClones, // number of generated clones
    $nbAdditionalElevators, // ignore (always zero)
    $nbElevators // number of elevators
);
$elevators = array();
for ($i = 0; $i < $nbElevators; $i++)
{
    fscanf(STDIN, "%d %d",
        $elevatorFloor, // floor on which this elevator is found
        $elevatorPos // position of the elevator on its floor
    );
    if (!isset($elevators[$elevatorFloor])) {
        $elevators[$elevatorFloor] = array();
    }
    $elevators[$elevatorFloor][] = $elevatorPos;
}

$clonesBlocked = array();
// game loop
while (TRUE)
{
    fscanf(STDIN, "%d %d %s",
        $cloneFloor, // floor of the leading clone
        $clonePos, // position of the leading clone on its floor
        $direction // direction of the leading clone: LEFT or RIGHT
    );

    error_log(var_export('START', true));
    error_log(var_export($cloneFloor, true));

    if (-1 === $cloneFloor) {
        echo "WAIT\n";
        continue;
    }

    if ($cloneFloor === $exitFloor) {
        $elevatorPos = $exitPos;
    } else {
        $elevatorPos = $elevators[$cloneFloor][0];
    }

    error_log(var_export($clonePos, true));
    error_log(var_export($elevatorPos, true));
    error_log(var_export($direction, true));
    if (($clonePos >= $elevatorPos && 'LEFT' === $direction) || ($clonePos <= $elevatorPos && 'RIGHT' === $direction)) {
        echo "WAIT\n";
        continue;
    }

    echo("BLOCK\n"); // action: WAIT or BLOCK
}