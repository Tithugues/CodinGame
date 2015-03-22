<?php

class Node {
    public $id = null;
    public $parcouru = false;
    public $dist = null;
    /**
     * @var array Nodes id
     */
    public $prev = null;

    public function __construct($id) {
        $this->id = $id;
    }
}

class Dijkstra {
    public $nodes = array();
    /**
     * @var null|Stack Stack
     */
    public $stack = null;
    public $links = array();

    public function __construct($nbNodes, &$links) {
        $this->stack = new Stack();
        $this->initialise($nbNodes, $links);
    }

    public function initialise($nbNodes, &$links) {
        $nodesWOParents = array();
        for ($i = 0; $i <= $nbNodes; $i++) {
            $this->nodes[$i] = new Node($i);
            if (!isset($links[$i])) {
                $nodesWOParents[$i] = true;
            }
        }

        $links[$nbNodes] = array();
        foreach ($nodesWOParents as $i => $nodes) {
            $links[$nbNodes][$i] = 1;
        }

        $this->links =& $links;

        $this->stack->add($this->nodes[$nbNodes]);

        $this->nodes[$nbNodes]->parcouru = true;
        $this->nodes[$nbNodes]->dist = 0;
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
                if ($node->dist === null || $node->dist < $farest->dist + 1) {
                    $node->dist = $farest->dist + 1;
                    $node->prev = array($farest->id => true);
                } elseif ($node->dist === $farest->dist + 1) {
                    $node->prev[$farest->id] = true;
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



//Test3 - step2
$links = array (
    1 =>
        array (
            2 => 1,
            3 => 1,
        ),
    3 => array(4 => 1)
);
$N = 3;

$dijkstra = new Dijkstra($N, $links);
$dijkstra->mesurer();

echo var_export($dijkstra, true);