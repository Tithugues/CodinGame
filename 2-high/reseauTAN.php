<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Node {
    public $id = null;
    public $name = null;
    public $latitude = null;
    public $longitude = null;
    public $parcouru = false;
    public $dist = null;
    /**
     * @var array Nodes id
     */
    public $prev = null;

    public function __construct($id, $name, $latitude, $longitude) {
        $this->id = $id;
        $this->name = $name;
        $this->latitude = $latitude * M_PI / 180;
        $this->longitude = $longitude * M_PI / 180;
    }

    public function getShortId() {
        return substr($this->id, 9);
    }

    public function getDistanceFrom(Node $node) {
        $x = ($node->longitude - $this->longitude) * cos(($this->latitude + $node->latitude) / 2);
        $y = $node->latitude - $this->latitude;
        return sqrt(pow($x, 2) + pow($y, 2)) * 6371;
    }
}

class Dijkstra {
    public $nodes = array();
    public $stack = null;
    public $links = array();

    /**
     * Construct Dijkstra.
     * @param Node[] $nodes Nodes array
     * @param string $start Start node id
     * @param array $links Links between nodes and their distance
     */
    public function __construct($nodes, $start, $links) {
        $this->stack = new Stack();
        $this->initialise($nodes, $start, $links);
    }

    /**
     * Initialise Dijkstra.
     * @param Node[] $nodes Nodes array
     * @param string $start Start node id
     * @param array $links Links between nodes and their distance
     */
    public function initialise($nodes, $start, $links) {
        $this->nodes = $nodes;
        $this->links = $links;

        $this->stack->add($this->nodes[$start]);

        $this->nodes[$start]->dist = 0;
    }

    public function mesurer() {
        /** @var $closest Node */
        while ($closest = $this->stack->findClosest()) {
            $closest->parcouru = true;

            if (!isset($this->links[$closest->id])) {
                continue;
            }

            $children = $this->links[$closest->id];
            foreach ($children as $nodeId => $dist) {
                /** @var Node $node */
                $node = $this->nodes[$nodeId];
                if ($node->dist === null || $closest->dist + $dist < $node->dist) {
                    $node->dist = $closest->dist + $dist;
                    $node->prev = $closest->id;
                }
                $this->stack->add($node);
            }
        }
    }
}

class Stack {
    protected $_list = array();

    /**
     * @param Node $node
     * @return bool
     */
    public function add($node) {
        if (isset($this->_list[$node->id]) || $node->parcouru) {
            return false;
        }

        $this->_list[$node->id] = $node;
        return true;
    }

    public function findClosest() {
        if ($this->isEmpty()) {
            return false;
        }

        $closest = null;
        foreach ($this->_list as $node) {
            if (null === $closest) {
                $closest = $node;
                continue;
            }

            if ($node->dist < $closest->dist) {
                $closest = $node;
            }
        }

        unset($this->_list[$closest->id]);

        return $closest;
    }

    public function isEmpty() {
        return empty($this->_list);
    }
}

fscanf(STDIN, "%s", $startPoint);
fscanf(STDIN, "%s", $endPoint);
fscanf(STDIN, "%d", $numberNodes);
/** @var Node[] $nodes */
$nodes = array();
for ($i = 0; $i < $numberNodes; $i++)
{
    $stop = stream_get_line(STDIN, 256, "\n");
    list($id, $name,, $latitude, $longitude,,,,) = explode(',', $stop);
    $name = substr($name, 1, -1);

    $nodes[$id] = new Node($id, $name, $latitude, $longitude);
}
debug($nodes);
fscanf(STDIN, "%d", $numberLinks);
$paths = array();
for ($i = 0; $i < $numberLinks; $i++)
{
    $path = stream_get_line(STDIN, 256, "\n");
    list($idA, $idB) = explode(' ', $path);
    $paths[$idA][$idB] = $nodes[$idA]->getDistanceFrom($nodes[$idB]);
}

$length = new Dijkstra($nodes, $startPoint, $paths);
$length->mesurer();

if (null === $nodes[$endPoint]->prev && $startPoint != $endPoint) {
    echo "IMPOSSIBLE\n";
    exit;
}

$listNodes = array();
$current = $endPoint;
do {
    array_unshift($listNodes, $nodes[$current]->name);
    $current = $nodes[$current]->prev;
} while (null !== $current);

foreach($listNodes as $nodeName) {
    echo $nodeName . "\n";
}
