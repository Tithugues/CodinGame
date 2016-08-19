<?php

define('DEBUG', false);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

fscanf(STDIN, "%d", $nbOfMoto); // the amount of motorbikes to control
fscanf(STDIN, "%d", $nbOfMotoThatShouldSurvive); // the minimum amount of motorbikes that must survive
// L0 to L3 are lanes of the road. A dot character . represents a safe space, a zero 0 represents a hole in the road.
fscanf(STDIN, "%s", $L0);
fscanf(STDIN, "%s", $L1);
fscanf(STDIN, "%s", $L2);
fscanf(STDIN, "%s", $L3);

$holes = array();
for ($lineId = 0; $lineId < 4; ++$lineId) {
    $line = ${'L' . $lineId};
    $holeSize = 0;
    $lineHoles = array();
    for ($i = strlen($line) - 1; 0 <= $i; --$i) {
        if ('0' === $line{$i}) {
            ++$holeSize;
            continue;
        }

        //Here, we are not anymore on a hole.
        if (0 !== $holeSize) {
            $lineHoles[$i+1] = $holeSize;
            $holeSize = 0;
        }
    }

    $holes[$lineId] = $lineHoles;
    unset($lineHoles);
}

// game loop
while (true) {
    fscanf(STDIN, "%d", $S); // the motorbikes' speed
    $motos = array();
    for ($i = 0; $i < $nbOfMoto; $i++) {
        // x coordinate of the motorbike
        // y coordinate of the motorbike
        // indicates whether the motorbike is activated "1" or destroyed "0"
        fscanf(STDIN, "%d %d %d", $X, $Y, $active);
        if (1 !== $active) {
            continue;
        }

        $motos[$Y] = true;

    }

    // A single line containing one of 6 keywords: SPEED, SLOW, JUMP, WAIT, UP, DOWN.
    echo("SPEED\n");
}
