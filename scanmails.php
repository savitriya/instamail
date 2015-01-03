<?php
// define an absolute path to library directory
// you don't have to set a constant but it is just good practice 
// you can also use a variable or add the path directly below
define('APPLICATION_LIBRARY',realpath(dirname(__FILE__).'/library'));
// Note again: the path is the parent of your Zend folder, not the Zend folder itself.
// now set the include path

/*set_include_path(implode(PATH_SEPARATOR, array(
  APPLICATION_LIBRARY, get_include_path(),
)));*/
set_include_path(APPLICATION_LIBRARY);
require_once 'Zend/Loader/Autoloader.php';
require_once 'config.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$scannedAddresses = array();

// ini_set('set_time_limit',60);

$storage = new Zend_Mail_Storage_Imap(array('host'=>$GMAIL_HOST,
											  'port'=>$GMAIL_PORT,
											  'ssl'=>$SSL,
											  'user'=>$GMAIL_USERNAME,
											  'password'=>$GMAIL_PASSWORD));

$totalmailcount_file = 'totalmailcount.txt';
// Write the contents back to the file
file_put_contents($totalmailcount_file, count($storage));
                                         
$validator=new Zend_Validate_EmailAddress();
$i=0;$j=0;
$db=Db::instance();

$emails =  $db->select( 'subscriber', array( 'email' ) )->all(); // Select id & field columns
foreach ( $emails as $email){
	if(!in_array($email->email,$scannedAddresses)){
		array_push($scannedAddresses, $email->email);
	}
}
foreach ($storage as $messageNum => $message) {
	$storage->noop(); // keep alive
	$file = 'scanmailscount.txt';
	// Open the file to get existing content
	$current = file_get_contents($file);
	
	// Append a new person to the file
	if($current == "" || $current >= count($storage) ){
		$current = 0;
	}else{
		$messageNum = $current+1;
		$current = $messageNum;
	}
	
	
	$uid = $storage->getUniqueId($messageNum);
	//echo $uid;
	
	/*if($i==50){
		break;
	}*/
	try{
		//echo "From: ".$message->from;
		$from=$message->from;
		if(preg_match_all("/\<(.*?)\>/",$from,$matches))
		{
			if($validator->isValid($matches[1][0])){
				if(!in_array($matches[1][0],$scannedAddresses)){
					array_push($scannedAddresses, $matches[1][0]);
					$db->create('subscriber',array('email'=>$matches[1][0]));
						
				}							
			}
		}
		else{
			if($validator->isValid($from)){
				if(!in_array($from,$scannedAddresses)){
					array_push($scannedAddresses, $from);
					$db->create('subscriber',array('email'=>$from));
						
				}
			}
		}
		//echo "\n";
	}
	catch(Exception $e){
	}

	
	/*foreach ($message->getHeaders() as $name => $value) {
		if (is_string($value)) {
			echo "$name: $value\n";
			continue;
		}
		foreach ($value as $entry) {
			echo "$name: $entry\n";
		}
	}*/
	
	try{
		//insert Cc into Db
		if($message->headerExists('Cc') && isset($message->cc)){
			//echo "Cc: ".$message->cc;
			$cc=$message->cc;
			$cc=trim($cc,",");
			$cc=trim($cc);
			$temp=explode(",",$cc);
			foreach ($temp as $value){
				if((!empty($value) || $value!="" ) ){
					if(preg_match_all("/\<(.*?)\>/",$value,$matches))
					{
						if($validator->isValid($matches[1][0])){
							if(!in_array($matches[1][0],$scannedAddresses)){
								array_push($scannedAddresses, $matches[1][0]);
								$db->create('subscriber',array('email'=>$matches[1][0]));
								
							}							
						}
					}
					else{
						if($validator->isValid($value)){
							if(!in_array($value,$scannedAddresses)){
								array_push($scannedAddresses, $value);
								$db->create('subscriber',array('email'=>$value));
							}
						}
					}
				}
			}
		}
		//echo "\n";
	}catch(Exception $e){
	}
	try{
		//insert Bcc into Db
		if($message->headerExists('Bcc')  && isset($message->bcc)){
			//echo "Bcc: ".$message->bcc;
			$bcc=$message->bcc;
			$bcc=trim($bcc,",");
			$bcc=trim($bcc);
			$temp=explode(",",$bcc);
			foreach ($temp as $value){
				if((!empty($value) || $value!="" ) ){
					if(preg_match_all("/\<(.*?)\>/",$value,$matches))
					{
						if($validator->isValid($matches[1][0])){
							if(!in_array($matches[1][0],$scannedAddresses)){
								array_push($scannedAddresses, $matches[1][0]);
								$db->create('subscriber',array('email'=>$matches[1][0]));
							}
						}
					}
					else{
						if($validator->isValid($value)){
							if(!in_array($value,$scannedAddresses)){
								array_push($scannedAddresses, $value);
								$db->create('subscriber',array('email'=>$value));
								
							}
						}
					}
				}
			}
		}
		//echo "\n";
	}catch(Exception $e){
	}
	$content='';
	try{
		// get the first none multipart part
		if ($message->isMultipart()) {
			 $iParts = $message->countParts();
			for ($z = 1; $z < $iParts; $z++) {
				$part = $message->getPart($z);

				// ATTACHEMENT?
				/*if () {
					// DO MAIL DOWNLOAD
				}*/

				// MAIL TEXT
				if (strtok($part->contentType, ';') == 'text/plain' || strtok($part->contentType, ';') == 'text/html') {
					$contentType = $part->getHeader('content-type');
					$content = $part->getContent();
					break;
				} 
			}
		}
		else{
			$content = $message->getContent();
		}
	}
	catch(Exception $e){
	}
	
	
	if($content!=""){
	
		// don't need to preassign $matches, it's created dynamically

		// this regex handles more email address formats like a+b@google.com.sg, and the i makes it case insensitive
		$pattern = '/[a-z0-9_\-\+]+@[a-z0-9\-]+\.([a-z]{2,3})(?:\.[a-z]{2})?/i';

		// preg_match_all returns an associative array
		preg_match_all($pattern, $content, $matches);

		// the data you want is in $matches[0], dump it with var_export() to see it
		$temp=$matches[0];
		for($k=0;$k<count($temp);$k++){
			if(!in_array($temp[$k],$scannedAddresses)){
				array_push($scannedAddresses, $temp[$k]);
				$db->create('subscriber',array('email'=>$temp[$k]));
			}
		}
	}
	
	// Write the contents back to the file
	file_put_contents($file, $current);
	
	$i++;
	$storage->noop(); // keep alive
}


/*
for($l=0;$l<count($scannedAddresses);$l++){
	try{
		$db->create('subscriber',array('email'=>$scannedAddresses[$l]));
	}
	catch(Exception $e){
	}	
}*/
//echo "Total Number of Messages Processed ".$i;
