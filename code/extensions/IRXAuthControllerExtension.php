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
		$config = Config::inst()->forClass('IRXBasicAuth');
		if($config->entire_site_protected) {
			self::requireLogin($config->entire_site_protected_message, $config->entire_site_protected_code, false);
		}
	}
}
