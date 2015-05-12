<?php
/**
 * The machines are gaining ground. Time to show them what we're really made of...
 **/

define('DEBUG', true);

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
    /** @var int[][][][] Number of links between two nodes */
    public $links = array();

    public function __construct($map, $width, $height) {
        $this->map = $map;
        $this->width = $width;
        $this->height = $height;

        $this->initNodes();
    }

    public function initNodes() {
        for ($rowId = 0; $rowId < $this->height; ++$rowId) {
            for ($colId = 0; $colId < $this->width; ++$colId) {
                if ('.' === $this->map[$rowId]{$colId}) {
                    continue;
                }

                $this->nodes[$rowId][$colId] = (int)$this->map[$rowId]{$colId};
            }
        }
    }

    public function parse() {
        while (true) {
            _d('New parse!');
            $nbAddedLinks = 0;
            foreach ($this->nodes as $rowId => $row) {
                foreach ($row as $colId => $nbFreeNodes) {
                    if (0 === $nbFreeNodes) {
                        continue;
                    }

                    $nbAddedLinks += $this->linkNode($rowId, $colId);
                }
            }

            if (0 === $nbAddedLinks) {
                break;
            }
        }
    }

    public function displayResult() {
        foreach ($this->links as $rowId1 => $cols) {
            foreach ($cols as $colId1 => $children) {
                foreach ($children as $rowId2 => $colsChildren) {
                    foreach ($colsChildren as $colId2 => $nbLinks) {
                        echo $colId1 . ' ' . $rowId1 . ' ' . $colId2 . ' ' . $rowId2 . ' ' . $nbLinks . "\n";
                    }
                }
            }
        }
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
        _d('Add link between ' . $colId1 . ' ' . $rowId1 . ' and ' . $colId2 . ' ' . $rowId2);

        if (isset($this->links[$rowId1][$colId1][$rowId2][$colId2])) {
            $r = ++$this->links[$rowId1][$colId1][$rowId2][$colId2];
        } elseif (isset($this->links[$rowId2][$colId2][$rowId1][$colId1])) {
            $r = ++$this->links[$rowId2][$colId2][$rowId1][$colId1];
        } else {
            $r = $this->links[$rowId1][$colId1][$rowId2][$colId2] = 1;
        }

        $this->nodes[$rowId1][$colId1]--;
        $this->nodes[$rowId2][$colId2]--;

        return $r;
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
            2 - $this->countSetLinksBetweenNodes($rowId1, $colId1, $rowId2, $colId2),
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
            return $this->links[$rowId1][$colId1][$rowId2][$colId2];
        }

        if (isset($this->links[$rowId2][$colId2][$rowId1][$colId1])) {
            return $this->links[$rowId2][$colId2][$rowId1][$colId1];
        }

        return 0;
    }

    protected function findBottomNode($rowId, $colId) {
        for ($i = $rowId + 1; $i < $this->height; ++$i) {
            if ('.' === $this->map[$i]{$colId}) {
                continue;
            }

            return array($i, $colId);
        }

        return false;
    }

    protected function findTopNode($rowId, $colId) {
        for ($i = $rowId - 1; $i >= 0; --$i) {
            if ('.' === $this->map[$i]{$colId}) {
                continue;
            }

            return array($i, $colId);
        }

        return false;
    }

    protected function findLeftNode($rowId, $colId) {
        for ($i = $colId - 1; $i >= 0; --$i) {
            if ('.' === $this->map[$rowId]{$i}) {
                continue;
            }

            return array($rowId, $i);
        }

        return false;
    }

    protected function findRightNode($rowId, $colId) {
        for ($i = $colId + 1; $i < $this->width; ++$i) {
            if ('.' === $this->map[$rowId]{$i}) {
                continue;
            }

            return array($rowId, $i);
        }

        return false;
    }
}

$map = new Map($lines, $width, $height);
$map->parse();
$map->displayResult();