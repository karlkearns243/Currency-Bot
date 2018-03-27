<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);
ini_set('max_execution_time', 5000);

require_once('functions.php');
require_once('Forex.php');
require_once('DateInterval.php');

$ForexAPI = New ForexAPIs();
$Markets = array();

//put this into its own script.
$values = $ForexAPI->GetMarginBlotter(array());
$RealizedProfit = $values->GetMarginBlotterResult->Output->Margin->RealizedProfit;
$UnrealizedProfit = $values->GetMarginBlotterResult->Output->Margin->UnrealizedProfit;
$MarginBalance = $values->GetMarginBlotterResult->Output->Margin->MarginBalance;
echo json_encode(array("RealizedProfit" => $RealizedProfit, "UnrealizedProfit" => $UnrealizedProfit, "MarginBalance" => $MarginBalance));