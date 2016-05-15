<?php

define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

class Aligner
{
    protected $text = [];
    protected $alignment = false;
    protected $largest = false;

    public function align($alignment, $text)
    {
        $this->text = $text;
        $this->alignment = $alignment;

        $this->initialiseLargest();
        $this->alignRows();

        return implode("\n", $this->text);
    }

    protected function initialiseLargest()
    {
        foreach ($this->text as $row) {
            $this->largest = max($this->largest, strlen($row));
        }
    }

    protected function alignRows()
    {
        if ('LEFT' === $this->alignment) {
            $this->alignLeft();
        } elseif ('CENTER' === $this->alignment) {
            $this->alignCenter();
        } elseif ('RIGHT' === $this->alignment) {
            $this->alignRight();
        } elseif ('JUSTIFY' === $this->alignment) {
            $this->alignJustify();
        } else {
            throw new Exception('What?');
        }
    }

    protected function alignLeft()
    {
    }

    protected function alignCenter()
    {
        foreach ($this->text as &$row) {
            $row = str_pad($row, $this->largest, ' ', STR_PAD_BOTH);
            $row = rtrim($row);
        }
    }

    protected function alignRight()
    {
        foreach ($this->text as &$row) {
            $row = str_pad($row, $this->largest, ' ', STR_PAD_LEFT);
        }
    }

    protected function alignJustify()
    {
        _('Largest: ' . $this->largest);
        foreach ($this->text as &$row) {
            $rowsWords = explode(' ', $row);
            //If only 1 word, leave.
            if (1 === count($rowsWords)) {
                continue;
            }

            $spacesToAdd = $this->largest - strlen($row) + count($rowsWords) - 1;
            if (0 === $spacesToAdd) {
                continue;
            }

            _('Current length: ' . strlen($row));
            _('Spaces to add: ' . $spacesToAdd);

            $spacesBetweenWords = $spacesToAdd / (count($rowsWords) - 1);
            $addedSpaces = 0;
            $row = array_shift($rowsWords);
            foreach ($rowsWords as $word) {
                $spacesHere = floor($addedSpaces + $spacesBetweenWords) - $addedSpaces;
                $row .= str_pad(null, $spacesHere, ' ') . $word;
            }
        }
    }
}

$alignment = stream_get_line(STDIN, 7 + 1, "\n");
_($alignment);

fscanf(STDIN, "%d", $N);
$text = [];
for ($i = 0; $i < $N; $i++)
{
    $text[] = stream_get_line(STDIN, 256 + 1, "\n");
}

_($text);

$aligner = new Aligner();

echo($aligner->align($alignment, $text) . "\n");
