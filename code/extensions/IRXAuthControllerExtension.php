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
}
