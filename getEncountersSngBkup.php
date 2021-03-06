<?php

ini_set('max_execution_time', 300);
$file = 'debug.txt';
$console = "";

$server = 'MSHSQL8CLSTA\PR100';
//$server = "PC\SQLEXPRESS";
$database = "med2020";

//$dbh = new PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=S:\\OQPM\\OQPM Team\\Vadim\\AuditToolTest\audit_be.accdb;Uid=Admin");
$dbh = new PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=C:\\OQPM\\OQPM Team\\Vadim\\AuditToolTest\audit_be.accdb;Uid=Admin");
//$dbh = new PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=c:\\xampp\\htdocs\\AuditTest\\AuditToolTest\\audit_be.accdb;Uid=Admin");

$uid = file_get_contents("uid.txt");
$pwd = file_get_contents("pwd.txt");
//$uid = "sa";
//$pwd = "78we56";

//$fieldsFile = file_get_contents("fieldsToSave.csv");
//$fieldsToSave = str_getcsv(file_get_contents('fieldsToSave.csv'));
$fieldList = file("fieldList.csv", FILE_IGNORE_NEW_LINES);

date_default_timezone_set('America/Toronto');
$date = date('m/d/Y h:i:s a', time());

try {
      $conn = new PDO( "sqlsrv:server=" . $server . ";Database = " . $database, $uid, $pwd); 
      $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 
   }

   catch( PDOException $e ) {
      die( "Error connecting to SQL Server" ); 
   }
   
   //$encounterListRaw = $_POST['encounterList'];   
   $encounterListRaw =  isset($_GET["EncounterList"]) ? $_GET["EncounterList"] : "" ;
   $encounterList = explode(",", $encounterListRaw);
   //$encounterList = array_filter($encounterListRaw);
   $inQuery = implode(",", array_fill(0, count($encounterList), '?'));
   
   $query = "select *,
		CONVERT(VARCHAR(10), AdmissionDate, 111) as AdmissionDateFormated,
		CONVERT(VARCHAR(10), DischargeDate, 111) as DischargeDateFormated
		from dbo.I10_Abstract_And_CMG_VR WHERE EncounterNumber IN (" . $inQuery . ")
		"; 
//   $query = "select *,
//		CONVERT(VARCHAR(10), AdmissionDate, 111) as AdmissionDateFormated,
//		CONVERT(VARCHAR(10), DischargeDate, 111) as DischargeDateFormated
//		from cmgView WHERE EncounterNumber IN (" . $inQuery . ")
//		"; 
   $stmt = $conn->prepare( $query ); 
   
   foreach ($encounterList as $k => $encounter){
	   $stmt->bindValue(($k+1), trim($encounter));
   }
   
   $stmt->execute();
   
   $rowArr = array();
   while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){ 
   
		$encounter_Number = $row['EncounterNumber'];
		
		$sql_Changes = "SELECT * FROM Chart_Changes WHERE Encounter_Number = :Encounter_Number AND Diagnosis_Code = '' ORDER BY Date_Of_Change";
		$sth_Changes = $dbh->prepare($sql_Changes);
		$sth_Changes->bindParam(':Encounter_Number', $encounter_Number, PDO::PARAM_STR);
		$sth_Changes->execute();
		$changes = 0;
		$row['Changes'] = $changes;
		$row['Comments'] = '';
		while ($row_Changes = $sth_Changes->fetch()) {
			$changes++;
			$row['Changes'] = (string) $changes;
			$row['Changed_ID' . $changes] = $row_Changes['ID'];
			$row['Changed_Field' . $changes] = $row_Changes['Field'];
			$row['Changed_Old_Value' . $changes] = $row_Changes['Old_Value'];
			$row['Changed_New_Value' . $changes] = $row_Changes['New_Value'];
			$row['Changed_Reviewer_Name' . $changes] = $row_Changes['Reviewer_Name'];
			$row['Changed_Date_Of_Change' . $changes] = $row_Changes['Date_Of_Change'];
			if ($row_Changes['Field'] == 'Comments'){
				$row['Comments'] = $row_Changes['New_Value'];
			}
		}
		
		$rowFound = false;
		foreach($fieldList as &$field){
			$sql_GetValue = "SELECT TOP 1 * FROM Chart_Values WHERE EncounterNumber = :EncounterNumber ORDER BY Date_Of_Get DESC";
			$sth_GetValue = $dbh->prepare($sql_GetValue);
			$sth_GetValue->bindParam(':EncounterNumber', $encounter_Number, PDO::PARAM_STR);
			$sth_GetValue->execute();
			$changes = $row['Changes'];
			while ($row_Value = $sth_GetValue->fetch()) {
				$rowFound = true;
				if ($row_Value[$field] != $row[$field]){
					
					$changes++;
					$row['Changes'] = (string) $changes;
					$row['Changed_ID' . $changes] = $row_Value['ID'];
					$row['Changed_Field' . $changes] = $field;
					$row['Changed_Old_Value' . $changes] = $row_Value[$field];
					$row['Changed_New_Value' . $changes] = $row[$field];
					$row['Changed_Reviewer_Name' . $changes] = "WINRECS";
					$row['Changed_Date_Of_Change' . $changes] = $row_Value['Date_Of_Get'];
					
					$sqlChanged = 
					"Insert Into Chart_Changes(
							Encounter_Number,
							Reviewer_Name,
							Field,
							Old_Value,
							New_Value,
							Date_Of_Change
						) Values (
							:Encounter_Number,
							:Reviewer_Name,
							:Field,
							:Old_Value,
							:New_Value,
							:Date_Of_Change
						)";

					$sthChanged = $dbh->prepare($sqlChanged);
					$sthChanged->bindParam(':Encounter_Number', $encounter_Number, PDO::PARAM_STR);
					$sthChanged->bindParam(':Reviewer_Name',$a = 'WINRECS', PDO::PARAM_STR);
					$sthChanged->bindParam(':Field', $field, PDO::PARAM_STR);
					$sthChanged->bindParam(':Old_Value', $row_Value[$field], PDO::PARAM_STR);
					$sthChanged->bindParam(':New_Value', $row[$field], PDO::PARAM_STR);
					
					$sthChanged->bindParam(':Date_Of_Change', $row_Value['Date_Of_Get'] , PDO::PARAM_INT);
						
					header('Content-type: application/json');
					if (!$sthChanged) {
						echo "\nPDO::errorInfo():\n";
						print_r($dbh->errorInfo());
						$response_array['status'] = 'error';  
						header('Content-type: application/json');
						echo json_encode();
					}

					$sthChanged->execute();
				}
			}
		}
		$row['AddRow'] = false;
		array_push($rowArr , $row);
		
		$addRowArr = array();	
		
//		$queryDiag = "select *,
//			CONVERT(VARCHAR(10), AdmissionDate, 111) as AdmissionDateFormated,
//			CONVERT(VARCHAR(10), DischargeDate, 111) as DischargeDateFormated
//			from diagView WHERE EncounterNumber = '" . $row['EncounterNumber'] . "'
//			AND DiagnosisCIHIValue != '" . $row['Diagnosis_Code'] . "'
//			"; 
		$queryDiag = "select *,
			CONVERT(VARCHAR(10), AdmissionDate, 111) as AdmissionDateFormated,
			CONVERT(VARCHAR(10), DischargeDate, 111) as DischargeDateFormated
			from I10_Abstract_And_Diagnosis_VR WHERE EncounterNumber = '" . $row['EncounterNumber'] . "'
			AND DiagnosisCIHIValue != '" . $row['Diagnosis_Code'] . "'
			"; 
		$stmtDiag = $conn->prepare( $queryDiag ); 
   
		$stmtDiag->execute();
   
		$diagString = "";
		while ( $rowDiag = $stmtDiag->fetch( PDO::FETCH_ASSOC ) ){ 
			$rowDiagNew = array();
			
			$rowDiagNew['EncounterNumber'] = $encounter_Number;
			$rowDiagNew['Diagnosis_Code'] = $rowDiag['DiagnosisCode'];
			$rowDiagNew['Diagnosis_Description'] = $rowDiag['DiagnosisCodeDesc'];
			$rowDiagNew['Diagnosis_Type'] = $rowDiag['DiagnosisType'];
			$rowDiagNew['Diagnosis_Type_Desc'] = $rowDiag['DiagnosisTypeDesc'];
			$diagString .= "'" . $rowDiagNew['Diagnosis_Code'] . "',"; 
			
			$sql_Changes = "SELECT * FROM Chart_Changes WHERE Encounter_Number = :Encounter_Number AND Diagnosis_Code = :Diagnosis_Code ORDER BY Date_Of_Change";
			$sth_Changes = $dbh->prepare($sql_Changes);
			$sth_Changes->bindParam(':Encounter_Number', $encounter_Number, PDO::PARAM_STR);
			$sth_Changes->bindParam(':Diagnosis_Code', $rowDiagNew['Diagnosis_Code'], PDO::PARAM_STR);
			$sth_Changes->execute();
			$changes = 0;
			$rowDiagNew['Changes'] = $changes;
			while ($row_Changes = $sth_Changes->fetch()) {
				$changes++;
				$rowDiagNew['Changes'] = (string) $changes;
				$rowDiagNew['Changed_ID' . $changes] = $row_Changes['ID'];
				$rowDiagNew['Changed_Field' . $changes] = $row_Changes['Field'];
				$rowDiagNew['Changed_Old_Value' . $changes] = $row_Changes['Old_Value'];
				$rowDiagNew['Changed_New_Value' . $changes] = $row_Changes['New_Value'];
				$rowDiagNew['Changed_Reviewer_Name' . $changes] = $row_Changes['Reviewer_Name'];
				$rowDiagNew['Changed_Date_Of_Change' . $changes] = $row_Changes['Date_Of_Change'];
			}
			
			$sql_GetValue = "SELECT TOP 1 * FROM Chart_Diagnosis WHERE Encounter_Number = :EncounterNumber AND Diagnosis_Code = :Diagnosis_Code ORDER BY Date_Of_Get DESC";
			$sth_GetValue = $dbh->prepare($sql_GetValue);
			$sth_GetValue->bindParam(':EncounterNumber', $encounter_Number, PDO::PARAM_STR);
			$sth_GetValue->bindParam(':Diagnosis_Code', $rowDiagNew['Diagnosis_Code'], PDO::PARAM_STR);
			$sth_GetValue->execute();
			$diagFound = false;
			while ($row_Value = $sth_GetValue->fetch()) {
				$diagFound = true;
				$currField = 'Diagnosis_Type';
				if ($row_Value[$currField] != $rowDiagNew[$currField]){
					$changes++;
					$rowDiagNew['Changes'] = (string) $changes;
					$rowDiagNew['Changed_ID' . $changes] = $row_Value['ID'];
					$rowDiagNew['Changed_Field' . $changes] = $currField;
					$rowDiagNew['Changed_Old_Value' . $changes] = $row_Value[$currField];
					$rowDiagNew['Changed_New_Value' . $changes] = $rowDiagNew[$currField];
					$rowDiagNew['Changed_Reviewer_Name' . $changes] = "WINRECS";
					$rowDiagNew['Changed_Date_Of_Change' . $changes] = $row_Value['Date_Of_Get'];

					
					$sqlChanged = 
					"Insert Into Chart_Changes (
							Encounter_Number,
							Reviewer_Name,
							Field,
							Old_Value,
							New_Value,
							Date_Of_Change,
							Type,
							Diagnosis_Code
						) Values (
							:Encounter_Number,
							:Reviewer_Name,
							:Field,
							:Old_Value,
							:New_Value,
							:Date_Of_Change,
							:Type,
							:Diagnosis_Code
						)";

					$sthChanged = $dbh->prepare($sqlChanged);
					$sthChanged->bindParam(':Encounter_Number', $encounter_Number, PDO::PARAM_STR);
					$sthChanged->bindParam(':Reviewer_Name',$a = 'WINRECS', PDO::PARAM_STR);
					$sthChanged->bindParam(':Field', $currField, PDO::PARAM_STR);
					$sthChanged->bindParam(':Old_Value', $row_Value[$currField], PDO::PARAM_STR);
					$sthChanged->bindParam(':New_Value', $rowDiagNew[$currField], PDO::PARAM_STR);
					$sthChanged->bindParam(':Type', $a = 'Change', PDO::PARAM_STR);
					$sthChanged->bindParam(':Diagnosis_Code', $rowDiagNew['Diagnosis_Code'], PDO::PARAM_STR);
					
					$sthChanged->bindParam(':Date_Of_Change', $row_Value['Date_Of_Get'] , PDO::PARAM_INT);
						
					header('Content-type: application/json');
					if (!$sthChanged) {
						echo "\nPDO::errorInfo():\n";
						print_r($dbh->errorInfo());
						$response_array['status'] = 'error';  
						header('Content-type: application/json');
						echo json_encode();
					}

					$sthChanged->execute();
				}
			}
			//$console .= ':' . $diagFound . ' - ' . $rowFound . 'for: ' . $rowDiagNew['Diagnosis_Code'] . ';';
			if ($diagFound == false && $rowFound){
				$currField = 'Diagnosis_Code';
				$changes++;
				$rowDiagNew['Changes'] = (string) $changes;
				$rowDiagNew['Changed_ID' . $changes] = 'Add';
				$rowDiagNew['Changed_Field' . $changes] = $currField;
				$rowDiagNew['Changed_Old_Value' . $changes] = 'N/A';
				$rowDiagNew['Changed_New_Value' . $changes] = $rowDiagNew[$currField];
				$rowDiagNew['Changed_Reviewer_Name' . $changes] = "WINRECS";
				$rowDiagNew['Changed_Date_Of_Change' . $changes] = $date;
				$rowDiagNew['Changed_Type' . $changes] = "Add";
				
				$sqlChanged = 
				"Insert Into Chart_Changes (
						Encounter_Number,
						Reviewer_Name,
						Field,
						Old_Value,
						New_Value,
						Date_Of_Change,
						Type,
						Diagnosis_Code
					) Values (
						:Encounter_Number,
						:Reviewer_Name,
						:Field,
						:Old_Value,
						:New_Value,
						:Date_Of_Change,
						:Type,
						:Diagnosis_Code
					)";

				$sthChanged = $dbh->prepare($sqlChanged);
				$sthChanged->bindParam(':Encounter_Number', $encounter_Number, PDO::PARAM_STR);
				$sthChanged->bindParam(':Reviewer_Name',$a = 'WINRECS', PDO::PARAM_STR);
				$sthChanged->bindParam(':Field', $currField, PDO::PARAM_STR);
				$sthChanged->bindParam(':Old_Value', $a = 'N/A', PDO::PARAM_STR);
				$sthChanged->bindParam(':New_Value', $rowDiagNew[$currField], PDO::PARAM_STR);
				$sthChanged->bindParam(':Type', $a = 'Add', PDO::PARAM_STR);
				$sthChanged->bindParam(':Diagnosis_Code', $rowDiagNew['Diagnosis_Code'], PDO::PARAM_STR);
				
				$sthChanged->bindParam(':Date_Of_Change', $date , PDO::PARAM_INT);
					
				header('Content-type: application/json');
				if (!$sthChanged) {
					echo "\nPDO::errorInfo():\n";
					print_r($dbh->errorInfo());
					$response_array['status'] = 'error';  
					header('Content-type: application/json');
					echo json_encode();
				}

				$sthChanged->execute();
			}
			array_push($addRowArr , $rowDiagNew);
			
			$sqlSaveDiag = 
				"Insert Into Chart_Diagnosis(
						Encounter_Number,
						Reviewer_Name,
						Diagnosis_Code,
						Diagnosis_Description,
						Diagnosis_Type,
						Diagnosis_Type_Desc,
						Date_Of_Get
					) Values (
						:Encounter_Number,
						:Reviewer_Name,
						:Diagnosis_Code,
						:Diagnosis_Description,
						:Diagnosis_Type,
						:Diagnosis_Type_Desc,
						:Date_Of_Get
					)";

			$sth = $dbh->prepare($sqlSaveDiag);
			$sth->bindParam(':Encounter_Number', $encounter_Number, PDO::PARAM_STR);
			$sth->bindParam(':Reviewer_Name',$a = 'WINRECS', PDO::PARAM_STR);
			$sth->bindParam(':Diagnosis_Code', $rowDiagNew['Diagnosis_Code'], PDO::PARAM_STR);
			$sth->bindParam(':Diagnosis_Description',  $rowDiagNew['Diagnosis_Description'], PDO::PARAM_STR);
			$sth->bindParam(':Diagnosis_Type',  $rowDiagNew['Diagnosis_Type'], PDO::PARAM_STR);
			$sth->bindParam(':Diagnosis_Type_Desc',  $rowDiagNew['Diagnosis_Type_Desc'], PDO::PARAM_STR);
			
			$sth->bindParam(':Date_Of_Get', $date , PDO::PARAM_INT);
				
			header('Content-type: application/json');
			if (!$sth) {
				echo "\nPDO::errorInfo():\n";
				print_r($dbh->errorInfo());
				$response_array['status'] = 'error';  
				header('Content-type: application/json');
				echo json_encode();
			}
			
			$sth->execute();
		
		}
		
		$diagString = rtrim($diagString, ",");
		//$console .= $diagString;
		$sql_GetValue = "SELECT * FROM Chart_Diagnosis WHERE Encounter_Number = :EncounterNumber AND Diagnosis_Code NOT IN  (" . $diagString . ") ORDER BY Date_Of_Get DESC";
		$sth_GetValue = $dbh->prepare($sql_GetValue);
		$sth_GetValue->bindParam(':EncounterNumber', $encounter_Number, PDO::PARAM_STR);
		$sth_GetValue->execute();
		while ($row_Value = $sth_GetValue->fetch()) {
			$currField = 'Diagnosis_Code';
			$rowDiagNewDeleted = array();
			
			$rowDiagNewDeleted['EncounterNumber'] = $encounter_Number;
			$rowDiagNewDeleted['Diagnosis_Code'] = $row_Value['Diagnosis_Code'];
			$rowDiagNewDeleted['Diagnosis_Description'] = $row_Value['Diagnosis_Description'];
			$rowDiagNewDeleted['Diagnosis_Type'] = $row_Value['Diagnosis_Type'];
			$rowDiagNewDeleted['Diagnosis_Type_Desc'] = $row_Value['Diagnosis_Type_Desc'];
			$diagString .= "'" . $rowDiagNewDeleted['Diagnosis_Code'] . "',"; 
			
			$changes = 1;
			$rowDiagNewDeleted['Changes'] = $changes;
			$rowDiagNewDeleted['Changes'] = (string) $changes;
			$rowDiagNewDeleted['Changed_ID' . $changes] = 'Delete';
			$rowDiagNewDeleted['Changed_Field' . $changes] = $currField;
			$rowDiagNewDeleted['Changed_Old_Value' . $changes] = $rowDiagNewDeleted[$currField];
			$rowDiagNewDeleted['Changed_New_Value' . $changes] = 'N/A';
			$rowDiagNewDeleted['Changed_Reviewer_Name' . $changes] = "WINRECS";
			$rowDiagNewDeleted['Changed_Date_Of_Change' . $changes] = $date;
			$rowDiagNewDeleted['Changed_Type' . $changes] = 'Delete';
			
			$sql_CheckExsists = "SELECT * FROM Chart_Changes WHERE Encounter_Number = :EncounterNumber AND Type = 'Delete' AND Diagnosis_Code = :Diagnosis_Code";
			$sth_CheckExsists = $dbh->prepare($sql_CheckExsists);
			$sth_CheckExsists->bindParam(':EncounterNumber', $encounter_Number, PDO::PARAM_STR);
			$sth_CheckExsists->bindParam(':Diagnosis_Code', $row_Value['Diagnosis_Code'], PDO::PARAM_STR);
			$sth_CheckExsists->execute();
			if($sth_CheckExsists->fetch(PDO::FETCH_NUM) == 0){
				$sqlChanged = 
				"Insert Into Chart_Changes (
						Encounter_Number,
						Reviewer_Name,
						Field,
						Old_Value,
						New_Value,
						Date_Of_Change,
						Type,
						Diagnosis_Code
					) Values (
						:Encounter_Number,
						:Reviewer_Name,
						:Field,
						:Old_Value,
						:New_Value,
						:Date_Of_Change,
						:Type,
						:Diagnosis_Code
					)";

				$sthChanged = $dbh->prepare($sqlChanged);
				$sthChanged->bindParam(':Encounter_Number', $encounter_Number, PDO::PARAM_STR);
				$sthChanged->bindParam(':Reviewer_Name',$a = 'WINRECS', PDO::PARAM_STR);
				$sthChanged->bindParam(':Field', $currField, PDO::PARAM_STR);
				$sthChanged->bindParam(':Old_Value', $row_Value[$currField], PDO::PARAM_STR);
				$sthChanged->bindParam(':New_Value', $a = 'N/A', PDO::PARAM_STR);
				$sthChanged->bindParam(':Type', $a = 'Delete', PDO::PARAM_STR);
				$sthChanged->bindParam(':Diagnosis_Code', $row_Value['Diagnosis_Code'], PDO::PARAM_STR);
				
				$sthChanged->bindParam(':Date_Of_Change', $date , PDO::PARAM_INT);
					
				header('Content-type: application/json');
				if (!$sthChanged) {
					echo "\nPDO::errorInfo():\n";
					print_r($dbh->errorInfo());
					$response_array['status'] = 'error';  
					header('Content-type: application/json');
					echo json_encode();
				}

				$sthChanged->execute();		
			}				
			
			array_push($addRowArr , $rowDiagNewDeleted);
			
		}
		
		//$queryInt = "select *,
		//	CONVERT(VARCHAR(10), AdmissionDate, 111) as AdmissionDateFormated,
		//	CONVERT(VARCHAR(10), DischargeDate, 111) as DischargeDateFormated
		//	from intervensionView WHERE EncounterNumber = '" . $row['EncounterNumber'] . "'
		//	AND IntervCIHIValue != '" . $row['InterventionAssignment'] . "'
		//	"; 
		$queryInt = "select *,
			CONVERT(VARCHAR(10), AdmissionDate, 111) as AdmissionDateFormated,
			CONVERT(VARCHAR(10), DischargeDate, 111) as DischargeDateFormated
			from I10_Abstract_And_Intervention_VR WHERE EncounterNumber = '" . $row['EncounterNumber'] . "'
			AND IntervCIHIValue != '" . $row['InterventionAssignment'] . "'
			"; 
		$stmtInt = $conn->prepare( $queryInt ); 
   
		$stmtInt->execute();
   
		$addRowArrLen = count($addRowArr);
		$addRowArrIndex = 0;
		while ( $rowInt = $stmtInt->fetch( PDO::FETCH_ASSOC ) ){ 
			$rowIntNew = array();
			
			$rowIntNew['EncounterNumber'] = $encounter_Number;
			$rowIntNew['InterventionAssignment'] = $rowInt['IntervCIHIValue'];
			$rowIntNew['InterventionAssignmentDesc'] = $rowInt['IntervCodeDesc'];
			
			if ($addRowArrIndex>$addRowArrLen){
				array_push($addRowArr , $rowIntNew);
			}else{
				$addRowArr[$addRowArrIndex]['EncounterNumber'] = $encounter_Number;
				$addRowArr[$addRowArrIndex]['InterventionAssignment'] = $rowInt['IntervCIHIValue'];
				$addRowArr[$addRowArrIndex]['InterventionAssignmentDesc'] = $rowInt['IntervCodeDesc'];
				$addRowArrIndex++;
			}
		}

		foreach($addRowArr as &$addRow){
			$addRow['AddRow'] = true;
			array_push($rowArr , $addRow);
		}
		
		$sqlSaveValues = "Insert Into Chart_Values(";
		$sqlSaveValues .= "Reviewer_Name,";
		$sqlSaveValues .= "Date_Of_Get,";
		foreach($fieldList as &$fieldQry){
			$sqlSaveValues .= $fieldQry . ",";
		}
		$sqlSaveValues = rtrim($sqlSaveValues, ",");
		$sqlSaveValues .= ") Values (";
		$sqlSaveValues .= ":Reviewer_Name,";
		$sqlSaveValues .= ":Date_Of_Get,";
		foreach($fieldList as &$fieldQry){
			$sqlSaveValues .= ":" . $fieldQry . ",";
		}
		$sqlSaveValues = rtrim($sqlSaveValues, ",");
		$sqlSaveValues .= ")";
		$sth = $dbh->prepare($sqlSaveValues);
		$sth->bindParam(':Reviewer_Name', $_GET['ReviewerName'], PDO::PARAM_STR);
		foreach($fieldList as &$fieldQry){
			//$console .= ':' . $fieldQry . ' - ' . $row[$fieldQry] . '; ';
			$sth->bindParam(':' . $fieldQry,  $row[$fieldQry], PDO::PARAM_STR);
		}
		$sth->bindParam(':Date_Of_Get', $date , PDO::PARAM_INT);
		$console .= $sqlSaveValues . ";";
			
		header('Content-type: application/json');
		if (!$sth) {
			echo "\nPDO::errorInfo():\n";
			print_r($dbh->errorInfo());
			$response_array['status'] = 'error';  
			header('Content-type: application/json');
			echo json_encode();
		} else {
		}

		$sth->execute();
		
		
	}

   header('Content-type: application/json');
	echo json_encode($rowArr);	
	//print_r($_POST['encounterList']);

   // Free statement and connection resources. 
   $stmt = null; 
   $conn = null; 
   

   

file_put_contents($file, $console);
?>