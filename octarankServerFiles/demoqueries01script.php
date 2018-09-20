<?php

echo "this should print when the script is called.<br><br>";

//	connect to current test server
$link = mysqli_connect("localhost", "admin", "admin", "octaranktest03");

//	check connection
if ($link === false) {
	echo "this should print if the connection has failed. the following should provide more information:<br>";
	die("ERROR: Could not connect. " . mysqli_connect_error());
}

echo "this should print if the connection is successfully established.<br><br>";

//	filter for potential sql injection
$bar = mysqli_real_escape_string($link, $_REQUEST['bar']);
$net = mysqli_real_escape_string($link, $_REQUEST['net']);

echo "this should print if the definitions of bar and net have been passed without critical error.<br><br>";

//	define the mysql query needed to add a new row to the bnpairs table
$sql = "INSERT INTO bnpairs (PBarrier, PNet) VALUES ('$bar', '$net')";

echo "this should print if the definition of sql has been passed without error.<br><br>";

//	run query with check
if(mysqli_query($link, $sql)){
	echo "this should print if the query has been made without error.<br><br>	";
} else {
	echo "this should print if the query has failed in some way. The following should provide more information:<br>";
	echo "ERROR: Could not execute SQL command $sql. " . mysqli_error($link);
}

//	attempt query execution
/*	this query is meant to define all the rank-changes that would occur if the lower-ranked fighter won this hypothetical match

	for now we should try to find incline list and shortest connection:
		identify which of fid1 and fid2 is the lower-ranked fighter, set this one as fid1
		apply breadth-first-search to barriers of fid1, storing first path to fid2 as shortest connection between the two
		continue breadth-first-search for all branches that do not reach fid2, storing all fighters on those branches as incline list
		return incline list and (if extant) shortest connection from fid1 to fid2

	next, we would want to determine which class of outcome an upset would be:
		see refined notes on algorithm
	
	next, we would want to redistribute all fighters to their appropriate new levels:
		
	
	next, we would want to redistribute the fighters in each row according to streak length/number of wins
*/
/*
$sql = "INSERT INTO Fights (TLowerFighter, THigherFighter) VALUES ('$fid1', '$fid2')";
if(mysqli_query($link, $sql)) {
	echo "Records added";
} else {
	echo "ERROR: Could not execute SQL command $sql. " . mysqli_error($link);
}
*/

//	close connection
mysqli_close($link);

echo "this should print once the connection has been closed.";

?>