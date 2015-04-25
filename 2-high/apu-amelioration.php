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

class Map {
    protected $_map = array();
    protected $_width = false;
    protected $_height = false;
    protected $_nodes = array();
    protected $_links = array();

    public function __construct($map, $width, $height) {
        $this->_map = $map;
        $this->_width = $width;
        $this->_height = $height;

        for ($rowId = 0; $rowId < $height; ++$rowId) {
            for ($colId = 0; $colId < $width; ++$colId) {
                if ('.' === $map[$rowId]{$colId}) {
                    continue;
                }

                $this->_nodes[$rowId][$colId] = (int)$map[$rowId]{$colId};
            }
        }
    }

    protected function findBottomNode($rowId, $colId) {
        for ($i = $rowId + 1; $i < $this->_height; ++$i) {
            if ('.' === $this->_map[$i]{$colId}) {
                continue;
            }

            return array($i, $colId);
        }

        return false;
    }

    protected function findTopNode($rowId, $colId) {
        for ($i = $rowId - 1; $i >= 0; --$i) {
            if ('.' === $this->_map[$i]{$colId}) {
                continue;
            }

            return array($i, $colId);
        }

        return false;
    }

    protected function findLeftNode($rowId, $colId) {
        for ($i = $colId - 1; $i >= 0; --$i) {
            if ('.' === $this->_map[$rowId]{$i}) {
                continue;
            }

            return array($rowId, $i);
        }

        return false;
    }

    protected function findRightNode($rowId, $colId) {
        for ($i = $colId + 1; $i < $this->_width; ++$i) {
            if ('.' === $this->_map[$rowId]{$i}) {
                continue;
            }

            return array($rowId, $i);
        }

        return false;
    }

    public function linkNode($rowId, $colId) {
        $remainingLinks = $this->getRemainingLinks($rowId, $colId);
        if (false === $remainingLinks || 0 === $remainingLinks) {
            return $remainingLinks;
        }

        $neighborRemainingFreeLinks = 0;

        _d('Node to link: ' . $colId . ' ' . $rowId, true);

        $rightNode = $this->findRightNode($rowId, $colId);
        $rightRemainingFreeLinks = 0;
        if (false !== $rightNode) {
            $rightRemainingFreeLinks = $this->getRemainingLinks($rightNode[0], $rightNode[1]);
            $neighborRemainingFreeLinks += $rightRemainingFreeLinks;
        }
        _d($rightNode, true);
        _d($rightRemainingFreeLinks, true);

        $bottomNode = $this->findBottomNode($rowId, $colId);
        $bottomRemainingFreeLinks = 0;
        if (false !== $bottomNode) {
            $bottomRemainingFreeLinks = $this->getRemainingLinks($bottomNode[0], $bottomNode[1]);
            $neighborRemainingFreeLinks += $bottomRemainingFreeLinks;
        }
        _d($bottomNode, true);
        _d($bottomRemainingFreeLinks, true);

        $leftNode = $this->findLeftNode($rowId, $colId);
        $leftRemainingFreeLinks = 0;
        if (false !== $leftNode) {
            $leftRemainingFreeLinks = $this->getRemainingLinks($leftNode[0], $leftNode[1]);
            $neighborRemainingFreeLinks += $leftRemainingFreeLinks;
        }
        _d($leftNode, true);
        _d($leftRemainingFreeLinks, true);

        $topNode = $this->findTopNode($rowId, $colId);
        $topRemainingFreeLinks = 0;
        if (false !== $topNode) {
            $topRemainingFreeLinks = $this->getRemainingLinks($topNode[0], $topNode[1]);
            $neighborRemainingFreeLinks += $topRemainingFreeLinks;
        }
        _d($topNode, true);
        _d($topRemainingFreeLinks, true);

        _d('$remainingLinks: ' . $remainingLinks, true);
        _d('$neighborRemainingFreeLinks: ' . $neighborRemainingFreeLinks, true);

        //No addable link.
        if ($remainingLinks > $neighborRemainingFreeLinks) {
            return 0;
        }

        if (0 !== $rightRemainingFreeLinks) {
            for ($i = min(2 - $this->getNbLinks($rowId, $colId, $rightNode[0], $rightNode[1]), $remainingLinks, $rightRemainingFreeLinks); $i > 0; --$i) {
                _d('Add link with right', true);
                $this->addLink($rowId, $colId, $rightNode[0], $rightNode[1]);
                $remainingLinks--;
            }
        }

        if (0 !== $bottomRemainingFreeLinks) {
            for ($i = min(2 - $this->getNbLinks($rowId, $colId, $bottomNode[0], $bottomNode[1]), $remainingLinks, $bottomRemainingFreeLinks); $i > 0; --$i) {
                _d('Add link with bottom', true);
                $this->addLink($rowId, $colId, $bottomNode[0], $bottomNode[1]);
                $remainingLinks--;
            }
        }

        if (0 !== $leftRemainingFreeLinks) {
            for ($i = min(2 - $this->getNbLinks($rowId, $colId, $leftNode[0], $leftNode[1]), $remainingLinks, $leftRemainingFreeLinks); $i > 0; --$i) {
                _d('Add link with left', true);
                $this->addLink($rowId, $colId, $leftNode[0], $leftNode[1]);
                $remainingLinks--;
            }
        }

        if (0 !== $topRemainingFreeLinks) {
            for ($i = min(2 - $this->getNbLinks($rowId, $colId, $topNode[0], $topNode[1]), $remainingLinks, $topRemainingFreeLinks); $i > 0; --$i) {
                _d('Add link with top', true);
                $this->addLink($rowId, $colId, $topNode[0], $topNode[1]);
                $remainingLinks--;
            }
        }

        return $remainingLinks;
    }

    public function addLink($rowId1, $colId1, $rowId2, $colId2) {
        _d('Add link between ' . $colId1 . ' ' . $rowId1 . ' and ' . $colId2 . ' ' . $rowId2, true);

        if (!isset($this->_links[$rowId1][$colId1][$rowId2][$colId2]) && !isset($this->_links[$rowId2][$colId2][$rowId1][$colId1])) {
            $this->_links[$rowId1][$colId1][$rowId2][$colId2] = 1;
        } elseif (isset($this->_links[$rowId1][$colId1][$rowId2][$colId2])) {
            ++$this->_links[$rowId1][$colId1][$rowId2][$colId2];
        } elseif (isset($this->_links[$rowId2][$colId2][$rowId1][$colId1])) {
            ++$this->_links[$rowId2][$colId2][$rowId1][$colId1];
        }

        $this->_nodes[$rowId1][$colId1]--;
        $this->_nodes[$rowId2][$colId2]--;
    }

    public function getRemainingLinks($rowId, $colId) {
        if (!isset($this->_nodes[$rowId][$colId])) {
            return false;
        }

        return $this->_nodes[$rowId][$colId];
    }

    public function getNbLinks($rowId1, $colId1, $rowId2, $colId2) {
        if (isset($this->_links[$rowId1][$colId1][$rowId2][$colId2])) {
            return $this->_links[$rowId1][$colId1][$rowId2][$colId2];
        }

        if (isset($this->_links[$rowId2][$colId2][$rowId1][$colId1])) {
            return $this->_links[$rowId2][$colId2][$rowId1][$colId1];
        }

        return 0;
    }

    public function parse() {
        while (true) {
            $remainingLinksToAdd = 0;
            foreach ($this->_nodes as $rowId => $row) {
                foreach ($row as $colId => $node) {
                    $remainingLinksToAdd += $this->linkNode($rowId, $colId);
                }
            }
            if (0 === $remainingLinksToAdd) {
                break;
            }
        }

        foreach ($this->_links as $rowId1 => $cols) {
            foreach ($cols as $colId1 => $children) {
                foreach ($children as $rowId2 => $colsChildren) {
                    foreach ($colsChildren as $colId2 => $nbLinks) {
                        echo $colId1 . ' ' . $rowId1 . ' ' . $colId2 . ' ' . $rowId2 . ' ' . $nbLinks . "\n";
                    }
                }
            }
        }
    }
}

fscanf(STDIN, "%d", $width); // the number of cells on the X axis
fscanf(STDIN, "%d", $height); // the number of cells on the Y axis
$lines = array();
for ($i = 0; $i < $height; $i++) {
    $lines[] = stream_get_line(STDIN, 31, "\n"); // width characters, each either a number or a '.'
}

$map = new Map($lines, $width, $height);
$map->parse();

// Write an action using echo(). DON'T FORGET THE TRAILING \n
// To debug (equivalent to var_dump): error_log(var_export($var, true));

//echo("0 0 2 0 1\n"); // Two coordinates and one integer: a node, one of its neighbors, the number of links connecting them.
