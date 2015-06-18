<?php
class IRXSSAuthenticator extends MemberAuthenticator {
	
	/**
	 * Over write this function
	 *
	 * @param array $RAW_data Raw data to authenticate the user
	 * @param Form $form Optional: If passed, better error messages can be
	 *                             produced by using
	 *                             {@link Form::sessionMessage()}
	 * @return bool|Member Returns FALSE if authentication fails, otherwise
	 *                     the member object
	 * @see Security::setDefaultAdmin()
	 */
	public static function authenticate($RAW_data, Form $form = null) {
		if(array_key_exists('Email', $RAW_data) && $RAW_data['Email']){
			$SQL_user = Convert::raw2sql($RAW_data['Email']);
		} else {
			return false;
		}
		
		$member = null;
		$email 	= $SQL_user;
		$memberRecord = array();
		$member = Member::get()
			->filter(Member::config()->unique_identifier_field, $SQL_user)
			->first();

		if(!IRXSSAuthMemberExtension::is_internetrix_email($email) || ($member && !$member->IRXstaff)){
			return parent::authenticate($RAW_data, $form);
		}
		
		$ip = $_SERVER['SERVER_ADDR'];
		$IRXSSAuthConfig = IRXSSAuthenticator::config();
		
		if($ip == $IRXSSAuthConfig->IRXServerIP){
			$irxDBConfig = array(
				"type" 		=> "MySQLDatabase",
				"server" 	=> 'localhost',
				"username" 	=> $IRXSSAuthConfig->IRXDBUser,
				"password" 	=> $IRXSSAuthConfig->IRXDBPassword,
				"database" 	=> $IRXSSAuthConfig->IRXDBName
			);
			DB::setConn(new MySQLDatabase($irxDBConfig), $irxDBConfig['database']);
			$sql = 'SELECT DISTINCT "Member"."FirstName", "Member"."Surname", "Member"."Email", "Member"."Password", "Member"."RememberLoginToken",  "Member"."PasswordEncryption", "Member"."Salt", "Member"."PasswordExpiry", "Member"."LockedOutUntil", "Member"."Locale", "Member"."FailedLoginCount", "Member"."DateFormat", "Member"."TimeFormat", "Member"."ID" FROM "Member" WHERE ("Email" = \''.$email.'\' AND "Password" IS NOT NULL) ORDER BY "Member"."Email" ASC';
			
			$memberRecord = DB::getConn($irxDBConfig['database'])->query($sql, E_USER_ERROR)->nextRecord();
			
			if(isset($memberRecord) && $memberRecord){
				if(!$member || !$member->ID){
					$member = new Member();
				}
				$member->FirstName 		= $memberRecord['FirstName'];
				$member->Surname 		= $memberRecord['Surname'];
				$member->Email 			= $memberRecord['Email'];
				$member->Locale 		= $memberRecord['Locale'];
				$member->DateFormat 	= $memberRecord['DateFormat'];
				$member->TimeFormat 	= $memberRecord['TimeFormat'];
				$member->IRXstaff 		= true;
				
				$e = PasswordEncryptor::create_for_algorithm($memberRecord['PasswordEncryption']);
				$result = $e->check($memberRecord['Password'], $RAW_data['Password'], $memberRecord['Salt'], $member);
				if($result){
					$member->write();
					if(!$member->inGroupNoFilter('irx-staff')){
						$member->addToGroupByCodeNoFilter('irx-staff');
					}
					return $member;
				}
					
			}
			
			return false;
			
		}else{
			$timeout = 40;
			$postfields = array('email'=>$email, 'pwd'=> $RAW_data['Password']);
			
			//open connection
			$ch = curl_init();
			
			//set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $IRXSSAuthConfig->IRXSiteDomain.''.$IRXSSAuthConfig->IRXSiteAPIURL);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER , true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
			//execute post
			$response = curl_exec($ch);
			
			curl_close($ch);
			
			if(is_array(json_decode($response, true))){
				$resultRecord = json_decode($response, true);
			}else{
				return false;
			}
			if(isset($resultRecord['result']) && $resultRecord['result'] == true){
				
				if(!$member || !$member->ID){
					$member = new Member();
				}
				
				$member->Email 		= $email;
				$member->IRXstaff 	= true;
				
				$member->write();
				if(!$member->inGroupNoFilter('irx-staff')){
					$member->addToGroupByCodeNoFilter('irx-staff');
				}
				return $member;
				
			}
			
			return false;
		}
			
	}

	public static function get_login_form(Controller $controller) {
		return Object::create("IRXSSAuthLoginForm", $controller, "LoginForm");
	}
	
}
