--TEST--
Test for Services_PageRank CheckHash
--FILE--
<?php

//settings
$query = 'google.com';

//set class
require_once '../Services/PageRank.php';
$pr = new Services_PageRank();
echo $pr->getCheckhash($query);

?>

--EXPECT--
81020345