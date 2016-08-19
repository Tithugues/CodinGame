<?php
// A single line containing one of 4 keywords: SPEED, SLOW, JUMP, WAIT.

fscanf(STDIN, "%d",
    $R // the length of the road before the gap.
);
fscanf(STDIN, "%d",
    $G // the length of the gap.
);
fscanf(STDIN, "%d",
    $L // the length of the landing platform.
);

// game loop
while (TRUE)
{
    fscanf(STDIN, "%d",
        $S // the motorbike's speed.
    );
    fscanf(STDIN, "%d",
        $X // the position on the road of the motorbike.
    );
    error_log(var_export($R, true));
    error_log(var_export($G, true));
    error_log(var_export($L, true));
    error_log(var_export($S, true));
    error_log(var_export($X, true));

    //If gap is behind us.
    if ($X >= $R + $G) {
        echo("SLOW\n");
        continue;
    }

    //If after next move, we'll be into the gap, jump!
    if ($X + $S > $R) {
        echo("JUMP\n");
        continue;
    }

    //If still before the gap and not fast enough.
    if ($S < $G + 1) {
        echo("SPEED\n");
        continue;
    }

    //If still before the gap and to fast.
    if ($S > $G + 1) {
        echo("SLOW\n");
        continue;
    }

    //If still before the gap and at
    //if ($S + $X > $R + G)

    echo("WAIT\n");
    continue;
}
