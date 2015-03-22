<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

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
    public $stack = null;
    public $links = array();

    public function __construct($nbNodes, $start, &$links) {
        $this->stack = new Stack();
        $this->initialise($nbNodes, $start, $links);
    }

    public function initialise($nbNodes, $start, &$links) {
        for ($i = 0; $i < $nbNodes; $i++) {
            $this->nodes[$i] = new Node($i);
        }

        $this->links =& $links;

        $this->stack->add($this->nodes[$start]);

        $this->nodes[$start]->parcouru = true;
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
                if ($node->dist === null || $node->dist > $closest->dist + 1) {
                    $node->dist = $closest->dist + 1;
                    $node->prev = array($closest->id => true);
                } elseif ($node->dist === $closest->dist + 1) {
                    $node->prev[$closest->id] = true;
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

fscanf(STDIN, "%d %d %d",
    $N, // the total number of nodes in the level, including the gateways
    $L, // the number of links
    $E  // the number of exit gateways
);
//error_log(var_export($N, true));

$links = array();
for ($i = 0; $i < $L; $i++)
{
    fscanf(STDIN, "%d %d",
        $N1, // N1 and N2 defines a link between these nodes
        $N2
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

$gateways = array();
for ($i = 0; $i < $E; $i++)
{
    fscanf(STDIN, "%d",
        $EI // the index of a gateway node
    );
    $gateways[$i] = $EI;
}

/*error_log(var_export($links, true));
error_log(var_export($gateways, true));*/

// game loop
while (TRUE)
{
    fscanf(STDIN, "%d",
        $SI // The index of the node on which the Skynet agent is positioned this turn
    );
    //error_log(var_export($SI, true));

    // Write an action using echo(). DON'T FORGET THE TRAILING \n
    // To debug (equivalent to var_dump): error_log(var_export($var, true));

    $d = new Dijkstra($N, $SI, $links);
    $d->mesurer();

    $closestGateway = null;
    foreach ($gateways as $gateway) {
        if (
            null === $closestGateway
            || null === $d->nodes[$closestGateway]->dist
            || (
                null !== $d->nodes[$gateway]->dist
                && $d->nodes[$gateway]->dist < $d->nodes[$closestGateway]->dist
            )
        ) {
            $closestGateway = $gateway;
        }
    }

    $keys = array_keys($d->nodes[$closestGateway]->prev);

    echo $keys[0] . ' ' . $d->nodes[$closestGateway]->id . "\n";

    unset($links[$keys[0]][$d->nodes[$closestGateway]->id]);
    unset($links[$d->nodes[$closestGateway]->id][$keys[0]]);
}