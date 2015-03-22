<?php

$debug = false;

function debug($var, $force = false) {
    if ($GLOBALS['debug'] || $force) {
        error_log(var_export($var, true));
    }
}

class BaseConverter {
    const TO_20 = 1;
    const FROM_20 = 2;

    public static $_values = array(
        0 => '0',
        1 => '1',
        2 => '2',
        3 => '3',
        4 => '4',
        5 => '5',
        6 => '6',
        7 => '7',
        8 => '8',
        9 => '9',
        10 => 'a',
        11 => 'b',
        12 => 'c',
        13 => 'd',
        14 => 'e',
        15 => 'f',
        16 => 'g',
        17 => 'h',
        18 => 'i',
        19 => 'j',
    );

    public static function convert($number, $direction) {
        if (static::TO_20 === $direction) {
            return static::_convertFrom10To20($number);
        }
        if (static::FROM_20 === $direction) {
            return static::_convertFrom20To10($number);
        }
        return false;
    }

    protected static function _convertFrom10To20($number) {
        $digits = array();
        do {
            $digits[] = static::$_values[$number%20];
            $number = floor($number/20);
        } while($number != 0);

        unset($number);

        $sNumber = null;
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $sNumber .= $digits[$i];
        }

        return $sNumber;
    }

    protected static function _convertFrom20To10($number) {
        $sNumber = (string)$number;
        unset($number);

        $digits = array_reverse(str_split($sNumber));
        $numberB10 = 0;
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $numberB10 += current(array_keys(static::$_values, $digits[$i], true)) * pow(20, $i);
        }

        return $numberB10;
    }
}

class MayaDigit {
    protected $_content = array();

    public function __construct($content) {
        $this->_content = $content;
    }

    public function __toString() {
        return implode("\n", $this->_content);
    }
}

class MayaDigits {
    protected $_digits = array();

    /*public function addMayaDigit($digit, MayaDigit $oMayaDigit) {
        $this->_digits[$digit] = $oMayaDigit;
    }*/

    public function addMayaDigit($digit, $mayaDigit) {
        $this->_digits[$digit] = $mayaDigit;
    }

    public function getMayaDigit($digit) {
        return $this->_digits[$digit];
    }

    /*public function getDigit(MayaDigit $oMayaDigit) {
        $keys = array_keys($this->_digits, $oMayaDigit, true);
        return current($keys);
    }*/

    public function getDigit($mayaDigit) {
        $keys = array_keys($this->_digits, $mayaDigit, true);
        return current($keys);
    }
}

fscanf(STDIN, "%d %d",
    $L,
    $H
);
$mayaDigits = array_fill_keys(range(0, 19), array());
for ($i = 0; $i < $H; $i++)
{
    fscanf(STDIN, "%s",
        $numeral
    );
    $row = str_split($numeral, $L);
    unset($numeral);
    foreach ($row as $number => $content) {
        $mayaDigits[$number][] = $content;
    }
    unset($row, $number, $content);
}
unset($i);

$oMayaDigits = new MayaDigits();
foreach ($mayaDigits as $number => $content) {
    //$oMayaDigits->addMayaDigit($number, new MayaDigit($content));
    $oMayaDigits->addMayaDigit(BaseConverter::convert($number, BaseConverter::TO_20), $content);
}
unset($mayaDigits, $number, $content);

//Read first number.
fscanf(STDIN, "%d",
    $numberLines //Number of lines for first number.
);
$mayaDigits = array_fill_keys(range(0, ($numberLines / $H) - 1), array());
for ($i = 0; $i < $numberLines; $i++)
{
    fscanf(STDIN, "%s",
        $numLine
    );
    $mayaDigits[(int)floor($i / $H)][] = $numLine;
}
//First number in base 20.
$number1b20 = null;
foreach ($mayaDigits as $id => $mayaDigit) {
    $number1b20 .= (string)$oMayaDigits->getDigit($mayaDigit);
}
unset($mayaDigits, $id, $mayaDigit);
debug($number1b20);
$number1b10 = BaseConverter::convert($number1b20, BaseConverter::FROM_20);
unset($number1b20);
debug($number1b10);

//Read second number
fscanf(STDIN, "%d",
    $numberLines //Number of lines for first number.
);
$mayaDigits = array_fill_keys(range(0, ($numberLines / $H) - 1), array());
for ($i = 0; $i < $numberLines; $i++)
{
    fscanf(STDIN, "%s",
        $numLine
    );
    $mayaDigits[(int)floor($i / $H)][] = $numLine;
}
//Second number in base 20.
$number2b20 = null;
foreach ($mayaDigits as $id => $mayaDigit) {
    $number2b20 .= (string)$oMayaDigits->getDigit($mayaDigit);
}
unset($mayaDigits, $id, $mayaDigit);
debug($number2b20);
$number2b10 = BaseConverter::convert($number2b20, BaseConverter::FROM_20);
unset($number2b20);
debug($number2b10);

fscanf(STDIN, "%s",
    $operation
);

$result = null;
if ('+' === $operation) {
    $result = $number1b10 + $number2b10;
} elseif ('-' === $operation) {
    $result = $number1b10 - $number2b10;
}if ('*' === $operation) {
    $result = $number1b10 * $number2b10;
}if ('/' === $operation) {
    $result = $number1b10 / $number2b10;
}

debug($number1b10 . ' ' . $operation . ' ' . $number2b10 . ' = ' . $result, true);
$resultb20 = BaseConverter::convert($result, BaseConverter::TO_20);

debug($resultb20, true);

$mayaDigits = str_split($resultb20);
foreach ($mayaDigits as $mayaDigit) {
    echo implode("\n", $oMayaDigits->getMayaDigit($mayaDigit)) . "\n";
}

// Write an action using echo(). DON'T FORGET THE TRAILING \n
// To debug (equivalent to var_dump): error_log(var_export($var, true));
