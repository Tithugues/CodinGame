<?php

abstract class Tile {
    const LEFT = 'LEFT';
    const TOP = 'TOP';
    const RIGHT = 'RIGHT';
    const BOTTOM = 'BOTTOM';

    //Gates are 0 = left, 1 = top, 2 = right, 3 = bottom
    protected $_ways = array();

    /**
     * Get exit for a specific entry
     * @param int $entry Entry
     * @return int Exit for an entry
     */
    public function getExit($entry) {
        if (!isset($this->_ways[$entry])) {
            return false;
        }

        return $this->_ways[$entry];
    }
}

class Tile0 extends Tile {
}

class Tile1 extends Tile {
    protected $_ways = array(self::LEFT => self::BOTTOM, self::TOP => self::BOTTOM, self::RIGHT => self::BOTTOM);
}

class Tile2 extends Tile {
    protected $_ways = array(self::LEFT => self::RIGHT, self::RIGHT => self::LEFT);
}

class Tile3 extends Tile {
    protected $_ways = array(self::TOP => self::BOTTOM);
}

class Tile4 extends Tile {
    protected $_ways = array(self::TOP => self::LEFT, self::RIGHT => self::BOTTOM);
}

class Tile5 extends Tile {
    protected $_ways = array(self::TOP => self::RIGHT, self::LEFT => self::BOTTOM);
}

class Tile6 extends Tile {
    protected $_ways = array(self::LEFT => self::RIGHT, self::RIGHT => self::LEFT);
}

class Tile7 extends Tile {
    protected $_ways = array(self::TOP => self::BOTTOM, self::RIGHT => self::BOTTOM);
}

class Tile8 extends Tile {
    protected $_ways = array(self::LEFT => self::BOTTOM, self::RIGHT => self::BOTTOM);
}

class Tile9 extends Tile {
    protected $_ways = array(self::LEFT => self::BOTTOM, self::TOP => self::BOTTOM);
}

class Tile10 extends Tile {
    protected $_ways = array(self::TOP => self::LEFT);
}

class Tile11 extends Tile {
    protected $_ways = array(self::TOP => self::RIGHT);
}

class Tile12 extends Tile {
    protected $_ways = array(self::RIGHT => self::BOTTOM);
}

class Tile13 extends Tile {
    protected $_ways = array(self::LEFT => self::BOTTOM);
}

class Puzzle {
    /**
     * @var array Tiles array
     */
    protected $_board = array();
    /**
     * @var array Current Indiana position
     */
    protected $_position = array();

    public function addRow($row) {
        $aTilesTypes = explode(' ', $row);
        for ($i = 0, $nbColumns = count($aTilesTypes); $i < $nbColumns; $i++) {
            $className = 'Tile' . $aTilesTypes[$i];
            $this->_board[$i][] = new $className();
        }
    }

    public function enter($column, $row) {
        $this->_position = array($column, $row);
    }

    public function move($entryPos) {
        $position = $this->_position;
        /** @var Tile $tile */
        $tile = $this->_board[$position[0]][$position[1]];
        $direction = $tile->getExit($entryPos);

        return $this->getNewPosition($direction);
    }

    protected function getNewPosition($direction) {
        $position = $this->_position;
        if (Tile::RIGHT === $direction) {
            return array($position[0] + 1, $position[1]);
        } elseif (Tile::BOTTOM === $direction) {
            return array($position[0], $position[1] + 1);
        } elseif (Tile::LEFT === $direction) {
            return array($position[0] - 1, $position[1]);
        }

        return false;
    }
}