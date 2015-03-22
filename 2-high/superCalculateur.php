<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

fscanf(STDIN, "%d", $nbCalcs);
$calcs = array();
for ($i = 0; $i < $nbCalcs; $i++) {
    fscanf(STDIN, "%d %d", $start, $length);
    //Keep shorter task.
    if (!isset($calcs[$start]) || $length < $calcs[$start]) {
        $calcs[$start] = $length;
    }
}

//Revert calcs.
krsort($calcs);
debug($calcs, true);

//Read from end to start
foreach ($calcs as $start => $length) {
    $remove = false;
    //Check if the current task is conflicting another task after it.
    for ($i = $start + 1; $i < $start + $length; ++$i) {
        if (isset($calcs[$i])) {
            $remove = true;
            break;
        }
    }
    //If it is, remove the current task.
    if ($remove) {
        unset($calcs[$start]);
    }
}

echo count($calcs) . "\n";
