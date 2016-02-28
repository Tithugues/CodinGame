<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/
 
 function _($trace) {
     error_log(var_export($trace, true));
 }

class Point {
    protected $x;
    protected $y;

    public function __construct($x, $y) {
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

    public function toArray($named = false) {
        if ($named)
            $return = array('x' => $this->getX(), 'y' => $this->getY());
        else
            $return = array($this->getX(), $this->getY());
        return $return;
    }
}

interface DriverStrategy {
    public function __construct($checkpoints, $numberOfTurns);

    /**
     * @param $x
     * @param $y
     * @return $this
     */
    public function setMyPosition($x, $y);

    /**
     * @param $x
     * @param $y
     * @return $this
     */
    public function setOpponentPosition($x, $y);

    /**
     * @param $vx
     * @param $vy
     * @return $this
     */
    public function setSpeed($vx, $vy);

    /**
     * @param $angle
     * @return $this
     */
    public function setAngle($angle);

    /**
     * @param $id
     * @return $this
     */
    public function setNextCheckPoint($id);

    /**
     * @return array
     */
    public function drive();
}

abstract class DriverStrategyAbstract implements DriverStrategy {
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

    protected function getDistance($pointA, $pointB) {
        return sqrt(pow($pointA['x'] - $pointB['x'], 2) + pow($pointA['y'] - $pointB['y'], 2));
    }
}

class DummyDriver extends DriverStrategyAbstract implements DriverStrategy {
    public function drive()
    {
        $nextCheckpoint = $this->checkpoints[$this->nextCheckPointId];
        return array($nextCheckpoint['x'], $nextCheckpoint['y'], 100);
    }
}

class SlowDownDriver extends DriverStrategyAbstract implements DriverStrategy {
    public function drive()
    {
        $checkpoint = $this->checkpoints[$this->nextCheckPointId];
        $distance = $this->getDistance($this->position->toArray(true), $checkpoint);
        _($distance);
        $power = (int)($distance / 45);
        $power = max(50, $power);
        $power = min(100, $power);
        return array($checkpoint['x'], $checkpoint['y'], $power);
    }
}

class FasterSlowDownDriver extends DriverStrategyAbstract implements DriverStrategy {
    public function drive()
    {
        $checkpoint = $this->checkpoints[$this->nextCheckPointId];
        $distance = $this->getDistance(array('x' => $this->x, 'y' => $this->y), $checkpoint);
        _($distance);
        $power = (int)($distance / 45);
        $power = max(50, $power);
        $power = min(200, $power);
        return array($checkpoint['x'], $checkpoint['y'], $power);
    }
}

class AggressiveDummyDriver extends DriverStrategyAbstract implements DriverStrategy {
    public function drive()
    {
        $opponentsPositions = $this->opponentsPositions;
        $this->opponentsPositions = [];
        $return = $opponentsPositions[0]->toArray(false);
        array_push($return, 200);
        return $return;
    }
}

class AggressiveAlternativeDriver extends DriverStrategyAbstract implements DriverStrategy {
    protected $nbTurnsPassed = 0;
    public function drive()
    {
        ++$this->nbTurnsPassed;

        //Get opponents positions and reset them.
        $opponentsPositions = $this->opponentsPositions;
        $this->opponentsPositions = [];

        //Choose an opponent.
        $opponentIndex = floor($this->nbTurnsPassed / 75)%2;
        $return = $opponentsPositions[$opponentIndex]->toArray(false);
        array_push($return, 150);
        return $return;
    }
}

class AggressiveAlternativeSlowDownDriver extends DriverStrategyAbstract implements DriverStrategy {
    protected $nbTurnsPassed = 0;
    public function drive()
    {
        ++$this->nbTurnsPassed;

        //Get opponents positions and reset them.
        $opponentsPositions = $this->opponentsPositions;
        $this->opponentsPositions = [];

        //Choose an opponent.
        $opponentIndex = floor($this->nbTurnsPassed / 100)%2;
        $return = $opponentsPositions[$opponentIndex]->toArray(false);

        $power = 150;
        if ($this->getDistance($this->position->toArray(true), $opponentsPositions[$opponentIndex]->toArray(true)) < 1000) {
            $power = 100;
        }

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
$drivers = [new AggressiveAlternativeSlowDownDriver($checkpoints, $laps), new SlowDownDriver($checkpoints, $laps)];

// game loop
while (TRUE)
{
    for ($i = 0; $i < 2; $i++)
    {
        fscanf(STDIN, "%d %d %d %d %d %d",
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
    for ($i = 0; $i < 2; $i++)
    {
        fscanf(STDIN, "%d %d %d %d %d %d",
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
