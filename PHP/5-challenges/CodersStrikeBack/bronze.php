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
    public function setCurrentCoordinates(Coordinates $coordinates): self;

    /**
     * @return Coordinates
     * @throws RuntimeException If current coordinates not yet initialized.
     */
    public function getCoordinates(): Coordinates;
    public function getMovementAngle(): ?int;
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
    public function __construct(PodInterface $ally, PodInterface $enemy, CheckpointsManagerInterface $manager);

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

final class DummyRunStrategy implements RunStrategyInterface
{
    use angleTrait;

    /** @var PodInterface */
    private $ally;

    /** @var PodInterface */
    private $enemy;

    /** @var CheckpointsManagerInterface */
    private $checkpointManager;

    public function __construct(PodInterface $ally, PodInterface $enemy, CheckpointsManagerInterface $manager)
    {
        $this->ally = $ally;
        $this->enemy = $enemy;
        $this->checkpointManager = $manager;
    }

    public function getDestination(): array
    {
        $thrust = null;
        $allyCoordinates = $this->ally->getCoordinates();
        $checkpointCoordinates = $this->ally->getNextCheckpointCoordinates();
        $myDist = $this->ally->getDistanceToNextCheckpoint();
        $myAngleToCheckpoint = $this->ally->getPodAngleWithNextCheckpoint();

        $abscisse = $checkpointCoordinates->getX() - $allyCoordinates->getX();
        $ordonnee = $checkpointCoordinates->getY() - $allyCoordinates->getY();
        $hyp = sqrt(pow($abscisse, 2) + pow($ordonnee, 2));
        $facteur = ($hyp - 300) / $hyp;
        $destAbscisse = $abscisse * $facteur;
        $destOrdonnee = $ordonnee * $facteur;

        //Si l'angle du mouvement n'est pas assez proche de l'angle nécessaire pour arriver à destination,
        //alors il faut donner au pod un angle pour compenser.
        $currentMovementAngle = $this->ally->getMovementAngle();
        if (null !== $currentMovementAngle) {
            $angleToReachCheckpoint = $this->getAngle($allyCoordinates, $checkpointCoordinates);
            $ecartAngleToReachTarget = $this->diffAngles($angleToReachCheckpoint, $currentMovementAngle);
            $angleToTarget = null;

            $myAngle = $this->addAngles($angleToReachCheckpoint, $myAngleToCheckpoint);

            if (abs($ecartAngleToReachTarget) > 90) {
                _('> 90');
                //Target median between checkpoint and opposite of movement.
                $oppositeAngleToMovement = $this->addAngles($currentMovementAngle, 180);
                _('ANGLE OPPOSITE TO MOVEMENT: ' . $oppositeAngleToMovement);
                $ecartAngleOppositeCheckpoint = $this->diffAngles($angleToReachCheckpoint, $oppositeAngleToMovement);
                _('ANGLE BETWEEN OPPOSITE AND CP: ' . $ecartAngleOppositeCheckpoint);
                $angleToTarget = $this->addAngles($oppositeAngleToMovement, (int) ($ecartAngleOppositeCheckpoint / 2));
                _('PRE-COMPENSATION: ' . $angleToTarget);

                $ecartAnglePodMovement = $this->diffAngles($currentMovementAngle, $myAngle);
                if ($ecartAnglePodMovement > 0) {
                    $angleToTarget = $this->addAngles($angleToTarget, +1);
                } else {
                    $angleToTarget = $this->addAngles($angleToTarget, -1);
                }
                _('POST-COMPENSATION: ' . $angleToTarget);

                /*$angleToTarget = $this->addAngles($currentMovementAngle, 180);
                $ecartAnglePodMovement = $this->diffAngles($currentMovementAngle, $myAngle);
                _('ANGLE POD->MOVEMENT: ' . $ecartAnglePodMovement);
                _('PRE-COMPENSATION: ' . $angleToTarget);
                if ($ecartAnglePodMovement > 0) {
                    $angleToTarget = $this->addAngles($angleToTarget, -(int)($ecartAnglePodMovement/2)+1);
                } else {
                    $angleToTarget = $this->addAngles($angleToTarget, -(int)($ecartAnglePodMovement/2)-1);
                }
                _('POST-COMPENSATION: ' . $angleToTarget);
                $thrust = 100;*/
            } elseif (abs($ecartAngleToReachTarget) > 30) {
                _('> 30');
                $angleToTarget = $this->addAngles($angleToReachCheckpoint, $ecartAngleToReachTarget);
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
            }
        }

        if (null === $thrust) {
            if (abs($myAngleToCheckpoint) < 15 && $myDist > 5000) {
                $thrust = 'BOOST';
            } elseif (abs($myAngleToCheckpoint) > 135) {
                $thrust = 0;
            } elseif (abs($myAngleToCheckpoint) > 90) {
                $thrust = 15;
            } elseif (abs($myAngleToCheckpoint) > 70) {
                $thrust = 30;
            } else {
                /*if ($myDist < 1500) {
                    $thrust = 30;
                } else*/if ($myDist < 1500) {
                    $thrust = 45;
                } elseif ($myDist < 2000) {
                    $thrust = 70;
                } elseif ($myDist < 2500) {
                    $thrust = 85;
                } else {
                    $thrust = 100;
                }
            }
        }

        return ['x' => $allyCoordinates->getX() + round($destAbscisse), 'y' => $allyCoordinates->getY() + round($destOrdonnee), 'thrust' => $thrust];
    }
}

$map = new DummyRunStrategy($ally = new Pod(), $enemy = new Pod(), $manager = new CheckpointsManager());

// game loop
while (TRUE)
{
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

    echo ($dest['x'] . ' ' . $dest['y'] . ' ' . $dest['thrust'] . ' ' . $nextCheckpointDist . ' ' . $nextCheckpointAngle . ' ' . $dest['thrust'] . "\n");
}