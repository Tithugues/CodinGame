<?php

define('DEBUG', true);
function _($var)
{
    if (defined('DEBUG') && DEBUG) {
        error_log(var_export($var, true));
    }
}

final class Coordinates
{
    private $x;
    private $y;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getX(): int
    {
        return $this->x;
    }

    public function getY(): int
    {
        return $this->y;
    }
}

interface PodInterface
{
    public function __construct(DistanceManagerInterface $distanceManager);

    public function setCurrentCoordinates(Coordinates $coordinates): self;

    /**
     * @return Coordinates
     * @throws RuntimeException If current coordinates not yet initialized.
     */
    public function getCoordinates(): Coordinates;

    public function calculateNextCoordinates(int $step): Coordinates;

    public function getMovementAngle(): ?int;

    public function getSpeed(): int;

    public function setNextCheckpointCoordinates(Coordinates $coordinates): self;

    public function getNextCheckpointCoordinates(): Coordinates;

    public function getDistanceToNextCheckpoint(): int;

    public function setDistanceToNextCheckpoint(int $distance): self;

    public function getPodAngleWithNextCheckpoint(): int;

    public function setPodAngleWithNextCheckpoint(int $angle): self;
}

final class Pod implements PodInterface
{
    use angleTrait;

    /** @var DistanceManagerInterface */
    private $distanceManager;

    /** @var Coordinates */
    private $currentCoordinates;
    /** @var Coordinates */
    private $previousCoordinates;
    /** @var Coordinates */
    private $nextCheckpointCoordinates;
    /** @var int */
    private $distanceToNextCheckpoint;
    /** @var int */
    private $podAngleWithNextCheckpoint;
    /** @var int */
    private $movementAngle;

    public function __construct(DistanceManagerInterface $distanceManager)
    {
        $this->distanceManager = $distanceManager;
    }

    public function setCurrentCoordinates(Coordinates $coordinates): PodInterface
    {
        $this->previousCoordinates = $this->currentCoordinates;
        $this->currentCoordinates = clone($coordinates);
        $this->movementAngle = null;
        return $this;
    }

    public function getCoordinates(): Coordinates
    {
        return clone($this->currentCoordinates);
    }

    public function calculateNextCoordinates(int $step, int $power = 100): Coordinates
    {
        if (null === $this->previousCoordinates || null === $this->currentCoordinates) {
            throw new RuntimeException('Not enough data do calculate next coordinates.');
        }

        if (0 >= $step) {
            throw new InvalidArgumentException('Step should be > 0.');
        }

        $movementAngle = $this->getMovementAngle();
        $speed = $this->getSpeed();

        return $this->calculateCoordinates($this->currentCoordinates, $movementAngle, $speed, $step, $power);
    }

    private function calculateCoordinates(Coordinates $point, int $movementAngle, int $speed, int $step, int $power = 100): Coordinates
    {
        if ($step === 0) {
            return $point;
        }

        $newSpeed = $speed*0.85 + $power;
        _('new speed: ' . $newSpeed);
        _('Decalage X: ' . cos(deg2rad($movementAngle))*$newSpeed);
        _('Decalage Y: ' . sin(deg2rad($movementAngle))*$newSpeed);

        return $this->calculateCoordinates(new Coordinates(
            round($point->getX() + cos(deg2rad($movementAngle))*$newSpeed),
            round($point->getY() + sin(deg2rad($movementAngle))*$newSpeed)
        ), $movementAngle, round($newSpeed), --$step, $power);
    }

    private function getPreviousCoordinates(): Coordinates
    {
        if (null === $this->previousCoordinates) {
            throw new RuntimeException('Previous coordinates not yet initialized.');
        }
        return clone($this->previousCoordinates);
    }

    public function setDistanceToNextCheckpoint(int $distance): PodInterface
    {
        $this->distanceToNextCheckpoint = $distance;
        return $this;
    }

    public function getDistanceToNextCheckpoint(): int
    {
        return $this->distanceToNextCheckpoint;
    }

    public function setPodAngleWithNextCheckpoint(int $angle): PodInterface
    {
        $this->podAngleWithNextCheckpoint = $angle;
        return $this;
    }

    public function getPodAngleWithNextCheckpoint(): int
    {
        return $this->podAngleWithNextCheckpoint;
    }

    public function getMovementAngle(): ?int
    {
        if (null !== $this->movementAngle) {
            return $this->movementAngle;
        }

        return $this->calculateMovementAngle();
    }

    public function setNextCheckpointCoordinates(Coordinates $coordinates): PodInterface
    {
        $this->nextCheckpointCoordinates = clone($coordinates);
        $this->movementAngle = null;
        return $this;
    }

    public function getNextCheckpointCoordinates(): Coordinates
    {
        return clone($this->nextCheckpointCoordinates);
    }

    private function calculateMovementAngle(): ?int
    {
        try {
            $this->movementAngle = $this->getAngle(
                $this->getPreviousCoordinates(),
                $this->getCoordinates()
            );
        } catch (RuntimeException $e) {
            $this->movementAngle = null;
        }

        return $this->movementAngle;
    }

    public function getSpeed(): int
    {
        try {
            return $this->distanceManager->measure($this->getPreviousCoordinates(), $this->getCoordinates());
        } catch (RuntimeException $e) {
            return 0;
        }
    }
}

interface CheckpointsManagerInterface
{
    /**
     * @return bool
     */
    public function areAllCheckpointsKnown(): bool;

    /**
     * @param Coordinates $checkpoint Checkpoint coordinates to add
     *
     * @return $this
     */
    public function addCheckpoint(Coordinates $checkpoint): self;

    /**
     * @return Coordinates[]
     */
    public function getCheckpoints(): array;

    /**
     * Return the checkpoint after the given checkpoint
     *
     * @param Coordinates $checkpoint
     *
     * @return Coordinates
     * @throws RuntimeException If we don't know if all checkpoints are already known
     */
    //public function getNextCheckpoint(Coordinates $checkpoint): Coordinates;
}

final class CheckpointsManager implements CheckpointsManagerInterface
{
    /** @var bool */
    private $allCheckpointsKnown = false;

    /** @var Coordinates[] Checkpoints coordinates */
    private $checkpoints = [];

    public function areAllCheckpointsKnown(): bool
    {
        return $this->allCheckpointsKnown;
    }

    public function addCheckpoint(Coordinates $checkpoint): CheckpointsManagerInterface
    {
        //Don't add any more checkpoints, as we have all of them.
        if ($this->areAllCheckpointsKnown()) {
            return $this;
        }

        //Don't add anymore checkpoints, as we are back to the first one.
        if (count($this->checkpoints) > 1 && $checkpoint == $this->checkpoints[0]) {
            $this->allCheckpointsKnown = true;
            return $this;
        }

        $this->checkpoints[] = $checkpoint;
        return $this;
    }

    public function getCheckpoints(): array
    {
        return $this->checkpoints;
    }

    /*public function getNextCheckpoint(Coordinates $checkpoint): Coordinates
    {
        // TODO: Implement getNextCheckpoint() method.
        throw new DomainException(__METHOD__ . ': not yet implemented');
    }*/
}

interface RunStrategyInterface
{
    public function __construct(PodInterface $ally, PodInterface $enemy, CheckpointsManagerInterface $manager, DistanceManagerInterface $distanceManager);

    /**
     * TODO Replace output by an object.
     * @return array ['x' => …, 'y' => …, 'thrust' => …, 'comment' => …]
     */
    public function getDestination(): array;
}

trait angleTrait
{
    private function getAngle(Coordinates $start, Coordinates $end): int
    {
        $abscisse = $end->getX() - $start->getX();
        $ordonnee = $end->getY() - $start->getY();
        $signeOrdonnee = 0 === $ordonnee ? 1 : $ordonnee / abs($ordonnee);
        $hyp = sqrt(($abscisse ** 2) + ($ordonnee ** 2));
        return $this->sanitizeAngle(0 === $hyp ? 0 : rad2deg(acos($abscisse / $hyp)) * $signeOrdonnee);
    }

    /**
     * Angle should be: -180 < $angle <= 180
     *
     * @param int $angle
     *
     * @return int
     */
    private function sanitizeAngle(int $angle): int
    {
        if (180 < $angle) {
            return $angle - 360;
        }

        if (-180 >= $angle) {
            return $angle + 360;
        }

        return $angle;
    }

    private function addAngles(int $start, int $angle): int
    {
        return $this->sanitizeAngle($start + $angle);
    }

    private function diffAngles(int $end, int $start): int
    {
        return $this->sanitizeAngle($end - $start);
    }
}

interface DistanceManagerInterface
{
    public function measure(Coordinates $p1, Coordinates $p2): int;
}

final class DistanceManager implements DistanceManagerInterface
{
    public function measure(Coordinates $p1, Coordinates $p2): int
    {
        $abscisse = $p1->getX() - $p2->getX();
        $ordonnee = $p1->getY() - $p2->getY();
        return round(sqrt(($abscisse ** 2) + ($ordonnee ** 2)));
    }
}

final class DummyRunStrategy implements RunStrategyInterface
{
    use angleTrait;

    /** @var PodInterface */
    private $ally;

    /** @var PodInterface */
    private $enemy;

    /** @var CheckpointsManagerInterface */
    private $checkpointManager;

    /** @var DistanceManagerInterface */
    private $distanceManager;

    /** @var int Last time the shield was activated */
    private $lastShield = 4;

    public function __construct(PodInterface $ally, PodInterface $enemy, CheckpointsManagerInterface $manager, DistanceManagerInterface $distanceManager)
    {
        $this->ally = $ally;
        $this->enemy = $enemy;
        $this->checkpointManager = $manager;
        $this->distanceManager = $distanceManager;
    }

    public function getDestination(): array
    {
        ++$this->lastShield;
        $thrust = null;
        $allyCoordinates = $this->ally->getCoordinates();
        $checkpointCoordinates = $this->ally->getNextCheckpointCoordinates();
        $myDist = $this->ally->getDistanceToNextCheckpoint();
        $myAngleToCheckpoint = $this->ally->getPodAngleWithNextCheckpoint();

        $currentMovementAngle = $this->ally->getMovementAngle();
        $allySpeed = $this->ally->getSpeed();
        $enemySpeed = $this->enemy->getSpeed();
        _('SPEED: ' . $allySpeed);
        $angleToReachCheckpoint = $this->getAngle($allyCoordinates, $checkpointCoordinates);
        $myAngle = $this->addAngles($angleToReachCheckpoint, $myAngleToCheckpoint);

        $abscisse = $checkpointCoordinates->getX() - $allyCoordinates->getX();
        $ordonnee = $checkpointCoordinates->getY() - $allyCoordinates->getY();
        $hyp = sqrt(pow($abscisse, 2) + pow($ordonnee, 2));
        $facteur = ($hyp - 300) / $hyp;
        $destAbscisse = $abscisse * $facteur;
        $destOrdonnee = $ordonnee * $facteur;

        try {
            if ($this->lastShield < 3) {
                throw new RuntimeException('Shield activated ' . $this->lastShield . ' turn(s) ago.');
            }
            $distBetweenPodsIn1Step = $this->distanceManager->measure(
                $allyNextCoordinates = $this->ally->calculateNextCoordinates(1),
                $enemyNextCoordinates = $this->enemy->calculateNextCoordinates(1)
            );
            _('Ally next coordinates: ');
            _($allyNextCoordinates);
            _('Enemy next coordinates: ');
            _($enemyNextCoordinates);
            $ecartPodsMovementAngle = $this->diffAngles($currentMovementAngle, $this->enemy->getMovementAngle());
            $anglePods = $this->getAngle($this->enemy->getCoordinates(), $this->ally->getCoordinates());
            $ecartPodsAngleMyAngle = $this->diffAngles($anglePods, $myAngle);
            $enemyIsBehindAlly = abs($ecartPodsAngleMyAngle) < 45;
            $ecartReversePodsAngleMyAngle = $this->diffAngles($this->addAngles($anglePods, 180), $myAngle);
            $allyIsBehindEnemy = abs($ecartReversePodsAngleMyAngle) < 45;
            _('$allySpeed: ' . $allySpeed);
            _('$enemySpeed: ' . $enemySpeed);
            _('$ecartPodsAngleMyAngle: ' . $ecartPodsAngleMyAngle);
            _('$ecartReversePodsAngleMyAngle: ' . $ecartReversePodsAngleMyAngle);
            _('$ecartPodsMovementAngle: ' . $ecartPodsMovementAngle);
            _('$enemyIsBehindAlly: ' . (int)$enemyIsBehindAlly);
            _('$allyIsBehindEnemy: ' . (int)$allyIsBehindEnemy);
            _('Distance in 1 step: ' . $distBetweenPodsIn1Step);
            if ($distBetweenPodsIn1Step < 750 && $enemySpeed > 300
                && (
                    //
                    (!$enemyIsBehindAlly && abs($ecartPodsMovementAngle) > 45)
                    || ($allyIsBehindEnemy && abs($ecartPodsMovementAngle) < 45)
                )
            ) {
                $this->lastShield = 0;
                return [
                    'x' => $allyCoordinates->getX() + round($destAbscisse),
                    'y' => $allyCoordinates->getY() + round($destOrdonnee),
                    'thrust' => 'SHIELD',
                ];
            }
            $distBetweenPodsIn2Steps = $this->distanceManager->measure(
                $allyCoordinatesIn2Steps = $this->ally->calculateNextCoordinates(2),
                $enemyCoordinatesIn2Steps = $this->enemy->calculateNextCoordinates(2)
            );
            _('Distance in 2 steps: ' . $distBetweenPodsIn2Steps);
            if ($distBetweenPodsIn2Steps < 800 && $enemySpeed > 300 && abs($ecartPodsAngleMyAngle) > 45 && abs($ecartPodsMovementAngle) > 45) {
                _('Will shield on next turn.');
                //Se tourner vers l'ennemi et accélérer.
                return [
                    'x' => $enemyCoordinatesIn2Steps->getX() * 2 - $allyCoordinatesIn2Steps->getX(),
                    'y' => $enemyCoordinatesIn2Steps->getY() * 2 - $allyCoordinatesIn2Steps->getY(),
                    'thrust' => '100',
                ];
            }
        } catch (RuntimeException $e) {
            _($e->getMessage());
        }

        //Si l'angle du mouvement n'est pas assez proche de l'angle nécessaire pour arriver à destination,
        //alors il faut donner au pod un angle pour compenser.
        if (null !== $currentMovementAngle) {
            $ecartAngleToReachTarget = $this->diffAngles($angleToReachCheckpoint, $currentMovementAngle);
            $angleToTarget = null;

            if (abs($ecartAngleToReachTarget) > 90) {
                if ($allySpeed > 200) {
                    _('> 90');
                    //Target median between checkpoint and opposite of movement.
                    $oppositeAngleToMovement = $this->addAngles($currentMovementAngle, 180);
                    _('ANGLE OPPOSITE TO MOVEMENT: ' . $oppositeAngleToMovement);
                    $ecartAngleOppositeCheckpoint = $this->diffAngles(
                        $angleToReachCheckpoint,
                        $oppositeAngleToMovement
                    );
                    _('ANGLE BETWEEN OPPOSITE AND CP: ' . $ecartAngleOppositeCheckpoint);
                    $angleToTarget = $this->addAngles(
                        $oppositeAngleToMovement,
                        (int) ($ecartAngleOppositeCheckpoint / 2)
                    );
                    _('PRE-COMPENSATION: ' . $angleToTarget);

                    $ecartAnglePodMovement = $this->diffAngles($currentMovementAngle, $myAngle);
                    if ($ecartAnglePodMovement > 0) {
                        $angleToTarget = $this->addAngles($angleToTarget, +1);
                    } else {
                        $angleToTarget = $this->addAngles($angleToTarget, -1);
                    }
                    _('POST-COMPENSATION: ' . $angleToTarget);
                }

            } elseif (abs($ecartAngleToReachTarget) > 30) {
                _('> 30');
                $angleToTarget = $this->addAngles($angleToReachCheckpoint, round($ecartAngleToReachTarget/2));
            }

            _('POD ABSOLUTE ANGLE: ' . $myAngle);
            _('MOUVEMENT: ' . $currentMovementAngle);
            _('TARGET: ' . $angleToReachCheckpoint);
            _('ECART MOUVEMENT/TARGET: ' . $ecartAngleToReachTarget);
            _('COMPENSATION: ' . $angleToTarget);
            if (null !== $angleToTarget) {
                $destAbscisse = cos(deg2rad($angleToTarget)) * $myDist;
                $destOrdonnee = sin(deg2rad($angleToTarget)) * $myDist;
                _('New abscisse: ' . $destAbscisse);
                _('New ordonnee: ' . $destOrdonnee);
                //$thrust = 100 - min(50, round(abs($myAngleToCheckpoint/2)));
            }
        }

        if (null === $thrust) {
            if (abs($myAngleToCheckpoint) < 15 && $myDist > 6000) {
                $thrust = 'BOOST';
            } elseif (abs($myAngleToCheckpoint) > 135) {
                $thrust = 10;
            } elseif (abs($myAngleToCheckpoint) > 90) {
                $thrust = 35;
            } elseif (abs($myAngleToCheckpoint) > 70) {
                $thrust = 60;
            } elseif ($allySpeed < 250) {
                $thrust = 100;
            } else {
                /*if ($myDist < 1500) {
                    $thrust = 30;
                } else*/
                if ($myDist < 1500) {
                    $thrust = 45;
                } elseif ($myDist < 2000) {
                    $thrust = 60;
                } else {
                    $thrust = 100;
                }
            }
        }

        return [
            'x' => $allyCoordinates->getX() + round($destAbscisse),
            'y' => $allyCoordinates->getY() + round($destOrdonnee),
            'thrust' => $thrust,
        ];
    }
}


$distanceManager = new DistanceManager();
$map = new DummyRunStrategy(
    $ally = new Pod($distanceManager),
    $enemy = new Pod($distanceManager),
    $manager = new CheckpointsManager(),
    $distanceManager
);

// game loop
while (true) {
    fscanf(
        STDIN,
        '%d %d %d %d %d %d',
        $x,
        $y,
        $nextCheckpointX,
        $nextCheckpointY,
        $nextCheckpointDist,
        $nextCheckpointAngle
    );
    fscanf(
        STDIN,
        "%d %d",
        $xEnnemy,
        $yEnnemy
    );

    $cpCoordinates = new Coordinates($nextCheckpointX, $nextCheckpointY);
    $manager->addCheckpoint($cpCoordinates);
    $ally->setCurrentCoordinates(new Coordinates($x, $y))
        ->setDistanceToNextCheckpoint($nextCheckpointDist)
        ->setPodAngleWithNextCheckpoint($nextCheckpointAngle)
        ->setNextCheckpointCoordinates($cpCoordinates);
    $enemy->setCurrentCoordinates(new Coordinates($xEnnemy, $yEnnemy));
    $dest = $map->getDestination();

    _('Me: ' . $x . ' ' . $y);
    _('Next checkpoint: ' . $nextCheckpointX . ' ' . $nextCheckpointY);
    _('Distance: ' . $nextCheckpointDist);
    _('Angle: ' . $nextCheckpointAngle);

    echo($dest['x']
        . ' '
        . $dest['y']
        . ' '
        . $dest['thrust']
        . ' '
        . $nextCheckpointDist
        . ' '
        . $nextCheckpointAngle
        . ' '
        . $dest['thrust']
        . "\n");
}