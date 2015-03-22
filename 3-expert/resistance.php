<?php

define('DEBUG', false);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Morse {
    protected $_latinToMorse = array(
        'A' => '.-',
        'B' => '-...',
        'C' => '-.-.',
        'D' => '-..',
        'E' => '.',
        'F' => '..-.',
        'G' => '--.',
        'H' => '....',
        'I' => '..',
        'J' => '.---',
        'K' => '-.-',
        'L' => '.-..',
        'M' => '--',
        'N' => '-.',
        'O' => '---',
        'P' => '.--.',
        'Q' => '--.-',
        'R' => '.-.',
        'S' => '...',
        'T' => '-',
        'U' => '..-',
        'V' => '...-',
        'W' => '.--',
        'X' => '-..-',
        'Y' => '-.--',
        'Z' => '--..',
    );

    protected $_initialMorseCode = null;
    protected $_initialMorseLength = null;
    protected $_words = array();

    public function __construct($morseCode) {
        $this->_initialMorseCode = $morseCode;
        $this->_initialMorseLength = strlen($morseCode);
    }

    public function addWord($word) {
        $sMorse = $this->_convertToMorse($word);

        $initialMorseCode = $this->_initialMorseCode;
        if (false === ($pos = strpos($initialMorseCode, $sMorse))) {
            return false;
        }

        $positions = array();
        do {
            $positions[$pos] = true;
        } while (false !== ($pos = strpos($initialMorseCode, $sMorse, ++$pos)));

        $this->_words[$word] = array(
            'morse' => $sMorse,
            'length' => strlen($sMorse),
            'positions' => $positions
        );

        return true;
    }

    protected function _convertToMorse($sWord) {
        $sMorse = '';
        for ($i = 0, $length = strlen($sWord); $i < $length; ++$i) {
            $sMorse .= $this->_latinToMorse[$sWord{$i}];
        }
        return $sMorse;
    }

    public function parse() {
        return $this->_parse(0);
    }

    protected function _parse($pos) {
        if ($this->_initialMorseLength <= $pos) {
            return 1;
        }

        $nbPossibilities = 0;
        foreach ($this->_words as $word => $aMorse) {
            _d('Check: ' . $word);
            if (!isset($aMorse['positions'][$pos])) {
                continue;
            }
            _d('Match');

            $nbPossibilities += $this->_parse($pos + $aMorse['length']);
            _d('nbPossibilities: ' . $nbPossibilities);
        }
        _d('loop', true);

        return $nbPossibilities;
    }
}

fscanf(STDIN, "%s", $sMorse);
fscanf(STDIN, "%d", $numberWords);
$oMorse = new Morse($sMorse);
for ($i = 0; $i < $numberWords; $i++) {
    fscanf(STDIN, "%s", $word);
    $oMorse->addWord($word);
}

echo $oMorse->parse() . "\n";
