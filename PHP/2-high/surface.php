<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Map {
    protected $_map = null;
    protected $_mapSurf = array();
    protected $_width = null;
    protected $_height = null;
    protected $_currentSurface = 0;
    protected $_stack = array();

    public function __construct($map, $width, $height) {
        $this->_map = $map;
        $this->_width = $width;
        $this->_height = $height;
        $this->_length = $width * $height;
    }

    protected function _getIndex($x, $y) {
        return $y * $this->_width + $x;
    }

    public function explore($x, $y) {
        $this->_addStack($x, $y);
        $this->_explore();

        unset($this->_currentSurface);
        $this->_currentSurface = 0;

        $index = $this->_getIndex($x, $y);
        return $this->_mapSurf[$index];
    }

    protected function _explore() {
        while (null !== ($coordinates = array_shift($this->_stack))) {
            $x = array_shift($coordinates);
            $y = array_shift($coordinates);
            $index = $this->_getIndex($x, $y);
            ++$this->_mapSurf[$index];

            if ($this->_width !== $x + 1) {
                $this->_addStack($x + 1, $y);
            }
            if (0 !== $x) {
                $this->_addStack($x - 1, $y);
            }
            if ($this->_height !== $y + 1) {
                $this->_addStack($x, $y + 1);
            }
            if (0 !== $y) {
                $this->_addStack($x, $y - 1);
            }
        }

        return $this;
    }

    protected function _addStack($x, $y) {
        $index = $this->_getIndex($x, $y);
        if (isset($this->_mapSurf[$index])) {
            return $this;
        }

        if ('#' === $this->_map[$index]) {
            $this->_mapSurf[$index] = 0;
            return $this;
        }

        $this->_mapSurf[$index] =& $this->_currentSurface;
        $this->_stack[] = array($x, $y);
        return $this;
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

/*debug(number_format(memory_get_peak_usage()), true);
debug(number_format(memory_get_peak_usage(true)), true);*/