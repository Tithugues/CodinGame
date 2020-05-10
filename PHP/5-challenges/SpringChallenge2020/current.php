<?php
/**
 * Grab the pellets as fast as you can!
 **/

namespace PacMan\Bronze;

use Exception;

define('DEBUG', true);
define('PELLET_DEFAULT_SIZE', 1);
define('PELLET_SUPER_SIZE', 10);
define('MAP_GROUND', ' ');
define('MAP_WALL', '#');

function _($var, bool $force = false)
{
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

interface DistanceCalculatorInterface
{
    public function getDistance(PointInterface $a, PointInterface $b);
}

interface Stringable
{
    public function __toString(): string;
}

interface PointInterface
{
    public function getPosition(): array;
}

interface PelletInterface extends PointInterface, Stringable
{
    public function getSize(): int;

    /**
     * Number of turns this pellet has been seen.
     * @return int
     */
    public function seen(): int;

    /**
     * Increase number of turns this pellet has been seen.
     * @return int
     */
    public function increaseSeen(): int;

    /**
     * Reset number of turns this pellet has been seen.
     * @return PelletInterface
     */
    public function justSeen(): PelletInterface;
}

interface PelletsManagerInterface
{
    public function resetPellets(): PelletsManagerInterface;

    /**
     * @param int $x
     * @param int $y
     * @param int $size
     *
     * @return PelletsManagerInterface
     */
    public function addPellet(int $x, int $y, int $size): PelletsManagerInterface;

    /**
     * @return PelletInterface[]
     */
    public function getSmallPellets(): array;

    /**
     * @return PelletInterface[]
     */
    public function getSuperPellets(): array;

    /**
     * @return PelletInterface[]
     */
    public function getPellets(): array;

    /**
     * See case (so pellet can be marked as seen)
     *
     * @param int $x
     * @param int $y
     *
     * @return bool
     */
    public function justSeen(int $x, int $y): bool;

    public function cleanVisibleCases(array $visibleCases): void;
}

interface PacmanInterface extends PointInterface, Stringable
{
    public function getId(): int;

    public function isMine(): bool;
}

interface PacmenManagerInterface
{
    public function resetPacmen(): PacmenManagerInterface;

    /**
     * @param PacmanInterface $pacman
     *
     * @return PacmenManagerInterface
     */
    public function addPacman(PacmanInterface $pacman): PacmenManagerInterface;

    /**
     * @return PacmanInterface[]
     */
    public function getPacmen(): array;

    /**
     * @return PacmanInterface[]
     */
    public function getMyPacmen(): array;
}

interface MapInterface
{
    public function addRow(string $row): MapInterface;

    public function getWidth(): int;

    public function getHeight(): int;

    /**
     * Returns coordinates of cases visible by a pacman.
     *
     * @param PointInterface $point
     *
     * @return array[]
     */
    public function getVisibleCases(PointInterface $point): array;

    /**
     * Returns grounds connected to given case
     *
     * @param int[] $coordinates
     *
     * @return array
     */
    public function connectedGroundCases(array $coordinates): array;
}

interface TargetFinderInterface
{
    public function getTargets(): array;
}

class SquareDistanceCalculator implements DistanceCalculatorInterface
{
    public function getDistance(PointInterface $a, PointInterface $b)
    {
        $aCoordinates = $a->getPosition();
        $bCoordinates = $b->getPosition();
        return ($aCoordinates[0] - $bCoordinates[0]) ** 2 + ($aCoordinates[1] - $bCoordinates[1]) ** 2;
    }
}

class GroundDistanceCalculator implements DistanceCalculatorInterface
{
    /**
     * @var MapInterface
     */
    private $map;

    /**
     * Distances between points
     * @var array
     *
     * Format:
     * starting point => [arrival point => distance]
     */
    private $distances = [];

    public function __construct(MapInterface $map)
    {
        $this->map = $map;
    }

    public function getDistance(PointInterface $a, PointInterface $b)
    {
        $startingPointKey = implode('/', $a->getPosition());
        $arrivalPointKey = implode('/', $b->getPosition());

        if (! isset($this->distances[$startingPointKey])) {
            $this->distances[$startingPointKey] = [$startingPointKey => 0];
        }
        $toVisit = $this->distances[$startingPointKey];
        while (!isset($this->distances[$startingPointKey][$arrivalPointKey])) {
            $currentPositionKey = key($toVisit);
            $currentPositionDistance = array_shift($toVisit);
            $connectedGroundCases = $this->map->connectedGroundCases(explode('/', $currentPositionKey));
            // Remove all cases already visited
            $connectedGroundCases = array_diff_key($connectedGroundCases, $this->distances[$startingPointKey]);
            // Or planned to be visited
            $connectedGroundCases = array_diff_key($connectedGroundCases, $toVisit);
            $toVisit += array_combine(
                array_keys($connectedGroundCases),
                array_fill(0, count($connectedGroundCases), $currentPositionDistance + 1)
            );
            asort($toVisit);
            reset($toVisit); // Needed?
            $this->distances[$startingPointKey] += $toVisit;
        }

        return $this->distances[$startingPointKey][$arrivalPointKey];
    }

}

abstract class AbstractPellet implements PelletInterface
{
    private $x;
    private $y;
    private $seen = 0;

    public function __construct(int $x, int $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    public function getPosition(): array
    {
        return [
            $this->x,
            $this->y,
        ];
    }

    public function increaseSeen(): int
    {
        return ++$this->seen;
    }

    public function seen(): int
    {
        return $this->seen;
    }

    public function justSeen(): PelletInterface
    {
        $this->seen = 0;
        return $this;
    }

    public function __toString(): string
    {
        return $this->x . '/' . $this->y;
    }
}

class SmallPellet extends AbstractPellet
{
    public function getSize(): int
    {
        return PELLET_DEFAULT_SIZE;
    }
}

class SuperPellet extends AbstractPellet
{
    public function getSize(): int
    {
        return PELLET_SUPER_SIZE;
    }
}

class PelletsManager implements PelletsManagerInterface
{
    /** @var SmallPellet[] */
    private $smallPellets = [];
    /** @var SuperPellet[] */
    private $superPellets = [];

    private static function generatePelletKey($x, $y)
    {
        return $x . '/' . $y;
    }

    public function resetPellets(): PelletsManagerInterface
    {
        // SmallPellets are not always visible, so keep track of them.
        array_walk(
            $this->smallPellets,
            static function (PelletInterface $pellet) {
                $pellet->increaseSeen();
            }
        );
        // SuperPellets are always visible, so reset them each time.
        $this->superPellets = [];
        return $this;
    }

    public function addPellet(int $x, int $y, int $size): PelletsManagerInterface
    {
        if ($size === PELLET_SUPER_SIZE) {
            $this->superPellets[] = new SuperPellet($x, $y);
            return $this;
        }

        $pelletKey = $this::generatePelletKey($x, $y);
        if (array_key_exists($pelletKey, $this->smallPellets)) {
            $this->smallPellets[$pelletKey]->justSeen();
        } else {
            $this->smallPellets[$pelletKey] = new SmallPellet($x, $y);
        }
        return $this;
    }

    public function getSmallPellets(): array
    {
        return $this->smallPellets;
    }

    public function getSuperPellets(): array
    {
        return $this->superPellets;
    }

    public function getPellets(): array
    {
        return array_merge($this->superPellets, $this->smallPellets);
    }

    public function justSeen(int $x, int $y): bool
    {
        $pelletKey = $this::generatePelletKey($x, $y);
        if (! array_key_exists($pelletKey, $this->smallPellets)) {
            return false;
        }

        $this->smallPellets[$pelletKey]->justSeen();
        return true;
    }

    public function cleanVisibleCases(array $visibleCases): void
    {
        foreach ($visibleCases as $visibleCase) {
            $pelletKey = $this::generatePelletKey(...$visibleCase);
            if (! array_key_exists($pelletKey, $this->smallPellets)) {
                continue;
            }
            if ($this->smallPellets[$pelletKey]->seen() !== 0) {
                unset($this->smallPellets[$pelletKey]);
            }
        }
    }
}

class Pacman implements PacmanInterface
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var bool
     */
    private $mine;
    /**
     * @var int
     */
    private $x;
    /**
     * @var int
     */
    private $y;

    public function __construct(int $id, bool $mine, int $x, int $y)
    {
        $this->id = $id;
        $this->mine = $mine;
        $this->x = $x;
        $this->y = $y;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isMine(): bool
    {
        return $this->mine;
    }

    public function getPosition(): array
    {
        return [
            $this->x,
            $this->y,
        ];
    }

    public function __toString(): string
    {
        return (string)$this->getId();
    }
}

class PacmenManager implements PacmenManagerInterface
{
    /**
     * @var PacmanInterface[]
     */
    private $pacmen;

    public function resetPacmen(): PacmenManagerInterface
    {
        $this->pacmen = [];
        return $this;
    }

    public function addPacman(PacmanInterface $pacman): PacmenManagerInterface
    {
        $this->pacmen[] = $pacman;
        return $this;
    }

    public function getPacmen(): array
    {
        return $this->pacmen;
    }

    public function getMyPacmen(): array
    {
        return array_filter(
            $this->pacmen,
            static function (PacmanInterface $pacman) {
                return $pacman->isMine();
            }
        );
    }

}

class Map implements MapInterface
{
    private $width;
    private $height;
    private $row = '';

    public function __construct(int $width, int $height)
    {
        $this->width = $width;
        $this->height = $height;
    }

    public function addRow(string $row): MapInterface
    {
        $this->row .= $row;
        return $this;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getVisibleCases(PointInterface $point): array
    {
        // TODO: manage ground on edges, to loop on the other side.
        $visibleCases = [implode('/', $point->getPosition()) => $point->getPosition()];
        [$x, $y] = $visibleCases[implode('/', $point->getPosition())];

        // Check to the left
        $i = $x;
        while (--$i >= 0 && $this->row[$y * $this->getWidth() + $i] === MAP_GROUND) {
            $visibleCases[$i . '/' . $y] = [
                $i,
                $y,
            ];
        }

        // Check to the right
        $i = $x;
        while (++$i < $this->getWidth() && $this->row[$y * $this->getWidth() + $i] === MAP_GROUND) {
            $visibleCases[$i . '/' . $y] = [
                $i,
                $y,
            ];
        }

        // Check to the top
        $i = $y;
        while (--$i > 0 && $this->row[$i * $this->getWidth() + $x] === MAP_GROUND) {
            $visibleCases[$x . '/' . $i] = [
                $x,
                $i,
            ];
        }

        // Check to the bottom
        $i = $y;
        while (++$i > $this->getHeight() && $this->row[$i * $this->getWidth() + $x] === MAP_GROUND) {
            $visibleCases[$x . '/' . $i] = [
                $x,
                $i,
            ];
        }

        return $visibleCases;
    }

    public function connectedGroundCases(array $coordinates): array
    {
        // TODO: manage ground on edges, to loop on the other side.
        $connected = [];
        [$x, $y] = $coordinates;

        // Check to the left
        if (($x - 1) >= 0 && $this->row[$y * $this->getWidth() + $x - 1] === MAP_GROUND) {
            $connected[($x - 1) . '/' . $y] = [
                $x - 1,
                $y,
            ];
        }

        // Check to the right
        if (($x + 1) < $this->getWidth() && $this->row[$y * $this->getWidth() + $x + 1] === MAP_GROUND) {
            $connected[($x + 1) . '/' . $y] = [
                $x + 1,
                $y,
            ];
        }

        // Check to the top
        if (($y - 1) >= 0 && $this->row[($y - 1) * $this->getWidth() + $x] === MAP_GROUND) {
            $connected[$x . '/' . ($y - 1)] = [
                $x,
                $y - 1,
            ];
        }

        // Check to the bottom
        if (($y + 1) < $this->getHeight() && $this->row[($y + 1) * $this->getWidth() + $x] === MAP_GROUND) {
            $connected[$x . '/' . ($y + 1)] = [
                $x,
                $y + 1,
            ];
        }

        return $connected;
    }

}

class PelletNotFoundException extends Exception
{
}

interface SmallPelletsTargetterInterface
{
    public function getTarget(array $pellets, PacmanInterface $pacman): PelletInterface;
}

class SmallPelletsTargetter implements SmallPelletsTargetterInterface
{
    /** @var DistanceCalculatorInterface */
    private $distanceCalculator;

    public function __construct(DistanceCalculatorInterface $distanceCalculator)
    {
        $this->distanceCalculator = $distanceCalculator;
    }

    /**
     * @param PelletInterface[] $pellets
     * @param PacmanInterface $pacman
     *
     * @return PelletInterface
     */
    public function getTarget(array $pellets, PacmanInterface $pacman): PelletInterface
    {
        usort(
            $pellets,
            function (PelletInterface $a, PelletInterface $b) use ($pacman) {
                // +1 not to multiply by 0
                $pointsA = $this->distanceCalculator->getDistance($pacman, $a) * ($a->seen() + 1);
                $pointsB = $this->distanceCalculator->getDistance($pacman, $b) * ($b->seen() + 1);
                //_('A: ' . $a . ' seen ' . $a->seen());
                //_('B: ' . $b . ' seen ' . $b->seen());
                if ($pointsA === $pointsB) {
                    return 0;
                }

                if ($pointsA < $pointsB) {
                    return -1;
                }

                return 1;
            }
        );
        return reset($pellets);
    }
}

class TargetFinder implements TargetFinderInterface
{
    /**
     * @var MapInterface
     */
    private $map;
    /**
     * @var PelletsManagerInterface
     */
    private $pelletsManager;
    /**
     * @var PacmenManagerInterface
     */
    private $pacmenManager;
    /**
     * @var SmallPelletsTargetterInterface
     */
    private $smallPelletsSorter;

    /**
     * TargetFinder constructor.
     *
     * @param MapInterface $map
     * @param PelletsManagerInterface $pelletsManager
     * @param PacmenManagerInterface $pacmenManager
     * @param SmallPelletsTargetterInterface $smallPelletsSorter
     */
    public function __construct(
        MapInterface $map,
        PelletsManagerInterface $pelletsManager,
        PacmenManagerInterface $pacmenManager,
        SmallPelletsTargetterInterface $smallPelletsSorter
    )
    {
        $this->map = $map;
        $this->pelletsManager = $pelletsManager;
        $this->pacmenManager = $pacmenManager;
        $this->smallPelletsSorter = $smallPelletsSorter;
    }

    public function getTargets(): array
    {
        // Firstly, check visible cases and remove unexisting pellets
        // Secondly, send the closest pacmen to super pellets
        // Thirdly, send remaining pacmen to closest pellets
        $targets = [];
        $freePacmen = $this->pacmenManager->getMyPacmen();

        $visibleCases = [];
        foreach ($freePacmen as $pacman) {
            $visibleCases += $this->map->getVisibleCases($pacman);
        }
        $this->pelletsManager->cleanVisibleCases($visibleCases);

        $superPellets = $this->pelletsManager->getSuperPellets();
        foreach ($superPellets as $superPellet) {
            if ($freePacmen === []) {
                break;
            }
            $closestPacman = $this->getClosestPacman($superPellet, $freePacmen);
            $freePacmen = array_diff($freePacmen, [$closestPacman]);
            $targets[] = 'MOVE ' . $closestPacman->getId() . ' ' . implode(
                    ' ',
                    $superPellet->getPosition()
                );
        }

        $freePellets = $this->pelletsManager->getSmallPellets();
        foreach ($freePacmen as $pacman) {
            try {
                $closestPellet = $this->smallPelletsSorter->getTarget($freePellets, $pacman);
                $freePellets = array_diff($freePellets, [$closestPellet]);
                $targets[] = 'MOVE ' . $pacman->getId() . ' ' . implode(' ', $closestPellet->getPosition());
            } catch (PelletNotFoundException $e) {
                $targets[] = 'MOVE ' . $pacman->getId() . ' ' . implode(' ', $this->getRandomPosition());
            }
        }

        return $targets;
    }

    /**
     * @param PacmanInterface $pacman
     * @param array $pellets
     *
     * @return PelletInterface
     * @throws PelletNotFoundException
     */
    private function getClosestPellet(PacmanInterface $pacman, array $pellets): PelletInterface
    {
        return $this->getClosest($pacman, $pellets);
    }

    /**
     * @param PacmanInterface $pacman
     * @param array $pellets
     *
     * @return PelletInterface
     * @throws PelletNotFoundException
     */
    private function getClosest(PacmanInterface $pacman, array $pellets): PelletInterface
    {
        if (count($pellets) === 0) {
            throw new PelletNotFoundException();
        }
        $closestPellet = array_shift($pellets);
        $closestPelletDistance = $this->getSquareDistance($pacman, $closestPellet);
        //TODO: Loop and find closest
        foreach ($pellets as $pellet) {
            if (($currentPelletDistance = $this->getSquareDistance($pacman, $pellet)) < $closestPelletDistance) {
                $closestPellet = $pellet;
                $closestPelletDistance = $currentPelletDistance;
            }
        }
        return $closestPellet;
    }

    private function getSquareDistance(PointInterface $a, PointInterface $b): float
    {
        $aCoordinates = $a->getPosition();
        $bCoordinates = $b->getPosition();
        return ($aCoordinates[0] - $bCoordinates[0]) ** 2 + ($aCoordinates[1] - $bCoordinates[1]) ** 2;
    }

    /**
     * @param PelletInterface $superPellet
     * @param PacmanInterface[] $freePacmen
     *
     * @return PacmanInterface
     */
    private function getClosestPacman(PelletInterface $superPellet, array $freePacmen): PacmanInterface
    {
        $closestPacman = array_shift($freePacmen);
        $closestPacmanDistance = $this->getSquareDistance($closestPacman, $superPellet);
        foreach ($freePacmen as $freePacman) {
            if (($currentPacmanDistance = $this->getSquareDistance($freePacman, $superPellet))
                < $closestPacmanDistance) {
                $closestPacman = $freePacman;
                $closestPacmanDistance = $currentPacmanDistance;
            }
        }
        return $closestPacman;
    }

    private function getRandomPosition(): array
    {
        return [
            random_int(0, $this->map->getWidth() - 1),
            random_int(0, $this->map->getHeight() - 1),
        ];
    }
}

// $width: size of the grid
// $height: top left corner is (x=0, y=0)
fscanf(STDIN, "%d %d", $width, $height);
$map = new Map($width, $height);

for ($i = 0; $i < $height; $i++) {
    $row = stream_get_line(STDIN, $width + 1, "\n");// one line of the grid: space " " is floor, pound "#" is wall
    $map->addRow($row);
}

$pelletManager = new PelletsManager();
$pacmenManager = new PacmenManager();
$distanceCalculator = new GroundDistanceCalculator($map);
$smallPelletsTargetter = new SmallPelletsTargetter($distanceCalculator);
$targetFinder = new TargetFinder($map, $pelletManager, $pacmenManager, $smallPelletsTargetter);

// game loop
while (true) {
    fscanf(STDIN, "%d %d", $myScore, $opponentScore);
    // $visiblePacCount: all your pacs and enemy pacs in sight
    fscanf(STDIN, "%d", $visiblePacCount);
    $pacmenManager->resetPacmen();
    for ($i = 0; $i < $visiblePacCount; $i++) {
        // $pacId: pac number (unique within a team)
        // $mine: true if this pac is yours
        // $x: position in the grid
        // $y: position in the grid
        // $typeId: unused in wood leagues
        // $speedTurnsLeft: unused in wood leagues
        // $abilityCooldown: unused in wood leagues
        fscanf(STDIN, "%d %d %d %d %s %d %d", $pacId, $mine, $x, $y, $typeId, $speedTurnsLeft, $abilityCooldown);
        $pacmenManager->addPacman(new Pacman($pacId, (bool)$mine, $x, $y));
    }

    // $visiblePelletCount: all pellets in sight
    fscanf(STDIN, "%d", $visiblePelletCount);
    $pelletManager->resetPellets();
    for ($i = 0; $i < $visiblePelletCount; $i++) {
        // $value: amount of points this pellet is worth
        fscanf(STDIN, "%d %d %d", $x, $y, $value);
        $pelletManager->addPellet($x, $y, $value);
    }

    // Write an action using echo(). DON'T FORGET THE TRAILING \n
    // To debug: error_log(var_export($var, true)); (equivalent to var_dump)
    echo implode('|', $targetFinder->getTargets()) . PHP_EOL;
}
