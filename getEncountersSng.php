<?php

$file = 'debug.txt';
$console = "";

$server = 'MSHSQL8CLSTA\PR100';
$database = "med2020";

$dbh = new PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=S:\\OQPM\\OQPM Team\\Vadim\\AuditToolTest\audit_be.accdb;Uid=Admin");

$uid = file_get_contents("uid.txt");
$pwd = file_get_contents("pwd.txt");

//$fieldsFile = file_get_contents("fieldsToSave.csv");
//$fieldsToSave = str_getcsv(file_get_contents('fieldsToSave.csv'));
$fieldList = file("fieldList.csv", FILE_IGNORE_NEW_LINES);

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
   $stmt = $conn->prepare( $query ); 
   
   foreach ($encounterList as $k => $encounter){
	   $stmt->bindValue(($k+1), trim($encounter));
   }
   
   $stmt->execute();
   
   $rowArr = array();
   while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){ 
   
		$encounter_Number = $row['EncounterNumber'];
		
		$sql_Changes = "SELECT * FROM Chart_Changes WHERE Encounter_Number = :Encounter_Number ORDER BY Date_Of_Change";
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
		
		foreach($fieldList as &$field){
			$sql_GetValue = "SELECT TOP 1 * FROM Chart_Values WHERE EncounterNumber = :EncounterNumber ORDER BY Date_Of_Get DESC";
			$sth_GetValue = $dbh->prepare($sql_GetValue);
			$sth_GetValue->bindParam(':EncounterNumber', $encounter_Number, PDO::PARAM_STR);
			$sth_GetValue->execute();
			$changes = $row['Changes'];
			while ($row_Value = $sth_GetValue->fetch()) {
				$console .= $row_Value[$field] . ' ';
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
		array_push($rowArr , $row);
		
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
			$sth->bindParam(':' . $fieldQry,  $row[$fieldQry], PDO::PARAM_STR);
		}
		date_default_timezone_set('America/Toronto');
		$date = date('m/d/Y h:i:s a', time());
		$sth->bindParam(':Date_Of_Get', $date , PDO::PARAM_INT);
			
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