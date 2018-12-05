<?php

namespace Internetrix\IRXSSAuth\Extensions;

use SilverStripe\ORM\DataExtension;
use SilverStripe\Control\Controller;
use Internetrix\IRXSSAuth\Forms\IRXSSAuthLoginForm;
use SilverStripe\Security\MemberAuthenticator\MemberLoginForm;

class IRXAuthMemberLoginExtension extends DataExtension {

    public function __construct(Controller $controller)
    {
        return IRXSSAuthLoginForm::create($controller, "LoginForm")
            ->addExtraClass('IRXSSAuthLoginForm')
            ->setHTMLID('MemberLoginForm_LoginForm'); //need to set HTMLID so form messages from Security::permissionFailure continue to work
    }
}
