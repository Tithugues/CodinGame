<?php
/**
 * The machines are gaining ground. Time to show them what we're really made of...
 **/

define('DEBUG', false);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

fscanf(STDIN, "%d", $width); // the number of cells on the X axis
fscanf(STDIN, "%d", $height); // the number of cells on the Y axis
$lines = array();
for ($i = 0; $i < $height; $i++) {
    $lines[] = stream_get_line(STDIN, 31, "\n"); // width characters, each either a number or a '.'
}

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
        $networkId = 1;
        for ($rowId = 0; $rowId < $this->height; ++$rowId) {
            for ($colId = 0; $colId < $this->width; ++$colId) {
                if ('.' === $this->map[$rowId]{$colId}) {
                    continue;
                }

                $this->nodes[$rowId][$colId] = (int)$this->map[$rowId]{$colId};
                $this->nodesNetwork[$rowId][$colId] = $networkId++;
            }
        }
        /*_d('Networks:');
        _d($this->nodesNetwork);*/
    }

    public function parse() {
        while (true) {
            _d('New parse!', true);
            $nbAddedLinks = 0;
            foreach ($this->nodes as $rowId => $row) {
                foreach ($row as $colId => $nbFreeNodes) {
                    if (0 === $nbFreeNodes) {
                        continue;
                    }

                    $nbAddedLinks += $this->linkNode($rowId, $colId);
                }
            }
            _d($this->nodes);

            if (0 === $nbAddedLinks) {
                //If there still are some nodes with links to add...
                if ($this->existsNonFilledNodes()) {
                    _d('Exists non filled nodes.', true);
                    return $this->assume();
                }
                _d('All nodes filled.');

                return $this;
            }
        }
    }

    public function assume() {
        do {
            //Find node with remaining free links...
            foreach ($this->nodes as $colId => $cols) {
                foreach ($cols as $rowId => $nbRemainingLinks) {
                    if (0 === $nbRemainingLinks) {
                        continue;
                    }

                    break 2;
                }
            }

            if (isset($node)) {
                _d('block ' . $colId. ' ' . $rowId . ' -> ' . $node[1] . ' ' . $node[0], true);
                $this->blockLink($rowId, $colId, $node[0], $node[1]);
            }
            $clone = clone($this);
            if (false === ($node = $clone->getNodeWithRemainingLinks($rowId, $colId))) {
                return false;
            }
            _d('assume ' . $colId. ' ' . $rowId . ' -> ' . $node[1] . ' ' . $node[0], true);
            $clone->addLink($rowId, $colId, $node[0], $node[1]);
            _d($clone->nodes);
        } while (false === ($r = $clone->parse()));

        return false === $r->isOnOneNetwork() ? false : $r;
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
     * Display all the links
     * @return void
     */
    public function displayResult() {
        /*_d('Networks:');
        _d($this->nodesNetwork);*/
        foreach ($this->links as $rowId1 => $cols) {
            foreach ($cols as $colId1 => $children) {
                foreach ($children as $rowId2 => $colsChildren) {
                    foreach ($colsChildren as $colId2 => $nbLinks) {
                        echo $colId1 . ' ' . $rowId1 . ' ' . $colId2 . ' ' . $rowId2 . ' ' . $nbLinks['set'] . "\n";
                    }
                }
            }
        }
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
     * Try to link a node to its neighbors
     * @param int $rowId
     * @param int $colId
     * @return int|bool Number of added links or false if not a node
     */
    public function linkNode($rowId, $colId) {
        $remainingLinks = $this->getRemainingFreeLinks($rowId, $colId);
        if (false === $remainingLinks || 0 === $remainingLinks) {
            return $remainingLinks;
        }

        $neighborRemainingFreeLinks = 0;

        _d('Node to link: ' . $colId . ' ' . $rowId);

        $rightNode = $this->findRightNode($rowId, $colId);
        $rightRemainingFreeLinks = 0;
        if (false !== $rightNode) {
            $rightRemainingFreeLinks = $this->countRemainingFreeLinksBetweenNodes($rowId, $colId, $rightNode[0], $rightNode[1]);
            $neighborRemainingFreeLinks += $rightRemainingFreeLinks;
        }
        _d($rightNode);
        _d($rightRemainingFreeLinks);

        $bottomNode = $this->findBottomNode($rowId, $colId);
        $bottomRemainingFreeLinks = 0;
        if (false !== $bottomNode) {
            $bottomRemainingFreeLinks = $this->countRemainingFreeLinksBetweenNodes($rowId, $colId, $bottomNode[0], $bottomNode[1]);
            $neighborRemainingFreeLinks += $bottomRemainingFreeLinks;
        }
        _d($bottomNode);
        _d($bottomRemainingFreeLinks);

        $leftNode = $this->findLeftNode($rowId, $colId);
        $leftRemainingFreeLinks = 0;
        if (false !== $leftNode) {
            $leftRemainingFreeLinks = $this->countRemainingFreeLinksBetweenNodes($rowId, $colId, $leftNode[0], $leftNode[1]);
            $neighborRemainingFreeLinks += $leftRemainingFreeLinks;
        }
        _d($leftNode);
        _d($leftRemainingFreeLinks);

        $topNode = $this->findTopNode($rowId, $colId);
        $topRemainingFreeLinks = 0;
        if (false !== $topNode) {
            $topRemainingFreeLinks = $this->countRemainingFreeLinksBetweenNodes($rowId, $colId, $topNode[0], $topNode[1]);
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
                $this->addLink($rowId, $colId, $rightNode[0], $rightNode[1]);
                $nbAddedLinks++;
            }
        }

        if (0 !== $bottomRemainingFreeLinks) {
            for ($i = $bottomRemainingFreeLinks - $nbOfLinksNotToAdd; $i > 0; --$i) {
                _d('Add link with bottom');
                $this->addLink($rowId, $colId, $bottomNode[0], $bottomNode[1]);
                $nbAddedLinks++;
            }
        }

        if (0 !== $leftRemainingFreeLinks) {
            for ($i = $leftRemainingFreeLinks - $nbOfLinksNotToAdd; $i > 0; --$i) {
                _d('Add link with left');
                $this->addLink($rowId, $colId, $leftNode[0], $leftNode[1]);
                $nbAddedLinks++;
            }
        }

        if (0 !== $topRemainingFreeLinks) {
            for ($i = $topRemainingFreeLinks - $nbOfLinksNotToAdd; $i > 0; --$i) {
                _d('Add link with top');
                $this->addLink($rowId, $colId, $topNode[0], $topNode[1]);
                $nbAddedLinks++;
            }
        }

        //_d($this->casesCrossed);

        return $nbAddedLinks;
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
        //_d('Old network 1: ' . $rowId1 . '-' . $colId1 . ' = ' . $this->nodesNetwork[$rowId1][$colId1]);
        //_d('Old network 2: ' . $rowId2 . '-' . $colId2 . ' = ' . $this->nodesNetwork[$rowId2][$colId2]);
        if ($this->nodesNetwork[$rowId1][$colId1] < $this->nodesNetwork[$rowId2][$colId2]) {
            $this->nodesNetwork[$rowId2][$colId2] =& $this->nodesNetwork[$rowId1][$colId1];
        } else {
            $this->nodesNetwork[$rowId1][$colId1] =& $this->nodesNetwork[$rowId2][$colId2];
        }
        //_d('New network 1: ' . $this->nodesNetwork[$rowId1][$colId1]);
        //_d('New network 2: ' . $this->nodesNetwork[$rowId2][$colId2]);

        $this->crossCases($rowId1, $colId1, $rowId2, $colId2);

        return $r;
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
     * @param int $rowId
     * @param int $colId
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
            //$this->getRemainingFreeLinks($rowId1, $colId1),
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

    protected function findBottomNode($rowId, $colId) {
        for ($i = $rowId + 1; $i < $this->height; ++$i) {
            //If node found, leave.
            if ('.' !== $this->map[$i]{$colId}) {
                return array($i, $colId);
            }

            //Else, check if it is a crossed case
            if (isset($this->casesCrossed[$i][$colId]) && 'H' === $this->casesCrossed[$i][$colId]) {
                return false;
            }
        }

        return false;
    }

    protected function findTopNode($rowId, $colId) {
        for ($i = $rowId - 1; $i >= 0; --$i) {
            //If node found, leave.
            if ('.' !== $this->map[$i]{$colId}) {
                return array($i, $colId);
            }

            //Else, check if it is a crossed case
            if (isset($this->casesCrossed[$i][$colId]) && 'H' === $this->casesCrossed[$i][$colId]) {
                return false;
            }
        }

        return false;
    }

    protected function findLeftNode($rowId, $colId) {
        for ($i = $colId - 1; $i >= 0; --$i) {
            //If node found, leave.
            if ('.' !== $this->map[$rowId]{$i}) {
                return array($rowId, $i);
            }

            //Else, check if it is a crossed case
            if (isset($this->casesCrossed[$rowId][$i]) && 'V' === $this->casesCrossed[$rowId][$i]) {
                return false;
            }
        }

        return false;
    }

    protected function findRightNode($rowId, $colId) {
        for ($i = $colId + 1; $i < $this->width; ++$i) {
            //If node found, leave.
            if ('.' !== $this->map[$rowId]{$i}) {
                return array($rowId, $i);
            }

            //Else, check if it is a crossed case
            if (isset($this->casesCrossed[$rowId][$i]) && 'V' === $this->casesCrossed[$rowId][$i]) {
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
    public function getNodeWithRemainingLinks($rowId, $colId)
    {
        //Add a link with a node and then restart to parse!
        $rightNode = $this->findRightNode($rowId, $colId);
        if (false !== $rightNode && false !== ($nbRemainingFreeLinks = $this->countRemainingFreeLinksBetweenNodes(
                $rowId, $colId, $rightNode[0], $rightNode[1]
            )) && 0 !== $nbRemainingFreeLinks
        ) {
            return $rightNode;
        }

        $bottomNode = $this->findBottomNode($rowId, $colId);
        if (false !== $bottomNode && false !== ($nbRemainingFreeLinks = $this->countRemainingFreeLinksBetweenNodes(
                $rowId, $colId, $bottomNode[0], $bottomNode[1]
            )) && 0 !== $nbRemainingFreeLinks
        ) {
            return $bottomNode;
        }

        $leftNode = $this->findLeftNode($rowId, $colId);
        if (false !== $leftNode && false !== ($nbRemainingFreeLinks = $this->countRemainingFreeLinksBetweenNodes(
                $rowId, $colId, $leftNode[0], $leftNode[1]
            )) && 0 !== $nbRemainingFreeLinks
        ) {
            return $leftNode;
        }

        $topNode = $this->findTopNode($rowId, $colId);
        if (false !== $topNode && false !== ($nbRemainingFreeLinks = $this->countRemainingFreeLinksBetweenNodes(
                $rowId, $colId, $topNode[0], $topNode[1]
            )) && 0 !== $nbRemainingFreeLinks
        ) {
            return $topNode;
        }

        return false;
    }
}

$map = new Map($lines, $width, $height);
$map->parse()
    ->displayResult();