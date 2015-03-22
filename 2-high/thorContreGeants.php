<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Person {
    protected $_x = null;
    protected $_y = null;

    public function __construct($x, $y) {
        $this->_x = $x;
        $this->_y = $y;
    }

    public function getX() {
        return $this->_x;
    }

    public function getY() {
        return $this->_y;
    }

    public function move($x, $y) {
        $this->_x = $x;
        $this->_y = $y;
    }
}

class Map {
    const WIDTH = 40;
    const HEIGHT = 18;

    protected $_giants = array();
    /**
     * @var Person
     */
    protected $_thor = null;

    public function __construct($thor) {
        $this->_thor = $thor;
    }

    public function setGiants($giants) {
        $this->_giants = $giants;
    }

    protected function _getDistsFromGiants($x, $y) {
        $dists = array();

        /** @var Person $giant */
        foreach ($this->_giants as $id => $giant) {
            $distX = abs($giant->getX() - $x);
            $distY = abs($giant->getY() - $y);
            if (($dist = ($distX < $distY) ? $distY : $distX) < 2) {
                return false;
            }

            $dists[$id] = $dist;
        }

        return $dists;
    }

    protected function getNbDeathIfStrike() {
        $thorX = $this->_thor->getX();
        $thorY = $this->_thor->getY();
        $minX = max($thorX - 1, 0);
        $maxX = min($thorX + 1, self::WIDTH - 1);
        $minY = max($thorY - 1, 0);
        $maxY = min($thorY + 1, self::HEIGHT - 1);


    }

    public function findNewPos() {
        $thorX = $this->_thor->getX();
        $thorY = $this->_thor->getY();
        $minX = max($thorX - 1, 0);
        $maxX = min($thorX + 1, self::WIDTH - 1);
        $minY = max($thorY - 1, 0);
        $maxY = min($thorY + 1, self::HEIGHT - 1);

        $possiblePos = array();
        for ($x = $minX; $x <= $maxX; ++$x) {
            for ($y = $minY; $y <= $maxY; ++$y) {
                if (is_array($dists = $this->_getDistsFromGiants($x, $y))) {
                    $possiblePos[$x][$y] = $dists;
                    break;
                }

            }
        }

        debug($possiblePos, true);

        //No calm place.
        if (0 === count($possiblePos)) {
            return 'STRIKE';
        }

        return 'WAIT';
    }

    protected function _getDirection($x, $y) {
        $dir = null;
        $thorX = $this->_thor->getX();
        $thorY = $this->_thor->getY();

        if ($y < $thorY) {
            $dir .= 'N';
        } elseif ($thorY < $y) {
            $dir .= 'S';
        }

        if ($x < $thorX) {
            $dir .= 'W';
        } elseif ($thorX < $x) {
            $dir .= 'E';
        }

        return $dir;
    }
}

fscanf(STDIN, "%d %d", $thorPosX, $thorPosY);
$thor = new Person($thorPosX, $thorPosY);

$map = new Map($thor);

// game loop
while (TRUE) {
    debug('$thorPosX: ' . $thorPosX, true);
    debug('$thorPosY: ' . $thorPosY, true);
    fscanf(STDIN, "%d %d", $remainingHammersStrikes, $giantsNumber);
    /** @var Person[] $giants */
    $giants = array();
    for ($i = 0; $i < $giantsNumber; $i++) {
        fscanf(STDIN, "%d %d", $giantPosX, $giantPosY);
        $giants[] = new Person($giantPosX, $giantPosY);
    }

    $map->setGiants($giants);

    echo $map->findNewPos() . "\n";
}
