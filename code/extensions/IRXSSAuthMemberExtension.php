<?php

class IRXSSAuthMemberExtension extends DataExtension {
	
	private static $db = array(
		'IRXstaff' => 'Boolean',
		'IRXSSAuthLoginToken'	=> 'Varchar(160)'	// Note: this currently holds a hash, not a token.
	);
	
	public function updateCMSFields(FieldList $fields){
		$fields->removeByName('IRXstaff');
	}
	
	public function isInternetrixEmail(){
		return self::is_internetrix_email($this->owner->Email);
	}
	
	public static function is_internetrix_email($email){
		if(!$email){
			return false;
		}
		
		$email = explode('@', $email);
		
		$irxEmails = IRXSSAuthenticator::config()->IRXEmailDomains;
		
		if(!isset($irxEmails)){
			return false;
		}
		
		if(!is_array($irxEmails)){
			$irxEmails = explode(',',$irxEmails);
		}
		
		if(count($email) == 2 && isset($email[1]) && in_array($email[1], $irxEmails)){
			return true;
		}
		
		return false;
	}
	
	public function inGroupNoFilter($group, $strict = false) {
		if(is_numeric($group)) {
			$groupCheckObj = Group::get()->setDataQueryParam('RemoveGroupFilter', true)->byID($group);
		} elseif(is_string($group)) {
			$SQL_group = Convert::raw2sql($group);
			$groupCheckObj = Group::get()->setDataQueryParam('RemoveGroupFilter', true)->filter('Code', $SQL_group);
		} elseif($group instanceof Group) {
			$groupCheckObj = $group;
		} else {
			user_error('Member::inGroup(): Wrong format for $group parameter', E_USER_ERROR);
		}
		
		if(!$groupCheckObj) return false;
		
		$groupCandidateObjs = ($strict) ? $this->owner->getManyManyComponents("Groups") : $this->owner->Groups();
		if($groupCandidateObjs) foreach($groupCandidateObjs as $groupCandidateObj) {
			if($groupCandidateObj->ID == $groupCheckObj->ID) return true;
		}

		return false;
	}

	public function addToGroupByCodeNoFilter($groupcode, $title = ""){
		$group = Group::get()->setDataQueryParam('RemoveGroupFilter', true)->filter('Code',Convert::raw2sql($groupcode))->first();
		if($group) {
			$this->owner->Groups()->add($group);
		}
		else {
			if(!$title) $title = $groupcode;
				
			$group = new Group();
			$group->Code = $groupcode;
			$group->Title = $title;
			$group->write();
				
			$this->owner->Groups()->add($group);
		}
	}
}
