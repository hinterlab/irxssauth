<?php
namespace Internetrix\Irxssauth;
class IRXSSAuthLoginForm extends MemberLoginForm {
	
	protected $authenticator_class = 'IRXSSAuthenticator';
	
	public function __construct($controller, $name, $fields = null, $actions = null,
			$checkCurrentUser = true) {
		
		$this->extend('preConstruct', $controller, $name, $fields, $actions, $checkCurrentUser);
	
		parent::__construct($controller, $name, $fields, $actions);
	}
	
}
