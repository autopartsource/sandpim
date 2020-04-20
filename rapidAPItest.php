<?php

$curl = curl_init();

curl_setopt_array($curl, array(
	CURLOPT_URL => "https://product-data1.p.rapidapi.com/lookup?upc=012993101619",
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_ENCODING => "",
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 30,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => array(
		"x-rapidapi-host: product-data1.p.rapidapi.com",
		"x-rapidapi-key: 2eb67acfe8msh352f22a18322944p1b4203jsn357daa999018"
	),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	echo "cURL Error #:" . $err;
} else {
	echo $response;
}
?>