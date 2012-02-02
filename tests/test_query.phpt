--TEST--
Test for Services_PageRank query
--FILE--
<?php

//settings
$success = 'Rank_1:1:7';
$fail = file_get_contents('fail.html');

//set class
require_once '../Services/PageRank.php';
$pr = new Services_PageRank();

//set mock
require_once 'HTTP/Request2/Adapter/Mock.php';
$mock = new HTTP_Request2_Adapter_Mock();
$response = "HTTP/1.1 200 OK\r\n" .
    "Content-Length: %s\r\n" .
    "Connection: close\r\n" .
    "\r\n%s";
$mock->addResponse(sprintf($response,strlen($success),$success));
$mock->addResponse(sprintf($response,strlen($fail),$fail));

//set mock adapter
$pr->request->setAdapter($mock);

// success
try {
	$pagerank = $pr->query('example.com');
	echo "PageRank: $pagerank/10\n";
} catch (Services_PageRank_Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}

// fail
try {
	echo $pr->query('.');
} catch (Services_PageRank_Exception $e) {
	echo 'Caught exception: ',  $e->getMessage(), "\n";
}

?>

--EXPECT--
PageRank: 7/10
Caught exception: Unable to parse, found HTML