<?php
require_once('functions.php');

$data = json_decode($_POST["data"]);
$lastupdated = $_POST["lastupdated"];
$queryvalues = "";

foreach($data as $key => $value){
	$product = $key;
	$support1 = $value->Support1;
	$support2 = $value->Support2;
	$support3 = $value->Support3;
	$pivot = $value->Pivot;
	$resistance1 = $value->Resistance1;
	$resistance2 = $value->Resistance2;
	$resistance3 = $value->Resistance3;
	$timeframe = $value->timeframe;
	
	if(empty($queryvalues)){
		$queryvalues = "('$lastupdated', '$product', '$timeframe', '$support1','$support2','$support3','$pivot','$resistance1','$resistance2','$resistance3')";
	} else {
		$queryvalues .= ",('$lastupdated', '$product', '$timeframe', '$support1','$support2','$support3','$pivot','$resistance1','$resistance2','$resistance3')";
	}
}

try{
	$query = $conn->prepare("INSERT INTO rates.DailyFX (LastUpdated, Product, Timeframe, Support1, Support2, Support3, Pivot, Resistance1, Resistance2, Resistance3) VALUES $queryvalues");
	$query->execute();
} catch(PDOException $error){
	echo "Error inserting DailyFX: " . $error;
}

echo "INSERT INTO rates.DailyFX (LastUpdated, Product, Timeframe, Support1, Support2, Support3, Pivot, Resistance1, Resistance2, Resistance3) VALUES $queryvalues";
?>