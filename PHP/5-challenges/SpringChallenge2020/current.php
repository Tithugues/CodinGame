<?php
/**
 * Grab the pellets as fast as you can!
 **/

namespace PacMan\Bronze;

use Exception;

define('DEBUG', true);
define('PELLET_DEFAULT_SIZE', 1);
define('PELLET_SUPER_SIZE', 10);

function _($var, bool $force = false)
{
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

interface Stringable
{
    public function __toString(): string;
}

interface Point
{
    public function getPosition(): array;
}

interface PelletInterface extends Point, Stringable
{
    public function getSize(): int;
}

interface PelletsManagerInterface
{
    public function resetPellets(): PelletsManagerInterface;

    /**
     * @param PelletInterface $pellet
     *
     * @return PelletsManagerInterface
     */
    public function addPellet(PelletInterface $pellet): PelletsManagerInterface;

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
}

interface PacmanInterface extends Point, Stringable
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
}

interface TargetFinderInterface
{
    public function getTargets(): array;
}

class Pellet implements PelletInterface
{
    private $x;
    private $y;
    private $size;

    public function __construct(int $x, int $y, int $size)
    {
        $this->x = $x;
        $this->y = $y;
        $this->size = $size;
    }

    public function getPosition(): array
    {
        return [
            $this->x,
            $this->y,
        ];
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function __toString(): string
    {
        return $this->x . '/' . $this->y;
    }
}

class PelletsManager implements PelletsManagerInterface
{
    private $smallPellets = [];
    private $superPellets = [];

    public function resetPellets(): PelletsManagerInterface
    {
        $this->smallPellets = [];
        $this->superPellets = [];
        return $this;
    }

    public function addPellet(PelletInterface $pellet): PelletsManagerInterface
    {
        if ($pellet->getSize() === PELLET_DEFAULT_SIZE) {
            $this->smallPellets[] = $pellet;
        } else {
            $this->superPellets[] = $pellet;
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
}

class PelletNotFoundException extends Exception
{
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
     * TargetFinder constructor.
     *
     * @param MapInterface $map
     * @param PelletsManagerInterface $pelletsManager
     * @param PacmenManagerInterface $pacmenManager
     */
    public function __construct(
        MapInterface $map,
        PelletsManagerInterface $pelletsManager,
        PacmenManagerInterface $pacmenManager
    )
    {
        $this->map = $map;
        $this->pelletsManager = $pelletsManager;
        $this->pacmenManager = $pacmenManager;
    }

    public function getTargets(): array
    {
        // Firstly send the closest pacmen to super pellets
        // Secondly send remaining pacmen to closest pellets
        $targets = [];
        $freePacmen = $this->pacmenManager->getMyPacmen();
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
                $closestPellet = $this->getClosestPellet($pacman, $freePellets);
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

    private function getSquareDistance(Point $a, Point $b): float
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
$targetFinder = new TargetFinder($map, $pelletManager, $pacmenManager);

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
        $pelletManager->addPellet(new Pellet($x, $y, $value));
    }

    // Write an action using echo(). DON'T FORGET THE TRAILING \n
    // To debug: error_log(var_export($var, true)); (equivalent to var_dump)
    echo implode('|', $targetFinder->getTargets()) . PHP_EOL;
}
