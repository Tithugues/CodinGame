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
    const GRAVITY = 3.711;

    const WRONG_WAY = false;

    /**
     * @param Map $map
     */
    public function run($map)
    {
        $aimXs = $map->getLandXs();
        $aimX = ($aimXs[0] + $aimXs[1])/2;
        $aimY = $map->getLandY();

        // game loop
        while (TRUE)
        {
            fscanf(STDIN, "%d %d %d %d %d %d %d",
                $X,
                $Y,
                $HS, // the horizontal speed (in m/s), can be negative.
                $VS, // the vertical speed (in m/s), can be negative.
                $F, // the quantity of remaining fuel in liters.
                $R, // the rotation angle in degrees (-90 to 90).
                $P // the thrust power (0 to 4).
            );

            $speed = $this->getSpeed($VS, $HS);

            $aboveLandPlace = $aimXs[0]+50 < $X && $X < $aimXs[1]-50 && $Y > $aimY;

            //If just above the land place with good speed, straight.
            if ($aboveLandPlace && $VS > -40 && abs($HS) < 20) {
                _d('ready to land');
                $nextP = $P - 1;
                if ($VS < -30) {
                    $nextP = $P + 1;
                }
                $nextP = $this->filterPower($nextP);
                $this->tellConf(0, $nextP);
                continue;
            }

            //If above, so but too fast, we should slow down.
            if ($aboveLandPlace) {
                $nextR = $this->compensate($VS, $HS);
                $this->tellConf($nextR, 4);
                continue;
            }

            /*$estimatedLand = $this->getLandEstimation($map, $X, $Y, $VS, $HS, $R);
            _d($estimatedLand);*/

            $angle = $this->getNeededAngle($VS, $HS, $aimX - $X, $aimY - $Y);

            if (is_nan($angle)) {
                $nextR = 30;
                if ($X < $aimX) {
                    $nextR *= -1;
                }

                $nextP = 4;
                $this->tellConf($nextR, $nextP);
                continue;
            }

            $nextR = round($angle);
            $nextP = 4;

            if ($VS > -18) {
                $nextP = $P - 1;
            } elseif ($speed > 55 || abs($HS) > 20) {
                $nextP = 4;
                $nextR = 0;
                if ($VS != 0) {
                    $nextR = round(rad2deg(atan(abs($HS / $VS))) * 0.7);
                    _d('land');
                    _d($aimX);
                    _d($X);
                    if ($aimX < $X) {
                        _d('reverse');
                        $nextR *= -1;
                    }
                }
            }

            if ($nextP < 0) {
                $nextP = 0;
            }

            $this->tellConf($nextR, $nextP);
        }
    }

    protected function tellConf($angle, $power)
    {
        $power = $this->filterPower($power);
        echo("$angle $power\n");
    }

    protected function filterPower($power)
    {
        return min(4, max(0, $power));
    }

    protected function getSpeed($verticalSpeed, $horizontalSpeed)
    {
        return sqrt(pow($verticalSpeed, 2) + pow($horizontalSpeed, 2));
    }

    /**
     * @param Map $map
     * @param int $verticalSpeed
     * @param int $horizontalSpeed
     * @param int $angle
     *
     * @return int
     */
    protected function getLandEstimation($map, $x, $y, $verticalSpeed, $horizontalSpeed, $angle)
    {
        $xs = $map->getLandXs();
        if (($x < $xs[0] && $map->getDirection($horizontalSpeed) === Map::LEFT) || ($xs[0] < $x && $map->getDirection($horizontalSpeed) === Map::RIGHT)) {
            return static::WRONG_WAY;
        }

        $speed = sqrt(pow($verticalSpeed, 2) + pow($horizontalSpeed, 2));
        $angleRad = deg2rad(90 - abs($angle));

        $d = $speed * cos($angleRad) / static::GRAVITY * ($speed * sin($angleRad) + sqrt(pow($speed * sin($angleRad), 2) + 2 * static::GRAVITY));

        return $d;
    }

    protected function getNeededAngle($verticalSpeed, $horizontalSpeed, $aimX, $aimY)
    {
        $speed = sqrt(pow($verticalSpeed, 2) + pow($horizontalSpeed, 2));

        $angle = atan(
            pow($speed, 2) + sqrt(pow($speed, 4) - static::GRAVITY * (static::GRAVITY * pow($aimX, 2) + 2 * $aimY * pow($speed, 2)))
            / (static::GRAVITY * $aimX)
        );
        _d($speed);
        _d($angle);

        return $angle;
    }

    private function compensate($VS, $HS)
    {
        _d('compensate');
        return round(rad2deg(atan(abs($HS / $VS))) * 0.7);
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