<?php

//	PRELIMINARY OPERATIONS:
{
	echo "PRELIMINARY OPERATIONS: connecting to server...";
	
	//	connect to current testing server
	$link = mysqli_connect("localhost", "admin-test", "admin", "octarank-test");
	//	check connection
	if ($link === false) {
		die("ERROR: Could not connect. " . mysqli_connect_error() . "<br><br>");
	}
	error_reporting(E_ALL);
	ini_set('display_errors',1);
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	
	//	filter for potential sql injection and define X (fighter 1) and Y (fighter 2) and strWC (weight class in which the fight took place)
	$X = mysqli_real_escape_string($link, $_REQUEST['fid1']);
	$Y = mysqli_real_escape_string($link, $_REQUEST['fid2']);
	$strWC = mysqli_real_escape_string($link, $_REQUEST['wc']);
	
	//	* * * screen input and ensure fids are set legitimate fighter IDs
	//			valid entries include legitimate fighter IDs and full names (stripped of caps)
	//			if invalid, indicate entered values could not be parsed and quit
	
	
	
	//	* * * ensure selected weight class is valid for both specified fighters
	//			ensure both fighters are men if a male weight class is selected, and women if a female weight class is selected
	
	
	
	//	check if this fight is a draw:
	$drawCheck = mysqli_real_escape_string($link, $_REQUEST['drawCheck']);
	if ($drawCheck){
		//	* * * add a draw to each fighter's record
		
		
		//	* * * modify streak sign and length accordingly
		
		
		//	* * * add appropriate row to 'matches'
		
		
		//	quit, performing no further operations
		mysqli_close($link);
		exit("<br>Records of draw added to database");
	}
	
	echo "<br><br>PRELIMINARY OPERATIONS: fid1 = '$X', fid2 = '$Y', strWC = '$strWC'<br>";
	
	
	//	indentCount should increase for each level of function being executed,
	//	and indent should precede any declaration of function execution indentCount times
	$indent="--";
	$indentCount = 0;
	
	//	initialize certain variables that will be used by multiple functions:
	//		connection list between X and Y
	$conList = [];
	//		connection list between arbitrary fighters
	$tempConList = [];
	//		list of ordered pairs to determine when getGenConList should return without further operation
	$oldPairs = [];
	//		FList
	//		FList is meant to hold fighters which are relevant to either case 1 or case 2,
	//		and will be defined by the operation of checkConList()
	$FList = [];
	//		strOutcome will hold...
	//			'lower' if winner started out lower
	//			'higher' if winner started out higher
	$strOutcome = "";
	
	//	* * * increase max execution time for this script
	ini_set('max_execution_time', 100);
	
	echo "<br><br>PRELIMINARY OPERATIONS: PASSED<br>";
}


//	FUNCTION DEFINITIONS:
{
	//	ESSENTIAL:
	{
		//	function to update records when X was ranked higher than Y
		function higherWin($A, $B){
			//	define global variables used in this scope
			global $link, $strWC, $strOutcome, $indent, $indentCount;
			
			$thisIndent = str_repeat($indent, $indentCount);
			echo "<br><br>$thisIndent HIGHER FIGHTER WINS:<br>";
			
			
			//	modify 'bnpairs' data for A and B:
			//		generate new pair for A and B
			$indentCount++;
			addBNPair($A, $B);
			$indentCount--;
			
			
			//	modify 'matches' data:
			//		update streak bits for matches involving A
			$indentCount++;
			updFightStreaks($A, 'win');
			$indentCount--;
			//		update streak bits for matches involving B
			$indentCount++;
			updFightStreaks($B, 'lose');
			$indentCount--;
			//		add new match (TID should be an auto_increment column)
			mysqli_query($link, "INSERT INTO
						matches (MLower, MHigher, MWinner, MLowerStreak, MHigherStreak, MWeightClass)
						VALUES ('$A', '$B', '$strOutcome', 1, 1, '$strWC')");
			//		drop expired matches
			mysqli_query($link, "DELETE FROM matches WHERE MLowerStreak=0 AND MHigherStreak=0");
			
			
			//	modify 'rankings' data for A and B:
			//		re-sort the fighters and count the new level total
			$indentCount++;
			$levelCount = finalPlaceLevel();
			$indentCount--;
			//		for each level, run a function to sort that level's fighters
			$levelNo = $levelCount-1;
			while($levelNo >= 0){
				//	get fighters on this level
				$levelFighters = mysqli_query($link, "SELECT RFighter FROM rankings WHERE RWeightClass='$strWC' AND RLevel='$levelNo'");
				
				$fighterNo = $levelFighters->num_rows;
				echo "<br><br>number of fighters on level '$levelNo' = '$fighterNo'";
				
				//	pass this list of fighters to a function that will sort them and modify their rankings data accordingly
				$indentCount++;
				sortLevel($levelFighters, $levelNo);
				$indentCount--;
				
				$levelNo--;
			}
			//		define fighters' places in the weight class
			echo "<br><br><br> Defining RWeightPlace values...";
			$levelNo = $levelCount-1;
			$weightCount = 0;
			$newWeightCount = 0;
			$thisWeightCount = 0;
			while($levelNo >= 0){
				echo "<br><br> ...for level '$levelNo':";
				echo "<br>-----(initial weightCount = '$weightCount')";
				
				//	get fighters on this level
				$levelFighters = mysqli_query($link, "SELECT RFighter FROM rankings WHERE RWeightClass='$strWC' AND RLevel='$levelNo'");
				
				while($F = $levelFighters->fetch_row()[0]){
					echo "<br>fighter '$F' is assigned place ";
					
					//	find this fighter's RLevelPlace value
					$FLevelPlace = mysqli_query($link, "SELECT RLevelPlace FROM rankings WHERE RWeightClass='$strWC' AND RFighter='$F'")->fetch_row()[0];
					
					//	define newWeightCount as the current weightCount plus this fighter's RLevelPlace
					$thisWeightCount = $weightCount+$FLevelPlace;
					
					//	fighter f is assigned place 
					echo "'$thisWeightCount'";
					
					//	update thisWeightCount to indicate the ranking of the highest fighter passed thus far
					if($thisWeightCount > $newWeightCount){
						echo " (updating baseline weightCount '$weightCount' to the new highest value in this level '$newWeightCount')";
						$newWeightCount = $thisWeightCount;
					}
					
					//	assign thisWeightCount to fighter's RWeightPlace
					mysqli_query($link, "UPDATE rankings SET RWeightPlace='$thisWeightCount' WHERE RWeightClass='$strWC' AND RFighter='$F'");
				}
				
				$weightCount = $newWeightCount;
				
				$levelNo--;
			}
		}
		
		
		//	function to update records when X was ranked lower than Y
		function lowerWin($A, $B){
			//	define global variables used in this scope
			global $link, $strWC, $conList, $indent, $indentCount;
			
			$thisIndent = str_repeat($indent, $indentCount);
			echo "<br><br>$thisIndent LOWER FIGHTER WINS:<br>";
			
			//	determine algorithmic case of this fight:
			//		search for connection between A and B
			$conList = [$B];
			$indentCount++;
			$connectionExists = getConList($A, $B);
			$indentCount--;
			//		check entries in conList for streaks including fighter A
			$case = 0;
			if ($connectionExists!=0){
				$indentCount++;
				$case = checkConList($A, $B);
				$indentCount--;
			}
			echo "<br>Case = '$case'";
			
			
			//	modify 'matches' data:
			//		update streak bits for matches involving A
			$indentCount++;
			updFightStreaks($A, 'win');
			$indentCount--;
			//		update streak bits for matches involving B
			$indentCount++;
			updFightStreaks($B, 'lose');
			$indentCount--;
			//		add new match (TID should be an auto_increment column)
			mysqli_query($link, "INSERT INTO
						matches (MLower, MHigher, MWinner, MLowerStreak, MHigherStreak, MWeightClass)
						VALUES ('$A', '$B', '$strOutcome', 1, 1, '$strWC')");
			//		drop expired matches
			mysqli_query($link, "DELETE FROM matches WHERE MLowerStreak=0 AND MHigherStreak=0");
			
			
			//	algorithmic operations:
			//		operate based on case conditions:
			if ($case==0){
				//	execute operations for case 0
				$indentCount++;
				exec0($A, $B);
				$indentCount--;
			} else if ($case==1){
				//	execute operations for case 1
				$indentCount++;
				exec1($A, $B);
				$indentCount--;
			} else if ($case==2){
				//	execute operations for case 2
				$indentCount++;
				exec2($A, $B);
				$indentCount--;
			} else if ($case==3){
				//	execute operations for case 3
				$indentCount++;
				exec3($A, $B);
				$indentCount--;
			}
			//		redistribute affected fighters
			$indentCount++;
			$levelCount = finalPlaceLevel();
			$indentCount--;
			
			
			//	level and weight sorting:
			//		for each level, run a function to sort that level's fighters
			$levelNo = $levelCount-1;
			while($levelNo >= 0){
				//	get fighters on this level
				$levelFighters = mysqli_query($link, "SELECT RFighter FROM rankings WHERE RWeightClass='$strWC' AND RLevel='$levelNo'");
				
				$fighterNo = $levelFighters->num_rows;
				echo "<br><br>number of fighters on level '$levelNo' = '$fighterNo'";
				
				//	pass this list of fighters to a function that will sort them and modify their rankings data accordingly
				$indentCount++;
				sortLevel($levelFighters, $levelNo);
				$indentCount--;
				
				$levelNo--;
			}
			//		define fighters' places in the weight class
			echo "<br><br><br> Defining RWeightPlace values...";
			$levelNo = $levelCount-1;
			$weightCount = 0;
			$newWeightCount = 0;
			$thisWeightCount = 0;
			while($levelNo >= 0){
				echo "<br><br> ...for level '$levelNo':";
				echo "<br>-----(initial weightCount = '$weightCount')";
				
				//	get fighters on this level
				$levelFighters = mysqli_query($link, "SELECT RFighter FROM rankings WHERE RWeightClass='$strWC' AND RLevel='$levelNo'");
				
				while($F = $levelFighters->fetch_row()[0]){
					echo "<br>fighter '$F' is assigned place ";
					
					//	find this fighter's RLevelPlace value
					$FLevelPlace = mysqli_query($link, "SELECT RLevelPlace FROM rankings WHERE RWeightClass='$strWC' AND RFighter='$F'")->fetch_row()[0];
					
					//	define newWeightCount as the current weightCount plus this fighter's RLevelPlace
					$thisWeightCount = $weightCount+$FLevelPlace;
					
					//	fighter f is assigned place 
					echo "'$thisWeightCount'";
					
					//	update thisWeightCount to indicate the ranking of the highest fighter passed thus far
					if($thisWeightCount > $newWeightCount){
						echo " (updating baseline weightCount '$weightCount' to the new highest value in this level '$newWeightCount')";
						$newWeightCount = $thisWeightCount;
					}
					
					//	assign thisWeightCount to fighter's RWeightPlace
					mysqli_query($link, "UPDATE rankings SET RWeightPlace='$thisWeightCount' WHERE RWeightClass='$strWC' AND RFighter='$F'");
				}
				
				$weightCount = $newWeightCount;
				
				$levelNo--;
			}
		}
	}
	
	
	//	GENERAL:
	{
		//	function to get the level of arbitrary fighter F
		function getLevel($F){
			//	define global variables used in this scope
			global $link;
			
			$FLevel = mysqli_query($link, "SELECT RLevel FROM rankings WHERE RFighter='$F'")->fetch_row()[0];
			
			return $FLevel;
		}
		
		
		//	function to find the highest net of a specified fighter
		function getNet($bar){
			//	define global variables used in this scope
			global $link;
			
			//	find all nets for fighter bar
			$allNets = mysqli_query($link, "SELECT PNet FROM bnpairs WHERE PBar='$bar'");
			
			//	return the highest-ranked net in the allNets list (if no net exists, return 0 to indicate fighter should be placed at base)
			$hiLevel = 0;
			$retval = 0;
			while($net = $allNets->fetch_assoc()){
				$netLevel = mysqli_query($link, "SELECT RLevel FROM rankings WHERE RFighter='$net' AND RWeightClass='WAW'")->fetch_row;
				//	if net has a higher level than hiLevel, set retval equal to net and hiLevel equal to the new level
				if($netLevel > $hiLevel){
					$hiLevel = $netLevel;
					$retval = $net;
				}
			}
			
			return $retval;
		}
		
		
		//	function to return array of net IDs for arbitrary fighter F
		function getAllNets($F){
			//	define global variables used in this scope
			global $link, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION: getAllNets - '$F'";
			
			//	find all nets of fighter F
			$allNets = mysqli_query($link, "SELECT PNet FROM bnpairs WHERE PBar='$F'");
			
			return $allNets;
		}

		
		//	function to add new b/n pair
		function addBNPair($bar, $net){
			//	define global variables used in this scope
			global $link, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION: addBNPair - (bar = '$bar', net = '$net)";
			
			$pid = .5*($bar + $net)*($bar + $net + 1) + $net;
			
			//	delete any old b/n pair which placed the new barrier as the net of the new net
			$indentCount++;
			remBNPair($net, $bar);
			$indentCount--;
			
			//	find names for fighters bar and net
			$barName = mysqli_query($link, "SELECT FName FROM fighters WHERE FID='$bar'")->fetch_row()[0];
			$netName = mysqli_query($link, "SELECT FName FROM fighters WHERE FID='$net'")->fetch_row()[0];
			
			$sqlAddBNPair = "INSERT IGNORE INTO bnpairs (PID, PBar, PBar, PNet, PNetName) VALUES ('$pid', '$bar', '$barName', '$net', '$netName')";
			if(mysqli_query($link, $sqlAddBNPair)){
				echo "<br>b/n pair '$bar'/'$net' added successfully or already exists<br>";
			} else {
				echo "ERROR: Could not execute SQL command $sqlAddBNPair. " . mysqli_error($link);
			}
		}
		

		//	function to remove b/n pair
		function remBNPair($bar, $net){
			//	define global variables used in this scope
			global $link, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION: remBNPair - (bar = '$bar', net = '$net')";
			
			$pid = .5*($bar + $net)*($bar + $net + 1) + $net;
			
			if(mysqli_query($link, "SELECT PID FROM bnpairs WHERE PID='$pid'")->num_rows==0){
				echo "<br>no such b/n pair";
				return;
			}
			
			$sqlRemBNPair = "DELETE FROM bnpairs WHERE PID = '$pid'";
			if(mysqli_query($link, $sqlRemBNPair)) {
				echo "<br>$thisIndent (b/n pair removed successfully)<br>";
			} else {
				echo "ERROR: Could not execute SQL command $sqlRemBNPair. " . mysqli_error($link);
			}
		}
		
		
		//	function to update streak bits on fights involving fighter F
		function updFightStreaks($F, $newOutcome){
			//	define global variables used in this scope
			global $link, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION: updFightStreaks - '$F' , '$newOutcome'";
			
			$strHigher = 'higher';
			$strLower = 'lower';
			
			//	set streak sign for fighter F to (0) in all antiquated rows of 'matches'
			if($newOutcome=='win'){
				mysqli_query($link, "UPDATE matches SET THigherStreak = 0 WHERE THigherFighter = '$F' AND TOutcome='$strLower'");
				mysqli_query($link, "UPDATE matches SET TLowerStreak = 0 WHERE TLowerFighter = '$F' AND TOutcome='$strHigher'");
			} else {
				mysqli_query($link, "UPDATE matches SET THigherStreak = 0 WHERE THigherFighter = '$F' AND TOutcome='$strHigher'");
				mysqli_query($link, "UPDATE matches SET TLowerStreak = 0 WHERE TLowerFighter = '$F' AND TOutcome='$strLower'");
			}
		}
		
		
		//	function to return a list of fighters which are connected between a specified pair
		function getGenConList($lower, $upper){
			//	define global variables used in this scope
			global $link, $tempConList, $oldPairs, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			echo "<br>$thisIndent FUNCTION EXECUTION: getGenConList: lower = '$lower' and upper = '$upper'";
			
			//	initialize retval
			$retval = 0;
			
			
			$newPair = [$lower, $upper];
			//	check if newPair exists in oldPairs array
			foreach($oldPairs as $pair){
				if (($pair[0]==$lower) && ($pair[1]==$upper)){
					//return if this (lower, upper) pair already occurs in oldPairs array
					return($retval);
				}
			}
			//	if this is a new pair, add it to the array
			array_push($oldPairs, [$lower, $upper]);
			
			
			//	find the barriers of fighter 'lower'
			$results = mysqli_query($link, "SELECT PBar FROM bnpairs WHERE PNet = '$lower'");
			if ($results->num_rows == 0){
				//	return if fighter has no barriers
				return($retval);
			}
			
			
			$RList = [];
			while($R = $results->fetch_row()[0]){
				if ($R == $upper){
					//	tempConList is appended
					array_push($tempConList, $lower);
					//	this will now return (1), so the 'lower' of any calling function will be marked as part of the a barrier-chain
					$retval = 1;
				}
				array_push($RList, $R);
			}
			
			echo "<br>$thisIndent $indent RList:<br>$thisIndent $indent $indent";
			foreach($RList as $R){
				echo "'$R', ";
			}		
			
			foreach($RList as $R){
				$indentCount++;
				if(getGenConList($R, $upper) == 1){
					array_push($tempConList, $lower);
					$retval = 1;
				}
				$indentCount--;
			}
			
			//	remove duplicates
			$tempConList = array_unique($tempConList);
			
			return $retval;
		}
		
		
		//	function to check if there is an upward connection between arbitrary fighters
		//	unless spec is defined as TRUE (indicating a special case as in case 2's operations), no connection is identified if bar is below net
		function connectionCheck($bar, $net, $spec=FALSE){
			//	define global variables used in this scope
			global $link, $indent, $indentCount;
			
//			$thisIndent  = str_repeat($indent, $indentCount);
			
//			echo "<br>$thisIndent FUNCTION EXECUTION: connectionCheck - (bar = '$bar', net = '$net')";
			
			//	bypass further operation if bar is ranked below net (on a higher level)
			$barRank = getLevel($bar);
			$netRank = getLevel($net);
			if(($barRank >= $netRank) && !$spec){
				return(0);
			}
			
			//	find barriers of this fighter
			$results = mysqli_query($link, "SELECT PBar FROM bnpairs WHERE PNet = '$net'");
			
			if ($results->num_rows == 0){
				//	if there are no barriers, then this is a dead-end and should return 0
				return(0);
			}
			
			//	templist will hold all the returned barriers which are not B, in case the function needs to recurse
			$templist = [];
			
			while($row = $results->fetch_row()){
				if ($row[0] == $bar){
					//	a connection exists, so return 1
					return 1;
				} else {
					//	add this barrier to the templist
					array_push($templist, $row);
				}
			}
			
			foreach($templist as &$x){
				$indentCount++;
				if(connectionCheck($bar, $x[0], $spec) == 1){
					$indentCount--;
					return 1;
				}
				$indentCount--;
			}
			
			return 0;
		}
		
		
		//	function to recursively break barriers that would fall below argument F (in case 2)
		function breakLowBars($F, $prevF, $init, $firstCall){
			//	define global variables used in this scope
			global $link, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			echo "<br>$thisIndent FUNCTION EXECUTION: breakLowBars - '$F'";
			
			$strHi = 'higher';
			$strLo = 'lower';
			$barList = [];
			$checkF = 0;
			
			//	init holds the FIDs of each fighter this loop has already recursed upon
			//	firstCall indicates whether this recursion is the first time in this loop
			foreach($init as $P){
				if(($P == $F) && !$firstCall){
					//	if the recursive loop has returned to itself, return so that the calling function can finish execution
					echo "<br>$thisIndent CLOSED LOOP FOUND - F = '$F'";
					return;
				}
			}
			array_push($init, $F);
			if (mysqli_query($link, "SELECT FStreakSign FROM fighters WHERE FID='$F'")->fetch_row()[0] == -1){
				//	this fighter is on a losing streak and needn't be considered
				return;
			}
			
			//	find all of F's barriers
			$FBars = mysqli_query($link, "SELECT PBar FROM bnpairs WHERE PNet='$F'");
			//	screen F's barriers to only those which have not lost since they beat F:
			while($R = $FBars->fetch_row()[0]){
				array_push($barList, $R);
			}
			
			
			//	at this point, barList should hold all of F's barriers
			echo "<br>$thisIndent barList for fighter '$F':<br>";
			foreach($barList as $R){
				echo "'$R', ";
			}
			echo "<br>";
			
			
			//	run breakLowBars on the elements of barList
			foreach($barList as $R){
				$indentCount++;
				breakLowBars($R, $F, $init, FALSE);
				$indentCount--;
			}
			
			echo "<br>$thisIndent $indent breakLowBars sequence returned to iteration where F = '$F'";
			
			
			//	at this point, every barrier R in barList should have finished and returned from its breakLowBars function
			
			
			//	check if F counts prevF in its win streak:
			echo "<br>$thisIndent $indent Checking if the F value for the function which called this one ('$prevF') is included in the streak of this function's F value ('$F')...";
			//		find matches in which F and prevF participated
			$fightsFP = mysqli_query($link, "SELECT MLowerStreak FROM matches WHERE MWinner='$strLo' AND MLower='$F' AND MHigher='$prevF'");
			//		check if there is a fight in the results where F's streak-bit is 1 (thus indicating prevF is in F's streak)
			while($FBit = $fightsFP->fetch_row()[0]){
				if($FBit == 1){
					echo "<br>$thisIndent $indent ...prevF ('$prevF') is still counted in F's ('$F') win streak";
					$checkF = 1;
				}
			}
			$fightsPF = mysqli_query($link, "SELECT MHigherStreak FROM matches WHERE MWinner='$strHi' AND MLower='$prevF' AND MHigher='$F'");
			while($FBit = $fightsPF->fetch_row()[0]){
				if($FBit == 1){
					echo "<br>$thisIndent $indent ...prevF ('$prevF') is still counted in F's ('$F') win streak";
					$checkF = 1;
				}
			}
			
			echo "<br>$thisIndent $indent check = '$checkF'";
			
			
			//	break F's barriers R which would fall below F if F counts prevF in its win streak AND if that barrier R does not have a fight in which it beat F
			if ($checkF == 1){
				foreach($barList as $R){
					echo "<br>$thisIndent $indent Checking for upward connection between '$F's barrier '$R' and '$F' itself...";
					$indentCount++;
					$conCheck = connectionCheck($F, $R, TRUE);
					$indentCount--;
					echo "<br>$thisIndent $indent check = '$conCheck'";
					
					
					echo "<br>$thisIndent $indent Checking that R does not have F in its current win-streak...";
					$strCheck = 1;
					$fightsFR = mysqli_query($link, "SELECT MLowerStreak FROM matches WHERE MWinner='$strLo' AND MLower='$R' AND MHigher='$F'");
					$fightsRF = mysqli_query($link, "SELECT MHigherStreak FROM matches WHERE MWinner='$strHi' AND MLower='$F' AND MHigher='$R'");
					while($b = $fightsFR->fetch_row()[0]){
						if($b == 1){
							$strCheck = 0;
						}
					}
					while($b = $fightsRF->fetch_row()[0]){
						if($b == 1){
							$strCheck = 0;
						}
					}
					echo "<br>$thisIndent $indent check = '$strCheck'";
					
					if (($conCheck!=0) && ($strCheck!=0)){
						$indentCount++;
						remBNPair($R, $F);
						$indentCount--;
					}
				}
			}
		}
		
		
		//	function to place a given fighter one level higher than it currently is
		function incrLevel($F){
			//	define global variables used in this scope
			global $link, $strWC;
			
			mysqli_query($link, "UPDATE rankings SET RLevel=RLevel+1 WHERE RWeightClass='$strWC' AND RFighter='$F'");
		}
	}
	
	
	//	UNIVERSAL:
	{
		//	function to recursively execute GetBarriers query and use it to construct the conList arrays
		function getConList($net, $target){
			//	define global variables used in this scope
			global $link, $conList, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			echo "<br>$thisIndent FUNCTION EXECUTION: getConList - (net = '$net', target = '$target')";
			
			//	find the barriers of this fighter
			$results = mysqli_query($link, "SELECT PBar FROM bnpairs WHERE PNet = '$net'");
			
			//	initialize retval
			$retval = 0;
			
			if ($results->num_rows == 0){
				//	if there are no barriers, then this is a dead-end and should return 0
				return($retval);
			}
			
			//	RList will hold all of net's barriers
			$RList = [];
			
			//	check if any of the barriers is the target fighter
			while($R = $results->fetch_row()[0]){
				if ($R == $target){
					//	conList is appended
					array_push($conList, $net);
					//	this will now return (1), so the net of any calling function will know it belongs to a barrier-chain
					$retval = 1;
				}
				array_push($RList, $R);
			}
			
			
/*			echo "<br>$thisIndent $indent RList:<br>$thisIndent $indent $indent";
			foreach($RList as $R){
				echo "'$R', ";
			}
*/			
			
			//	then each barrier is searched for its own barriers
			foreach($RList as $R){
				$indentCount++;
				if(getConList($R, $target) == 1){
					//	if the function returns 1 for net R, then this net is part of a chain from net to target
					array_push($conList, $net);
					$retval = 1;
				}
				$indentCount--;
			}
			
			//	remove duplicates
			$conList = array_unique($conList);
			
			//	retval will be 1 if it is part of an barrier-connection to target, and 0 otherwise
			//	retval is never redefined as 0, so even if only one of this net's barriers connects to the target it will suffice
			return $retval;
		}
		
		
		//	function to check all intermediary fighters and return (1) if stage 1, (2) if stage 2, (3) if stage 3
		function checkConList($A, $B){
			//	define global variables used in this scope
			global $link, $conList, $FList, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION: checkConList";
			
			//	retval is hereby set to (3) - if the conditions by which cases 1 and 2 are defined do not occur, it will not be reset
			$retval = 3;
			
			//	for every fighter F between A and B, check if there is at least one record of a fight between A and F
			foreach ($conList as $F){
				
				echo "<br>$thisIndent $indent checking whether there is a record of a fight between '$A' and '$F'";
				
				
				//	* * * some of the later if-checks may be avoided by further qualifying these searches
				//	AF queries 'matches' for matches between A, as the lower fighter, and F, as the higher fighter
				$AF = mysqli_query($link, "SELECT * FROM matches WHERE MLower='$A' AND MHigher='$F'");
				if(!$AF){
					mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
				}
				//	FA queries 'matches' for matches between F, as the lower fighter, and A, as the higher fighter
				$FA = mysqli_query($link, "SELECT * FROM matches WHERE MLower='$F' AND MHigher='$A'");
				if(!$FA){
					mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
				}
				
				
				//	check that either AF or FA has at least one row:
				if (mysqli_num_rows($AF) || mysqli_num_rows($FA)){
					echo "<br>$thisIndent $indent $indent there is at least one fight recorded between '$A' and '$F'";
					
					//	if either AF or FA has at least one row of results, it is either case 1 or case 2
					if(mysqli_num_rows($AF)){
						//	for each record in the results returned to AF, check that MWinner was "higher"
						while($row = $AF->fetch_assoc()){
							if($row["MWinner"] == "higher"){
								
								$fightID = $row["MID"];
								echo "<br>$thisIndent $indent $indent $indent higher fighter '$F' won in match '$fightID'";
								
								//	for any that pass that check, check if MLowerStreak is (1)
								if($row["MLowerStreak"] == 1){
									//	this is the condition for case 1
									
									if(($retval == 3) || ($retval == 2)){
										//	if this is the first fighter satisfying case 1 for A, then FList is defined to hold only this fighter
										$FList = array($F);
									} else {
										//	otherwise, this fighter is pushed to the preexisting FList array
										array_push($FList, $F);
									}
									$retval = 1;
								} else {
									//	this is the condition for case 2
									//	but, because case 1 takes precedence, it should only redefine retval if retval is currently equal to (3)
									
									echo "<br>$thisIndent $indent $indent $indent ...and still counts this fight in its streak";
									
									if($retval == 3){
										$FList = array($F);
										$retval = 2;
									} else if ($retval == 2){
										array_push($FList, $F);
										$retval = 2;
									}
								}
							}
						}
					}
					if(mysqli_num_rows($FA)){
						//	for each record in the results returned to FA, check that MWinner was "lower"
						while($row = $FA->fetch_assoc()){
							if($row["MWinner"] == "lower"){
								
								$fightID = $row["MID"];
								echo "<br>$thisIndent $indent $indent $indent lower fighter '$F' won in fight '$fightID'";
								
								//	for any that pass that check, check if MHigherStreak is (1)
								if($row["MHigherStreak"] == 1){
									//	this is the condition for case 1
									
									if(($retval == 3) || ($retval == 2)){
										//	if this is the first fighter satisfying case 1 for A, then FList is defined to hold only this fighter
										$FList = array($F);
									} else {
										//	otherwise, this fighter is pushed to the preexisting FList array
										array_push($FList, $F);
									}
									$retval = 1;
								} else {
									//	this is the condition for case 2,
									//	but, because case 1 takes precedence, it should only redefine retval if retval is currently equal to (3)
									
									echo "<br>$thisIndent $indent $indent $indent ...and still counts this fight in its streak";
									
									if($retval == 3){
										$FList = array($F);
										$retval = 2;
									} else if ($retval == 2){
										array_push($FList, $F);
										$retval = 2;
									}
								}
							}
						}
					}
				}
			}
				
				
				
			
			//	remove duplicates from FList
			$FList = array_unique($FList);
			
			return $retval;
		}
		
		
		//	function to relocate affected fighters to above their highest nets
		function finalPlaceLevel(){
			//	define global variables used in this scope
			global $link, $indent, $strWC, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br><br>$thisIndent FUNCTION EXECUTION: finalPlaceLevel";
			
			//	place all fighters in this weight class on level 0
			mysqli_query($link, "UPDATE rankings SET RLevel=0 WHERE RWeightClass='$strWC'");
			
			$levelCounter = 0;
			//	continue to loop through levels 0-X until some level (X+1) has no fighters in it
			$levelFighters = mysqli_query($link, "SELECT RFighter FROM rankings WHERE RWeightClass='$strWC' AND RLevel='$levelCounter'");
			
			while($levelFighters->num_rows > 0){
				echo "<br>Level = '$levelCounter'";
				
				//	for every fighter on this level...
				while($F = $levelFighters->fetch_row()[0]){
					echo "<br>--fighter number '$F': new level = ";
					
					// ...find all nets to F
					$FNets = mysqli_query($link, "SELECT PNet FROM bnpairs WHERE PBar='$F'");
					
					//	for every net N of fighter F...
					while($N = $FNets->fetch_row()[0]){
						//	...check if N is on or above F's current level
						if(getLevel($N) >= getLevel($F)){
							//	if so, place F on the next level up
							incrLevel($F);
						}
					}
					
					$newLevel = getLevel($F);
//					echo "'$newLevel'";
				}
				
//				echo "<br>";
				
				//	increment counter and redefine levelFighters
				$levelCounter++;
				$levelFighters = mysqli_query($link, "SELECT RFighter FROM rankings WHERE RWeightClass='$strWC' AND RLevel='$levelCounter'");
			}
			
			//	levelCounter now holds the number of levels (including the base, 0) in this weight class
			return $levelCounter;
		}
		
		
		//	function to accept an array of mysqli_query results and sort them as fighter objects
		function sortLevel($levelFighters, $levelNo){
			//	define global variables used in this scope
			global $link, $strWC, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION: sortLevel - '$levelNo'";
			
			//	the first ranked fighter in this level will have a RLevelPlace of 1
			$levelPosition = 0;
			
			//	define an array to hold information about this level's fighters
			$FInfo = [[],[],[],[],[],[],[]];
			//	define a second array to hold information about the fighters whose streaks are negative, and should therefore be sorted separately
			$FInfoNeg = [[],[],[],[],[],[],[]];
			
			//	define each element of FInfo as an array of the form:
			//	[FID, FStreakSign, FStreakLength, FWins, FDraws, FLosses, FName]
			while($F = $levelFighters->fetch_row()[0]){
				
				//	get details of fighter F
				$FDetails = mysqli_query($link, "SELECT FStreakSign, FStreakLength, FWins, FDraws, FLosses, FName FROM fighters WHERE FID='$F'")->fetch_assoc();
				
				if($FDetails["FStreakSign"] == -1){
					//	push details to FInfoNeg array
					array_push($FInfoNeg[0], $F);
					array_push($FInfoNeg[1], $FDetails["FStreakSign"]);
					array_push($FInfoNeg[2], $FDetails["FStreakLength"]);
					array_push($FInfoNeg[3], $FDetails["FWins"]);
					array_push($FInfoNeg[4], $FDetails["FDraws"]);
					array_push($FInfoNeg[5], $FDetails["FLosses"]);
					array_push($FInfoNeg[6], $FDetails["FName"]);
				} else {
					//	push details to FInfo array
					array_push($FInfo[0], $F);
					array_push($FInfo[1], $FDetails["FStreakSign"]);
					array_push($FInfo[2], $FDetails["FStreakLength"]);
					array_push($FInfo[3], $FDetails["FWins"]);
					array_push($FInfo[4], $FDetails["FDraws"]);
					array_push($FInfo[5], $FDetails["FLosses"]);
					array_push($FInfo[6], $FDetails["FName"]);
				}
			}
			
			//	sort FInfo
			if(count($FInfo[0])>0){
				//	array_multisort
				array_multisort($FInfo[1], SORT_DESC, $FInfo[2], SORT_DESC, $FInfo[3], SORT_DESC, $FInfo[4], SORT_DESC, $FInfo[5], SORT_ASC, $FInfo[6], SORT_DESC);
			}
			//	sort FInfoNeg
			if(count($FInfoNeg[0])>0){
				//	array_multisort
				array_multisort($FInfoNeg[1], SORT_DESC, $FInfoNeg[2], SORT_ASC, $FInfoNeg[3], SORT_DESC, $FInfoNeg[4], SORT_DESC, $FInfoNeg[5], SORT_ASC, $FInfoNeg[6], SORT_DESC);
			}
			
			//	rank FInfo
			foreach($FInfo[0] as $F){
				//	for each fighter, assign levelPosition to that fighter's RLevelPlace and then iterate levelPosition
				$levelPosition++;
				echo "<br>levelPosition for fighter '$F': '$levelPosition'";
				
				mysqli_query($link, "UPDATE rankings SET RLevelPlace='$levelPosition' WHERE RFighter='$F' and RWeightClass='$strWC'");
			}
			//	rank FInfoNeg
			foreach($FInfoNeg[0] as $F){
				//	for each fighter, assign levelPosition to that fighter's RLevelPlace and then iterate levelPosition
				$levelPosition++;
				echo "<br>levelPosition for fighter '$F': '$levelPosition'";
				
				mysqli_query($link, "UPDATE rankings SET RLevelPlace='$levelPosition' WHERE RFighter='$F' and RWeightClass='$strWC'");
			}
		}
	}

	
	//	SPECIAL:
	{
		//	function to define the EList for case 1
		function makeEList($EList){
			//	define global variables used in this scope
			global $link, $conList, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			echo "$thisIndent FUNCTION EXECUTION: makeEList:<br>$thisIndent $indent";
			foreach($EList as $E){
				echo "'$E', ";
			}
			echo "<br>";
			
			
			$strHi = 'higher';
			$strLo = 'lower';
			
			//	tempEList will ultimately be copied over EList
			$tempEList = $EList;
			
			foreach($EList as $E){
				//	get E's nets N
				$ENets = mysqli_query($link, "SELECT PNet FROM bnpairs WHERE PBar='$E'");
				
				while($N = $ENets->fetch_row()[0]){
					//	check if N is on a losing streak
					$NStreakSign = mysqli_query($link, "SELECT FStreakSign FROM fighters WHERE FID='$N'")->fetch_row()[0];
					
					if($NStreakSign == -1){
						//	check if E's net N is in the connection list between A and B
						$NCheck = in_array($N, $conList);
						
						if($NCheck){
							//	check if E's net N counts E in its losing streak
							//	EN - N was higher ranked in a match with 'win' outcome
							$fightsEN = mysqli_query($link, "SELECT MHigherStreak FROM matches WHERE MLower='$E' AND MHigher='$N' AND MWinner='$strLo'");
							while($checkBit = $fightsEN->fetch_row()[0]){
								if($checkBit == 1){
									//	add this fighter N to the tempEList
									array_push($tempEList, $N);
								}
							}
							//	NE - N was lower ranked in a match with 'lose' outcome
							$fightsEN = mysqli_query($link, "SELECT MLowerStreak FROM matches WHERE MLower='$N' AND MHigher='$E' AND MWinner='$strHi'");
							while($checkBit = $fightsEN->fetch_row()[0]){
								if($checkBit == 1){
									//	add this fighter N to the tempEList
									array_push($tempEList, $N);
								}
							}
						}
					}
				}
			}
			
			//remove duplicates
			$tempEList = array_unique($tempEList);
			
			//	check if tempEList is equal to EList
			if($EList == $tempEList){
				return($EList);
			}else{
				//	run next iteration
				$indentCount++;
				$EList = makeEList($tempEList);
				$indentCount--;
				return($EList);
			}
		}
		
		
		//	function to execute operations for case 0
		function exec0($A, $B){
			//	define global variables used in this scope
			global $link, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION: exec0";
			
			
			//	modify 'bnpairs' data:
			//		generate new pair for A and B
			$indentCount++;
			addBNPair($A, $B);
			$indentCount--;
			
			//		break A's barriers R which would now be placed below A
			$ABars = mysqli_query($link, "SELECT PBar FROM bnpairs WHERE PNet='$A'");
			while($R = $ABars->fetch_row()[0]){
				//	check if R connects upward to B
				if (connectionCheck($B, $R, TRUE) == 1){
					//	remove the b/n pair R/F
					remBNPair($R, $A);
				}
			}
			
			//		* * * add functionality to ensure A doesn't end up above any of its barriers
		}
		
		
		//	function to execute operations for case 1
		function exec1($A, $B){
			//	define global variables used in this scope
			global $link, $FList, $conList, $tempConList, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION: exec1";
			
			//	FList holds the fighters which are both on A's losing streak and are connected between A and B
			echo "<br>$thisIndent $indent FList:<br>$thisIndent $indent ";
			foreach($FList as $F){
				echo "'$F', ";
			}
			echo "<br>";
			
			//	EList holds the fighters, starting with B, which are connected to A and count B or some other element of EList in their losing streak
			$EList = [$B];
			
			
			//	generate EList:
			$EList = makeEList($EList);
			
			
			//	modify 'bnpairs' data:
			//		generate new pair for A and B (removing the pair for B and A)
			$indentCount++;
			addBNPair($A, $B);
			$indentCount--;
			
			
			//		remove bnpairs where net is in FList or upward and bar is in EList
			foreach($FList as $F){
				//	search upward connections which are on the conList
				getGenConList($F, $B);
				$upList = $tempConList;
				
				echo "<br>$thisIndent $indent $indent upList for FList element '$F':<br>";
				foreach($upList as $U){
					echo "'$U', ";
				}
				echo "<br>";
				
				foreach($upList as $G){
					if((in_array($G, $conList)) && !(in_array($G, $EList))){
						//	get G's barriers
						$GBars = mysqli_query($link, "SELECT PBar FROM bnpairs WHERE PNet='$G'");
						while($R = $GBars->fetch_row()[0]){
							//	check if R is in the EList
							if(in_array($R, $EList)){
								//	break G's barrier R
								remBNPair($R, $G);
							}
						}
					}
				}
			}
			
			
			//		remove bnpairs where net is A and bar will fall below A
			echo "<br>$thisIndent $indent breaking barriers of fighter '$A'...";
			$ABars = mysqli_query($link, "SELECT PBar FROM bnpairs WHERE PNet='$A'");
			while($R = $ABars->fetch_row()[0]){
				echo "<br>$thisIndent $indent $indent checking if '$R' is in EList...";
				if(in_array($R, $EList)){
					echo "<br>$thisIndent $indent $indent $indent '$R' is in EList, breaking b/n pair ('$R'/'$A')";
					remBNPair($R, $A);
				}
			}
		}
		
		
		//	function to execute operations for case 2
		function exec2($A, $B){
			//	define global variables used in this scope
			global $link, $FList, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION: exec2";
			
			//	FList holds the fighters which both have A on their win streaks and are connected between A and B
			
			echo "<br>$thisIndent $indent FList:<br>";
			foreach($FList as $F){
				echo "'$F', ";
			}
			echo "<br>";
			
			//	modify 'bnpairs' data:
			//		add A/B pair to bnpairs
			$indentCount++;
			addBNPair($A, $B);
			$indentCount--;
			
			//		break all of F's barriers R that WILL end up below F
			foreach($FList as $F){
				$indentCount++;
				breakLowBars($F, $A, [$F, $A, $B], TRUE);
				$indentCount--;
			}
		}
		
		
		//	function to execute operations for case 3
		function exec3($A, $B){
			//	define global variables used in this scope
			global $link, $FList, $indent, $indentCount;
			
			$thisIndent  = str_repeat($indent, $indentCount);
			
			echo "<br>$thisIndent FUNCTION EXECUTION:";
			
			//	modify 'bnpairs' data:
			//		generate new pair for A and B
			$indentCount++;
			addBNPair($A, $B);
			$indentCount--;
			
			//		break A's barriers R that connect A with B
			$ABars = mysqli_query($link, "SELECT PBar FROM bnpairs WHERE PNet='$A'");
			while($R = $ABars->fetch_row()[0]){
				//	check if R connects upward to B
				if (connectionCheck($B, $R) == 1){
					//	remove the b/n pair R/F
					remBNPair($R, $A);
				}
			}
			
			//		break A's barriers R which would now be placed below A
			$ABars = mysqli_query($link, "SELECT PBar FROM bnpairs WHERE PNet='$A'");
			while($R = $ABars->fetch_row()[0]){
				//	check if R connects upward to B
				if (connectionCheck($B, $R, TRUE) == 1){
					//	remove the b/n pair R/F
					remBNPair($R, $A);
				}
			}
		}
	}
}


//	FINAL OPERATIONS:
{
	//	determine information about each fighter:
	//		query fighter X's streak sign
	$signX = mysqli_query($link, "SELECT FStreakSign FROM fighters WHERE FID = '$X'")->fetch_row()[0];
	//		query fighter Y's streak sign
	$signY = mysqli_query($link, "SELECT FStreakSign FROM fighters WHERE FID = '$Y'")->fetch_row()[0];
	//		query fighter X's weight rank
	$rankX = mysqli_query($link, "SELECT RWeightPlace FROM rankings WHERE RFighter='$X'")->fetch_row()[0];
	//		query fighter Y's weight rank
	$rankY = mysqli_query($link, "SELECT RWeightPlace FROM rankings WHERE RFighter='$Y'")->fetch_row()[0];
	
	
	//	modify 'fighters' data:
	//		update streak signs for fighters X and Y
	if($signX != 1){
		mysqli_query($link, "UPDATE fighters SET FStreakSign = 1 WHERE FID = '$X'");
		mysqli_query($link, "UPDATE fighters SET FStreakLength = 1 WHERE FID = '$X'");
	} else {
		mysqli_query($link, "UPDATE fighters SET FStreakLength = FStreakLength+1 WHERE FID = '$X'");
	}
	if($signY !=-1){
		mysqli_query($link, "UPDATE fighters SET FStreakSign = -1 WHERE FID = '$Y'");
		mysqli_query($link, "UPDATE fighters SET FStreakLength = 1 WHERE FID = '$Y'");
	} else {
		mysqli_query($link, "UPDATE fighters SET FStreakLength = FStreakLength+1 WHERE FID = '$Y'");
	}
	//		update overall records
	mysqli_query($link, "UPDATE fighters SET FWins = FWins + 1 WHERE FID = '$X'");
	mysqli_query($link, "UPDATE fighters SET FLosses = FLosses + 1 WHERE FID = '$Y'");
	
	
	//	determine whether X or Y was initially the lower-ranked fighter:
	//	as rankX increases in value, X becomes a lower value
	if ($rankX > $rankY){
		//	set the outcome string:
		$strOutcome = 'lower';
		//	run code to update records for fights where the lower-ranked fighter wins:
		lowerWin($X, $Y);
	} else {
		//	set the outcome string:
		$strOutcome = 'higher';
		//	run code to update records for fights where the higher-ranked fighter wins:
		higherWin($X, $Y);
	}
	
	
	//	close connection
	mysqli_close($link);
	
	echo "<br><br>FINAL OPERATIONS: PASSED";
}


?>


