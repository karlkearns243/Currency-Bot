<?php
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 900);
ini_set('default_socket_timeout', 15);

require_once('functions.php');

//This is the first page that should be run to retrieve the token that is used in connections.php
try{
	$client = new SoapClient("https://demoweb.efxnow.com/gaincapitalwebservices/authenticate/authenticationservice.asmx?WSDL");
	$result = $client->__soapCall("AuthenticateCredentials",
		array(
			array("ApplicationName" => "Test", "IPAddress" => "127.0.0.1", "MachineName" => "Oca055", "Language" => "English", "userID" => FOREXAPI_UN, "password" => FOREXAPI_PW)
		)
	);
	echo $Token = $result->AuthenticationResult->token;
	
	if(empty($Token)){
		echo "Failed to retrieve token, error: " . $result->AuthenticationResult->ErrorNo;
	}
} catch(Exception $error){
	echo "An error occured: " . $error->getMessage();
}
//$Token = FOREXAPI_TOKEN; //eventually this should be automatically run if token doesn't work
?>