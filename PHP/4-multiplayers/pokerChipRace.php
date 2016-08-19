<?php
/**
 * It's the survival of the biggest!
 * Propel your chips across a frictionless table top to avoid getting eaten by bigger foes.
 * Aim for smaller oil droplets for an easy size boost.
 * Tip: merging your chips will give you a sizeable advantage.
 **/

define('DEBUG', false);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

// your id (0 to 4)
fscanf(STDIN, "%d", $playerId);

// game loop
while (TRUE) {
    // The number of chips under your control
    fscanf(STDIN, "%d", $playerChipCount);
    // The total number of entities on the table, including your chips
    fscanf(STDIN, "%d", $entityCount);
    for ($i = 0; $i < $entityCount; ++$i) {
        fscanf(STDIN, "%d %d %f %f %f %f %f",
            $id, // Unique identifier for this entity
            $player, // The owner of this entity (-1 for neutral droplets)
            $radius, // the radius of this entity
            $x, // the X coordinate (0 to 799)
            $y, // the Y coordinate (0 to 514)
            $vx, // the speed of this entity along the X axis
            $vy // the speed of this entity along the Y axis
        );
    }
    for ($i = 0; $i < $playerChipCount; $i++) {

        // Write an action using echo(). DON'T FORGET THE TRAILING \n
        // To debug (equivalent to var_dump): error_log(var_export($var, true));

        echo("0 0\n"); // One instruction per chip: 2 real numbers (x y) for a propulsion, or 'WAIT'.
    }
}
