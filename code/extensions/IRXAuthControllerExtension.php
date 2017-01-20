<?php
class IRXAuthControllerExtension extends DataExtension {
	
	function onBeforeInit() {
		if($this->owner->basicAuthEnabled){
			if( defined('SS_USE_BASIC_AUTH') && SS_USE_BASIC_AUTH ){
				IRXBasicAuth::protect_entire_site_if_necessary();
			}else{
				IRXBasicAuth::protect_staging_site_if_necessary();
			}
		}
	}
	
	public function protect_site_from_indexing() {
		if($this->owner->basicAuthEnabled){
			if( defined('SS_USE_BASIC_AUTH') && SS_USE_BASIC_AUTH ){
				return true;
			}else{
				
				$config = Config::inst()->forClass('IRXBasicAuth');
				$stagingDomains = $config->StagingDomainFeaturedStrings;
				$isStaging = false;
				
				foreach($stagingDomains as $domain){
					if( strpos( $_SERVER['HTTP_HOST'], $domain ) !== false) $isStaging = true;
				}
				
				if($config->staging_site_protected && $isStaging) {
					return true;
				}
				
			}
		}
		
		return false;
	}
}
