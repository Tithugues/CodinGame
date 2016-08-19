<?php

define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

interface SegmentFigureInterface
{
    public function getFigureLines();
}

abstract class SegmentFigureAbstract implements SegmentFigureInterface
{
    const SEGMENT_TOP = 0;
    const SEGMENT_TOP_LEFT = 1;
    const SEGMENT_TOP_RIGHT = 2;
    const SEGMENT_CENTER = 3;
    const SEGMENT_BOTTOM_LEFT = 4;
    const SEGMENT_BOTTOM_RIGHT = 5;
    const SEGMENT_BOTTOM = 6;

    protected $segmentSize;
    protected $symbol;
    /** @var int[] Segments used for the current figure */
    protected $segments = [];

    public function __construct($segmentSize, $symbol)
    {
        $this->segmentSize = $segmentSize;
        $this->symbol = $symbol;
    }

    public function getFigureLines()
    {
        $width = $this->getFigureWidth();
        $height = $this->getFigureHeight();

        $widthNeeded = array_pad([], $width, ' ');
        $figure = array_pad([], $height, $widthNeeded);

        foreach ($this->segments as $segment) {
            switch ($segment) {
                case self::SEGMENT_TOP:
                    $figure = $this->fillHorizontal($figure, 1, 0);
                    break;
                case self::SEGMENT_CENTER:
                    $figure = $this->fillHorizontal($figure, 1, floor($height / 2));
                    break;
                case self::SEGMENT_BOTTOM:
                    $figure = $this->fillHorizontal($figure, 1, $height - 1);
                    _($figure);
                    break;

                case self::SEGMENT_TOP_LEFT:
                    $figure = $this->fillVertical($figure, 0, 1);
                    break;
                case self::SEGMENT_TOP_RIGHT:
                    $figure = $this->fillVertical($figure, $width - 1, 1);
                    break;
                case self::SEGMENT_BOTTOM_LEFT:
                    $figure = $this->fillVertical($figure, 0, ceil($height / 2));
                    break;
                case self::SEGMENT_BOTTOM_RIGHT:
                    $figure = $this->fillVertical($figure, $width - 1, ceil($height / 2));
                    break;
            }
        }

        return $figure;
    }

    private function getFigureWidth()
    {
        return $this->segmentSize + 2;
    }

    private function getFigureHeight()
    {
        return $this->segmentSize * 2 + 3;
    }

    private function fillHorizontal($figure, $x, $y)
    {
        for ($xx = $x; $xx <= $x + $this->segmentSize - 1; ++$xx) {
            $figure[$y][$xx] = $this->symbol;
        }

        return $figure;
    }

    private function fillVertical($figure, $x, $y)
    {
        for ($yy = $y; $yy <= $y + $this->segmentSize - 1; ++$yy) {
            $figure[$yy][$x] = $this->symbol;
        }

        return $figure;
    }
}

class Segment0 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP,
        self::SEGMENT_TOP_LEFT,
        self::SEGMENT_TOP_RIGHT,
        self::SEGMENT_BOTTOM_LEFT,
        self::SEGMENT_BOTTOM_RIGHT,
        self::SEGMENT_BOTTOM,
    ];
}

class Segment1 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP_RIGHT,
        self::SEGMENT_BOTTOM_RIGHT,
    ];
}

class Segment2 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP,
        self::SEGMENT_TOP_RIGHT,
        self::SEGMENT_CENTER,
        self::SEGMENT_BOTTOM_LEFT,
        self::SEGMENT_BOTTOM,
    ];
}

class Segment3 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP,
        self::SEGMENT_TOP_RIGHT,
        self::SEGMENT_CENTER,
        self::SEGMENT_BOTTOM_RIGHT,
        self::SEGMENT_BOTTOM,
    ];
}

class Segment4 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP_LEFT,
        self::SEGMENT_TOP_RIGHT,
        self::SEGMENT_CENTER,
        self::SEGMENT_BOTTOM_RIGHT,
    ];
}

class Segment5 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP,
        self::SEGMENT_TOP_LEFT,
        self::SEGMENT_CENTER,
        self::SEGMENT_BOTTOM_RIGHT,
        self::SEGMENT_BOTTOM,
    ];
}

class Segment6 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP,
        self::SEGMENT_TOP_LEFT,
        self::SEGMENT_CENTER,
        self::SEGMENT_BOTTOM_LEFT,
        self::SEGMENT_BOTTOM_RIGHT,
        self::SEGMENT_BOTTOM,
    ];
}

class Segment7 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP,
        self::SEGMENT_TOP_RIGHT,
        self::SEGMENT_BOTTOM_RIGHT,
    ];
}

class Segment8 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP,
        self::SEGMENT_TOP_LEFT,
        self::SEGMENT_TOP_RIGHT,
        self::SEGMENT_CENTER,
        self::SEGMENT_BOTTOM_LEFT,
        self::SEGMENT_BOTTOM_RIGHT,
        self::SEGMENT_BOTTOM,
    ];
}

class Segment9 extends SegmentFigureAbstract
{
    protected $segments = [
        self::SEGMENT_TOP,
        self::SEGMENT_TOP_LEFT,
        self::SEGMENT_TOP_RIGHT,
        self::SEGMENT_CENTER,
        self::SEGMENT_BOTTOM_RIGHT,
        self::SEGMENT_BOTTOM,
    ];
}

class NumberManager implements SegmentFigureInterface
{
    protected $number;
    protected $segmentSize;
    protected $symbol;

    public function __construct($number, $segmentSize, $symbol)
    {
        $this->number = $number;
        $this->segmentSize = $segmentSize;
        $this->symbol = $symbol;
    }

    public function getFigureLines()
    {
        $height = $this->getFigureHeight();
        $numberLines = array_pad([], $height, []);

        $number = (string)$this->number;
        for ($i = 0; $i < strlen($number); ++$i) {
            $figureClass = 'Segment' . $number{$i};
            /** @var SegmentFigureAbstract $figure */
            $figure = new $figureClass($this->segmentSize, $this->symbol);
            $figureLines = $figure->getFigureLines();
            _($figureLines);
            foreach ($figureLines as $lineId => $figureLine) {
                if (!empty($numberLines[$lineId])) {
                    $numberLines[$lineId][] = ' ';
                }
                foreach ($figureLine as $char) {
                    $numberLines[$lineId][] = $char;
                }
            }
            _($numberLines);
        }

        return $numberLines;
    }

    private function getFigureHeight()
    {
        return $this->segmentSize * 2 + 3;
    }
}

fscanf(STDIN, "%d", $N);
$C = stream_get_line(STDIN, 1 + 1, "\n");
fscanf(STDIN, "%d", $S);

$segmentFigure8 = new NumberManager($N, $S, $C);

foreach ($segmentFigure8->getFigureLines() as $figureLine) {
    echo rtrim(implode('', $figureLine)) . "\n";
}
