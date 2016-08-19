<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

class Map {
    protected $_list = array();

    public function addRow($row) {
        $this->_list[] = str_split($row);
        return true;
    }

    public function getStartPosition() {
        foreach ($this->_list as $rowId => $row) {
            foreach ($row as $colId => $cell) {
                if ('@' === $cell) {
                    return array($colId, $rowId);
                }
            }
        }

        return false;
    }

    public function getBloc($colId, $rowId) {
        if (!isset($this->_list[$rowId][$colId])) {
            return false;
        }

        return $this->_list[$rowId][$colId];
    }

    public function breakBloc($colId, $rowId) {
        if (!isset($this->_list[$rowId][$colId])) {
            return false;
        }

        if ('X' !== $this->_list[$rowId][$colId]) {
            return false;
        }

        $this->_list[$rowId][$colId] = ' ';
        return true;
    }
}

class Bender {
    const CLOCKWISE = 1;
    const COUNTERCLOCKWISE = 2;

    const DIRECTION_SOUTH = 'SOUTH';
    const DIRECTION_EAST = 'EAST';
    const DIRECTION_NORTH = 'NORTH';
    const DIRECTION_WEST = 'WEST';


    protected $_breaker = false;
    protected $_rotation = self::COUNTERCLOCKWISE;
    protected $_direction = self::DIRECTION_SOUTH;

    protected $_directions = array(
        self::DIRECTION_SOUTH,
        self::DIRECTION_EAST,
        self::DIRECTION_NORTH,
        self::DIRECTION_WEST
    );

    public function getDirection() {
        return $this->_direction;
    }

    protected function _setDirection($direction) {
        $this->_direction = $direction;
    }

    public function changeDirection() {
        $dirKey = current(array_keys($this->_directions, $this->_direction));

        if (self::COUNTERCLOCKWISE === $this->_rotation) {
            $dirKey = ($dirKey+1)%4;
        } else {
            $dirKey--;
            if (-1 === $dirKey) {
                $dirKey = 3;
            }
        }

        $this->_setDirection($this->_directions[$dirKey]);

        return $this->getDirection();
    }

    public function isBreaker() {
        return $this->_breaker;
    }
}

class BenderManager {
    protected $_map;
    protected $_bender;
    protected $_x;
    protected $_y;

    public function __construct(Map $map, Bender $bender) {
        $this->_map = $map;
        $this->_bender = $bender;

        $this->_initialisePosition();
    }

    protected function _initialisePosition() {
        $position = $this->_map->getStartPosition();
        if (false === $position) {
            return false;
        }

        $this->_x = $position[0];
        $this->_y = $position[1];
        return true;
    }

    protected function _move() {

    }
}

fscanf(STDIN, "%d %d",
    $L,
    $C
);
$map = new Map();
for ($i = 0; $i < $L; $i++)
{
    $row = stream_get_line(STDIN, $C, "\n");
    $map->addRow($row);
}

// Write an action using echo(). DON'T FORGET THE TRAILING \nð
// To debug (equivalent to var_dump): error_log(var_export($var, true));

echo("answer\n");
