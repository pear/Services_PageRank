--TEST--
Test for Services_PageRank URL
--FILE--
<?php

//settings
$query = 'example.com';

//set class
require_once '../Services/PageRank.php';
$pr = new Services_PageRank();
$pr->setQuery($query);
echo $pr->getUrl();

?>

--EXPECT--
http://toolbarqueries.google.com/tbr?client=navclient-auto&ch=85ee6a887&features=Rank&q=info:example.com