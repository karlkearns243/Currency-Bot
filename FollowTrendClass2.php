<?php
require_once('functions.php');
require_once('Forex.php');
require_once('Mail.php');

class FollowTrend extends ForexAPIs
{
	private $Errors;
	
	function __construct(){
		parent::__construct();
	}
	
	public function getErrors(){
		return $this->Errors;
	}
	
	public function clearErrors(){
		$this->Errors = "";
	}
	
	public function DetermineTrend($Market, $IsSqueeze, $Decimal){
		$Rates = array();
		$CountTrendUp = 0;
		$CountTrendDown = 0;
		$Trend = "";
		
		//gather how both markets are doing as individual wholes.
		
		
		$results = $this->GetLatestChartData(array("Product" => $Market, "TimeInterval" => "ONE_MINUTE", "Datetime" => date("Y-m-d")));
		$data = explode("$",$results->GetLatestChartDataResult->Data);
		foreach($data as $datapoint){
			$data = explode("\\",$datapoint);
			$datetime = explode(" ", $data[0]);
			$open = $data[1];
			$date = $datetime[0];
			$time = $datetime[1];
			$datetime = new DateTime($date.' '.$time);
			if(!empty($currentdatetime)) $DateDifference = date_diff($datetime, $currentdatetime);
			//if(!empty($DateDifference)) echo "Hour Difference: " . $DateDifference->h."<br>";
			if(!empty($DateDifference) && $DateDifference->h) $Rates[$DateDifference->h."h"] = $open;
			if(!empty($DateDifference) && $DateDifference->h == 0 && $DateDifference->i <= 30) $Rates["30m"] = $open;
			if(!empty($DateDifference) && $DateDifference->h == 0 && $DateDifference->i <= 15) $Rates["15m"] = $open;
			if(!empty($DateDifference) && $DateDifference->h == 0 && $DateDifference->i <= 3) $Rates["3m"] = $open;
			if(!empty($DateDifference) && $DateDifference->h == 0 && $DateDifference->i <= 1) $Rates["1m"] = $open;
			array_push($data,$date);
			array_push($data,$time);
			//print_r($data);
			if(empty($currentdate)) $currentdate = $date;
			if(empty($currenttime)) $currenttime = $time;
			if(empty($currentdatetime)) $currentdatetime = new Datetime($currentdate.' '.$currenttime);
			if(empty($Rates["Current"])) $Rates["Current"] = $open;
		}
		if(!empty($Rates["6h"]) && $Rates["Current"] > $Rates["6h"]){ $Trend6h = "Up"; $CountTrendUp++; } else { $Trend6h = "Down"; $CountTrendDown++;}
		if(!empty($Rates["4h"]) && $Rates["Current"] > $Rates["4h"]){ $Trend4h = "Up"; $CountTrendUp++; } else { $Trend4h = "Down"; $CountTrendDown++;}
		if(!empty($Rates["2h"]) && $Rates["Current"] > $Rates["2h"]){ $Trend2h = "Up"; $CountTrendUp++; } else { $Trend2h = "Down"; $CountTrendDown++;}
		if(!empty($Rates["1h"]) && $Rates["Current"] > $Rates["1h"]){ $Trend1h = "Up"; $CountTrendUp++; } else { $Trend1h = "Down"; $CountTrendDown++;}
		if(!empty($Rates["30m"]) && $Rates["Current"] > $Rates["30m"]){ $Trend30m = "Up"; $CountTrendUp++; } else { $Trend30m = "Down"; $CountTrendDown++;}
		if(!empty($Rates["15m"]) && $Rates["Current"] > $Rates["15m"]){ $Trend15m = "Up"; $CountTrendUp++; } else { $Trend15m = "Down"; $CountTrendDown++;}
		
		//echo "CountTrendUp: " . $CountTrendUp."<br>";
		//echo "CountTrendDown: " . $CountTrendDown."<br>";
		//echo $Trend4h . "|". $Trend1h ."|". $Trend30m ."|". $Trend15m;
		//echo "<br>";
		
		//1 min .25
		if(!empty($Decimal) && !empty($Rates["Current"]) && !empty($Rates["1m"])){
			$RateChange = number_format($Rates["Current"] - $Rates["1m"], $Decimal);
			if($Decimal == 3){
				$RateChangeCap1 = .125;
				$RateChangeCap3 = .250;
			} else if($Decimal == 4){
				$RateChangeCap1 = .0175;
				$RateChangeCap3 = .0350;
			} else if($Decimal == 5){
				$RateChangeCap1 = .00150;
				$RateChangeCap3 = .00300;
			}
			if(abs($RateChange) >= $RateChangeCap1){
				if(!empty($Rates["1m"]) && $Rates["Current"] > $Rates["1m"]){ $Trend = "Up"; $CountTrendUp++; } else { $Trend = "Down"; $CountTrendDown++;}
				if(!empty($Trend)) return $Trend."|".$CountTrendUp."|".$CountTrendDown."|Spike";
			}
			//3 mins .50
			$RateChange = number_format($Rates["Current"] - $Rates["3m"], 6);
			if(abs($RateChange) >= $RateChangeCap3){
				if(!empty($Rates["3m"]) && $Rates["Current"] > $Rates["3m"]){ $Trend = "Up"; $CountTrendUp++; } else { $Trend = "Down"; $CountTrendDown++;}
				if(!empty($Trend)) return $Trend."|".$CountTrendUp."|".$CountTrendDown."|Spike";
			}
		}
		
		//after huge spike and first 15 mins goes other way do we hold off at least an hour to see new trend?
		
		
		
		if($IsSqueeze){ //stupid see if its at top or bottom of squeeze and make entry
			if($Rates["15m"] == "Down" && $Rates["30m"] == "Down") $Trend = "Down";
			if($Rates["15m"] == "Up" && $Rates["30m"] == "Up") $Trend = "Up";
			
			if(!empty($Trend)) return $Trend."|".$CountTrendUp."|".$CountTrendDown;
		} else {
			//Obvious Trends
			if($CountTrendUp == 6) $Trend = "Up";
			if($CountTrendDown == 6) $Trend = "Down";
			if(!empty($Trend)) return $Trend."|".$CountTrendUp."|".$CountTrendDown;
			
			if($CountTrendUp >= 4 && $Rates["15m"] == "Down") $Trend = ""; else if($CountTrendUp >= 4) $Trend = "Up";
			if($CountTrendDown >= 4 && $Rates["15m"] == "Up") $Trend = ""; else if($CountTrendDown >= 4) $Trend = "Down";
			if(!empty($Trend)) return $Trend."|".$CountTrendUp."|".$CountTrendDown;
			
			if($CountTrendUp >= 2 && $Rates["15m"] == "Down" && $Rates["30m"] == "Down") $Trend = "Down";
			if($CountTrendDown >= 2 && $Rates["15m"] == "Up" && $Rates["30m"] == "Up") $Trend = "Up";
			if(!empty($Trend)) return $Trend."|".$CountTrendUp."|".$CountTrendDown;
		}
		
		//echo $Trend4h . "|". $Trend1h ."|". $Trend30m ."|". $Trend15m;
		//print_r($Rates);
	}

	public function IsSqueeze($Market, $MaxSqueeze, $Decimals){
		$SqueezeCounter = 0;
		$Rates = array();
		
		$results = $this->GetLatestChartData(array("Product" => $Market, "TimeInterval" => "ONE_MINUTE", "Datetime" => date("Y-m-d")));
		$data = explode("$",$results->GetLatestChartDataResult->Data);
		foreach($data as $datapoint){
			$data = explode("\\",$datapoint);
			$datetime = explode(" ", $data[0]);
			$open = $data[1];
			$date = $datetime[0];
			$time = $datetime[1];
			$datetime = new DateTime($date.' '.$time);
			if(!empty($currentdatetime)) $DateDifference = date_diff($datetime, $currentdatetime);
			if(!empty($DateDifference) && ($DateDifference->h == 0 || $DateDifference->h == 1)){
				$RateChange = number_format($Rates["Current"] - $open, $Decimals);
				$RateChange2 = number_format($open - $Rates["Current"], $Decimals);
				$RateChange = max($RateChange, $RateChange2);
				if($RateChange < $MaxSqueeze){
					$SqueezeCounter++;
					if(!empty($Rates["MaxRate"]) && $open > $Rates["MaxRate"]) $Rates["MaxRate"] = $open;
					if(!empty($Rates["MinRate"]) && $open < $Rates["MinRate"]) $Rates["MinRate"] = $open;
					if($SqueezeCounter > 90){
						//echo $Market."|Squeeze<br>";
						$MaxDifference = abs(number_format($Rates["Current"] - $Rates["MaxRate"], $Decimals));
						$MinDifference = abs(number_format($Rates["Current"] - $Rates["MinRate"], $Decimals));
						if($MaxDifference < $MinDifference) if($MaxDifference < .00012) $BuySell = "S"; else $BuySell = ""; else if($MinDifference < .00012) $BuySell = "B"; else $BuySell = "";
						return 1 . "|" . $Rates["MaxRate"] . "|" . $Rates["MinRate"] . "|" . $Rates["Current"] . "|" . $BuySell;
					}
				} else {
					//echo $SqueezeCounter . ": " . abs($RateChange)."<br>";
					//echo $Market."|Not Squeeze<br>";
					$SqueezeCounter = 0;
					$Rates["MaxRate"] = 0;
					$Rates["MinRate"] = 0;
					return 0;
				}
			}
			
			if(empty($currentdate)) $currentdate = $date;
			if(empty($currenttime)) $currenttime = $time;
			if(empty($currentdatetime)) $currentdatetime = new Datetime($currentdate.' '.$currenttime);
			if(empty($Rates["Current"])) $Rates["Current"] = $open;
			if(empty($Rates["MaxRate"])) $Rates["MaxRate"] = $open;
			if(empty($Rates["MinRate"])) $Rates["MinRate"] = $open;
		}
		return 0;
	}

	public function IsGoodEntry($Market, $BuySell, $Decimal, $IsSqueeze){
		$counter = 0;

		$results = $this->GetLatestChartData(array("Product" => $Market, "TimeInterval" => "ONE_MINUTE", "Datetime" => date("Y-m-d")));
		$data = explode("$",$results->GetLatestChartDataResult->Data);
		$SupportResistances = $this->DetermineSupportResistance($Market);
		foreach($data as $datapoint){
			$data = explode("\\",$datapoint);
			$datetime = explode(" ", $data[0]);
			if(!empty($data[1])){
				$open = $data[1];
				$date = $datetime[0];$time = $datetime[1];
				$datetime = new DateTime($date.' '.$time);
				if(!empty($currentdatetime)) $DateDifference = date_diff($datetime, $currentdatetime);
				
				if(!empty($Rates["Current"])){
					if(!empty($DateDifference) && $DateDifference->h == 0 && $DateDifference->i <= 5){
						$counter++;
						if($BuySell == "B"){
							if($Rates["Current"] < $open){
								//echo "Not good buy entry: $Market";
								return false;
							}
						} else if($BuySell == "S"){
							if($Rates["Current"] > $open){
								//echo "Not good sell entry: $Market";
								return false;
							}
						}
					}
				}

				if(empty($currentdate)) $currentdate = $date;
				if(empty($currenttime)) $currenttime = $time;
				if(empty($currentdatetime)) $currentdatetime = new Datetime($currentdate.' '.$currenttime);
				if(empty($Rates["Current"])) $Rates["Current"] = $open;
			}
		}

		if(!$IsSqueeze){
			//if rate is close to resistance/support we need to determine if its breaking or not before entering trade
			//currently if its close to a resistance/support we just dont enter trade
			if(!empty($Rates["Current"]) && !empty($SupportResistances)){
				$currentrate = (float)$Rates["Current"];
				if($BuySell == "B"){
					foreach($SupportResistances[$Market]["Resistance"] as $key => $value){
						$resistrate = floatval($value);
						$RateDifference = $currentrate - $resistrate;
						//echo "$Market | Buy | " . $currenttest . " | " .  $valuetest . " | " . number_format(floatval(abs($RateDifference)),$Decimal) . "|";
						if($Decimal == 3){
							if(abs($RateDifference) < .025) return false; //echo "Not good trade"; else echo "Good trade";
						} else if($Decimal == 4){
							if(abs($RateDifference) < .0025) return false; //echo "Not good trade"; else echo "Good trade";
						} else if($Decimal == 5){
							if(abs($RateDifference) < .00025) return false; //echo "Not good trade"; else echo "Good trade";
						}
						//echo "<br>";
					}
				} else if($BuySell == "S"){
					foreach($SupportResistances[$Market]["Support"] as $key => $value){
						$supportrate = floatval($value);
						$RateDifference = $currentrate - $supportrate;
						//echo "$Market | Sell | " . $currenttest . " | " .  $valuetest . " | " . $RateDifference . "<br>";
						if($Decimal == 3){
							if(abs($RateDifference) < .025) return false; //echo "Not good trade"; else echo "Good trade";
						} else if($Decimal == 4){
							if(abs($RateDifference) < .0025) return false; //echo "Not good trade"; else echo "Good trade";
						} else if($Decimal == 5){
							if(abs($RateDifference) < .00025) return false; //echo "Not good trade"; else echo "Good trade";
						}
					}
				}
			}

			//echo "$Market|Good Entry counter: " . $counter . "<br>";
			if($counter >= 5) return true;
			return false;
		}
	}

	public function DetermineSupportResistance($Market){
		$SupportResistance = array();
		$today = date('Y-m-d');
		
		try{
			$query = $conn->prepare("(SELECT DFX.*, M.Decimals 
									 FROM rates.DailyFX DFX 
									 INNER JOIN test.Markets M ON REPLACE(DFX.Product,'/','') = REPLACE(M.Market,'/','')
									 WHERE DFX.Product = :market AND Date(DFX.DateCreated) = :today AND TimeFrame = 'Monthly' LIMIT 1)
									UNION
									(SELECT DFX.*, M.Decimals 
									 FROM rates.DailyFX DFX 
									 INNER JOIN test.Markets M ON REPLACE(DFX.Product,'/','') = REPLACE(M.Market,'/','')
									 WHERE DFX.Product = :market AND Date(DFX.DateCreated) = :today AND TimeFrame = 'Weekly' LIMIT 1)
									UNION
									(SELECT DFX.*, M.Decimals 
									 FROM rates.DailyFX DFX 
									 INNER JOIN test.Markets M ON REPLACE(DFX.Product,'/','') = REPLACE(M.Market,'/','')
									 WHERE DFX.Product = :market AND Date(DFX.DateCreated) = :today AND TimeFrame = 'Daily' LIMIT 1)
									UNION
									(SELECT DFX.*, M.Decimals 
									 FROM rates.DailyFX DFX 
									 INNER JOIN test.Markets M ON REPLACE(DFX.Product,'/','') = REPLACE(M.Market,'/','')
									 WHERE DFX.Product = :market AND Date(DFX.DateCreated) = :today AND TimeFrame = 'Hourly' LIMIT 1)");
			$query->bindParam(":today", $today);
			$query->bindParam(":market", $Market);
			$query->execute();
			$results = $query->fetch(PDO::FETCH_ASSOC);
			if(!empty(count($results))){
				foreach($results as $row){
					$Decimals = $row["Decimals"];
					$Support1 = $row["Support1"];
					$Support2 = $row["Support2"];
					$Support3 = $row["Support3"];
					$Pivot = $row["Pivot"];
					$Resistance1 = $row["Resistance1"];
					$Resistance2 = $row["Resistance2"];
					$Resistance3 = $row["Resistance3"];
					$Timeframe = $row["Timeframe"];
					
					$SupportResistance[$Timeframe]["Support"]["L1"] = $Support1;
					$SupportResistance[$Timeframe]["Support"]["L2"] = $Support2;
					$SupportResistance[$Timeframe]["Support"]["L3"] = $Support3;
					$SupportResistance[$Timeframe]["Pivot"] = $Pivot;
					$SupportResistance[$Timeframe]["Resistance"]["L1"] = $Resistance1;
					$SupportResistance[$Timeframe]["Resistance"]["L2"] = $Resistance2;
					$SupportResistance[$Timeframe]["Resistance"]["L3"] = $Resistance3;
				}
			} else {
				//scape info
				return false;
			}
			
			return $SupportResistance;
		} catch(PDOException  $error){
			$this->Errors = "Error gathering invest hirtory rates(1): " . $error->getMessage();
			echo "Error gathering invest hirtory rates(1): " . $error->getMessage();
			
			return false;
		}
		return false;
	}
	
	public function DetermineTrendInvesting($Market, $timeperiod){
		$enddate = date("Y-m-d");
		if($timeperiod == "1Month"){
			$startdate = date('Y-m-d',(strtotime('-30 day', strtotime($enddate))));
		} else if($timeperiod == "1Week"){
			$startdate = date('Y-m-d',(strtotime('-7 day', strtotime($enddate))));
		} else {
			return false;
		}
		
		try{
			$query = $conn->prepare("SELECT SUM(ChangePercent) AS `ChangePercent` FROM rates.invest_history WHERE RateDate BETWEEN :startdate AND :enddate AND Product = :market");
			$query->bindParam(":startdate", $startdate);
			$query->bindParam(":enddate", $enddate);
			$query->bindParam(":market", $Market);
			$query->execute();
			$results = $query->fetch(PDO::FETCH_ASSOC);
			if(!empty(count($results))){
				foreach($results as $row){
					$ChangePercent = $row["ChangePercent"];
					if($ChangePercent >= .40) return "UpTrend|$ChangePercent";
					if($ChangePercent <= -.40) return "DownTrend|$ChangePercent";
					return "Squeeze|$ChangePercent";
				}
			}
			
			return true;
		} catch(PDOException  $error){
			$this->Errors = "Error gathering invest hirtory rates(1): " . $error->getMessage();
			echo "Error gathering invest hirtory rates(1): " . $error->getMessage();
			
			return false;
		}
		
		return false;
	}
	
	public function CreateTrigger($Market, $Program, $Trigger, $TimeFrame, $Date, $Time){
		try{
			$query = $conn->prepare("INSERT INTO test.`Triggers` (`Status`, `Program`, `Product`, `Trigger`, `TimeFrame`, `Date`, `Time`) VALUES ('Active', :program, :market, :trigger, :timeframe, :date, :time) ON DUPLICATE KEY UPDATE id=id");
			$query->execute(array(
				"program" => $Program,
				"market" => $Market,
				"trigger" => $Trigger,
				"timeframe" => $TimeFrame,
				"date" => $Date,
				"time" => $Time
			));
			
			return true;
		} catch(PDOException  $error){
			$this->Errors = "Error inserting trigger(1): " . $error->getMessage();
			echo "Error inserting trigger(1): " . $error->getMessage();
			
			return false;
		}

		return false;
	}
	
	public function SendTextMessage($from, $to, $cc, $receipents, $subject, $message){
		$headers = array(
			'From' => $from,
			'To' => $to,
			'Cc' => $cc,
			'Subject' => $subject
		);

		$smtp = Mail::factory('smtp', array(
			'host' => 'ssl://smtp.gmail.com',
			'port' => '465',
			'auth' => true,
			'username' => 'karlkearns243@gmail.com',
			'password' => 'cqosnqttfxgrengh'
		));
		$mail = $smtp->send($recipients, $headers, $body);

		if (PEAR::isError($mail)) {
			echo('<p>Failed to Text: ' . $mail->getMessage() . '</p>');
		}
	}
}
?>