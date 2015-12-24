<?php
/**
 * Save humans, destroy zombies!
 **/

define('DEBUG', true);

/**
 * Debugger
 * @param mixed $var   Variable to display
 * @param bool  $force Force debug
 */
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

class Map
{
    public static function getDist($x1, $y1, $x2, $y2)
    {
        return sqrt(pow($x1 - $x2, 2) + pow($y1 - $y2, 2));;
    }

    public static function tellDirection($zombieX, $zombieY)
    {
        echo("$zombieX $zombieY\n");
    }
}

class StrategyFactory
{
    protected function __construct()
    {
    }

    /**
     * @return StrategyInterface
     */
    public static function getStrategy()
    {
        return new DummyStrategy6();
    }
}

interface StrategyInterface
{
    public function findDirection($humans, $zombies, $me);
}

class DummyStrategy3 extends StrategyFactory implements StrategyInterface
{
    public function findDirection($humans, $zombies, $me)
    {
        if (1 === count($zombies)) {
            return array_shift($zombies);
        }

        $closestHuman = null;
        $closestDist = null;
        foreach ($humans as $humanId => $human) {
            $myDistFromHuman = Map::getDist($human['x'], $human['y'], $me['x'], $me['y']);
            $nbRoundMeToHuman = $myDistFromHuman / 1000;

            foreach ($zombies as $zombie) {
                $dist = Map::getDist($zombie['xNext'], $zombie['yNext'], $human['x'], $human['y']);

                //Count nb round before zombie eating human
                $nbRoundZombieToHuman = $dist / 400;
                _d('My nb round: ' . $nbRoundMeToHuman);
                _d('Zombie nb round: ' . $nbRoundZombieToHuman);
                if ($nbRoundZombieToHuman < $nbRoundMeToHuman) {
                    _d('Human ' . $humanId . ' too far.');
                    continue;
                }

                if (null === $closestHuman || $dist < $closestDist) {
                    $closestHuman = $human;
                    $closestDist = $dist;
                }
            }
        }

        return $closestHuman;
    }
}

class DummyStrategy4 extends StrategyFactory implements StrategyInterface
{
    public function findDirection($humans, $zombies, $me)
    {
        if (1 === count($zombies)) {
            return array_shift($zombies);
        }

        $closestZombie = null;
        $closestDist = null;
        foreach ($humans as $humanId => $human) {
            $myDistFromHuman = Map::getDist($human['x'], $human['y'], $me['x'], $me['y']);
            $nbRoundMeToHuman = $myDistFromHuman / 1000;

            foreach ($zombies as $zombie) {
                $dist = Map::getDist($zombie['xNext'], $zombie['yNext'], $human['x'], $human['y']);

                //Count nb round before zombie eating human
                $nbRoundZombieToHuman = $dist / 400;
                _d('My nb round: ' . $nbRoundMeToHuman);
                _d('Zombie nb round: ' . $nbRoundZombieToHuman);
                if ($nbRoundZombieToHuman < $nbRoundMeToHuman) {
                    _d('Human ' . $humanId . ' too far.');
                    continue;
                }

                if (null === $closestZombie || $dist < $closestDist) {
                    $closestZombie = $zombie;
                    $closestDist = $dist;
                }
            }
        }

        return $closestZombie;
    }
}

class DummyStrategy5 extends StrategyFactory implements StrategyInterface
{
    public function findDirection($humans, $zombies, $me)
    {
        //$humans[-1] = $me;
        $zombies = $this->calculateDistanceBetweenZombiesAndClosestHuman($zombies, $humans);

        $destination = null;
        $closestDist = null;
        foreach ($zombies as $zombieId => $zombie) {
            $human = $humans[$zombie['closestHumanId']];
            _d('Zombie id to check: ' . $zombieId);
            $crossingPoint = $this->crossingZombieRoadBeforeItEatsHuman($me, $zombie, $human);

            if (false === $crossingPoint) {
                _d('can\'t save him.');
                continue;
            }

            _d($crossingPoint);
            $distBetweenZombieAndHuman = Map::getDist($zombie['x'], $zombie['y'], $human['x'], $human['y']);
            $distCrossingPoint = Map::getDist($me['x'], $me['y'], $crossingPoint['x'], $crossingPoint['y']);
            _d('Dist between me and crossing point with zombie: ' . $distCrossingPoint);

            if (null === $destination || $distBetweenZombieAndHuman < $closestDist) {
                _d('New zombie id: ' . $zombieId);
                _d('New dist between zombie and human: ' . $distCrossingPoint);
                $destination = ['x' => $crossingPoint['x'], 'y' => $crossingPoint['y']];
                $closestDist = $distBetweenZombieAndHuman;
            }
        }

        _d($destination);
        if (null === $destination) {
            if (1 === count($zombies)) {
                $zombie = array_shift($zombies);
                return ['x' => $zombie['x'], 'y' => $zombie['y']];
            }
            return $me;
        }

        return $destination;
    }

    /**
     * @param $me
     * @param $zombie
     * @param $human
     *
     * @return array
     */
    public function crossingZombieRoadBeforeItEatsHuman($me, $zombie, $human)
    {
        $destination = false;

        $xStep = $zombie['xNext'] - $zombie['x'];
        $yStep = $zombie['yNext'] - $zombie['y'];

        $distZombieToHuman = Map::getDist($zombie['x'], $zombie['y'], $human['x'], $human['y']);

        $nbRoundZombieToThisPosition = 0;
        $last = false;
        do {
            if ($last) {
                return false;
            }

            ++$nbRoundZombieToThisPosition;
            if ($distZombieToHuman <= 400) {
                $zombie['x'] = $human['x'];
                $zombie['y'] = $human['y'];
                $last = true;
            } else {
                $zombie['x'] += $xStep;
                $zombie['y'] += $yStep;
            }

            $myDistToZombiePosition = Map::getDist($me['x'], $me['y'], $zombie['x'], $zombie['y']);
            $nbRoundMeToZombie = ($myDistToZombiePosition-2000) / 1000;

            $distZombieToHuman = Map::getDist($zombie['x'], $zombie['y'], $human['x'], $human['y']);

            //If I can reach the zombie, set position and leave.
            if ($nbRoundMeToZombie <= $nbRoundZombieToThisPosition) {
                return ['x' => $zombie['x'], 'y' => $zombie['y']];
            }
        } while (true);

        return $destination;
    }

    /**
     * Calculate distance between zombies and closest human
     * @param array $zombies
     * @param array $humans
     * @return array
     */
    private function calculateDistanceBetweenZombiesAndClosestHuman($zombies, $humans)
    {
        foreach ($zombies as $zombieId => &$zombie) {
            foreach ($humans as $humanId => $human) {
                $dist = Map::getDist($zombie['x'], $zombie['y'], $human['x'], $human['y']);

                if (!array_key_exists('closestHumanId', $zombie) || $dist < $zombie['distClosestHuman']) {
                    $zombie['closestHumanId'] = $humanId;
                    $zombie['distClosestHuman'] = $dist;
                }
            }
        }

        return $zombies;
    }
}

class DummyStrategy6 extends StrategyFactory implements StrategyInterface
{
    public function findDirection($humans, $zombies, $me)
    {
        //$humans[-1] = $me;
        $zombies = $this->calculateDistanceBetweenZombiesAndClosestHuman($zombies, $humans);

        $destination = null;
        $closestDist = null;
        foreach ($zombies as $zombieId => $zombie) {
            $human = &$humans[$zombie['closestHumanId']];
            _d('Zombie id to check: ' . $zombieId);
            _d('Human attacked: ' . $zombie['closestHumanId']);
            $crossingPoint = $this->crossingZombieRoadBeforeItEatsHuman($me, $zombie, $human);

            if (false === $crossingPoint) {
                _d('can\'t save him.');
                $human['dead'] = true;
                continue;
            }

            _d($crossingPoint);
            $distBetweenZombieAndHuman = Map::getDist($zombie['x'], $zombie['y'], $human['x'], $human['y']);
            $distCrossingPoint = Map::getDist($me['x'], $me['y'], $crossingPoint['x'], $crossingPoint['y']);
            _d('Dist between me and crossing point with zombie: ' . $distCrossingPoint);

            if (null === $destination || $distBetweenZombieAndHuman < $closestDist) {
                _d('New zombie id: ' . $zombieId);
                _d('New dist between zombie and human: ' . $distCrossingPoint);
                $destination = ['x' => $crossingPoint['x'], 'y' => $crossingPoint['y']];
                $closestDist = $distBetweenZombieAndHuman;
            }
        }

        _d($destination);
        //If there is a destination, send it.
        if (null !== $destination) {
            return $destination;
        }

        //If only one more zombie, try to reach it.
        if (1 === count($zombies)) {
            $zombie = array_shift($zombies);
            return ['x' => $zombie['x'], 'y' => $zombie['y']];
        }

        //Else, reach one.
        return array_shift($zombies);
    }

    /**
     * @param $me
     * @param $zombie
     * @param $human
     *
     * @return array
     */
    public function crossingZombieRoadBeforeItEatsHuman($me, $zombie, $human)
    {
        if (array_key_exists('dead', $human)) {
            return false;
        }

        $destination = false;

        $xStep = $zombie['xNext'] - $zombie['x'];
        $yStep = $zombie['yNext'] - $zombie['y'];

        $distZombieToHuman = Map::getDist($zombie['x'], $zombie['y'], $human['x'], $human['y']);

        $nbRoundZombieToThisPosition = 0;
        $last = false;
        do {
            if ($last) {
                return false;
            }

            ++$nbRoundZombieToThisPosition;
            if ($distZombieToHuman <= 400) {
                $zombie['x'] = $human['x'];
                $zombie['y'] = $human['y'];
                $last = true;
            } else {
                $zombie['x'] += $xStep;
                $zombie['y'] += $yStep;
            }

            $myDistToZombiePosition = Map::getDist($me['x'], $me['y'], $zombie['x'], $zombie['y']);
            $nbRoundMeToZombie = ($myDistToZombiePosition-2000) / 1000;

            $distZombieToHuman = Map::getDist($zombie['x'], $zombie['y'], $human['x'], $human['y']);

            //If I can reach the zombie, set position and leave.
            if ($nbRoundMeToZombie <= $nbRoundZombieToThisPosition) {
                return ['x' => $zombie['x'], 'y' => $zombie['y']];
            }
        } while (true);

        return $destination;
    }

    /**
     * Calculate distance between zombies and closest human
     * @param array $zombies
     * @param array $humans
     * @return array
     */
    private function calculateDistanceBetweenZombiesAndClosestHuman($zombies, $humans)
    {
        foreach ($zombies as $zombieId => &$zombie) {
            foreach ($humans as $humanId => $human) {
                $dist = Map::getDist($zombie['x'], $zombie['y'], $human['x'], $human['y']);

                if (!array_key_exists('closestHumanId', $zombie) || $dist < $zombie['distClosestHuman']) {
                    $zombie['closestHumanId'] = $humanId;
                    $zombie['distClosestHuman'] = $dist;
                }
            }
        }

        return $zombies;
    }
}

// game loop
/**
 * @param $zombieX
 * @param $zombieY
 */

$strategy = StrategyFactory::getStrategy();

while (TRUE)
{
    fscanf(STDIN, "%d %d",
        $x, //myX
        $y  //myY
    );
    $me = ['x' => $x, 'y' => $y];
    fscanf(STDIN, "%d", $humanCount);
    $humans = [];
    for ($i = 0; $i < $humanCount; $i++)
    {
        fscanf(STDIN, "%d %d %d",
            $humanId,
            $humanX,
            $humanY
        );
        $humans[$humanId] = ['x' => $humanX, 'y' => $humanY];
    }
    fscanf(STDIN, "%d", $zombieCount);
    $zombies = [];
    for ($i = 0; $i < $zombieCount; $i++)
    {
        fscanf(STDIN, "%d %d %d %d %d",
            $zombieId,
            $zombieX,
            $zombieY,
            $zombieXNext,
            $zombieYNext
        );
        $zombies[$zombieId] = ['x' => $zombieX, 'y' => $zombieY, 'xNext' => $zombieXNext, 'yNext' => $zombieYNext];
    }

    //Find closest zombie to a human.
    $human = $strategy->findDirection($humans, $zombies, $me);

    Map::tellDirection($human['x'], $human['y']);
}
