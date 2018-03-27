<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);

require_once('functions.php');
require_once('Forex.php');

$Markets = array();

$today = date("Y-m-d");
try{
	$stmt = $conn->prepare("SELECT M.Market, M.MaxSqueeze, M.TechnicalURL, M.Decimals, COUNT(SR.id) AS `SRCount`
							FROM Markets M 
							LEFT JOIN SupportResistance SR ON M.Market=SR.Product AND DATE(SR.DateCreated) = :today
							WHERE M.Status = 'Active' 
							GROUP BY M.Market
							HAVING SRCount = 0");
	$stmt->bindParam(":today", $today);
	$stmt->execute();
	$results = $stmt->fetch(PDO::FETCH_ASSOC);

	foreach($results as $row){
		$Markets[] = array("Market" => $row["Market"], "MaxSqueeze" => $row["MaxSqueeze"], "TechnicalURL" => $row["TechnicalURL"], "Decimals" => $row["Decimals"]);
	}
} catch(PDOException  $error){
	echo "Error getting League information: " . $error->getMessage();
}
?>
<html>
	<head>
		<title>Resistance/Support</title>
	</head>
	<body>
		<div id="container">
			<?php
			foreach($Markets as $key => $value){
				error_reporting(E_ALL);
				if(!function_exists('curl_version')) {
					throw new Exception('Curl package missing');
				}

				$ch = curl_init();
				$timeout = 10;
				$url = $value["TechnicalURL"];
				curl_setopt($ch, CURLOPT_URL, $url);
				$http_headers = array(
					'User-Agent: Junk', // Any User-Agent will do here
				);
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $http_headers);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , 0);
				$data = curl_exec($ch);
				curl_close($ch);
				?>
				<div id="<?php echo $value["Market"];?>" hidden><?php echo $data;?></div>
				<script>
					$(document).ready(function(){
						var product = "<?php echo $value["Market"];?>";
						var decimals = "<?php echo $value["Decimals"];?>";
						var support1 = $("#curr_table tbody tr:nth-child(2) td:nth-child(2)").html();
						var support2 = $("#curr_table tbody tr:nth-child(2) td:nth-child(3)").html();
						var support3 = $("#curr_table tbody tr:nth-child(2) td:nth-child(4)").html();
						
						var resistance1 = $("#curr_table tbody tr:nth-child(2) td:nth-child(6)").html();
						var resistance2 = $("#curr_table tbody tr:nth-child(2) td:nth-child(7)").html();
						var resistance3 = $("#curr_table tbody tr:nth-child(2) td:nth-child(8)").html();
						
						console.log("Support 1: " + support1);
						console.log("Support 2: " + support2);
						console.log("Support 3: " + support3);
						console.log("Resistance 1: " + resistance1);
						console.log("Resistance 2: " + resistance2);
						console.log("Resistance 3: " + resistance3);
						
						$.ajax({
							type : 'POST',
							url : 'InsertResistanceSupport.php',
							data: { product: product, decimals: decimals, support1: support1, support2: support2, support3: support3, resistance1: resistance1, resistance2: resistance2, resistance3: resistance3 },
							success: function(data){
								console.log(data);
								location.reload();
							},
							error: function(){
								console.log("Error");
							}
						});
					});
				</script>
				<?php
				break;
			}
			?>
		</div>
	</body>
</html>