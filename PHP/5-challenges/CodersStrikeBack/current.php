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

define('CHECKPOINT_RAYON', 600);

define('POD_TIMEOUT', 100);

define('SOLUTION_GENERATION_NUMBER_SOLUTIONS', 4);
define('SOLUTION_GENERATION_NUMBER_TURNS', 6);
define('SOLUTION_GENERATION_TARGET_FACTOR', 10000);

define('SCORE_VALIDATED_CHECKPOINTS', 1000);
define('SCORE_FINISH', 999999999);
define('SCORE_TIMEOUT', -SCORE_FINISH);
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
        if (null !== $comment) {
            $this->comment = $comment;
        } else {
            $this->comment = $x . ' ' . $y . ' ' . $thrust;
        }
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
    public function __construct();
    public function setState(
        Coordinates $coordinates,
        float $speedX,
        float $speedY,
        float $angle,
        int $nextCheckpointId
    ): self;

    public function useShield(): self;
    public function useBoost(): self;
    public function play(MoveInterface $move): self;
    public function score(): int;
    public function getNextCheckpointId(): int;
    public function getCurrentCoordinates(): Coordinates;
    public function getAngle(): float;
    public function getSpeedX(): float;
    public function getSpeedY(): float;
}

final class Pod implements PodInterface
{
    private $remainingCheckpoints;
    private $timeout = POD_TIMEOUT;

    /** @var Coordinates */
    private $currentCoordinates;
    /** @var float */
    private $speedX;
    /** @var float */
    private $speedY;
    /** @var int */
    private $nextCheckpointId = 1;
    /** @var float */
    private $angle;
    /** @var int */
    private $shieldEffectEnd = 0;
    /** @var bool */
    private $boostAvailable = true;

    public function __construct()
    {
        $this->remainingCheckpoints = RUN_TOTAL_CHECKPOINTS_TO_VALIDATE;
    }

    public function setState(
        Coordinates $coordinates,
        float $speedX,
        float $speedY,
        float $angle,
        int $nextCheckpointId
    ): PodInterface
    {
        $this->currentCoordinates = $coordinates;
        $this->speedX = $speedX;
        $this->speedY = $speedY;
        $this->angle = $angle;

        --$this->timeout;

        if ($nextCheckpointId !== $this->nextCheckpointId) {
            $this->reduceRemainingCheckpoints();
        }

        $this->nextCheckpointId = $nextCheckpointId;

        $this->shieldEffectEnd > 0 && --$this->shieldEffectEnd;

        return $this;
    }

    public function useShield(): PodInterface
    {
        $this->shieldEffectEnd = SHIELD_EFFECT_DURATION;
        return $this;
    }

    public function useBoost(): PodInterface
    {
        $this->boostAvailable = false;
        return $this;
    }

    public function play(MoveInterface $move): PodInterface
    {
        $this->rotate($move->getTarget())
            ->thrust($move->getThrust())
            ->move()
            ->end();

        return $this;
    }

    public function getNextCheckpointId(): int
    {
        return $this->nextCheckpointId;
    }

    public function getCurrentCoordinates(): Coordinates
    {
        return $this->currentCoordinates;
    }

    /**
     * @param Coordinates $target
     *
     * @return Pod
     */
    private function rotate(Coordinates $target): self
    {
        $this->angle = AngleManager::getAngle($this->currentCoordinates, $target);
        return $this;
    }

    private function thrust(string $thrust): self
    {
        /** @var int $effectiveThrust */
        if ($thrust === THRUST_SHIELD) {
            $this->useShield();
            $effectiveThrust = 0;
        } elseif ($this->shieldEffectEnd !== 0) {
            $effectiveThrust = 0;
        } elseif ($thrust === THRUST_BOOST) {
            $this->useBoost();
            $effectiveThrust = 650;
        } else {
            $effectiveThrust = $thrust;
        }

        if ($this->shieldEffectEnd === 0) {
            $this->speedX += cos(deg2rad($this->angle)) * $effectiveThrust;
            $this->speedY += sin(deg2rad($this->angle)) * $effectiveThrust;
        }

        return $this;
    }

    private function move(): self
    {
        $this->currentCoordinates = new Coordinates(
            round($this->currentCoordinates->getX() + $this->speedX),
            round($this->currentCoordinates->getY() + $this->speedY)
        );
        if (DistanceManager::measure(
                $this->currentCoordinates,
                CheckpointsManager::getCheckpoint($this->nextCheckpointId)
            ) <= CHECKPOINT_RAYON) {
            $this->bumpNextCheckpointId();
        }
        return $this;
    }

    private function end(): self
    {
        $this->speedX = (int)($this->speedX * SPEED_BREAK_PER_TURN);
        $this->speedY = (int)($this->speedY * SPEED_BREAK_PER_TURN);
        --$this->timeout;
        return $this;
    }

    private function reduceRemainingCheckpoints(): self
    {
        --$this->remainingCheckpoints;
        $this->timeout = POD_TIMEOUT;
        return $this;
    }

    public function score(): int
    {
        //TODO Add distance to next checkpoint: close gives more points than far.
        if ($this->timeout === 0) {
            return SCORE_TIMEOUT;
        }
        if ($this->remainingCheckpoints === 0) {
            return SCORE_FINISH;
        }
        return (RUN_TOTAL_CHECKPOINTS_TO_VALIDATE - $this->remainingCheckpoints) * SCORE_VALIDATED_CHECKPOINTS;
    }

    private function bumpNextCheckpointId(): self
    {
        $this->nextCheckpointId = CheckpointsManager::getNextCheckpointId($this->nextCheckpointId);
        return $this;
    }

    public function getAngle(): float
    {
        return $this->angle;
    }

    public function getSpeedX(): float
    {
        return $this->speedX;
    }

    public function getSpeedY(): float
    {
        return $this->speedY;
    }
}

final class CheckpointsManager
{
    /** @var Coordinates[] Checkpoints coordinates */
    private static $checkpoints;

    public static function setCheckpoints(Coordinates ...$checkpoints): void
    {
        static::$checkpoints = static::clone(...$checkpoints);
    }

    public static function getCheckpoint(int $id): Coordinates
    {
        return clone(static::$checkpoints[$id]);
    }

    public static function getNextCheckpointId(int $id): int
    {
        return ($id+1)%count(static::$checkpoints);
    }

    public static function getNextCheckpoint(int $id): Coordinates
    {
        return clone(static::$checkpoints[static::getNextCheckpointId($id)]);
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
    public static function getAngle(Coordinates $start, Coordinates $end): float
    {
        $abscisse = $end->getX() - $start->getX();
        $ordonnee = $end->getY() - $start->getY();
        $signeOrdonnee = 0 === $ordonnee ? 1 : $ordonnee / abs($ordonnee);
        $hyp = sqrt(($abscisse ** 2) + ($ordonnee ** 2));
        return self::sanitizeAngle(0 === $hyp ? 0 : rad2deg(acos($abscisse / $hyp)) * $signeOrdonnee);
    }

    /**
     * Format an absolute angle to a signed angle
     *
     * @param float $angle
     *
     * @return float
     */
    public static function getSignedAngle(float $angle): float
    {
        return ($angle > 180) ? $angle - 360 : $angle;
    }

    /**
     * Angle should be: 0 <= $angle < 360
     *
     * @param int $angle
     *
     * @return int
     */
    public static function sanitizeAngle(float $angle): float
    {
        while ($angle > 360) {
            $angle -= 360;
        }
        while ($angle < 0) {
            $angle += 360;
        }
        return $angle;
    }

    public static function addAngles(float $start, float $angle): float
    {
        return self::sanitizeAngle($start + $angle);
    }

    public static function diffAngles(float $end, int $start): float
    {
        return self::sanitizeAngle($end - $start);
    }
}

final class DistanceManager
{
    public static function measure(Coordinates $p1, Coordinates $p2): int
    {
        $abscisse = $p1->getX() - $p2->getX();
        $ordonnee = $p1->getY() - $p2->getY();
        return round(sqrt(($abscisse ** 2) + ($ordonnee ** 2)));
    }
}

interface MoveInterface
{
    public function __construct(Coordinates $target, string $thrust);
    public function getTarget(): Coordinates;
    public function getThrust(): string;
}

final class Move implements MoveInterface
{
    /** @var Coordinates */
    private $target;
    /** @var string */
    private $thrust;

    public function __construct(Coordinates $target, string $thrust)
    {
        $this->target = $target;
        $this->thrust = $thrust;
    }

    public function getTarget(): Coordinates
    {
        return $this->target;
    }

    public function getThrust(): string
    {
        return $this->thrust;
    }
}

interface SolutionInterface
{
    public function addMove(MoveInterface ...$moves): self;

    /**
     * @return MoveInterface[]
     */
    public function shiftMove(): array;
    public function setPods(PodInterface ...$pods): self;
    public function play(): self;
    public function score(): int;
}

final class Solution implements SolutionInterface
{
    use PodsManagerTrait {
        setPods as _setPods;
    }

    /** @var MoveInterface[][] */
    private $moves = [];

    public function addMove(MoveInterface ...$moves): SolutionInterface
    {
        $this->moves[] = $moves;
        foreach ($moves as $id => $move) {
            $this->allies[$id]->play($move);
        }
        return $this;
    }

    /**
     * @return MoveInterface[]
     */
    public function shiftMove(): array
    {
        return array_shift($this->moves);
    }

    public function setPods(PodInterface ...$pods): SolutionInterface
    {
        return $this->_setPods(...$this->clonePods(...$pods));
    }

    public function play(): SolutionInterface
    {
        foreach ($this->moves as $moves) {
            foreach ($moves as $podIndex => $move) {
                $this->allies[$podIndex]->play($move);
            }
        }

        return $this;
    }

    public function score(): int
    {
        $alliesScores = array_map(static function (PodInterface $pod) { return $pod->score(); }, $this->allies);
        $enemiesScores = array_map(static function (PodInterface $pod) { return $pod->score(); }, $this->enemies);

        return max($alliesScores) - max($enemiesScores);
    }
}

trait PodsManagerTrait
{
    /** @var PodInterface[] */
    private $allies = [];
    /** @var PodInterface[] */
    private $enemies = [];

    private function setPods(PodInterface ...$pods): self
    {
        $nbPodsByTeam = count($pods)/2;
        $this->allies = array_slice($pods, 0, $nbPodsByTeam, false);
        $this->enemies = array_slice($pods, $nbPodsByTeam, $nbPodsByTeam, false);
        return $this;
    }

    private function getPods(): array
    {
        return array_merge($this->allies, $this->enemies);
    }

    /**
     * @param PodInterface[] $pods
     *
     * @return PodInterface[]
     */
    private function clonePods(PodInterface ...$pods): array
    {
        return array_map(
            static function (PodInterface $pod) {
                return clone($pod);
            },
            array_merge($pods)
        );
    }
}

interface SolutionGeneratorInterface
{
    public function setPods(PodInterface ...$pods): SolutionGeneratorInterface;
    public function generate(): SolutionInterface;
    public function fillSolution(SolutionInterface $solution): SolutionInterface;
}

final class DummySolutionGenerator implements SolutionGeneratorInterface
{
    use PodsManagerTrait {
        setPods as _setPods;
    }

    public function setPods(PodInterface ...$pods): SolutionGeneratorInterface
    {
        return $this->_setPods(...$pods);
    }

    public function generate(): SolutionInterface
    {
        $solution = (new Solution())->setPods(...$this->getPods());
        return $this->generateTurns($solution, SOLUTION_GENERATION_NUMBER_TURNS);
    }

    public function fillSolution(SolutionInterface $solution): SolutionInterface
    {
        $solution->setPods(...$this->clonePods(...$this->getPods()))->play();
        return $this->generateTurns($solution, 1);
    }

    private function generateTurns(SolutionInterface $solution, int $numberTurnsToGenerate): SolutionInterface
    {
        if (0 === $numberTurnsToGenerate) {
            return $solution;
        }

        $move0 = $this->generateMove($this->allies[0]);
        $move1 = $this->generateMove($this->allies[1]);
        $solution->addMove($move0, $move1);

        return $this->generateTurns($solution, --$numberTurnsToGenerate);
    }

    private function angleLimiter(float $angle): float
    {
        if ($angle > MAX_ROTATION) {
            return MAX_ROTATION;
        }

        if ($angle < -MAX_ROTATION) {
            return -MAX_ROTATION;
        }

        return $angle;
    }

    private function getMovementAngle(PodInterface $pod): float
    {
        $hyp = sqrt($pod->getSpeedX()**2 + $pod->getSpeedY()**2);
        if ($hyp === 0.0) {
            return $pod->getSpeedX() < 0 ? 180 : 0;
        }
        $angle = rad2deg(acos($pod->getSpeedX() / $hyp));
        return rad2deg(asin($pod->getSpeedY() / $hyp)) > 1 ? $angle : -$angle;
    }

    /**
     * @param PodInterface $pod
     *
     * @return MoveInterface
     * @throws Exception
     */
    private function generateMove(PodInterface $pod): MoveInterface
    {
        $nextCP = CheckpointsManager::getCheckpoint($pod->getNextCheckpointId());
        $angleToTarget = AngleManager::getSignedAngle(AngleManager::getAngle($pod->getCurrentCoordinates(), $nextCP));
        _('$angleToTarget: ' . $angleToTarget);
        $distToTarget = DistanceManager::measure($pod->getCurrentCoordinates(), $nextCP);
        _('$distToTarget: ' . $distToTarget);

        _('My Angle: ' . AngleManager::getSignedAngle($pod->getAngle()));
        if ($pod->getAngle() === -1.0) {
            $rotation = $angleToTarget;
            $diffAngleToTarget = 0;
        } else {
            $diffAngleToTarget = AngleManager::getSignedAngle(AngleManager::diffAngles($angleToTarget, $pod->getAngle()));
            _('$diffAngleToTarget: ' . $diffAngleToTarget);

            $movementAngle = AngleManager::getSignedAngle($this->getMovementAngle($pod));
            _('$movementAngle: ' . $movementAngle);

            $diffMovementTargetAngles = AngleManager::getSignedAngle(AngleManager::diffAngles($angleToTarget, $movementAngle));
            _('$diffMovementTargetAngles: ' . $diffMovementTargetAngles);
            if (abs($diffMovementTargetAngles) > 45) {
                _('Premier if');
                $rotation = $diffAngleToTarget > 0 ? MAX_ROTATION : -MAX_ROTATION;
            } elseif (abs($diffAngleToTarget) <= MAX_ROTATION) {
                _('Deuxième if');
                $rotation = $diffMovementTargetAngles;
                _('rotation temp: ' . $rotation);
                $rotation = random_int($this->angleLimiter($rotation-5), $this->angleLimiter($rotation+5));
            } else {
                _('Troisième if');
                $rotation = ($diffAngleToTarget >= 0) ? MAX_ROTATION : -MAX_ROTATION;
            }
        }
        _('Rotation: ' . $rotation);

        $newAngle = AngleManager::addAngles($pod->getAngle(), $rotation);
        _('new angle: ' . $newAngle);
        $target = new Coordinates(
            $pod->getCurrentCoordinates()->getX() + cos(deg2rad($newAngle)) * SOLUTION_GENERATION_TARGET_FACTOR,
            $pod->getCurrentCoordinates()->getY() + sin(deg2rad($newAngle)) * SOLUTION_GENERATION_TARGET_FACTOR,
        );

        if ($distToTarget > 6000 && abs($diffAngleToTarget) < 15 /*&& $pod->isBoostAvailable()*/) {
            $thrust = THRUST_BOOST;
        } elseif (abs($diffAngleToTarget) > 135) {
            $thrust = random_int(0, 30);
        } elseif (abs($diffAngleToTarget) > 90) {
            $thrust = random_int(50, 70);
        } else {
            if ($distToTarget < 1400) {
                $thrust = random_int(40, 100);
            } elseif ($distToTarget < 2000) {
                $thrust = random_int(60, 100);
            } else {
                $thrust = 100;
            }
        }

        _('Thrust: ' . $thrust);

        return new Move($target, $thrust);
    }
}

interface SolutionManagerInterface
{
    public function __construct(SolutionGeneratorInterface $sg);

    /**
     * Get next move for each ally pod.
     * @return AnswerInterface
     */
    public function getNextMoves(): AnswerInterface;
}

final class DummySolutionManager implements SolutionManagerInterface
{
    use PodsManagerTrait;

    /** @var SolutionGeneratorInterface */
    private $solutionGenerator;
    /** @var Solution[] */
    private $solutions = [];

    public function __construct(SolutionGeneratorInterface $sg, PodInterface ...$pods)
    {
        $this->solutionGenerator = $sg;
        $this->setPods(...$pods);
    }

    public function getNextMoves(): AnswerInterface
    {
        $this->generate();
        $this->solutions = [$this->getBestSolution()];
        $moves = $this->solutions[0]->shiftMove();
        $globalAnswer = new AnswersComposite();
        foreach ($this->allies as $allyIndex => $ally) {
            $ally = clone($ally);
            $ally->play($moves[$allyIndex]);
            $globalAnswer->addAnswer(
                new Answer(
                    $moves[$allyIndex]->getTarget()->getX(),
                    $moves[$allyIndex]->getTarget()->getY(),
                    $moves[$allyIndex]->getThrust()
                )
            );
        }
        return $globalAnswer;
    }

    private function generate(): SolutionManagerInterface
    {
        $this->solutionGenerator->setPods(...$this->clonePods(...$this->getPods()));
        //!empty($this->solutions) && $this->solutionGenerator->fillSolution($this->solutions[0]);
        $this->solutions = [];

        while (count($this->solutions) < SOLUTION_GENERATION_NUMBER_SOLUTIONS) {
            $this->addSolution($this->solutionGenerator->generate());
        }

        return $this;
    }

    private function addSolution(SolutionInterface $solution): SolutionManagerInterface
    {
        $this->solutions[] = $solution;
        return $this;
    }

    /**
     * @return Solution
     */
    private function getBestSolution(): Solution
    {
        $solutionsScores = array_map(
            static function (Solution $solution) {
                return $solution->play()->score();
            },
            $this->solutions
        );
        arsort($solutionsScores);
        return $this->solutions[key($solutionsScores)];
    }
}

fscanf(STDIN, '%d', $lapsNumber);
define('RUN_NUMBER_LAPS', $lapsNumber);
fscanf(STDIN, '%d', $checkpointsNumber);
define('RUN_NUMBER_CHECKPOINT_BY_LAP', $checkpointsNumber);
define('RUN_TOTAL_CHECKPOINTS_TO_VALIDATE', RUN_NUMBER_LAPS * RUN_NUMBER_CHECKPOINT_BY_LAP);

/** @var Coordinates[] $checkpoints */
$checkpoints = [];

for ($i = 0; $i < $checkpointsNumber; ++$i) {
    /** @var int $x Checkpoint X */
    /** @var int $y Checkpoint Y */
    fscanf(STDIN, '%d %d', $x, $y);
    $checkpoints[] = new Coordinates($x, $y);
}

CheckpointsManager::setCheckpoints(...$checkpoints);
/** @var PodInterface[] $pods */
$pods = [
    //Allies
    new Pod(),
    new Pod(),
    //Enemies
    new Pod(),
    new Pod(),
];
$solutionManager = new DummySolutionManager(new DummySolutionGenerator(), ...$pods);

// game loop
$turn = 0;
while (true) {
    for ($i = 0; $i < 4; ++$i) {
        /** @var int $speedX Pod X speed */
        /** @var int $speedY Pod Y speed */
        /** @var int $podAngle Pod absolute angle */
        /** @var int $nextCheckpointId Pod next checkpoint ID */
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
        $pods[$i]->setState(new Coordinates($x, $y), $speedX, $speedY, $podAngle, $nextCheckpointId);
    }

    $solutionManager->getNextMoves()->echo();
}
