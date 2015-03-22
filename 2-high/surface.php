<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
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
    /** @var array[][][] */
    protected $_nodes = array();
    /** @var Lake[] */
    protected $_lakes = array();
    /** @var Node[] */
    protected $_stack = array();

    public function addNode($x, $y, $content) {
        $this->_nodes[$x][$y] = array(
            'x' => $x,
            'y' => $y,
            'content' => $content,
            'lakeId' => null,
            'read' => false
        );
    }

    public function foundUnreadWatersClosedTo($x, $y) {
        $waters = array();
        if (
            isset($this->_nodes[$x-1])
            && 'O' === $this->_nodes[$x-1][$y]['content']
            && false === $this->_nodes[$x-1][$y]['read']
        ) {
            debug('ajoutée 1');
            $this->_nodes[$x-1][$y]['read'] = true;
            $waters[] = $this->_nodes[$x-1][$y];
        }
        if (
            isset($this->_nodes[$x+1])
            && 'O' === $this->_nodes[$x+1][$y]['content']
            && false === $this->_nodes[$x+1][$y]['read']
        ) {
            debug('ajoutée 2');
            $this->_nodes[$x+1][$y]['read'] = true;
            $waters[] = $this->_nodes[$x+1][$y];
        }
        if (
            isset($this->_nodes[$x][$y-1])
            && 'O' === $this->_nodes[$x][$y-1]['content']
            && false === $this->_nodes[$x][$y-1]['read']
        ) {
            debug('ajoutée 3');
            $this->_nodes[$x][$y-1]['read'] = true;
            $waters[] = $this->_nodes[$x][$y-1];
        }
        if (
            isset($this->_nodes[$x][$y+1])
            && 'O' === $this->_nodes[$x][$y+1]['content']
            && false === $this->_nodes[$x][$y+1]['read']
        ) {
            debug('ajoutée 4');
            $this->_nodes[$x][$y+1]['read'] = true;
            $waters[] = $this->_nodes[$x][$y+1];
        }
        debug('foundUnreadWatersClosedTo');
        debug($x);
        debug($y);
        debug($waters);
        return $waters;
    }

    public function getSurfaceLake($x, $y) {
        debug('getSurfaceLake');
        debug($x);
        debug($y);
        debug($this->_nodes[$x][$y]);
        $startNode = $this->_nodes[$x][$y];

        if ('#' === $startNode['content']) {
            return 0;
        }

        if (null !== ($lakeId = $startNode['lakeId'])) {
            return $this->_lakes[$lakeId]->getSurface();
        }

        $lakeId = count($this->_lakes);
        $lake = new Lake($lakeId);
        $this->_lakes[$lakeId] = $lake;

        $this->_stack[] = $startNode;

        while (!empty($this->_stack)) {
            debug('while');
            $node = array_shift($this->_stack);
            //$node =& $this->_nodes[$node['x']][$node['y']];
            /*if ($this->_nodes[$node['x']][$node['y']]['read']) {
                debug('already read');
                continue;
            }*/

            $this->_nodes[$node['x']][$node['y']]['read'] = true;
            $this->_nodes[$node['x']][$node['y']]['lakeId'] = $lakeId;
            $lake->addSurface();
            $this->_stack = array_merge($this->_stack, $this->foundUnreadWatersClosedTo($this->_nodes[$node['x']][$node['y']]['x'], $this->_nodes[$node['x']][$node['y']]['y']));
        }

        return $lake->getSurface();
    }
}

fscanf(STDIN, "%d", $width);
fscanf(STDIN, "%d", $height);
$map = new Map();
for ($rowId = 0; $rowId < $height; $rowId++)
{
    debug('read: ' . $rowId, true);
    fscanf(STDIN, "%s", $line);
    $length = strlen($line);
    for ($colId = 0; $colId < $length; ++$colId) {
        $map->addNode($colId, $rowId, $line{$colId});
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