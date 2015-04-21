<?php

$dbh = new PDO("odbc:Driver={Microsoft Access Driver (*.mdb, *.accdb)};Dbq=C:\\xampp\\htdocs\\AuditTest\\audit_be.accdb;Uid=Admin");

$parameters = $_POST['parameters'];

$dbh->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

$sql = 

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

	$sth = $dbh->prepare($sql);
	$sth->bindParam(':Reviewer_Name', $parameters ['Reviewer_Name'], PDO::PARAM_STR);
	$sth->bindParam(':Encounter_Number', $parameters ['Encounter_Number'], PDO::PARAM_STR);
	$sth->bindParam(':Field', $parameters ['Field'], PDO::PARAM_STR);
	$sth->bindParam(':Old_Value', $parameters ['Old_Value'], PDO::PARAM_STR);
	$sth->bindParam(':New_Value', $parameters ['New_Value'], PDO::PARAM_STR);
	
	date_default_timezone_set('America/Toronto');
	$date = date('m/d/Y h:i:s a', time());
	$sth->bindParam(':Date_Of_Change', $date , PDO::PARAM_INT);
		
	header('Content-type: application/json');
	if (!$sth) {
		echo "\nPDO::errorInfo():\n";
		print_r($dbh->errorInfo());
		$response_array['status'] = 'error';  
		header('Content-type: application/json');
		echo json_encode();
	}

	$sth->execute();
	
$dbh = null;

$response_array['status'] = 'success';  
header('Content-type: application/json');
echo json_encode($response_array);

?>