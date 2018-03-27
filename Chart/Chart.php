<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);

require_once('functions.php');

//Token
$client = new SoapClient("https://demoweb.efxnow.com/gaincapitalwebservices/authenticate/authenticationservice.asmx?WSDL");
$result = $client->__soapCall("AuthenticateCredentials",
	array(
		array("ApplicationName" => "Test", "IPAddress" => "127.0.0.1", "MachineName" => "Oca055", "Language" => "English", "userID" => FOREXAPI_UN, "password" => FOREXAPI_PW)
	)
);
$Token = $result->AuthenticationResult->token;

//Chart
$client = new SoapClient("http://democharting.efxnow.com/Charting/ChartingService.asmx?WSDL");
$result = $client->__soapCall("GetAnonymousChartBlotter",
	array(
		array("ApplicationName" => "Test", "IPAddress" => "127.0.0.1", "MachineName" => "Oca055", "Language" => "English", "Token" => $Token, "Product" => "USD/JPY", "TimeInterval" => "ONE_MINUTE", "IBRates" => 1)
	)
);
//print_r($result);
$data = explode("$",$result->GetAnonymousChartBlotterResult->Data);
//print_r($data);
foreach($data as $datapoint){
	$data = explode("\\",$datapoint);
	$datetime = str_split($data[0],9);
	$date = explode("/",$datetime[0]);
	$time = explode(":",$datetime[1]);
	$ampm = trim($datetime[2]);
	$month = $date[0];
	$day = $date[1];
	$year = $date[2];
	$hour = $time[0];
	$minute = $time[1];
	$second = $time[2];
	$open = $data[1];
	$high = $data[2];
	$low = $data[3];
	$close = $data[4];
	echo $year."|".$month."|".$day."|".$hour."|".$minute."|".$second."|".$ampm."<br>";
}
exit;
?>
<html>
<head>
	<script type="text/javascript">
		window.onload = function () {
			var chart = new CanvasJS.Chart("chartContainer", {
				title: {
					text: "Basic Candle Stick Chart"
				},
				zoomEnabled: true,
				axisY: {
					includeZero: false,
					title: "Prices",
					prefix: "$ ",
					interval: .100,
				},
				axisX: {
					interval: 180,
					intervalType: "minute",
					valueFormatString: "HH:mm"
				},
				data: [
				{
					type: "candlestick",
					dataPoints: [
						<?php
						foreach($data as $datapoint){
							$data = explode("\\",$datapoint);
							$datetime = str_split($data[0],9);
							$date = explode("/",$datetime[0]);
							$time = explode(":",$datetime[1]);
							$ampm = trim($datetime[2]);
							$month = trim($date[0]);
							$day = trim($date[1]);
							$year = trim($date[2]);
							$hour = trim($time[0]);
							$minute = trim($time[1]);
							$second = trim($time[2]);
							$open = $data[1];
							$high = $data[2];
							$low = $data[3];
							$close = $data[4];
							?>
							{ x: new Date("<?php echo $year;?> <?php echo $month;?> <?php echo $day;?> <?php echo $hour;?>:<?php echo $minute;?>:<?php echo $second;?> <?php echo $ampm;?>"), y: [<?php echo $open;?>, <?php echo $high;?>, <?php echo $low;?>, <?php echo $close;?>] },
							<?php
						}
						?>
					]
				}
				]
			});
			for(var i = 0; i< chart.options.data.length; i++) chart.options.data[i].dataPoints = chart.options.data[i].dataPoints.filter(skipWeekend);

			chart.render();

			function skipWeekend(dps) {
			  return dps.x.getDay() !== 6 && dps.x.getDay() !== 0;
			}
		}
	</script>
	<script src="canvasjs.min.js"></script>
	<title>CanvasJS Example</title>
</head>
<body>
	<div id="chartContainer" style="height: 900px; width: 100%;overflow: auto;">
	</div>
</body>
</html>