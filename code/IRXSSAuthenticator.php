<?php
namespace Internetrix\IRXSSAuth;

use function explode;
use Internetrix\IRXSSAuth\Extensions\IRXSSAuthMemberExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Security\Member;
use SilverStripe\Security\MemberAuthenticator\MemberAuthenticator;
use function ucfirst;

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
//	public function authenticate($RAW_data, Form $form = null) {
    public function authenticate(array $data, HTTPRequest $request, ValidationResult &$result = null) {
		
		if(array_key_exists('Email', $data) && $data['Email']){
			$SQL_user = Convert::raw2sql($data['Email']);
		} else {
			return false;
		}
		
		$identifier_field = Member::config()->unique_identifier_field;
		
		$member = null;
		$email 	= $SQL_user;
		$member = Member::get()
			->filter($identifier_field, $SQL_user)
			->first();

		if(!IRXSSAuthMemberExtension::is_internetrix_email($email) || ($member && !$member->IRXstaff)){
			return parent::authenticate($data, $request,$result);
		}
		
		$timeout = 40;
		$postfields = array('email'=>$email, 'pwd'=> $data['Password']);
		$IRXSSAuthConfig = Config::forClass(IRXSSAuthenticator::class);
		
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

        $myResult = print_r($resultRecord, true);
		if(isset($resultRecord['result']) && $resultRecord['result'] == true){
			
			if(!$member || !$member->ID){
				$member = new Member();
			}
			
			$member->$identifier_field = $email;
			if($identifier_field != 'Email'){
				$member->Email = $email;
			}

            $username = substr($email, 0, strpos($email, '@'));

			if($username){
			    $parts = explode(".", $username);
			    if(count($parts) > 1){
                    $member->FirstName = ucfirst($parts[0]);
                    $member->Surname = ucfirst($parts[1]);
                }elseif(isset($parts[0])){
                    $member->FirstName = ucfirst($parts[0]);
                }
            }

			$member->IRXstaff 	= true;
			
			$member->write();
			if(!$member->inGroupNoFilter('irx-staff')){
				$member->addToGroupByCodeNoFilter('irx-staff');
			}

            $request->getSession()->clear('BackURL');

            $result = $result ?: ValidationResult::create();
			
			return $member;
			
		}
		
		return null;
		
			
	}

	public static function get_login_form(Controller $controller) {
		return IRXSSAuthLoginForm::create($controller, "LoginForm")
			->addExtraClass('IRXSSAuthLoginForm')
			->setHTMLID('MemberLoginForm_LoginForm'); //need to set HTMLID so form messages from Security::permissionFailure continue to work 
	}
	
}
