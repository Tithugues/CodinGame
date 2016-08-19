<?php

define('DEBUG', false);

function debug($var, $force = false) {
    if (DEBUG || $force) {
        error_log(var_export($var, true));
    }
}

function getText($string, $indent) {
    $string = trim($string);

    if ('' === $string) {
        return '';
    }

    return str_pad('', $indent * 4, ' ') . $string;
}

fscanf(STDIN, "%d", $numberLines);
$text = null;
for ($i = 0; $i < $numberLines; $i++)
{
    $CGXLine = stream_get_line(STDIN, 1000, "\n");
    $text .= $CGXLine;
}

debug($text);
$lines = array();
$line = null;
$indent = 0;
$pos = 0;
$length = strlen($text);
while ($pos < $length) {
    if ('\'' === $text{$pos}) {
        $start = $pos;
        $pos = strpos($text, '\'', ++$pos);
        $line .= substr($text, $start, $pos - $start + 1);
        unset($start);
        debug('string');
        debug($line);
        ++$pos;
        continue;
    }

    if ('(' === $text{$pos}) {
        debug('parenthèse ouvrante');
        debug($line);
        $line = getText($line, $indent);
        debug($line);
        if ('' !== $line) {
            $lines[] = $line;
        }
        $lines[] = getText('(', $indent);
        $line = null;
        $indent++;
        $pos++;
        continue;
    }

    if (')' === $text{$pos}) {
        debug('parenthèse fermante');
        debug($line);
        $line = getText($line, $indent);
        if ('' !== $line) {
            $lines[] = $line;
        }
        $line = ')';
        --$indent;
        ++$pos;
        continue;
    }

    if (';' === $text{$pos}) {
        debug('point virgule');
        debug($line);
        $lines[] = getText($line . ';', $indent);
        $line = null;
        ++$pos;
        continue;
    }

    if (' ' !== $text{$pos}) {
        $line .= $text{$pos};
    }
    ++$pos;
}

$lines[] = getText($line, $indent);

debug('solution');

foreach ($lines as $line) {
    echo $line . "\n";
}
