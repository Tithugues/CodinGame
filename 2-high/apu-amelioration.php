<?php
/**
 * The machines are gaining ground. Time to show them what we're really made of...
 **/

define('DEBUG', false);

function _d($var, $force = false) {
    /*if (DEBUG || $force) {
        error_log(var_export($var, true));
    }*/
}

fscanf(STDIN, "%d", $width); // the number of cells on the X axis
fscanf(STDIN, "%d", $height); // the number of cells on the Y axis
$lines = array();
for ($i = 0; $i < $height; $i++) {
    $lines[] = stream_get_line(STDIN, 31, "\n"); // width characters, each either a number or a '.'
}

/**
 * Class Map
 */
class Map {
    /** @var string[] Map lines */
    public $map = array();
    /** @var int|bool Map width or false while not initialised */
    public $width = false;
    /** @var int|bool Map height or false while not initialised */
    public $height = false;
    /** @var int[][] Remaining free links on nodes */
    public $nodes = array();
    /** @var int[][][][][] Number of links between two nodes */
    public $links = array();
    /** @var string[][] Cases (not nodes) crossed by a link and its direction */
    public $casesCrossed = array();
    /** @var int[][] Nodes network id, at the end, all the nodes should be on the same network */
    public $nodesNetwork = array();

    public function __construct($map, $width, $height) {
        $this->map = $map;
        $this->width = $width;
        $this->height = $height;

        $this->initNodes();
    }

    public function initNodes() {
        $networkId = 0;
        for ($rowId = 0; $rowId < $this->height; ++$rowId) {
            for ($colId = 0; $colId < $this->width; ++$colId) {
                if ('.' === $this->map[$rowId]{$colId}) {
                    continue;
                }

                $this->nodes[$rowId][$colId] = (int)$this->map[$rowId]{$colId};
                $this->nodesNetwork[$rowId][$colId] = ++$networkId;
            }
        }
    }

    /**
     * Check that all nodes are on the same network
     * @return bool
     */
    public function isOnOneNetwork() {
        $networks = array();
        foreach ($this->nodesNetwork as $cols) {
            foreach ($cols as $networkId) {
                $networks[$networkId] = true;
            }
        }
        return 1 === count($networks);
    }

    /**
     * Check if a network not linked to some nodes doesn't have more free remaining links.
     *
     * @return bool
     */
    public function hasAClosedSubNetwork() {
        /** @var array $networks Contain all networks and its state: networkId => open */
        $openNetworks = array();

        foreach ($this->nodes as $rowId => $cols) {
            foreach ($cols as $colId => $remainingFreeLinks) {
                $networkId = $this->nodesNetwork[$rowId][$colId];
                if (!array_key_exists($networkId, $openNetworks)) {
                    $openNetworks[$networkId] = false;
                }
                if (0 !== $remainingFreeLinks) {
                    $openNetworks[$networkId] = true;
                }
            }
        }

        //Only one network? So no sub-network.
        if (1 === count($openNetworks)) {
            return false;
        }

        //Loop only for trace.
        foreach ($openNetworks as $networkId => $open) {
            if (false === $open) {
                _d($networkId, true);
                //_d($this->nodesNetwork, true);
                break;
            }
        }

        return in_array(false, $openNetworks);
    }

    /**
     * Does it exist nodes with remaining links to add?
     * @return bool
     */
    public function existsNonFilledNodes() {
        foreach ($this->nodes as $cols) {
            foreach ($cols as $nbFreeNodes) {
                if (0 !== $nbFreeNodes) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add a link between two nodes
     * @param int $rowId1
     * @param int $colId1
     * @param int $rowId2
     * @param int $colId2
     * @return int Number of links between the two nodes after adding the new one
     */
    public function addLink($rowId1, $colId1, $rowId2, $colId2) {
        _d('Add link between ' . $colId1 . ' ' . $rowId1 . ' and ' . $colId2 . ' ' . $rowId2, true);

        if (isset($this->links[$rowId1][$colId1][$rowId2][$colId2]['set'])) {
            $r = ++$this->links[$rowId1][$colId1][$rowId2][$colId2]['set'];
        } elseif (isset($this->links[$rowId2][$colId2][$rowId1][$colId1]['set'])) {
            $r = ++$this->links[$rowId2][$colId2][$rowId1][$colId1]['set'];
        } else {
            $r = $this->links[$rowId1][$colId1][$rowId2][$colId2]['set'] = 1;
            $this->links[$rowId1][$colId1][$rowId2][$colId2]['blocked'] = 0;
        }

        $this->nodes[$rowId1][$colId1]--;
        $this->nodes[$rowId2][$colId2]--;

        $this->updateNetwork($this->nodesNetwork[$rowId1][$colId1], $this->nodesNetwork[$rowId2][$colId2]);
        $this->crossCases($rowId1, $colId1, $rowId2, $colId2);

        return $r;
    }

    /**
     * @param int $oldNetworkId
     * @param int $newNetworkId
     *
     * @return void
     */
    protected function updateNetwork($oldNetworkId, $newNetworkId) {
        foreach ($this->nodesNetwork as $rowId => $cols) {
            foreach ($cols as $colId => $networkId) {
                if ($oldNetworkId !== $networkId) {
                    continue;
                }

                $this->nodesNetwork[$rowId][$colId] = $newNetworkId;
            }
        }
    }

    /**
     * Block a link between two nodes
     * @param int $rowId1
     * @param int $colId1
     * @param int $rowId2
     * @param int $colId2
     * @return int Number of blocked links between the two nodes after blocking the new one
     */
    public function blockLink($rowId1, $colId1, $rowId2, $colId2) {
        _d('Add link between ' . $colId1 . ' ' . $rowId1 . ' and ' . $colId2 . ' ' . $rowId2);

        if (isset($this->links[$rowId1][$colId1][$rowId2][$colId2]['blocked'])) {
            $r = ++$this->links[$rowId1][$colId1][$rowId2][$colId2]['blocked'];
        } elseif (isset($this->links[$rowId2][$colId2][$rowId1][$colId1]['blocked'])) {
            $r = ++$this->links[$rowId2][$colId2][$rowId1][$colId1]['blocked'];
        } else {
            $this->links[$rowId1][$colId1][$rowId2][$colId2]['set'] = 0;
            $r = $this->links[$rowId1][$colId1][$rowId2][$colId2]['blocked'] = 1;
        }

        return $r;
    }

    /**
     * Register crosses cases, so we won't be able to cross it in another way
     *
     * @param $rowId1
     * @param $colId1
     * @param $rowId2
     * @param $colId2
     *
     * @return void
     */
    public function crossCases($rowId1, $colId1, $rowId2, $colId2) {
        $way = ($rowId1 === $rowId2 ? 'H' : 'V');
        for ($rowId = min($rowId1, $rowId2), $rowIdEnd = max($rowId1, $rowId2); $rowId <= $rowIdEnd; ++$rowId) {
            for ($colId = min($colId1, $colId2), $colIdEnd = max($colId1, $colId2); $colId <= $colIdEnd; ++$colId) {
                $this->casesCrossed[$rowId][$colId] = $way;
            }
        }
    }

    /**
     * Count remaining free links on this node.
     *
     * @param int $rowId
     * @param int $colId
     *
     * @return int|bool Number of free links or false if not a node
     */
    public function getRemainingFreeLinks($rowId, $colId) {
        if (!isset($this->nodes[$rowId][$colId])) {
            return false;
        }

        return $this->nodes[$rowId][$colId];
    }

    /**
     * Count remaining free links between two nodes
     * @param int $rowId1
     * @param int $colId1
     * @param int $rowId2
     * @param int $colId2
     * @return int|bool Number of remaining free links or false if one is not a node
     */
    public function countRemainingFreeLinksBetweenNodes($rowId1, $colId1, $rowId2, $colId2) {
        if (false === ($remainingFreeLinksNode1 = $this->getRemainingFreeLinks($rowId1, $colId1))) {
            return false;
        }
        if (false === ($remainingFreeLinksNode2 = $this->getRemainingFreeLinks($rowId2, $colId2))) {
            return false;
        }

        return min(
            2 - $this->countSetLinksBetweenNodes(
                $rowId1, $colId1, $rowId2, $colId2
            ) - $this->countForbiddenLinksBetweenNodes($rowId1, $colId1, $rowId2, $colId2),
            $this->getRemainingFreeLinks($rowId2, $colId2)
        );
    }

    /**
     * Count set links between two nodes
     * @param int $rowId1
     * @param int $colId1
     * @param int $rowId2
     * @param int $colId2
     * @return int Number of links between two nodes
     */
    public function countSetLinksBetweenNodes($rowId1, $colId1, $rowId2, $colId2) {
        if (isset($this->links[$rowId1][$colId1][$rowId2][$colId2])) {
            return $this->links[$rowId1][$colId1][$rowId2][$colId2]['set'];
        }

        if (isset($this->links[$rowId2][$colId2][$rowId1][$colId1])) {
            return $this->links[$rowId2][$colId2][$rowId1][$colId1]['set'];
        }

        return 0;
    }

    /**
     * Count forbidden links between two nodes
     * @param int $rowId1
     * @param int $colId1
     * @param int $rowId2
     * @param int $colId2
     * @return int Number of forbidden links between two nodes
     */
    public function countForbiddenLinksBetweenNodes($rowId1, $colId1, $rowId2, $colId2) {
        if (isset($this->links[$rowId1][$colId1][$rowId2][$colId2])) {
            return $this->links[$rowId1][$colId1][$rowId2][$colId2]['blocked'];
        }

        if (isset($this->links[$rowId2][$colId2][$rowId1][$colId1])) {
            return $this->links[$rowId2][$colId2][$rowId1][$colId1]['blocked'];
        }

        return 0;
    }
}

/**
 * Interface Renderer
 */
interface Renderer
{
    public function render();
}

/**
 * Class MapRenderer
 */
class MapRenderer implements Renderer
{
    protected $map;

    /**
     * MapRenderer constructor.
     *
     * @param Map $map
     */
    public function __construct($map)
    {
        $this->map = $map;
    }

    /**
     * Display all the links
     * @return void
     */
    public function render() {
        if (!($this->map instanceof Map)) {
            return;
        }

        _d($this->map, true);
        foreach ($this->map->links as $rowId1 => $cols) {
            foreach ($cols as $colId1 => $children) {
                foreach ($children as $rowId2 => $colsChildren) {
                    foreach ($colsChildren as $colId2 => $nbLinks) {
                        if (0 === $nbLinks['set']) {
                            continue;
                        }

                        echo $colId1 . ' ' . $rowId1 . ' ' . $colId2 . ' ' . $rowId2 . ' ' . $nbLinks['set'] . "\n";
                    }
                }
            }
        }
    }
}

/**
 * Interface Parser
 */
interface Parser
{
    /**
     * @param Map $map
     *
     * @return Map|bool Map filled or false if impossible
     */
    public function parse($map);
}

/**
 * Class DummyParser
 */
class DummyParser implements Parser
{
    /**
     * @var Map
     */
    protected $map;

    protected function findBottomNode($rowId, $colId) {
        for ($i = $rowId + 1; $i < $this->map->height; ++$i) {
            //If node found, leave.
            if ('.' !== $this->map->map[$i]{$colId}) {
                return array($i, $colId);
            }

            //Else, check if it is a crossed case
            if (isset($this->map->casesCrossed[$i][$colId]) && 'H' === $this->map->casesCrossed[$i][$colId]) {
                return false;
            }
        }

        return false;
    }

    protected function findTopNode($rowId, $colId) {
        for ($i = $rowId - 1; $i >= 0; --$i) {
            //If node found, leave.
            if ('.' !== $this->map->map[$i]{$colId}) {
                return array($i, $colId);
            }

            //Else, check if it is a crossed case
            if (isset($this->map->casesCrossed[$i][$colId]) && 'H' === $this->map->casesCrossed[$i][$colId]) {
                return false;
            }
        }

        return false;
    }

    protected function findLeftNode($rowId, $colId) {
        for ($i = $colId - 1; $i >= 0; --$i) {
            //If node found, leave.
            if ('.' !== $this->map->map[$rowId]{$i}) {
                return array($rowId, $i);
            }

            //Else, check if it is a crossed case
            if (isset($this->map->casesCrossed[$rowId][$i]) && 'V' === $this->map->casesCrossed[$rowId][$i]) {
                return false;
            }
        }

        return false;
    }

    protected function findRightNode($rowId, $colId) {
        for ($i = $colId + 1; $i < $this->map->width; ++$i) {
            //If node found, leave.
            if ('.' !== $this->map->map[$rowId]{$i}) {
                return array($rowId, $i);
            }

            //Else, check if it is a crossed case
            if (isset($this->map->casesCrossed[$rowId][$i]) && 'V' === $this->map->casesCrossed[$rowId][$i]) {
                return false;
            }
        }

        return false;
    }

    /**
     * Get a node with remaining links
     * @param $rowId
     * @param $colId
     * @return array|bool Node coordinates or false if no node
     */
    public function getNeighborWithRemainingLinks($rowId, $colId)
    {
        _d('getNeighborWithRemainingLinks: ' . $colId . ' ' . $rowId, self::$trace);

        //Add a link with a node and then restart to parse!
        $rightNode = $this->findRightNode($rowId, $colId);
        if (false !== $rightNode && false !== ($nbRemainingFreeLinks = $this->map->countRemainingFreeLinksBetweenNodes(
                $rowId, $colId, $rightNode[0], $rightNode[1]
            )) && 0 !== $nbRemainingFreeLinks
        ) {
            return $rightNode;
        }

        $bottomNode = $this->findBottomNode($rowId, $colId);
        if (false !== $bottomNode && false !== ($nbRemainingFreeLinks = $this->map->countRemainingFreeLinksBetweenNodes(
                $rowId, $colId, $bottomNode[0], $bottomNode[1]
            )) && 0 !== $nbRemainingFreeLinks
        ) {
            return $bottomNode;
        }

        $leftNode = $this->findLeftNode($rowId, $colId);
        if (false !== $leftNode && false !== ($nbRemainingFreeLinks = $this->map->countRemainingFreeLinksBetweenNodes(
                $rowId, $colId, $leftNode[0], $leftNode[1]
            )) && 0 !== $nbRemainingFreeLinks
        ) {
            return $leftNode;
        }

        $topNode = $this->findTopNode($rowId, $colId);
        if (false !== $topNode && false !== ($nbRemainingFreeLinks = $this->map->countRemainingFreeLinksBetweenNodes(
                $rowId, $colId, $topNode[0], $topNode[1]
            )) && 0 !== $nbRemainingFreeLinks
        ) {
            return $topNode;
        }

        _d('Not found', self::$trace);
        return false;
    }

    /**
     * Try to link a node to its neighbors
     * @param int $rowId
     * @param int $colId
     * @return int|bool Number of added links or false if not a node
     */
    public function linkNode($rowId, $colId) {
        $remainingLinks = $this->map->getRemainingFreeLinks($rowId, $colId);
        if (false === $remainingLinks || 0 === $remainingLinks) {
            return $remainingLinks;
        }

        $neighborRemainingFreeLinks = 0;

        _d('Node to link: ' . $colId . ' ' . $rowId);

        $rightNode = $this->findRightNode($rowId, $colId);
        $rightRemainingFreeLinks = 0;
        if (false !== $rightNode) {
            $rightRemainingFreeLinks = $this->map->countRemainingFreeLinksBetweenNodes($rowId, $colId, $rightNode[0], $rightNode[1]);
            $neighborRemainingFreeLinks += $rightRemainingFreeLinks;
        }
        _d($rightNode);
        _d($rightRemainingFreeLinks);

        $bottomNode = $this->findBottomNode($rowId, $colId);
        $bottomRemainingFreeLinks = 0;
        if (false !== $bottomNode) {
            $bottomRemainingFreeLinks = $this->map->countRemainingFreeLinksBetweenNodes($rowId, $colId, $bottomNode[0], $bottomNode[1]);
            $neighborRemainingFreeLinks += $bottomRemainingFreeLinks;
        }
        _d($bottomNode);
        _d($bottomRemainingFreeLinks);

        $leftNode = $this->findLeftNode($rowId, $colId);
        $leftRemainingFreeLinks = 0;
        if (false !== $leftNode) {
            $leftRemainingFreeLinks = $this->map->countRemainingFreeLinksBetweenNodes($rowId, $colId, $leftNode[0], $leftNode[1]);
            $neighborRemainingFreeLinks += $leftRemainingFreeLinks;
        }
        _d($leftNode);
        _d($leftRemainingFreeLinks);

        $topNode = $this->findTopNode($rowId, $colId);
        $topRemainingFreeLinks = 0;
        if (false !== $topNode) {
            $topRemainingFreeLinks = $this->map->countRemainingFreeLinksBetweenNodes($rowId, $colId, $topNode[0], $topNode[1]);
            $neighborRemainingFreeLinks += $topRemainingFreeLinks;
        }
        _d($topNode);
        _d($topRemainingFreeLinks);

        _d('$remainingLinks: ' . $remainingLinks);
        _d('$neighborRemainingFreeLinks: ' . $neighborRemainingFreeLinks);

        $nbOfLinksNotToAdd = $neighborRemainingFreeLinks - $remainingLinks;
        if ($nbOfLinksNotToAdd >= 2) {
            return 0;
        }

        $nbAddedLinks = 0;
        if (0 !== $rightRemainingFreeLinks) {
            for ($i = $rightRemainingFreeLinks - $nbOfLinksNotToAdd; $i > 0; --$i) {
                _d('Add link with right');
                $this->map->addLink($rowId, $colId, $rightNode[0], $rightNode[1]);
                $nbAddedLinks++;
            }
        }

        if (0 !== $bottomRemainingFreeLinks) {
            for ($i = $bottomRemainingFreeLinks - $nbOfLinksNotToAdd; $i > 0; --$i) {
                _d('Add link with bottom');
                $this->map->addLink($rowId, $colId, $bottomNode[0], $bottomNode[1]);
                $nbAddedLinks++;
            }
        }

        if (0 !== $leftRemainingFreeLinks) {
            for ($i = $leftRemainingFreeLinks - $nbOfLinksNotToAdd; $i > 0; --$i) {
                _d('Add link with left');
                $this->map->addLink($rowId, $colId, $leftNode[0], $leftNode[1]);
                $nbAddedLinks++;
            }
        }

        if (0 !== $topRemainingFreeLinks) {
            for ($i = $topRemainingFreeLinks - $nbOfLinksNotToAdd; $i > 0; --$i) {
                _d('Add link with top');
                $this->map->addLink($rowId, $colId, $topNode[0], $topNode[1]);
                $nbAddedLinks++;
            }
        }

        //_d($this->casesCrossed);

        return $nbAddedLinks;
    }

    protected static $trace = true;

    /**
     * Make a supposition
     *
     * @return bool
     */
    public function assume() {
        do {
            $hasClosedSubNetwork = false;
            //If already assume a link and still false, block the link and continue.
            if (isset($node)) {
                _d('Block ' . $colId. ' ' . $rowId . ' -> ' . $node[1] . ' ' . $node[0], self::$trace);
                _d('Blocked: ' . $this->map->blockLink($rowId, $colId, $node[0], $node[1]), self::$trace);

                return $this->parse($this->map);
            }

            $node = false;

            //Find node with remaining free links...
            foreach ($this->map->nodes as $rowId => $cols) {
                foreach ($cols as $colId => $nbRemainingLinks) {
                    _d('Checking ' . $colId . ' ' . $rowId . ': ' . $nbRemainingLinks, self::$trace);
                    if (0 === $nbRemainingLinks) {
                        continue;
                    }

                    if (false === ($node = $this->getNeighborWithRemainingLinks($rowId, $colId))) {
                        continue;
                    }

                    _d('Found', true);
                    break 2;
                }
            }

            if (false === $node) {
                return false;
            }

            if ($colId == 2 && $rowId == 8 && $node[1] == 3 && $node[0] == 8) {
                self::$trace = true;
            }

            _d('Assume ' . $colId. ' ' . $rowId . ' -> ' . $node[1] . ' ' . $node[0], self::$trace);
            $clone = clone($this->map);
            $clone->addLink($rowId, $colId, $node[0], $node[1]);
            if ($clone->hasAClosedSubNetwork()) {
                _d('Sub network detected', true);
                $hasClosedSubNetwork = true;
                $parser = new static();
                continue;
            }

            $parser = new static();
        } while ($hasClosedSubNetwork || false === ($r = $parser->parse($clone)) || $onManyNetwork = (false === $r->isOnOneNetwork()));
        _d($onManyNetwork, self::$trace);

        return $onManyNetwork ? false : $r;
    }

    /**
     * Parse map
     *
     * @param Map $map
     *
     * @return Map|bool Map fully filled or false if impossible
     */
    public function parse($map) {
        $this->map = $map;

        while (true) {
            _d('New parse!', self::$trace);
            $nbAddedLinks = 0;
            foreach ($this->map->nodes as $rowId => $row) {
                foreach ($row as $colId => $nbFreeNodes) {
                    if (0 === $nbFreeNodes) {
                        continue;
                    }

                    $nbAddedLinks += $this->linkNode($rowId, $colId);
                }
            }
            _d($this->map->nodes);
            _d($nbAddedLinks, self::$trace);

            if (0 === $nbAddedLinks) {
                //If there still are some nodes with links to add...
                if ($this->map->existsNonFilledNodes()) {
                    _d('Exists non filled nodes.', self::$trace);
                    return $this->assume();
                }
                _d('All nodes filled.');

                return $this->map;
            }
        }
    }
}

$map = new Map($lines, $width, $height);
$parser = new DummyParser($map);
$renderer = new MapRenderer($parser->parse($map));
$renderer->render();