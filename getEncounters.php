<?php

$file = 'debug.txt';
$console = "";

$server = 'MSHSQL8CLSTA\PR100';
$database = "med2020";

$dbh = new PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=S:\\OQPM\\OQPM Team\\Vadim\\AuditToolTest\audit_be.accdb;Uid=Admin");

$uid = file_get_contents("uid.txt");
$pwd = file_get_contents("pwd.txt");

try {
      $conn = new PDO( "sqlsrv:server=" . $server . ";Database = " . $database, $uid, $pwd); 
      $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 
   }

   catch( PDOException $e ) {
      die( "Error connecting to SQL Server" ); 
   }
   
	
   $encounterListRaw = $_POST['encounterList'];   
   $encounterList = array_filter($encounterListRaw);
   $inQuery = implode(",", array_fill(0, count($encounterList), '?'));
   
   $query = "select *,
		CONVERT(VARCHAR(10), AdmissionDate, 111) as AdmissionDateFormated,
		CONVERT(VARCHAR(10), DischargeDate, 111) as DischargeDateFormated
		from dbo.I10_Abstract_And_CMG_VR WHERE EncounterNumber IN (" . $inQuery . ")
		"; 
   $stmt = $conn->prepare( $query ); 
   
   foreach ($encounterList as $k => $encounter){
		$console .= ($encounter) . ' ';
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
		
		array_push($rowArr , $row);
   }

   header('Content-type: application/json');
	echo json_encode($rowArr);	
	//print_r($_POST['encounterList']);

   // Free statement and connection resources. 
   $stmt = null; 
   $conn = null; 
   

   

//file_put_contents($file, $console);
?>