<?php

define('DEBUG', true);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

class Inversor
{
    public function inverse($number)
    {
        $dividend = 10;
        $result = '';
        $previous = [];
        while (true) {
            _('------------------------------------------------');
            $quotient = floor($dividend / $number);
            $remainder = $dividend % $number;
            _($quotient);
            _($remainder);

            //Find combination in previous values.
            $combination = [$quotient, $remainder];
            if (false !== ($position = array_search($combination, $previous))) {
                //If found, leave.
                break;
            }

            //If not found, add quotient to result.
            $result .= $quotient;
            $previous[] = $combination;

            //If remainder = 0, division is over.
            if (0 === $remainder) {
                _('stop');
                break;
            }

            //Loop with a new dividend.
            $dividend = $remainder * 10;
        }

        if (false !== $position) {
            $result = substr_replace($result, '(', $position, 0) . ')';
        }

        $result = '0.' . $result;

        return $result;
    }
}

fscanf(STDIN, "%d", $n);
_($n);

$inversor = new Inversor();

echo($inversor->inverse($n) . "\n");
