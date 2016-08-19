<?php
/**
 * Don't let the machines win. You are humanity's last hope...
 **/

define('DEBUG', false);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

fscanf(STDIN, "%d", $width); // the number of cells on the X axis
fscanf(STDIN, "%d", $height); // the number of cells on the Y axis
$lines = array();
for ($rowId = 0; $rowId < $height; ++$rowId) {
    $lines[] = stream_get_line(STDIN, 31, "\n"); // width characters, each either 0 or .
}

foreach ($lines as $rowId => $line) {
    for ($colId = 0; $colId < $width; ++$colId) {
        if ('.' === $line{$colId}) {
            continue;
        }

        //Find right node.
        $rightColId = strpos($line, '0', $colId + 1);
        if (false === $rightColId) {
            $rightNode = '-1 -1';
        } else {
            $rightNode = $rightColId . ' ' . $rowId;
        }

        $bottomRowId = false;
        for ($i = $rowId + 1; $i < $height; ++$i) {
            if ('.' === $lines[$i]{$colId}) {
                continue;
            }

            $bottomRowId = $i;
            break;
        }
        if (false === $bottomRowId) {
            $bottomNode = '-1 -1';
        } else {
            $bottomNode = $colId . ' ' . $bottomRowId;
        }

        echo $colId . ' ' . $rowId . ' ' . $rightNode . ' ' . $bottomNode . "\n";
    }
}
