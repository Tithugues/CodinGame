<?php

define('DEBUG', true);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Fx {
    /** @var float */
    protected $_a = null;
    /** @var float */
    protected $_b = null;

    /**
     * Construct a f(x)
     * @param $a
     * @param $b
     */
    public function __construct($a, $b) {
        $this->_a = (float)$a;
        $this->_b = (float)$b;
    }

    /**
     * Get "a" constant
     * @return float
     */
    public function getA() {
        return $this->_a;
    }

    /**
     * Get "b" constant
     * @return float
     */
    public function getB() {
        return $this->_b;
    }

    /**
     * Calculate Y from X
     * @param float $x
     * @return float
     */
    public function getY($x) {
        return $this->getA() * $x + $this->getB();
    }

    /**
     * Calculate X from Y
     * @param float $y
     * @return float
     */
    public function getXFromY($y) {
        return ($y - $this->getB()) / $this->getA();
    }

    /**
     * Get X of crossing point
     * @param Fx $f
     * @return bool|float X of crossing point or false if parallels
     */
    public function getCrossingX(Fx $f) {
        if ($this->getA() == $f->getA()) {
            return false;
        }

        return ($this->getB() - $f->getB()) / ($f->getA() - $this->getA());
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

        $xKeys = array_keys($this->_map);
        $minX = min($xKeys);
        if (1 === count($this->_map) && $this->_map[$minX]['min'] === $this->_map[$minX]['max']) {
            $x = $minX;
            $y = $this->_map[$minX]['min'];

            if (true === $move) {
                $this->move($x, $y);
            }

            return array($x, $y);
        }

        //Get farest columns.
        $corners = array();
        $minYForMinX = $this->_map[$minX]['min'];
        $maxYForMinX = $this->_map[$minX]['max'];
        $corners[] = array($minX, $minYForMinX);
        $corners[] = array($minX, $maxYForMinX);
        $maxX = max($xKeys);
        $minYForMaxX = $this->_map[$maxX]['min'];
        $maxYForMaxX = $this->_map[$maxX]['max'];
        $corners[] = array($maxX, $minYForMaxX);
        $corners[] = array($maxX, $maxYForMaxX);

        //Find closest corner.
        $closestCornerDist = null;
        $closestCornerX = null;
        $closestCornerY = null;
        //Find farest corner.
        $farestCornerDist = null;
        $farestCornerX = null;
        $farestCornerY = null;
        foreach ($corners as $cornerId => $corner/*list($cornerX, $cornerY)*/) {
            list($cornerX, $cornerY) = $corner;
            $dist = $this->_getDist($this->_currentX, $this->_currentY, $cornerX, $cornerY);
            _d('Corner ' . $cornerId . ': ' . $dist);
            if (null === $closestCornerDist || $dist < $closestCornerDist) {
                _d('closest');
                $closestCornerDist = $dist;
                $closestCornerX = $cornerX;
                $closestCornerY = $cornerY;
            }
            if (null === $farestCornerDist || $farestCornerDist <= $dist) {
                _d('farest');
                $farestCornerDist = $dist;
                $farestCornerX = $cornerX;
                $farestCornerY = $cornerY;
            }
        }

        _d('closestCorner: ' . $closestCornerX . ' ' . $closestCornerY);
        _d('farestCorner: ' . $farestCornerX . ' ' . $farestCornerY);

        $fMedian = $this->_getFx($closestCornerX, $closestCornerY, $farestCornerX, $farestCornerY);
        _d($fMedian);


        //If median is vertical...
        if (false === $fMedian) {
            $x = $closestCornerX;
            $y = $this->_limitY($farestCornerY - ($this->_currentY - $closestCornerY));

            if (true === $move) {
                $this->move($x, $y);
            }

            return array($x, $y);
        }

        if (0 == $fMedian->getA()) {
            $x = $this->_limitX($farestCornerX - ($this->_currentX - $closestCornerX));
            $y = $this->_limitY(round($fMedian->getY($x)));

            if (true === $move) {
                $this->move($x, $y);
            }

            return array($x, $y);
        }

        //$x = $this->_limitX($farestCornerX - ($this->_currentX - $closestCornerX));
        $fPerp = $this->_getFPerpendicular($closestCornerX, $closestCornerY, $farestCornerX, $farestCornerY, $this->_currentX, $this->_currentY);
        $crossingX = $fMedian->getCrossingX($fPerp);
        $crossingX2 = $farestCornerX - ($crossingX - $closestCornerX);
        $fPerp2 = $this->_getFxFromA($fPerp->getA(), $crossingX2, $fMedian->getY($crossingX2));

        $fMove = $this->_getFxFromA($fMedian->getA(), $this->_currentX, $this->_currentY);

        $crossingX3 = $fMove->getCrossingX($fPerp2);
        $fLimit = $this->_getFx($crossingX3, $fPerp2->getY($crossingX3), $farestCornerX, $farestCornerY);

        $x = round($crossingX3);
        $step = ($x < $farestCornerX ? 1 : -1);

        do {

            //$fSeparation = $this->_getFSeparation($closestCornerX, $closestCornerY, $farestCornerX, $farestCornerY);


            $y = round($fLimit->getY($x));

        } while (!$this->_isInMap($x, $y) && $x += $step);
        _d($x);
        _d($y);

        if ($this->_isInMap($x, $y)) {
            _d('isInMap');
            if (true === $move) {
                $this->move($x, $y);
            }

            return array($x, $y);
        }

        _d('is not in map');

        //If out of map, find new point...
        $fx = $this->_getFx($this->_currentX, $this->_currentY, $x, $y);

        //If f(x) vertical, stop on border
        if (false === $fx) {
            if ($y < 0) {
                $y = 0;
            } else {
                $y = $this->_height - 1;
            }
            if ($x < 0) {
                $x = 0;
            } else {
                $x = $this->_width - 1;
            }

            if (true === $move) {
                $this->move($x, $y);
            }

            return array($x, $y);
        }

        if ($x < 0) {
            $x = 0;
            $y = round($fx->getY($x));
        } elseif ($this->_width <= $x) {
            $x = $this->_width - 1;
            $y = round($fx->getY($x));
        }

        if ($y < 0) {
            _d($fx);
            $y = 0;
            $x = round($fx->getXFromY($y));
        } elseif ($this->_height <= $y) {
            $y = $this->_height - 1;
            $x = round($fx->getXFromY($y));
        }

        if (true === $move) {
            $this->move($x, $y);
        }

        return array($x, $y);
    }

    protected function _limitX($x) {
        return min($this->_width - 1, max(0, $x));
    }

    protected function _limitY($y) {
        return min($this->_height - 1, max(0, $y));
    }

    protected function _isInMap($x, $y) {
        return 0 <= $x && $x < $this->_width && 0 <= $y && $y < $this->_height;
    }

    protected function _getDist($x1, $y1, $x2, $y2) {
        _d('a²: ' . pow($x2 - $x1, 2));
        _d('b²: ' . pow($y2 - $y1, 2));
        return sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2));
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

        $f = $this->_getFSeparation($this->_previousX, $this->_previousY, $this->_currentX, $this->_currentY);

        //We know that the movement is from X cases and Y cases.
        //Let's find the limit line between these 2 places.

        //If horizontal move, split vertically.
        if (false === $f) {
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

        _d($f);
        //_d('$f(2): ' . $f->getY(2));

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

    /**
     * Generate Fx from a and b
     * @param int $a
     * @param int $b
     * @return Fx
     */
    protected function _generateFx($a, $b) {
        return new Fx($a, $b);
    }

    /**
     * Get f(x) from 2 points
     * @param int $x1 X first point
     * @param int $y1 Y first point
     * @param int $x2 X second point
     * @param int $y2 Y second point
     * @return bool|Fx f(x) or false if perpendicular is vertical
     */
    protected function _getFx($x1, $y1, $x2, $y2) {
        $moveX = $x2 - $x1;

        //If vertical move, split horizontally. No f(x) possible.
        if (0 === $moveX) {
            return false;
        }

        $moveY = $y2 - $y1;

        //look for f(x) = ax + b
        $a = $moveY / $moveX;
        $b = $y1 - ($a * $x1);

        return $this->_generateFx($a, $b);
    }

    protected function _getFxFromA($a, $x, $y) {
        $b = $y - ($a * $x);

        return $this->_generateFx($a, $b);
    }

    /**
     * Get perpendicular line between 2 points, crossing one specific point.
     * @param int $x1 X first point
     * @param int $y1 Y first point
     * @param int $x2 X second point
     * @param int $y2 Y second point
     * @param int $crossX X point to cross
     * @param int $crossY Y point to cross
     * @return bool|Fx f(x) or false if perpendicular is vertical
     */
    protected function _getFPerpendicular($x1, $y1, $x2, $y2, $crossX, $crossY) {
        $moveY = $y2 - $y1;

        //If horizontal move, split vertically. No f(x) possible.
        if (0 == $moveY) {
            return false;
        }

        $moveX = $x2 - $x1;

        $tmp = $moveX;
        $moveX = $moveY;
        $moveY = $tmp;
        unset($tmp);

        //look for f(x) = ax + b
        $a = -$moveY / $moveX;
        $b = $crossY - ($a * $crossX);

        return $this->_generateFx($a, $b);
    }

    /**
     * Get separation line between 2 points.
     * Unlike perpendicular, separation is going through the center of the 2 points.
     * @param int $x1 X first point
     * @param int $y1 Y first point
     * @param int $x2 X second point
     * @param int $y2 Y second point
     * @return bool|Fx f(x) or false if perpendicular is vertical
     */
    protected function _getFSeparation($x1, $y1, $x2, $y2) {
        //Center of move from old point to new point.
        $centerX = ($x1 + $x2) / 2;
        $centerY = ($y1 + $y2) / 2;

        return $this->_getFPerpendicular($x1, $y1, $x2, $y2, $centerX, $centerY);
    }

    /**
     * Erase data
     * @param Fx $f f(x)
     * @param int $toRemove Direction to remove
     */
    protected function _eraseData($f, $toRemove) {
        _d($toRemove);
        for ($x = $this->_width - 1; 0 <= $x; --$x) {
            if (!isset($this->_map[$x])) {
                continue;
            }

            $limitY = $f->getY($x);
            //_d('$f(' . $x . ') = ' . $limitY);
            if (self::UP === $toRemove) {
                $limitY = ceil($limitY-1);
                if ($this->_map[$x]['max'] <= $limitY) {
                    continue;
                }
                if ($limitY < $this->_map[$x]['min']) {
                    unset($this->_map[$x]);
                    continue;
                }
                $this->_map[$x]['max'] = $limitY;
            } elseif (self::DOWN === $toRemove) {
                $limitY = floor($limitY+1);
                if ($limitY <= $this->_map[$x]['min']) {
                    continue;
                }
                if ($this->_map[$x]['max'] < $limitY) {
                    unset($this->_map[$x]);
                    continue;
                }
                $this->_map[$x]['min'] = $limitY;
            } elseif (self::NOT_LIMIT === $toRemove) {
                //If $x $limitY has already been deleted, remove the whole column.
                //if (!isset($this->_map[$x][$limitY])) {
                if ($limitY < $this->_map[$x]['min'] || $this->_map[$x]['max'] < $limitY) {
                    unset($this->_map[$x]);
                    continue;
                }

                //If not, keep only $x $limitY
                $this->_map[$x] = array('min' => $limitY, 'max' => $limitY);
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

    //_d($map->getMap());

    list($X0, $Y0) = $map->getNewCoordinates();

    echo $X0 . " " . $Y0 . "\n";
}