<?php

/**
 * Class CarpetSewer
 */
class CarpetSewer
{
    public function sewAndCut($levels, $x1, $y1, $x2, $y2) {
        $carpet = $this->sew($levels);

        $patch = [];
        for ($i = $y1; $i <= $y2; ++$i) {
            $patch[] = substr($carpet[$i], $x1, $x2 - $x1 + 1);
            unset($carpet[$i]);
        }

        return $patch;
    }

    /**
     * @param $level
     *
     * @return array
     */
    protected function sew($level) {
        if (0 === $level) {
            return [0];
        }

        $border = $this->sewBorder($level);
        $middle = $this->sewMiddle($level);
        $heightChild = pow(3, $level - 1);

        $height = $heightChild * 3;
        $carpet = array_pad([], $height, '');

        for ($row = 0; $row < $heightChild; ++$row) {
            $carpet[$heightChild + $row] .= $border[$row];
            $carpet[$heightChild + $row] .= $middle[$row];
            $carpet[$heightChild + $row] .= $border[$row];
            unset($middle[$row]);

            $carpet[$row] .= str_pad('', $height, $border[$row]);
            $carpet[$heightChild*2 + $row] = $carpet[$row];
            unset($border[$row]);
        }

        return $carpet;
    }

    /**
     * @param int $level Number of levels under this carpet
     *
     * @return string[]
     */
    protected function sewBorder($level) {
        if (1 === $level) {
            return [0];
        }

        return $this->sew($level - 1);
    }

    /**
     * @param int $level Number of levels under this carpet
     *
     * @return string[]
     */
    protected function sewMiddle($level) {
        $length = pow(3, $level - 1);
        return array_pad([], $length, str_pad('', $length, '+'));
    }
}

fscanf(STDIN, "%d", $L); //Number of levels
fscanf(STDIN, "%d %d %d %d",
    $x1, //Top left x
    $y1, //Top left y
    $x2, //Bottom right x
    $y2  //Bottom right y
);

$sewer = new CarpetSewer();
echo implode("\n", $sewer->sewAndCut($L, $x1, $y1, $x2, $y2)) . "\n";