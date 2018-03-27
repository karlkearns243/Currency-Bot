<?php
//test
require_once('functions.php');
require_once('DateInterval.php');


class stdObject {
    public function __construct(array $arguments = array()) {
        if (!empty($arguments)) {
            foreach ($arguments as $property => $argument) {
                $this->{$property} = $argument;
            }
        }
    }

    public function __call($method, $arguments) {
        $arguments = array_merge(array("stdObject" => $this), $arguments); // Note: method argument 0 will always referred to the main class ($this).
        if (isset($this->{$method}) && is_callable($this->{$method})) {
            return call_user_func_array($this->{$method}, $arguments);
        } else {
            throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
        }
    }
}

class ForexAPIs{
	private $Token;
	private $defaults;
	
	function __construct($ApplicationName = "ForexClass"){
		$this->Token = FOREXAPI_TOKEN;
		$this->defaults = array("ApplicationName" => $ApplicationName, "IPAddress" => "127.0.0.1", "MachineName" => "Forex Production Server", "Language" => "English", "Token" => $this->Token);
	}

	//Creates Trade record in database and sends api to forex to make trade
	function CreateTrade($values, $Spike = 0, $StopLimit = null, $SoftStopLimit = null, $Program = null){
		$values = array(array_merge($this->defaults, $values));
		if(!empty($StopLimit)) $StopLimit = "'$StopLimit'"; else $StopLimit = "NULL";
		if(!empty($SoftStopLimit)) $SoftStopLimit = "'$SoftStopLimit'"; else $SoftStopLimit = "NULL";
		if(empty($Program) && !empty($values[0]["Program"])) $Program = $values[0]["Program"]; else $Program = "NULL";
		
		$results = $this->GetRate(array("Product" => $values[0]["Product"]));
		$data = explode("$", $results->getRateResult);
		foreach($data as $products){
			$product = explode("\\", $products);
			$Spread = number_format(($product[2] / $product[1]),1);
			$Spread = ((str_replace(".","",substr($product[2],-4)) - str_replace(".","",substr($product[1],-4))) / 10);
		}
		
		//insert api trade into table before attempting api call in case it fails.
		try{
			$query = $conn->prepare("INSERT INTO APITrades (Product, Spread, BuySell, Amount, Status, Spike, VersionID, StopLimit, SoftStopLimit, Program) VALUES (:product, :spread, :buysell, :amount, :status, :spike, (SELECT MAX(id) FROM VERSIONS), :stoplimit, :softstoplimit, :program)");
			$query->execute(array(
				"product" => $values[0]["Product"],
				"spread" => $Spread,
				"buysell" => $values[0]["BuySell"],
				"amount" => $values[0]["Amount"],
				"status" => "Active",
				"spike" => $Spike,
				"stoplimit" => null,
				"softstoplimit" => null,
				"program" => $Program
			));
			$tradeid = $conn->lastInsertId();
		} catch(PDOException $error){
			echo "Error inserting API trade: " . $error->getMessage();
		}
		
		//perform forex api call to execute trade.
		$results = $this->ExecuteAPI("DealRequestAtBest", $values, "Trading");
		print_r($results); //show information on page
		
		$Rate = $results->DealRequestAtBestResult->rate;
		$ReferenceID = $results->DealRequestAtBestResult->dealId;
		
		//finish updating api trade info from api results
		try{
			$query = $conn->prepare("UPDATE APITrades SET Rate = :rate, DealReference = :referenceid WHERE id = :tradeid");
			$query->execute(array(
				"rate" => $Rate,
				"referenceid" => $ReferenceID,
				"tradeid" => $tradeid
			));
		} catch(PDOException $error){
			echo "Error updateing API trade information: " . $error->getMessage();
		}
		
		return $results;
	}
	
	function CloseMarketTrades($values){
		$values = array(array_merge($this->defaults, $values));
		$results = $this->ExecuteAPI("DealRequestAtBest", $values, "Trading");
		
		$Rate = $results->DealRequestAtBestResult->rate;
		try{
			$query = $conn->prepare("UPDATE APITrades SET ExitRate = :rate, Status = :status WHERE Program = :program AND Product = :product AND Status = :filterstatus");
			$query->execute(array(
				"rate" => $Rate,
				"program" => $values[0]["Program"],
				"product" => $values[0]["Product"],
				"status" => "Closed",
				"filterstatus" => "Active"
			));
		} catch(PDOException $error){
			echo "Error updateing API trade information: " . $error->getMessage();
		}

		return $results;
	}
	
	//Close Trade
	function CloseTrade($values, $DealReference, $ProfitLoss, $FinalPIP){
		$values = array(array_merge($this->defaults, $values));
		$results = $this->ExecuteAPI("DealRequestAtBest", $values, "Trading");
		
		$Rate = $results->DealRequestAtBestResult->rate;
		if(empty($ProfitLoss)) $ProfitLoss = NULL;
		if(empty($FinalPIP))  $FinalPIP = NULL;
		
		try{
			$query = $conn->prepare("UPDATE APITrades SET ExitRate = :rate, Status = :status, ProfitLoss = :profitloss, FinalPIP = :finalpip WHERE DealReference = :dealreference");
			$query->execute(array(
				"rate" => $Rate,
				"profitloss" => $ProfitLoss,
				"status" => "Closed",
				"finalpip" => $FinalPIP,
				"dealreference" => $DealReference
			));
		} catch(PDOException $error){
			echo "Error updateing API trade information: " . $error->getMessage();
		}
		
		return $results;
	}
	
	//Open Trades
	function OpenTrades($values){
		$values = array(array_merge($this->defaults, $values));
		return $this->ExecuteAPI("GetOpenDealBlotter", $values, "Trading");
	}
	
	//
	function GetDealInfo($values){
		$values = array(array_merge($this->defaults, $values));
		return $this->ExecuteAPI("GetOpenDealBlotter", $values, "Trading");
	}

	//Customer Deal Activity
	function DealActivity($values){
		$values = array(array_merge($this->defaults, $values));
		return $this->ExecuteAPI("GetDealInfoBlotter", $values, "Trading");
	}
	
	function GetMarginBlotter($values){
		$values = array(array_merge($this->defaults, $values));
		return $this->ExecuteAPI("GetMarginBlotter", $values, "Trading");
	}

	//Current Day Rates
	//Pair\BID\OFFER\STATUS\HIGH\LOW\DECIMALS\NOTATION\CLOSINGBID\CONTRACTPAIR\COUNTERPAIR
	function GetRateAPI($values){
		$values = array(array_merge($this->defaults, $values));
		return $this->ExecuteAPI("getRate", $values, "Rates");
	}
	
	function GetRate($values){
		$datastring = "";
		$Product = $values["Product"];
		
		$stmt = $conn->prepare("SELECT * FROM rates.forex_history 
								WHERE Product = :product AND CONCAT(RateDate,' ',RateTime) > :datetime
								ORDER BY id DESC LIMIT 1");
		$stmt->bindParam(":product", $Product);
		$stmt->bindParam(":datetime", date("Y-m-d H:i:s", strtotime('-1 minutes')));
		$stmt->execute();
		$results = $stmt->fetch(PDO::FETCH_ASSOC);
		if(!empty($results)){
			foreach($results as $row){
				$RateBid = $row["RateBid"];
				$RateAsk = $row["RateAsk"];
				$datastring = $Product."\\".$RateBid."\\".$RateAsk;
			}
		} else {
			return $this->GetRateAPI($values);
		}
		
		$obj = new stdObject();
		$obj->getRateResult = $datastring;
		
		return $obj;
	}
	
	//doesnt work
	function GetRates($values){
		$values = array(array_merge($this->defaults, $values));
		return $this->ExecuteAPI("getRates", $values, "Rates");
	}
	
	function GetRateBlotterAPI($values){
		$values = array(array_merge($this->defaults, $values));
		return $this->ExecuteAPI("getRateBlotter", $values, "Rates");
	}
	
	function GetRateBlotter($values){
		$datastring = "";
		
		try{
			$stmt = $conn->prepare("SELECT FH.*, M.Decimals
									FROM rates.forex_history FH
									LEFT JOIN Markets M ON FH.Product = M.Market
									WHERE CONCAT(RateDate,' ',RateTime) > :datetime
									GROUP BY Product
									ORDER BY id DESC");
			$stmt->bindParam(":datetime", date("Y-m-d H:i:s", strtotime('-1 minutes')));
			$stmt->execute();
			$results = $stmt->fetch(PDO::FETCH_ASSOC);
			if(!empty($results)){
				foreach($results as $row){
					$RateBid = $row["RateBid"];
					$RateAsk = $row["RateAsk"];
					$Decimals = $row["Decimals"];
					$datastring .= $row["Product"]."\\".$RateBid."\\".$RateAsk."\\"."\\"."\\"."\\".$Decimals."$";
				}
				$datastring = rtrim($datastring,"$");
			} else {
				return $this->GetRateBlotterAPI($values);
			}
		} catch(PDOException $error){
			echo "Error getting rate information: $error";
		}
		
		$obj = new stdObject();
		$obj->getRateBlotterResult = $datastring;
		return $obj;
	}
	
	function LiquidateAll($values){
		$values = array(array_merge($this->defaults, $values));
		$results = $this->ExecuteAPI("LiquidateAll", $values, "Trading");
		$Success = $results->LiquidateAllResult->Success;
		if($Success){
			try{
				$stmt = $conn->prepare("SELECT Product, DealReference FROM APITrades WHERE Status = 'Active'");
				$stmt->execute();
				$results = $stmt->fetch(PDO::FETCH_ASSOC);
				foreach($results as $row){
					$Markets[$row["Product"]] = array("DealReference" => $row["DealReference"]);
				}
			} catch(PDOException $error){
				echo "Error getting League information: $error";
			}

			$DealResponse = $results->LiquidateAllResult->Output;
			if(count($Markets) == 1){
				foreach($DealResponse as $key => $value){
					$product = $value->product;
					$exitrate = $value->rate;
					$exitdealid = $value->dealId;
					
					if(empty($queryvalues)) $queryvalues = "('".$Markets[$product]["DealReference"]."','$product','$exitrate','$exitdealid','Group Profit', 'Closed')";
						else $queryvalues .= ",('".$Markets[$product]["DealReference"]."','$product','$exitrate','$exitdealid','Group Profit', 'Closed')";
				}
			} else if(count($Markets) > 1) {
				foreach($DealResponse as $key => $value){
					foreach($value as $key2 => $value2){
						$product = $value2->product;
						$exitrate = $value2->rate;
						$exitdealid = $value2->dealId;
						
						if(empty($queryvalues)) $queryvalues = "('".$Markets[$product]["DealReference"]."','$product','$exitrate','$exitdealid','Group Profit', 'Closed')";
							else $queryvalues .= ",('".$Markets[$product]["DealReference"]."','$product','$exitrate','$exitdealid','Group Profit', 'Closed')";
					}
				}
			}
			try{
				$stmt = $conn->prepare("INSERT INTO APITrades (DealReference, Product, ExitRate, ExitDealReference, ProfitLoss, Status) VALUES $queryvalues ON DUPLICATE KEY UPDATE ExitRate = VALUES(ExitRate), ExitDealReference = VALUES(ExitDealReference), ProfitLoss = VALUES(ProfitLoss), Status = VALUES(Status)");
				$stmt->execute();
				foreach($results as $row){
					$Markets[$row["Product"]] = array("DealReference" => $row["DealReference"]);
				}
			} catch(PDOException $error){
				echo "Error getting League information: $error";
			}
		}
		return $results;
	}
	
	function InsertLatestChartData($values){
		$valuestring = "";
		$Product = $values["Product"];
		$values = array(array_merge($this->defaults, $values));
		$results = $this->ExecuteAPI("GetLatestChartData", $values, "Chart");
		
		$data = explode("$",$results->GetLatestChartDataResult->Data);
		foreach($data as $datapoint){
			$data = explode("\\",$datapoint);
			$datetime = explode(" ", $data[0]);
			$bid = $data[4];
			$date = date("Y-m-d", strtotime($datetime[0]));
			$time = date("H:i:s", strtotime("-4 HOUR",strtotime($datetime[1] . " " . $datetime[2])));
			
			if(empty($valuestring)) $valuestring = "('$date','$time','$Product','$bid')";
				else $valuestring .= ",('$date','$time','$Product','$bid')";
		}
		
		try{
			$query = $conn->prepare("INSERT INTO rates.forex_history (RateDate, RateTime, Product, RateBid) VALUES $valuestring ON DUPLICATE KEY UPDATE Product = VALUES(Product), RateBid = VALUES(RateBid)");
			$query->execute(array());
		} catch(PDOException $error){
			echo "Error inserting forex history rates: $error";
		}
		
		return $results;
	}

	function GetLatestChartData($values){
		$datastring = "";
		$Product = $values["Product"];
		$DateTime = $values["Datetime"];
		$TimeInterval = $values["TimeInterval"];
		
		if($TimeInterval == "ONE_MINUTE"){
			$interval = 8;
			$intervaltime = "HOUR";
		}
		
		try{
			$stmt = $conn->prepare("SELECT * FROM rates.forex_history 
									WHERE Product = :product AND CONCAT(RateDate, ' ',RateTime) > DATE_SUB(:date, INTERVAL $interval $intervaltime) 
									GROUP BY Product, RateTime, Minute(RateTime) 
									ORDER BY RateDate DESC, RateTime DESC");
			$stmt->bindParam(":product", $product);
			$stmt->bindParam(":date", date("Y-m-d H:i:s"));
			$stmt->execute();
			$results = $stmt->fetch(PDO::FETCH_ASSOC);

		} catch(PDOException $error){
			echo "Error gathering chart data: $error";
		}

		if($TimeInterval == "ONE_MINUTE" && count($results) >= 420){
			foreach($results as $row){
				$RateDate = date("m/d/Y", strtotime("+4 HOUR",strtotime($row["RateDate"])));
				$RateTime = date("g:i:s A", strtotime("+4 HOUR",strtotime($row["RateTime"])));
				$RateBid = $row["RateBid"];
				$datastring .= $RateDate." ".$RateTime."\\".$RateBid."$";
			}
			$datastring = rtrim($datastring,"$");
		} else {
			return $this->InsertLatestChartData(array("Product" => $Product, "TimeInterval" => "ONE_MINUTE", "Datetime" => $DateTime));
		}
		
		$obj = new stdObject();
		$obj->GetLatestChartDataResult->Product = $Product;
		$obj->GetLatestChartDataResult->Data = $datastring;
		$obj->GetLatestChartDataResult->Success = 1;
		$obj->GetLatestChartDataResult->Message = "";
		$obj->GetLatestChartDataResult->ErrorNo = "";
		
		return $obj;
	}
	
	function GetProductSetting($values){
		$values = array(array_merge($this->defaults, $values));
		return $this->ExecuteAPI("GetProductSetting", $values, "Configuration");
	}
	
	function GetAnonymousProductSettings($values){
		$values = array(array_merge($this->defaults, $values));
		return $this->ExecuteAPI("GetAnonymousProductSettings", $values, "Configuration");
	}

	function ExecuteAPI($operation, $values, $type){
		if($type == "Trading"){
			$client = new SoapClient("https://demoweb.efxnow.com/GainCapitalWebServices/Trading/TradingService.asmx?WSDL");
		} else if($type == "Configuration"){
			$client = new SoapClient("https://demoweb.efxnow.com/GainCapitalWebServices/Configuration/ConfigurationService.asmx?WSDL");
		} else if($type == "Rates"){
			$client = new SoapClient("https://demoweb.efxnow.com/GainCapitalWebServices/Rates/RatesService.asmx?WSDL");
		} else if($type == "Chart"){
			$client = new SoapClient("https://democharting.efxnow.com/Charting/ChartingService.asmx?WSDL");
		} else{
			return "Invalid Type: $type";
		}
		try{
			$results = $client->__soapCall($operation, $values);
			
			try{
				$query = $conn->prepare("INSERT INTO APICalls (Operation, Type, Request, Result) VALUES (:operation, :type, :values , :results)");
				$query->execute(array(
					"operation" => $operation,
					"type" => $type,
					"values" => serialize($values),
					"results" => serialize($results)
				));
			} catch(PDOException $error){
				echo "Failed to insert record of API call: $error";
			}

		} catch(SoapFault $error){
			try{
				$query = $conn->prepare("INSERT INTO APICalls (Operation, Type, Request, Result) VALUES (:operation, :type, :values, :error)");
				$query->execute(array(
					"operation" => $operation,
					"type" => $type,
					"values" => serialize($values),
					"error" => $error
				));
			} catch(PDOException $error){
				echo "Failed to insert record of API call: $error";
			}
		}
		
		return $results;
	}
}
?>