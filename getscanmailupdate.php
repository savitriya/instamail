<?php
$totalmailcount_file = 'totalmailcount.txt';
$totalmail=file_get_contents($totalmailcount_file);

$file = 'scanmailscount.txt';
// Open the file to get existing content
$current = file_get_contents($file);

$response = array('totalmails'=> $totalmail,'currentscaned'=>$current);
echo json_encode($response);