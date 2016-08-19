<?php
define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

class OutOfGridException extends Exception {}
class NoMoreSpaceException extends Exception {}

/**
 * Interface SmashEngineArgentInterface
 */
interface SmashEngineArgentInterface {
    /**
     * @param int[][] $colors
     * @param array[] $gridA
     * @param array[] $gridB
     */
    public function reinit(array $colors, array $gridA, array $gridB);

    /**
     * Get grid of player A
     * @return array[]
     */
    public function getA();

    /**
     * @param int $colId Column ID
     * @param int $orientation Second ball is on the
     *                         0: right
     *                         1: top
     *                         2: left
     *                         3: bottom
     *
     * @return int Number of points
     * @throws Exception
     * @throws OutOfGridException
     * @throws NoMoreSpaceException
     */
    public function putOnA($colId, $orientation);

    /**
     * Get grid of player B
     * @return array[]
     */
    public function getB();

    /**
     * @param int $colId Column ID
     * @param int $orientation Second ball is on the
     *                         0: right
     *                         1: top
     *                         2: left
     *                         3: bottom
     *
     * @return int Number of points
     * @throws Exception
     * @throws OutOfGridException
     * @throws NoMoreSpaceException
     */
    public function putOnB($colId, $orientation);

    /**
     * @return int[][] Next 2 colors
     */
    public function getNextColors();
}

class DummySmashEngineArgent implements SmashEngineArgentInterface {
    /** @var int[][] Colors of next blocks */
    protected $colors;
    /** @var int Index of current color */
    protected $currentColorIndex;
    /** @var array[] Grid of player 1 */
    protected $gridA;
    /** @var array[] Grid of player 2 */
    protected $gridB;

    /**
     * @inheritdoc
     */
    public function reinit(array $colors, array $gridA, array $gridB) {
        $this->colors = $colors;
        $this->currentColorIndex = 0;
        $this->gridA = $gridA;
        $this->gridB = $gridB;
    }

    /**
     * @inheritdoc
     */
    public function getNextColors()
    {
        return $this->colors[$this->currentColorIndex++];
    }

    /**
     * @inheritdoc
     */
    public function putOnA($colId, $orientation)
    {
        return $this->put($this->gridA, $colId, $orientation);
    }

    /**
     * @inheritdoc
     */
    public function putOnB($colId, $orientation)
    {
        return $this->put($this->gridB, $colId, $orientation);
    }

    /**
     * Add balls and calculate points
     * @param array[] $grid
     * @param int $colId
     * @param int $orientation Second ball is on the
     *                         0: right
     *                         1: top
     *                         2: left
     *                         3: bottom
     *
     * @return int Number of points
     * @throws Exception
     * @throws OutOfGridException
     * @throws NoMoreSpaceException
     */
    protected function put(&$grid, $colId, $orientation)
    {
        $colorsByColumn = [$colId => [$this->colors[$this->currentColorIndex][0]]];
        if (0 === $orientation) {
            if (5 === $colId) {
                throw new OutOfGridException();
            }
            $colorsByColumn[$colId + 1] = [$this->colors[$this->currentColorIndex][1]];
        } elseif (1 === $orientation) {
            $colorsByColumn[$colId][] = $this->colors[$this->currentColorIndex][1];
        } elseif (2 === $orientation) {
            if (0 === $colId) {
                throw new OutOfGridException();
            }
            $colorsByColumn[$colId - 1] = [$this->colors[$this->currentColorIndex][1]];
        } elseif (3 === $orientation) {
            array_unshift($colorsByColumn[$colId], $this->colors[$this->currentColorIndex][1]);
        } else {
            throw new Exception('Unexisting orentation.');
        }

        $cellsChanged = [];

        foreach ($colorsByColumn as $colId => $line) {
            foreach ($line as $color) {
                //If column full, leave with exception.
                if ('.' !== $grid[$colId][0]) {
                    throw new NoMoreSpaceException();
                }
                //Else, add color on top.
                for ($lineId = 11; $lineId >= 0; --$lineId) {
                    //Not free space, so still look the upper space.
                    if ('.' !== $grid[$colId][$lineId]) {
                        continue;
                    }

                    //Found a free space: add the color and go ahead with the next color.
                    $grid[$colId][$lineId] = $color;
                    $cellsChanged[$colId][$lineId] = $color;
                    break;
                }
            }
        }

        return $this->estimate($grid, $cellsChanged);
    }

    /**
     * @param array[] $grid
     * @param int[][] $cellsChanged
     *
     * @return int
     */
    protected function estimate($grid, $cellsChanged)
    {
        _('estimate');
        $points = 0;
        foreach ($cellsChanged as $colId => $lines) {
            foreach ($lines as $lineId => $color) {
                _('Check ' . $colId . ' ' . $lineId . ' ' . $color);
                _('Check left ' . $this->getColorLeft($grid, $colId, $lineId) . ' with ' . $color);
                if ($this->getColorLeft($grid, $colId, $lineId) === $color) {
                    _('Left is the same');
                    ++$points;
                }
                _('Check bottom ' . $this->getColorBottom($grid, $colId, $lineId) . ' with ' . $color);
                if ($this->getColorBottom($grid, $colId, $lineId) === $color) {
                    _('Bottom is the same');
                    ++$points;
                }
                _('Check right ' . $this->getColorRight($grid, $colId, $lineId) . ' with ' . $color);
                if ($this->getColorRight($grid, $colId, $lineId) === $color) {
                    _('Right is the same');
                    ++$points;
                }
                _('For ' . $colId . ' ' . $lineId . ' ' . $color . ': ' . $points . ' points');
            }
        }
        return $points;
    }

    protected function validCell($grid, $colId, $lineId) {
        if (array_key_exists($colId, $grid) && array_key_exists($lineId, $grid[$colId])) {
            return $grid[$colId][$lineId];
        }
        return false;
    }

    protected function getColorLeft($grid, $colId, $lineId) {
        return $this->validCell($grid, --$colId, $lineId);
    }

    protected function getColorBottom($grid, $colId, $lineId) {
        return $this->validCell($grid, $colId, ++$lineId);
    }

    protected function getColorRight($grid, $colId, $lineId) {
        return $this->validCell($grid, ++$colId, $lineId);
    }

    /**
     * @inheritdoc
     */
    public function getA()
    {
        return $this->gridA;
    }

    /**
     * @inheritdoc
     */
    public function getB()
    {
        return $this->gridB;
    }

    /*protected function getChainPower($turn)
    {
        $chainPower = null;

        if (1 === $turn) {
            $chainPower = 0;
        } else {
            $chainPower = 8 * pow(2, $turn);
        }

        return $chainPower;
    }

    protected function getTurnPoints($numberOfBlocks, $chainPower, $colorBonus, $groupBonus)
    {
        $score = $chainPower * $colorBonus * $groupBonus;
        //Score should be between 1 and 999.
        $score = max($score, 1);
        $score = min($score, 999);
        return (10 * $numberOfBlocks) * $score;
    }*/
}

interface SmashPlayerArgent {
    public function position(array $colors, array $myGrid, array $hisGrid);
}

class DummySmashPlayerArgent implements SmashPlayerArgent {
    public function position(array $colors, array $myGrid, array $hisGrid)
    {
        $myGrid = $this->convertGridFromRowsToColumns($myGrid);

        $nextColor = $colors[0][0];
        _('Next color: ' . $nextColor);

        $return = $this->findColumnWithGroup($myGrid, $nextColor);
        if (false !== $return) {
            return $return;
        }

        _('No color found');

        //Find lowest columns
        return $this->findLowestColumn($myGrid);
    }

    protected function convertGridFromRowsToColumns($grid)
    {
        $newGrid = [];

        foreach ($grid as $line => $row) {
            for ($column = strlen($row) - 1; $column >= 0; --$column) {
                $newGrid[$column][$line] = '.' === $row{$column} ? $row{$column} : (int)$row{$column};
            }
        }

        return $newGrid;
    }

    protected function getColor($grid, $colId, $rowId) {
        $color = false;
        if (array_key_exists($colId, $grid) && array_key_exists($rowId, $grid[$colId])) {
            $color = $grid[$colId][$rowId];
        }
        return $color;
    }

    /**
     * @param array $grid
     * @param int   $nextColor
     *
     * @return int|bool Column id or false if none
     */
    protected function findColumnWithGroup(array $grid, $nextColor)
    {
        $colsToIgnore = [];
        $return = false;
        foreach ($grid as $colId => $column) {
            foreach ($column as $lineId => $color) {
                if ($lineId < 2 && '.' !== $color) {
                    $colsToIgnore[$colId] = true;
                }
                if (array_key_exists($colId, $colsToIgnore)) {
                    continue;
                }

                if ('.' === $color) {
                    continue;
                }

                if ($color === $nextColor) {
                    $return = $colId;
                    break 2;
                }

                break;
            }
        }

        return $return;
    }

    /**
     * @param array $myGrid
     *
     * @return int Column id of lowest column
     */
    protected function findLowestColumn(array $myGrid)
    {
        $lowestColumns = [];
        $lowestColumnsHeight = null;
        foreach ($myGrid as $colId => $column) {
            $currentColumnHeight = 0;
            //Top to bottom
            foreach ($column as $lineId => $color) {
                _($color);
                if ('.' === $color) {
                    continue;
                }

                $currentColumnHeight = 12 - $lineId;
                break;
            }
            _('Current column: ' . $colId);
            _('Current column height: ' . $currentColumnHeight);
            _('Lowest column height: ' . $lowestColumnsHeight);
            if (null === $lowestColumnsHeight || $currentColumnHeight < $lowestColumnsHeight) {
                _('Init or New lowest column found');
                $lowestColumns = [$colId];
                $lowestColumnsHeight = $currentColumnHeight;
            } elseif ($currentColumnHeight === $lowestColumnsHeight) {
                _('As low as lowest known');
                $lowestColumns[] = $colId;
            }
        }

        _('Lowest columns');
        _($lowestColumns);

        return $lowestColumns[array_rand($lowestColumns)];
    }
}

class BruteForceSmashPlayerArgent extends DummySmashPlayerArgent {
    protected $smashEngine;
    protected $colors;
    protected $myGrid;
    protected $hisGrid;

    /**
     * BruteForceSmashPlayerArgent constructor.
     *
     * @param SmashEngineArgentInterface $smashEngine
     */
    public function __construct(SmashEngineArgentInterface $smashEngine) {
        $this->smashEngine = $smashEngine;
    }

    public function position(array $colors, array $myGrid, array $hisGrid)
    {
        $this->colors = $colors;
        $this->myGrid = $this->convertGridFromRowsToColumns($myGrid);
        $this->hisGrid = $this->convertGridFromRowsToColumns($hisGrid);

        return implode(' ', $this->bruteForce());
    }

    /**
     * @return int[] Elements to return
     */
    protected function bruteForce()
    {
        $bestPoints = false;
        $bestOutputs = false;
        for ($colId = 0; $colId <= 5; ++$colId) {
            for ($orientation = 0; $orientation <= 3; ++$orientation) {
                $this->smashEngine->reinit($this->colors, $this->myGrid, $this->hisGrid);
                try {
                    $points = $this->smashEngine->putOnA($colId, $orientation);
                } catch (Exception $e) {
                    continue;
                }

                if (false === $bestPoints || $bestPoints < $points) {
                    $bestPoints = $points;
                    $bestOutputs = [[$colId, $orientation]];
                    _('New best points = ' . $bestPoints);
                    _('New best outputs = ');
                    _($bestOutputs);
                } elseif ($bestPoints === $points) {
                    $bestOutputs[] = [$colId, $orientation];
                    _('New best outputs = ');
                    _($bestOutputs);
                }
            }
        }

        _('Result: Best points = ' . $bestPoints);
        _('Result: Best outputs = ');
        _($bestOutputs);

        return $bestOutputs[array_rand($bestOutputs)];
    }
}

// game loop
$engine = new BruteForceSmashPlayerArgent(new DummySmashEngineArgent());
while (TRUE) {
    $colors = [];
    for ($i = 0; $i < 8; $i++) {
        fscanf(STDIN, "%d %d",
            $colorA, // color of the first block
            $colorB // color of the attached block
        );
        $colors[] = [$colorA, $colorB];
    }

    $myGrid = [];
    for ($i = 0; $i < 12; $i++) {
        fscanf(STDIN, "%s", $myGrid[]);
    }

    $hisGrid = [];
    for ($i = 0; $i < 12; $i++) {
        fscanf(STDIN, "%s", $hisGrid[]);
    }

    echo ($engine->position($colors, $myGrid, $hisGrid) . "\n"); // "x": the column in which to drop your blocks
}
