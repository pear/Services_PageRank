--TEST--
Test for Services_PageRank CheckHash
--FILE--
<?php

//settings
$query = 'example.com';

//set class
require_once '../Services/PageRank.php';
$pr = new Services_PageRank();
$pr->setQuery($query);
echo $pr->getCheckhash();

?>

--EXPECT--
85ee6a887