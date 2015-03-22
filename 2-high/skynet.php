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
                if ($node->dist === null || $closest->dist + 1 < $node->dist) {
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

    // Write an action using echo(). DON'T FORGET THE TRAILING \n
    // To debug (equivalent to var_dump): error_log(var_export($var, true));

    $d = new Dijkstra($N, $SI, $links);
    $d->mesurer();

    //Get all nodes linked to a gateway
    $prevs = array();
    foreach ($gateways as $gateway) {
        $prevs = array_merge($prevs, array_keys($links[$gateway]));
        debug($prevs);
    }

    //Find the more used node linked to a gateway
    $prevsNbUses = array_count_values($prevs);
    unset($prevs);

    debug($prevsNbUses, true);

    //Find critical nodes
    $mostCritical = null;
    $criticalityMostCritical = null;
    $mostUsed = null;
    $maxUses = null;
    //$criticalities = array();
    foreach ($prevsNbUses as $prev => $nbGatewaysLinked) {
        debug('Prev: ' . $prev, true);
        $uses = $prevsNbUses[$prev];
        $criticality = $uses - $d->nodes[$prev]->dist;
        //$criticalities[$prev] = $criticality;
        debug('Criticality: ' . $criticality, true);
        debug('Uses: ' . $uses, true);
        if (null === $mostCritical) {
            $mostCritical = $prev;
            $criticalityMostCritical = $criticality;
            $mostUsed = $prev;
            $maxUses = $uses;
            continue;
        }
        debug('Current dist: ' . $d->nodes[$prev]->dist, true);
        debug('Most critical dist: ' . $d->nodes[$mostCritical]->dist, true);
        debug('Current nb uses: ' . $uses, true);
        debug('Most critical nb uses: ' . $maxUses, true);
        if ($criticalityMostCritical < $criticality) {
            debug('New most critical! > ' . $criticalityMostCritical, true);
            $mostCritical = $prev;
            $criticalityMostCritical = $criticality;
        } elseif ($criticalityMostCritical === $criticality && $d->nodes[$prev]->dist > $d->nodes[$mostCritical]->dist) {
            debug('New most critical! because closer!', true);
            $mostCritical = $prev;
        }
        if ($maxUses < $uses) {
            debug('New most used! > ' . $maxUses, true);
            $mostUsed = $prev;
            $maxUses = $uses;
        } elseif ($maxUses === $uses && $d->nodes[$prev]->dist < $d->nodes[$mostUsed]->dist) {
            debug('New most used and closer!', true);
            $mostUsed = $prev;
        }
    }

    debug($mostCritical);
    debug($criticalityMostCritical);

    if ($criticalityMostCritical > 0) {
        $in = $mostCritical;
    } else {
        $in = $mostUsed;
    }

    $out = null;
    foreach ($gateways as $gateway) {
        if (isset($links[$in][$gateway])) {
            $out = $gateway;
            break;
        }
    }

    echo $in . ' ' . $out . "\n";
    unset($links[$in][$out]);
    unset($links[$out][$in]);
}
