<?php
/**
 * pass through authentication file
 * 
 * for pass-through authentication (PTA) to work we need to recreate the cookie values needed for logging in
 * 
 * suggested procedure is to create any necessary scripts in bouncer_admin/pta
 * then pass the resulting email and password values to this page
 * 
 */ 

/* --- any includes and other top level code here ----------------------------------------------------*/

	error_reporting (E_ALL ^ E_NOTICE);
	
	include('pta/external_login.php');

/* --- logout and login calls here -------------------------------------------------------------------*/
	
	if(isset($_GET['logout'])) {
		
		// this calls the wicked words logout function (included below)
		bouncer_logout();		
		
		// now insert logout call for external pta function here
		$external_logout_script = '';
		header('Location: '.$external_logout_script);
		exit();
		
	} else { // if we're not logging out then call our external pta script
	
		/*
			what you want at the end of this section is the email and password value 
			to match up to the values stored in the ww_authors database table
		*/
		
		$login_params = array();
		
		// return the email and password - these are used in 
		// the pta function call at the bottom of the page
		
		$email		= $login_params['email'];
		$password	= $login_params['password'];	
	}

/* --- DO NOT ALTER BELOW THIS LINE --------------------------------------------------------------- */

/**
 * pta()
 * 
 * @param	$email		string	email (username)
 * @param	$password	string	password value
 * @global	$cipher		string	additional string (from bouncer_params.php for munging cookie value
 * 
 */ 

	function pta($email, $password) {
		// required variables
			global $cipher;
			$time = time() + 60*60*24*30;
			$c_key = md5($cipher.$password);
		// set cookie values
			setcookie("ad_c_key", $c_key, $time, "/");
			setcookie("ad_c_user", $email, $time, "/");
			print_r($_COOKIE['ad_c_user']);
			print_r($_COOKIE['ad_c_key']);
		// refresh in order to set cookies, then exit to prevent looping
			header('Location: '.$_SERVER["PHP_SELF"]."?".$_SERVER["QUERY_STRING"]);
			exit();
	}


/**
 * bouncer_logout()
 * 
 * this ensures that cookies and sessions created by the built in 'bouncer'
 * login functon are removed - this function should be called before any pta logout
 * function otherwise the user won't be fully logged out
 * 
 * this version of the inbuilt logout function mirrors the one included in
 * bouncer-functions.php, but omits the header redirect
 * 
 */
	
	function bouncer_logout() {
		// this replicates the logout function in bouncer_functions.php
		if (!session_id()) session_start();
		$_SESSION = array();
		$_COOKIE = array();
		unset($_SESSION[AD_SESS]['logged_in']);
		unset($_SESSION[AD_SESS]['guest']);
		unset($_SESSION[AD_SESS]['guest_areas']);
		unset($_SESSION[AD_SESS]['user_id']);
		session_regenerate_id();
		session_destroy();
		setcookie("ad_c_user",'', time()-3600, "/");
		setcookie("ad_c_key",'', time()-3600, "/");	
		unset($_COOKIE['ad_c_user']);
		unset($_COOKIE['ad_c_key']);		
	}

// this bit runs the actual pta function if cookie values aren't set

	if( (!isset($_COOKIE['ad_c_key'])) && (!isset($_COOKIE['ad_c_user'])) && (!isset($_GET['logout'])) ) {
		include_once('_scripts/bouncer_params.php');
		pta($email, $password);
	}
?>