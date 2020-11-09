<?php
namespace Internetrix\IRXSSAuth\Extensions;

use SilverStripe\Core\Convert;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;
use Internetrix\IRXSSAuth\IRXSSAuthenticator;
use SilverStripe\Core\Config\Config;

class IRXSSAuthMemberExtension extends DataExtension {
	
	private static $db = array(
		'IRXstaff'				=> 'Boolean',
		'IRXSSAuthLoginToken'	=> 'Text'	// Note: this currently holds a hash, not a token.
	);
	
	public function updateCMSFields(FieldList $fields){
		$fields->removeByName('IRXstaff');
		$fields->removeByName('IRXSSAuthLoginToken');
	}
	
	public function isInternetrixEmail(){
		return self::is_internetrix_email($this->owner->Email);
	}
	
	public static function is_internetrix_email($email){
		if(!$email){
			return false;
		}
		
		$email = explode('@', $email);
		
		$irxEmails = Config::forClass(IRXSSAuthenticator::class)->IRXEmailDomains;
		
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
			//JT Fix for SS4
			//$this->owner->Groups()->add($group);
			$group->Members()->add($this->owner);
		}
		else {
			if(!$title) $title = $groupcode;
				
			$group = new Group();
			$group->Code = $groupcode;
			$group->Title = $title;
			$group->write();
				
			//JT Fix for SS4
			//$this->owner->Groups()->add($group);
			$group->Members()->add($this->owner);
		}
	}
}
