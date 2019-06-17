<?php
namespace Internetrix\IRXSSAuth\Extensions;

use Internetrix\IRXSSAuth\Security\IRXBasicAuth;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataExtension;
use SilverStripe\View\Requirements;

class IRXAuthControllerExtension extends DataExtension
{
	function onBeforeInit()
    {
		if ($this->owner->basicAuthEnabled) {
			if (defined('SS_USE_BASIC_AUTH') && SS_USE_BASIC_AUTH) {
				IRXBasicAuth::protect_entire_site_if_necessary();
			} else {
				IRXBasicAuth::protect_staging_site_if_necessary();
			}
		}
		Requirements::javascript('internetrix/silverstripe-irxssauth:javascript/toggle.js');
        Requirements::css('internetrix/silverstripe-irxssauth:css/lock.css');
	}
	
	public function protect_site_from_indexing()
    {
		if ($this->owner->basicAuthEnabled) {
			if (defined('SS_USE_BASIC_AUTH') && SS_USE_BASIC_AUTH ) {
				return true;
			} else {
				$config = Config::forClass(IRXBasicAuth::class);
				$stagingDomains = $config->StagingDomainFeaturedStrings;
				$isStaging = false;
				
				foreach ($stagingDomains as $domain) {
					if (array_key_exists('HTTP_HOST', $_SERVER) && strpos( $_SERVER['HTTP_HOST'], $domain) !== false) $isStaging = true;
				}
				
				if ($config->staging_site_protected && $isStaging) {
					return true;
				}
				
			}
		}
		
		return false;
	}
}
