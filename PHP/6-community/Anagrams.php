<?php

define('DEBUG', false);

function _($trace, $force = false)
{
    if (DEBUG === true || $force) {
        error_log(var_export($trace, true));
    }
}

$phrase = stream_get_line(STDIN, 1024 + 1, "\n");

class AnagramsDecoder
{
    protected $result;
    protected $alphabet;

    public function __construct($sentence)
    {
        $this->result = $sentence;
        $this->alphabet = range('A', 'Z');
        _($this->result, true);
    }

    public function getResult()
    {
        $this->revertLengths();
        $this->firstToLast(4);
        $this->lastToFirst(3);
        $this->revertRank(2);

        return $this->result;
    }

    protected function revertLengths()
    {
        $words = explode(' ', $this->result);
        $wordsLengths = [];
        foreach ($words as $word) {
            array_unshift($wordsLengths, strlen($word));
        }

        $sentence = implode('', $words);
        $previousLengths = 0;
        foreach ($wordsLengths as $length) {
            $previousLengths += $length;
            $sentence = substr_replace($sentence, ' ', $previousLengths, 0);
            ++$previousLengths;
        }

        $this->result = rtrim($sentence);

        _($this->result, true);
    }

    protected function lastToFirst($rank)
    {
        _('lastToFirst');
        $posToReverse = $this->getPosFromRank($rank);
        _($posToReverse);

        if (empty($posToReverse)) {
            return;
        }

        $keys = array_keys($posToReverse);
        $letter = array_shift($posToReverse);
        array_push($posToReverse, $letter);

        $newLettersPos = array_combine($keys, $posToReverse);
        _($newLettersPos);

        foreach ($newLettersPos as $pos => $letter) {
            $this->result{$pos} = $letter;
        }

        _($this->result, true);
    }

    protected function firstToLast($rank)
    {
        _('firstToLast');
        $posToReverse = $this->getPosFromRank($rank);

        if (empty($posToReverse)) {
            return;
        }

        $keys = array_keys($posToReverse);
        $letter = array_pop($posToReverse);
        array_unshift($posToReverse, $letter);

        $newLettersPos = array_combine($keys, $posToReverse);
        _($newLettersPos, true);

        foreach ($newLettersPos as $pos => $letter) {
            $this->result{$pos} = $letter;
        }

        _($this->result, true);
    }

    protected function revertRank($rank)
    {
        $posToReverse = $this->getPosFromRank($rank);

        if (empty($posToReverse)) {
            return;
        }

        $keys = array_keys($posToReverse);
        $keys = array_reverse($keys);
        $newLettersPos = array_combine($keys, $posToReverse);
        _($posToReverse);
        _($newLettersPos);

        foreach ($newLettersPos as $pos => $letter) {
            $this->result{$pos} = $letter;
        }

        _($this->result);
    }

    protected function isLetterAtRank($letter, $rank)
    {
        $keys = array_keys($this->alphabet, $letter);
        if (empty($keys)) {
            return false;
        }
        return $keys[0]%$rank === ($rank - 1);
    }

    /**
     * @param $rank
     *
     * @return array
     */
    protected function getPosFromRank($rank)
    {
        $posToReverse = [];

        for ($i = 0, $length = strlen($this->result); $i < $length; ++$i) {
            $letter = $this->result{$i};
            if ($this->isLetterAtRank($letter, $rank)) {
                $posToReverse[$i] = $letter;
            }
        }

        return $posToReverse;
    }
}

$decoder = new AnagramsDecoder($phrase);

echo($decoder->getResult() . "\n");