<?php

define('DEBUG', false);

function _d($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

class Morse {
    /**
     * Translation array
     * @var array
     */
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

    /**
     * Morse code to decode
     * @var string
     */
    protected $_initialMorseCode = null;

    /**
     * Length of morse code to decode
     * @var int
     */
    protected $_initialMorseLength = null;

    /**
     * Positions of words into morse code to decode
     * @var array[]
     */
    protected $_positions = array();

    /**
     * Initialise object by adding the initial morse code to decode
     * @param string $morseCode Morse code to decode
     */
    public function __construct($morseCode) {
        $this->_initialMorseCode = $morseCode;
        $this->_initialMorseLength = strlen($morseCode);
    }

    /**
     * Add a word into dictionnary and its position, if the morse translation is found into the initial morse code
     * @param string $word Word to add
     * @return bool True if word had been added, else false
     */
    public function addWord($word) {
        $sMorse = $this->_translateToMorse($word);

        $initialMorseCode = $this->_initialMorseCode;
        if (false === ($pos = strpos($initialMorseCode, $sMorse))) {
            return false;
        }

        do {
            if (!isset($this->_positions[$pos])) {
                $this->_positions[$pos] = array();
            }
            $this->_positions[$pos][] = array('word' => $sMorse);
        } while (false !== ($pos = strpos($initialMorseCode, $sMorse, ++$pos)));

        return true;
    }

    /**
     * Translate a word in morse
     * @param $sWord
     * @return string
     */
    protected function _translateToMorse($sWord) {
        $sMorse = '';
        for ($i = 0, $length = strlen($sWord); $i < $length; ++$i) {
            $sMorse .= $this->_latinToMorse[$sWord{$i}];
        }
        return $sMorse;
    }

    /**
     * Travel through different paths and count possibilities
     * @return int Number of possibilities from this position
     */
    public function parse() {
        return $this->_parse(0);
    }

    /**
     * Travel through different paths and count possibilities from current position
     * @param int $pos Position to start travel
     * @return int Number of possibilities from this position
     */
    protected function _parse($pos) {
        if ($this->_initialMorseLength === $pos) {
            return 1;
        }

        if (!isset($this->_positions[$pos])) {
            return 0;
        }

        $nbPossibilities = 0;
        foreach ($this->_positions[$pos] as $key => $aMorse) {
            if (isset($aMorse['nbPossibilities'])) {
                $nbPossibilities += $aMorse['nbPossibilities'];
                continue;
            }
            //Check for possibilities from position after the current word.
            $nbPossibilitiesTmp = $this->_parse($pos + strlen($aMorse['word']));
            $this->_positions[$pos][$key]['nbPossibilities'] = $nbPossibilitiesTmp;
            $nbPossibilities += $nbPossibilitiesTmp;
        }

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
