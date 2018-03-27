<?php
date_default_timezone_set('America/New_York');
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);
?>
<html>
	<head>
		<title>Rates</title>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.1/jquery-ui.min.js"></script>
		<script>
			$( document ).ready(function() {
				var dt = new Date();
				var time = dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds();
				if(dt.getSeconds() >= 30){
					var wait = (60 - dt.getSeconds()) * 1000;
				} else {
					var wait = (30 - dt.getSeconds()) * 1000;
				}
				$("#container").html("Please wait: " + wait / 1000 + " seconds.");
				setTimeout(
				function(){
					UpdateRates();
					setInterval(function(){UpdateRates()}, 30000);
					function UpdateRates(){
						$(document).ready(function(){
							$.ajax({
								type : 'POST',
								url : 'InsertRates.php',
								success: function(data){
									$("#container").html("Running: " + data);
								},
								error: function(){
									$("#container").html("Error");
								}
							});
						});
					}
				}, wait);
			});
		</script>
	</head>
	<body>
		<div id="container"></div>
	</body>
</html>