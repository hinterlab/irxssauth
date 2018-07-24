<?php
namespace Internetrix\IRXSSAuth\Forms;

use Internetrix\IRXSSAuth\IRXSSAuthenticator;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;

class IRXSSAuthLoginForm extends MemberLoginForm {
	
    protected $authenticator_class = IRXSSAuthenticator::class;
	
	public function __construct($controller, $name, $fields = null, $actions = null,
			$checkCurrentUser = true) {
		
		$this->extend('preConstruct', $controller, $name, $fields, $actions, $checkCurrentUser);
	
		parent::__construct($controller, $name, $fields, $actions);
	}
	
}
