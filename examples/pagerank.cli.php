#!/usr/bin/php
<?php

/*

    Google PageRank PHP Command Line Interface (CLI) Script

    Requires: Services_PageRank PEAR package

    Install: Rename to pr (mv pagerank.cli.php pr) and make executable (chmod 755 pr)

    Usage: ./pr <query> (Eg: ./pr example.com)

*/

require('Services/PageRank.php');

if (isset($argv[1]) && $argv[1]) {
    try {
        $pr = new Services_PageRank($argv[1]);
        if ($pr->getPagerank()) {
            $result = $pr->getPagerank() . '/10';
        } else {
            $result = 'N/A';
        }
    } catch (Services_PageRank_Exception $e) {
        $result = $e->getMessage();
    }
    echo "PageRank: $result\n";
} else {
    echo 'Usage: ', $argv[0],' <query> (Eg: ',$argv[0]," example.com)\n";
}

//eof