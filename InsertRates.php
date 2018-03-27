<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);

require_once('functions.php');
require_once('Forex.php');

$ForexAPI = New ForexAPIs();
$Markets = array();

$today = date("Y-m-d");
$time = date("H:i:00");
$results = $ForexAPI->GetRateBlotterAPI(array());
print_r($results);
$data = explode("$",$results->getRateBlotterResult);
foreach($data as $datapoint){
	$data = explode("\\",$datapoint);
	$decimals = $data[6];
	$market = $data[0];
	$open = number_format($data[1],$decimals);
	$ask = number_format($data[2],$decimals);
	$spread = $open / $ask;
	$spread = ((str_replace(".","",substr($ask,-4)) - str_replace(".","",substr($open,-4))) / 10);
	//echo $market."|".$open."|".$ask."|<br>";
	if(empty($values)) $values = "('$today','$time','$market','$open','$ask', '$spread')";
		else $values .= ",('$today','$time','$market','$open','$ask', '$spread')";
}

try{
	$query = $conn->prepare("INSERT INTO rates.forex_history (RateDate, RateTime, Product, RateBid, RateAsk, Spread) VALUES $values ON DUPLICATE KEY UPDATE Product = VALUES(Product), RateBid = VALUES(RateBid), RateAsk = VALUES(RateAsk), Spread = VALUES(Spread)");
	$query->execute();
} catch(PDOException $error){
	echo "Error inserting history rates: " . $error;
}

echo "INSERT INTO rates.forex_history (RateDate, RateTime, Product, RateBid, RateAsk, Spread) VALUES $values ON DUPLICATE KEY UPDATE Product = VALUES(Product), RateBid = VALUES(RateBid), RateAsk = VALUES(RateAsk), Spread = VALUES(Spread)";
?>