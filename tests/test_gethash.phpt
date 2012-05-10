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
echo $pr->getHash();

?>

--EXPECT--
1592174727