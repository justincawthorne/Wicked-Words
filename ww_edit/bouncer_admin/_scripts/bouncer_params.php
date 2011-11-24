<?php
	
	session_name('BOUNCEADMINSESS'); // session name must be defined first!
	if (!session_id()) session_start();
	
	if(!defined('WW_ROOT')) {
		include_once('../ww_config/model_functions.php');		
	} else {
		include_once(WW_ROOT.'/ww_config/model_functions.php');
	}
	
	
// set path to bouncer folder

	$set_bounce_root 		= WW_ROOT."/ww_edit/bouncer_admin/"; // e.g. /home/path/on/server/to/bouncer/
	$set_bounce_html_root	= WW_REAL_WEB_ROOT."/ww_edit/bouncer_admin/"; // e.g. http://www.domain.com/path/to/bouncer/


// user defined settings

	$cipher 				= md5($config['site']['title'])	; // unique word for 'munging' cookies
	$stopsimlogin			= 0								; // set to 1 to prevent simultaneous logins (default is 0 - off)
	$test_mode				= 0								; // set to 1 to engage test mode (default is 0 - off)

// set up constants
	
	define('WW_SESS',		'ww_admin'					); // name of bouncer session/cookie - can be changed by the security conscious
	
	define('WW_SITE_NAME', 	$config['site']['title']	); // the name of your site
	define('WW_ADMIN_EMAIL', $config['admin']['email']	); // your email address for site support
	
	define('WW_USER_TBL', 	'authors'					); // mysql table containing user data
	
	define('WW_ID', 		'id'						); // user ID field
	define('WW_EMAIL', 		'email'						); // user email field
	define('WW_PASS', 		'pass'						); // user password field
	define('WW_SUB_EXPIRY', 'sub_expiry'				); // user subscription expiry
	define('WW_GUEST_FLAG', 'guest_flag'				); // user guest flag
	define('WW_GUEST_AREAS','guest_areas'				); // user guest areas
	define('WW_LAST_LOGIN',	'last_login'				); // user last login field
	define('WW_LAST_IP',	'last_ip'					); // user last IP field
	define('WW_LAST_SESS', 	'last_sess'					); // user last session field

/*-------------------------------------------------------------------------------------------------------*/
// you should not need to edit below this line

	include_once('bouncer_functions.php');

// get the server path to this file - use the __FILE__ constant to ensure the path remains, well, constant

	$bounce_root = clean_end_slash($set_bounce_root);
	define('WW_BOUNCE_ROOT',$bounce_root);
	
// check we got the root correct

	if(constant('WW_BOUNCE_ROOT')."/_scripts/bouncer_params.php" != __FILE__) {
		echo "WARNING: Bouncer configuration error:<br/>";
		echo "set_bounce_root needs to be manually configured in bouncer_params.php<br/>";
		exit();
	}
	
// now get the web path

	$bounce_html_root = clean_end_slash($set_bounce_html_root);	
	define('WW_BOUNCE_WEB_ROOT',$bounce_html_root);
	
// there's no way of checking the html_root automatically, but if css files aren't loading
// then that's a fairly good sign that things have gone awry...

	$bouncer_page = array();

// page locations - for redirecting users

	$bouncer_page['password_change'] 	= WW_REAL_WEB_ROOT.'/ww_edit/admin.php?changepass';	
	
	$bouncer_page['password_forgot'] 	= WW_REAL_WEB_ROOT.'/ww_edit/admin.php?forgotpass';
	if(isset($_POST['email'])) { $bouncer_page['password_forgot'] .= '&amp;email='.$_POST['email'];}

// optional pages - must be designed by user

	$bouncer_page['signup'] 		= '';
	$bouncer_page['renewal'] 		= '';
	
// user messages

	$bouncer_message = array();
	
	// general login prompt
	$bouncer_message['login_prompt'] = 
		"Please login to view this page.";
		
	// option to add a general message to all users
	$bouncer_message['general'] = 
		"";
	
	// prompt user to sign up
	$bouncer_message['subscribe_prompt'] = 
		(!empty($signup_page)) ? 
		"If are not currently a member of this site, you can <a href=\"".$bouncer_page['signup']."\">join here</a>." :
		"" ;
	
	// email doesn't match - user most likely isn't yet a member
	$bouncer_message['wrong_email'] = 'Sorry - the email address ';
	$bouncer_message['wrong_email'] .= (!empty($_POST['email'])) ? $_POST['email'] : '' ;
	$bouncer_message['wrong_email'] .= ' wasn\'t found. '.$bouncer_message['subscribe_prompt'];

	// password doesn't match - most likely mistyped
	$bouncer_message['wrong_password'] = 
		"Your password was entered incorrectly - please try again. 
		If you don't remember your password you may wish to <a href=\"".$bouncer_page['password_forgot']."\">reset it</a>.";
	
	// subscription has expired
	$bouncer_message['expired'] = 
		"Your subscription has expired. Please <a href=\"mailto:".constant('WW_ADMIN_EMAIL')."\">contact the administrator</a>.";

	// user has logged in elsewhere, or two users with same login
	$bouncer_message['sim_login'] = 
		"You appear to have logged in at a different location. 
		For security reasons you will be required to login again. 
		You may wish to <a href=\"".$bouncer_page['password_forgot']."\">change your password</a>.";
?>