<?php
require_once('functions.php');

$conn = new PDO("mysql:host=" . FOREX_HN .";dbname=" . FOREX_DB, FOREX_UN, FOREX_PW, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
$liveconn = new PDO("mysql:host=" . FOREX_HN .";dbname=" . LIVE_FOREX_DB, FOREX_UN, FOREX_PW, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
?>