<?php

define('DEBUG', true);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Map
{
    const STABLE = 0;
    const LEFT = 1;
    const RIGHT = 2;

    protected $edges = [];
    protected $landXs = [];
    protected $landY;
    protected $edgesF = [];

    public function __construct($edges)
    {
        $this->setEdges($edges);
    }

    public function setEdges($edges)
    {
        $this->edges = $edges;
        unset($edges);

        $first = true;
        foreach ($this->edges as $X => $Y) {
            if (isset($first)) {
                $last = ['x' => $X, 'y' => $Y];
                unset($first);
                continue;
            }

            $this->edgesF[] = Fx::getFx($last['x'], $last['y'], $X, $Y);

            if ($last['y'] === $Y && abs($X-$last['x']) >= 1000) {
                $this->setLand([$last['x'], $X], $Y);
            }

            $last = ['x' => $X, 'y' => $Y];
        }

        return $this;
    }

    public function getEdges()
    {
        return $this->edges;
    }

    /**
     * @return array
     */
    public function getEdgesF()
    {
        return $this->edgesF;
    }

    protected function setLand($landXs, $landY)
    {
        $this->landXs = $landXs;
        $this->landY = $landY;

        return $this;
    }

    public function getLandXs()
    {
        return $this->landXs;
    }

    public function getLandY()
    {
        return $this->landY;
    }

    /**
     * Get direction
     *
     * @param int $horizontalSpeed Horizontal speed
     *
     * @return int
     */
    public static function getDirection($horizontalSpeed) {
        $horizontalSpeed = (int)$horizontalSpeed;
        if ($horizontalSpeed === 0) {
            return static::STABLE;
        }

        if ($horizontalSpeed < 0) {
            return static::LEFT;
        }

        return static::RIGHT;
    }
}

class State
{
    const ALIGNED = 0;
    const LEFT = 1;
    const RIGHT = 2;
    const UPPER = 3;
    const LOWER = 4;

    protected $x;
    protected $y;
    protected $horizontalSpeed;
    protected $verticalSpeed;
    protected $fuel;
    protected $angle;
    protected $power;

    /**
     * @param $x
     * @param $y
     * @param $horizontalSpeed
     * @param $verticalSpeed
     * @param $fuel
     * @param $angle
     * @param $power
     *
     * @return static
     */
    public static function factory($x, $y, $horizontalSpeed, $verticalSpeed, $fuel, $angle, $power)
    {
        return new static($x, $y, $horizontalSpeed, $verticalSpeed, $fuel, $angle, $power);
    }

    protected function __construct($x, $y, $horizontalSpeed, $verticalSpeed, $fuel, $angle, $power)
    {
        $this->x = $x;
        $this->y = $y;
        $this->horizontalSpeed = $horizontalSpeed;
        $this->verticalSpeed = $verticalSpeed;
        $this->fuel = $fuel;
        $this->angle = $angle;
        $this->power = $power;
    }

    /**
     * @return mixed
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return mixed
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @return mixed
     */
    public function getHorizontalSpeed()
    {
        return $this->horizontalSpeed;
    }

    /**
     * @return mixed
     */
    public function getVerticalSpeed()
    {
        return $this->verticalSpeed;
    }

    /**
     * @return mixed
     */
    public function getFuel()
    {
        return $this->fuel;
    }

    /**
     * @return mixed
     */
    public function getAngle()
    {
        return $this->angle;
    }

    /**
     * @return mixed
     */
    public function getPower()
    {
        return $this->power;
    }

    /**
     * @param Map $map
     *
     * @return int
     */
    public function getHorizontalPosition($map)
    {
        $landXs = $map->getLandXs();
        if (($x = $this->getX()) < $landXs[0]) {
            return static::LEFT;
        }

        if ($landXs[1] < $x) {
            return static::RIGHT;
        }

        return static::ALIGNED;
    }

    /**
     * @param Map $map
     *
     * @return int
     */
    public function getVerticalPosition($map)
    {
        $landY = $map->getLandY();
        if (($y = $this->getY()) < $landY) {
            return static::LOWER;
        }

        if ($landY < $y) {
            return static::UPPER;
        }

        return static::ALIGNED;
    }
}

class DummyTrajectoryCalculation
{
    const HEIGHT_ADJUSTMENT = 500;
    const GLOBAL_ANGLE = 25;
    const ANGLE_ADJUSTMENT = 45;
    const GLOBAL_POWER = 3;
    const VERTICAL_SPEED_LIMIT = -35;
    const FACTOR_ADJUSTMENT = 0.7;

    protected $aim = [];

    /**
     * @param Map $map
     */
    public function run($map)
    {
        // game loop
        while (TRUE)
        {
            $correct = false;
            fscanf(STDIN, "%d %d %d %d %d %d %d",
                $X,
                $Y,
                $HS, // the horizontal speed (in m/s), can be negative.
                $VS, // the vertical speed (in m/s), can be negative.
                $F, // the quantity of remaining fuel in liters.
                $R, // the rotation angle in degrees (-90 to 90).
                $P // the thrust power (0 to 4).
            );
            $state = State::factory($X, $Y, $HS, $VS, $F, $R, $P);

            $aim = [];
            $aim = $this->getAim($map, $state);
            _d($aim);

            if ($X < $aim['x']) {
                _d('if1');
                $nextR = -static::GLOBAL_ANGLE;
                $nextP = static::GLOBAL_POWER;
            } elseif ($aim['x'] < $X) {
                _d('elseif1');
                $nextR = static::GLOBAL_ANGLE;
                $nextP = static::GLOBAL_POWER;
            } else {
                if ($HS < -20/* || $X < ($flatPlace[0] + $flatPlace[1]) / 2*/) {
                    _d('if2');
                    $nextR = -static::ANGLE_ADJUSTMENT;
                    $nextP = static::GLOBAL_POWER;
                    $correct = false;
                } elseif ($HS > 20/* || ($flatPlace[0] + $flatPlace[1]) / 2 < $X*/) {
                    _d('elseif2');
                    $nextR = static::ANGLE_ADJUSTMENT;
                    $nextP = static::GLOBAL_POWER;
                    $correct = false;
                } else {
                    _d('else');
                    $nextR = 0;
                    $nextP = static::GLOBAL_POWER;
                }
            }

            if ($aim['y']+500 > $Y && $aim['x'] <= $X && $X <= $aim['x']) {
                _d('if3');
                $nextR = 0;
            }

            if ($HS < -45) {
                _d('if4');
                $nextR = -static::GLOBAL_ANGLE;
                if ($VS != 0) {
                    $nextR = -round(rad2deg(atan(abs($HS / $VS)))*static::FACTOR_ADJUSTMENT);
                }
                $correct = true;
            } elseif ($HS > 45) {
                _d('elseif4');
                $nextR = static::GLOBAL_ANGLE;
                if ($VS != 0) {
                    $nextR = round(rad2deg(atan(abs($HS / $VS)))*static::FACTOR_ADJUSTMENT);
                }
                $correct = true;
            }

            if (-10 < $HS && $HS < 10 && $aim['x'] <= $X && $X <= $aim['x']) {
                _d('if5');
                $nextR = 0;
            }

            if ($VS < static::VERTICAL_SPEED_LIMIT) {
                _d('if6');
                $nextP = 4;
            }

            if ($correct && (abs($VS)+abs($HS) > 65 || $VS < -50)) {
                _d('if7');
                $nextP = 4;
            }

            if ($Y <= $aim['y']+static::HEIGHT_ADJUSTMENT) {
                _d('if8');
                _d($aim['y']);
                $nextP = 4;
            }

            echo("$nextR $nextP\n"); // R P. R is the desired rotation angle. P is the desired thrust power.
        }

    }

    /**
     * @param Map $map
     *
     * @return array
     */
    protected function getAim($map, $state)
    {
        //Find aim on land area
        $landPlaceXs = $map->getLandXs();
        if (($hPos = $state->getHorizontalPosition($map)) === State::LEFT) {
            $aim['x'] = round(($landPlaceXs[0]*2 + $landPlaceXs[1]) / 3, 1);
        } elseif ($hPos === State::RIGHT) {
            $aim['x'] = round(($landPlaceXs[0] + $landPlaceXs[1]*2) / 3, 1);
        } else {
            $aim['x'] = $state->getX();
        }
        $aim['y'] = $map->getLandY();

        $fx = Fx::getFx($myX = $state->getX(), $state->getY(), $aim['x'], $aim['y']);

        if (false === $fx) {
            return ['x' => $myX, 'y' => $map->getLandY()];
        }

        //Go through all edges to see if one is going through my way
        foreach ($map->getEdgesF() as $fxEdge) {
            $crossingX = $fx->getCrossingX($fxEdge);
            if (($myX < $crossingX && $crossingX < $aim['x']) || ($aim['x'] < $crossingX && $crossingX < $myX)) {
                $aim['x'] = $crossingX;
                $aim['y'] = $fx->getY($crossingX) + 100;
            }
        }

        return $aim;
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

    public static function getFx($x1, $y1, $x2, $y2) {
        $moveX = $x2 - $x1;

        //If vertical move, split horizontally. No f(x) possible.
        if (0 === $moveX) {
            return false;
        }

        $moveY = $y2 - $y1;

        //look for f(x) = ax + b
        $a = $moveY / $moveX;
        $b = $y1 - ($a * $x1);

        return static::_generateFx($a, $b);
    }

    protected static function _generateFx($a, $b) {
        return new static($a, $b);
    }
}

$edges = [];

fscanf(STDIN, "%d",
    $N // the number of points used to draw the surface of Mars.
);
$first = true;
for ($i = 0; $i < $N; $i++)
{
    fscanf(STDIN, "%d %d",
        $LAND_X, // X coordinate of a surface point. (0 to 6999)
        $LAND_Y // Y coordinate of a surface point. By linking all the points together in a sequential fashion, you form the surface of Mars.
    );
    $edges[$LAND_X] = $LAND_Y;
} unset($LAND_X, $LAND_Y);

$map = new Map($edges);

(new DummyTrajectoryCalculation())->run($map);