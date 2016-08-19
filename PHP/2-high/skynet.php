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
    public $gateways = array();

    public function __construct($nbNodes, $start, &$links, $gateways) {
        $this->stack = new Stack();
        $this->initialise($nbNodes, $start, $links, $gateways);
    }

    public function initialise($nbNodes, $start, &$links, $gateways) {
        for ($i = 0; $i < $nbNodes; $i++) {
            $this->nodes[$i] = new Node($i);
        }

        $this->links =& $links;
        $this->gateways = $gateways;

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

    public function getLinksToGateway($node) {
        $destinations = array_keys($this->links[$node]);
        return array_intersect($destinations, array_keys($this->gateways));
    }

    public function getNumberLinksToGatewayFor($node) {
        return count($this->getLinksToGateway($node));
    }

    public function getNumberLinksToGatewayUntil($node) {
        debug('Node: ' . $node);
        if (0 === $this->nodes[$node]->dist) {
            return $this->getNumberLinksToGatewayFor($node);
        }

        debug('Check prev: ', true);
        debug($this->nodes[$node]->prev, true);
        $prevs = array_keys($this->nodes[$node]->prev);
        $closerToGateWay = null;
        $nbLinksForCloser = null;
        foreach ($prevs as $prev) {
            $nbLinks = $this->getNumberLinksToGatewayFor($prev);
            if (null === $closerToGateWay || $nbLinks > $nbLinksForCloser) {
                $closerToGateWay = $prev;
                $nbLinksForCloser = $nbLinks;
            }
        }
        debug($closerToGateWay, true);
        return $this->getNumberLinksToGatewayFor($node)
            + $this->getNumberLinksToGatewayUntil($closerToGateWay);
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
    $gateways[$EI] = $links[$EI];
    unset($links[$EI]);
}

// game loop
while (TRUE)
{
    fscanf(STDIN, "%d",
        $SI // The index of the node on which the Skynet agent is positioned this turn
    );

    // Write an action using echo(). DON'T FORGET THE TRAILING \n
    // To debug (equivalent to var_dump): error_log(var_export($var, true));

    $d = new Dijkstra($N, $SI, $links, $gateways);
    $d->mesurer();

    //Find critical nodes
    if ($d->getNumberLinksToGatewayFor($SI)) {
        $mostCritical = $SI;
        $mostCriticality = 1;
    } else {
        //Get all nodes linked to a gateway
        $prevs = array();
        foreach ($gateways as $gateway => $children) {
            $prevs = array_merge($prevs, array_keys($children));
        };
        $prevs = array_unique($prevs);
        debug($prevs);


        $mostCritical = null;
        $mostCriticality = null;
        foreach ($prevs as $prev) {
            debug('Node: ' . $prev, true);
            $nbLinksUntil = $d->getNumberLinksToGatewayUntil($prev);
            debug('Nb links: ' . $nbLinksUntil, true);
            debug('Dist: ' . $d->nodes[$prev]->dist, true);
            $criticality = $nbLinksUntil - $d->nodes[$prev]->dist;
            debug('Criticality: ' . $criticality, true);
            if (
                null === $mostCritical
                || $criticality > $mostCriticality
                || (
                    $criticality === $mostCriticality
                    && $d->nodes[$prev]->dist < $d->nodes[$mostCritical]->dist
                )
            ) {
                debug('New most critical', true);
                $mostCritical = $prev;
                $mostCriticality = $criticality;
            }
        }
    }

    debug($mostCritical);
    debug($mostCriticality);

    $out = null;
    foreach ($gateways as $gateway => $children) {
        if (isset($links[$mostCritical][$gateway])) {
            $out = $gateway;
            break;
        }
    }

    echo $mostCritical . ' ' . $out . "\n";
    unset($links[$mostCritical][$out]);
    unset($gateways[$out][$mostCritical]);
}
