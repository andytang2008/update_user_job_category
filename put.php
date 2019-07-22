<?php
/**
The "User_extracted_infor.txt" file (XML format) obtained by using ALMA User Get API previously will be used.  We get primary id from User_extracted_infor.txt file (XML format) and find the matching primary id from the file named Users_infor_job_category.txt(tab delimited format), then write Job_category and Job_category_description into each user’s XML paragraph and save them back into ALMA User database. By this way, all users’ Job_category and Job_category_description are updated. 

Attention: be sure to test this code in Sandbox before applying it in Production environment.
Running envirnment: Windows 7 + PHP 7.3.3;
No any garantee!
07-2019
by Andy Tang
*/
ini_set("memory_limit","360M"); //Setup the maximum file size you will open.
$holdingFile_handle=file_get_contents("User_extracted_infor.txt"); //Open the xml file we retieved previously by using Get user API
//When using file_get_contents() to put xml into variable, no need to escape double quotes. 
$holdingFile_handle=str_replace ("<?xml", "$$<?xml", $holdingFile_handle); //Add seperator $$ to each xml paragraph
$pos = strpos($holdingFile_handle, "$$");
if ($pos !== false) {
    $newstring = substr_replace($holdingFile_handle, "", $pos, strlen("$$")); //Remove the first occurance of $$ which is seperator.
}   
$process = explode("$$", $newstring); //Use $$ as a seperator to explode xml file and put each user's xml records into array.
 
$handle = @fopen('Users_infor_job_category.txt', "r");  //This file contains user Primary ID, user group, job category, and job description data.
if ($handle) { 
   while (!feof($handle)) { 
       $lines[] = fgets($handle, 8192); //16384 is the maximum size of each line can contain.
   } 
   //print "count of array:".count($lines);
   fclose($handle); 
} 
for ($i=0;$i<count($process);$i++){
	$pos_left = strpos($process[$i], "<primary_id>")+strlen("<primary_id>");  //Get Primary ID left position
	$pos_right=strpos($process[$i], "</primary_id>"); //Get Primary ID right position
	$user_XML_ID=substr($process[$i],$pos_left,$pos_right-$pos_left);//Get Primary ID
	$user_XML_ID=str_replace(array("\r\n", "\n", "\r"), '', $user_XML_ID); //Remove CR and LF 
	
	$j=0;
	$summeryHoldingContent="";
	for ($j=0;$j<sizeof($lines);$j++){
				$haystack=$lines[$j];
				$needle="\t";
			if($user_XML_ID==substr($haystack,0,strpos($haystack, $needle))){

				echo "find it";
				echo "Primary ID:  ".$user_XML_ID;

				$pos1 = strpos($haystack, $needle);  //First Tab position
				$pos2 = strpos($haystack, $needle, $pos1 + strlen($needle));   //Second Tab position
				$pos3 = strpos($haystack, $needle, $pos2 + strlen($needle));    //Third Tab position
				
				$userID=substr($haystack,0,$pos1);
				$userID=str_replace(array("\r\n", "\n", "\r"), '', $userID);
				$userGroup=substr($haystack,$pos1+1,$pos2-$pos1-1);
				$userGroup=str_replace(array("\r\n", "\n", "\r"), '', $userGroup);
				$job_category=substr($haystack,$pos2+1,$pos3-$pos2-1);
				$job_category=str_replace(array("\r\n", "\n", "\r"), '', $job_category);
				$job_description=substr($haystack,$pos3+1,strlen($haystack)-$pos3-3);
				$job_description=str_replace(array("\r\n", "\n", "\r"), '', $job_description);
				
				
				echo "\r\n";
					echo "userID:  ".$userID. "\r\n";
					echo "userGroup:  ".$userGroup. "\r\n"; 
					echo "job_category:  ".$job_category. "\r\n"; 
					echo "job_description:  ".$job_description. "\r\n"; 
					
					echo "userID length:  ".strlen($userID). "\r\n";
					echo "userGroup length:  ".strlen($userGroup). "\r\n"; 
					echo "job_category length:  ".strlen($job_category). "\r\n"; 
					echo "job_description length:  ".strlen($job_description). "\r\n"; 
					
					echo "-------------------------beign-----------------------";	
					echo "-------------------------end-----------------------";
						$xml = simplexml_load_string($process[$i]);
						$xml->job_category=$job_category;
						$xml->job_category[0]['desc']=$job_description;
						$xml->job_description=$job_description;

						echo $xml->saveXML();
						$data=$xml->saveXML();
						
					apicall((string)$userID,$data); //andy
					echo "----------------------Super end--------------------------";
			}
		}
}
function apicall($userID,$contents){
	$ch = curl_init();
	$baseUrl = 'https://api-na.hosted.exlibrisgroup.com/almaws/v1/users/{user_id}';
	$templateParamNames = array('{user_id}');
	$templateParamValues = array(rawurlencode($userID));
  $baseUrl = str_replace($templateParamNames, $templateParamValues, $baseUrl);
	$queryParams = array(
		'apikey' => 'Please put your institutions API key here' // API key. Please put your institution's API key here. If you use sandbox API key, it will put user records into sandbox. If you use production API key, it will put user records into production environment. Ex Libris automatically puts data into Sandbox or Production according to API key type.
	);
	$url = $baseUrl . "?" . "user_id_type=all_unique&override=job_category&send_pin_number_letter=false&". http_build_query($queryParams);
	$data=$contents;
	echo $url;
	//echo "---------------";
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');  //We use PUT to update holding records
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$response = curl_exec($ch);
	
	if (curl_errno($ch)) {
		// This would be your first hint that something went wrong
		die('Couldn\'t send request: ' . curl_error($ch));
	} else {
		// Check the HTTP status code of the request
		$resultStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if ($resultStatus == 200) {
			// Everything went better than expected
		} else {
        die('Request failed: HTTP status code: ' . $resultStatus);
		}
	}
	curl_close($ch);
}
?>
