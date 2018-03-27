<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);
ini_set('max_execution_time', 5000);

require_once('functions.php');
require_once('FollowTrendLogic.php');
require_once('DateInterval.php');

$ForexAPI = New FollowTrend();
$Markets = array();

$MarketInfo = "";
try{
	$stmt = $conn->prepare("SELECT Market, Decimals, MaxSqueeze FROM Markets WHERE Status = 'Active'");
	$stmt->execute();
	$results = $stmt->fetch(PDO::FETCH_ASSOC);
	foreach($results as $row){
		$Markets[$row["Market"]]["Decimal"] = $row["Decimals"];
		$Markets[$row["Market"]]["MaxSqueeze"] = $row["MaxSqueeze"];
		$Markets[$row["Market"]]["ActiveTrade"] = 0;
	}
} catch(PDOException  $error){
	echo "Error getting Market information: " . $error->getMessage();
}

$results = $ForexAPI->GetRateBlotter(array());
$data = explode("$", $results->getRateBlotterResult);
foreach($data as $products){
$product = explode("\\", $products);
	if(array_key_exists($product[0], $Markets)){
		$Markets[$product[0]]["CurrentRate"] = $product[1];
	}
}

$BuySellOpposite = "";
try{
	$stmt = $conn->prepare("SELECT A.*, M.Decimals, M.MaxSqueeze FROM APITrades A INNER JOIN Markets M ON A.Product=M.Market AND M.Status = 'Active' WHERE A.Status = 'Active' AND Program = 'FollowTrend'");
	$stmt->execute();
	$results = $stmt->fetch(PDO::FETCH_ASSOC);
	foreach($results as $row){
		$Market = $row["Product"];
		$BuySell = $row["BuySell"];
		if($BuySell == "B") $BuySellOpposite = "S"; elseif($BuySell == "S") $BuySellOpposite = "B";
		$Decimal = $row["Decimals"];
		$Rate = number_format($row["Rate"], $Decimal);
		$Quantity = $row["Amount"] / 1000;
		$Spread = $row["Spread"];
		$DealReference = $row["DealReference"];
		$MaxSqueeze = $row["MaxSqueeze"];
		$Spike = $row["Spike"];
		
		$DecimalPlaces = strlen(substr(strrchr($Rate, "."), 1));
		
		if($DecimalPlaces == 3){
			$Multiplier = 100;
		} else if($DecimalPlaces == 4){
			$Multiplier = 1000;
		} else if($DecimalPlaces == 5){
			$Multiplier = 10000;
		}
		
		//Determine Profit
		if($BuySell == "B"){
			$UnrealizedPIP = -(($Rate - number_format($Markets[$Market]["CurrentRate"],$row["Decimals"])) * $Multiplier) + $Spread;
		} else if($BuySell == "S"){
			$UnrealizedPIP = (($Rate - $Markets[$Market]["CurrentRate"]) * $Multiplier) - $Spread;
		}
		
		//Get out of any trades that look to be not breaking a resistance/support band
		$SupportResistance = DetermineSupportResistance($Market);
		if(!empty($SupportResistance)){
			
		} else {
			//echo "Unable to determine Support and Resistance for $Market.";
		}
		
		//Get out of any profitable trades that look to be going bad
		
		
		$Squeeze = explode("|",IsSqueeze($Market, $MaxSqueeze, $Decimal));
		$IsSqueeze = $Squeeze[0];
		if(!empty($Squeeze[4])) $SqueezeBuySell = $Squeeze[4]; else $SqueezeBuySell = "";
		if($BuySellOpposite == $SqueezeBuySell){
			//squeeze reversal
			if($UnrealizedPIP > 0){
				$ForexAPI->CloseTrade(array("Product" => $Market, "BuySell" => $BuySellOpposite, "Amount" => $row["Amount"], "Rate" => $Rate), $DealReference, 'Profit', $UnrealizedPIP);
			} else {
				$ForexAPI->CloseTrade(array("Product" => $Market, "BuySell" => $BuySellOpposite, "Amount" => $row["Amount"], "Rate" => $Rate), $DealReference, 'Loss', $UnrealizedPIP);
			}
			$ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => $BuySellOpposite, "Amount" => $row["Amount"]), 0);
		}
		if(!empty($BuySellOpposite)){
			if($Spike == 1){
				$UnrealizedPIPLimit = 50;
				$UnrealizedPIPSoftStopLimit = -25;
				$UnrealizedPIPStopLimit = -25;
			} else if($IsSqueeze){
				//temp needs better logic for squeeze
				$UnrealizedPIPLimit = 20; //this shouldn't ever be hit if squeeze logic is right
				$UnrealizedPIPSoftStopLimit = -10; //this shouldn't ever be hit if squeeze logic is right
				$UnrealizedPIPStopLimit = -10; //this shouldn't ever be hit if squeeze logic is right
			} else {
				$UnrealizedPIPLimit = 15; //take profit 15 pips
				$UnrealizedPIPSoftStopLimit = -15; //take loss 15 pips
				$UnrealizedPIPStopLimit = -30; //take loss 30 pips
			}
			$MarketInfo .= json_encode(array("Market" => $Market, "UnrealizedPIPLimit" => $UnrealizedPIPLimit, "UnrealizedPIPSoftStopLimit" => $UnrealizedPIPSoftStopLimit, "UnrealizedPIPStopLimit" => $UnrealizedPIPStopLimit, "UnrealizedPIP" => $UnrealizedPIP, "Rate" => $Rate, "Amount" => $row["Amount"], "Current Rate" => $Markets[$Market]["CurrentRate"])) . ",";
			if($UnrealizedPIP > $UnrealizedPIPLimit){
				//close trade if profitable
				//echo "CloseTrade Limit|UnrealizedPIP: $UnrealizedPIP|UnrealizedPIPLimit: $UnrealizedPIPLimit|Market:".$Market;
				$ForexAPI->CloseTrade(array("Product" => $Market, "BuySell" => $BuySellOpposite, "Amount" => $row["Amount"], "Rate" => $Rate), $DealReference, 'Profit', $UnrealizedPIP);
			}
			if($UnrealizedPIP < $UnrealizedPIPStopLimit){
				//close trade if not profitable
				//echo "CloseTrade StopLimit|UnrealizedPIP: $UnrealizedPIP|UnrealizedPIPStopLimit: $UnrealizedPIPStopLimit|Market:".$Market;
				$ForexAPI->CloseTrade(array("Product" => $Market, "BuySell" => $BuySellOpposite, "Amount" => $row["Amount"], "Rate" => $Rate), $DealReference, 'Loss', $UnrealizedPIP);
			}
			if($UnrealizedPIP < $UnrealizedPIPSoftStopLimit){
				$Trend = explode("|",DetermineTrend($Market, $IsSqueeze, $Decimal));
				$TrendDirection = $Trend[0];
				if(($TrendDirection == "Up" && $row["BuySell"] == "S") || ($TrendDirection == "Down" && $row["BuySell"] == "B")){
					//echo "CloseTrade SoftStopLimit|Unrealized PIP: $UnrealizedPIP|UnrealizedPIPStopLimit: $UnrealizedPIPStopLimit|Market:".$Market;
					$results = $ForexAPI->CloseTrade(array("Product" => $Market, "BuySell" => $BuySellOpposite, "Amount" => $row["Amount"], "Rate" => $Rate), $DealReference, 'Soft Loss', $UnrealizedPIP);
					if($results->DealRequestAtBestResult->success == 1){
						$ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => $BuySellOpposite, "Amount" => $row["Amount"]), 0);
					}
				}
			}
		}
		$Markets[$Market]["ActiveTrade"] = 1;
		$Markets[$Market]["Decimal"] = $Decimal;
	}
} catch(PDOException  $error){
	echo "Error gathering market information: " . $error->getMessage();
}
echo rtrim($MarketInfo,",");

foreach($Markets as $Market => $value){
	//echo "|".$Market.": ".$value["ActiveTrade"]."<br>";
	$Squeeze = explode("|",IsSqueeze($Market, $value["MaxSqueeze"], $value["Decimal"]));
	$IsSqueeze = $Squeeze[0];
	$Trend = "";
	if($value["ActiveTrade"] == 0){
		if($IsSqueeze == 1){
			if(!empty($Squeeze[4])){
				$ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => $Squeeze[4], "Amount" => 2000), 0);
			}
		} else {
			//determine trend
			$Trend = explode("|",DetermineTrend($Market, $IsSqueeze, $value["Decimal"]));
			$TrendDirection = $Trend[0];
			if(!empty($Trend[1])){
				$TrendUp = $Trend[1];
				$TrendDown = $Trend[2];
				if(!empty($Trend[3])) $Spike = 1; else $Spike = 0;
				
				if($TrendUp == 6 || $TrendDown == 6) $TrendAmount = 5000; else if($Spike == 1) $TrendAmount = 4000; else $TrendAmount = 1000;
				//open trade based off trend
				if($TrendDirection == "Up"){
					if(IsGoodEntry($Market, "B", $value["Decimal"], $IsSqueeze)){
						//echo "CreateTrade|UpTrend|Market:".$Market;
						$ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => "B", "Amount" => $TrendAmount), $Spike);
					}
				} else if($TrendDirection == "Down"){
					if(IsGoodEntry($Market, "S", $value["Decimal"], $IsSqueeze)){
						//echo "CreateTrade|DownTrend|Market:".$Market;
						$ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => "S", "Amount" => $TrendAmount), $Spike);
					}
				} else {
					//echo "Unable to determine trend";
				}
			}
		}
	}
}
?>