<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

fscanf(STDIN, "%d",
    $N
);
$seqs = array();
for ($i = 0; $i < $N; $i++)
{
    fscanf(STDIN, "%s",
        $subseq
    );
    $seqs[] = $subseq;
}

//Simplify
foreach ($seqs as $id => $seq) {
    foreach ($seqs as $id2 => $seq2) {
        if ($id === $id2) {
            continue;
        }
        if (false !== strpos($seq2, $seq)) {
            debug('clean', true);
            debug($seq2, true);
            debug($seq, true);
            unset($seqs[$id]);
            break;
        }
    }
}

sort($seqs);

for ($id = 0, $nb = count($seqs); $id < $nb; ++$id) {
    if (!isset($seqs[$id])) {
        continue;
    }
    $seq = $seqs[$id];
    for ($i = strlen($seq)-1; 0 < $i; --$i) {
        $start = substr($seq, 0, $i);
        $end = substr($seq, -$i);
        foreach ($seqs as $id2 => $seq2) {
            if ($id === $id2) {
                continue;
            }
            $start2 = substr($seq2, 0, $i);
            $end2 = substr($seq2, -$i);
            //If end $seq = start $seq2
            if ($end === $start2) {
                debug('case 1', true);
                debug($seqs, true);
                $seqs[$id2] = $seq . substr($seq2, $i);
                unset($seqs[$id]);
                debug($seqs, true);
                break 2;
            }
            //If end $seq2 = start $seq
            if ($end2 === $start) {
                debug('case 2', true);
                debug($seqs, true);
                $seqs[$id2] = $seq2 . substr($seq, $i);
                unset($seqs[$id]);
                debug($seqs, true);
                break 2;
            }
        }
    }
}

debug($seqs, true);
echo strlen(implode('', $seqs)) . "\n";
