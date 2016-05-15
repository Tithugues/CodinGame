<?php

class SelectionRangesReducer
{
    public function reduce($ranges)
    {
        $ranges = $this->extractAndSort($ranges);

        //Loop and search for next values.
        $end = false;
        $result = [];
        foreach ($ranges as $range) {
            if (null !== $end && $range <= $end) {
                continue;
            }

            $end = $range;
            while (array_search(++$end, $ranges)) {}
            --$end; //Come back to last value found.

            if ($end - $range >= 2) {
                $result[] = $range . '-' . $end;
            } else {
                $result[] = $range;
                --$end;
            }
        }

        return implode(',', $result);
    }

    /**
     * @param string $ranges
     *
     * @return array
     */
    protected function extractAndSort($ranges)
    {
        $ranges = substr($ranges, 1, -1);
        $ranges = explode(',', $ranges);

        foreach ($ranges as &$range) {
            $range = (int)$range;
        }
        unset($range);

        sort($ranges);

        return $ranges;
    }
}

$N = stream_get_line(STDIN, 100 + 1, "\n");

$reducer = new SelectionRangesReducer();

echo($reducer->reduce($N) . "\n");
