<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);

require_once('functions.php');
require_once('Forex.php');

echo date("Y-m-d H:i:s")."<br>";
$query = $conn->prepare("INSERT INTO ProcessLog (DateCreated, `Process`, URL) VALUES (:date, :process, :URL);");
$query->execute(array(
    "date" => date("Y-m-d H:i:s"),
    "process" => "DailyFX",
    "URL" => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
));
$ProcessLogID = $conn->lastInsertId();

?>
<html>
	<head>
		<title>Daily FX</title>
	</head>
	<body>
		<div id="container">
			<?php
			error_reporting(E_ALL);
			if(!function_exists('curl_version')) {
				throw new Exception('Curl package missing');
			}

			$ch = curl_init();
			$timeout = 10;
			$url = "https://www.dailyfx.com/pivot-points";
			curl_setopt($ch, CURLOPT_URL, $url);
			$http_headers = array(
				'User-Agent: Test', // Any User-Agent will do here
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
			<div><?php echo $data;?></div>
			<script>
				$(document).ready(function(){
					var data = {};
					var lastupdated = "";
					$("span:contains('Hourly')").trigger( "click" );
					$('table.pivot-table > tbody.hidden-xs  > tr').each(function(){
						var product = $(this).children("td:nth-child(1)").text();
						var support3 = $(this).children('td:nth-child(2)').html();
						var support2 = $(this).children('td:nth-child(3)').html();
						var support1 = $(this).children('td:nth-child(4)').html();
						lastupdated = $("#pivotcontent div div div").html();
						
						var pivot = $(this).children(":nth-child(5)").html();
						
						var resistance1 = $(this).children(":nth-child(6)").html();
						var resistance2 = $(this).children(":nth-child(7)").html();
						var resistance3 = $(this).children(":nth-child(8)").html();
						
						var timeframe = $("button.btn-pivot-date.active span.hidden-xs").html();
						
						console.log("Timeframe: " + timeframe);
						console.log("Product: " + product);
						console.log("Support 1: " + support1);
						console.log("Support 2: " + support2);
						console.log("Support 3: " + support3);
						console.log("Pivot: " + pivot);
						console.log("Resistance 1: " + resistance1);
						console.log("Resistance 2: " + resistance2);
						console.log("Resistance 3: " + resistance3);
						data[product] = {};
						data[product] = {"timeframe": timeframe, "Support1": support1, "Support2": support2, "Support3": support3, "Pivot": pivot, "Resistance1": resistance1, "Resistance2": resistance2, "Resistance3": resistance3};
					});
					console.log("data: " + data);
					data_array = JSON.stringify(data);
					console.log("data_array: " + data_array);
					$.ajax({
						type : 'POST',
						url : 'InsertDailyFX.php',
						data: { "data": data_array, lastupdated: lastupdated},
						success: function(data){
							data = {};
							lastupdated = "";
							console.log($("span:contains('Daily')"));
							$("span:contains('Daily')")[4].click();
							setTimeout(function(){
								$('table.pivot-table > tbody.hidden-xs  > tr').each(function(){
									var product = $(this).children("td:nth-child(1)").text();
									var support3 = $(this).children('td:nth-child(2)').html();
									var support2 = $(this).children('td:nth-child(3)').html();
									var support1 = $(this).children('td:nth-child(4)').html();
									lastupdated = $("#pivotcontent div div div").html();
									
									var pivot = $(this).children(":nth-child(5)").html();
									
									var resistance1 = $(this).children(":nth-child(6)").html();
									var resistance2 = $(this).children(":nth-child(7)").html();
									var resistance3 = $(this).children(":nth-child(8)").html();
									
									var timeframe = $("button.btn-pivot-date.active span.hidden-xs").html();
									
									/*console.log("Timeframe: " + timeframe);
									console.log("Product: " + product);
									console.log("Support 1: " + support1);
									console.log("Support 2: " + support2);
									console.log("Support 3: " + support3);
									console.log("Pivot: " + pivot);
									console.log("Resistance 1: " + resistance1);
									console.log("Resistance 2: " + resistance2);
									console.log("Resistance 3: " + resistance3);*/
									data[product] = {};
									data[product] = {"timeframe": timeframe, "Support1": support1, "Support2": support2, "Support3": support3, "Pivot": pivot, "Resistance1": resistance1, "Resistance2": resistance2, "Resistance3": resistance3};
								});
								console.log("data: " + data);
								data_array = JSON.stringify(data);
								console.log("data_array: " + data_array);
								$.ajax({
									type : 'POST',
									url : 'InsertDailyFX.php',
									data: { "data": data_array, lastupdated: lastupdated},
									success: function(data){
										data = {};
										lastupdated = "";
										$("span:contains('Weekly')").trigger( "click" );
										setTimeout(function(){
											$('table.pivot-table > tbody.hidden-xs  > tr').each(function(){
												var product = $(this).children("td:nth-child(1)").text();
												var support3 = $(this).children('td:nth-child(2)').html();
												var support2 = $(this).children('td:nth-child(3)').html();
												var support1 = $(this).children('td:nth-child(4)').html();
												lastupdated = $("#pivotcontent div div div").html();
												
												var pivot = $(this).children(":nth-child(5)").html();
												
												var resistance1 = $(this).children(":nth-child(6)").html();
												var resistance2 = $(this).children(":nth-child(7)").html();
												var resistance3 = $(this).children(":nth-child(8)").html();
												
												var timeframe = $("button.btn-pivot-date.active span.hidden-xs").html();
												
												/*console.log("Timeframe: " + timeframe);
												console.log("Product: " + product);
												console.log("Support 1: " + support1);
												console.log("Support 2: " + support2);
												console.log("Support 3: " + support3);
												console.log("Pivot: " + pivot);
												console.log("Resistance 1: " + resistance1);
												console.log("Resistance 2: " + resistance2);
												console.log("Resistance 3: " + resistance3);*/
												data[product] = {};
												data[product] = {"timeframe": timeframe, "Support1": support1, "Support2": support2, "Support3": support3, "Pivot": pivot, "Resistance1": resistance1, "Resistance2": resistance2, "Resistance3": resistance3};
											});
											console.log("data: " + data);
											data_array = JSON.stringify(data);
											console.log("data_array: " + data_array);
											$.ajax({
												type : 'POST',
												url : 'InsertDailyFX.php',
												data: { "data": data_array, lastupdated: lastupdated},
												success: function(data){
													data = {};
													lastupdated = "";
													$("span:contains('Monthly')").trigger( "click" );
													setTimeout(function(){
														$('table.pivot-table > tbody.hidden-xs  > tr').each(function(){
															var product = $(this).children("td:nth-child(1)").text();
															var support3 = $(this).children('td:nth-child(2)').html();
															var support2 = $(this).children('td:nth-child(3)').html();
															var support1 = $(this).children('td:nth-child(4)').html();
															lastupdated = $("#pivotcontent div div div").html();
															
															var pivot = $(this).children(":nth-child(5)").html();
															
															var resistance1 = $(this).children(":nth-child(6)").html();
															var resistance2 = $(this).children(":nth-child(7)").html();
															var resistance3 = $(this).children(":nth-child(8)").html();
															
															var timeframe = $("button.btn-pivot-date.active span.hidden-xs").html();
															
															/*console.log("Timeframe: " + timeframe);
															console.log("Product: " + product);
															console.log("Support 1: " + support1);
															console.log("Support 2: " + support2);
															console.log("Support 3: " + support3);
															console.log("Pivot: " + pivot);
															console.log("Resistance 1: " + resistance1);
															console.log("Resistance 2: " + resistance2);
															console.log("Resistance 3: " + resistance3);*/
															data[product] = {};
															data[product] = {"timeframe": timeframe, "Support1": support1, "Support2": support2, "Support3": support3, "Pivot": pivot, "Resistance1": resistance1, "Resistance2": resistance2, "Resistance3": resistance3};
														});
														console.log("data: " + data);
														data_array = JSON.stringify(data);
														console.log("data_array: " + data_array);
														$.ajax({
															type : 'POST',
															url : 'InsertDailyFX.php',
															data: { "data": data_array, lastupdated: lastupdated},
															success: function(data){
																console.log(data);
																<?php
																$query = $conn->prepare("UPDATE test.ProcessLog SET Completed = 1 WHERE id = :processlogid;");
																$query->execute(array(
																	"processlogid" => $ProcessLogID
																));
																?>
															},
															error: function(){
																console.log("Error");
															}
														});
													}, 5000);
												},
												error: function(){
													console.log("Error");
												}
											});
										}, 5000);
									},
									error: function(){
										console.log("Error");
									}
								});
							}, 5000);
						},
						error: function(){
							console.log("Error");
						}
					});
					
					setTimeout(function(){
						location.reload();
					}, 1000 * 60 * 60)
				});
			</script>
		</div>
	</body>
</html>