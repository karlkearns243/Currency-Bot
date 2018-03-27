<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);
ini_set('max_execution_time', 5000);

require_once('functions.php');
require_once('FollowTrendClass2.php');
require_once('DateInterval.php');

$ForexAPI = New FollowTrend();
$Markets = array();

echo date("Y-m-d H:i:s")."<br>";
try{
	$query = $conn->prepare("INSERT INTO ProcessLog (DateCreated, `Process`, URL) VALUES (:date, 'GatherRSI', :URL)");
	$query->execute(array(
		"date" => date("Y-m-d H:i:s"),
		"URL" => $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
	));
} catch(PDOException $error){
	echo "Error inserting process log: " . $error;
}
$ProcessLogID = $conn->lastInsertId();

try{
	$stmt = $conn->prepare("SELECT M.Market FROM test.Markets M WHERE (M.Program = 'FollowTrend2' OR M.Program = 'FollowTrend3') ORDER BY M.Market ASC");
	$stmt->execute();
	$results = $stmt->fetch(PDO::FETCH_ASSOC);

	foreach($results as $row){
		$Markets[$row["Market"]] = array();
	}
} catch(PDOException $error){
	echo "Error getting Market information: " . $error;
}
?>
<html>
	<head>
		<title>RSI</title>
		<script src="http://code.jquery.com/jquery-latest.js"></script>
		<script>
			$(document).ready(function(){
				<?php
				foreach($Markets as $key => $value){
					$Market = $key;
					?>
					var market = "<?php echo $Market;?>";
					var all_loaded = true;
					$.ajax({
						type : 'POST',
						url : 'SafetyThird.php',
						beforeSend: function( xhr ) {
							$(".loading").html("<img src='https://media.giphy.com/media/y1ZBcOGOOtlpC/200.gif' />");
						},
						data: { "market": market },
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
							$("#container").append(data + "<hr>");
							if(all_loaded) $(".loading").html("");
						},
						error: function(){
							console.log("Error Test");
							$("#container").append("<hr> An Error has occured while processing request.");
							$(".loading").html("");
						}
					});
					<?php
				}
				?>
				var dt = new Date();
				var time = dt.getHours() + ":" + dt.getMinutes() + ":" + dt.getSeconds();
				if(dt.getMinutes() >= 30){
					var wait = (60 - dt.getMinutes()) * 1000 * 60;
				} else {
					var wait = (30 - dt.getMinutes()) * 1000 * 60;
				}
				$("#container").html("Reloading in: " + wait / 1000 / 60000 + " minutes.");
				setTimeout(
				function(){
					location.reload();
				}, wait);
			});
		</script>
	</head>
	<body>
		<div class="loading"></div>
		<div id="container"></div>
		<div class="loading"></div>
	</body>
</html>