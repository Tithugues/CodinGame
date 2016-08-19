<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

define('DEBUG', false);

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

class Pod
{
    //region attributes
    /**
     * Position
     * @var Point
     */
    protected $position;
    /**
     * X vector
     * @var int
     */
    protected $vx;
    /**
     * Y vector
     * @var int
     */
    protected $vy;
    /**
     * Angle
     * @var int
     */
    protected $angle;
    /**
     * Next checkpoint id
     * @var int
     */
    protected $nextCheckPointId;
    //endregion

    /**
     * Pod constructor.
     *
     * @param Point $position
     * @param int   $vx
     * @param int   $vy
     * @param int   $angle
     * @param int   $nextCheckPointId
     */
    public function __construct(Point $position, $vx, $vy, $angle, $nextCheckPointId)
    {
        $this->update($position, $vx, $vy, $angle, $nextCheckPointId);
    }

    /**
     * Pod updater.
     *
     * @param Point $position
     * @param int   $vx
     * @param int   $vy
     * @param int   $angle
     * @param int   $nextCheckPointId
     *
     * @return $this
     */
    public function update(Point $position, $vx, $vy, $angle, $nextCheckPointId)
    {
        $this->position = $position;
        $this->vx = $vx;
        $this->vy = $vy;
        $this->angle = $angle;
        $this->nextCheckPointId = $nextCheckPointId;

        return $this;
    }

    //region getters
    /**
     * @return Point
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @return int
     */
    public function getVx()
    {
        return $this->vx;
    }

    /**
     * @return int
     */
    public function getVy()
    {
        return $this->vy;
    }

    /**
     * @return int
     */
    public function getAngle()
    {
        return $this->angle;
    }

    /**
     * @return int
     */
    public function getNextCheckPointId()
    {
        return $this->nextCheckPointId;
    }
    //endregion

    public function status()
    {
        return array(
            'position'           => $this->position,
            'vx'                 => $this->vx,
            'vy'                 => $this->vy,
            'angle'              => $this->angle,
            'nextCheckPointId'   => $this->nextCheckPointId,
        );
    }

    public function move(Point $target, $power) {
        
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
            $angle = atan($vy / $vx);
            if ($vx < 0) {
                $angle = 180 - $angle;
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
        _('getMoveAngle');

        return $this->getAngle($this->vx, $this->vy);
    }

    protected function getObjectiveAngle()
    {
        _('getObjectiveAngle');
        $destination = $this->checkpoints[$this->nextCheckPointId];
        _($this->position->toArray(true));
        _($destination);

        return $this->getAngle(
            $destination['x'] - $this->position->getX(),
            $destination['y'] - $this->position->getY()
        );
    }
}

class DummyDriver extends AbstractDriverStrategy implements DriverStrategy
{
    public function drive()
    {
        $nextCheckpoint = $this->checkpoints[$this->nextCheckPointId];

        return array($nextCheckpoint['x'], $nextCheckpoint['y'], 100);
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

class CheckingAngleDriver extends AbstractDriverStrategy implements DriverStrategy
{
    public function drive()
    {
        $checkpoint = $this->checkpoints[$this->nextCheckPointId];
        $objectiveFx = Fx::generateFxFromPoints($this->position, new Point($checkpoint['x'], $checkpoint['y']));
        _($objectiveFx);

        $objectiveAngle = $this->angle;
        if (0 !== $this->vx) {
            $objectiveAngle = rad2deg(atan(abs($objectiveFx->getA())));
            _('Initial objective angle: ' . $objectiveAngle);
            if ($this->vx >= 0) {
                if ($this->vy >= 0) {
                    _('vx >= 0 && vy >= 0');
                    //$objectiveAngle is OK.
                } else {
                    _('vx >= 0 && vy < 0');
                    //$objectiveAngle += 270;
                }
            } else {
                if ($this->vy >= 0) {
                    _('vx < 0 && vy >= 0');
                    $objectiveAngle += 90;
                } else {
                    _('vx < 0 && vy < 0');
                    $objectiveAngle += 180;
                }
            }
        }

        _($this->angle);
        _($objectiveAngle);

        $distance = $this->position->getDistance($checkpoint);
        if ((abs($objectiveAngle - $this->angle) % 180) > 15) {
            _('Realign');
            $power = (int)($distance / 45);
            $power = max(40, $power);
            $power = min(100, $power);
        } else {
            _('GO!');
            $power = (int)($distance / 45);
            $power = max(50, $power);
            $power = min(115, $power);
        }

        return array($checkpoint['x'], $checkpoint['y'], $power);
    }
}

class CheckingAngleAdvancedDriver extends AbstractDriverStrategy implements DriverStrategy
{
    public function drive()
    {
        $checkpoint = $this->checkpoints[$this->nextCheckPointId];
        $objectiveFx = Fx::generateFxFromPoints($this->position, new Point($checkpoint['x'], $checkpoint['y']));
        _($objectiveFx);

        $objectiveAngle = $this->angle;
        if (false !== $objectiveFx) {
            $objectiveAngle = rad2deg(atan(abs($objectiveFx->getA())));
            _('Initial objective angle: ' . $objectiveAngle);
            if ($this->vx >= 0) {
                if ($this->vy >= 0) {
                    _('vx >= 0 && vy >= 0');
                    //$objectiveAngle is OK.
                } else {
                    _('vx >= 0 && vy < 0');
                    $objectiveAngle = 360 - $objectiveAngle;
                }
            } else {
                if ($this->vy >= 0) {
                    _('vx < 0 && vy >= 0');
                    $objectiveAngle = 180 - $objectiveAngle;
                } else {
                    _('vx < 0 && vy < 0');
                    $objectiveAngle += 180;
                }
            }
        }

        _($this->angle);
        _($objectiveAngle);

        $objectivePoint = $checkpoint;

        $distance = $this->position->getDistance($checkpoint);
        if ((abs($objectiveAngle - $this->angle) % 180) > 15) {
            _('Realign');

            $myFx = Fx::generateFxFromSpeeds($this->vx, $this->vy, $this->position);

            if (false !== $myFx) {
                $myY = $myFx->getY($checkpoint['x']);
                if ($myY > $checkpoint['y']) {
                    $objectivePoint['y'] = 2 * $objectivePoint['y'] - $myY;
                }
            }
            $power = 50;
        } else {
            _('GO!');
            $power = (int)($distance / 45);
            $power = max(50, $power);
            $power = min(115, $power);
        }

        return array($checkpoint['x'], $checkpoint['y'], $power);
    }
}

class ResearchDriver extends AbstractAdvancedDriverStrategy implements DriverStrategy
{
    protected $nbTurnsPassed = 0;

    public function drive()
    {
        $minimalSpeed = 30;

        ++$this->nbTurnsPassed;

        _($this->angle);
        $moveAngle = $this->getMoveAngle();
        $objectiveAngle = $this->getObjectiveAngle();

        $destination = $this->checkpoints[$this->nextCheckPointId];
        $distanceToCheckpoint = $this->getDistance($this->position->toArray(true), $destination);
        _($distanceToCheckpoint, true);
        $currentSpeed = $this->getSpeed();
        _($currentSpeed, true);

        $speed = $currentSpeed;
        $nbTurnsToSlowDown = 0;
        while ($speed > $minimalSpeed) {
            ++$nbTurnsToSlowDown;
            $speed *= 0.85;
        }
        _('Nb turns to slow down: ' . $nbTurnsToSlowDown, true);

        $averageSpeed = ($currentSpeed + $minimalSpeed)/2;
        _('Average speed: ' . $averageSpeed, true);

        $distanceBeforeSlowDown = $nbTurnsToSlowDown * $averageSpeed;

        _('Distance to slow down: ' . $distanceBeforeSlowDown, true);
        _('Distance to checkpoint: ' . $distanceToCheckpoint, true);

        $power = 115;
        if ($distanceToCheckpoint < $distanceBeforeSlowDown) {
            $power = 50;
        }

        array_push($destination, $power);

        return $destination;
    }
}

class AggressiveAlternativeDriver extends AbstractDriverStrategy implements DriverStrategy
{
    protected $nbTurnsPassed = 0;

    public function drive()
    {
        ++$this->nbTurnsPassed;

        //Get opponents positions and reset them.
        $opponentsPositions = $this->opponentsPositions;
        $this->opponentsPositions = [];

        //Choose an opponent.
        $opponentIndex = floor($this->nbTurnsPassed / 75) % 2;
        $return = $opponentsPositions[$opponentIndex]->toArray(false);
        array_push($return, 150);

        return $return;
    }
}

fscanf(STDIN, "%d", $laps);
fscanf(STDIN, "%d", $checkpointCount);

$checkpoints = [];
for ($i = 0; $i < $checkpointCount; $i++) {
    fscanf(STDIN, "%d %d", $checkpoints[$i]['x'], $checkpoints[$i]['y']);
}

/** @var DriverStrategy[] $drivers */
$drivers = [new ResearchDriver($checkpoints, $laps), new SlowDownDriver($checkpoints, $laps)];

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
