<head>
<meta http-equiv="X-UA-Compatible" content="IE=Edge" />
<link rel="stylesheet" type="text/css" href="Scripts/tooltipster.css">
<link rel="stylesheet" type="text/css" href="Scripts/jquery.handsontable.css">
<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css">
<link type="font" name="mytimes" subtype="TrueType" src="calibri.ttf" src-bold="calibrib.ttf" src-italic="calibrii.ttf" src-bolditalic="calibribi.ttf"/>
<style>
	#dataTableEdit.wordwrap  td {
		overflow: visible !important;
		white-space:normal !important;
	}

	.tooltip-theme {
		border-radius: 5px; 
		border: 0px solid #000;
		background: #4c4c4c;
		color: #fff;
	}
	.tooltip-theme .tooltipster-content {
		font-size: 14px;
		line-height: 16px;
		padding: 8px 10px;
	}
	.tooltip-item {
		display: inline-block;
		*display: inline;
		zoom: 1;
	}
	.tooltip-user {
		font-weight: 200;
		width:80px;
	}	
	.tooltip-date {
		font-weight: 800;
		padding-right: 10px;
	}
	.tooltip-value{
	}
	
	.tooltip-oldValue {
		color:#EA7272;
	}
	.tooltip-newValue {
		color:#7AE395;
	}
	
	.handsontable .dragdealer .handle {
		background: none repeat scroll 0 0 #2a2b2e  !important;
		height: 9px;
		position: absolute;
		width: 9px;
	}
	

	body{
	    margin: 0;
		font-family: Calibri, Georgia, Palatino Linotype, Palatino, Tahoma, Arial, SunSans-Regular, sans-serif; 
	}

	.changed {
		font-size:1.1em;
	}
	
	.changed-row {
		background: none repeat scroll 0 0 #E8C4C4   !important;
		color: #2D2D2D  !important
	}

	.changed-pre {
		background: none repeat scroll 0 0 #FF7D7D  !important;
		color: #2D2D2D  !important;
	}
	.changed-self{
		background: none repeat scroll 0 0 #80e486 !important;
		color: #2D2D2D  !important;
	}
	
	.container {
		height:100%;
	}
	
	#toolbar {
		background: none repeat scroll 0 0 #2a2b2e;
		color: #bdc1cb;
		height:40px;
		margin:0px;
	}
	#getEncounters {
		text-align: right;
		font-size:1.5em;
		margin:0px;
		padding:0px;
		padding-right:10px;
	}
	

	#panel-explain {
		background: none repeat scroll 0 0 #2a2b2e;
		color: #bdc1cb;
		height: 56px;
		left: 150px;
		padding: 10px;
		position: absolute;
		top: 100px;
		width: 200px;
	}
	
	.handsontable td {
		 background-color: #FFFFFF;
		 border-bottom: 1px solid #000000 !important;
       overflow: hidden !important;
       text-overflow: ellipsis !important;
       white-space:nowrap !important;
  
	}
	
	.container {
		background: none repeat scroll 0 0 #dcdcdc;
	}
	
	.htCore {
		border-bottom: 1px solid black !important;
	}
</style>
</head>
<body>
	<div class="container">
		<div id="dataTableImport"></div>
		<div id="dataTableEdit"></div>
		<div class="toolbar" id="toolbar">
			<div id="getEncounters" class="button button-getEncounters">Fetch Data for Encounters</div>
		</div>
		<!--div class="panel panel-explain" id="panel-explain">Paste/Type List of Encounters</div-->
	</div>
</body>

<script src="Scripts/jquery-1.11.2.min.js"></script>
<!--script src="//cdnjs.cloudflare.com/ajax/libs/handsontable/0.10.3/jquery.handsontable.full.js"></script-->
<script src="Scripts/jquery.handsontable.full.js"></script>
<script src="Scripts/jquery.tooltipster.min.js"></script>
<script>
	
	$(document).ready(function(){
		$.ajaxSetup({ cache: false });
		
		var $dataTableImport = $('#dataTableImport');
		
		var colHeadersOrg = [
            "EncounterNumber",
            "Chart Number",
			"Age Number",
            "AgeCode",
            "Age Category",
            "Admit Date",
            "Discharge Date",
            "Discharge Nursing Location",
            "Main Provider",
            "MRDx",
            "MRDx Desc",
			"CMG",
			"CMG Desc",
			"HIG",
			"HIG Desc",
			"HIG Weight",
			"HIG LS Trim",
			"Team",
			"Team Desc",
			"LOS",
			"Acute LOS",
			"CMG_ELOS",
			"HIG_ELOS",
			"ALC LOS",
			"RIW",
			"RIL",
			"Typical/Atypical",
			"CMG Year",
			"HCN",
			"SCU Visit",
			"DC Disp",
			"Comments"
        ];
		
		var maxed = false
		, resizeTimeout
		, availableWidth
		, availableHeight
		, $window = $(window)
		
		var calculateSize = function () {
			//var offset = $dataTableEdit.offset();
			availableWidth = $window.width(); // - offset.left + $window.scrollLeft();
			availableHeight = $window.height() - $('#toolbar').height(); // - offset.top + $window.scrollTop();
		};
		maxed = true;
		
		$dataTableImport.handsontable({
			data: [
				[2010418215],[2012169540],[2012189616],[2012227218],[2012308212],[2013459419],[2014093240],[2014124794],[2014165668],[2014226682],[2014272077],[2014301665],[2014303970],[2014311874],
				[2014311876],[2014314591],[2014314618],[2014316672],[2014319050],[2014331308]
			],
			startRows: 10,
			startCols: 1,
			minSpareRows: 5,
			colHeaders : colHeadersOrg,
			width: function () {
				if (maxed && availableWidth === void 0) {
					calculateSize();
				}
				return maxed  ?  availableWidth *1 : 300;
			},
			height: function () {
				if (maxed && availableHeight === void 0) {
					calculateSize();
				}
				return maxed  ?  availableHeight*1: 300;
			}
		});	
		
		$('#getEncounters').on('click',function(){
			var encounterList = $dataTableImport.handsontable('getDataAtCol','0');
			$.post("getEncounters.php", {encounterList: encounterList}, function(data){
				$('#dataTableImport').remove();
				$('#getEncounters').remove();
				$('#panel-explain').remove();
				
				calculateSize();
				
				highlightRenderer = function(instance, td, row, col, prop, value, cellProperties) {
					Handsontable.renderers.TextRenderer.apply(this, arguments);
					
					changes = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changes');
					for (var i=1, len=changes; i<=len; i++){
						$(td).addClass('changed-row');
						var field = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_Field' + i)
						if (field == prop){
							var changedID = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_ID' + i)
							var changedIDList = [];
							
							var oldValue = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_Old_Value' + i)
							var newValue = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_New_Value' + i)
							var user = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_Reviewer_Name' + i)
							var date = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_Date_Of_Change' + i)
							$(td).addClass('changed');
							$(td).addClass('changed-pre');
							$(td).tooltipster({
								theme: 'tooltip-theme'
							});
							var tooltipContent = $(td).tooltipster('content');
							if (tooltipContent == null) { 
								tooltipContent = $('<div></div>');
							} else {
								changedIDList = $(td).tooltipster('content').children().map(function(){return $(this).attr("changeID");}).get();
							}
							var currChange = "<div class='tooltip' id='change-" + changedID + "' changeID=" + changedID + "><div class='tooltip-item tooltip-date'>" + date + "</div><div class='tooltip-item tooltip-user'>" + user + "</div><div class='tooltip-item tooltip-value tooltip-oldValue'>" + oldValue + "</div> <i class='fa fa-long-arrow-right'></i> <div class='tooltip-item tooltip-value tooltip-newValue'>" + newValue + "</div></div>";
							//if ($.inArray(changedID, $dataTableEdit.handsontable("getCellMeta",row,col).changedIDArr) == -1){							
							if ($.inArray(changedID, changedIDList) == -1){							
							$(tooltipContent).append(currChange);
							}
							$(td).tooltipster('content', $(tooltipContent));
							/*if ($dataTableEdit.handsontable("getCellMeta",row,col).changedIDArr != null){
								$dataTableEdit.handsontable("getCellMeta",row,col).changedIDArr.push(changedID);
							} else {
								$dataTableEdit.handsontable("getCellMeta",row,col).changedIDArr = [changedID];
							}*/							
						}
					}
					
					if (cellProperties.changed) {
						$(td).addClass('changed');
						$(td).addClass('changed-self');
					}
				};
				Handsontable.renderers.registerRenderer('highlightRenderer', highlightRenderer);
				var colWidthsOrg = [100, 100, 50, 50, 150,100,100,100,200,100,500,100,500,100,500,100,100,100,300,100,100,100,100,100,100,100,100,100,00,100,100,500,200,200,110,110,110,110,150,200,100];
				
				var $dataTableEdit = $('#dataTableEdit');
				$dataTableEdit.handsontable({
					data:data,
					wordwrap: false,
					strechH: 'auto',
					strechW: 'auto',
					scrollH: 'auto',
					scrollV: 'auto',
					manualColumnResize: true,
					colHeaders: colHeadersOrg,
					fixedColumnsLeft: 2,
					columns: [
						{data: 'EncounterNumber', name:'EncounterNumber', renderer:"highlightRenderer"},
						{data: 'ChartNumber', renderer:"highlightRenderer"},
						{data: 'AgeNumber', renderer:"highlightRenderer"},
						{data: 'AgeCode', renderer:"highlightRenderer"},
						{data: 'CMGAgeCategoryDesc', renderer:"highlightRenderer"},
						{data: 'AdmissionDateFormated', renderer:"highlightRenderer"},
						{data: 'DischargeDateFormated', renderer:"highlightRenderer"},
						{data: 'DischNursingAreaUnitLocation', renderer:"highlightRenderer"},
						{data: 'AttendingPhysicianDesc', renderer:"highlightRenderer"},
						{data: 'Diagnosis_Code', renderer:"highlightRenderer"},
						{data: 'Diagnosis_Description', renderer:"highlightRenderer"},
						{data: 'CMG', renderer:"highlightRenderer"},
						{data: 'CMG_Description', renderer:"highlightRenderer"},
						{data: 'HIG_Code', renderer:"highlightRenderer"},
						{data: 'HIG_Description', renderer:"highlightRenderer"},
						{data: 'HIG_Weight', renderer:"highlightRenderer"},
						{data: 'HIG_LongStayTrimDays', renderer:"highlightRenderer"},
						{data: 'BasicOption19', renderer:"highlightRenderer"},
						{data: 'BasicOption19Desc', renderer:"highlightRenderer"},
						{data: 'LOSDays', renderer:"highlightRenderer"},
						{data: 'AcuteLOS', renderer:"highlightRenderer"},
						{data: 'CMG_ELOS', renderer:"highlightRenderer"},
						{data: 'HIG_ELOS', renderer:"highlightRenderer"},
						{data: 'ALCLOS', renderer:"highlightRenderer"},
						{data: 'RIW', renderer:"highlightRenderer"},
						{data: 'InpatientRILevel', renderer:"highlightRenderer"},
						{data: 'InpatientRIWAtypicalCodeDesc', renderer:"highlightRenderer"},
						{data: 'CMG_Year', renderer:"highlightRenderer"},
						{data: 'HealthCareNumber', renderer:"highlightRenderer"},
						{data: 'HIG_SCUFlag', renderer:"highlightRenderer"},
						{data: 'DischargeDisposition', renderer:"highlightRenderer"},
						{data: 'Comments', renderer:"highlightRenderer"}
					],
					scrollV: 'auto',
					width: function () {
						if (maxed && availableWidth === void 0) {
							calculateSize();
						}
						return maxed  ?  availableWidth *1 : 300;
					},
					height: function () {
						if (maxed && availableHeight === void 0) {
							calculateSize();
						}
						return maxed  ?  availableHeight*1 + 10: 300;
					},
					colWidths : colWidthsOrg,
					startRows: 5,
					startCols: 3,
					minSpareRows: 0,
					beforeRender: function(forced){
						if( $('td').tooltipster().length > 0) {
							$('td').tooltipster().remove();;
						}
					},
					afterChange: function(change,source){
						var changeArr = change[0];
						var row = changeArr[0];
						var encounterNumber = $dataTableEdit.handsontable('getDataAtRowProp',row,'EncounterNumber');
						var field = changeArr[1];
						var oldValue = changeArr[2];
						var newValue = changeArr[3];
						if (oldValue != newValue){
							var parameters = {
								Reviewer_Name: "Testing User",
								Encounter_Number: encounterNumber,
								Field: field,
								Old_Value: oldValue,
								New_Value: newValue
							}
							$.post("change.php",{parameters:parameters},function(result){
								var colComp = $dataTableEdit.handsontable("propToCol",field);
								$dataTableEdit.handsontable("getCellMeta",row,colComp).changed=true;
								$dataTableEdit.handsontable("render");
							});
						}
					}
				});
			})
		});
	});
	
	
	
	
</script>
