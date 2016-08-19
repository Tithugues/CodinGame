<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

// Write an action using echo(). DON'T FORGET THE TRAILING \n
// To debug (equivalent to var_dump): error_log(var_export($var, true));

fscanf(STDIN, "%d %d",
    $W, // width of the building.
    $H // height of the building.
);
fscanf(STDIN, "%d",
    $N // maximum number of turns before game over.
);
fscanf(STDIN, "%d %d",
    $X, //Start X
    $Y  //Start Y
);

$minX = 0;
$maxX = $W;
$minY = 0;
$maxY = $H;

// game loop
while (TRUE)
{
    fscanf(STDIN, "%s",
        $BOMB_DIR // the direction of the bombs from batman's current location (U, UR, R, DR, D, DL, L or UL)
    );

    for ($i = strlen($BOMB_DIR) - 1; $i >= 0; $i--) {
        switch ($BOMB_DIR[$i]) {
            case 'U':
                $maxY = $Y - 1;
                $Y = floor(($maxY + $minY)/2);
                break;
            case 'D':
                $minY = $Y + 1;
                $Y = floor(($maxY + $minY)/2);
                break;
            case 'L':
                $maxX = $X - 1;
                $X = floor(($maxX + $minX)/2);
                break;
            case 'R':
                $minX = $X + 1;
                $X = floor(($maxX + $minX)/2);
                break;
        }
    }

    echo($X . ' ' . $Y . "\n"); // the location of the next window Batman should jump to.
}
?>