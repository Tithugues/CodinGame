<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

class SmashEngine {
    /** @var int[][] Colors of next blocks */
    protected $colors;
    /** @var int Index of current color */
    protected $currentColorIndex;
    /** @var array[] Grid of player 1 */
    protected $gridA;
    /** @var array[] Grid of player 2 */
    protected $gridB;

    public function initialise($colors, $gridA, $gridB) {
        $this->colors = $colors;
        $this->currentColorIndex = 0;
        $this->gridA = $gridA;
        $this->gridB = $gridB;
    }


}

interface SmashPlayer {
    public function chooseColumn(array $colors, array $myGrid, array $hisGrid);
}

class DummySmashPlayer implements SmashPlayer {
    public function chooseColumn(array $colors, array $myGrid, array $hisGrid)
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
        $return = false;
        foreach ($grid as $colId => $column) {
            foreach ($column as $lineId => $color) {
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

class QuiteDummySmashPlayer extends DummySmashPlayer {
    /**
     * @param array $grid
     * @param int   $nextColor
     *
     * @return int|bool Column id or false if none
     */
    protected function findColumnWithGroup(array $grid, $nextColor)
    {
        $bestColumnsId = [];
        $bestNumberOfConnections = null;
        foreach ($grid as $colId => $column) {
            foreach ($column as $rowId => $color) {
                if ('.' === $color) {
                    continue;
                }

                //Check with the position above the current line.
                $numberOfConnections = $this->countNumberOfConnections($grid, $colId, $rowId-1, $nextColor);
                _('Number of connections: ' . $numberOfConnections);
                _('Current best number of connections: ' . $bestNumberOfConnections);

                //If no connection, don't prioritize this column.
                if (0 === $numberOfConnections) {
                    break;
                }

                if (null === $bestNumberOfConnections || $bestNumberOfConnections < $numberOfConnections) {
                    $bestColumnsId = [$colId];
                    $bestNumberOfConnections = $numberOfConnections;
                } elseif ($bestNumberOfConnections === $numberOfConnections) {
                    $bestColumnsId[] = $colId;
                }

                break;
            }
        }

        _('Columns with best number of connections');
        _($bestColumnsId);

        $colId = false;

        if (!empty($bestColumnsId)) {
            $colId = $bestColumnsId[array_rand($bestColumnsId)];
        }

        return $colId;
    }

    protected function countNumberOfConnections($grid, $colId, $rowId, $color)
    {
        _('Color searched: ' . $color . ' around ' . $colId . '/' . $rowId);

        $numberOfConnections = 0;
        //left
        _('left: ' . $this->getColor($grid, $colId-1, $rowId));
        if ($this->getColor($grid, $colId-1, $rowId) === $color) {
            ++$numberOfConnections;
        }

        //right
        _('right: ' . $this->getColor($grid, $colId+1, $rowId));
        if ($this->getColor($grid, $colId+1, $rowId) === $color) {
            ++$numberOfConnections;
        }

        //top
        _('top: ' . $this->getColor($grid, $colId, $rowId+1));
        if ($this->getColor($grid, $colId, $rowId+1) === $color) {
            ++$numberOfConnections;
        }

        //bottom
        _('bottom: ' . $this->getColor($grid, $colId, $rowId-1));
        if ($this->getColor($grid, $colId, $rowId-1) === $color) {
            ++$numberOfConnections;
        }

        return $numberOfConnections;
    }
}

class QuiteDummy2SmashPlayer extends QuiteDummySmashPlayer {
    /**
     * @param array $grid
     * @param int   $nextColor
     *
     * @return int|bool Column id or false if none
     */
    protected function findColumnWithGroup(array $grid, $nextColor)
    {
        $bestColumnsId = [];
        $bestNumberOfConnections = null;
        foreach ($grid as $colId => $column) {
            foreach ($column as $rowId => $color) {
                //Loop until first ball
                if ('.' === $color) {
                    continue;
                }

                break;
            }

            //If we stopped the loop because we found a ball, check the position above this ball.
            if ('.' !== $color) {
                --$rowId;
            }

            $numberOfConnections = $this->countNumberOfConnections($grid, $colId, $rowId, $nextColor);
            _('Number of connections: ' . $numberOfConnections);
            _('Current best number of connections: ' . $bestNumberOfConnections);

            //If no connection, don't prioritize this column.
            if (0 === $numberOfConnections) {
                continue;
            }

            if (null === $bestNumberOfConnections || $bestNumberOfConnections < $numberOfConnections) {
                $bestColumnsId = [$colId];
                $bestNumberOfConnections = $numberOfConnections;
            } elseif ($bestNumberOfConnections === $numberOfConnections) {
                $bestColumnsId[] = $colId;
            }
        }

        _('Columns with best number of connections');
        _($bestColumnsId);

        $colId = false;

        if (!empty($bestColumnsId)) {
            $colId = $bestColumnsId[array_rand($bestColumnsId)];
        }

        return $colId;
    }

    protected function countNumberOfConnections($grid, $colId, $rowId, $color)
    {
        _('Color searched: ' . $color . ' around ' . $colId . '/' . $rowId);

        $numberOfConnections = 0;
        //left
        $neighboorColor = $this->getColor($grid, $colId-1, $rowId);
        _('left: ' . $neighboorColor);
        if ($color === $neighboorColor) {
            ++$numberOfConnections;
        }
        if (0 === $neighboorColor) {
            $numberOfConnections += 0.1;
        }

        //right
        $neighboorColor = $this->getColor($grid, $colId+1, $rowId);
        _('right: ' . $neighboorColor);
        if ($color === $neighboorColor) {
            ++$numberOfConnections;
        }
        if (0 === $neighboorColor) {
            $numberOfConnections += 0.1;
        }

        //bottom
        $neighboorColor = $this->getColor($grid, $colId, $rowId+1);
        _('bottom: ' . $neighboorColor);
        if ($color === $neighboorColor) {
            ++$numberOfConnections;
        }
        if (0 === $neighboorColor) {
            $numberOfConnections += 0.1;
        }

        //top
        $neighboorColor = $this->getColor($grid, $colId, $rowId-1);
        _('top: ' . $neighboorColor);
        if ($color === $neighboorColor) {
            ++$numberOfConnections;
        }
        if (0 === $neighboorColor) {
            $numberOfConnections += 0.1;
        }

        return $numberOfConnections >= 1 ? floor($numberOfConnections) : $numberOfConnections;
    }
}

class TryComboSmashPlayer extends DummySmashPlayer {
    public function chooseColumn(array $colors, array $myGrid, array $hisGrid)
    {
        $myGrid = $this->convertGridFromRowsToColumns($myGrid);

        $nextColor = $colors[0][0];
        _('Next color: ' . $nextColor);

        $return = $this->findColumnWithGroup($myGrid, $nextColor);
        if (false !== $return) {
            return $return;
        }

        $return = $this->findColumnWithGroupSeparatedBySkull($myGrid, $nextColor);
        if (false !== $return) {
            return $return;
        }

        _('No color found');

        //Find lowest columns
        return $this->findLowestColumn($myGrid);
    }

    protected function findColumnWithGroupSeparatedBySkull($grid, $nextColor)
    {
        $return = false;
        foreach ($grid as $colId => $column) {
            foreach ($column as $lineId => $color) {
                if ('.' === $color) {
                    continue;
                }

                //If top ball is a skull, check next ball.
                if (0 === $color) {
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
}

// game loop
$engine = new BruteForceSmashPlayerArgent();
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

    echo ($engine->chooseColumn($colors, $myGrid, $hisGrid) . "\n"); // "x": the column in which to drop your blocks
}
