<?php

fscanf(STDIN, "%d", $N);
$mint = false;
for ($i = 0; $i < $N; $i++)
{
    fscanf(STDIN, "%s", $t);
    if (false !== $mint) {
        $mint = ($mint < $t) ? $mint : $t;
    } else {
        $mint = $t;
    }
}

echo($mint . "\n");
