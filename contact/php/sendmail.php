<?php
	
	$name = trim($_POST['name']);
	$email = $_POST['email'];
	$message = $_POST['message'];
	
	$site_owners_email = 'khoaofgod@gmail.com'; // Replace this with your own email address
	$site_owners_name = 'Khoa Bui'; // Replace with your name
		
	try {
		require_once('PHPMailer/class.phpmailer.php');
		$mail = new PHPMailer();
		
		$mail->From = $email;
		$mail->FromName = $name;
		$mail->Subject = "[Khoa] Contact Message: ".$name;
		$mail->AddAddress($site_owners_email, $site_owners_name);
		$mail->Body = $message;
		
		$mail->Mailer = "mail";
	//	$mail->Host = "smtp.yoursite.com"; // Replace with your SMTP server address
		//$mail->Port = 587;
		//$mail->SMTPSecure = "tls"; 
		
	//	$mail->SMTPAuth = true; // Turn on SMTP authentication
	//	$mail->Username = "user@smtp.com"; // SMTP username
	//	$mail->Password = "yourpassword"; // SMTP password
		
		$mail->Send();
		
		echo "success";
		
	} catch (Exception $e) {
		echo "fail";
	}

?>