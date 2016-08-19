<?php

define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

class GravityEmulator
{
    public function applyGravity($grid)
    {
        $height = count($grid);
        $width = strlen($grid[0]);

        for ($column = 0; $column < $width; ++$column) {
            _('----------------------------------------');
            _($grid);
            $nbFreeCellsOnColumn = $height;
            for ($row = $height - 1; $row >= 0; --$row) {
                if ('#' === substr($grid[$row], $column, 1)) {
                    --$nbFreeCellsOnColumn;
                }
            }
            _($nbFreeCellsOnColumn);

            for ($row = 0; $row < $height; ++$row) {
                $grid[$row] = substr_replace($grid[$row], '.', $column, 1);
                if ($row >= $nbFreeCellsOnColumn) {
                    $grid[$row] = substr_replace($grid[$row], '#', $column, 1);
                }
                _($grid);
            }
        }

        return implode("\n", $grid);
    }
}

fscanf(STDIN, "%d %d", $width, $height);
$grid = [];
for ($i = 0; $i < $height; $i++)
{
    fscanf(STDIN, "%s", $grid[]);
}

$ge = new GravityEmulator();

echo($ge->applyGravity($grid) . "\n");
