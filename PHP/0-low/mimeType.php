<?php

fscanf(STDIN, "%d",
    $N // Number of elements which make up the association table.
);
fscanf(STDIN, "%d",
    $Q // Number Q of file names to be analyzed.
);
$exts = array();
for ($i = 0; $i < $N; $i++)
{
    fscanf(STDIN, "%s %s",
        $EXT, // file extension
        $MT // MIME type.
    );
    if (strlen($MT) > 50) {
        continue;
    }
    $exts[strtolower($EXT)] = $MT;
}
for ($i = 0; $i < $Q; $i++)
{
    $FNAME = strtolower(stream_get_line(STDIN, 1024, "\n")); // One file name per line.
    if (strlen($FNAME) > 256) {
        echo "UNKNOWN\n";
    }
    $ext = pathinfo($FNAME, PATHINFO_EXTENSION);
    error_log(var_export($ext, true));
    if (strlen($ext) <= 10 && isset($exts[$ext])) {
        echo $exts[$ext] . "\n";
    } else {
        echo("UNKNOWN\n");
    }
}
