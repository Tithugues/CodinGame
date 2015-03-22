<?php

//$data = array(0 => array(0), 1 => array(1), 2 => array(2));
//$data = array(0 => array(0), 1 => array(2), 2 => array(2));
//$data = array(0 => array(0), 1 => array(2, 3), 2 => array(2));
//$data = array(1 => array(1));
$data = array(0 => array(0), 1 => array(2), 2 => array(2), 5 => array(3));

function calculateMediane($data) {
    $allRows = array();
    foreach ($data as $column) {
        foreach ($column as $row) {
            $allRows[] = $row;
        }
    }

    sort($allRows);
    $nbHouses = count($allRows);
    $medianeRank = ceil($nbHouses / 2) - 1;
    $position = $allRows[$medianeRank];

    return $position;
}

function calculateLength($data, $position = null) {
    if (null === $position) {
        $position = calculateMediane($data);
    }

    $keys = array_keys($data);
    sort($keys);

    $length = $keys[count($keys) - 1] - $keys[0];

    foreach ($data as $column) {
        foreach ($column as $row) {
            $length += abs($position - $row);
        }
    }

    return $length;
}

$position = calculateMediane($data);
$length = calculateLength($data, $position);

echo 'Position: ' . $position . "<br />\n";
echo 'Length: ' . $length . "<br />\n";
