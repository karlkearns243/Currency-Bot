<?php
require_once('functions.php');

$data = json_decode($_POST["data"]);
foreach($data as $key => $value){
	$Product = $key;
	$RateDate = $value->ratedate;
	$Price = $value->price;
	$Open = $value->open;
	$High = $value->high;
	$Low = $value->low;
	$Change = str_replace("%","",$value->change);
	
	if(empty($queryvalues)){
		$queryvalues = "('$Product', '".date("Y-m-d",strtotime(date("Y-m-d") . "-1 days"))."', '$Price', '$Open', '$High','$Low','$Change')";
	} else {
		$queryvalues .= ",('$Product', '".date("Y-m-d",strtotime(date("Y-m-d") . "-1 days"))."', '$Price', '$Open', '$High','$Low','$Change')";
	}
}

try{
	$query = $conn->prepare("INSERT INTO rates.invest_history (Product, RateDate, Price, OpenRate, HighRate, LowRate, ChangePercent) VALUES $queryvalues ON DUPLICATE KEY UPDATE id=id");
	$query->execute();
} catch(PDOException $error){
	echo "Error inserting investing history: " . $error;
}

echo "INSERT INTO rates.invest_history (Product, RateDate, Price, OpenRate, HighRate, LowRate, ChangePercent) VALUES $queryvalues ON DUPLICATE KEY UPDATE id=id";
?>