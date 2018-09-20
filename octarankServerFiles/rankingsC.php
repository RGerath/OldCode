<?php
header('Content-type: text/json'); 
//	BLOCK 1:	establish php connection with database and prepare for weight class 'WAW'
$link = mysqli_connect("localhost", "admin-test", "admin", "octarank-test")
or die('Error connecting to MySQL server.');


//	get strWC
$strWC = $_GET['strWC'];


//	BLOCK 2:	get the info from the 'rankings' table for this weight class
$RInfo = mysqli_query($link, "SELECT * FROM rankings WHERE RWeightClass='$strWC'");
//	RInfoRef holds arrays which hold information on a single fighter's ranking data
$RInfoRef = [];
//	array to hold key values of multi-array
$keyNums = [];
//	extract necessary info from RInfo:
while($row = mysqli_fetch_array($RInfo)){
	$tempRow = [$row["RWeightPlace"], $row["RLevelPlace"], $row["RLevel"], $row["RFighter"]];
	
	// push other values pertaining to the fighter to the row
	$FInfo = mysqli_query($link, "SELECT * FROM fighters WHERE FID='$tempRow[3]'");
	while($rowF = mysqli_fetch_array($FInfo)){
		array_push($tempRow,
			$rowF["FName"],
			$rowF["FStreakSign"],
			$rowF["FStreakLength"],
			$rowF["FWins"],
			$rowF["FLosses"],
			$rowF["FDraws"]
		);
	}
	
	//	push tempRow to the returned JSON structure and assign keys to weight place
	array_push($RInfoRef, $tempRow);
	array_push($keyNums, $row["RWeightPlace"]);
}
//	sort RInfoRef by the key values in RWeightPlace
array_multisort($keyNums, SORT_ASC, $RInfoRef);

//	BLOCK 3:	print to JSON and close
header('Content-Type: application/json');
echo json_encode($RInfoRef);
mysqli_close($link);
?>