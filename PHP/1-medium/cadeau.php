<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

fscanf(STDIN, "%d",
    $N
);
fscanf(STDIN, "%d",
    $C
);
$budgets = array();
for ($i = 0; $i < $N; $i++)
{
    fscanf(STDIN, "%d",
        $B
    );
    $budgets[] = $B;
}

rsort($budgets);

// Write an action using echo(). DON'T FORGET THE TRAILING \n
// To debug (equivalent to var_dump): error_log(var_export($var, true));

//"Reste à payer"
$remainingBalance = $C;
$participations = array();
for ($i = $N - 1; $i >= 0; $i--) {
    $average = floor($remainingBalance / ($i + 1));
    if ($budgets[$i] <= $average) {
        $participation = $budgets[$i];
    } else {
        $participation = $average;
    }

    $participations[] = $participation;
    $remainingBalance -= $participation;
}

if ($remainingBalance !== 0) {
    echo("IMPOSSIBLE\n");
} else {
    foreach ($participations as $participation) {
        echo($participation . "\n");
    }
}

?>