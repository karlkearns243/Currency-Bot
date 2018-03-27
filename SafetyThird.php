<?php
date_default_timezone_set('America/New_York');
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);
ini_set('max_execution_time', 5000);

require_once('functions.php');
require_once('FollowTrendClass2.php');
require_once('DateInterval.php');

$ForexAPI = New FollowTrend();
$Markets = array();

$test = true;
$text = false;

$Program = "FollowTrend3";
if(!empty($_POST["market"])) $Market = $_POST["market"];
if(!empty($Market)){
	$rownumber = 0;
	$TimeFrame = "";
	$SumGain = 0;
	$SumLoss = 0;
	$UnrealizedPIP = 0;
	
	try{
		$stmt = $conn->prepare("SELECT a.RateDate, a.RateTime, a.RateBid AS `RateBid`, '30m' as `TimeFrame`
										FROM (SELECT RateDate, RateTime, RateBid
											  FROM rates.forex_history 
											  WHERE Product = :market AND MID(RateTime,4,2) IN (00, 30) AND DAYOFWEEK(RateDate) <> 7 AND (DAYOFWEEK(RateDate) <> 6 or (DAYOFWEEK(RateDate) = 6 AND LEFT(RateTime,5) <= '17:00')) AND (DAYOFWEEK(RateDate) <> 1 or ((DAYOFWEEK(RateDate) = 1 AND LEFT(RateTime,5) >= '17:00')))
											  GROUP BY RateDate, LEFT(RateTime,5) 
											  ORDER BY RateDate DESC, RateTime DESC LIMIT 250) a 
								  UNION
								  SELECT a.RateDate, a.RateTime, a.RateBid AS `RateBid`, '1h' as `TimeFrame`
										FROM (SELECT RateDate, RateTime, RateBid
											  FROM rates.forex_history 
											  WHERE Product = :market AND MID(RateTime,4,2) IN (00) AND DAYOFWEEK(RateDate) <> 7 AND (DAYOFWEEK(RateDate) <> 6 or (DAYOFWEEK(RateDate) = 6 AND LEFT(RateTime,5) <= '17:00')) AND (DAYOFWEEK(RateDate) <> 1 or ((DAYOFWEEK(RateDate) = 1 AND LEFT(RateTime,5) >= '17:00')))
											  GROUP BY RateDate, LEFT(RateTime,5) 
											  ORDER BY RateDate DESC, RateTime DESC LIMIT 250) a 
								  UNION
								  SELECT a.RateDate, a.RateTime, a.RateBid AS `RateBid`, '4h' as `TimeFrame`
										FROM (SELECT RateDate, RateTime, RateBid
											  FROM rates.forex_history 
											  WHERE Product = :market AND MID(RateTime,4,2) IN (00) AND LEFT(RateTime, 2) IN (16, 20, 00, 04, 08, 12) AND DAYOFWEEK(RateDate) <> 7 AND (DAYOFWEEK(RateDate) <> 6 or (DAYOFWEEK(RateDate) = 6 AND LEFT(RateTime,5) <= '17:00')) AND (DAYOFWEEK(RateDate) <> 1 or ((DAYOFWEEK(RateDate) = 1 AND LEFT(RateTime,5) >= '17:00')))
											  GROUP BY RateDate, LEFT(RateTime,5) 
											  ORDER BY RateDate DESC, RateTime DESC LIMIT 250) a 
								  ORDER BY TimeFrame DESC, RateDate ASC, RateTime ASC");
		$stmt->bindParam(":market", $Market);
		$stmt->execute();
		$results = $stmt->fetch(PDO::FETCH_ASSOC);
	} catch(PDOException $error){
		echo "Error getting RSI information: " . $error;
	}
	
	foreach($results => $row){
		if($row["TimeFrame"] != $TimeFrame && !empty($TimeFrame)){
			try{
				$query = $conn->prepare("INSERT INTO rates.RSIs (Product, RSIDate, RSITime, RSI, TimeFrame) VALUES (:market, :ratedate, :ratetime, :rsi, :timeframe) ON DUPLICATE KEY UPDATE id=id");
				$query->execute(array(
					"market" => $Market,
					"ratedate" => $RateDate,
					"ratetime" => $RateTime,
					"rsi" => $RSI,
					"timeframe" => $TimeFrame
				));
			} catch(PDOException $error){
				echo "Error inserting RSI(1): " . $error;
			}
		}
		$rownumber++;
		$RateDate = $row["RateDate"];
		$RateTime = $row["RateTime"];
		$RateBid = $row["RateBid"];
		$TimeFrame = $row["TimeFrame"];
		if($rownumber == 1){
			$Change = "";
			$Gain = "";
			$Loss = "";
		} else {
			$Change = number_format($RateBid - $LastRateBid,5);
			if($Change > 0){
				$Gain = number_format($RateBid - $LastRateBid,5);
			} else if($Change < 0){
				$Gain = 0;
			}
			if($Change > 0){
				$Loss = 0;
			} else if($Change < 0){
				$Loss = number_format(-($RateBid - $LastRateBid),5);
			}
		}

		if($rownumber > 1 && $rownumber <= 100){
			if(number_format($RateBid - $LastRateBid,5) > 0){
				$SumGain += $Gain;
			}
			if(number_format($RateBid - $LastRateBid,5) < 0){
				$SumLoss += $Loss;
			}
			
			if($rownumber == 100){
				$AvgGain = number_format($SumGain / 100,5);
				$AvgLoss = number_format($SumLoss / 100,5);
				$RS = number_format($AvgGain / $AvgLoss,2);
				if($AvgLoss == 0){
					$RSI = 100;
				} else {
					$RSI = number_format(100 - (100 / (1 + $RS)),2);
				}
			}
		} else if($rownumber < 100){
			$AvgGain = "";
			$AvgLoss = "";
			$RS = "";
			$RSI = "";
		} else if($rownumber > 100){
			$AvgGain = number_format((($AvgGain*13) + $Gain)/14,5);
			$AvgLoss = number_format((($AvgLoss*13) + $Loss)/14,5);
			$RS = number_format($AvgGain / $AvgLoss,2);
			if($AvgLoss == 0){
				$RSI = 100;
			} else {
				$RSI = number_format(100 - (100/(1 + $RS)),2);
			}
			IF($AvgLoss == 0){
				$RSI = 100;
			} else {
				$RSI = number_format(100 - (100/(1 + $RS)),2);
			}
		}
		$LastRateBid = $RateBid;

		if($rownumber == count($results) && $rownumber > 114){
			try{
				$query = $conn->prepare("INSERT INTO rates.RSIs (Product, RSIDate, RSITime, RSI, TimeFrame) VALUES (:market, :ratedate, :ratetime, :rsi, :timeframe) ON DUPLICATE KEY UPDATE id=id");
				$query->execute(array(
					"market" => $Market,
					"ratedate" => $RateDate,
					"ratetime" => $RateTime,
					"rsi" => $RSI,
					"timeframe" => $TimeFrame
				));
			} catch(PDOException $error){
				echo "Error inserting RSI(2): " . $error;
			}
		}
	}

	$i = 1;
	$TimeFrame = "";
	
	try{
		$stmt = $conn->prepare("SELECT * FROM (
									SELECT * FROM rates.RSIs 
									WHERE Product = :market AND TimeFrame = '30m' 
									ORDER BY RSIDate Desc, RSITime DESC LIMIT 2) a
								UNION
								SELECT * FROM(
									SELECT * FROM rates.RSIs 
									WHERE Product = :market AND TimeFrame = '1h' 
									ORDER BY RSIDate Desc, RSITime DESC LIMIT 2) b
								UNION
								SELECT * FROM(
									SELECT * FROM rates.RSIs 
									WHERE Product = :market AND TimeFrame = '4h' 
									ORDER BY RSIDate Desc, RSITime DESC LIMIT 2) c
								ORDER BY FIELD(TimeFrame,'30m','1h','4h'), RSIDate ASC, RSITime ASC");
		$stmt->bindParam(":market", $Market);
		$stmt->execute();
		$results = $stmt->fetch(PDO::FETCH_ASSOC);

		$number = count($results);
		foreach($results => $row)){
			--$number;
			if(($row["TimeFrame"] == $TimeFrame || $number === 0) && !empty($TimeFrame)){
				if($RSI >= 69 && $row["RSI"] < 69 && $row["RSI"] >= 60){
					$ForexAPI->CreateTrigger($Market, "GatherRSI", "Sell Signal", $TimeFrame, $RSIDate, $RSITime);
					$Triggers[$Market][$TimeFrame] = "Sell";
				} else if($RSI <= 31 && $row["RSI"] > 31 && $row["RSI"] <= 40){
					$ForexAPI->CreateTrigger($Market, "GatherRSI", "Buy Signal", $TimeFrame, $RSIDate, $RSITime);
					$Triggers[$Market][$TimeFrame] = "Buy";
				} else if(($RSI < 50 && $row["RSI"] >= 50) || ($RSI > 50 && $row["RSI"] <= 50)){
					$ForexAPI->CreateTrigger($Market, "GatherRSI", "Pivot Signal", $TimeFrame, $RSIDate, $RSITime);
					$Triggers[$Market][$TimeFrame] = "Half Close";
				} else if(($row["RSI"] >= 75) || ($row["RSI"] <= 25)){
					$ForexAPI->CreateTrigger($Market, "GatherRSI", "Pivot Signal", $TimeFrame, $RSIDate, $RSITime);
					$Triggers[$Market][$TimeFrame] = "Close";
				}
			}
			$Product = $row["Product"];
			$RSIDate = $row["RSIDate"];
			$RSITime = $row["RSITime"];
			$RSI = $row["RSI"];
			$TimeFrame = $row["TimeFrame"];
		}
	} catch(PDOException $error){
		echo "Error getting RSI information(2): " . $error;
	}

	if(!empty($Triggers[$Market]["30m"])){
		$Action = $Triggers[$Market]["30m"];
		echo "<h3>Market: $Market | Action: $Action</h3><br><div style='min-height: 50px;'>";

		try{
			$stmt = $conn->prepare("SELECT M.Market, M.Decimals, M.MaxSqueeze, A.Amount, A.MaxRate, A.MinRate, A.TotalAmount, A.BuySell, A.Spread,
										   FORMAT(FH.MaxRate + (FH.MaxRate * .005), M.Decimals) AS `HighExitRate`, FORMAT(FH.MinRate - (FH.MinRate * 0.005), M.Decimals) AS `LowExitRate` 
									FROM test.Markets M 
									LEFT JOIN (SELECT MAX(Amount) AS `Amount`, Product, MAX(Rate) AS `MaxRate`, MIN(Rate) AS `MinRate`, SUM(Amount) AS `TotalAmount`, BuySell, Spread 
											   FROM test.APITrades 
											   WHERE Status = 'Active' AND Program = :program
											   GROUP BY Product) A ON M.Market = A.Product 
									LEFT JOIN (SELECT Product, MIN(RateAsk) AS `MinRate`, MAX(RateAsk) AS `MaxRate` FROM rates.forex_history 
											   WHERE RateDate BETWEEN :startdate AND :enddate AND Product = :market
											   GROUP BY Product) FH ON M.Market = FH.Product 
									WHERE M.Program = :program AND M.Market = :market
									ORDER BY Amount ASC");
			$stmt->bindParam(":startdate", date('Y-m-d',(strtotime('-14 day',strtotime(date("Y-m-d"))))));
			$stmt->bindParam(":enddate", date('Y-m-d',(strtotime('-7 day',strtotime(date("Y-m-d"))))));
			$stmt->bindParam(":program", $Program);
			$stmt->bindParam(":market", $Market);
			$stmt->execute();
			$results = $stmt->fetch(PDO::FETCH_ASSOC);

			foreach($results as $row){
				$Markets[$row["Market"]]["Decimal"] = $row["Decimals"];
				$Markets[$row["Market"]]["BuySell"] = $row["BuySell"];
				$Markets[$row["Market"]]["Spread"] = $row["Spread"];
				if($row["BuySell"] == "B") $Markets[$row["Market"]]["BuySellOpposite"] = "S"; 
					else if($row["BuySell"] == "S") $Markets[$row["Market"]]["BuySellOpposite"] = "B";
				$Markets[$row["Market"]]["MaxSqueeze"] = $row["MaxSqueeze"];
				$Markets[$row["Market"]]["TotalAmount"] = $row["TotalAmount"];
				if(!empty($row["Amount"])) $Markets[$row["Market"]]["Amount"] = $row["Amount"];
					else $Markets[$row["Market"]]["Amount"] = 2000;
				$Markets[$row["Market"]]["Rate"]["MaxRate"] = $row["MaxRate"];
				$Markets[$row["Market"]]["Rate"]["MinRate"] = $row["MinRate"];
				$Markets[$row["Market"]]["Rate"]["ExitRate"]["High"] = $row["HighExitRate"];
				$Markets[$row["Market"]]["Rate"]["ExitRate"]["Low"] = $row["LowExitRate"];
			}
		} catch(PDOException $error){
			echo "Error getting League information: " . $error;
		}

		if(!empty($Markets)){
			$rates = $ForexAPI->GetRateBlotter(array());
			$data = explode("$",$rates->getRateBlotterResult);
			foreach($data as $datapoint){
				$data = explode("\\",$datapoint);
				$decimals = (int)$data[6];
				$ratemarket = $data[0];
				$ask = number_format((float)$data[2],$decimals);
				
				if(array_key_exists($ratemarket, $Markets)){
					$Markets[$ratemarket]["Rate"]["Ask"] = $ask;
				}
			}
			$Ask = $Markets[$Market]["Rate"]["Ask"];
			
			$DecimalPlaces = strlen(substr(strrchr($Markets[$Market]["Rate"]["Ask"], "."), 1));
			$Multiplier = 1;
			for($i = 0; $i < $DecimalPlaces - 1; $i++) {
				$Multiplier .= 0;
			}
			
			try{
				$stmt = $conn->prepare("SELECT Rate FROM test.APITrades WHERE Status = 'Active' AND Program = :program AND Product = :market");
				$stmt->bindParam(":market", $Market);
				$stmt->bindParam(":program", $Program);
				$stmt->execute();
				$results = $stmt->fetch(PDO::FETCH_ASSOC);

				foreach($results as $row){
					$AskRate = number_format($row["Rate"], $Markets[$Market]["Decimal"]);
					if($Markets[$Market]["BuySell"] == "B"){
						$UnrealizedPIP += -(($AskRate - number_format($Ask, $Markets[$Market]["Decimal"])) * (int)$Multiplier) + $Markets[$Market]["Spread"];
					} else if($Markets[$Market]["BuySell"] == "S"){
						$UnrealizedPIP += (($AskRate - number_format($Ask, $Markets[$Market]["Decimal"])) * (int)$Multiplier) - $Markets[$Market]["Spread"];
					}
				}
			} catch(PDOException $error){
				echo "Error getting League information: " . $error;
			}
			
			if(!empty($UnrealizedPIP)) echo "<br />UnrealizedPIP: " . $UnrealizedPIP;
			
			if(!empty($UnrealizedPIP) && $UnrealizedPIP > 5)
				$Profitable = true;
			else
				$Profitable = false;
			
			$MaxRate = $Markets[$Market]["Rate"]["MaxRate"];
			$MinRate = $Markets[$Market]["Rate"]["MinRate"];
			$Markets[$Market]["SupportResistance"] = $ForexAPI->DetermineSupportResistance(str_replace('/','',$Market));

			$OneMonthTrend = $ForexAPI->DetermineTrendInvesting($Market, "1Month");
			$OneWeekTrend = $ForexAPI->DetermineTrendInvesting($Market, "1Week");

			$MonthTrend = explode("|",$OneMonthTrend);
			$WeekTrend = explode("|",$OneWeekTrend);
			echo "<br />Month Trend: " . $MonthTrend[0];
			echo "<br />Week Trend: " . $WeekTrend[0];

			if($Action == "Buy"){
				echo "<br />Profitable: $Profitable";
				if($Profitable){
					echo "<br />$Market: Close all positions profit. Buy Signal on sell";
					if($test) $ForexAPI->CloseMarketTrades(array("Program" => $Program, "Product" => $Market, "BuySell" => $Markets[$Market]["BuySellOpposite"], "Amount" => $Markets[$Market]["TotalAmount"], "Rate" => $Ask));
				}
				
				if($Markets[$Market]["Amount"] <= 25000){
					if(!empty($Markets[$Market]["SupportResistance"]["Daily"]["Support"])){
						$Supports = $Markets[$Market]["SupportResistance"]["Daily"]["Support"];
						if(!empty($Supports)){
							foreach($Supports as $key2 => $value2){
								echo "<br />$Market | Buy".$key2." ".number_format($value2,$Markets[$Market]["Decimal"])." ".$Ask."<br>";
								if($Ask < $Supports[$key2] && ($Ask < $MinRate || $MinRate == null) && ($MinRate > $Supports[$key2] || $MinRate == null)){
									if($MonthTrend[0] == "UpTrend" && $WeekTrend[0] == "UpTrend"){
										echo "<br />Buy Amount Support MonthUp/WeekUp: " . round($Markets[$Market]["Amount"] * 2.5) . "<br>";
										if($test) $ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => "B", "Amount" => round($Markets[$Market]["Amount"] * 2.5, -3), "Program" => $Program), 0);
									} else if($MonthTrend[0] == "UpTrend"){
										echo "<br />Buy Amount Support MonthUp: " . round($Markets[$Market]["Amount"] * 2) . "<br>";
										if($test) $ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => "B", "Amount" => round($Markets[$Market]["Amount"] * 2, -3), "Program" => $Program), 0);
									} else if($WeekTrend[0] == "UpTrend"){
										#I dont know if i like weekly trends yet
										//echo "<br />Buy Amount Support WeekUp: " . round($Markets[$Market]["Amount"] * 1.5) . "<br>";
										//if($test) $ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => "B", "Amount" => round($Markets[$Market]["Amount"] * 1.5, -3), "Program" => $Program), 0);
									}
								}
							}
						}
					} else {
						//do some magic to create own support/resists
						
					}
				}
			} else if($Action == "Sell"){
				echo "<br />Profitable: $Profitable";
				if($Profitable){
					echo "<br />$Market: Close all positions profit. Sell Signal on Buy";
					if($test) $ForexAPI->CloseMarketTrades(array("Program" => $Program, "Product" => $Market, "BuySell" => $Markets[$Market]["BuySellOpposite"], "Amount" => $Markets[$Market]["TotalAmount"], "Rate" => $Ask));
				}
				
				if($Markets[$Market]["Amount"] <= 25000){
					if(!empty($Markets[$Market]["SupportResistance"]["Daily"]["Resistance"])){
						$Resistances = $Markets[$Market]["SupportResistance"]["Daily"]["Resistance"];
						if(!empty($Resistances)){
							foreach($Resistances as $key2 => $value2){
								echo "<br />$Market | Sell".$key2." ".number_format($value2,$Markets[$Market]["Decimal"])." ".$Ask."<br>";
								if($Ask > $Resistances[$key2] && ($Ask > $MaxRate || $MaxRate == null) && ($MaxRate < $Resistances[$key2] || $MaxRate == null)){
									if($MonthTrend[0] == "DownTrend" && $WeekTrend[0] == "DownTrend"){
										echo "<br />Sell Amount Resist MonthDown/WeekDown: " . round($Markets[$Market]["Amount"] * 2.5) . "<br>";
										if($test) $ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => "S", "Amount" => round($Markets[$Market]["Amount"] * 2.5, -3), "Program" => $Program), 0);
									} else if($MonthTrend[0] == "DownTrend"){
										echo "<br />Sell Amount Resist MonthDown: " . round($Markets[$Market]["Amount"] * 2) . "<br>";
										if($test) $ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => "S", "Amount" => round($Markets[$Market]["Amount"] * 2, -3), "Program" => $Program), 0);
									} else if($WeekTrend[0] == "DownTrend"){
										#I dont know if i like weekly trends yet
										//echo "<br />Sell Amount Resist WeekDown: " . round($Markets[$Market]["Amount"] * 1.5) . "<br>";
										//if($test) $ForexAPI->CreateTrade(array("Product" => $Market, "BuySell" => "S", "Amount" => round($Markets[$Market]["Amount"] * 1.5, -3), "Program" => $Program), 0);
									}
								}
							}
						}
					} else {
						//do some magic to create own support/resists
						
					}
				}
			} else if($Action == "Close"){
				if($UnrealizedPIP < -15){
					if($Markets[$Market]["Amount"] > 2000){
						
						try{
							$stmt = $conn->prepare("SELECT * FROM (
														SELECT RSI, RSIDate, RSITime FROM rates.RSIs 
														WHERE Product = :market AND TimeFrame = '30m' 
														ORDER BY RSIDate DESC, RSITime DESC LIMIT 2) a
													ORDER BY RSIDate ASC, RSITime ASC");
							$stmt->bindParam(":market", $Market);
							$stmt->execute();
							$results = $stmt->fetch(PDO::FETCH_ASSOC);

							foreach($results as $row){
								if(empty($RSI1)){
									$RSI1 = $row["RSI"];
									$RSITime1 = $row["RSITime"];
								} else if(empty($RSI2)){
									$RSI2 = $row["RSI"];
									$RSITime2 = $row["RSITime"];
								}
							}
						} catch(PDOException $error){
							echo "Error getting League information: " . $error;
						}
						
						if(!empty($RSI2)){
							if($Markets[$Market]["BuySell"] == "B"){
								if($RSI2 <= 25){
									echo "<br />$Market: Close all positions loss. Ask strength below 15 RSI";
									if($test) $ForexAPI->CloseMarketTrades(array("Program" => $Program, "Product" => $Market, "BuySell" => $Markets[$Market]["BuySellOpposite"], "Amount" => $Markets[$Market]["TotalAmount"], "Rate" => $Ask));
								}
							} else if($Markets[$Market]["BuySell"] == "S"){
								if($RSI2 >= 75){
									echo "<br />$Market: Close all positions loss. Ask strength above 85 RSI";
									if($test) $ForexAPI->CloseMarketTrades(array("Program" => $Program, "Product" => $Market, "BuySell" => $Markets[$Market]["BuySellOpposite"], "Amount" => $Markets[$Market]["TotalAmount"], "Rate" => $Ask));
								}
							}
						}
					}
				}
			} else if($Action == "Half Close") {
				if($Markets[$Market]["TotalAmount"] >= 10000){ //fix this
				
					try{
						$stmt = $conn->prepare("SELECT * FROM (
													SELECT RSI, RSIDate, RSITime FROM rates.RSIs 
													WHERE Product = :market AND TimeFrame = '30m' 
													ORDER BY RSIDate DESC, RSITime DESC LIMIT 2) a
												ORDER BY RSIDate ASC, RSITime ASC");
						$stmt->bindParam(":market", $Market);
						$stmt->execute();
						$results = $stmt->fetch(PDO::FETCH_ASSOC);

						foreach($results as $row){
							if(empty($RSI1)){
								$RSI1 = $row["RSI"];
								$RSITime1 = $row["RSITime"];
							} else if(empty($RSI2)){
								$RSI2 = $row["RSI"];
								$RSITime2 = $row["RSITime"];
							}
						}
					} catch(PDOException $error){
						echo "Error getting League information: " . $error;
					}
					
					try{
						$stmt = $conn->prepare("SELECT Amount, DealReference, Rate FROM test.APITrades WHERE Status = 'Active' and Product = :market AND Program = :program ORDER BY Amount DESC LIMIT 1");
						$stmt->bindParam(":market", $Market);
						$stmt->bindParam(":program", $Program);
						$stmt->execute();
						$results = $stmt->fetch(PDO::FETCH_ASSOC);

						foreach($results as $row){
							$Amount = $row["Amount"];
							$DealReference = $row["DealReference"];
							$Rate = $row["Rate"];
						}
					} catch(PDOException $error){
						echo "Error getting League information: " . $error;
					}
					
					if(!empty($DealReference)){
						if($Markets[$Market]["BuySell"] == "B"){
							if(!empty($RSI1) && !empty($RSI2) && ($RSI1 < 50 || $RSI2 >= 50) && $Ask >= $Rate){
								echo "<br />$Market: Close half position profit. Ask RSI crossed 50";
								if($test) $ForexAPI->CloseTrade(array("Program" => $Program, "Product" => $Market, "BuySell" => $Markets[$Market]["BuySellOpposite"], "Amount" => $Amount, "Rate" => $Ask), $DealReference, "Profit", $UnrealizedPIP);
							}
						} else if($Markets[$Market]["BuySell"] == "S"){
							if(!empty($RSI1) && !empty($RSI2) && ($RSI1 > 50 || $RSI2 <= 50) && $Ask <= $Rate){
								echo "<br />$Market: Close half positions profit. Ask RSI crossed 50";
								if($test) $ForexAPI->CloseTrade(array("Program" => $Program, "Product" => $Market, "BuySell" => $Markets[$Market]["BuySellOpposite"], "Amount" => $Amount, "Rate" => $Ask), $DealReference, "Profit", $UnrealizedPIP);
							}
						}
					}
				}
			} else {
				echo "No action determined.";
			}
		}
		echo "</div>";
	} else {
		echo "<h3>Market: $Market | Action: No Action</h3><br><div style='min-height: 50px;'></div>";
	}
}
?>