<?php
require_once('functions.php');

$product = $_POST["product"];
$decimals = $_POST["decimals"];
$support1 = number_format($_POST["support1"], $decimals);
$support2 = number_format($_POST["support2"], $decimals);
$support3 = number_format($_POST["support3"], $decimals);
$resistance1 = number_format($_POST["resistance1"], $decimals);
$resistance2 = number_format($_POST["resistance2"], $decimals);
$resistance3 = number_format($_POST["resistance3"], $decimals);

try{
	$query = $conn->prepare("INSERT INTO SupportResistance (Product, Level, Support, Resistance) VALUES (:product, 1, :support1, :resistance1), (:product, 2, :support2, :resistance2), (:product, 3, :support3, :resistance3)");
	$query->execute(array(
		"product" => $product,
		"support1" => $support1,
		"support2" => $support2,
		"support3" => $support3,
		"resistance1" => $resistance1,
		"resistance2" => $resistance2,
		"resistance3" => $resistance3
	));
} catch(PDOException $error){
	echo "Error inserting SupportResistance: " . $error;
}
?>