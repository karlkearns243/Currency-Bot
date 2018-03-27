<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);

require_once('functions.php');
require_once('Forex.php');

echo date("Y-m-d H:i:s")."<br>";

try{
	$query = $conn->prepare("INSERT INTO ProcessLog (DateCreated, `Process`, URL) VALUES (:date, :process, :URL)");
	$query->execute(array(
		"date" => date("Y-m-d H:i:s"),
		"process" => "Investing",
		"URL" => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
	));
} catch(PDOException $error){
	echo "Error: " . $error;
}
$ProcessLogID = $conn->lastInsertId();

error_reporting(E_ALL);
if(!function_exists('curl_version')) {
	throw new Exception('Curl package missing');
}

if(date("N",strtotime(date("Y-m-d"))) != 7){
	?>
	<html>
		<head>
			<title>Investing</title>
			<script src="http://code.jquery.com/jquery-latest.js"></script>
			<script>
				$(document).ready(function(){
					var data = {};
					var product = $("div#tradeNowFloat div.instrumentFloaterInner div.float_lang_base_1 span:first").text();
					var ratedate = $("table.historicalTbl:first tbody tr:nth-child(1)").children("td:nth-child(1)").text();
					var price = $("table.historicalTbl:first tbody tr:nth-child(2)").children("td:nth-child(2)").text();
					var open = $("table.historicalTbl:first tbody tr:nth-child(2)").children("td:nth-child(3)").text();
					var high = $("table.historicalTbl:first tbody tr:nth-child(2)").children("td:nth-child(4)").text();
					var low = $("table.historicalTbl:first tbody tr:nth-child(2)").children("td:nth-child(5)").text();
					var change = $("table.historicalTbl:first tbody tr:nth-child(2)").children("td:nth-child(6)").text();
					
					data[product] = {};
					data[product] = {"ratedate": ratedate, "price": price, "open": open, "high": high, "low": low, "change": change};

					data_array = JSON.stringify(data);
					console.log("test_data_array: " + data_array);
					if($("#data").text() != ""){
						$.ajax({
							type : 'POST',
							url : 'InsertInvesting.php',
							data: { "data": data_array},
							success: function(data){
								<?php
								try{
									$query = $conn->prepare("UPDATE test.ProcessLog SET Completed = 1 WHERE id = :processlogid");
									$query->execute(array(
										"processlogid" => $ProcessLogID
									));
								} catch(PDOException $error){
									echo "Error completing process log: " . $error;
								}
								?>
								console.log("date: " + data);
								location.reload();
							},
							error: function(){
								console.log("Error Test");
							}
						});
					}
					
					setTimeout(function(){
						location.reload();
					}, 1000 * 60 * 60 * 4)
				});
			</script>
		</head>
		<body>
			<div id="container">
				<?php
				try{
					$stmt = $conn->prepare("SELECT M.* FROM test.Markets M
											LEFT JOIN rates.invest_history I ON M.Market=I.Product AND I.RateDate = :date
											WHERE M.Program IN ('FollowTrend2','FollowTrend3') AND I.RateDate IS NULL 
											LIMIT 1");
					$stmt->bindParam(":date", $date("Y-m-d",strtotime(date("Y-m-d") . "-1 days")));
					$stmt->execute();
					$results = $stmt->fetch(PDO::FETCH_ASSOC);

					foreach($results => $row){
						$ch = curl_init();
						$timeout = 10;
						$url = $row["TechnicalURL"];
						curl_setopt($ch, CURLOPT_URL, $url);
						$http_headers = array(
							'User-Agent: Test2', // Any User-Agent will do here
						);
						curl_setopt($ch, CURLOPT_HEADER, true);
						curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , 0);
						$data = curl_exec($ch);
						header("Access-Control-Allow-Origin: *");
						curl_close($ch);
						?>
						<div id="data"><?php echo $data;?></div>
						<?php
					}
				} catch(PDOException $error){
					echo "Error inserting DailyFX: " . $error;
				}
				?>
			</div>
		</body>
	</html>
	<?php
}
?>