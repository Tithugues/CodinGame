<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Node {
    protected $_x = null;
    protected $_y = null;
    protected $_content = null;
    protected $_read = false;
    protected $_lakeId = null;

    public function __construct($x, $y, $content) {
        $this->_x = $x;
        $this->_y = $y;
        $this->_content = $content;
    }

    public function getX() {
        return $this->_x;
    }

    public function getY() {
        return $this->_y;
    }

    public function getContent() {
        return $this->_content;
    }

    public function isRead() {
        return $this->_read;
    }

    public function read() {
        $this->_read = true;
        return $this;
    }

    public function getLakeId() {
        return $this->_lakeId;
    }

    public function setLakeId($lakeId) {
        $this->_lakeId = $lakeId;
        return $this;
    }
}

class Lake {
    protected $_id = null;
    protected $_surface = 0;

    public function __construct($id) {
        $this->_id = $id;
    }

    public function addSurface($surface = 1) {
        $this->_surface += $surface;
        return $this;
    }

    public function getSurface() {
        return $this->_surface;
    }
}

class Map {
    /** @var Node[][] */
    protected $_nodes = array();
    /** @var Lake[] */
    protected $_lakes = array();
    /** @var Node[] */
    protected $_stack = array();

    public function addNode(Node $node) {
        $this->_nodes[$node->getX()][$node->getY()] = $node;
    }

    public function foundUnreadWatersClosedTo($x, $y) {
        /** @var Node[] $waters */
        $waters = array();
        if (
            isset($this->_nodes[$x - 1])
            && 'O' === $this->_nodes[$x - 1][$y]->getContent()
            && false === $this->_nodes[$x - 1][$y]->isRead()
        ) {
            $waters[] = $this->_nodes[$x-1][$y];
        }
        if (
            isset($this->_nodes[$x+1])
            && 'O' === $this->_nodes[$x+1][$y]->getContent()
            && false === $this->_nodes[$x+1][$y]->isRead()
        ) {
            $waters[] = $this->_nodes[$x+1][$y];
        }
        if (
            isset($this->_nodes[$x][$y-1])
            && 'O' === $this->_nodes[$x][$y-1]->getContent()
            && false === $this->_nodes[$x][$y-1]->isRead()
        ) {
            $waters[] = $this->_nodes[$x][$y-1];
        }
        if (
            isset($this->_nodes[$x][$y+1])
            && 'O' === $this->_nodes[$x][$y+1]->getContent()
            && false === $this->_nodes[$x][$y+1]->isRead()
        ) {
            $waters[] = $this->_nodes[$x][$y+1];
        }
        debug('foundUnreadWatersClosedTo');
        debug($waters);
        return $waters;
    }

    public function getSurfaceLake($x, $y) {
        debug('getSurfaceLake');
        debug($x);
        debug($y);
        debug($this->_nodes[$x][$y]);
        $startNode = $this->_nodes[$x][$y];

        if ('#' === $startNode->getContent()) {
            return 0;
        }

        if (null !== ($lakeId = $startNode->getLakeId())) {
            return $this->_lakes[$lakeId]->getSurface();
        }

        $lakeId = count($this->_lakes);
        $lake = new Lake($lakeId);
        $this->_lakes[$lakeId] = $lake;

        $this->_stack[] = $startNode;

        while (!empty($this->_stack)) {
            debug('while');
            /** @var Node $node */
            $node = array_shift($this->_stack);
            if ($node->isRead()) {
                continue;
            }

            $node->read();
            $node->setLakeId($lakeId);
            $lake->addSurface();
            $this->_stack = array_merge($this->_stack, $this->foundUnreadWatersClosedTo($node->getX(), $node->getY()));
        }

        return $lake->getSurface();
    }
}

fscanf(STDIN, "%d", $width);
fscanf(STDIN, "%d", $height);
$map = new Map();
for ($rowId = 0; $rowId < $height; $rowId++)
{
    $line = stream_get_line(STDIN, $width, "\n");
    $contents = str_split($line);
    foreach ($contents as $colId => $content) {
        $map->addNode(new Node($colId, $rowId, $content));
    }
}
fscanf(STDIN, "%d", $numberCoordinates);
$searchedLakes = array();
for ($rowId = 0; $rowId < $numberCoordinates; $rowId++)
{
    fscanf(STDIN, "%d %d", $x, $y);
    $searchedLakes[] = array('x' => $x, 'y' => $y);
}

foreach ($searchedLakes as $searchedLake) {
    $surface = $map->getSurfaceLake($searchedLake['x'], $searchedLake['y']);
    debug('Surface: ' . $surface);
    echo $surface . "\n";
}