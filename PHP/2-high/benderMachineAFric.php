<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Node {
    public $id = null;
    public $parcouru = false;
    public $dist = null;
    /**
     * @var Node Nodes id
     */
    public $prev = null;

    public function __construct($id) {
        $this->id = $id;
    }
}

class Dijkstra {
    public $nodes = array();
    /**
     * @var Stack Stack
     */
    public $stack = null;
    public $links = array();

    public function __construct($nodes, $start, $links) {
        $this->stack = new Stack();
        $this->initialise($nodes, $start, $links);
    }

    public function initialise($nodes, $start, $links) {
        $this->nodes = $nodes;
        $this->links = $links;

        $this->stack->add($this->nodes[$start]);

        $this->nodes[$start]->parcouru = true;
        $this->nodes[$start]->dist = 0;
    }

    public function mesurer() {
        /** @var Node $farest */
        while ($farest = $this->stack->findFarest()) {
            $farest->parcouru = true;

            if (!isset($this->links[$farest->id])) {
                continue;
            }

            $children = $this->links[$farest->id];
            foreach ($children as $nodeId => $dist) {
                /** @var Node $node */
                $node = $this->nodes[$nodeId];
                if ($node->dist === null || $node->dist < $farest->dist + $dist) {
                    $node->dist = $farest->dist + $dist;
                    $node->prev = $farest->id;

                    //Update dist of followers.
                    $this->updateDists($nodeId);
                }
                $this->stack->add($node);
            }
        }
    }

    protected function updateDists($nodeId) {
        if (!isset($this->links[$nodeId]) || false === $this->nodes[$nodeId]->parcouru) {
            return;
        }

        foreach($this->links[$nodeId] as $nextId => $dist) {
            $tmpDist = $this->nodes[$nodeId]->dist + $dist;
            if ($this->nodes[$nextId]->dist < $tmpDist) {
                $this->nodes[$nextId]->dist = $this->nodes[$nodeId]->dist + $dist;
                $this->updateDists($nextId);
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

    public function findFarest() {
        if ($this->isEmpty()) {
            return false;
        }

        /** @var Node $farest */
        $farest = null;
        foreach ($this->_list as $node) {
            if (null === $farest) {
                $farest = $node;
                continue;
            }

            if ($node->dist > $farest->dist) {
                $farest = $node;
            }
        }

        unset($this->_list[$farest->id]);

        return $farest;
    }

    public function isEmpty() {
        return empty($this->_list);
    }
}

/** @var Node[] $nodes */
$nodes = array('_' => new Node('_'), 'E' => new Node('E'));
$links = array();
$prevs = array(0 => array('_'));

fscanf(STDIN, "%d", $nbRooms);
for ($i = 0; $i < $nbRooms; $i++) {
    /** @var int $money */
    /** @var int $exit1 */
    /** @var int $exit2 */
    fscanf(STDIN, "%d %d %s %s", $room, $money, $exit1, $exit2);
    $nodes[] = new Node($room);
    if (!isset($prevs[$exit1])) {
        $prevs[$exit1] = array();
    }
    if (!isset($prevs[$exit2])) {
        $prevs[$exit2] = array();
    }
    $prevs[$exit1][] = $room;
    $prevs[$exit2][] = $room;

    foreach ($prevs[$room] as $prev) {
        $links[$prev][$room] = $money;
    }
}

foreach ($prevs['E'] as $prev) {
    $links[$prev]['E'] = 0;
}

$dijkstra = new Dijkstra($nodes, '_', $links);
$dijkstra->mesurer();
//debug($dijkstra, true);

echo $nodes['E']->dist . "\n";
