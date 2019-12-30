<?php

//region debug
define('DEBUG', true);
function _($var)
{
    if (defined('DEBUG') && DEBUG) {
        error_log(var_export($var, true));
    }
}
//endregion

//region configuration
define('SHIELD_EFFECT_DURATION', 3);
define('SPEED_BREAK_PER_TURN', 0.85);

define('THRUST_SHIELD', 'SHIELD');
define('THRUST_BOOST', 'BOOST');
define('THRUST_BOOST_REAL_POWER', 650);
define('THRUST_BOOST_UNAVAILABLE', 100);
define('MAX_ROTATION', 18);
//endregion

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

interface AnswerInterface
{
    public function echo(): void;
}

interface OneAnswerInterface extends AnswerInterface
{
    public function getThrust(): string;
}

final class Answer implements OneAnswerInterface
{
    private $x;
    private $y;
    private $thrust;
    private $comment;

    public function __construct(int $x, int $y, string $thrust, ?string $comment = null)
    {
        $this->x = $x;
        $this->y = $y;
        $this->thrust = $thrust;
        $this->comment = $comment;
    }

    public function echo(): void
    {
        echo rtrim($this->x . ' ' . $this->y . ' ' . $this->thrust . ' ' . $this->comment) . "\n";
    }

    public function getThrust(): string
    {
        return $this->thrust;
    }
}

final class AnswersComposite implements AnswerInterface
{
    /** @var AnswerInterface[] */
    private $answers = [];

    public function addAnswer(AnswerInterface $answer): self
    {
        $this->answers[] = $answer;
        return $this;
    }

    public function echo(): void
    {
        foreach ($this->answers as $answer) {
            $answer->echo();
        }
    }


}

interface PodInterface
{
    public function setState(
        Coordinates $coordinates,
        int $speedX,
        int $speedY,
        int $angle,
        int $nextCheckpointId
    ): self;

    /**
     * @return Coordinates
     * @throws RuntimeException If current coordinates not yet initialized.
     */
    public function getCurrentCoordinates(): Coordinates;
    public function getPreviousCoordinates(): Coordinates;
    public function getSpeedX(): int;
    public function getSpeedY(): int;
    public function getAngle(): int;
    public function getNextCheckpointId(): int;
    public function useShield(): self;
    public function getShieldEffectEnd(): int;
    public function useBoost(): self;
    public function isBoostAvailable(): bool;
}

final class Pod implements PodInterface
{
    /** @var Coordinates */
    private $currentCoordinates;
    /** @var Coordinates */
    private $previousCoordinates;
    /** @var int */
    private $speedX;
    /** @var int */
    private $speedY;
    /** @var int */
    private $nextCheckpointId;
    /** @var int */
    private $angle;
    /** @var int */
    private $shieldEffectEnd = 0;
    /** @var bool */
    private $boostAvailable = true;

    public function setState(
        Coordinates $coordinates,
        int $speedX,
        int $speedY,
        int $angle,
        int $nextCheckpointId
    ): PodInterface
    {
        $this->currentCoordinates = $coordinates;
        $this->speedX = $speedX;
        $this->speedY = $speedY;
        $this->angle = $angle;
        $this->nextCheckpointId = $nextCheckpointId;

        $this->shieldEffectEnd > 0 && --$this->shieldEffectEnd;

        return $this;
    }

    public function getCurrentCoordinates(): Coordinates
    {
        return clone($this->currentCoordinates);
    }

    public function getPreviousCoordinates(): Coordinates
    {
        if (null === $this->previousCoordinates) {
            throw new RuntimeException('Previous coordinates not yet initialized.');
        }
        return clone($this->previousCoordinates);
    }

    public function getSpeedX(): int
    {
        return $this->speedX;
    }

    public function getSpeedY(): int
    {
        return $this->speedY;
    }

    public function getAngle(): int
    {
        return $this->angle;
    }

    public function getNextCheckpointId(): int
    {
        return $this->nextCheckpointId;
    }

    public function useShield(): PodInterface
    {
        $this->shieldEffectEnd = SHIELD_EFFECT_DURATION;
        return $this;
    }

    public function getShieldEffectEnd(): int
    {
        return $this->shieldEffectEnd;
    }

    public function useBoost(): PodInterface
    {
        $this->boostAvailable = false;
        return $this;
    }

    public function isBoostAvailable(): bool
    {
        return $this->boostAvailable;
    }
}

interface CheckpointsManagerInterface
{
    public function __construct(Coordinates ...$checkpoints);

    /**
     * @return Coordinates[]
     */
    public function getCheckpoints(): array;

    /**
     * Return the given checkpoint
     *
     * @param int $id Checkpoint ID, regarding the order given calling addCheckpoint (starts to 0)
     *
     * @return Coordinates
     */
    public function getCheckpoint(int $id): Coordinates;

    /**
     * Return the checkpoint after the given checkpoint
     *
     * @param int $id Checkpoint ID, regarding the order given calling addCheckpoint (starts to 0)
     *
     * @return Coordinates
     */
    public function getNextCheckpoint(int $id): Coordinates;
}

final class CheckpointsManager implements CheckpointsManagerInterface
{
    /** @var Coordinates[] Checkpoints coordinates */
    private $checkpoints;

    public function __construct(Coordinates ...$checkpoints)
    {
        $this->checkpoints = static::clone(...$checkpoints);
    }

    public function getCheckpoints(): array
    {
        return static::clone(...$this->checkpoints);
    }

    public function getCheckpoint(int $id): Coordinates
    {
        return clone($this->checkpoints[$id]);
    }

    public function getNextCheckpoint(int $id): Coordinates
    {
        return clone($this->checkpoints[($id+1)%count($this->checkpoints)]);
    }

    private static function clone(Coordinates ...$checkpoints): array
    {
        $clone = static function (Coordinates $coordinate) {
            return clone($coordinate);
        };
        return array_map($clone, $checkpoints);
    }
}

final class AngleManager
{
    public static function getAngle(Coordinates $start, Coordinates $end): int
    {
        $abscisse = $end->getX() - $start->getX();
        $ordonnee = $end->getY() - $start->getY();
        $signeOrdonnee = 0 === $ordonnee ? 1 : $ordonnee / abs($ordonnee);
        $hyp = sqrt(($abscisse ** 2) + ($ordonnee ** 2));
        return self::sanitizeAngle(0 === $hyp ? 0 : rad2deg(acos($abscisse / $hyp)) * $signeOrdonnee);
    }

    /**
     * Angle should be: 0 <= $angle < 360
     *
     * @param int $angle
     *
     * @return int
     */
    public static function sanitizeAngle(int $angle): int
    {
        return $angle%360;
    }

    public static function addAngles(int $start, int $angle): int
    {
        return self::sanitizeAngle($start + $angle);
    }

    public static function diffAngles(int $end, int $start): int
    {
        return self::sanitizeAngle($end - $start);
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

interface PhysicsEngineInterface
{
    public function getNextState(PodInterface $pod, Coordinates $destination, string $thrust): PodInterface;
}

final class DummyPhysicsEngineInterface implements PhysicsEngineInterface
{
    /** @var AngleManager */
    private $angleManager;

    public function __construct(AngleManager $angleManager)
    {
        $this->angleManager = $angleManager;
    }

    /**
     * @param PodInterface $pod
     * @param string $thrust
     *
     * @return int
     */
    private static function getEffectiveThrust(PodInterface $pod, string $thrust): int
    {
        if ($thrust === THRUST_SHIELD || $pod->getShieldEffectEnd() > 0) {
            $effectiveThrust = 0;
        } elseif ($thrust === THRUST_BOOST) {
            if (! $pod->isBoostAvailable()) {
                $effectiveThrust = THRUST_BOOST_UNAVAILABLE;
            } else {
                $effectiveThrust = THRUST_BOOST_REAL_POWER;
            }
        } else {
            $effectiveThrust = $thrust;
        }
        return $effectiveThrust;
    }

    private static function truncate(float $val, int $f = 0): float
    {
        if (($p = strpos($val, '.')) !== false) {
            $val = (float) substr($val, 0, $p + 1 + $f);
        }
        return $val;
    }

    /**
     * Here are the steps for a movement:
     * 1: turn
     * 2: accelerate
     * 3: lose speed
     *
     * When thrust:
     * - SHIELD: will act like "thrust=0"
     * - BOOST: will act like "thrust=?"
     *
     * TODO: handle shocks
     * TODO: detect new checkpoint ID
     *
     * @param PodInterface $pod
     * @param Coordinates $destination
     * @param string $thrust
     *
     * @return PodInterface
     */
    public function getNextState(PodInterface $pod, Coordinates $destination, string $thrust): PodInterface
    {
        $pod = clone($pod);

        $effectiveThrust = self::getEffectiveThrust($pod, $thrust);
        $newPodAngle = $this->getNewPodAngle($pod, $destination);

        $speedXBeforeLosingSpeed = cos(deg2rad($newPodAngle)) * $effectiveThrust + $pod->getSpeedX();
        $speedYBeforeLosingSpeed = sin(deg2rad($newPodAngle)) * $effectiveThrust + $pod->getSpeedY();

        $newSpeedX = $speedXBeforeLosingSpeed * SPEED_BREAK_PER_TURN;
        $newSpeedY = $speedYBeforeLosingSpeed * SPEED_BREAK_PER_TURN;

        $newNextCheckpointId = $pod->getNextCheckpointId();

        $pod->setState(new Coordinates(round($pod->getCurrentCoordinates()->getX() + $speedXBeforeLosingSpeed), round($pod->getCurrentCoordinates()->getY() + $speedYBeforeLosingSpeed)), self::truncate($newSpeedX), self::truncate($newSpeedY), $newPodAngle, $newNextCheckpointId);

        _($pod);

        return $pod;
    }

    /**
     * @param PodInterface $pod
     * @param Coordinates $destination
     *
     * @return int
     */
    private function getNewPodAngle(PodInterface $pod, Coordinates $destination): int
    {
        $angleNeededToReachDestination = $this->angleManager::getAngle($pod->getCurrentCoordinates(), $destination);
        $currentPodAngle = $pod->getAngle();
        if (-1 === $currentPodAngle) {
            $newPodAngle = $angleNeededToReachDestination;
        } else {
            $angleToDestination = $this->angleManager::diffAngles($angleNeededToReachDestination, $pod->getAngle());
            if ($angleToDestination < 0) {
                $angleToAdd = max($angleToDestination, -18);
            } else {
                $angleToAdd = min($angleToDestination, 18);
            }
            $newPodAngle = $this->angleManager::sanitizeAngle($pod->getAngle() + $angleToAdd);
        }
        return $newPodAngle;
}
}

interface RunStrategyInterface
{
    public function __construct(CheckpointsManagerInterface $checkpointsManager, DistanceManagerInterface $distanceManager, AngleManager $angleManager, PhysicsEngineInterface $pe, PodInterface ...$pods);

    /**
     * @param int $index Index given in the construct
     * @param Coordinates $coordinates
     * @param int $speedX
     * @param int $speedY
     * @param int $angle
     * @param int $nextCheckpointId
     *
     * @return $this
     */
    public function setPodState(int $index, Coordinates $coordinates, int $speedX, int $speedY, int $angle, int $nextCheckpointId): self;
    public function getDestinations(): AnswerInterface;
}

final class DummyRunStrategy implements RunStrategyInterface
{
    /** @var PodInterface[] */
    private $allies;

    /** @var PodInterface[] */
    private $enemies;

    /** @var CheckpointsManagerInterface */
    private $checkpointsManager;

    /** @var DistanceManagerInterface */
    private $distanceManager;

    /** @var AngleManager */
    private $angleManager;

    /** @var PhysicsEngineInterface */
    private $physicsEngine;

    /** @var int Last time the shield was activated */
    private $lastShield = 4;

    /**
     * @var int
     * @deprecated
     */
    private $turn = 0;

    public function __construct(CheckpointsManagerInterface $checkpointsManager, DistanceManagerInterface $distanceManager, AngleManager $angleManager, PhysicsEngineInterface $pe, PodInterface ...$pods)
    {
        if (count($pods)%2 === 1) {
            throw new InvalidArgumentException('There should be as much ally pods than enemy pods.');
        }

        $numberPodsByTeam = count($pods) / 2;
        $this->checkpointsManager = $checkpointsManager;
        $this->distanceManager = $distanceManager;
        $this->angleManager = $angleManager;
        $this->physicsEngine = $pe;
        $this->allies = array_slice($pods, 0, $numberPodsByTeam, true);
        $this->enemies = array_slice($pods, $numberPodsByTeam, $numberPodsByTeam, true);
    }

    public function setPodState(
        int $index,
        Coordinates $coordinates,
        int $speedX,
        int $speedY,
        int $angle,
        int $nextCheckpointId
    ): RunStrategyInterface
    {
        if (array_key_exists($index, $this->allies)) {
            $pod = $this->allies[$index];
        } elseif (array_key_exists($index, $this->enemies)) {
            $pod = $this->enemies[$index];
        } else {
            throw new InvalidArgumentException('Non-existing given index. "' . $index . '".');
        }
        $pod->setState($coordinates, $speedX, $speedY, $angle, $nextCheckpointId);
        return $this;
    }

    public function getDestinations(): AnswerInterface
    {
        foreach ($this->allies as $ally) {
            _(
                $ally->getCurrentCoordinates()->getX() . ',' . $ally->getCurrentCoordinates()->getY()
                    . ' ' . $ally->getSpeedX() . '/' . $ally->getSpeedY()
                    . ' -> ' . $ally->getAngle()
            );
        }

        $decalage = +100000;
        $thrusts = [1 => 'SHIELD', 2 => 'BOOST'];

        $globalAnswer = new AnswersComposite();

        if ($this->turn === 0) {
            ++$this->turn;
            $this->physicsEngine->getNextState($this->allies[0],  new Coordinates($this->allies[0]->getCurrentCoordinates()->getX() + $decalage, $this->allies[0]->getCurrentCoordinates()->getY()), $thrusts[$this->turn]);
            $thrust = $thrusts[$this->turn];
            if (THRUST_SHIELD === $thrust) {
                $this->allies[0]->useShield();
                $this->allies[1]->useShield();
            } elseif (THRUST_BOOST === $thrust) {
                $this->allies[0]->useBoost();
                $this->allies[1]->useBoost();
            }
            return $globalAnswer->addAnswer(new Answer($this->allies[0]->getCurrentCoordinates()->getX() + $decalage, $this->allies[0]->getCurrentCoordinates()->getY(), $thrusts[$this->turn]))
                ->addAnswer(new Answer($this->allies[1]->getCurrentCoordinates()->getX() + $decalage, $this->allies[1]->getCurrentCoordinates()->getY(), $thrusts[$this->turn]));
        }

        if ($this->turn === 1) {
            ++$this->turn;
            $this->physicsEngine->getNextState($this->allies[0],  new Coordinates($this->allies[0]->getCurrentCoordinates()->getX(), $this->allies[0]->getCurrentCoordinates()->getY() + $decalage), $thrusts[$this->turn]);
            $thrust = $thrusts[$this->turn];
            if (THRUST_SHIELD === $thrust) {
                $this->allies[0]->useShield();
                $this->allies[1]->useShield();
            } elseif (THRUST_BOOST === $thrust) {
                $this->allies[0]->useBoost();
                $this->allies[1]->useBoost();
            }
            return $globalAnswer->addAnswer(new Answer($this->allies[0]->getCurrentCoordinates()->getX(), $this->allies[0]->getCurrentCoordinates()->getY() + $decalage, $thrusts[$this->turn]))
                ->addAnswer(new Answer($this->allies[1]->getCurrentCoordinates()->getX(), $this->allies[1]->getCurrentCoordinates()->getY() + $decalage, $thrusts[$this->turn]));
        }

        throw new Exception('temporary exception');

        $numberPodsByGroup = count($this->allies)/2;

        foreach (array_slice($this->allies, 0, $numberPodsByGroup, true) as $index => $ally) {
            $globalAnswer->addAnswer($podAnswer = $this->getDestinationForRunner($index));
            $thrust = $podAnswer->getThrust();
            if (THRUST_SHIELD === $thrust) {
                $ally->useShield();
            } elseif (THRUST_BOOST === $thrust) {
                $ally->useBoost();
            }
        }
        foreach (array_slice($this->allies, $numberPodsByGroup, $numberPodsByGroup, true) as $index => $ally) {
            $globalAnswer->addAnswer($podAnswer = $this->getDestinationForKicker($index));
            $thrust = $podAnswer->getThrust();
            if (THRUST_SHIELD === $thrust) {
                $ally->useShield();
            } elseif (THRUST_BOOST === $thrust) {
                $ally->useBoost();
            }
        }
        return $globalAnswer;
    }

    private function getDestinationForRunner(int $podIndex): OneAnswerInterface
    {
        $pod = $this->allies[$podIndex];

        $thrust = null;
        $allyCoordinates = $pod->getCurrentCoordinates();
        $checkpointCoordinates = $this->checkpointsManager->getCheckpoints()[$pod->getNextCheckpointId()];
        $myDist = $this->distanceManager->measure(
            $pod->getCurrentCoordinates(),
            $this->checkpointsManager->getCheckpoint($pod->getNextCheckpointId())
        );
        $myAngleToCheckpoint = $this->angleManager::getAngle(
            $pod->getCurrentCoordinates(),
            $this->checkpointsManager->getCheckpoint($pod->getNextCheckpointId())
        );

        $currentMovementAngle = $this->getMovementAngle($pod);
        $allySpeed = $this->getSpeed($pod);
        $enemySpeed = $this->getSpeed($this->enemies[2]);
        _('SPEED: ' . $allySpeed);
        $angleToReachCheckpoint = $this->angleManager::getAngle($allyCoordinates, $checkpointCoordinates);
        $myAngle = $this->angleManager::addAngles($angleToReachCheckpoint, $myAngleToCheckpoint);

        $abscisse = $checkpointCoordinates->getX() - $allyCoordinates->getX();
        $ordonnee = $checkpointCoordinates->getY() - $allyCoordinates->getY();
        $hyp = sqrt(($abscisse ** 2) + ($ordonnee ** 2));
        $facteur = ($hyp - 300) / $hyp;
        $destAbscisse = $abscisse * $facteur;
        $destOrdonnee = $ordonnee * $facteur;

        try {
            if ($this->lastShield < 3) {
                throw new RuntimeException('Shield activated ' . $this->lastShield . ' turn(s) ago.');
            }
            $distBetweenPodsIn1Step = $this->distanceManager->measure(
                $allyNextCoordinates = $this->calculateNextCoordinates($pod, 1),
                $enemyNextCoordinates = $this->calculateNextCoordinates($this->enemies[2], 1)
            );
            _('Ally next coordinates: ');
            _($allyNextCoordinates);
            _('Enemy next coordinates: ');
            _($enemyNextCoordinates);
            $ecartPodsMovementAngle = $this->angleManager::diffAngles($currentMovementAngle, $this->getMovementAngle($this->enemies[2]));
            $anglePods = $this->angleManager::getAngle($this->enemies[2]->getCurrentCoordinates(), $pod->getCurrentCoordinates());
            $ecartPodsAngleMyAngle = $this->angleManager::diffAngles($anglePods, $myAngle);
            $enemyIsBehindAlly = abs($ecartPodsAngleMyAngle) < 45;
            $ecartReversePodsAngleMyAngle = $this->angleManager::diffAngles($this->angleManager::addAngles($anglePods, 180), $myAngle);
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
                return new Answer(
                    $allyCoordinates->getX() + round($destAbscisse),
                    $allyCoordinates->getY() + round($destOrdonnee),
                    THRUST_SHIELD
                );
            }
            $distBetweenPodsIn2Steps = $this->distanceManager->measure(
                $allyCoordinatesIn2Steps = $this->calculateNextCoordinates($pod, 2),
                $enemyCoordinatesIn2Steps = $this->calculateNextCoordinates($this->enemies[2], 2)
            );
            _('Distance in 2 steps: ' . $distBetweenPodsIn2Steps);
            if ($distBetweenPodsIn2Steps < 800 && $enemySpeed > 300 && abs($ecartPodsAngleMyAngle) > 45 && abs($ecartPodsMovementAngle) > 45) {
                _('Will shield on next turn.');
                //Se tourner vers l'ennemi et accélérer.
                return new Answer(
                    $enemyCoordinatesIn2Steps->getX() * 2 - $allyCoordinatesIn2Steps->getX(),
                    $enemyCoordinatesIn2Steps->getY() * 2 - $allyCoordinatesIn2Steps->getY(),
                    '100'
                );
            }
        } catch (RuntimeException $e) {
            _($e->getMessage());
        }

        if (null !== $currentMovementAngle) {
            $ecartAngleToReachTarget = $this->angleManager::diffAngles($angleToReachCheckpoint, $currentMovementAngle);
            $angleToTarget = null;

            //Si l'angle du mouvement n'est pas assez proche de l'angle nécessaire pour arriver à destination,
            //alors il faut donner au pod un angle pour compenser.
            if (abs($ecartAngleToReachTarget) > 90) {
                if ($allySpeed > 200) {
                    _('> 90');
                    //Target median between checkpoint and opposite of movement.
                    $oppositeAngleToMovement = $this->angleManager::addAngles($currentMovementAngle, 180);
                    _('ANGLE OPPOSITE TO MOVEMENT: ' . $oppositeAngleToMovement);
                    $ecartAngleOppositeCheckpoint = $this->angleManager::diffAngles(
                        $angleToReachCheckpoint,
                        $oppositeAngleToMovement
                    );
                    _('ANGLE BETWEEN OPPOSITE AND CP: ' . $ecartAngleOppositeCheckpoint);
                    $angleToTarget = $this->angleManager::addAngles(
                        $oppositeAngleToMovement,
                        (int) ($ecartAngleOppositeCheckpoint / 1.3)
                    );
                    _('PRE-COMPENSATION: ' . $angleToTarget);

                    $ecartAnglePodMovement = $this->angleManager::diffAngles($currentMovementAngle, $myAngle);
                    if ($ecartAnglePodMovement > 0) {
                        $angleToTarget = $this->angleManager::addAngles($angleToTarget, +1);
                    } else {
                        $angleToTarget = $this->angleManager::addAngles($angleToTarget, -1);
                    }
                    _('POST-COMPENSATION: ' . $angleToTarget);
                }

            } else {
                _('<= 90');
                $angleToTarget = $this->angleManager::addAngles($angleToReachCheckpoint, round($ecartAngleToReachTarget/1.4));
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
            if ($myDist > 6000 && abs($myAngleToCheckpoint) < 15 && $pod->isBoostAvailable()) {
                $thrust = THRUST_BOOST;
            } elseif ($allySpeed > 400 && abs($myAngleToCheckpoint) > 135) {
                $thrust = 0;
            } elseif (abs($myAngleToCheckpoint) > 90) {
                $thrust = 60;
            /*} elseif (abs($myAngleToCheckpoint) > 70) {
                $thrust = 65;*/
            } elseif ($allySpeed < 250) {
                $thrust = 100;
            } else {
                /*if ($myDist < 1500) {
                    $thrust = 30;
                } else*/
                if ($myDist < 1400) {
                    $thrust = 45;
                } elseif ($myDist < 2000) {
                    $thrust = 70;
                } else {
                    $thrust = 100;
                }
            }
        }

        return new Answer(
            $allyCoordinates->getX() + round($destAbscisse),
            $allyCoordinates->getY() + round($destOrdonnee),
            $thrust
        );
    }

    private function getDestinationForKicker(int $podIndex): OneAnswerInterface
    {
        $pod = $this->allies[$podIndex];

        $closestEnemy = null;
        $closestEnemyDistance = null;
        foreach ($this->enemies as $enemy) {
            $distance = $this->distanceManager->measure($pod->getCurrentCoordinates(), $enemy->getCurrentCoordinates());
            if (null === $closestEnemy || $distance < $closestEnemyDistance) {
                $closestEnemy = $enemy;
                $closestEnemyDistance = $distance;
            }
        }

        return new Answer(
            $closestEnemy->getCurrentCoordinates()->getX(),
            $closestEnemy->getCurrentCoordinates()->getY(),
            100
        );
    }

    /**
     * @param PodInterface $pod
     * @param int $turns
     * @param int $power
     *
     * @return Coordinates
     * @deprecated
     */
    public function calculateNextCoordinates(PodInterface $pod, int $turns, int $power = 100): Coordinates
    {
        if (0 >= $turns) {
            throw new InvalidArgumentException('"turns" should be > 0.');
        }

        $pod = clone($pod);

        $movementAngle = $this->getMovementAngle($pod);
        $speed = $this->getSpeed($pod);

        return $this->calculateCoordinates($pod->getCurrentCoordinates(), $movementAngle, $speed, $turns, $power);
    }

    /**
     * @param Coordinates $point
     * @param int $movementAngle
     * @param int $speed
     * @param int $turns
     * @param int $power
     *
     * @return Coordinates
     * @deprecated
     */
    private function calculateCoordinates(Coordinates $point, int $movementAngle, int $speed, int $turns, int $power = 100): Coordinates
    {
        if ($turns === 0) {
            return $point;
        }

        $newSpeed = $speed*SPEED_BREAK_PER_TURN + $power;
        _('new speed: ' . $newSpeed);
        _('Decalage X: ' . cos(deg2rad($movementAngle))*$newSpeed);
        _('Decalage Y: ' . sin(deg2rad($movementAngle))*$newSpeed);

        return $this->calculateCoordinates(new Coordinates(
            round($point->getX() + cos(deg2rad($movementAngle))*$newSpeed),
            round($point->getY() + sin(deg2rad($movementAngle))*$newSpeed)
        ), $movementAngle, round($newSpeed), --$turns, $power);
    }

    /**
     * @param PodInterface $pod
     *
     * @return int
     * @deprecated
     */
    public function getMovementAngle(PodInterface $pod): int
    {
        return $this->calculateMovementAngle($pod);
    }

    /**
     * @param PodInterface $pod
     *
     * @return int|null
     * @deprecated
     */
    private function calculateMovementAngle(PodInterface $pod): int
    {
        try {
            $angle = $this->angleManager::getAngle(
                $pod->getPreviousCoordinates(),
                $pod->getCurrentCoordinates()
            );
        } catch (RuntimeException $e) {
            $angle = 0;
        }

        return $angle;
    }

    /**
     * @param PodInterface $pod
     *
     * @return int
     * @deprecated
     */
    public function getSpeed(PodInterface $pod): int
    {
        return round(sqrt($pod->getSpeedX()**2 + $pod->getSpeedY()**2));
    }
}

fscanf(STDIN, '%d', $lapsNumber);
fscanf(STDIN, '%d', $checkpointsNumber);

/** @var Coordinates $checkpoints */
$checkpoints = [];

for ($i = 0; $i < $checkpointsNumber; ++$i) {
    fscanf(STDIN, '%d %d', $x, $y);
    $checkpoints[] = new Coordinates($x, $y);
}

$distanceManager = new DistanceManager();
$angleManager = new AngleManager();
$checkpointsManager = new CheckpointsManager(...$checkpoints);
$physicsEngine = new DummyPhysicsEngineInterface($angleManager);
$runStrategy = new DummyRunStrategy(
    $checkpointsManager,
    $distanceManager,
    $angleManager,
    $physicsEngine,
    new Pod(), new Pod(), //Allies
    new Pod(), new Pod() //Enemies
);

// game loop
while (true) {
    for ($i = 0; $i < 4; ++$i) {
        fscanf(
            STDIN,
            '%d %d %d %d %d %d',
            $x,
            $y,
            $speedX,
            $speedY,
            $podAngle,
            $nextCheckpointId
        );
        $runStrategy->setPodState($i, new Coordinates($x, $y), $speedX, $speedY, $podAngle, $nextCheckpointId);
    }

    $runStrategy->getDestinations()->echo();
}