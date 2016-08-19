<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

class Point
{
    protected $x;
    protected $y;

    public function __construct($x, $y)
    {
        $this->x = $x;
        $this->y = $y;
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

    public function toArray($named = false)
    {
        if ($named) {
            $return = array('x' => $this->getX(), 'y' => $this->getY());
        } else {
            $return = array($this->getX(), $this->getY());
        }

        return $return;
    }

    /**
     * Distance between 2 points
     *
     * @param Point|array $point
     *
     * @return float
     */
    public function getDistance($point)
    {
        if (is_array($point)) {
            return $this->getDistanceFromArray($point);
        }

        return $this->getDistanceFromPoint($point);
    }

    protected function getDistanceFromArray(array $point)
    {
        $point = new Point($point['x'], $point['y']);

        return $this->getDistanceFromPoint($point);
    }

    protected function getDistanceFromPoint(Point $point)
    {
        return sqrt(pow($this->getX() - $point->getX(), 2) + pow($this->getY() - $point->getY(), 2));
    }
}

class Fx
{
    /** @var float */
    protected $_a = null;
    /** @var float */
    protected $_b = null;

    /**
     * Construct a f(x)
     *
     * @param $a
     * @param $b
     */
    public function __construct($a, $b)
    {
        $this->_a = (float)$a;
        $this->_b = (float)$b;
    }

    /**
     * Get "a" constant
     *
     * @return float
     */
    public function getA()
    {
        return $this->_a;
    }

    /**
     * Get "b" constant
     *
     * @return float
     */
    public function getB()
    {
        return $this->_b;
    }

    /**
     * Calculate Y from X
     *
     * @param float $x
     *
     * @return float
     */
    public function getY($x)
    {
        return $this->getA() * $x + $this->getB();
    }

    /**
     * Calculate X from Y
     *
     * @param float $y
     *
     * @return float
     */
    public function getXFromY($y)
    {
        return ($y - $this->getB()) / $this->getA();
    }

    /**
     * Get X of crossing point
     *
     * @param Fx $f
     *
     * @return bool|float X of crossing point or false if parallels
     */
    public function getCrossingX(Fx $f)
    {
        if ($this->getA() == $f->getA()) {
            return false;
        }

        return ($this->getB() - $f->getB()) / ($f->getA() - $this->getA());
    }

    public static function generateFxFromPoints(Point $pointA, Point $pointB)
    {
        $moveX = $pointB->getX() - $pointA->getX();

        //If vertical move, split horizontally. No f(x) possible.
        if (0 === $moveX) {
            return false;
        }

        $moveY = $pointB->getY() - $pointA->getY();

        //look for f(x) = ax + b
        $a = $moveY / $moveX;

        return self::generateFxFromA($a, $pointA);
    }

    public static function generateFxFromSpeeds($vx, $vy, Point $point)
    {
        if ($vx === 0) {
            return false;
        }
        $a = $vy / $vx;

        return self::generateFxFromA($a, $point);
    }

    public static function generateFxFromA($a, Point $point)
    {
        $b = $point->getY() - $a * $point->getX();

        return new Fx($a, $b);
    }
}

/**
 * Interface DriverStrategy
 * Driver contract
 */
interface DriverStrategy
{
    /**
     * @param $x
     * @param $y
     *
     * @return $this
     */
    public function setMyPosition($x, $y);

    /**
     * @param $x
     * @param $y
     *
     * @return $this
     */
    public function setOpponentPosition($x, $y);

    /**
     * @param $vx
     * @param $vy
     *
     * @return $this
     */
    public function setSpeed($vx, $vy);

    /**
     * @param $angle
     *
     * @return $this
     */
    public function setAngle($angle);

    /**
     * @param $id
     *
     * @return $this
     */
    public function setNextCheckPoint($id);

    /**
     * @return array
     */
    public function drive();
}

abstract class AbstractDriverStrategy implements DriverStrategy
{
    protected $checkpoints = [];
    protected $numberOfTurns;
    /**
     * @var Point
     */
    protected $position;
    protected $vx;
    protected $vy;
    protected $angle;
    protected $nextCheckPointId;
    /**
     * @var Point[]
     */
    protected $opponentsPositions;

    public function __construct($checkpoints, $numberOfTurns)
    {
        $this->checkpoints = $checkpoints;
        $this->numberOfTurns = $numberOfTurns;
    }

    public function status()
    {
        return array(
            'checkpoints'        => $this->checkpoints,
            'numberOfTurns'      => $this->numberOfTurns,
            'position'           => $this->position,
            'vx'                 => $this->vx,
            'vy'                 => $this->vy,
            'angle'              => $this->angle,
            'nextCheckPointId'   => $this->nextCheckPointId,
            'opponentsPositions' => $this->opponentsPositions,
        );
    }

    public function setAngle($angle)
    {
        $this->angle = $angle;

        return $this;
    }

    public function setNextCheckPoint($id)
    {
        $this->nextCheckPointId = $id;

        return $this;
    }

    public function setSpeed($vx, $vy)
    {
        $this->vx = $vx;
        $this->vy = $vy;

        return $this;
    }

    public function setMyPosition($x, $y)
    {
        $this->position = new Point($x, $y);

        return $this;
    }

    public function setOpponentPosition($x, $y)
    {
        $this->opponentsPositions[] = new Point($x, $y);

        return $this;
    }

    protected function getDistance($pointA, $pointB)
    {
        return sqrt(pow($pointA['x'] - $pointB['x'], 2) + pow($pointA['y'] - $pointB['y'], 2));
    }
}

abstract class AbstractAdvancedDriverStrategy extends AbstractDriverStrategy implements DriverStrategy
{
    protected function getSpeed()
    {
        return sqrt(pow($this->vx, 2) + pow($this->vy, 2));
    }

    protected function getAngle($vx, $vy)
    {
        _($vx);
        _($vy);
        if (0 === $vx) {
            if ($vy > 0) {
                $angle = 90;
            } elseif ($vy < 0) {
                $angle = 270;
            } else {
                $angle = false;
            }
        } else {
            $angle = rad2deg(atan($vy / $vx));
            _('Temp angle: ' . $angle);
            if ($vx < 0) {
                $angle = 180 + $angle;
            } elseif ($vy < 0) {
                //Here, $angle is negative.
                $angle = 360 + $angle;
                //Now $angle is between 270 and 360.
            }
        }
        _($angle);

        return $angle;
    }

    protected function getMoveAngle()
    {
        return $this->getAngle($this->vx, $this->vy);
    }

    protected function getObjectiveAngle()
    {
        $destination = $this->checkpoints[$this->nextCheckPointId];

        _($this->position->toArray(true));
        _($destination);
        return $this->getAngle(
            $destination['x'] - $this->position->getX(),
            $destination['y'] - $this->position->getY()
        );
    }
}

class SlowDownDriver extends AbstractDriverStrategy implements DriverStrategy
{
    public function drive()
    {
        $checkpoint = $this->checkpoints[$this->nextCheckPointId];
        $distance = $this->position->getDistance($checkpoint);
        $power = (int)($distance / 45);
        $power = max(50, $power);
        $power = min(115, $power);

        return array($checkpoint['x'], $checkpoint['y'], $power);
    }
}

class ResearchDriver extends AbstractAdvancedDriverStrategy implements DriverStrategy
{
    protected $nbTurnsPassed = 0;

    protected function diffAngles($origine, $destination) {
        if (!is_numeric($origine) || !is_numeric($destination)) {
            return false;
        }

        $diff = $destination - $origine;
        if ($diff < -180) {
            $diff += 360;
        } elseif ($diff > 180) {
            $diff -= 360;
        }
        return $diff;
    }

    protected function getOptimalNbTurnsToCheckpoint($power = 0)
    {
        $destination = $this->checkpoints[$this->nextCheckPointId];
        $distanceToCheckpoint = $this->getDistance($this->position->toArray(true), $destination);
        $speed = $this->getSpeed();

        $nbTurns = 0;
        while ($distanceToCheckpoint > 0) {
            ++$nbTurns;
            $speed += $power;
            $distanceToCheckpoint -= $speed;
            $speed *= 0.85;
        }

        return $nbTurns;
    }

    public function drive()
    {
        ++$this->nbTurnsPassed;

        _('Current angle:' . $this->angle, true);
        $moveAngle = $this->getMoveAngle();
        _('Move angle:' . $moveAngle, true);
        $objectiveAngle = $this->getObjectiveAngle();
        _('Objective angle:' . $objectiveAngle, true);

        $destination = $this->checkpoints[$this->nextCheckPointId];

        $minimalSpeed = 65;
        if ($this->angle == -1 || $moveAngle === false || abs($this->diffAngles($objectiveAngle, $this->angle)) <= 25) {
            //If moving in right way or opposite way, accelerate.
            if ($moveAngle === false
                || abs($this->diffAngles($objectiveAngle, $moveAngle)) <= 25 //Right way
                || abs($this->diffAngles($objectiveAngle + 180, $moveAngle)) <= 25 //Opposite way
            ) {
                //Power ON

                $distanceToCheckpoint = $this->getDistance($this->position->toArray(true), $destination);
                $currentSpeed = $this->getSpeed();

                $power = 125;
                if ($currentSpeed >= $minimalSpeed) {
                    $speed = $currentSpeed;
                    $distanceBeforeSlowDown = 0;
                    while ($speed > $minimalSpeed) {
                        $distanceBeforeSlowDown += $speed;
                        $speed *= 0.85;
                    }

                    _('Distance to slow down: ' . $distanceBeforeSlowDown, true);
                    _('Distance to checkpoint: ' . $distanceToCheckpoint, true);
                    if ($distanceToCheckpoint < $distanceBeforeSlowDown) {
                        $power = $minimalSpeed;
                    }
                }

            } else {
                //Power medium until slow down
                $optimalNbTurnsToCheckpoint = $this->getOptimalNbTurnsToCheckpoint($minimalSpeed);
                if ($optimalNbTurnsToCheckpoint > 10) {
                    $power = 125;
                } elseif ($optimalNbTurnsToCheckpoint > 5) {
                    $power = 100;
                } else {
                    $power = 75;
                }
            }
        } else {
            //Power off until turned
            $power = 25;
        }

        array_push($destination, $power);

        return $destination;
    }
}

class AdvancedResearchDriver extends ResearchDriver implements DriverStrategy
{
    public function drive()
    {
        ++$this->nbTurnsPassed;

        _('Current angle:' . $this->angle, true);
        $moveAngle = $this->getMoveAngle();
        _('Move angle:' . $moveAngle, true);
        $objectiveAngle = $this->getObjectiveAngle();
        _('Objective angle:' . $objectiveAngle, true);

        $destination = $this->checkpoints[$this->nextCheckPointId];

        $minimalSpeed = 65;
        $diffBetweenObjectiveAndMove = $this->diffAngles($objectiveAngle, $moveAngle);
        _($diffBetweenObjectiveAndMove, true);
        $diffBetweenObjectiveAndAngle = $this->diffAngles($objectiveAngle, $this->angle);
        _($diffBetweenObjectiveAndAngle, true);
        $diffBetweenMoveAndDest = $this->diffAngles($moveAngle, $objectiveAngle);
        _($diffBetweenMoveAndDest, true);
        if ($this->angle == -1 || $moveAngle === false || abs($diffBetweenObjectiveAndAngle) <= 20) {
            //If moving in right way or opposite way, accelerate.
            if ($moveAngle === false
                || abs($this->diffAngles($objectiveAngle, $moveAngle)) <= 20 //Right way
                || abs($this->diffAngles($objectiveAngle + 180, $moveAngle)) <= 20 //Opposite way
            ) {
                //Power ON

                $power = 120;
                if ($this->getSpeed() >= $minimalSpeed) {
                    _('Fast enough', true);
                    $nbTurnsBeforeCheckpoint = $this->getNbTurnsBeforeCheckpoint($minimalSpeed);
                    if ($nbTurnsBeforeCheckpoint < 5) {
                        _('Close enough', true);
                        $nextCheckPointId = ($this->nextCheckPointId+1)%count($this->checkpoints);
                        $destination = $this->checkpoints[$nextCheckPointId];
                        $power = $minimalSpeed;
                    } else {
                        $distanceBeforeSlowDown = $this->getDistanceBeforeSlowDown($minimalSpeed);
                        $distanceToCheckpoint = $this->getDistance($this->position->toArray(true), $destination);

                        _('Distance to slow down: ' . $distanceBeforeSlowDown, true);
                        _('Distance to checkpoint: ' . $distanceToCheckpoint, true);
                        if ($distanceToCheckpoint < $distanceBeforeSlowDown) {
                            $power = $minimalSpeed;
                        }
                    }
                }

            } else {
                //Power medium until slow down
                $optimalNbTurnsToCheckpoint = $this->getOptimalNbTurnsToCheckpoint($minimalSpeed);
                if ($optimalNbTurnsToCheckpoint > 10) {
                    $power = 125;
                } elseif ($optimalNbTurnsToCheckpoint > 5) {
                    $power = 100;
                } else {
                    $power = 75;
                }
            }
            /*
        } elseif (abs($diffBetweenMoveAndDest) < 40 || abs($diffBetweenMoveAndDest) + 180 < 40) {
            _('Compensate', true);
            $destinationAngle = ($objectiveAngle + $diffBetweenMoveAndDest)%360;
            _('Destination angle: ' . $destinationAngle, true);

            if (90 < $destinationAngle && $destinationAngle < 270) {
                $xFactor = -1;
            } elseif (90 === $destinationAngle || 270 === $destinationAngle) {
                $xFactor = 0;
            } else {
                $xFactor = 1;
            }

            $destination['x'] = $this->position->getX() + $xFactor * 1000;
            $destination['y'] = $this->position->getY() - round(tan(deg2rad($destinationAngle)) * 1000);
            _($destination, true);

            $power = $minimalSpeed;
            if (abs($this->diffAngles($destinationAngle, $this->angle)) < 15) {
                $power = 200;
            }
            _($power, true);
            */

        } else {
            //Power off until turned
            $power = 25;
        }

        array_push($destination, $power);

        return $destination;
    }

    /**
     * @param $minimalSpeed
     *
     * @return int
     */
    protected function getDistanceBeforeSlowDown($minimalSpeed)
    {
        $speed = $this->getSpeed();
        $distanceBeforeSlowDown = 0;
        while ($speed > $minimalSpeed) {
            $distanceBeforeSlowDown += $speed;
            $speed *= 0.85;
        }

        return $distanceBeforeSlowDown;
    }

    /**
     * @param $minimalSpeed
     *
     * @return int
     */
    protected function getNbTurnsBeforeCheckpoint($minimalSpeed)
    {
        $position = $this->position->toArray(true);
        $destination = $this->checkpoints[$this->nextCheckPointId];

        $distanceToCheckpoint = $this->getDistance($position, $destination);
        unset($position, $destination);

        $speed = $this->getSpeed();
        $nbTurnsBeforeCheckpoint = 0;
        while ($distanceToCheckpoint > 0) {
            ++$nbTurnsBeforeCheckpoint;
            $speed += $minimalSpeed;
            $distanceToCheckpoint -= $speed;
            $speed *= 0.85;
        }

        return $nbTurnsBeforeCheckpoint;
    }
}

class AdvancedResearchDriver2 extends ResearchDriver implements DriverStrategy
{
    public function drive()
    {
        ++$this->nbTurnsPassed;

        _('Current angle:' . $this->angle, true);
        $moveAngle = $this->getMoveAngle();
        _('Move angle:' . $moveAngle, true);
        $objectiveAngle = $this->getObjectiveAngle();
        _('Objective angle:' . $objectiveAngle, true);

        $destination = $this->checkpoints[$this->nextCheckPointId];

        $minimalSpeed = 75;
        $diffBetweenObjectiveAndMove = $this->diffAngles($objectiveAngle, $moveAngle);
        _($diffBetweenObjectiveAndMove, true);
        $diffBetweenObjectiveAndAngle = $this->diffAngles($objectiveAngle, $this->angle);
        _($diffBetweenObjectiveAndAngle, true);
        $diffBetweenMoveAndDest = $this->diffAngles($moveAngle, $objectiveAngle);
        _($diffBetweenMoveAndDest, true);

        $nbTurnsBeforeCheckpoint = $this->getNbTurnsBeforeCheckpoint($minimalSpeed);

        if (false === $moveAngle) {
            _('Start');
            $power = 125;
            //If almost right direction...
        } elseif (abs($diffBetweenObjectiveAndMove) <= 22) {
            _('Move in right direction');

            $power = 125;
            if ($this->getSpeed() >= $minimalSpeed) {
                _('Fast enough', true);

                if ($nbTurnsBeforeCheckpoint < 4) {
                    _('Close enough', true);
                    $nextCheckPointId = ($this->nextCheckPointId + 1) % count($this->checkpoints);
                    $destination = $this->checkpoints[$nextCheckPointId];
                    $power = $minimalSpeed;
                } else {
                    $distanceBeforeSlowDown = $this->getDistanceBeforeSlowDown($minimalSpeed);
                    $distanceToCheckpoint = $this->getDistance($this->position->toArray(true), $destination);

                    _('Distance to slow down: ' . $distanceBeforeSlowDown, true);
                    _('Distance to checkpoint: ' . $distanceToCheckpoint, true);
                    if ($distanceToCheckpoint < $distanceBeforeSlowDown) {
                        $power = $minimalSpeed;
                    }
                }
            } else {
                if (abs($diffBetweenObjectiveAndAngle) <= 20 || abs(($diffBetweenObjectiveAndAngle+180)%180) <= 20) {
                    $power = 125;
                } else {
                    $power = $minimalSpeed;
                }
            }

        } elseif (abs($diffBetweenObjectiveAndMove) <= 90 && ($nbTurnsBeforeCheckpoint > 10 || $this->getSpeed() <= 50)) {
            _('Far enough');
            $power = $nbTurnsBeforeCheckpoint * 10;
            $power = min($power, 150);
            $power = round($power);
        } else {
            _('Other');
            //Power off until face to checkpoint
            $power = $minimalSpeed;
        }

        array_push($destination, $power);

        return $destination;
    }

    /**
     * @param $minimalSpeed
     *
     * @return int
     */
    protected function getDistanceBeforeSlowDown($minimalSpeed)
    {
        $speed = $this->getSpeed();
        $distanceBeforeSlowDown = 0;
        while ($speed > $minimalSpeed) {
            $distanceBeforeSlowDown += $speed;
            $speed *= 0.85;
        }

        return $distanceBeforeSlowDown;
    }

    /**
     * @param $minimalSpeed
     *
     * @return int
     */
    protected function getNbTurnsBeforeCheckpoint($minimalSpeed)
    {
        $position = $this->position->toArray(true);
        $destination = $this->checkpoints[$this->nextCheckPointId];

        $distanceToCheckpoint = $this->getDistance($position, $destination);
        unset($position, $destination);

        $speed = $this->getSpeed();
        $nbTurnsBeforeCheckpoint = 0;
        while ($distanceToCheckpoint > 0) {
            ++$nbTurnsBeforeCheckpoint;
            $speed += $minimalSpeed;
            $distanceToCheckpoint -= $speed;
            $speed *= 0.85;
        }

        return $nbTurnsBeforeCheckpoint;
    }
}

fscanf(STDIN, "%d", $laps);
fscanf(STDIN, "%d", $checkpointCount);

$checkpoints = [];
for ($i = 0; $i < $checkpointCount; $i++) {
    fscanf(STDIN, "%d %d", $checkpoints[$i]['x'], $checkpoints[$i]['y']);
}

/** @var DriverStrategy[] $drivers */
$drivers = [new AdvancedResearchDriver2($checkpoints, $laps), new SlowDownDriver($checkpoints, $laps)];

// game loop
while (true) {
    for ($i = 0; $i < 2; $i++) {
        fscanf(
            STDIN,
            "%d %d %d %d %d %d",
            $x,
            $y,
            $vx,
            $vy,
            $angle,
            $nextCheckPoint
        );
        $drivers[$i]->setMyPosition($x, $y)
            ->setSpeed($vx, $vy)
            ->setAngle($angle)
            ->setNextCheckPoint($nextCheckPoint);
    }
    for ($i = 0; $i < 2; $i++) {
        fscanf(
            STDIN,
            "%d %d %d %d %d %d",
            $x,
            $y,
            $vx,
            $vy,
            $angle,
            $nextCheckPointId
        );
        foreach ($drivers as $driver) {
            $driver->setOpponentPosition($x, $y);
        }
    }

    foreach ($drivers as $driver) {
        echo implode(' ', $driver->drive()) . "\n";
    }
}
