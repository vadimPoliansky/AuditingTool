<head>
<link rel="stylesheet" type="text/css" href="Scripts/tooltipster.css">
<link rel="stylesheet" type="text/css" href="Scripts/jquery.handsontable.css">
<style>

	.changed {
		font-size:1.1em;
	}

	.changed-pre {
		background: none repeat scroll 0 0 #ff6363 !important;
	}
	.changed-self{
		background: none repeat scroll 0 0 #80e486 !important;
	}
</style>
</head>
<body>
    <div id="dataTableImport"></div>
	<div id="dataTableEdit"></div>
</body>

<div id="getEncounters" class="button button-getEncounters">Get</div>

<script src="Scripts/jquery-1.11.2.min.js"></script>
<script src="Scripts/jquery.handsontable.full.min.js"></script>
<script src="Scripts/jquery.tooltipster.min.js"></script>
<script>
	
	$(document).ready(function(){
		var $dataTableImport = $('#dataTableImport');
		
		$dataTableImport.handsontable({
			startRows: 10,
			startCols: 1,
			colHeaders: true,
			minSpareRows: 1
		});	
		
		$('#getEncounters').on('click',function(){
			var encounterList = $dataTableImport.handsontable('getDataAtCol','0');
			$.post("getEncounters.php", {encounterList: encounterList}, function(data){
				$dataTableImport.handsontable('destroy');
				
				highlightRenderer = function(instance, td, row, col, prop, value, cellProperties) {
					Handsontable.renderers.TextRenderer.apply(this, arguments);
					
					changes = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changes');
					for (var i=1, len=changes; i<=len; i++){
						var field = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_Field' + i)
						if (field == prop){
							var oldValue = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_Old_Value' + i)
							var newValue = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_New_Value' + i)
							var user = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_Reviewer_Name' + i)
							var date = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_Date_Of_Change' + i)
							var changedID = $dataTableEdit.handsontable('getDataAtRowProp',row,'Changed_ID' + i)
							$(td).addClass('changed');
							$(td).addClass('changed-pre');
							$(td).tooltipster();
							var tooltipContent = $(td).tooltipster('content');
							if (tooltipContent == null) { 
								tooltipContent = ''; 
							}
							var currChange = "<div>" + user + " changed " + field + " on " + date + " from " + oldValue + " to " + newValue  + "</div>";
							$(tooltipContent).append(currChange);
							$(td).tooltipster('content', $(tooltipContent));
							if ($dataTableEdit.handsontable("getCellMeta",row,col).changedIDArr != null){
								$dataTableEdit.handsontable("getCellMeta",row,col).changedIDArr.push(changedID);
							} else {
								$dataTableEdit.handsontable("getCellMeta",row,col).changedIDArr = [];
							}
							console.log($dataTableEdit.handsontable("getCellMeta",row,col).changedIDArr);
						}
					}
					
					if (cellProperties.changed) {
						$(td).addClass('changed');
						$(td).addClass('changed-self');
					}
				};
				Handsontable.renderers.registerRenderer('highlightRenderer', highlightRenderer);
				
				var $dataTableEdit = $('#dataTableEdit');
				$dataTableEdit.handsontable({
					data:data,
					strechH: 'auto',
					strechW: 'auto',
					scrollH: 'auto',
					scrollV: 'auto',
					width:500,
					height:700,
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
						{data: 'BasicOption19', renderer:"highlightRenderer"},
						{data: 'BasicOption19Desc', renderer:"highlightRenderer"},
						{data: 'LOSDays', renderer:"highlightRenderer"},
						{data: 'AcuteLOS', renderer:"highlightRenderer"},
						{data: 'CMG_ELOS', renderer:"highlightRenderer"},
						{data: 'ALCLOS', renderer:"highlightRenderer"},
						{data: 'RIW', renderer:"highlightRenderer"},
						{data: 'InpatientRILevel', renderer:"highlightRenderer"},
						{data: 'InpatientRIWAtypicalCodeDesc', renderer:"highlightRenderer"},
						{data: 'CMG_Year', renderer:"highlightRenderer"},
						{data: 'HealthCareNumber', renderer:"highlightRenderer"},
						{data: 'HIG_SCUFlag', renderer:"highlightRenderer"},
						{data: 'DischargeDisposition', renderer:"highlightRenderer"}
					],
					startRows: 5,
					startCols: 3,
					colHeaders: true,
					minSpareRows: 1,
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
					},
				});
			})
		});
	});
	
	
	
	
</script>
