<?php

define('DEBUG', false);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Map {
    const UP = 0;
    const DOWN = 1;
    const LEFT = 2;
    const RIGHT = 3;
    const NOT_LIMIT = 4;

    /** @var int Map width */
    protected $_width = null;
    /** @var int Map height */
    protected $_height = null;

    protected $_currentX = null;
    protected $_currentY = null;
    protected $_previousX = null;
    protected $_previousY = null;

    /** @var array Available cells */
    protected $_map = array();

    protected $_firstMoves = 3;

    public function __construct($w, $h, $x, $y) {
        $this->_width = $w;
        $this->_height = $h;

        $this->_currentX = $x;
        $this->_currentY = $y;
        $this->_previousX = $x;
        $this->_previousY = $y;

        $column = array('min' => 0, 'max' => $this->_height - 1);
        $this->_map = array_fill(0, $w, $column);
    }

    public function getMap() {
        return $this->_map;
    }

    public function getNewCoordinates($move = true) {
        _d($this->_currentX);
        _d($this->_currentY);

        //Get farest columns.
        $xKeys = array_keys($this->_map);
        $minX = min($xKeys);
        $maxX = max($xKeys);

        if (0 < $this->_firstMoves--) {
            $minYForMinX = $this->_currentY;
            $maxYForMinX = $this->_currentY;
            $minYForMaxX = $this->_currentY;
            $maxYForMaxX = $this->_currentY;
        } else {
            $yKeysForMinX = array_keys($this->_map[$minX]);
            $minYForMinX = min($yKeysForMinX);
            $maxYForMinX = max($yKeysForMinX);
            $yKeysForMaxX = array_keys($this->_map[$maxX]);
            $minYForMaxX = min($yKeysForMaxX);
            $maxYForMaxX = max($yKeysForMaxX);
        }

        $dists = array();
        $dists[] = $distMinXMinY = pow($this->_currentX - $minX, 2) + pow($this->_currentY - $minYForMinX, 2);
        $dists[] = $distMinXMaxY = pow($this->_currentX - $minX, 2) + pow($this->_currentY - $maxYForMinX, 2);
        $dists[] = $distMaxXMinY = pow($this->_currentX - $maxX, 2) + pow($this->_currentY - $minYForMaxX, 2);
        $dists[] = $distMaxXMaxY = pow($this->_currentX - $maxX, 2) + pow($this->_currentY - $maxYForMaxX, 2);
        $maxDist = max($dists);

        if ($distMinXMinY === $maxDist) {
            $x = $minX;
            $y = $minYForMinX;
        } elseif ($distMinXMaxY === $maxDist) {
            $x = $minX;
            $y = $maxYForMinX;
        } elseif ($distMaxXMinY === $maxDist) {
            $x = $maxX;
            $y = $minYForMaxX;
        } elseif ($distMaxXMaxY === $maxDist) {
            $x = $maxX;
            $y = $maxYForMaxX;
        } else {
            _d($dists);
            die('ERROR line ' . __LINE__ . ': ' . $maxDist);
        }

        if (true === $move) {
            $this->move($x, $y);
        }

        return array($x, $y);
    }

    public function move($x, $y) {
        $this->_previousX = $this->_currentX;
        $this->_previousY = $this->_currentY;
        $this->_currentX = $x;
        $this->_currentY = $y;
    }

    public function cleanCells($far) {
        if (
            'UNKNOWN' === $far
            || ('SAME' === $far && $this->_currentX === $this->_previousX && $this->_currentY === $this->_previousY)
        ) {
            return;
        }

        $moveX = $this->_currentX - $this->_previousX;
        $moveY = $this->_currentY - $this->_previousY;

        //We know that the movement is from X cases and Y cases.
        //Let's find the limit line between these 2 places.

        //If horizontal move, split vertically.
        if (0 === $moveY) {
            $mediane = ($this->_currentX + $this->_previousX) / 2;
            if ('COLDER' === $far) {
                $toRemove = ($this->_currentX > $this->_previousX ? self::RIGHT : self::LEFT);
            } elseif ('WARMER' === $far) {
                $toRemove = ($this->_currentX < $this->_previousX ? self::RIGHT : self::LEFT);
            } elseif ('SAME' === $far) {
                $toRemove = self::NOT_LIMIT;
            } else {
                die('ERROR line ' . __LINE__ . ': ' . $far);
            }
            $this->_eraseDataHorizontalMove($mediane, $toRemove);
            return;
        }

        /***********************
        //This part is a specific case (vertical move) of generic case, with $a = 0;
        //Check if this is more efficient.

        //If vertical move, split horizontally.
        if (0 === $moveX) {
            $mediane = ($this->_currentY + $this->_previousY) / 2;
            if ('COLDER' === $far) {
                $toRemove = ($this->_currentY > $this->_previousY ? self::UP : self::DOWN);
            } elseif ('WARMER' === $far) {
                $toRemove = ($this->_currentY < $this->_previousY ? self::UP : self::DOWN);
            } elseif ('SAME' === $far) {
                $toRemove = self::NOT_LIMIT;
            } else {
                die('ERROR line ' . __LINE__ . ': ' . $far);
            }
            $this->_eraseDataVerticalMove($mediane, $toRemove);
            return;
        }

        /**********************/

        $tmp = $moveX;
        $moveX = $moveY;
        $moveY = $tmp;
        unset($tmp);

        //look for f(x) = ax + b
        if (0 === $moveX) {
            $a = 0;
        } else {
            $a = -$moveY / $moveX;
        }

        //Center of move from old place to new place.
        $centerX = ($this->_currentX + $this->_previousX) / 2;
        $centerY = ($this->_currentY + $this->_previousY) / 2;

        $b = $centerY - ($a * $centerX);

        $f = $this->_getF($a, $b);

        _d('$centerY: ' . $centerY);
        _d('$a: ' . $a);
        _d('$centerX: ' . $centerX);
        _d('$b: ' . $b);
        _d('$f($centerX): ' . $f($centerX));
        _d('$f(2): ' . $f(2));

        if ('SAME' === $far) {
            $toRemove = self::NOT_LIMIT;
        } elseif ('COLDER' === $far) {
            $toRemove = ($this->_currentY > $this->_previousY ? self::UP : self::DOWN);
        } elseif ('WARMER' === $far) {
            $toRemove = ($this->_currentY < $this->_previousY ? self::UP : self::DOWN);
        } else {
            die('ERROR line ' . __LINE__ . ': ' . $far);
        }

        $this->_eraseData($f, $toRemove);
    }

    protected function _getF($a, $b) {
        return function($x) use ($a, $b) {
            return $a * $x + $b;
        };
    }

    protected function _eraseData($f, $toRemove) {
        for ($x = $this->_width - 1; 0 <= $x; --$x) {
            if (!isset($this->_map[$x])) {
                continue;
            }

            //$mapX =& $this->_map[$x];

            $limitY = $f($x);
            _d('$f(' . $x . ') = ' . $limitY);
            if (self::UP === $toRemove) {
                for ($y = max(array_keys($this->_map[$x])); $limitY <= $y; --$y) {
                    unset($this->_map[$x][$y]);
                }
            } elseif (self::DOWN === $toRemove) {
                for ($y = min(array_keys($this->_map[$x])); $y <= $limitY; ++$y) {
                    unset($this->_map[$x][$y]);
                }
            } elseif (self::NOT_LIMIT === $toRemove) {
                //If $x $limitY has already been deleted, remove the whole column.
                if (!isset($this->_map[$x][$limitY])) {
                    unset($this->_map[$x]);
                    continue;
                }

                //If not, keep only $x $limitY
                $this->_map[$x] = array($limitY => true);
            } else {
                die('ERROR line ' . __LINE__ . ': ' . $toRemove);
            }
        }
        $this->_purgeMap();
    }

    protected function _purgeMap() {
        for ($x = $this->_width; 0 <= $x; --$x) {
            if (empty($this->_map[$x])) {
                unset($this->_map[$x]);
            }
        }
    }

    protected function _eraseDataHorizontalMove($limitX, $toRemove) {
        if (self::RIGHT === $toRemove) {
            for ($x = max(array_keys($this->_map)); $limitX <= $x; --$x) {
                unset($this->_map[$x]);
            }
        } elseif (self::LEFT === $toRemove) {
            for ($x = min(array_keys($this->_map)); $x <= $limitX; ++$x) {
                unset($this->_map[$x]);
            }
        } elseif (self::NOT_LIMIT === $toRemove) {
            for ($x = $this->_width - 1; 0 <= $x; --$x) {
                //Don't remove column if limit.
                if ($x === $limitX) {
                    continue;
                }

                unset($this->_map[$x]);
            }
        } else {
            _d('ERROR line ' . __LINE__ . ': ' . $toRemove);
        }
    }

    protected function _eraseDataVerticalMove($limitY, $toRemove) {
        for ($x = $this->_width - 1; 0 <= $x; --$x) {
            if (self::UP === $toRemove) {
                for ($y = $this->_height; $limitY <= $y; --$y) {
                    unset($this->_map[$x][$y]);
                }
            } elseif (self::DOWN === $toRemove) {
                for ($y = 0; $y <= $limitY; ++$y) {
                    unset($this->_map[$x][$y]);
                }
            } elseif (self::NOT_LIMIT === $toRemove) {
                //If $x $limitY has already been deleted, remove the whole column.
                if (!isset($this->_map[$x][$limitY])) {
                    unset($this->_map[$x]);
                    continue;
                }

                //If not, keep only $x $limitY
                $this->_map[$x] = array($limitY => true);
            } else {
                die('ERROR line ' . __LINE__ . ': ' . $toRemove);
            }
        }
        $this->_purgeMap();
    }
}

// width of the building.
// height of the building.
fscanf(STDIN, "%d %d", $W, $H);
fscanf(STDIN, "%d", $N); // maximum number of turns before game over.
fscanf(STDIN, "%d %d", $X0, $Y0);

$map = new Map($W, $H, $X0, $Y0);

// game loop
while (TRUE) {
    // Current distance to the bomb compared to previous distance (COLDER, WARMER, SAME or UNKNOWN)
    fscanf(STDIN, "%s", $BOMB_DIST);

    $map->cleanCells($BOMB_DIST);

    _d($map->getMap());

    list($X0, $Y0) = $map->getNewCoordinates();

    echo $X0 . " " . $Y0 . "\n";
}
