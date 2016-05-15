<?php

fscanf(STDIN, "%d", $N);

echo(pow($N, 3) - pow(max($N - 2, 0), 3) . "\n");
