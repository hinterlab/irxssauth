<?php
namespace Internetrix\IRXSSAuth\Security;

use SilverStripe\Control\Cookie;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Security\RandomGenerator;
use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;

/**
 * Provides an interface to HTTP basic authentication.
 *
 * This utility class can be used to secure any request with basic authentication.  To do so,
 * {@link IRXBasicAuth::requireLogin()} from your Controller's init() method or action handler method.
 *
 * It also has a function to protect your entire site.  See {@link IRXBasicAuth::protect_entire_site()}
 * for more information.
 *
 * @package framework
 * @subpackage security
 */
class IRXBasicAuth {
	/**
	 * @config
	 * @var Boolean Flag set by {@link self::protect_entire_site()}
	 */
	private static $entire_site_protected = false;
	
	/**
	 * @config
	 * @var Boolean Flag set by {@link self::staging_site_protected()}
	 */
	private static $staging_site_protected = false;

	/**
	 * @config
	 * @var String|array Holds a {@link Permission} code that is required
	 * when calling {@link protect_site_if_necessary()}. Set this value through
	 * {@link protect_entire_site()}.
	 */
	private static $entire_site_protected_code = 'ADMIN';
	
	/**
	 * @config
	 * @var String|array Holds a {@link Permission} code that is required
	 * when calling {@link protect_site_if_necessary()}. Set this value through
	 * {@link protect_entire_site()}.
	 */
	private static $staging_site_protected_code = 'ADMIN';

	/**
	 * @config
	 * @var String Message that shows in the authentication box.
	 * Set this value through {@link protect_entire_site()}.
	 */
	private static $entire_site_protected_message = "SilverStripe test website. Use your CMS login.";
	
	/**
	 * @config
	 * @var String Message that shows in the authentication box.
	 * Set this value through {@link protect_entire_site()}.
	 */
	private static $staging_site_protected_message = "SilverStripe test website. Use your CMS login.";
	
	private static $_already_tried_to_auto_log_in = false;

    /**
     * Require basic authentication.  Will request a username and password if none is given.
     *
     * Used by {@link Controller::init()}.
     *
     *
     * @param HTTPRequest $request
     * @param string $realm
     * @param string|array $permissionCode Optional
     * @param boolean $tryUsingSessionLogin If true, then the method with authenticate against the
     *  session log-in if those credentials are disabled.
     * @return bool|Member
     * @throws HTTPResponse_Exception
     */
    public static function requireLogin(
        HTTPRequest $request,
        $realm,
        $permissionCode = null,
        $tryUsingSessionLogin = true
    ) {
        if ((Director::is_cli() && static::config()->get('ignore_cli'))) {
            return true;
        }

        $renewAuthToken = false;
        $member = null;

        try {
            if ($request->getHeader('PHP_AUTH_USER') && $request->getHeader('PHP_AUTH_PW')) {
                /** @var MemberAuthenticator $authenticator */
                $authenticators = Security::singleton()->getApplicableAuthenticators(Authenticator::LOGIN);

                $member = $authenticator->authenticate([
                    'Email' => $request->getHeader('PHP_AUTH_USER'),
                    'Password' => $request->getHeader('PHP_AUTH_PW'),
                ], $request);
                if ($member instanceof Member && $member->ID) {
                    $renewAuthToken = true;
                }

            }
        } catch (DatabaseException $e) {
            // Database isn't ready, let people in
            return true;
        }

        if (!$member && $tryUsingSessionLogin) {
            $member = Security::getCurrentUser();
        }

        // If we've failed the authentication mechanism, then show the login form
        if (!$member) {
            $response = new HTTPResponse(null, 401);
            $response->addHeader('WWW-Authenticate', "Basic realm=\"$realm\"");

            if ($request->getHeader('PHP_AUTH_USER')) {
                $response->setBody(
                    _t(
                        'SilverStripe\\Security\\BasicAuth.ERRORNOTREC',
                        "That username / password isn't recognised"
                    )
                );
            } else {
                $response->setBody(
                    _t(
                        'SilverStripe\\Security\\BasicAuth.ENTERINFO',
                        'Please enter a username and password.'
                    )
                );
            }

            // Exception is caught by RequestHandler->handleRequest() and will halt further execution
            $e = new HTTPResponse_Exception(null, 401);
            $e->setResponse($response);
            throw $e;
        }

        if ($permissionCode && !Permission::checkMember($member->ID, $permissionCode)) {
            $response = new HTTPResponse(null, 401);
            $response->addHeader('WWW-Authenticate', "Basic realm=\"$realm\"");

            if ($request->getHeader('PHP_AUTH_USER')) {
                $response->setBody(
                    _t(
                        'SilverStripe\\Security\\BasicAuth.ERRORNOTADMIN',
                        'That user is not an administrator.'
                    )
                );
            }

            // Exception is caught by RequestHandler->handleRequest() and will halt further execution
            $e = new HTTPResponse_Exception(null, 401);
            $e->setResponse($response);
            throw $e;
        }

        $domain = array_key_exists('HTTP_HOST', $_SERVER) ? Convert::raw2sql($_SERVER['HTTP_HOST']) : '';

        if($renewAuthToken && $domain){
            $generator = new RandomGenerator();
            $token = $generator->randomToken('sha1');
            $hash = $member->encryptWithUserSettings($token);

            $authTokens = Convert::json2array($member->IRXSSAuthLoginToken);
            if(!$authTokens || !is_array($authTokens) || empty($authTokens)){
                $authTokens = array();
            }
            $authTokens[$domain] = $hash;

            $member->IRXSSAuthLoginToken = Convert::array2json($authTokens);
            $member->write();
            Cookie::set('isa_enc', $member->ID . ':' . $token, 7, null, null, null, true);
        }

        return $member;
    }

	/**
	 * Enable protection of the entire site with basic authentication.
	 *
	 * This log-in uses the Member database for authentication, but doesn't interfere with the
	 * regular log-in form. This can be useful for test sites, where you want to hide the site
	 * away from prying eyes, but still be able to test the regular log-in features of the site.
	 *
	 * If you are including conf/ConfigureFromEnv.php in your _config.php file, you can also enable
	 * this feature by adding this line to your _ss_environment.php:
	 *
	 * define('SS_USE_BASIC_AUTH', true);
	 *
	 * @param boolean $protect Set this to false to disable protection.
	 * @param String $code {@link Permission} code that is required from the user.
	 *  Defaults to "ADMIN". Set to NULL to just require a valid login, regardless
	 *  of the permission codes a user has.
	 */
	public static function protect_entire_site($protect = true, $code = 'ADMIN', $message = null) {
		Config::inst()->update('IRXBasicAuth', 'entire_site_protected', $protect);
		Config::inst()->update('IRXBasicAuth', 'entire_site_protected_code', $code);
		Config::inst()->update('IRXBasicAuth', 'entire_site_protected_message', $message);
	}
	
	/**
	 * Enable protection of the staging site with basic authentication.
	 *
	 * This log-in uses the Member database for authentication for staging sites, but doesn't interfere with the
	 * regular log-in form. This can be useful for staging sites, where you want to hide the site
	 * away from prying eyes, but still be able to allow live site visited normally.
	 *
	 * define('IRX_USE_STAGE_AUTH', true); is not set by default
	 *
	 * @param boolean $protect Set this to false to disable protection.
	 * @param String $code {@link Permission} code that is required from the user.
	 *  Defaults to "ADMIN". Set to NULL to just require a valid login, regardless
	 *  of the permission codes a user has.
	 */
	public static function protect_staging_site($protect = true, $code = 'ADMIN', $message = null) {
		Config::inst()->update('IRXBasicAuth', 'staging_site_protected', $protect);
		Config::inst()->update('IRXBasicAuth', 'staging_site_protected_code', $code);
		Config::inst()->update('IRXBasicAuth', 'staging_site_protected_message', $message);
	}

	/**
	 * Call {@link IRXBasicAuth::requireLogin()} if {@link IRXBasicAuth::protect_entire_site()} has been called.
	 * This is a helper function used by {@link Controller::init()}.
	 *
	 * If you want to enabled protection (rather than enforcing it),
	 * please use {@link protect_entire_site()}.
	 */
	public static function protect_entire_site_if_necessary() {
		$config = Config::forClass(IRXBasicAuth::class);
		if($config->entire_site_protected) {
			self::requireLogin($config->entire_site_protected_message, $config->entire_site_protected_code, false);
		}
	}
	
	/**
	 * Call {@link IRXBasicAuth::requireLogin()} if {@link IRXBasicAuth::protect_entire_site()} has been called.
	 * This is a helper function used by {@link Controller::init()}.
	 *
	 * If you want to enabled protection (rather than enforcing it),
	 * please use {@link protect_staging_site()}.
	 */
	public static function protect_staging_site_if_necessary() {
		
		if(!isset($_SERVER['HTTP_HOST']) || !$_SERVER['HTTP_HOST']){
			return false;
		}
		
		$config = Config::forClass(IRXBasicAuth::class);
		$stagingDomains = $config->StagingDomainFeaturedStrings;
		$isStaging = false;
		
		foreach($stagingDomains as $domain){
			if( array_key_exists('HTTP_HOST', $_SERVER) && strpos( $_SERVER['HTTP_HOST'], $domain ) !== false) $isStaging = true;
		}
		
		if($config->staging_site_protected && $isStaging) {
			self::requireLogin($config->staging_site_protected_message, $config->entire_site_protected_code, false);
		}
	}
	
	public static function autoAuth(){
		// Don't bother trying this multiple times
		self::$_already_tried_to_auto_log_in = true;
		
		if((defined('SS_USE_BASIC_AUTH') && SS_USE_BASIC_AUTH)
			|| strpos(Cookie::get('isa_enc'), ':') === false
			|| Session::get("loggedInAs")
			|| !Security::database_is_ready()
		) {
			return null;
		}

		list($uid, $token) = explode(':', Cookie::get('isa_enc'), 2);

		if (!$uid || !$token) {
			return null;
		}

		$member = DataObject::get_by_id("Member", $uid);

		// check if autologin token matches
		if($member) {
			$hash = $member->encryptWithUserSettings($token);
			$authTokens = Convert::json2array($member->IRXSSAuthLoginToken);
			$domain = array_key_exists('HTTP_HOST', $_SERVER) ? Convert::raw2sql($_SERVER['HTTP_HOST']) : '';
			
			if(!$authTokens || !is_array($authTokens) || empty($authTokens) || !$domain || !array_key_exists($domain, $authTokens) || $authTokens[$domain] !== $hash) {
				$member = null;
			}
		}
		
		return $member;
	}

}
