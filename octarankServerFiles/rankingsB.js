var WCInfo = {
	fighterCount: 0,
	levelCount: 0,
	FData: {
		fid: [0],
		name: ['name'],
		sSign: [0],
		sLength: [0],
		wins: [0],
		losses: [0],
		draws: [0],
		bars: [[0]],
		nets: [[0]]
	},
	strWC: 'und'
}


window.addEventListener('load',function(){
	printTitles();
	dispRankings();
	initListeners();
});


//	populate the pageTitle and subTitle elements of the HTML form, or throw an error
function printTitles(){
	var strWC = parent.document.URL.substring(parent.document.URL.indexOf('?')+7, parent.document.URL.length);
	
	var strTitle = "Undefined";
	
	//	check that strWC is valid, and interpret it as the appropriate title string:
	if(strWC == "HW"){
		strTitle = "Men's Heavy-Weight Rankings";
		WCInfo.strWC = 'HW';
	}/*		* * * other weight classes go here
	else if(strWC == "") {
		
	}*/else if(strWC == "WAW"){
		strTitle = "Women's Atom-Weight Rankings";
		WCInfo.strWC = 'WAW';
	}
	
	if(strTitle == "Undefined"){
		//	* * * report an error, and query to database will not work
	}
	
	//	define the page's titles
	var pageTitle = document.getElementById('pageTitle');
	var subTitle = document.getElementById('subTitle');
	pageTitle.innerHTML = strTitle;
	subTitle.innerHTML = strTitle;
}



//	write the ranking data to the html page's 'rankDisplay' ul element
function dispRankings(){
	
	//	rankDisplay should receive ul-type children defined by the function on the JSON data
	var rankDisplay = document.getElementById('rankDisplay');
	
	$.getJSON('rankingsC.php?strWC='+WCInfo.strWC, 0, function(rankData){
		/*	rankData columns - 
			0	RWeightPlace
			1	RLevelPlace
			2	RLevel
			3	FID
			4	FName
			5	FStreakSign
			6	FStreakLength
			7	FWins
			8	FLosses
			9	FDraws
		*/
		
		
		//	define a new ul element for the first (highest) level
		var newList = document.createElement('ul');
		
		//	operate upon the data imported from the database
		Object.keys(rankData).forEach(function(rankKey){
			//	use new information passed from the PHP script to populate the WCInfo.FData object
			if(WCInfo.FData.name == ['name']){
				//	if WCInfo.FData.name is still ['name'], then perform the following operations
				WCInfo.FData.fid = [rankData[rankKey][3]];
				WCInfo.FData.name = [rankData[rankKey][4]];
				WCInfo.FData.sSign = [rankData[rankKey][5]];
				WCInfo.FData.sLength = [rankData[rankKey][6]];
				WCInfo.FData.wins = [rankData[rankKey][7]];
				WCInfo.FData.losses = [rankData[rankKey][8]];
				WCInfo.FData.draws = [rankData[rankKey][9]];
			} else {
				//	otherwise, push the rankData values to the appropriate arrays without overwriting content
				WCInfo.FData.fid.push(rankData[rankKey][3]);
				WCInfo.FData.name.push(rankData[rankKey][4]);
				WCInfo.FData.sSign.push(rankData[rankKey][5]);
				WCInfo.FData.sLength.push(rankData[rankKey][6]);
				WCInfo.FData.wins.push(rankData[rankKey][7]);
				WCInfo.FData.losses.push(rankData[rankKey][8]);
				WCInfo.FData.draws.push(rankData[rankKey][9]);
			}
			
			
			//	assign newItem objects as children to newList, and assign newList objects as children to rankDisplay
			
			//	define the new list item (as the FID of the keyed fighter)
			var newItem = document.createElement('li');
			var itemValue = rankData[rankKey][4] + " (ID:" + rankData[rankKey][3] + ")";
			newItem.appendChild(document.createTextNode(itemValue));
			
			//	assign an html ID for this newItem
			newItem.setAttribute('id', 'fighter'+rankData[rankKey][3]);
			//	assign mouseup event
			newItem.setAttribute('onmouseup', "onNameClick(this.id);");
			
			
			//	? assign mouseover event (mouseover should change format of name (color? italics?))
			
			
			//	increment the fighterCount in WCInfo
			WCInfo.fighterCount++;
			
			if(rankData[rankKey][1] == 1){
				//	check that newList has at least one child node:
				if(newList.hasChildNodes()){
					//	add the current newList to the div
					rankDisplay.appendChild(newList);
					//	increment the levelCount in WCInfo
					WCInfo.levelCount++;
					
					//	redefine newList
					newList = document.createElement('ul');
					
					//	append this newList with a first element indicating level
					var newLevel = "Level " + rankData[rankKey][2];
					newList.appendChild(document.createTextNode(newLevel));
					
					//	append newList with element indicating fighter
					newList.appendChild(newItem);
					//	give newList an ID
					newList.setAttribute('id', 'level'+rankData[rankKey][2]);
				} else {
					//	this is presumably the first iteration
					//	give newList an ID
					newList.setAttribute('id', 'level'+rankData[rankKey][2]);
					//	append newList with a first element indicating level
					var newLevel = "Level " + rankData[rankKey][2];
					newList.appendChild(document.createTextNode(newLevel));
					
					//	append newList with element indicating fighter
					newList.appendChild(newItem);
				}
			} else {
				//	otherwise, continue defining the current list item
				newList.appendChild(newItem);
			}
		});
		
		//	append last newList to the rankData div
		rankDisplay.appendChild(newList);
		//	increment the levelCount in WCInfo
		WCInfo.levelCount++;
	});
	
	//	re-sort the WCInfo.FData object by fighter ID:
//	WCInfo.FData
	function tempSort(){
		return [].slice.call(arguments).sort(function(index){
			return function(a, b){
				return (a[3] === b[3] ? 0 : (a[3] < b[3] ? -1 : 1));
			};
		});
	};
};


//	?	define listeners for elements generated by dispRankings function
function initListeners(){
	//	?	add listener for escape key-press
	//		this doesn't work
	document.keyup = function(e){
		if(e.keyCode == 27){
			onEscapePress();
		}
	}
}


//	perform operations for clicking on a fighter's name
function onNameClick(elemID){
	//	get FID information from elemID
	var numpattern = /\d+/g;
	var fid = elemID.match(numpattern);
	console.log(fid);
	
	
	//	use the elemID to construct the following parameters from the WCInfo.FData object
	var paramPanel, paramColors;
	/*	paramPanel should hold...
			elemID
			WCInfo.FData.name
			WCInfo.FData.sSign
			WCInfo.FData.sLength
			WCInfo.FData.wins
			WCInfo.FData.losses
			WCInfo.FData.draws
	*/
	paramPanel = {
		fid: fid[0],
		nam: WCInfo.FData.name[fid],
		ssi: WCInfo.FData.sSign[fid],
		sle: WCInfo.FData.sLength[fid],
		win: WCInfo.FData.wins[fid],
		los: WCInfo.FData.losses[fid],
		dra: WCInfo.FData.draws[fid]
	};
	//	* * * find a way to get bars and nets into WCInfo.FData
	/*	paramColors should hold...
			elemID
			WCInfo.FData.bars
			WCInfo.FData.nets
	*/
	paramColors = {
		fid: fid[0]/*,
		WCInfo.FData.bars[fid],
		WCInfo.FData.nets[fid]
		*/
	};
	
	
	
	//	?	add bold effect to this list item (maybe change color as well?)
	
	
	
	//	revert all text colors to default
	inactBNcolors();
	
	//	close all subordinate panels
	inactFighterPanels();
	
	//	change text color for html elements representing barriers or nets to the selected fighter
	activBNcolors(paramColors);
	
	//	open a new panel for selected fighter
	activFighterPanels(paramPanel);
}


//	perform operations to clear information relevant to previously selected fighter (if any)
function onEscapePress(){
	inactBNcolors();
	inactFighterPanels();
}


//	revert text colors to default
function inactBNcolors(){
	console.log("running inactBNcolors");
	
	//	* * * reset to default all CSS and formatting on this page
}


//	close all open panels
function inactFighterPanels(){
	console.log("running inactFighterPanels");
	
	//	* * * delete all elements in the panelDiv
	panelDiv.removeChild(panelDiv.lastChild);
}


//	* * * change text color for page elements corresponding to fighter's barriers and nets
function activBNcolors(paramColors){
	/*	Color code:
	BLACK - default
	GREEN - selected/active fighter
	RED - barrier to selected fighter via case 1 with secondary fighter
	ORANGE - barrier to selected fighter
	YELLOW - barrier to selected fighter via case 2 with secondary fighter
	BLUE - net to selected fighter
	*/
	
	console.log("running activBNcolors");
	
	//	paramColors should hold a list of barriers at index 0 and a list of nets at index 1
	
	
}


//	open panel with information on selected fighter
function activFighterPanels(paramPanel){
	console.log("running activFighterPanels");
	
	//	paramPanel should hold various data about the selected fighter
	
	//	add a panel element to the panelDiv div
	var infoPanel =
		'<div class="panel panel-info">'+
		'</div> ' +
		'<div class="panel-body">' +
			'<div class="row">' +
				'<p>Fighter Name: ' +
					paramPanel["nam"] +
				'</p>'+
				'<p>Streak: ' +
					paramPanel["ssi"] + ' (times) ' + paramPanel["sle"] + 
				'</p>'+
				'<p>Wins: ' +
					paramPanel["win"] +
				'</p>'+
				'<p>Losses: ' +
					paramPanel["los"] +
				'</p>'+
				'<p>Draws: ' +
					paramPanel["dra"] +
				'</p>'+
			'</div>'+
		'</div>';

	$("#panelDiv").append(infoPanel);
}


