<?php

define('DEBUG', true);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

abstract class RomNumber {
    protected static $romFiguresList = [
        'I' => 1,
        'V' => 5,
        'X' => 10,
        'L' => 50,
        'C' => 100,
        'D' => 500,
        'M' => 1000,
    ];
}

class RomNumberWriter extends RomNumber {
    protected $decimalNumber;

    public function setDecimalNumber($decimalNumber) {
        $this->decimalNumber = $decimalNumber;
        return $this;
    }

    public function __toString()
    {
        $remainingDecimalNumber = $this->decimalNumber;
        $romNumber = '';
        $previousRomValue = false;
        $prepreviousRomValue = false;
        foreach (array_reverse(self::$romFiguresList) as $romFigure => $decimalFigure) {
            _d('foreach: ' . $romFigure . ' => ' . $decimalFigure . ' / ' . $romNumber . ' / ' . $remainingDecimalNumber);
            while (true) {
                _d('while: ' . $romFigure . ' => ' . $decimalFigure . ' / ' . $romNumber . ' / ' . $remainingDecimalNumber);
                if (in_array($decimalFigure, [1, 10, 100])) {
                    _d('in_array');
                    $romFigureToUse = false;
                    if ($remainingDecimalNumber >= 9*$decimalFigure) {
                        $romFigureToUse = $prepreviousRomValue;
                    } elseif ($remainingDecimalNumber >= 4*$decimalFigure) {
                        $romFigureToUse = $previousRomValue;
                    }
                    if (false !== $romFigureToUse) {
                        $romNumber .= $romFigure . $romFigureToUse;
                        $remainingDecimalNumber += $decimalFigure;
                        $remainingDecimalNumber -= self::$romFiguresList[$romFigureToUse];
                        break;
                    }
                }

                if ($remainingDecimalNumber >= $decimalFigure) {
                    $romNumber .= $romFigure;
                    $remainingDecimalNumber -= $decimalFigure;
                    continue;
                }

                break;
            }

            if (0 === $remainingDecimalNumber) {
                break;
            }

            $prepreviousRomValue = $previousRomValue;
            $previousRomValue = $romFigure;
        }

        return $romNumber;
    }
}

class RomNumberReader extends RomNumber {
    protected $romNumber;

    public function setRomNumber($romNumber) {
        $this->romNumber = $romNumber;
        return $this;
    }

    public function convertToDecimal() {
        $decimalNumber = 0;

        $previousRomFigure = false;
        for ($i = strlen($this->romNumber) - 1; $i >= 0; --$i) {
            //
        }
    }
}

$romNumber = new RomNumberWriter();
echo $romNumber->setDecimalNumber(9);