<?php
// Read inputs from STDIN. Print outputs to STDOUT.

fscanf(STDIN, "%d", $n);
$links = array();
for ($i = 0; $i < $n; $i++)
{
    fscanf(STDIN, "%d %d",
        $origin,
        $dest
    );

    $links[$origin][$dest] = 1;
}

$origins = array();
$keys = array_keys($links);
foreach ($keys as $key) {
    $origin = true;
    foreach ($links as $link)  {
        if (isset($link[$key])) {
            $origin = false;
            break;
        }
    }
    if ($origin) {
        $origins[] = $key;
    }
}

$nb = 0;
while (!empty($origins)) {
    error_log(var_export($origins, true));
    $children = array();
    foreach ($origins as $origin) {
        if (isset($links[$origin])) {
            $children = array_merge($children, array_keys($links[$origin]));
        }
    }
    $origins = $children;
    $nb++;
}

echo $nb . "\n";