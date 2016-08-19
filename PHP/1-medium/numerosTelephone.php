<?php
/**
 * Auto-generated code below aims at helping you parse
 * the standard input according to the problem statement.
 **/

class Book {
    public $list = array();
    public $numberNodes = 0;

    public function addNumber($phoneNumber) {
        $aDigits = str_split($phoneNumber);
        $array =& $this->list;
        foreach ($aDigits as $sDigit) {
            if (!isset($array[$sDigit])) {
                $array[$sDigit] = array();
                $this->numberNodes++;
            }

            $array =& $array[$sDigit];
        }
    }
}

fscanf(STDIN, "%d",
    $N
);

$b = new Book();
for ($i = 0; $i < $N; $i++)
{
    fscanf(STDIN, "%s",
        $telephone
    );
    $b->addNumber($telephone);
}

// Write an action using echo(). DON'T FORGET THE TRAILING \n
// To debug (equivalent to var_dump): error_log(var_export($var, true));

echo $b->numberNodes . "\n";
