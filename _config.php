<?php

use Internetrix\IRXSSAuth\Security\IRXBasicAuth;

if( defined('IRX_USE_STAGE_AUTH') && IRX_USE_STAGE_AUTH ) {
	IRXBasicAuth::protect_staging_site();
}