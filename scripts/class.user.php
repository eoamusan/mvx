<?php
	include "connect.php";
	require 'phpmailer/class.phpmailer.php';
	
	class User{
		public $db;
	
		public function __construct(){
            $this->db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
 
            if(mysqli_connect_errno()) {
                echo "Error: Could not connect to database.";
				exit;
            }
        }

        function sendMailPHPMailer($template_name, $template_content, $to, $subject, $attachments = null){

        	$mail = new PHPMailer(true);

			try {
	  
				$body = file_get_contents('../views/emails/'.$template_name.'.html');

				for($i = 0; $i < count($template_content); $i++){
					$body = eregi_replace("-".$template_content[$i]['name']."-", $template_content[$i]['content'], $body);
				}

				$mail->IsSMTP();
		        $mail->Host = 'mail.mvxchange.com';
		        $mail->SMTPAuth = true;
		        $mail->Username = 'info@mvxchange.com';
		        $mail->Password = 'pass@123*';
		        $mail->SMTPSecure = 'ssl';
		        $mail->Port = 465;
		        $mail->setFrom('info@mvxchange.com', "MVXChange.com");
		        $mail->addAddress($to['email'], $to['name']);
		        $mail->isHTML(true);

				$mail->Subject = $subject;
				$mail->AltBody = 'To view the message, please use an HTML compatible email viewer!';
				$mail->MsgHTML($body);
				$mail->Send();

			} catch (phpmailerException $e) {

				// echo $e->errorMessage(); //Pretty error messages from PHPMailer

			} catch (Exception $e) {

				// echo $e->getMessage(); //Boring error messages from anything else!

			}
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

		public function register($data){

			$response = [];

			$date = new DateTime(null, new DateTimeZone('Africa/Lagos'));
			$created_at = $date->getTimestamp();

			$email_user = $this->db->prepare("SELECT * FROM users WHERE email = ?");
			$email_user->bind_param("s", strtolower($data->email));
			$email_user->execute();
			$email_user->store_result();

			$email_used = $email_user->num_rows();

			if ($data->category == "Charterer") {
				$mobile_user = $this->db->prepare("SELECT * FROM charterer WHERE mobile = ?");
				$mobile_user->bind_param("s", $data->charterermobile);
				$mobile_user->execute();
				$mobile_user->store_result();
				$mobile_used = $mobile_user->num_rows();
			}

			if ($data->category == "Procurement Vendor / Supplier") {

				$mobile_user = $this->db->prepare("SELECT * FROM procurement_agent WHERE phone_number = ? OR mobile_contact_person = ? OR phone_number = ? OR mobile_contact_person = ?");
				$mobile_user->bind_param("ssss", $data->businessphonenumber, $data->contactpersonmobile, $data->contactpersonmobile, $data->businessphonenumber);
				$mobile_user->execute();
				$mobile_user->store_result();
				$mobile_used = $mobile_user->num_rows();
			}

			if ($data->category == "Ship Owner") {
				$mobile_user = $this->db->prepare("SELECT * FROM shipowner WHERE businessmobile = ? OR contactmobile = ? OR businessmobile = ? OR contactmobile = ?");
				$mobile_user->bind_param("ssss", $data->bizmobile, $data->contactpersonmobile, $data->contactpersonmobile, $data->bizmobile);
				$mobile_user->execute();
				$mobile_user->store_result();
				$mobile_used = $mobile_user->num_rows();
			}

			if(($email_used > 0) || ($mobile_used > 0)){

				if(($email_used > 0) && ($mobile_used > 0)){
					$response['msg'] = "Both email and mobile number have an account linked to them";
				}else if($email_used > 0){
					$response['msg'] = "Email account has an account linked to it";
				}else if($mobile_used > 0){
					$response['msg'] = "Mobile number has an account linked to it";
				}

			}else{

				$valid = 0;
				$link = $this->generate(6);
				$password = md5($data->password);

				$register = $this->db->prepare("INSERT INTO users(email, category, password, verificationcode, createdat) VALUES(?, ?, ?, ?, ?)");
				$register->bind_param("sssss", strtolower($data->email), $data->category, $password, $link, $created_at);

				if($register->execute())
				{
					$recent_insert_id = $this->db->insert_id;
					$updated_at = $date->getTimestamp();

					if ($data->category == "Charterer") {
						$name = $data->charterername;
						$email = $data->email;
						$mobile = $data->charterermobile;

						$update_record = $this->db->prepare("INSERT INTO charterer(userid, name, company_name, email, mobile, updatedat) VALUES(?, ?, ?, ?, ?, ?)");

						$update_record->bind_param("isssss", $recent_insert_id, $data->charterername, $data->charterercompanyname, strtolower($data->email), $data->charterermobile, $updated_at);

						$update_record->execute();
					}

					if ($data->category == "Procurement Vendor / Supplier") {
						$name = $data->contactpersonname.' ('.$data->businessname.')';
						$email = $data->contactpersonemail;
						$mobile = $data->contactpersonmobile;

						$cleanname = preg_replace('/\s+/', '', $name);
						$random_hash = $cleanname.date('YmdHis',time()).mt_rand();

						$extension_logo = substr($data->procurement_companylogoname, strrpos($data->procurement_companylogoname, '.') + 1);
						$extension_profile = substr($data->procurement_companyprofilename, strrpos($data->procurement_companyprofilename, '.') + 1);
						
						$companylogo = "../assets/images/users/procurementcompanylogos/".$random_hash.'.'.$extension_logo;
						$companyprofile = "../assets/images/users/procurementcompanyprofiles/".$random_hash.'.'.$extension_profile;
						
						$image_logo = $this->base64_to_image( $data->procurement_companylogo, $companylogo );
						$image_profile = $this->base64_to_image( $data->procurement_companyprofile, $companyprofile );

						$update_record = $this->db->prepare("INSERT INTO procurement_agent(userid, subcategory, logo, name, company_registration_number, website, phone_number, services, country, corporate_office_address, name_contact_person, mobile_contact_person, email_contact_person, permits, company_profile, updatedat) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

						$update_record->bind_param("isssssssssssssss", $recent_insert_id, trim($data->subcategory), $companylogo, $data->businessname, $data->businessregistrationnumber, $data->website, $data->businessphonenumber, json_encode($data->services), $data->country, $data->corporateofficeaddress, $data->contactpersonname, $data->contactpersonmobile, strtolower($data->contactpersonemail), json_encode($data->permits), $companyprofile, $updated_at);

						$update_record->execute();
					}

					if ($data->category == "Ship Owner") {
						$name = $data->shipcontactpersonname.' ('.$data->shipbusinessname.')';
						$email = $data->shipcontactpersonemail;
						$mobile = $data->shipcontactpersonmobile;

						$cleanname = preg_replace('/\s+/', '', $name);

						$random_hash = $cleanname.date('YmdHis',time()).mt_rand();

						$extension_logo = substr($data->companylogoname, strrpos($data->companylogoname, '.') + 1);
						$extension_profile = substr($data->companyprofilename, strrpos($data->companyprofilename, '.') + 1);

						$companylogo = "../assets/images/users/shipcompanylogos/".$random_hash.'.'.$extension_logo;
						$companyprofile = "../assets/images/users/shipcompanyprofiles/".$random_hash.'.'.$extension_profile;
						$image_logo = $this->base64_to_image( $data->companylogo, $companylogo );
						$image_profile = $this->base64_to_image( $data->companyprofile, $companyprofile );

						$update_record = $this->db->prepare("INSERT INTO shipowner(userid, subcategory, companylogo, businessname, companyregistrationnumber, website, businessemail, businessmobile, services, corporateofficeaddress, contactname, contactmobile, contactemail, companyprofile, permits, updatedat) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

						$update_record->bind_param("isssssssssssssss", $recent_insert_id, trim($data->shipcategory), $companylogo, $data->shipbusinessname, $data->shipcompanyregistrationnumber, $data->shipwebsite, strtolower($data->shipbizemail), $data->shipbizmobile, json_encode($data->shipservices), $data->shipcorporateofficeaddress, $data->shipcontactpersonname, $data->shipcontactpersonmobile, strtolower($data->shipcontactpersonemail), $companyprofile, json_encode($data->shippermits), $updated_at);

						$update_record->execute();
					}

					$response['msg'] = "Registration Successful";

					$content = 	array(
									array(
										'name' => 'name', 'content' => $name
									),
									array(
										'name' => 'link', 'content' => $link
									)
								);

					$to = 	array(
								'email' => $email,
								'name' => $name
							);

					$this->sendMailPHPMailer('signup', $content, $to, 'Welcome to MVXchange');

					#Send sms to user
					$message = "Welcome to MVXchange!";
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

		public function charter($data){
			$response = [];

			$date = new DateTime(null, new DateTimeZone('Africa/Lagos'));
			$created_at = $date->getTimestamp();
			$document = "";

			if (isset($data->vessel_specification_document)) {
				$cleanname = preg_replace('/\s+/', '', $data->vessel_specification_documentname);
				$cleanname = str_replace(".", "", $cleanname);
				$random_hash = $cleanname.date('YmdHis',time()).mt_rand();

				$extension = substr($data->vessel_specification_documentname, strrpos($data->vessel_specification_documentname, '.') + 1);
				
				$document = "../assets/images/charters/".$random_hash.'.'.$extension;
				
				$image = $this->base64_to_image( $data->vessel_specification_document, $document );
			}

			$register = $this->db->prepare("INSERT INTO charters(user_id, preferred_shipowner_category, vessel_type, identity, preferred_flag, max_age, firm_duration, tonnage_dwt_min, tonnage_dwt_max, tonnage_grt_min, tonnage_grt_max, expected_mob_date, preferred_daily_hire_rate, location_of_operation, scope_of_work, performance_sbp_bp_min, performance_sbp_bp_max, performance_sbp_bhp_min, performance_sbp_bhp_max, performance_sbp_speed_min, performance_sbp_speed_max, dimensions_length_min, dimensions_length_max, dimensions_breadth_min, dimensions_breadth_max, dimensions_depth_min, dimensions_depth_max, dimensions_draft_min, dimensions_draft_max, end_client, reg_doc_class, reg_doc_ncdmb, dec_cargo_cleardeckarea_min, dec_cargo_cleardeckarea_max, dec_cargo_deckstrength_min, dec_cargo_deckstrength_max, dec_cargo_deckcrane, dp_one, dp_two, valid_ovid_cmid, add_inspection, purpose, port_of_delivery, port_of_redelivery, fuel_consumption_on_tow, accommodation_passengers_min, accommodation_passengers_max, accommodation_hospital, helipad, additional_data, vessel_specification_document, created_at, updated_at) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$register->bind_param("isssssssssssissssssssssssssssssssssssssssssssssssssss", $data->userid, $data->preferred_shipowner_category, $data->vessel_type, $data->identity, $data->preferred_flag, $data->max_age, $data->firm_duration, $data->tonnage_dwt_min, $data->tonnage_dwt_max, $data->tonnage_grt_min, $data->tonnage_grt_max, $data->expected_mob_date, $data->daily_hire_rate, $data->location_of_operation, $data->scope_of_work, $data->performance_sbb_bp_min, $data->performance_sbb_bp_max, $data->performance_sbb_bhp_min, $data->performance_sbb_bhp_max, $data->performance_sbb_speed_min, $data->performance_sbb_speed_max, $data->dimensions_length_min, $data->dimensions_length_max, $data->dimensions_breadth_min, $data->dimensions_breadth_max, $data->dimensions_depth_min, $data->dimensions_depth_max, $data->dimensions_draft_min, $data->dimensions_draft_max, $data->end_client, $data->class, $data->ncdmb, $data->cleardeckarea_min, $data->cleardeckarea_max, $data->deckstrength_min, $data->deckstrength_max, $data->deckcrane, $data->dp1, $data->dp2, $data->valid_ovid_cmid, $data->inspection, $data->purpose, $data->port_of_delivery, $data->port_of_redelivery, $data->fuel_consumption_on_tow, $data->accommodation_passengers_min, $data->accommodation_passengers_max, $data->accommodation_hospital, $data->helipad, $data->additional_data, $document, $created_at, $created_at);

			if($register->execute()){
				$response['msg'] = "Charter Added";
				$daily_hire_rate = (string) $data->daily_hire_rate;
				$max_age = (string) $data->max_age;

				$content = 	array(
									array(
										'name' => 'name', 'content' => $data->username
									),
									array(
										'name' => 'preferred_shipowner_category', 'content' => $data->preferred_shipowner_category
									),
									array(
										'name' => 'vessel_type', 'content' => $data->vessel_type
									),
									array(
										'name' => 'identity', 'content' => $data->identity
									),
									array(
										'name' => 'preferred_flag', 'content' => $data->preferred_flag
									),
									array(
										'name' => 'max_age', 'content' => $max_age
									),
									array(
										'name' => 'firm_duration', 'content' => $data->firm_duration
									),
									array(
										'name' => 'tonnage_dwt_min', 'content' => $data->tonnage_dwt_min
									),
									array(
										'name' => 'tonnage_dwt_max', 'content' => $data->tonnage_dwt_max
									),
									array(
										'name' => 'tonnage_grt_min', 'content' => $data->tonnage_grt_min
									),
									array(
										'name' => 'tonnage_grt_max', 'content' => $data->tonnage_grt_max
									),
									array(
										'name' => 'expected_mob_date', 'content' => $data->expected_mob_date
									),
									array(
										'name' => 'daily_hire_rate', 'content' => $daily_hire_rate
									),
									array(
										'name' => 'location_of_operation', 'content' => $data->location_of_operation
									),
									array(
										'name' => 'scope_of_work', 'content' => $data->scope_of_work
									),
									array(
										'name' => 'performance_sbb_bp_min', 'content' => $data->performance_sbb_bp_min
									),
									array(
										'name' => 'performance_sbb_bp_max', 'content' => $data->performance_sbb_bp_max
									),
									array(
										'name' => 'performance_sbb_bhp_min', 'content' => $data->performance_sbb_bhp_min
									),
									array(
										'name' => 'performance_sbb_bhp_max', 'content' => $data->performance_sbb_bhp_max
									),
									array(
										'name' => 'performance_sbb_speed_min', 'content' => $data->performance_sbb_speed_min
									),
									array(
										'name' => 'performance_sbb_speed_max', 'content' => $data->performance_sbb_speed_max
									),
									array(
										'name' => 'dimensions_length_min', 'content' => $data->dimensions_length_min
									),
									array(
										'name' => 'dimensions_length_max', 'content' => $data->dimensions_length_max
									),
									array(
										'name' => 'dimensions_breadth_min', 'content' => $data->dimensions_breadth_min
									),
									array(
										'name' => 'dimensions_breadth_max', 'content' => $data->dimensions_breadth_max
									),
									array(
										'name' => 'dimensions_depth_min', 'content' => $data->dimensions_depth_min
									),
									array(
										'name' => 'dimensions_depth_max', 'content' => $data->dimensions_depth_max
									),
									array(
										'name' => 'dimensions_draft_min', 'content' => $data->dimensions_draft_min
									),
									array(
										'name' => 'dimensions_draft_max', 'content' => $data->dimensions_draft_max
									),
									array(
										'name' => 'end_client', 'content' => ($data->end_client ? $data->end_client : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'class', 'content' => ($data->class ? $data->class : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'ncdmb', 'content' => ($data->ncdmb ? $data->ncdmb : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'cleardeckarea_min', 'content' => ($data->cleardeckarea_min ? $data->cleardeckarea_min.' SQM' : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'cleardeckarea_max', 'content' => ($data->cleardeckarea_max ? $data->cleardeckarea_max.' SQM' : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'deckstrength_min', 'content' => ($data->deckstrength_min ? $data->deckstrength_min.' MT/sqm' : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'deckstrength_max', 'content' => ($data->deckstrength_max ? $data->deckstrength_max.' MT/sqm' : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'deckcrane', 'content' => ($data->deckcrane ? $data->deckcrane : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'dp1', 'content' => ($data->dp1 ? $data->dp1 : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'dp2', 'content' => ($data->dp2 ? $data->dp2 : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'valid_ovid_cmid', 'content' => ($data->valid_ovid_cmid ? $data->valid_ovid_cmid : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'inspection', 'content' => ($data->inspection ? $data->inspection : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'purpose', 'content' => ($data->purpose ? $data->purpose : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'port_of_delivery', 'content' => ($data->port_of_delivery ? $data->port_of_delivery : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'port_of_redelivery', 'content' => ($data->port_of_redelivery ? $data->port_of_redelivery : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'fuel_consumption_on_tow', 'content' => ($data->fuel_consumption_on_tow ? $data->fuel_consumption_on_tow : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'accommodation_passengers_min', 'content' => ($data->accommodation_passengers_min ? $data->accommodation_passengers_min : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'accommodation_passengers_max', 'content' => ($data->accommodation_passengers_max ? $data->accommodation_passengers_max : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'accommodation_hospital', 'content' => ($data->accommodation_hospital ? $data->accommodation_hospital : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'helipad', 'content' => ($data->helipad ? $data->helipad : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'additional_data', 'content' => ($data->additional_data ? $data->additional_data : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'vessel_specification_document', 'content' => $document
									),
									array(
										'name' => 'created_at', 'content' => $date->format('d-m-Y H:i:s')
									)
								);
					
				$to = 	array(
							'email' => $data->useremail,
							'name' => $data->username
						);

				$this->sendMailPHPMailer('charter', $content, $to, 'MVXchange: Vessel Charter Order');

				#Send sms to user
				$message = "MVXchange received your Charter order!";
				$message .= "\n\n";
				$message .= "Hey ".$data->username.", your order for a Vessel has been received. You will now begin to get offers in line with the specifications provided.";

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $data->usermobile, BETASMS_SENDER);
			}

			return $response;
		}

		public function addvessel($data){
			$response = [];

			$date = new DateTime(null, new DateTimeZone('Africa/Lagos'));
			$created_at = $date->getTimestamp();
			$document = "";
			$vessel_photos = [];

			$folder = $data->userid.'_'.date('YmdHis',time()).mt_rand();
			mkdir("../assets/images/vessels/".$folder, 0777, true);

			if (isset($data->vessel_specification_document)) {
				$cleanname = preg_replace('/\s+/', '', $data->vessel_specification_documentname);
				$cleanname = str_replace(".", "", $cleanname);
				$random_hash = $cleanname.date('YmdHis',time()).mt_rand();

				$extension = substr($data->vessel_specification_documentname, strrpos($data->vessel_specification_documentname, '.') + 1);
				
				$document = "../assets/images/vessels/".$folder."/".$random_hash.'.'.$extension;
				
				$image = $this->base64_to_image( $data->vessel_specification_document, $document );
			}
			
			foreach ($data->vessel_photopreview as $key => $photo) {
				$extension = substr($photo->name, strrpos($photo->name, '.') + 1);
				$photo_name = "../assets/images/vessels/".$folder."/".$folder.$key.'.'.$extension;
				array_push($vessel_photos, $photo_name);
				
				$image = $this->base64_to_image( $photo->image, $photo_name );
			}

			if($data->vessel_availability == 'Unavailable'){
				$vessel_availability = $data->unavailable_till;
			}else{
				$vessel_availability = $data->vessel_availability;
			}

			$register = $this->db->prepare("INSERT INTO vessels(user_id, vessel_availability, daily_hire_rate, vessel_photos, imo_number, vessel_name, ownership_status, current_location, year_built, specification_sheet, preferred_flag, classification, classification_expiry, purpose, vessel_type, bp, bhp, da, ds, dp, maximum_speed, dwt, grt, length, breadth_moulded, depth_moulded, maximum_draft, accommodation, deck_crane, helipad, valid_ncdmb_class, valid_ovidcmid, created_at, updated_at) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$register->bind_param("issssssssssssssiiiiiiiiiiiiiisssss", $data->userid, $vessel_availability, $data->daily_hire_rate, json_encode($vessel_photos), $data->imo_number, $data->vessel_name, $data->ownership_status, $data->current_location, $data->year_built, $document, $data->flag, $data->class, $data->classification_expiry, $data->purpose, $data->vessel_type, $data->bp, $data->bhp, $data->da, $data->ds, $data->dynamic_positioning, $data->maximum_speed, $data->deadweight_tonnage, $data->gross_tonnage, $data->length_oa, $data->breadth_moulded, $data->depth_moulded, $data->maximum_draft, $data->accommodation, $data->deckcrane, $data->helipad, $data->valid_ncdmb_class, $data->valid_ovidcmid, $created_at, $created_at);

			if($register->execute()){
				$response['msg'] = "Vessel Added";
				$daily_hire_rate = (string) $data->daily_hire_rate;

				$content = 	array(
									array(
										'name' => 'name', 'content' => $data->username
									),
									array(
										'name' => 'vessel_availability', 'content' => (($data->vessel_availability == 'Unavailable') ? "Unavailable till ".$data->unavailable_till : $data->vessel_availability)
									),
									array(
										'name' => 'daily_hire_rate', 'content' => $daily_hire_rate
									),
									array(
										'name' => 'imo_number', 'content' => $data->imo_number
									),
									array(
										'name' => 'vessel_name', 'content' => $data->vessel_name
									),
									array(
										'name' => 'ownership_status', 'content' => $data->ownership_status
									),
									array(
										'name' => 'current_location', 'content' => $data->current_location
									),
									array(
										'name' => 'year_built', 'content' => $data->year_built
									),
									array(
										'name' => 'flag', 'content' => $data->flag
									),
									array(
										'name' => 'class', 'content' => $data->class
									),
									array(
										'name' => 'classification_expiry', 'content' => $data->classification_expiry
									),
									array(
										'name' => 'purpose', 'content' => $data->purpose
									),
									array(
										'name' => 'vessel_type', 'content' => $data->vessel_type
									),
									array(
										'name' => 'bp', 'content' => $data->bp
									),
									array(
										'name' => 'bhp', 'content' => $data->bhp
									),
									array(
										'name' => 'da', 'content' => ($data->da ? $data->da : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'ds', 'content' => ($data->ds ? $data->ds : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'dynamic_positioning', 'content' => ($data->dynamic_positioning ? $data->dynamic_positioning : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'maximum_speed', 'content' => $data->maximum_speed
									),
									array(
										'name' => 'deadweight_tonnage', 'content' => ($data->deadweight_tonnage ? $data->deadweight_tonnage : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'gross_tonnage', 'content' => $data->gross_tonnage
									),
									array(
										'name' => 'length_oa', 'content' => ($data->length_oa ? $data->length_oa : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'breadth_moulded', 'content' => ($data->breadth_moulded ? $data->breadth_moulded : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'depth_moulded', 'content' => ($data->depth_moulded ? $data->depth_moulded : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'maximum_draft', 'content' => ($data->maximum_draft ? $data->maximum_draft : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'accommodation', 'content' => ($data->accommodation ? $data->accommodation : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'deckcrane', 'content' => ($data->deckcrane ? $data->deckcrane : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'helipad', 'content' => ($data->helipad ? $data->helipad : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'valid_ncdmb_class', 'content' => ($data->valid_ncdmb_class ? $data->valid_ncdmb_class : '<i style="font-weight: 300;">Not Specified</i>')
									),
									array(
										'name' => 'valid_ovidcmid', 'content' => $data->valid_ovidcmid
									),
									array(
										'name' => 'created_at', 'content' => $created_at
									)
								);
					
				$to = 	array(
							'email' => $data->useremail,
							'name' => $data->username
						);

				$this->sendMailPHPMailer('addvessel', $content, $to, 'MVXchange: Vessel Added');

				#Send sms to user
				$message = "MVXchange received your Vessel details!";
				$message .= "\n\n";
				$message .= "Hey ".$data->username.", your Vessel details have been received. You will now begin to get requests in line with the specifications provided.";

				$this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $data->usermobile, BETASMS_SENDER);
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
				
		public function login($data){
			$response = [];

			$sql = $this->db->prepare('SELECT * FROM users WHERE email = ? AND password = ?');
			$sql->bind_param('ss', strtolower($data->email), md5($data->password));
			$sql->execute();
			$sql->store_result();
			$sql->bind_result($id, $email, $category, $password, $verificationcode, $verified, $enabled, $created_at);
			$sql->fetch();
			
			$result = $sql->num_rows();
			
			if($result > 0){
				if($category == "Charterer"){
					$sql = $this->db->prepare('SELECT * FROM charterer WHERE userid = ?');
					$sql->bind_param('i', $id);
					$sql->execute();
					$sql->store_result();
					$sql->bind_result($c_id, $c_userid, $c_name, $c_company_name, $c_email, $c_mobile, $c_updatedat);
					$sql->fetch();

					$reachname = $c_name;
					$reachmobile = $c_mobile;
				}

				if($category == "Ship Owner"){
					$sql = $this->db->prepare('SELECT * FROM shipowner WHERE userid = ?');
					$sql->bind_param('i', $id);
					$sql->execute();
					$sql->store_result();
					$sql->bind_result($s_id, $s_userid, $s_subcategory, $s_companylogo, $s_businessname, $s_companyregistrationnumber, $s_website, $s_businessemail, $s_businessmobile, $s_services, $s_corporateofficeaddress, $s_contactname, $s_contactmobile, $s_contactemail, $s_companyprofile, $s_permits, $s_updatedat);
					$sql->fetch();

					$reachname = $s_contactname.' ('.$s_businessname.')';
					$reachmobile = $s_contactemail;
				}

				if($category == "Procurement Vendor / Supplier"){
					$sql = $this->db->prepare('SELECT * FROM procurement_agent WHERE userid = ?');
					$sql->bind_param('i', $id);
					$sql->execute();
					$sql->store_result();
					$sql->bind_result($p_id, $p_userid, $p_subcategory, $p_logo, $p_name, $p_company_registration_number, $p_website, $p_phone_number, $p_services, $p_country, $p_corporate_office_address, $p_name_contact_person, $p_mobile_contact_person, $p_email_contact_person, $p_permits, $p_company_profile, $p_updatedat);
					$sql->fetch();

					$reachname = $p_name_contact_person.' ('.$p_name.')';
					$reachmobile = $p_email_contact_person;
				}

				if (($verified == 1) && ($enabled == 1)) {				
					$login['data']['id'] = $id;
					$login['data']['email'] = $email;
					$login['data']['category'] = $category;
					$login['data']['verificationcode'] = $verificationcode;
					$login['data']['verified'] = $verified;
					$login['data']['enabled'] = $enabled;
					$login['data']['created_at'] = $created_at;

					$login['data']['hashed_password'] = $password;
					$login['msg'] = "Logged In";
					$login['data']['display_name'] = $reachname;

					if($category == "Charterer"){
						$login['data']['c_id'] = $c_id;
						$login['data']['c_userid'] = $c_userid;
						$login['data']['c_name'] = $c_name;
						$login['data']['c_company_name'] = $c_company_name;
						$login['data']['c_email'] = $c_email;
						$login['data']['c_mobile'] = $c_mobile;
						$login['data']['c_updatedat'] = $c_updatedat;
					}

					if($category == "Ship Owner"){
						$login['data']['s_id'] = $s_id;
						$login['data']['s_userid'] = $s_userid;
						$login['data']['s_subcategory'] = $s_subcategory;
						$login['data']['s_companylogo'] = $s_companylogo;
						$login['data']['s_businessname'] = $s_businessname;
						$login['data']['s_companyregistrationnumber'] = $s_companyregistrationnumber;
						$login['data']['s_website'] = $s_website;
						$login['data']['s_businessemail'] = $s_businessemail;
						$login['data']['s_businessmobile'] = $s_businessmobile;
						$login['data']['s_services'] = $s_services;
						$login['data']['s_corporateofficeaddress'] = $s_corporateofficeaddress;
						$login['data']['s_contactname'] = $s_contactname;
						$login['data']['s_contactmobile'] = $s_contactmobile;
						$login['data']['s_contactemail'] = $s_contactemail;
						$login['data']['s_companyprofile'] = $s_companyprofile;
						$login['data']['s_permits'] = $s_permits;
						$login['data']['s_updatedat'] = $s_updatedat;
					}

					if($category == "Procurement Vendor / Supplier"){
						$login['data']['p_id'] = p_id;
						$login['data']['p_userid'] = p_userid;
						$login['data']['p_subcategory'] = p_subcategory;
						$login['data']['p_logo'] = p_logo;
						$login['data']['p_name'] = p_name;
						$login['data']['p_company_registration_number'] = p_company_registration_number;
						$login['data']['p_website'] = p_website;
						$login['data']['p_phone_number'] = p_phone_number;
						$login['data']['p_services'] = p_services;
						$login['data']['p_country'] = p_country;
						$login['data']['p_corporate_office_address'] = p_corporate_office_address;
						$login['data']['p_name_contact_person'] = p_name_contact_person;
						$login['data']['p_mobile_contact_person'] = p_mobile_contact_person;
						$login['data']['p_email_contact_person'] = p_email_contact_person;
						$login['data']['p_permits'] = p_permits;
						$login['data']['p_company_profile'] = p_company_profile;
						$login['data']['p_updatedat'] = p_updatedat;
					}
				}else if (($verified == 0) && ($enabled == 1)){
					$login['msg'] = "Account Not Verified";
				}else if (($verified == 1) && ($enabled == 0)){
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
			$sql->bind_result($id, $email, $category, $password, $verificationcode, $verified, $enabled, $created_at);
			$sql->fetch();

			if($sql->num_rows() > 0){
				if($category == "Charterer"){
					$sql = $this->db->prepare('SELECT * FROM charterer WHERE userid = ?');
					$sql->bind_param('i', $id);
					$sql->execute();
					$sql->store_result();
					$sql->bind_result($c_id, $c_userid, $c_name, $c_company_name, $c_email, $c_mobile, $c_updatedat);
					$sql->fetch();

					$reachname = $c_name;
					$reachmobile = $c_mobile;
				}

				if($category == "Ship Owner"){
					$sql = $this->db->prepare('SELECT * FROM shipowner WHERE userid = ?');
					$sql->bind_param('i', $id);
					$sql->execute();
					$sql->store_result();
					$sql->bind_result($s_id, $s_userid, $s_subcategory, $s_companylogo, $s_businessname, $s_companyregistrationnumber, $s_website, $s_businessemail, $s_businessmobile, $s_services, $s_corporateofficeaddress, $s_contactname, $s_contactmobile, $s_contactemail, $s_companyprofile, $s_permits, $s_updatedat);
					$sql->fetch();

					$reachname = $s_contactname.' ('.$s_businessname.')';
					$reachmobile = $s_contactemail;
				}

				if($category == "Procurement Vendor / Supplier"){
					$sql = $this->db->prepare('SELECT * FROM procurement_agent WHERE userid = ?');
					$sql->bind_param('i', $id);
					$sql->execute();
					$sql->store_result();
					$sql->bind_result($p_id, $p_userid, $p_subcategory, $p_logo, $p_name, $p_company_registration_number, $p_website, $p_phone_number, $p_services, $p_country, $p_corporate_office_address, $p_name_contact_person, $p_mobile_contact_person, $p_email_contact_person, $p_permits, $p_company_profile, $p_updatedat);
					$sql->fetch();

					$reachname = $p_name_contact_person.' ('.$p_name.')';
					$reachmobile = $p_email_contact_person;
				}

				$verification_check = ($verificationcode == $code);

				if($verification_check && ($verified == 0)){
					$response = [
									'verification' => $verification_check,
									'name' => $reachname,
									'email' => $email,
									'mobile' => $reachmobile
								];
				}else if($verification_check && ($verified == 1)){
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
				
		public function verify($data){
			$response = [];
			$verified = 1;

			$verify = $this->verifyCode(strtolower($data->email), $data->code);

			if($verify['verification']){
				$sql = $this->db->prepare('UPDATE users SET verified = ? WHERE email = ?');
				$sql->bind_param('is', $verified, strtolower($data->email));
				$sql->execute();

				$response['msg'] = 'Account Verified';

				$content = 	array(
									array(
										'name' => 'name', 'content' => $verify['name']
									)
								);
					
				$to = 	array(
							'email' => $verify['email'],
							'name' => $verify['name']
						);

				$this->sendMailPHPMailer('verification', $content, $to, 'MVXchange: Verification successful');

				#Send sms to user
				$message = "MVXchange Verification Successful!";
				$message .= "\n\n";
				$message .= "You did it! ".$verify['name'].", your account is now verified";

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
			$sql->bind_result($id, $email, $category, $password, $verificationcode, $verified, $enabled, $created_at);
			$sql->fetch();

			if($sql->num_rows > 0){
				if($category == "Charterer"){
					$sql = $this->db->prepare('SELECT * FROM charterer WHERE userid = ?');
					$sql->bind_param('i', $id);
					$sql->execute();
					$sql->store_result();
					$sql->bind_result($c_id, $c_userid, $c_name, $c_company_name, $c_email, $c_mobile, $c_updatedat);
					$sql->fetch();

					$reachname = $c_name;
					$reachmobile = $c_mobile;
				}

				if($category == "Ship Owner"){
					$sql = $this->db->prepare('SELECT * FROM shipowner WHERE userid = ?');
					$sql->bind_param('i', $userid);
					$sql->execute();
					$sql->store_result();
					$sql->bind_result($s_id, $s_userid, $s_subcategory, $s_companylogo, $s_businessname, $s_companyregistrationnumber, $s_website, $s_businessemail, $s_businessmobile, $s_services, $s_corporateofficeaddress, $s_contactname, $s_contactmobile, $s_contactemail, $s_companyprofile, $s_permits, $s_updatedat);
					$sql->fetch();

					$reachname = $s_contactname.' ('.$s_businessname.')';
					$reachmobile = $s_contactemail;
				}

				if($category == "Procurement Vendor / Supplier"){
					$sql = $this->db->prepare('SELECT * FROM procurement_agent WHERE userid = ?');
					$sql->bind_param('i', $userid);
					$sql->execute();
					$sql->store_result();
					$sql->bind_result($p_id, $p_userid, $p_subcategory, $p_logo, $p_name, $p_company_registration_number, $p_website, $p_phone_number, $p_services, $p_country, $p_corporate_office_address, $p_name_contact_person, $p_mobile_contact_person, $p_email_contact_person, $p_permits, $p_company_profile, $p_updatedat);
					$sql->fetch();

					$reachname = $p_name_contact_person.' ('.$p_name.')';
					$reachmobile = $p_email_contact_person;
				}
				$content = 	array(
										array(
											'name' => 'name', 'content' => $reachname
										),
										array(
											'name' => 'link', 'content' => $verificationcode
										)
									);

				$to = 	array(
								'email' => $email,
								'name' => $reachname
							);

				$this->sendMailPHPMailer('signup', $content, $to, 'Welcome to MVXchange');

				#Send sms to user
				$message = "Welcome to MVXchange!";
				$message .= "\n\n";
				$message .= "To verify your account, please use this code on first login:"."\n";
				$message .= $verificationcode;

				// $this->sendSMS(BETASMS_USER, BETASMS_PASS, $message, $reachmobile, BETASMS_SENDER);

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

		public function getvessels() {
			$vessels = [];

			$stmt = $this->db->prepare('SELECT * FROM vessels');
	        $stmt->bind_result($id, $user_id, $vessel_availability, $daily_hire_rate, $vessel_photos, $imo_number, $vessel_name, $ownership_status, $current_location, $year_built, $specification_sheet, $preferred_flag, $classification, $classification_expiry, $purpose, $vessel_type, $bp, $bhp, $da, $ds, $dp, $maximum_speed, $dwt, $grt, $length, $breadth_moulded, $depth_moulded, $maximum_draft, $accommodation, $deck_crane, $helipad, $valid_ncdmb_class, $valid_ovidcmid, $created_at, $updated_at);
			$result = $stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows >= "1") {

		        	while($data = $stmt->fetch()){ 
		        		    $vessel = [
			        				'id' => $id,
			        				'user_id' => $user_id,
			        				'vessel_availability' => $vessel_availability,
			        				'daily_hire_rate' => $daily_hire_rate,
			        				'vessel_photos' => $vessel_photos,
			        				'imo_number' => $imo_number,
			        				'vessel_name' => $vessel_name,
			        				'ownership_status' => $ownership_status,
			        				'current_location' => $current_location,
			        				'year_built' => $year_built,
			        				'specification_sheet' => $specification_sheet,
			        				'preferred_flag' => $preferred_flag,
			        				'classification' => $classification,
			        				'classification_expiry' => $classification_expiry,
			        				'purpose' => $purpose,
			        				'vessel_type' => $vessel_type,
			        				'bp' => $bp,
			        				'bhp' => $bhp,
			        				'da' => $da,
			        				'ds' => $ds,
			        				'dp' => $dp,
			        				'maximum_speed' => $maximum_speed,
			        				'dwt' => $dwt,
			        				'grt' => $grt,
			        				'length' => $length,
			        				'breadth_moulded' => $breadth_moulded,
			        				'depth_moulded' => $depth_moulded,
			        				'maximum_draft' => $maximum_draft,
			        				'accommodation' => $accommodation,
			        				'deck_crane' => $deck_crane,
			        				'helipad' => $helipad,
			        				'valid_ncdmb_class' => $valid_ncdmb_class,
			        				'valid_ovidcmid' => $valid_ovidcmid,
			        				'created_at' => $created_at,
			        				'updated_at' => $updated_at
			        			];

			        array_push($vessels, $vessel);
			    }
	        }

		    return $vessels;
		}

		public function getvessel($id) {
			$stmt = $this->db->prepare('SELECT * FROM vessels WHERE id = ?');
			$stmt->bind_param("i", $id);
	        $stmt->bind_result($id, $user_id, $vessel_availability, $daily_hire_rate, $vessel_photos, $imo_number, $vessel_name, $ownership_status, $current_location, $year_built, $specification_sheet, $preferred_flag, $classification, $classification_expiry, $purpose, $vessel_type, $bp, $bhp, $da, $ds, $dp, $maximum_speed, $dwt, $grt, $length, $breadth_moulded, $depth_moulded, $maximum_draft, $accommodation, $deck_crane, $helipad, $valid_ncdmb_class, $valid_ovidcmid, $created_at, $updated_at);
			$result = $stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows >= "1") {

		        	while($data = $stmt->fetch()){ 
		        		    $vessel = [
			        				'id' => $id,
			        				'user_id' => $user_id,
			        				'vessel_availability' => $vessel_availability,
			        				'daily_hire_rate' => $daily_hire_rate,
			        				'vessel_photos' => $vessel_photos,
			        				'imo_number' => $imo_number,
			        				'vessel_name' => $vessel_name,
			        				'ownership_status' => $ownership_status,
			        				'current_location' => $current_location,
			        				'year_built' => $year_built,
			        				'specification_sheet' => $specification_sheet,
			        				'preferred_flag' => $preferred_flag,
			        				'classification' => $classification,
			        				'classification_expiry' => $classification_expiry,
			        				'purpose' => $purpose,
			        				'vessel_type' => $vessel_type,
			        				'bp' => $bp,
			        				'bhp' => $bhp,
			        				'da' => $da,
			        				'ds' => $ds,
			        				'dp' => $dp,
			        				'maximum_speed' => $maximum_speed,
			        				'dwt' => $dwt,
			        				'grt' => $grt,
			        				'length' => $length,
			        				'breadth_moulded' => $breadth_moulded,
			        				'depth_moulded' => $depth_moulded,
			        				'maximum_draft' => $maximum_draft,
			        				'accommodation' => $accommodation,
			        				'deck_crane' => $deck_crane,
			        				'helipad' => $helipad,
			        				'valid_ncdmb_class' => $valid_ncdmb_class,
			        				'valid_ovidcmid' => $valid_ovidcmid,
			        				'created_at' => $created_at,
			        				'updated_at' => $updated_at
			        			];
			    }
	        }

		    return $vessel;
		}

		public function getoffers() {
			$offers = [];

			$stmt = $this->db->prepare('SELECT vessels.id, vessels.user_id, vessels.vessel_availability, vessels.daily_hire_rate, vessels.vessel_photos, vessels.imo_number, vessels.vessel_name, vessels.ownership_status, vessels.current_location, vessels.year_built, vessels.specification_sheet, vessels.preferred_flag, vessels.classification, vessels.classification_expiry, vessels.purpose, vessels.vessel_type, vessels.bp, vessels.bhp, vessels.da, vessels.ds, vessels.dp, vessels.maximum_speed, vessels.dwt, vessels.grt, vessels.length, vessels.breadth_moulded, vessels.depth_moulded, vessels.maximum_draft, vessels.accommodation, vessels.deck_crane, vessels.helipad, vessels.valid_ncdmb_class, vessels.valid_ovidcmid, vessels.created_at, vessels.updated_at, offers.id, offers.charter_id, offers.vessel_id, offers.created_at, users.id, shipowner.businessname  FROM offers LEFT JOIN charters ON offers.charter_id = charters.id LEFT JOIN vessels ON offers.vessel_id = vessels.id LEFT JOIN users ON vessels.user_id = users.id LEFT JOIN shipowner ON users.id = shipowner.userid');
	        $stmt->bind_result($vessels_id, $vessels_user_id, $vessels_vessel_availability, $vessels_daily_hire_rate, $vessels_vessel_photos, $vessels_imo_number, $vessels_vessel_name, $vessels_ownership_status, $vessels_current_location, $vessels_year_built, $vessels_specification_sheet, $vessels_preferred_flag, $vessels_classification, $vessels_classification_expiry, $vessels_purpose, $vessels_vessel_type, $vessels_bp, $vessels_bhp, $vessels_da, $vessels_ds, $vessels_dp, $vessels_maximum_speed, $vessels_dwt, $vessels_grt, $vessels_length, $vessels_breadth_moulded, $vessels_depth_moulded, $vessels_maximum_draft, $vessels_accommodation, $vessels_deck_crane, $vessels_helipad, $vessels_valid_ncdmb_class, $vessels_valid_ovidcmid, $vessels_created_at, $vessels_updated_at, $offers_id, $offers_charter_id, $offers_vessel_id, $offers_created_at, $user_id, $user_name);
			$result = $stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows >= "1") {

		        	while($data = $stmt->fetch()){ 
		        		    $offer = [
			        				'vessels_id' => $vessels_id,
			        				'vessels_user_id' => $vessels_user_id,
			        				'vessels_vessel_availability' => $vessels_vessel_availability,
			        				'vessels_daily_hire_rate' => $vessels_daily_hire_rate,
			        				'vessels_vessel_photos' => $vessels_vessel_photos,
			        				'vessels_imo_number' => $vessels_imo_number,
			        				'vessels_vessel_name' => $vessels_vessel_name,
			        				'vessels_ownership_status' => $vessels_ownership_status,
			        				'vessels_current_location' => $vessels_current_location,
			        				'vessels_year_built' => $vessels_year_built,
			        				'vessels_specification_sheet' => $vessels_specification_sheet,
			        				'vessels_preferred_flag' => $vessels_preferred_flag,
			        				'vessels_classification' => $vessels_classification,
			        				'vessels_classification_expiry' => $vessels_classification_expiry,
			        				'vessels_purpose' => $vessels_purpose,
			        				'vessels_vessel_type' => $vessels_vessel_type,
			        				'vessels_bp' => $vessels_bp,
			        				'vessels_bhp' => $vessels_bhp,
			        				'vessels_da' => $vessels_da,
			        				'vessels_ds' => $vessels_ds,
			        				'vessels_dp' => $vessels_dp,
			        				'vessels_maximum_speed' => $vessels_maximum_speed,
			        				'vessels_dwt' => $vessels_dwt,
			        				'vessels_grt' => $vessels_grt,
			        				'vessels_length' => $vessels_length,
			        				'vessels_breadth_moulded' => $vessels_breadth_moulded,
			        				'vessels_depth_moulded' => $vessels_depth_moulded,
			        				'vessels_maximum_draft' => $vessels_maximum_draft,
			        				'vessels_accommodation' => $vessels_accommodation,
			        				'vessels_deck_crane' => $vessels_deck_crane,
			        				'vessels_helipad' => $vessels_helipad,
			        				'vessels_valid_ncdmb_class' => $vessels_valid_ncdmb_class,
			        				'vessels_valid_ovidcmid' => $vessels_valid_ovidcmid,
			        				'vessels_created_at' => $vessels_created_at,
			        				'vessels_updated_at' => $vessels_updated_at,
			        				'offers_id' => $offers_id,
			        				'offers_charter_id' => $offers_charter_id,
			        				'offers_vessel_id' => $offers_vessel_id,
			        				'offers_created_a' => $offers_created_at,
			        				'user_id' => $user_id,
			        				'user_name' => $user_name
			        			];

			        array_push($offers, $offer);
			    }
	        }

		    return $offers;
		}

		public function getcharterrequests() {
			$charters = [];

			$stmt = $this->db->prepare('SELECT * FROM charters');
	        $stmt->bind_result($id, $user_id, $preferred_shipowner_category, $vessel_type, $identity, $preferred_flag, $max_age, $firm_duration, $tonnage_dwt_min, $tonnage_dwt_max, $tonnage_grt_min, $tonnage_grt_max, $expected_mob_date, $preferred_daily_hire_rate, $location_of_operation, $scope_of_work, $performance_sbp_bp_min, $performance_sbp_bp_max, $performance_sbp_bhp_min, $performance_sbp_bhp_max, $performance_sbp_speed_min, $performance_sbp_speed_max, $dimensions_length_min, $dimensions_length_max, $dimensions_breadth_min, $dimensions_breadth_max, $dimensions_depth_min, $dimensions_depth_max, $dimensions_draft_min, $dimensions_draft_max, $end_client, $reg_doc_class, $reg_doc_ncdmb, $dec_cargo_cleardeckarea_min, $dec_cargo_cleardeckarea_max, $dec_cargo_deckstrength_min, $dec_cargo_deckstrength_max, $dec_cargo_deckcrane, $dp_one, $dp_two, $valid_ovid_cmid, $add_inspection, $purpose, $port_of_delivery, $port_of_redelivery, $fuel_consumption_on_tow, $accommodation_passengers_min, $accommodation_passengers_max, $accommodation_hospital, $helipad, $additional_data, $vessel_specification_document, $created_at, $updated_at);
			$result = $stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows >= "1") {

		        	while($data = $stmt->fetch()){ 
		        		    $charter = [
		        		    		'id' => $id,
		        		    		'user_id' => $user_id,
		        		    		'preferred_shipowner_category' => $preferred_shipowner_category,
		        		    		'vessel_type' => $vessel_type,
		        		    		'identity' => $identity,
		        		    		'preferred_flag' => $preferred_flag,
		        		    		'max_age' => $max_age,
		        		    		'firm_duration' => $firm_duration,
		        		    		'tonnage_dwt_min' => $tonnage_dwt_min,
		        		    		'tonnage_dwt_max' => $tonnage_dwt_max,
		        		    		'tonnage_grt_min' => $tonnage_grt_min,
		        		    		'tonnage_grt_max' => $tonnage_grt_max,
		        		    		'expected_mob_date' => $expected_mob_date,
		        		    		'preferred_daily_hire_rate' => $preferred_daily_hire_rate,
		        		    		'location_of_operation' => $location_of_operation,
		        		    		'scope_of_work' => $scope_of_work,
		        		    		'performance_sbp_bp_min' => $performance_sbp_bp_min,
		        		    		'performance_sbp_bp_max' => $performance_sbp_bp_max,
		        		    		'performance_sbp_bhp_min' => $performance_sbp_bhp_min,
		        		    		'performance_sbp_bhp_max' => $performance_sbp_bhp_max,
		        		    		'performance_sbp_speed_min' => $performance_sbp_speed_min,
		        		    		'performance_sbp_speed_max' => $performance_sbp_speed_max,
		        		    		'dimensions_length_min' => $dimensions_length_min,
		        		    		'dimensions_length_max' => $dimensions_length_max,
		        		    		'dimensions_breadth_min' => $dimensions_breadth_min,
		        		    		'dimensions_breadth_max' => $dimensions_breadth_max,
		        		    		'dimensions_depth_min' => $dimensions_depth_min,
		        		    		'dimensions_depth_max' => $dimensions_depth_max,
		        		    		'dimensions_draft_min' => $dimensions_draft_min,
		        		    		'dimensions_draft_max' => $dimensions_draft_max,
		        		    		'end_client' => $end_client,
		        		    		'reg_doc_class' => $reg_doc_class,
		        		    		'reg_doc_ncdmb' => $reg_doc_ncdmb,
		        		    		'dec_cargo_cleardeckarea_min' => $dec_cargo_cleardeckarea_min,
		        		    		'dec_cargo_cleardeckarea_max' => $dec_cargo_cleardeckarea_max,
		        		    		'dec_cargo_deckstrength_min' => $dec_cargo_deckstrength_min,
		        		    		'dec_cargo_deckstrength_max' => $dec_cargo_deckstrength_max,
		        		    		'dec_cargo_deckcrane' => $dec_cargo_deckcrane,
		        		    		'dp_one' => $dp_one,
		        		    		'dp_two' => $dp_two,
		        		    		'valid_ovid_cmid' => $valid_ovid_cmid,
		        		    		'add_inspection' => $add_inspection,
		        		    		'purpose' => $purpose,
		        		    		'port_of_delivery' => $port_of_delivery,
		        		    		'port_of_redelivery' => $port_of_redelivery,
		        		    		'fuel_consumption_on_tow' => $fuel_consumption_on_tow,
		        		    		'accommodation_passengers_min' => $accommodation_passengers_min,
		        		    		'accommodation_passengers_max' => $accommodation_passengers_max,
		        		    		'accommodation_hospital' => $accommodation_hospital,
		        		    		'helipad' => $helipad,
		        		    		'additional_data' => $additional_data,
		        		    		'vessel_specification_document' => $vessel_specification_document,
		        		    		'created_at' => $created_at,
		        		    		'updated_at' => $updated_at
			        			];

			        array_push($charters, $charter);
			    }
	        }

		    return $charters;
		}

		public function getcharterrequest($id) {
			$stmt = $this->db->prepare('SELECT * FROM charters WHERE id = ?');
			$stmt->bind_param("i", $id);
	        $stmt->bind_result($charter_id, $user_id, $preferred_shipowner_category, $vessel_type, $identity, $preferred_flag, $max_age, $firm_duration, $tonnage_dwt_min, $tonnage_dwt_max, $tonnage_grt_min, $tonnage_grt_max, $expected_mob_date, $preferred_daily_hire_rate, $location_of_operation, $scope_of_work, $performance_sbp_bp_min, $performance_sbp_bp_max, $performance_sbp_bhp_min, $performance_sbp_bhp_max, $performance_sbp_speed_min, $performance_sbp_speed_max, $dimensions_length_min, $dimensions_length_max, $dimensions_breadth_min, $dimensions_breadth_max, $dimensions_depth_min, $dimensions_depth_max, $dimensions_draft_min, $dimensions_draft_max, $end_client, $reg_doc_class, $reg_doc_ncdmb, $dec_cargo_cleardeckarea_min, $dec_cargo_cleardeckarea_max, $dec_cargo_deckstrength_min, $dec_cargo_deckstrength_max, $dec_cargo_deckcrane, $dp_one, $dp_two, $valid_ovid_cmid, $add_inspection, $purpose, $port_of_delivery, $port_of_redelivery, $fuel_consumption_on_tow, $accommodation_passengers_min, $accommodation_passengers_max, $accommodation_hospital, $helipad, $additional_data, $vessel_specification_document, $created_at, $updated_at);
			$result = $stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows >= "1") {

		        	while($data = $stmt->fetch()){ 
		        		    $charter = [
		        		    		'id' => $charter_id,
		        		    		'user_id' => $user_id,
		        		    		'preferred_shipowner_category' => $preferred_shipowner_category,
		        		    		'vessel_type' => $vessel_type,
		        		    		'identity' => $identity,
		        		    		'preferred_flag' => $preferred_flag,
		        		    		'max_age' => $max_age,
		        		    		'firm_duration' => $firm_duration,
		        		    		'tonnage_dwt_min' => $tonnage_dwt_min,
		        		    		'tonnage_dwt_max' => $tonnage_dwt_max,
		        		    		'tonnage_grt_min' => $tonnage_grt_min,
		        		    		'tonnage_grt_max' => $tonnage_grt_max,
		        		    		'expected_mob_date' => $expected_mob_date,
		        		    		'preferred_daily_hire_rate' => $preferred_daily_hire_rate,
		        		    		'location_of_operation' => $location_of_operation,
		        		    		'scope_of_work' => $scope_of_work,
		        		    		'performance_sbp_bp_min' => $performance_sbp_bp_min,
		        		    		'performance_sbp_bp_max' => $performance_sbp_bp_max,
		        		    		'performance_sbp_bhp_min' => $performance_sbp_bhp_min,
		        		    		'performance_sbp_bhp_max' => $performance_sbp_bhp_max,
		        		    		'performance_sbp_speed_min' => $performance_sbp_speed_min,
		        		    		'performance_sbp_speed_max' => $performance_sbp_speed_max,
		        		    		'dimensions_length_min' => $dimensions_length_min,
		        		    		'dimensions_length_max' => $dimensions_length_max,
		        		    		'dimensions_breadth_min' => $dimensions_breadth_min,
		        		    		'dimensions_breadth_max' => $dimensions_breadth_max,
		        		    		'dimensions_depth_min' => $dimensions_depth_min,
		        		    		'dimensions_depth_max' => $dimensions_depth_max,
		        		    		'dimensions_draft_min' => $dimensions_draft_min,
		        		    		'dimensions_draft_max' => $dimensions_draft_max,
		        		    		'end_client' => $end_client,
		        		    		'reg_doc_class' => $reg_doc_class,
		        		    		'reg_doc_ncdmb' => $reg_doc_ncdmb,
		        		    		'dec_cargo_cleardeckarea_min' => $dec_cargo_cleardeckarea_min,
		        		    		'dec_cargo_cleardeckarea_max' => $dec_cargo_cleardeckarea_max,
		        		    		'dec_cargo_deckstrength_min' => $dec_cargo_deckstrength_min,
		        		    		'dec_cargo_deckstrength_max' => $dec_cargo_deckstrength_max,
		        		    		'dec_cargo_deckcrane' => $dec_cargo_deckcrane,
		        		    		'dp_one' => $dp_one,
		        		    		'dp_two' => $dp_two,
		        		    		'valid_ovid_cmid' => $valid_ovid_cmid,
		        		    		'add_inspection' => $add_inspection,
		        		    		'purpose' => $purpose,
		        		    		'port_of_delivery' => $port_of_delivery,
		        		    		'port_of_redelivery' => $port_of_redelivery,
		        		    		'fuel_consumption_on_tow' => $fuel_consumption_on_tow,
		        		    		'accommodation_passengers_min' => $accommodation_passengers_min,
		        		    		'accommodation_passengers_max' => $accommodation_passengers_max,
		        		    		'accommodation_hospital' => $accommodation_hospital,
		        		    		'helipad' => $helipad,
		        		    		'additional_data' => $additional_data,
		        		    		'vessel_specification_document' => $vessel_specification_document,
		        		    		'created_at' => $created_at,
		        		    		'updated_at' => $updated_at
			        			];
			    }
	        }

		    return $charter;
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