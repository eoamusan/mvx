<?php
	include "connect.php";
	require_once('sendgrid/sendgrid-php.php');
	
	class User{
		public $db;
	
		public function __construct(){
            $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
 
            if(mysqli_connect_errno()) {
                echo "Error: Could not connect to database.";
				exit;
            }
        }

        function sendMailSendgrid($template_name, $template_content, $to, $subject, $attachments = null){

        	$from = new SendGrid\Email("Backseat Nigeria", "info@backseat.ng");
			$to = new SendGrid\Email($to['name'], $to['email']);
			$content = new SendGrid\Content("text/html", "Welcome to Backseat Nigeria");
			$mail = new SendGrid\Mail($from, $subject, $to, $content);

			for($i = 0; $i < count($template_content); $i++){
				$mail->personalization[0]->addSubstitution("-".$template_content[$i]['name']."-", $template_content[$i]['content']);
			}

			$mail->setTemplateId($template_name);

			$apiKey = SENDGRID_APIKEY;
			$sg = new \SendGrid($apiKey);

			try {
			    $response = $sg->client->mail()->send()->post($mail);
			} catch (Exception $e) {
			    echo 'Caught exception: ',  $e->getMessage(), "\n";
			}

			// echo $response->statusCode();
			// print_r($response->headers());
			// echo $response->body();
		}

		function sendSMS($username, $password, $message, $mobiles, $sender){

			//allow remote access to this script, replace the * to your domain e.g http://www.example.com if you wish to recieve requests only from your server
			header("Access-Control-Allow-Origin: *");

			//rebuild form data
			$postdata = http_build_query(
			    array(
			        'username' => $username,
			        'password' => $password,
					'message' => $message,
					'mobiles' => $mobiles,
					'sender' => $sender
			    )
			);

			//prepare a http post request
			$opts = array('http' =>
			    array(
			        'method'  => 'POST',
			        'header'  => 'Content-type: application/x-www-form-urlencoded',
			        'content' => $postdata
			    )
			);

			//create a stream to communicate with betasms api
			$context  = stream_context_create($opts);

			//get result from communication
			$result = file_get_contents('http://login.betasms.com/api/', false, $context);

			//return result to client, this will return the appropriate respond code
			// echo $result;

		}

		public function register($fname, $lname, $address, $city, $state, $zip, $card_authorization_code, $card_bin, $card_last4, $card_exp_month, $card_exp_year, $card_channel, $card_card_type, $card_bank, $card_country_code, $card_brand, $card_reusable, $email, $mobile, $password, $car_owner, $car_ownerfirstname, $car_ownerlastname, $car_ownermobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurance_type, $car_insurancecompanyname, $car_paperscomplete){

			$response = [];

			$date = new DateTime(null, new DateTimeZone('Africa/Lagos'));
			$created_at = $date->getTimestamp();

			$check_user = $this->db->prepare("SELECT * FROM users WHERE email = ? OR mobile = ?");
			$check_user->bind_param("ss", strtolower($email), $mobile);
			$check_user->execute();
			$check_user->store_result();
        	$check_user->bind_result($dbid, $dbfname, $dblname, $dbaddress, $dbcity, $dbstate, $dbzip, $dbemail, $dbmobile, $dbpassword, $enabled, $dbverification, $dbverification_code, $dbtype, $dbcarowner, $dbcarowner_firstname, $dbcarowner_lastname, $dbcarowner_mobile, $dbcar_make, $dbcar_model, $dbcar_geartype, $dbcar_insurance, $dbcar_insurancetype, $dbcar_insurancecompany, $dbcar_paperscomplete, $dbdate);
			$check_user->fetch();

			$user_check = $check_user->num_rows();

			if($user_check > 0){

				if((strtolower($email) == strtolower($dbemail)) && ($mobile == $dbmobile)){
					$response['msg'] = "Both email and mobile number have an account linked to them";
				}else if($email == $dbemail){
					$response['msg'] = "Email account has an account linked to it";
				}else if($mobile == $dbmobile){
					$response['msg'] = "Mobile number has an account linked to it";
				}

			}else{

				$valid = 0;
				$link = $this->generate(6);

				$register = $this->db->prepare("INSERT INTO users(fname, lname, address, city, state, zip, email, mobile, password, verification, verification_code, carowner, carowner_firstname, carowner_lastname, carowner_mobile, car_make, car_model, car_geartype, car_insurance, car_insurancetype, car_insurancecompany, car_paperscomplete, date) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
				$register->bind_param("sssssssssiissssssssssis", $fname, $lname, $address, $city, $state, $zip, strtolower($email), $mobile, $password, $valid, $link, $car_owner, $car_ownerfirstname, $car_ownerlastname, $car_ownermobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurance_type, $car_insurancecompanyname, $car_paperscomplete, $created_at);

				if($register->execute())
				{
					$recent_insert_id = $this->db->insert_id;

					if($card_authorization_code){
						$update_payment = $this->db->prepare("INSERT INTO card_authorizations(user_id, authorization_code, card_type, card_brand, last4, exp_month, exp_year, bin, bank, channel, reusable, country_code) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

						$update_payment->bind_param("isssssssssss", $recent_insert_id, $card_authorization_code, $card_card_type, $card_brand, $card_last4, $card_exp_month, $card_exp_year, $card_bin, $card_bank, $card_channel, $card_reusable, $card_country_code);

						$update_payment->execute();
					}

					$response['msg'] = "Registration Successful";

					$content = 	array(
									array(
										'name' => 'fname', 'content' => $fname
									),
									array(
										'name' => 'lname', 'content' => $lname
									),
									array(
										'name' => 'link', 'content' => $link
									)
								);

					$to = 	array(
								'email' => $email,
								'name' => $fname.' '.$lname
							);

					$this->sendMailSendgrid('9b3f0bce-8f02-4971-be3d-c4b766705478', $content, $to, 'Welcome to Backseat');

					#Send sms to user
					$message = "Take the Backseat!";
					$message .= "\n\n";
					$message .= "To verify your account, please use this code on first login:"."\n";
					$message .= $link;

					$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $mobile, BETASMS_SENDER);
					
				} else {
					$response['msg'] = "Something went wrong. Please try again";
				}

			}

			return $response;

		}

		function generate($length) {
		    $result = '';

		    for($i = 0; $i < $length; $i++) {
		        $result .= mt_rand(0, 9);
		    }

		    return $result;
		}

		public function updatePayment($riderid, $rideremail, $ridermobile, $orderid, $amount, $status, $reference, $date, $fname, $lname, $driver){
			$logpayment = $this->db->prepare("INSERT INTO payments(userid, orderid, amount, status, reference, date) VALUES(?, ?, ?, ?, ?, ?)");
			$logpayment->bind_param("iissss", $riderid, $orderid, $amount, $status, $reference, $date);

			if($logpayment->execute())
			{
				$recent_insert_id = $this->db->insert_id;

				if($status == "success"){
					$update_payment = $this->db->prepare("UPDATE orders SET charged = ? WHERE id = ?");
					$update_payment->bind_param("ii", $recent_insert_id, $orderid);

					$update_payment->execute();

					if($driver){
						$driverdetails = "with ".$driver;
					}else{
						$driverdetails = "";
					}

					$content = 	array(
									array(
										'name' => 'fname', 'content' => $fname
									),
									array(
										'name' => 'driver', 'content' => $driver
									),
									array(
										'name' => 'amount', 'content' => $amount
									)
								);

					$to = 	array(
								'email' => $rideremail,
								'name' => $fname.' '.$lname
							);

					$this->sendMailSendgrid('656b40c8-e469-4f3c-9e19-3ed05f94edc0', $content, $to, 'Backseat.ng: Payment successful!');

					#Send sms to user
					$message = "Backseat.ng: Successful payment!";
					$message .= "\n\n";
					$message .= "Dear, ".$fname."\n";
					$message .= "You have been successfully charged N".$amount." for your trip ".$driverdetails;
					$message .= $link;

					$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $ridermobile, BETASMS_SENDER);
				}

				$response['msg'] = "Payment updated";
				
			} else {
				$response['msg'] = "Something went wrong. Please try again";
			}

			return $response;
		}

		public function update($userid, $fname, $lname, $address, $city, $state, $zip, $card_authorization_code, $card_bin, $card_last4, $card_exp_month, $card_exp_year, $card_channel, $card_card_type, $card_bank, $card_country_code, $card_brand, $card_reusable, $email, $mobile, $password, $car_owner, $car_ownerfirstname, $car_ownerlastname, $car_ownermobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurance_type, $car_insurancecompanyname, $car_paperscomplete){

			$response = [];

			$check_payment = $this->db->prepare("SELECT * FROM card_authorizations WHERE user_id = ?");
			$check_payment->bind_param("i", $userid);
			$check_payment->execute();
			$check_payment->store_result();

			$payment_check = $check_payment->num_rows();

			$register = $this->db->prepare("UPDATE users SET fname = ?, lname = ?, address = ?, city = ?, state = ?, zip = ?, password = ?, carowner = ?, carowner_firstname = ?, carowner_lastname = ?, carowner_mobile = ?, car_make = ?, car_model = ?, car_geartype = ?, car_insurance = ?, car_insurancetype = ?, car_insurancecompany = ?, car_paperscomplete = ? WHERE id = ?");
			$register->bind_param("sssssssssssssssssi", $fname, $lname, $address, $city, $state, $zip, $password, $car_owner, $car_ownerfirstname, $car_ownerlastname, $car_ownermobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurance_type, $car_insurancecompanyname, $car_paperscomplete, $userid);

			if($register->execute())
			{
				if(($payment_check > 0) && ($card_authorization_code != NULL)){
					$register = $this->db->prepare("UPDATE card_authorizations SET authorization_code = ?, card_type = ?, card_brand = ?, last4 = ?, exp_month = ?, exp_year = ?, bin = ?, bank = ?, channel = ?, reusable = ?, country_code = ?  WHERE user_id = ?");
					$register->bind_param("sssssssssssi", $card_authorization_code, $card_card_type, $card_brand, $card_last4, $card_exp_month, $card_exp_year, $card_bin, $card_bank, $card_channel, $card_reusable, $card_country_code, $userid);
					$register->execute();
				}else if(($payment_check == 0) && ($card_authorization_code != NULL)){
					$update_payment = $this->db->prepare("INSERT INTO card_authorizations(user_id, authorization_code, card_type, card_brand, last4, exp_month, exp_year, bin, bank, channel, reusable, country_code) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

					$update_payment->bind_param("isssssssssss", $userid, $card_authorization_code, $card_card_type, $card_brand, $card_last4, $card_exp_month, $card_exp_year, $card_bin, $card_bank, $card_channel, $card_reusable, $card_country_code);

					$update_payment->execute();
				}

				$response['msg'] = "Account Updated";
				
			} else {
				$response['msg'] = "Something went wrong. Please try again";
			}

			return $response;

		}

		public function get_result( $statement ) {
		    $result = array();
		    $statement->store_result();
		    for ( $i = 0; $i < $statement->num_rows; $i++ ) {
		        $metadata = $statement->result_metadata();
		        $params = array();
		        while ( $Field = $metadata->fetch_field() ) {
		            $params[] = &$result[ $i ][ $Field->name ];
		        }
		        call_user_func_array( array( $statement, 'bind_result' ), $params );
		        $statement->fetch();
		    }
		    return $result;
		}

		function base64_to_image( $base64_string, $output_file ) {
			$ifp = fopen( $output_file, "wb" ); 
			$data = explode( ',', $base64_string );

			// we could add validation here with ensuring count( $data ) > 1
			fwrite( $ifp, base64_decode( $data[ 1 ] ) );

			// clean up the file resource    
			fclose( $ifp ); 

			return( $output_file ); 
		}

		public function order($address, $time, $orderdate, $duration, $cost, $userid){
			$response = [];
			$date = new DateTime(null, new DateTimeZone('Africa/Lagos'));
			$created_at = $date->getTimestamp();

			$order = $this->db->prepare("INSERT INTO orders(address, time, date, duration, cost, userid, orderdate) VALUES(?, ?, ?, ?, ?, ?, ?)");
			$order->bind_param("sssssis", $address, $time, $orderdate, $duration, $cost, $userid, $created_at);

			if($order->execute()){

				$response['msg'] = "Application saved";
				$_SESSION['application_submitted'] = 1;

				$sql = $this->db->prepare('SELECT * FROM users WHERE id = ?');
				$sql->bind_param('i', $userid);
				$sql->execute();
				$sql->store_result();
				$sql->bind_result($dbid, $dbfname, $dblname, $dbaddress, $dbcity, $dbstate, $dbzip, $dbemail, $dbmobile, $dbpassword, $dbenabled, $dbverification, $dbverification_code, $dbtype, $dbcarowner, $dbcarowner_firstname, $dbcarowner_lastname, $dbcarowner_mobile, $dbcar_make, $dbcar_model, $dbcar_geartype, $dbcar_insurance, $dbcar_insurancetype, $dbcar_insurancecompany, $dbcar_paperscomplete, $dbdate);
				$sql->fetch();

				$content = 	array(
									array(
										'name' => 'orderaddress', 'content' => $address
									),
									array(
										'name' => 'time', 'content' => $time
									),
									array(
										'name' => 'orderdate', 'content' => $orderdate
									),
									array(
										'name' => 'duration', 'content' => $duration
									),
									array(
										'name' => 'cost', 'content' => $cost
									),
									array(
										'name' => 'fname', 'content' => $dbfname
									),
									array(
										'name' => 'lname', 'content' => $dblname
									),
									array(
										'name' => 'useraddress', 'content' => $dbaddress
									),
									array(
										'name' => 'city', 'content' => $dbcity
									),
									array(
										'name' => 'state', 'content' => $dbstate
									),
									array(
										'name' => 'zip', 'content' => $dbzip
									),
									array(
										'name' => 'email', 'content' => $dbemail
									),
									array(
										'name' => 'mobile', 'content' => $dbmobile
									)
								);
					
				$to = 	array(
								'email' => $dbemail,
								'name' => $dbfname.' '.$dblname
							);
					
				$adminto = 	array(
								'email' => BK_ADMIN,
								'name' => 'Admin'
							);

				$this->sendMailSendgrid('91be02cb-bd88-49c0-a0da-728ae2e741db', $content, $to, 'Backseat.ng: We got your order!');
				$this->sendMailSendgrid('ff1c35e1-46bd-446c-b93f-920b480a5ce0', $content, $adminto, 'Backseat.ng: Order!');

				$message = "Backseat.ng: We got your order";
				$message .= "\n\n";
				$message .= "Your order is being processed. We would reach out to you in a bit."."\n\n";
				$message .= "If you have any questions, kindly reach out to support on +234 813 094 3976 or send an email to info@backseat.ng"."\n";

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $dbmobile, BETASMS_SENDER);
			}else{
				$response['msg'] = "Something went wrong";
			}

			return $response;
		}
				
		public function login($username, $password){

			$sql = $this->db->prepare('SELECT users.id, users.fname, users.lname, users.address, users.city, users.state, users.zip, users.email, users.mobile, users.password, users.enabled, users.verification, users.verification_code, users.type, users.carowner, users.carowner_firstname, users.carowner_lastname, users.carowner_mobile, users.car_make, users.car_model, users.car_geartype, users.car_insurance, users.car_insurancetype, users.car_insurancecompany, users.car_paperscomplete, users.date, card_authorizations.id, card_authorizations.authorization_code, card_authorizations.card_type, card_authorizations.card_brand, card_authorizations.last4, card_authorizations.exp_month, card_authorizations.exp_year, card_authorizations.bin, card_authorizations.bank, card_authorizations.channel, card_authorizations.reusable, card_authorizations.country_code FROM users LEFT JOIN card_authorizations ON users.id = card_authorizations.user_id WHERE email = ? AND password = ?');

			$passwd = md5($password);
			$sql->bind_param('ss', $username, $passwd);
			$sql->execute();
			$sql->store_result();
			$sql->bind_result($dbid, $dbfname, $dblname, $dbaddress, $dbcity, $dbstate, $dbzip, $dbemail, $dbmobile, $dbpassword, $dbenabled, $dbverification, $dbverification_code, $dbtype, $dbcarowner, $dbcarowner_firstname, $dbcarowner_lastname, $dbcarowner_mobile, $dbcar_make, $dbcar_model, $dbcar_geartype, $dbcar_insurance, $dbcar_insurancetype, $dbcar_insurancecompany, $dbcar_paperscomplete, $dbdate, $card_id, $authorization_code, $card_type, $card_brand, $last4, $exp_month, $exp_year, $bin, $bank, $channel, $reusable, $country_code);
			$sql->fetch();
			
			$result = $sql->num_rows();
			
			if($result == 1){
				if (($dbverification == 1) && ($dbenabled == 1)) {				
					$login['data']['id'] = $dbid;
					$login['data']['fname'] = $dbfname;
					$login['data']['lname'] = $dblname;
					$login['data']['address'] = $dbaddress;
					$login['data']['city'] = $dbcity;
					$login['data']['state'] = $dbstate;
					$login['data']['zip'] = $dbzip;
					$login['data']['email'] = $dbemail;
					$login['data']['mobile'] = $dbmobile;
					$login['data']['verification'] = $dbverification;
					$login['data']['verification_code'] = $dbverification_code;
					$login['data']['date'] = $dbdate;

					$login['data']['type'] = $dbtype;
					$login['data']['carowner'] = $dbcarowner;
					$login['data']['ownerfirstname'] = $dbcarowner_firstname;
					$login['data']['ownerlastname'] = $dbcarowner_lastname;
					$login['data']['ownermobile'] = $dbcarowner_mobile;
					$login['data']['car_make'] = json_decode($dbcar_make);
					$login['data']['car_model'] = json_decode($dbcar_model);
					$login['data']['car_type'] = $dbcar_geartype;
					$login['data']['carinsurance'] = $dbcar_insurance;
					$login['data']['carinsurancetype'] = $dbcar_insurancetype;
					$login['data']['carinsurancecompanyname'] = $dbcar_insurancecompany;
					$login['data']['car_paperscomplete'] = $dbcar_paperscomplete;

					$login['data']['card']['id'] = $card_id;
					$login['data']['card']['authorization_code'] = $authorization_code;
					$login['data']['card']['card_type'] = $card_type;
					$login['data']['card']['brand'] = $card_brand;
					$login['data']['card']['last4'] = $last4;
					$login['data']['card']['exp_month'] = $exp_month;
					$login['data']['card']['exp_year'] = $exp_year;
					$login['data']['card']['bin'] = $bin;
					$login['data']['card']['bank'] = $bank;
					$login['data']['card']['channel'] = $channel;
					$login['data']['card']['reusable'] = $reusable;
					$login['data']['card']['country_code'] = $country_code;


					$login['data']['hashed_password'] = $dbpassword;
					$login['msg'] = "Logged In";
				}else if (($dbverification == 0) && ($dbenabled == 1)){
					$login['msg'] = "Account Not Verified";
				}else if (($dbverification == 1) && ($dbenabled == 0)){
					$login['msg'] = "Your account has been suspended. Please send an email to complaints@backseat.ng for more information";
				}
			}else{
				$login['msg'] = "Invalid username and/or password";
			}
			
			return $login;
		}

		public function verifyCode($email, $code){
			$response = [];

			$sql = $this->db->prepare('SELECT * FROM users WHERE email = ?');
			$sql->bind_param('s', $email);
			$sql->execute();
			$sql->store_result();
			$sql->bind_result($id, $fname, $lname, $address, $city, $state, $zip, $email, $mobile, $password, $enabled, $verification, $verification_code, $type, $carowner, $carowner_firstname, $carowner_lastname, $carowner_mobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurancetype, $car_insurancecompany, $car_paperscomplete, $date);
			$sql->fetch();

			if($sql->num_rows() > 0){
				$verification_check = ($verification_code == $code);

				if($verification_check && ($verification == 0)){
					$response = [
									'verification' => $verification_check,
									'fname' => $fname,
									'lname' => $lname,
									'email' => $email,
									'mobile' => $mobile
								];
				}else if($verification_check && ($verification == 1)){
					$response = [
									'verification' => false,
									'msg' => 'Account already verified'
								];
				}else{
					$response = [
									'verification' => $verification_check,
									'msg' => 'Incorrect verification code'
								];
				}
			}else{
				$response = [
								'verification' => false,
								'msg' => "Email does not have a linked account"
							];
			}

			return $response;
		}
				
		public function verify($email, $code){
			$response = [];
			$verified = 1;

			$verify = $this->verifyCode(strtolower($email), $code);

			if($verify['verification']){
				$sql = $this->db->prepare('UPDATE users SET verification = ? WHERE email = ?');
				$sql->bind_param('is', $verified, strtolower($email));
				$sql->execute();

				$response['msg'] = 'Account Verified';

				$content = 	array(
									array(
										'name' => 'fname', 'content' => $verify['fname']
									)
								);
					
				$to = 	array(
							'email' => $verify['email'],
							'name' => $verify['fname'].' '.$verify['lname']
						);

				$this->sendMailSendgrid('aaf6caa5-76b3-4b7a-b455-a85315c875b5', $content, $to, 'Backseat.ng: Account Verification successful');

				#Send sms to user
				$message = "Take the Backseat!";
				$message .= "\n\n";
				$message .= "You did it! Your account is now verified";

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $verify['mobile'], BETASMS_SENDER);
			}else{
				$response['msg'] = $verify['msg'];
			}
			
			return $response;
		}

		public function disable($table, $userid){
			$unverified = 0;

			$sql = $this->db->prepare('UPDATE '.$table.' SET enabled = ? WHERE id = ?');
			$sql->bind_param('ii', $unverified, $userid);

			if($sql->execute()){
				$response = 1;
			}else{
				$response = 0;
			}
			
			return $response;
		}

		public function enable($table, $userid){
			$verified = 1;

			$sql = $this->db->prepare('UPDATE '.$table.' SET enabled = ? WHERE id = ?');
			$sql->bind_param('ii', $verified, $userid);

			if($sql->execute()){
				$response = 1;
			}else{
				$response = 0;
			}
			
			return $response;
		}

		function registrationExists($applicantemail){
			$applicant = $this->db->prepare("SELECT * FROM users WHERE email = ?");
			$applicant->bind_param('s', $applicantemail);
		    $applicant->execute();
		    $applicant->store_result();

		    $count = $applicant->num_rows();

		    if($count > 0){
		    	return true;
		    }else{
		    	return false;
		    }
		}

		public function initiateResetPassword($email){
			$response = [];

			if($this->registrationExists(strtolower($email))){

				$sql = $this->db->prepare('SELECT * FROM users WHERE email = ?');
				$sql->bind_param('s', strtolower($email));
				$sql->execute();
				$sql->store_result();
				$sql->bind_result($id, $fname, $lname, $address, $city, $state, $zip, $email, $mobile, $password, $enabled, $verification, $verification_code, $type, $carowner, $carowner_firstname, $carowner_lastname, $carowner_mobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurancetype, $car_insurancecompany, $car_paperscomplete, $date);
				$sql->fetch();

				$link = substr($date, -6);

				$content = 	array(
									array(
										'name' => 'fname', 'content' => $fname
									),
									array(
										'name' => 'link', 'content' => $link
									)
								);
					
				$to = 	array(
								'email' => $email,
								'name' => $fname.' '.$lname
							);

				$this->sendMailSendgrid('6ba00d9d-7136-42e7-b043-ea110654b859', $content, $to, 'Backseat.ng: Password Reset');

				#Send sms to user
				$message = "Take the Backseat!";
				$message .= "\n\n";
				$message .= "To reset your account password, please use this code:"."\n";
				$message .= $link;

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $mobile, BETASMS_SENDER);
				$response['msg'] = 'Mail sent';				

			}else{
				$response['msg'] = "Email does not have a linked account";
			}

			return $response;
		}

		public function authResetPassword($email, $code){
			$response = [];

			$sql = $this->db->prepare('SELECT * FROM users WHERE email = ?');
			$sql->bind_param('s', strtolower($email));
			$sql->execute();
			$sql->store_result();
			$sql->bind_result($id, $fname, $lname, $address, $city, $state, $zip, $email, $mobile, $password, $enabled, $verification, $verification_code, $type, $carowner, $carowner_firstname, $carowner_lastname, $carowner_mobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurancetype, $car_insurancecompany, $car_paperscomplete, $date);
			$sql->fetch();

			$link = substr($date, -6);

			if($link == $code){
				$response['msg'] = "Code authorized";
			}else{
				$response['msg'] = "Invalid Authentication Code";
			}

			return $response;
		}

		public function resetPassword($email, $password){
			$response = [];

			$sql = $this->db->prepare('SELECT * FROM users WHERE email = ?');
			$sql->bind_param('s', strtolower($email));
			$sql->execute();
			$sql->store_result();
			$sql->bind_result($dbid, $dbfname, $dblname, $dbaddress, $dbcity, $dbstate, $dbzip, $dbemail, $dbmobile, $dbpassword, $dbenabled, $dbverification, $dbverification_code, $dbtype, $dbcarowner, $dbcarowner_firstname, $dbcarowner_lastname, $dbcarowner_mobile, $dbcar_make, $dbcar_model, $dbcar_geartype, $dbcar_insurance, $dbcar_insurancetype, $dbcar_insurancecompany, $dbcar_paperscomplete, $dbdate);
			$sql->fetch();

			$newpassword = md5($password);

			$sql = $this->db->prepare('UPDATE users SET password = ? WHERE email = ?');
			$sql->bind_param('ss', $newpassword, strtolower($email));

			if($sql->execute()){

				$content = 	array(
									array(
										'name' => 'fname', 'content' => $dbfname
									)
								);
					
				$to = 	array(
								'email' => strtolower($email),
								'name' => $dbfname.' '.$dblname
							);

				$this->sendMailSendgrid('8ade9ba3-73ed-4237-9d91-812ed3d15be8', $content, $to, 'Backseat.ng: Password Reset Successful');

				$response['msg'] = "Password Reset";

				#Send sms to user
				$message = "Take the Backseat!";
				$message .= "\n\n";
				$message .= "Your password reset is successful";

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $dbmobile, BETASMS_SENDER);

			}else{
				$response['msg'] = "Something went wrong";
			}

			return $response;
		}
				
		public function login_admin($username, $password){
			$sql = $this->db->prepare('SELECT * FROM admin WHERE username = ? AND password = ?');
			$passwd = md5($password);
			$sql->bind_param('ss', $username, $passwd);
			$sql->execute();
			$sql->store_result();
			$sql->bind_result($id, $uname, $pword);
			$sql->fetch();
			
			$result = $sql->num_rows();
			
			if($result == 1){
				$_SESSION['fcmbAdminLogin'] = true;
				$_SESSION['admin_id'] = $username;
				$_SESSION['admin_username'] = $password;
				
				$admin_login = "Logged In";
			}else{
				$admin_login = "Invalid username and/or password";
			}
			
			return $admin_login;
		}
		
		public function admin_logout() {
            $_SESSION['fcmbAdminLogin'] = FALSE;
            session_destroy();

            return "Logged Out";
        }
		
		public function logout() {
            $_SESSION['fcmbSMELogin'] = FALSE;
            session_destroy();

            return "Logged Out";
        }

        public function resend($email) {
        	$response = [];

			$sql = $this->db->prepare('SELECT * FROM users WHERE email = ?');
			$sql->bind_param('s', $email);
			$sql->execute();
			$sql->store_result();
			$sql->bind_result($id, $fname, $lname, $address, $city, $state, $zip, $email, $mobile, $password, $enabled, $verification, $verification_code, $type, $carowner, $carowner_firstname, $carowner_lastname, $carowner_mobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurancetype, $car_insurancecompany, $car_paperscomplete, $date);

			$sql->fetch();

			if($sql->num_rows > 0){
				$content = 	array(
										array(
											'name' => 'fname', 'content' => $fname
										),
										array(
											'name' => 'lname', 'content' => $lname
										),
										array(
											'name' => 'link', 'content' => $verification_code
										)
									);

				$to = 	array(
								'email' => $email,
								'name' => $fname.' '.$lname
							);

				$this->sendMailSendgrid('9b3f0bce-8f02-4971-be3d-c4b766705478', $content, $to, 'Welcome to Backseat');

				#Send sms to user
				$message = "Take the Backseat!";
				$message .= "\n\n";
				$message .= "To verify your account, please use this code to verify account:"."\n";
				$message .= $verification_code;

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $mobile, BETASMS_SENDER);

				$response['msg'] = "Verification Sent";
			}else{
				$response['msg'] = "NO LINKED ACCOUNT EXISTS";
			}

			return $response;
        }
 		
		public function select($from, $where = null, $fields = '*', $order = null){
		
			$query = "SELECT " . $fields . " FROM " . $from;
			if($where != null){  
            	$query .= " WHERE id = ".$where;
			}

			if($order != null){
				$query .= " ORDER BY id ".$order;
			}
			
			$result = $this->db->prepare($query);
			$result->execute();
			$result->store_result();
			
			$count_row = $result->num_rows;
			
			return $result;
		
		}

		public function getorders() {
			$orders = [];

			$stmt = $this->db->prepare('SELECT users.id, users.fname, users.lname, users.address, users.city, users.state, users.zip, users.email, users.mobile, users.enabled, users.verification, users.type, users.carowner, users.carowner_firstname, users.carowner_lastname, users.carowner_mobile, users.car_make, users.car_model, users.car_geartype, users.car_insurance, users.car_insurancetype, users.car_insurancecompany, users.car_paperscomplete, users.date, orders.id, orders.address, orders.time, orders.date, orders.duration, orders.orderdate, orders.cost, orders.driverassigned, orders.charged, drivers.name, drivers.email, drivers.mobile, drivers.picture, card_authorizations.id, card_authorizations.authorization_code, card_authorizations.card_type, card_authorizations.card_brand, card_authorizations.last4, card_authorizations.exp_month, card_authorizations.exp_year, card_authorizations.bin, card_authorizations.bank, card_authorizations.channel, card_authorizations.reusable, card_authorizations.country_code FROM orders JOIN users ON orders.userid = users.id LEFT JOIN card_authorizations ON users.id = card_authorizations.user_id LEFT JOIN drivers ON orders.driverassigned = drivers.id');
	        $stmt->bind_result($riderid, $fname, $lname, $address, $city, $state, $zip, $email, $mobile, $enabled, $verification, $type, $carowner, $carowner_firstname, $carowner_lastname, $carowner_mobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurancetype, $car_insurancecompany, $car_paperscomplete, $userregdate, $orderid, $orderaddress, $ordertime, $orderdate, $orderduration, $orderregdate, $ordercost, $driverassigned, $charged, $drivername, $driveremail, $drivermobile, $driverpicture, $card_id, $authorization_code, $card_type, $card_brand, $last4, $exp_month, $exp_year, $bin, $bank, $channel, $reusable, $country_code);
			$result = $stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows >= "1") {

		        	while($data = $stmt->fetch()){ 
		        		    $order = [
		        		    		'riderid' => $riderid,
			        				'id' => $orderid,
			        				'fname' => $fname,
			        				'lname' => $lname,
			        				'email' => $email,
			        				'mobile' => $mobile,
			        				'address' => $address,
			        				'city' => $city,
			        				'state' => $state,
			        				'zip' => $zip,
			        				'orderaddress' => $orderaddress,
			        				'ordertime' => $ordertime,
			        				'orderdate' => $orderdate,
			        				'orderduration' => $orderduration,
			        				'ordercost' => $ordercost,
			        				'enabled' => $enabled,
			        				'driverassigned' => $driverassigned,
			        				'charged' => $charged,
			        				'drivername' => $drivername,
			        				'driveremail' => $driveremail,
			        				'drivermobile' => $drivermobile,
			        				'driverpicture' => $driverpicture,
			        				'card_id' => $card_id,
			        				'authorization_code' => $authorization_code,
			        				'card_type' => $card_type,
			        				'card_brand' => $card_brand,
			        				'last4' => $last4,
			        				'exp_month' => $exp_month,
			        				'exp_year' => $exp_year,
			        				'bin' => $bin,
			        				'bank' => $bank,
			        				'channel' => $channel,
			        				'reusable' => $reusable,
			        				'country_code' => $country_code
			        			];

			        array_push($orders, $order);
			    }
	        }

		    return $orders;
		}

		public function getuserorders($userid) {
			$orders = [];

			$stmt = $this->db->prepare('SELECT users.fname, users.lname, users.address, users.city, users.state, users.zip, users.email, users.mobile, users.enabled, users.verification, users.type, users.carowner, users.carowner_firstname, users.carowner_lastname, users.carowner_mobile, users.car_make, users.car_model, users.car_geartype, users.car_insurance, users.car_insurancetype, users.car_insurancecompany, users.car_paperscomplete, users.date, orders.id, orders.address, orders.time, orders.date, orders.duration, orders.orderdate, orders.cost, orders.driverassigned, orders.charged, drivers.name, drivers.email, drivers.mobile, drivers.picture, card_authorizations.id, card_authorizations.authorization_code, card_authorizations.card_type, card_authorizations.card_brand, card_authorizations.last4, card_authorizations.exp_month, card_authorizations.exp_year, card_authorizations.bin, card_authorizations.bank, card_authorizations.channel, card_authorizations.reusable, card_authorizations.country_code FROM orders JOIN users ON orders.userid = users.id LEFT JOIN card_authorizations ON users.id = card_authorizations.user_id LEFT JOIN drivers ON orders.driverassigned = drivers.id WHERE orders.userid = ?');
			$stmt->bind_param("i", $userid);
	        $stmt->bind_result($fname, $lname, $address, $city, $state, $zip, $email, $mobile, $enabled, $verification, $type, $carowner, $carowner_firstname, $carowner_lastname, $carowner_mobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurancetype, $car_insurancecompany, $car_paperscomplete, $userregdate, $orderid, $orderaddress, $ordertime, $orderdate, $orderduration, $orderregdate, $ordercost, $driverassigned, $charged, $drivername, $driveremail, $drivermobile, $driverpicture, $card_id, $authorization_code, $card_type, $card_brand, $last4, $exp_month, $exp_year, $bin, $bank, $channel, $reusable, $country_code);
			$result = $stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows >= "1") {

		        	while($data = $stmt->fetch()){ 
		        		    $order = [
			        				'id' => $orderid,
			        				'fname' => $fname,
			        				'lname' => $lname,
			        				'email' => $email,
			        				'mobile' => $mobile,
			        				'address' => $address,
			        				'city' => $city,
			        				'state' => $state,
			        				'zip' => $zip,
			        				'orderaddress' => $orderaddress,
			        				'ordertime' => $ordertime,
			        				'orderdate' => $orderdate,
			        				'orderduration' => $orderduration,
			        				'ordercost' => $ordercost,
			        				'enabled' => $enabled,
			        				'driverassigned' => $driverassigned,
			        				'charged' => $charged,
			        				'drivername' => $drivername,
			        				'driveremail' => $driveremail,
			        				'drivermobile' => $drivermobile,
			        				'driverpicture' => $driverpicture,
			        				'card_id' => $card_id,
			        				'authorization_code' => $authorization_code,
			        				'card_type' => $card_type,
			        				'card_brand' => $card_brand,
			        				'last4' => $last4,
			        				'exp_month' => $exp_month,
			        				'exp_year' => $exp_year,
			        				'bin' => $bin,
			        				'bank' => $bank,
			        				'channel' => $channel,
			        				'reusable' => $reusable,
			        				'country_code' => $country_code
			        			];

			        array_push($orders, $order);
			    }
	        }

		    return $orders;
		}

		public function getusers() {
			$users = [];

			$stmt = $this->db->prepare('SELECT users.id, users.fname, users.lname, users.address, users.city, users.state, users.zip, users.email, users.mobile, users.enabled, users.verification, users.type, users.carowner, users.carowner_firstname, users.carowner_lastname, users.carowner_mobile, users.car_make, users.car_model, users.car_geartype, users.car_insurance, users.car_insurancetype, users.car_insurancecompany, users.car_paperscomplete, users.date, count(orders.id) FROM users LEFT JOIN orders ON users.id = orders.userid GROUP BY users.id');
	        $stmt->bind_result($id, $fname, $lname, $address, $city, $state, $zip, $email, $mobile, $enabled, $verification, $type, $carowner, $carowner_firstname, $carowner_lastname, $carowner_mobile, $car_make, $car_model, $car_geartype, $car_insurance, $car_insurancetype, $car_insurancecompany, $car_paperscomplete, $userregdate, $tripcount);
			$result = $stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows >= "1") {

		        	while($data = $stmt->fetch()){ 
		        		    $user = [
			        				'id' => $id,
			        				'fname' => $fname,
			        				'lname' => $lname,
			        				'email' => $email,
			        				'mobile' => $mobile,
			        				'address' => $address,
			        				'city' => $city,
			        				'state' => $state,
			        				'zip' => $zip,
			        				'tripcount' => $tripcount,
			        				'verification' => $verification,
			        				'enabled' => $enabled
			        			];

			        array_push($users, $user);
			    }
	        }

		    return $users;
		}

		public function getdrivers() {
			$drivers = [];

			$stmt = $this->db->prepare('SELECT * FROM drivers');
	        $stmt->bind_result($id, $name, $email, $mobile, $picture, $enabled, $date);
			$result = $stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows >= "1") {

		        	while($data = $stmt->fetch()){ 
		        		    $driver = [
			        				'id' => $id,
			        				'name' => $name,
			        				'email' => $email,
			        				'mobile' => $mobile,
			        				'picture' => $picture,
			        				'enabled' => $enabled,
			        				'date' => $date
			        			];

			        array_push($drivers, $driver);
			    }
	        }

		    return $drivers;
		}

		public function addDriver($name, $email, $mobile, $picture){
			$response = [];
			$date = new DateTime(null, new DateTimeZone('Africa/Lagos'));
			$created_at = $date->getTimestamp();

			$check_driver = $this->db->prepare("SELECT * FROM drivers WHERE email = ? OR mobile = ?");
			$check_driver->bind_param("ss", strtolower($email), $mobile);
			$check_driver->execute();
			$check_driver->store_result();
        	$check_driver->bind_result($dbid, $dbname, $dbemail, $dbmobile, $dbpicture, $dbdate);
			$check_driver->fetch();

			$user_check = $check_driver->num_rows();

			if($user_check > 0){

				if((strtolower($email) == strtolower($dbemail)) && ($mobile == $dbmobile)){
					$response['msg'] = "Both email and mobile number have a driver account linked to them";
				}else if($email == $dbemail){
					$response['msg'] = "Email account has a driver account linked to it";
				}else if($mobile == $dbmobile){
					$response['msg'] = "Mobile number has a driver account linked to it";
				}

			}else{

				// Uploading driver profile picture
				$random_hash = date('YmdHis',time()).mt_rand();
				$extension = explode('/', explode(';', $picture)[0])[1];

				$profile_photo_path = '../images/drivers/'.$random_hash.'.'.$extension;
				$image = $this->base64_to_image( $picture, $profile_photo_path );

				$register = $this->db->prepare("INSERT INTO drivers(name, email, mobile, picture, date) VALUES(?, ?, ?, ?, ?)");
				$register->bind_param("sssss", $name, strtolower($email), $mobile, $profile_photo_path, $created_at);

				$content = 	array(
								array(
									'name' => 'name', 'content' => $name
								),
								array(
									'name' => 'mobile', 'content' => $mobile
								)
							);

				$to = 	array(
							'email' => $email,
							'name' => $name
						);

				$this->sendMailSendgrid('c56ca260-fc8c-426c-ad47-4107d62bf300', $content, $to, 'Backseat.ng added you as a driver');

				#Send sms to user
				$message = "Backseat.ng added you as a driver!";
				$message .= "\n\n";
				$message .= "Going forward, We will assign trips to you. You will get both and email and text messages when you have requests."."\n";

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $mobile, BETASMS_SENDER);

				if($register->execute()){
					$response['msg'] = 'Driver added';
				}else{
					$response['msg'] = 'Something went wrong';
				}
			}

			return $response;
		}

		public function assignDriver($riderfname, $riderlname, $ridernumber, $rideremail, $orderid, $orderaddress, $ordertime, $orderdate, $orderduration, $ordercost, $driverid, $drivername, $driveremail, $drivermobile){
			$response = [];

			$driverassigned = $this->db->prepare("SELECT * FROM orders WHERE id = ?");
			$driverassigned->bind_param("i", $orderid);
			$driverassigned->execute();
			$driverassigned->store_result();
        	$driverassigned->bind_result($dbid, $dbaddress, $dbdestination, $dbtime, $dbdate, $dbduration, $dbcost, $dbdistance, $dbuserid, $dbdriverassigned, $dbcharged, $dborderdate);
			$driverassigned->fetch();
				
			$content = 	array(
							array(
								'name' => 'drivername', 'content' => $drivername
							),
							array(
								'name' => 'riderfname', 'content' => $riderfname
							),
							array(
								'name' => 'riderlname', 'content' => $riderlname
							),
							array(
								'name' => 'ridernumber', 'content' => $ridernumber
							),
							array(
								'name' => 'rideremail', 'content' => $rideremail
							),
							array(
								'name' => 'orderaddress', 'content' => $orderaddress
							),
							array(
								'name' => 'ordertime', 'content' => $ordertime
							),
							array(
								'name' => 'orderdate', 'content' => $orderdate
							),
							array(
								'name' => 'orderduration', 'content' => $orderduration
							),
							array(
								'name' => 'ordercost', 'content' => $ordercost
							)
						);

			$to = 	array(
						'email' => $driveremail,
						'name' => $drivername
					);

			$this->sendMailSendgrid('b8684c20-cc67-466a-9f75-375871e17e34', $content, $to, 'Backseat.ng: Your service is needed!');

			#Send sms to user
			$message = "Backseat.ng: ".$riderfname." needs your service!";
			$message .= "\n\n";
			$message .= "Here are the details of the trip:"."\n";
			$message .= "Rider name: ".$riderfname." ".$riderlname."\n";
			$message .= "Rider number: ".$ridernumber."\n";
			$message .= "Pickup address: ".$orderaddress."\n";
			$message .= "Pickup time: ".$ordertime."\n";
			$message .= "Pickup date: ".$orderdate."\n";
			$message .= "Order duration: ".$orderduration."\n";
			$message .= "Order cost: ".$ordercost."\n";

			$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $drivermobile, BETASMS_SENDER);

			if($dbdriverassigned == 0){
				$content = 	array(
								array(
									'name' => 'riderfname', 'content' => $riderfname
								),
								array(
									'name' => 'drivername', 'content' => $drivername
								),
								array(
									'name' => 'drivermobile', 'content' => $drivermobile
								)
							);

				$to = 	array(
							'email' => $driveremail,
							'name' => $drivername
						);

				$this->sendMailSendgrid('fb1cce92-da32-4a47-8f9f-b53ea2e89c48', $content, $to, 'Backseat.ng: Your personal chauffeur here!');

				#Send sms to user
				$message = "Backseat.ng: You got a driver!";
				$message .= "\n\n";
				$message .= "Here are the details of your driver:"."\n";
				$message .= "Driver name: ".$drivername."\n";
				$message .= "Driver number: ".$drivermobile."\n";

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $ridernumber, BETASMS_SENDER);
			}else{
				$content = 	array(
								array(
									'name' => 'riderfname', 'content' => $riderfname
								),
								array(
									'name' => 'drivername', 'content' => $drivername
								),
								array(
									'name' => 'drivermobile', 'content' => $drivermobile
								)
							);

				$to = 	array(
							'email' => $driveremail,
							'name' => $drivername
						);

				$this->sendMailSendgrid('f721abeb-b6f2-4133-b316-45f08115dbb4', $content, $to, 'Backseat.ng: Sorry, we changed your chauffeur!');

				#Send sms to user
				$message = "Backseat.ng: Sorry, we changed your chauffeur!";
				$message .= "\n\n";
				$message .= "Here are the details of your new driver:"."\n";
				$message .= "Driver name: ".$drivername."\n";
				$message .= "Driver number: ".$drivermobile."\n";

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $ridernumber, BETASMS_SENDER);
			}

			$register = $this->db->prepare("UPDATE orders SET driverassigned = ? WHERE id = ?");
			$register->bind_param("ii", $driverid, $orderid);

			if($register->execute()){
				$response['msg'] = 'Driver Assigned';
			}

			return $response;
		}
		
		public function delete($from, $id){
			$query = "DELETE FROM " . $from . " WHERE id = " . $id;
			
			$result = $this->db->prepare($query);
			$result->execute();
			
			return 'success';
		}
		
		public function send_contact($name, $email, $keyword, $subject, $message, $mail){
			$email_message = "";
			$email_from = "amusantobi@gmail.com";
			$email_to = "amusantobi@gmail.com";
			
			$email_subject = 'CAREMI Contact'.$subject;
			
			$email_message .= "Name: ".$name."\n";
			$email_message .= "Email: ".$email."\n\n";
			$email_message .= "Keyword: ".$keyword."\n\n";
			$email_message .= "Message".$message."\n";
				 
			// create email headers
			$headers = 'From: '.$email_from."\r\n".
			'Reply-To: '.$email_from."\r\n" .
			'X-Mailer: PHP/' . phpversion();
			
			mail($email_to, $email_subject, $email_message, $headers);
		}

		public function reArrayFiles(&$file_post) {
		    $file_ary = array();
		    $file_count = count($file_post['name']);
		    $file_keys = array_keys($file_post);

		    for ($i=0; $i<$file_count; $i++) {
		        foreach ($file_keys as $key) {
		            $file_ary[$i][$key] = $file_post[$key][$i];
		        }
		    }

		    return $file_ary;
		}
    }
?>