<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);

require_once('functions.php');
require_once('Forex.php');

$MaxSqueeze = .100;
$SqueezeCounter = 0;
$SqueezePipTotal = 0;

$thismonth = date('m');
$lastmonth_startday = date("Y-m-d", mktime(0, 0, 0, date("m")-1, 1));
$lastmonth_endday = date("Y-m-d", mktime(0, 0, 0, date("m"), 0));

try{
	$stmt = $conn->prepare("SELECT id, RateDate, RateTime, OpenRate
							FROM rates.histdata_history h 
							WHERE (h.RateDate BETWEEN :lastmonth_startday AND :lastmonth_endday OR MID(h.RateDate, 6, 2) = :thismonth) AND h.Product = 'USD/JPY' 
							ORDER BY h.RateDate ASC, RateTime ASC");
	$stmt->bindParam(":lastmonth_startday", $lastmonth_startday);
	$stmt->bindParam(":lastmonth_endday", $lastmonth_endday);
	$stmt->bindParam(":thismonth", $thismonth);
	$stmt->execute();
	$results = $stmt->fetch(PDO::FETCH_ASSOC);
	if($results){
		if(count($results) > 1) {
			foreach($results as $row){
				$RateDate = $row["RateDate"];
				$RateTime = $row["RateTime"];
				if(!empty($InitialOpenRate)){
					$RateChange = number_format($InitialOpenRate - $row["OpenRate"],3);
					if(abs($RateChange) < $MaxSqueeze){
						if($LastRate != 0){
							$SqueezePipTotal += abs($row["OpenRate"] - $LastRate) * 100;
							//echo $row["RateDate"] . " " . $row["RateTime"] . " abs(" . $row["OpenRate"] . " - " . $LastRate . ") * 100 = " . abs($row["OpenRate"] - $LastRate) * 100 . "<br>";
							
						}
						$LastRate = $row["OpenRate"];
						$SqueezeCounter++;
					} else {
						if($row["id"] != $IntialID){
							if($SqueezeCounter > 60 * 8){
								if(empty($Time[substr($InitialTime,0,2)."-".substr($row["RateTime"],0,2)])) $Time[substr($InitialTime,0,2)."-".substr($row["RateTime"],0,2)] = 1; else $Time[substr($InitialTime,0,2)."-".substr($row["RateTime"],0,2)]++;
								echo "Start: ($InitialDate $InitialTime) End: (".$row["RateDate"] . " " . $row["RateTime"].") | PipTotal: $SqueezePipTotal | 8+ hour squeeze<br>";
							} else if($SqueezeCounter > 60 * 4){
								if(empty($Time[substr($InitialTime,0,2)."-".substr($row["RateTime"],0,2)])) $Time[substr($InitialTime,0,2)."-".substr($row["RateTime"],0,2)] = 1; else $Time[substr($InitialTime,0,2)."-".substr($row["RateTime"],0,2)]++;
								echo "Start: ($InitialDate $InitialTime) End: (".$row["RateDate"] . " " . $row["RateTime"].") | PipTotal: $SqueezePipTotal | 4-8 hour squeeze<br>";
							} else if($SqueezeCounter > 60 * 3){
								if(empty($Time[substr($InitialTime,0,2)."-".substr($row["RateTime"],0,2)])) $Time[substr($InitialTime,0,2)."-".substr($row["RateTime"],0,2)] = 1; else $Time[substr($InitialTime,0,2)."-".substr($row["RateTime"],0,2)]++;
								echo "Start: ($InitialDate $InitialTime) End: (".$row["RateDate"] . " " . $row["RateTime"].") | PipTotal: $SqueezePipTotal | 3-4 hour squeeze<br>";
							}
						}
						$SqueezeCounter = 0;
						$SqueezePipTotal = 0;
						$IntialID = $row["id"];
						$LastRate = 0;
						$InitialOpenRate = number_format($row["OpenRate"],3);
						$InitialDate = $row["RateDate"];
						$InitialTime = $row["RateTime"];
					}
				} else {
					$IntialID = $row["id"];
					$InitialOpenRate = number_format($row["OpenRate"],3);
					$InitialDate = $row["RateDate"];
					$InitialTime = $row["RateTime"];
				}
			}
		} else {
			echo "No Records Found.";
		}
	}
} catch(PDOException $error){
	echo "Error gathering history data: $error";
}
ksort($Time);
echo "<pre>";
	print_r($Time);
echo "</pre>";

arsort($Time);
$TopTime = array_slice($Time, 0, 5);

echo "<pre>";
	print_r($TopTime);
echo "</pre>";