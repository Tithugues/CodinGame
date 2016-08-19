<?php

define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

class PyramidBuilder
{
    protected $grid = [];

    protected $numberOfLevels = 0;
    protected $numberOfPyramidsOnLastLevel = 0;
    protected $height = 0;
    protected $width = 0;

    public function build($numberOfGlasses)
    {
        $this->getBiggestPyramidFromNumberOfGlasses($numberOfGlasses);

        $this->height = $this->numberOfLevels * 4;
        $this->width = $this->numberOfPyramidsOnLastLevel*5 + $this->numberOfPyramidsOnLastLevel - 1;

        _('Height: ' . $this->height);
        _('Width: ' . $this->width);

        $this->initialiseGrid();
        $this->buildLevels();

        return implode("\n", $this->grid);
    }

    /**
     * @param $numberOfGlasses
     * @return int
     */
    protected function getBiggestPyramidFromNumberOfGlasses($numberOfGlasses)
    {
        $increment = 1;
        $numberOfUsedGlasses = 0;
        while (true) {
            if ($numberOfUsedGlasses + $increment > $numberOfGlasses) {
                break;
            }

            $numberOfUsedGlasses += $increment++;
        }

        $this->numberOfLevels = --$increment;
        $this->numberOfPyramidsOnLastLevel = $increment;
    }

    protected function initialiseGrid()
    {
        for ($i = 0; $i < $this->numberOfLevels*4; ++$i) {
            $this->grid[] = str_pad(null, $this->width, ' ');
        }
    }

    protected function buildLevels()
    {
        for ($level = 0; $level < $this->numberOfLevels; ++$level) {
            $this->buildLevel($level);
        }
    }

    /**
     * @param int $level Indicates the level to build and number of glasses
     */
    protected function buildLevel($level)
    {
        _('Build level: ' . $level);
        $startRow = $level * 4;
        $widthNeeded = ($level+1) * 5 + $level;
        $startColumn = ($this->width - $widthNeeded) / 2;
        _('Start row: ' . $startRow);
        _('Width needed: ' . $widthNeeded);
        _('Start column: ' . $startColumn);

        for ($pyramidId = 0; $pyramidId < $level+1; ++$pyramidId) {
            $this->buildPyramid($startRow, $startColumn + 6 * $pyramidId);
            _($this->grid);
        }
    }

    protected function buildPyramid($startRow, $startColumn)
    {
        _('Start row: ' . $startRow);
        _('Start column: ' . $startColumn);
        $this->grid[$startRow]   = substr_replace($this->grid[$startRow], ' *** ', $startColumn, 5);
        $this->grid[$startRow+1] = substr_replace($this->grid[$startRow+1], ' * * ', $startColumn, 5);
        $this->grid[$startRow+2] = substr_replace($this->grid[$startRow+2], ' * * ', $startColumn, 5);
        $this->grid[$startRow+3] = substr_replace($this->grid[$startRow+3], '*****', $startColumn, 5);
    }
}

fscanf(STDIN, "%d", $N);

$pb = new PyramidBuilder();

echo($pb->build($N) . "\n");
