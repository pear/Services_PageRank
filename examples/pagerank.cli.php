#!/usr/bin/php
<?php

/*

    Google PageRank PHP Command Line Interface (CLI) Script

    Requires: Services_PageRank PEAR package

    Install: Rename to pr (mv pagerank.cli.php pr) and make executable (chmod 755 pr)

    Usage: ./pr <url>

*/

require('Services/PageRank.php');

if (isset($argv[1]) && $argv[1]) {
    echo new Services_PageRank($argv[1]);
} else {
    echo 'Usage: ' . $argv[0] . '<url>';
}

//eof