<?php
/**The first step is to retrieve the data from ALMA server and extract user records upon which we would like to add/modify Job category field. We use ALMA analytics to get the user primary id list which we give the file name of “User_Primary_ID.txt”.We use ALMA User “Get” API to get all of the users' records in XML format, and saved them into one file named “User_extracted_infor.txt”. 

Attention: be sure to test this code in Sandbox before apply it in Production environment.
Running envirnment: Windows 7 + PHP 7.3.3;
No any garantee!
07-2019
by Andy Tang
*/
ini_set("memory_limit","360M"); //Setup the maximum file size you will open.
$handle = @fopen('User_primary_ID.txt', "r");  //The file contais the list of Users's Primary ID you would like to use to download user's records from ALMA. Attention to include file extension. 
if ($handle) { 
   while (!feof($handle)) { 
       $lines[] = fgets($handle, 8192); //16384 is the maximum size of each line can contain.
   } 
  // Print "count of array:".count($lines);
   fclose($handle); 
} 
$fp = fopen('User_extracted_infor.txt', 'w'); //Prepare to output all downloaded users' records(XML format) into this file.
for ($i=0;$i<sizeof($lines);$i++){
	//echo $lines[$i];
	$user_id= str_replace(array("\r\n", "\n", "\r"), '', $lines[$i]);  //Delete the CR and LF sign at the end of each line.
	apicall($user_id,$fp);  //
	//sleep(1);
}
fclose($fp);
function apicall($userID,$filehandle){
	$ch = curl_init(); //Initiate the Curl
	$baseUrl = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/{user_id}';  
	$templateParamNames = array('{user_id}');
	$templateParamValues = array(rawurlencode($userID)); //if using urlencode(), you will have the trouble if the user primary id contain space.
    $baseUrl = str_replace($templateParamNames, $templateParamValues, $baseUrl); 
    // echo $baseUrl;
	echo $userID;
	
	$queryParams = array(
		'user_id_type' => 'all_unique',
			'view' => 'full',
			'expand' => 'none',
			'apikey' => 'Put your institutions API key here'  //Please put your institution's API key here.If you use Sandbox API key, it will extract data from Sandbox. If you use Production environment key, it will extract data from Production. Ex Libris automatically extracts data from Sandbox or Production according to API key type.
	
	);
	$url = $baseUrl . "?" . http_build_query($queryParams);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');  //Using Get method
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	curl_close($ch);
	//echo $response;
	fwrite($filehandle, $response);
}
?>
