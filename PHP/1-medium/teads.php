<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

$debug = false;

function debug($var, $force = false) {
    if ($GLOBALS['debug'] || $force) {
        error_log(var_export($var, true));
    }
}

function getCrossings($links) {
    $crossings = array();
    foreach ($links as $node => $link) {
        if (isCrossing($node, $links)) {
            $crossings[] = $node;
        }
    }
    return $crossings;
}

function simplifyWeb($links) {
    $crossings = getCrossings($links);
    while (null !== ($node = array_shift($crossings))) {
        if (!isset($links[$node])) {
            continue;
        }
        //debug('Crossing: ' . $node);
        $r = simplifyCrossing($node, $links);
        //debug($r);
        //debug($links);
        if (isCrossing($node, $links)) {
            $crossings[] = $node;
        }
    }

    return floor(count($links) / 2);
}

function removeNode(&$links, $node, $parent) {
    //debug('ClearLinks: ' . $node . ' - parent: ' . $parent);

    $children = $links[$node];
    unset($children[$parent]);
    unset($links[$node]);
    unset($links[$parent][$node]);

    //debug($children);

    $childrenKeys = array_keys($children);
    foreach ($childrenKeys as $child) {
        removeNode($links, $child, $node);
    }

    //debug('ClearLinks end');
}

/**
 * @param int $start Node
 * @param array $links Links list
 * @param int $parent Parent node
 * @param int $depth Depth from parent
 * @return array Went to the end of the path(bool) + length(int)
 */
function simplifyCrossing($start, &$links, $parent = null, $depth = 0) {
    $paths = array();
    $unreadChildren = $links[$start];
    if (null !== $parent) {
        unset($unreadChildren[$parent]);
        $paths[$parent] = array('ended' => false, 'length' => $depth);
    }

    //If it's the end of the path, leave.
    if (empty($unreadChildren)) {
        //debug('No more child');
        return array('ended' => true, 'length' => 1);
    }

    //Check paths through children
    $unreadChildrenKeys = array_keys($unreadChildren);
    foreach ($unreadChildrenKeys as $child) {
        //debug('simplifyCrossing: ' . $child);
        $paths[$child] = simplifyCrossing($child, $links, $start, $depth + 1);
    }

    //Look if there is an ended path shorter than 2 other paths, to remove it.
    foreach ($paths as $child => $path) {
        if (count($paths) <= 2) {
            break;
        }
        //If path is not ended, it can't be removed.
        if (!$path['ended']) {
            //debug('don\'t check parent');
            continue;
        }
        //debug('check path: ' . $child);
        //debug($path);

        $shorterThan = _comparePaths($path, $paths, $child);

        if (2 === $shorterThan) {
            //debug('remove: ' . $child);
            unset($paths[$child]);
            //debug($links);
            removeNode($links, $child, $start);
            //debug($links);
        }
    }

    //Check if only known paths and max length, to return value.
    unset($paths[$parent]);
    //debug('start: ' . $start);
    //debug($paths);
    $maxLength = 0;
    foreach ($paths as $child => $path) {
        if ($path['length'] > $maxLength) {
            $maxLength = $path['length'];
        }
    }

    return array('ended' => true, 'length' => $maxLength + 1);
}

/**
 * @param $path
 * @param $paths
 * @param $child
 * @return int
 */
function _comparePaths($path, $paths, $child)
{
    $length      = $path['length'];
    $shorterThan = 0;
    foreach ($paths as $otherNode => $otherPath) {
        //Don't compare to your self.
        if ($child === $otherNode) {
            continue;
        }
        //debug('compare to: ' . $otherNode);
        //debug($otherPath);
        if ($length > $otherPath['length']) {
            continue;
        }
        //debug('is shorter');
        if (2 === ++$shorterThan) {
            //debug('found 2 shorter!');
            break;
        }
    }

    return $shorterThan;
}

function isCrossing($node, $links) {
    return isset($links[$node]) && count($links[$node]) >= 3;
}

fscanf(STDIN, "%d",
    $n // le nombre n de relations au total.
);
$links = array();
for ($i = 0; $i < $n; $i++)
{
    fscanf(STDIN, "%d %d",
        $N1, // l'identifiant d'une personne liée à yi
        $N2 // l'identifiant d'une personne liée à xi
    );
    if (!isset($links[$N1])) {
        $links[$N1] = array();
    }
    if (!isset($links[$N2])) {
        $links[$N2] = array();
    }
    $links[$N1][$N2] = 1;
    $links[$N2][$N1] = 1;
}

//ksort($links);

// Write an action using echo(). DON'T FORGET THE TRAILING \n
// To debug (equivalent to var_dump): error_log(var_export($var, true));

echo simplifyWeb($links) . "\n"; // Le nombre d'étapes minimum pour propager la publicité.
