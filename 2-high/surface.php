<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Map {
    protected $_map = null;
    protected $_width = null;
    protected $_height = null;
    protected $_currentSurface = 0;

    public function __construct($map, $width, $height) {
        $this->_map = str_split($map);
        $this->_width = $width;
        $this->_height = $height;
        $this->_length = $width * $height;
    }

    protected function _getIndex($x, $y) {
        return $y * $this->_width + $x;
    }

    public function explore($x, $y) {
        $index = $this->_getIndex($x, $y);
        debug('explore', true);
        debug($index, true);
        if ($index < 0 || $this->_length <= $index) {
            return false;
        }

        debug($this->_map[$index], true);

        //Earth
        if ('#' === $this->_map[$index]) {
            return 0;
        }

        //Not earth, not water
        if ('O' !== $this->_map[$index]) {
            return $this->_map[$index];
        }

        unset($this->_currentSurface);
        $this->_currentSurface = 1;
        $this->_map[$index] =& $this->_currentSurface;

        $this->_explore($x + 1, $y);
        $this->_explore($x - 1, $y);
        $this->_explore($x, $y + 1);
        $this->_explore($x, $y - 1);

        return $this->_currentSurface;
    }

    protected function _explore($x, $y) {
        $index = $this->_getIndex($x, $y);
        debug('subexplore', true);
        debug($x, true);
        debug($y, true);
        debug($index, true);
        if ($index < 0 || $this->_length <= $index) {
            return false;
        }

        //Earth
        if ('#' === $this->_map[$index]) {
            return 0;
        }

        //Not earth, not water
        if ('O' !== $this->_map[$index]) {
            return $this->_map[$index];
        }

        $this->_currentSurface++;
        $this->_map[$index] =& $this->_currentSurface;

        $this->_explore($x + 1, $y);
        $this->_explore($x - 1, $y);
        $this->_explore($x, $y + 1);
        $this->_explore($x, $y - 1);

        return $this->_currentSurface;
    }
}

fscanf(STDIN, "%d", $width);
fscanf(STDIN, "%d", $height);
$map = null;
for ($rowId = 0; $rowId < $height; $rowId++)
{
    fscanf(STDIN, "%s", $line);
    $map .= $line;
}
$map = new Map($map, $width, $height);

fscanf(STDIN, "%d", $numberCoordinates);
$surfaces = array();
for ($rowId = 0; $rowId < $numberCoordinates; $rowId++)
{
    fscanf(STDIN, "%d %d", $x, $y);
    $surfaces[] = $map->explore($x, $y);
}

echo implode("\n", $surfaces) . "\n";
