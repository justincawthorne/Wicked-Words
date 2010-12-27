<?php 

// get user defined settings - relative path
	include_once('_scripts/bouncer_params.php');
	include_once('_scripts/bouncer_functions.php');
	
/**
 * 
 * Rather than actively grant access, we just run through all the conditions in
 * which access is denied. If any one condition fails then the login form is shown.
 * This should avoid the need for complicated nested conditionals.
 * 
 * activate embedded login on protected pages as follows:
 * 	$embedded_login = 1; // delete this or set to 0 for conventional login
 * 	include('restrict.php');
 * 	
 *  to use conventional login simply omit the $embedded_login variable, or set to 0
 * 
 *  * to logout simply set a link to current page with URL parameter 'logout' set
 * e.g. http://www.mydomain.com/index.php?logout
 * 
*/

if (!session_id()) session_start();

// logout?

	if(isset($_GET['logout'])) {
		logout();
	}

// zero guest_access

	$guest_access = (!empty($guest_access)) ? $guest_access : "";
		
// deny access if login or user id session isn't set

	if ( (empty($_SESSION[WW_SESS]['logged_in'])) || (empty($_SESSION[WW_SESS]['user_id'])) ) {
		if(empty($embedded_login)) {
			include_once('_scripts/login.php');
			if(isset($_GET['forgotpass'])) {
				echo forgotpass_form();				// show forgotten password form
			} elseif(isset($_GET['changepass'])) {
				echo changepass_form();				// show change password form
			} else {
				echo login_form();					// show standard login form
			}
			exit;
		} else {
			// if embedded login is used we don't want to redirect page, or exit the script
			$hidecontent = 1;
			include_once('_scripts/login.php');
			return;
		}
	}

// deny access if subscription has expired

	/* 
	if subscription has expired but you still want to grant	someone access to a subscription renewal 
	page (for instance)	just add '$renewal = 1;' above 'include(restrict.php);'
	*/
	
	if( (empty($renewal)) && (!empty($_SESSION[WW_SESS]['expired']))  ) { // 
		$loginmessage = $expired_message;
		// reset cookies just in case
		setcookie("c_user",'', time()-1, "/");
		setcookie("c_key",'', time()-1, "/");
		if(empty($embedded_login)) {
			include_once('_scripts/login.php');
			echo login_form();
			exit;
		} else {
			$hidecontent = 1;
			include_once('_scripts/login.php');
			return;
		}
	}

// prevent simultaneous logins

	/*
	to prevent simultaneous logins, i.e. stopping two users logging in at the same time 
	with the same password set '$stopsimlogin=1' on the bouncer_params.php
	*/
/*	
	if(!empty($stopsimlogin)) {
		$userID = (int)$_SESSION[WW_SESS]['user_id'];
		$current_sess = session_id();
		mysql_select_db($dbase_name, $dbase_conn);
		$query_rsNoDupe = "SELECT ".WW_LAST_SESS." FROM ".WW_USER_TBL." WHERE ".WW_ID." = ".$userID;
		$rsNoDupe = @mysql_query($query_rsNoDupe, $dbase_conn) or die(mysql_error());
		$row_rsNoDupe = mysql_fetch_assoc($rsNoDupe);
		$totalRows_rsNoDupe = mysql_num_rows($rsNoDupe);
		$stored_sess = $row_rsNoDupe[AD_LST_SESS_FLD];
		if($stored_sess != $current_sess) {
			// get session id stored in database for user id
			// compare to session id saved in session
			// if no match then go to login form
			$loginmessage = $sim_login_message;
			// reset cookies
			setcookie("ad_c_user",'', time()-1, "/");
			setcookie("ad_c_key",'', time()-1, "/");
			if(empty($embedded_login)) {
				// if embedded login is used we don't want to redirect page, or exit the script
				include_once('_scripts/login.php');
				echo login_form();
				exit;
			} else {
				$hidecontent = 1;
				include_once('_scripts/login.php');
				return;
			}
		}
	}
*/	
?>