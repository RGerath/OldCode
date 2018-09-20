<?php

//	connect to mysql server
//	unsure if this is proper server information
$link = mysqli_connect("localhost", "admin", "admin", "octaranktest02");

//	check connection
if ($link === false) {
	die("ERROR: Could not connect. " . mysqli_connect_error());
}

echo "success!";

//	close connection
mysqli_close($link);

?>