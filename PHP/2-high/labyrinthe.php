<?php

define('DEBUG', true);

function _($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Map
{
    protected $cells = [];
    protected $start = false;
    protected $end = false;

    public function getStart()
    {
        return $this->start;
    }

    protected function setStart($row, $column)
    {
        $this->start = [$row, $column];
    }

    public function getEnd()
    {
        return $this->end;
    }

    protected function setEnd($row, $column)
    {
        $this->end = [$row, $column];
    }

    public function setRow($i, $row) {
        $this->cells[$i] = $row;

        if ($this->getStart() === false && ($startCol = strpos($row, 'T')) !== false) {
            $this->setStart($i, $startCol);
        }

        if ($this->getEnd() === false && ($endCol = strpos($row, 'C')) !== false) {
            $this->setEnd($i, $endCol);
        }
    }

    public function getCells()
    {
        return $this->cells;
    }

    public function getHeight()
    {
        return count($this->cells);
    }

    public function getWidth()
    {
        return strlen($this->cells[0]);
    }
}

interface MoveInterface
{
    public function __construct($nbOfRounds, Map $map);

    public function setKirkPosition($row, $column);

    public function newTurn();
    public function getDirection();
}

class DummyMove implements MoveInterface
{
    const TRIP_GO = 0;
    const TRIP_BACK = 1;

    /** @var int */
    protected $nbRemainingRounds;
    /** @var int[] */
    protected $kirkPosition;
    /** @var Map */
    protected $map;

    /** @var int */
    protected $direction = self::TRIP_GO;

    public function __construct($nbOfRounds, Map $map)
    {
        $this->nbRemainingRounds = $nbOfRounds;
        $this->map = $map;
    }

    public function setKirkPosition($row, $column)
    {
        $this->kirkPosition = [$row, $column];

        $end = $this->map->getEnd();
        if (null !== $end && $this->kirkPosition === $end) {
            $this->direction = self::TRIP_BACK;
        }
    }

    public function newTurn()
    {
        --$this->nbRemainingRounds;
    }

    public function getDirection()
    {
        if (self::TRIP_BACK === $this->direction) {
            return 'LEFT';
        }
        return 'RIGHT';
    }
}

class SimpleMove extends DummyMove implements MoveInterface
{
    const TRIP_GO = 0;
    const TRIP_BACK = 1;

    /** @var int */
    protected $nbRemainingRounds;
    /** @var int[] */
    protected $kirkPosition;
    /** @var Map */
    protected $map;

    /** @var int */
    protected $direction = self::TRIP_GO;

    /** @var bool[][] */
    protected $exploredCells = [];

    protected $pathFinder;

    public function __construct($nbOfRounds, Map $map)
    {
        $this->nbRemainingRounds = $nbOfRounds;
        $this->map = $map;
    }

    public function setKirkPosition($row, $column)
    {
        $this->kirkPosition = [$row, $column];

        $this->exploredCells[$row][$column] = true;

        $end = $this->map->getEnd();
        if (null !== $end && $this->kirkPosition === $end) {
            $this->direction = self::TRIP_BACK;
        }
    }

    public function newTurn()
    {
        --$this->nbRemainingRounds;
    }

    public function getDirection()
    {
        if (self::TRIP_GO === $this->direction) {
            $direction = $this->getDirectionGo();
        } else {
            $direction = $this->getDirectionBack();
        }

        return $direction;
    }

    protected function getDirectionGo()
    {
        if ($this->pathFinder->pathExists()) {
        }
    }

    protected function getDirectionBack()
    {
        $goal = $this->map->getStart();
    }

    public function setPathFinder($pathFinder)
    {
        $this->pathFinder = $pathFinder;
    }
}

class Dijkstra {
    /** @var Map */
    protected $map;
    protected $stack;
    protected $distances = [];
    protected $parcourus = [];

    /**
     * Construct Dijkstra.
     * @param string $start Start node id
     */
    public function __construct(Map $map, $start) {
        $this->stack = new Stack($this);
        $this->initialise($map, $start);
    }

    /**
     * Initialise Dijkstra.
     * @param Map $map
     * @param string $start Start node id
     */
    public function initialise(Map $map, $start) {
        $this->map = $map;
        $this->addToStack($start[0], $start[1]);

        $this->distances[$start[0]][$start[1]] = 0;
    }

    protected function addToStack($courant, $suivant)
    {
        //Check map's limits.
        if (
            $suivant[0] < 0
            || $this->map->getHeight() <= $suivant[0]
            || $suivant[1] < 0
            || $this->map->getWidth() <= $suivant[1]
        ) {
            return;
        }

        $dist = $this->getDistance($suivant[0], $suivant[1]);
        if ($dist === null || $courant === null || $this->getDistance($courant[0], $courant[1]) + 1 < $dist) {
            $this->stack->add($suivant[0], $suivant[1]);
        }
    }

    public function mesurer() {
        while ($closest = $this->stack->findClosest()) {
            $this->parcourus[] = $closest;
            $x = $closest[0];
            $y = $closest[1];

            $this->addToStack($x + 1, $y);
            $this->addToStack($x - 1, $y);
            $this->addToStack($x, $y + 1);
            $this->addToStack($x, $y - 1);
        }
    }

    public function getDistance($row, $column)
    {
        if (array_key_exists($row, $this->distances) && array_key_exists($column, $this->distances[$row])) {
            return $this->distances[$row][$column];
        }

        return false;
    }
}

class Stack {
    protected $_list = array();
    /** @var Dijkstra */
    protected $dijkstra;

    public function __construct($dijkstra)
    {
        $this->dijkstra = $dijkstra;
    }

    public function add($row, $column) {
        if (in_array([$row, $column], $this->_list)) {
            return false;
        }

        $this->_list[] = [$row, $column];
        return true;
    }

    public function findClosest() {
        if ($this->isEmpty()) {
            return false;
        }

        $closest = null;
        foreach ($this->_list as $node) {
            if (null === $closest) {
                $closest = $node;
                continue;
            }

            if ($node->dist < $closest->dist) {
                $closest = $node;
            }
        }

        unset($this->_list[$closest->id]);

        return $closest;
    }

    public function isEmpty() {
        return empty($this->_list);
    }
}

fscanf(STDIN, "%d %d %d",
    $R, // number of rows.
    $C, // number of columns.
    $A // number of rounds between the time the alarm countdown is activated and the time the alarm goes off.
);

$map = new Map();
$moveStrategy = new SimpleMove($A, $map);

// game loop
while (TRUE)
{
    $moveStrategy->newTurn();

    fscanf(STDIN, "%d %d",
        $KR, // row where Kirk is located.
        $KC // column where Kirk is located.
    );
    $moveStrategy->setKirkPosition($KR, $KC);

    for ($i = 0; $i < $R; $i++)
    {
        fscanf(STDIN, "%s",
            $ROW // C of the characters in '#.TC?' (i.e. one line of the ASCII maze).
        );
        $map->setRow($i, $ROW);
    }

    _($map->getCells());

    echo($moveStrategy->getDirection() . "\n");
}
