<?php
/**
 * logout
 * 
 * function logs user out and clears all sessions and cookies
 * see restrict.php for usage instructions
 *
 */
	
	function logout() {
		if (!session_id()) session_start();
		unset($_SESSION[WW_SESS]['logged_in']);
		unset($_SESSION[WW_SESS]['guest']);
		unset($_SESSION[WW_SESS]['guest_areas']);
		unset($_SESSION[WW_SESS]['user_id']);
		session_regenerate_id();
		session_destroy();
		setcookie("ww_c_user",'', time()-1, "/");
		setcookie("ww_c_key",'', time()-1, "/");
		header('Location: '.WW_REAL_WEB_ROOT.'/ww_edit/index.php');
		exit;
	}


/**
 * verify_email
 * 
 * takes the posted email address and securely
 * checks it against emails currently stored in the database
 * returns true or false
 *
 * @param 	string 	$email
 * @return 	bool 	1/0
 */
	
	function bouncer_verify_email($email) {
		if(empty($email)) {
			return false;
		}
		$conn = author_connect();
		// grab existing list of email addresses
		$query = "SELECT ".WW_EMAIL." as emails
					FROM ".WW_USER_TBL;
		$result = $conn->query($query);
		$data = array();
		// create array of existing email addresses
		while($row = $result->fetch_assoc()) { 
			$data[] = strtolower($row['emails']);
		}
		$result->close();
		// check posted email address exists - if not bounce user immediately
		$email = strtolower($email);
		if(in_array($email, $data)) {
			return true;
		} else {
			// reset cookies
			setcookie('ww_c_user','', time()-1, "/");
			setcookie('ww_c_key','', time()-1, "/");
			//logout();
			// generate error message
			return false;
		}
	}


/**
 * send_password
 * 
 * takes a user's email address and sends them their password
 * for password reminder service
 *
 * @param 	string	$email
 * @return 	string	$message - error/success message
 */

	function forgotten_password($email) {
		// set new values for password and last_sess database fields
		$now 	= (int)time();
		$key 	= (int)rand(1000,9999);
		$sess	= $now * $key;
		$auth	= randomstring();
		// update database with key/sess
		$conn = author_connect();
		$update = "UPDATE ".WW_USER_TBL." 
					SET 
					".WW_LAST_SESS." = ".$sess.",
					".WW_PASS." = '".$conn->real_escape_string($auth)."'
					WHERE ".WW_EMAIL." = '".$conn->real_escape_string($email)."'";
		$update_result = $conn->query($update);
		if(!$update_result) {
			return $conn->error;
		}
		// compile email message
		$subject 	= WW_SITE_NAME." password reset";
		$url 		= WW_WEB_ROOT.'/ww_edit/index.php?changepass';
		$message 	= "Your password for the ".WW_SITE_NAME." website has been reset. To change your password please do the following:<br/><br/>";
		$message 	.= "1 - Go to ".$url."<br/><br/>";
		$message 	.= "2 - Enter this auth code:<br/>".$auth."<br/><br/>";
		$message 	.= "3 - Enter this key:<br/>".$key."<br/><br/>";
		$message 	.= "4 - Enter your new password<br/><br/>";
		$message 	.= "NOTE: this must be completed with ONE HOUR otherwise you will need to reset your password again.";
		$headers 	= 	"From: ".WW_ADMIN_EMAIL."\n".
					"X-Mailer: PHP/" . phpversion() . "\n" .
					"Content-Type: text/html; charset=utf-8\n" .
					"Content-Transfer-Encoding: 8bit\n\n";
		if(mail($email, $subject, $message, $headers, "-f".WW_ADMIN_EMAIL."")) { 
			$message = "Instructions for resetting your password have been sent to: ".$email.".";
		} else {
			$message = "There was a problem sending the email.";
		}
		return $message;
	}

/**
 * change_password
 * 
 */

	function change_password() {
		global $pre;
		$error = array();
		
		// check all fields are supplied
		if( (empty($_POST['auth'])) 
			|| (empty($_POST['key']))
			|| (empty($_POST['newpass'])) 
			|| (empty($_POST['confirmpass'])) ) {
			$error[] = '<p>All fields need to be filled in</p>';
		}
		$auth 		= trim($_POST['auth']);
		$key 		= trim($_POST['key']);
		$newpass 	= trim($_POST['newpass']);
		$confirmpass = trim($_POST['confirmpass']);

		// check entered passwords match
		$pass_len = strlen($newpass);
		if($pass_len < 8) {
			$error[] = '<p>Password needs to be at least 8 characters long</p>';
		}
		if($newpass != $confirmpass) {
			$error[] = '<p>Passwords don\'t match</p>';
		}
		
		// return errors if any
		if(!empty($error)) {
			$errors = implode(',',$error);
			return $errors;
		}
		
		// get database data for confirmation
		$conn = author_connect();
		$query = "
				SELECT 
					".WW_ID.", ".WW_EMAIL.", 
					".WW_PASS.", ".WW_LAST_SESS."
				FROM ".WW_USER_TBL." 
				WHERE ".WW_PASS." = '".$conn->real_escape_string($auth)."'";
		$result = $conn->query($query);
		$user_data = $result->fetch_assoc();
	
		// compare data - check auth code and time limit
		$limit = 3600;
		$passcheck = (strcmp($auth,$user_data[WW_PASS]) == 0) ? 1 : 0 ;
		if(empty($passcheck)) {
			$error[] = 'The auth code is incorrect';
		}
		$sess 		= $user_data[WW_LAST_SESS];
		$author_id 	= $user_data[WW_ID];
		$author_email = $user_data[WW_EMAIL];
		$time = $sess/$key;
		$time_now = time();
		if( ($time_now-$time) > $limit ) {
			$error[] = 'Time limit expired - password needs to be changed within one hour of reset';
		}
		
		// return errors if any
		if(!empty($error)) {
			$errors = implode(',',$error);
			return $errors;
		}
		
		// finally we creat the new password
		$len = 2 * (strlen($newpass));
		$salt 	= substr(md5(uniqid(rand(), true)), 0, $len);
		$hash_pass 	= $salt.hash("sha256",$salt.$newpass);
							
		// update database with new password
		$update = "UPDATE ".WW_USER_TBL." SET 
					".WW_PASS." = '".$conn->real_escape_string($hash_pass)."'
					WHERE ".WW_ID." = '".(int)$author_id."'";
		$update_result = $conn->query($update);
		if(!$update_result) {
			return $conn->error;
		}

		// email confirmation to user
		$subject = WW_SITE_NAME." - password changed";
		$message = "Your password for the ".WW_SITE_NAME." website (".WW_WEB_ROOT.") has been changed";
		$headers = 	"From: ".WW_ADMIN_EMAIL."\n".
					"X-Mailer: PHP/" . phpversion() . "\n" .
					"Content-Type: text/html; charset=utf-8\n" .
					"Content-Transfer-Encoding: 8bit\n\n";
		if(mail($author_email, $subject, $message, $headers, "-f".WW_ADMIN_EMAIL."")) {
			$loginmessage = "Your password has been changed.";
		} else {
		// message your password has been changed
			$loginmessage = "Your password has been changed. 
			Unfortunately we were unable to send a confirmation email.";
		}
		unset($_SESSION[WW_SESS]['logged_in']);
		return $loginmessage;
	}

/**
 * targetpage
 * 
 * used to redirect pages after form submit
 * 
 * 
 */

	function targetpage() {
		$target_page = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		$target_page = (substr($target_page,0,7) != "http://") ? "http://".$target_page : $target_page;
		return $target_page;	
	}

	
/**
 * randomstring
 * 
 * generates a random string fpr use when resetting a password
 * 
 * 
 */	

	function randomstring($length = 20) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$size = strlen( $chars );
		$str = '';
		for( $i = 0; $i < $length; $i++ ) {
			$str .= $chars[ rand( 0, $size - 1 ) ];
		}
		return $str;
	}

/** 
 * ---------------------------------------------------
 * FORMS and PAGE HTML
 * ----------------------------------------------------
 */	

/**
 * html_header
 * 
 * generates an html header section for bouncer pages
 * optional parameter gives a title to the page
 *
 * @param string $page_name
 * @return $header
 */

	function html_header($page_name = "Bouncer page") {
		
		$header = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
		 "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
			<head>
			<title>'.$page_name.' for '.WW_SITE_NAME.'</title>
			<link href="'.WW_BOUNCE_WEB_ROOT.'/_css/bouncer.css" rel="stylesheet" type="text/css" />
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
		if(stripos($_SERVER['HTTP_USER_AGENT'],"iphone")!== false) {
			$header .= '
			<meta name="viewport" content="width=device-width" />';
		}
		$header .= '
		</head>';
		// jump to email form field
		if($page_name = "Login Page") {
			$header .= '<body onload="document.forms[0].email.focus()">';	
		} else {
			$header .= '<body>';
		}
		$header .= '
		<div id="page">
		<div id="header">
			<h1>'.WW_SITE_NAME.'</h1>
		</div>';
		return $header;
	}


/**
 * html_footer
 * 
 * generates an html footer section for bouncer pages
 *
 * @return $footer
 */

	function html_footer() {
		$footer = '
		</div>
		<div id="footer">
			<p>Security provided by Bouncer :: an <a href="http://www.evilchicken.biz/">evil
		    chicken</a> production</p>
		</div>
		</body>
		</html>
		';
		return $footer;
	}

/**
 * mini_login
 * 
 * generates html for the embedded login form
 *
 * @return $mini-login - generated html
 */

	function mini_login() {
		
		global $bouncer_message;
		
		// html follows
		if(isset($bouncer_message['error'])) {
			$mini_login =  "
			<p>".$bouncer_message['error']."</p>";
		}
		$mini_login .= 
		'
		<form id="bouncer_miniloginform" method="post" action="'.targetpage().'">
			
			<p>
				<label for="email">Email address:</label>
				<input name="email" type="text" id="email" class="email" />
			</p>
			<p>
				<label for="pass">Password:</label>
				<input name="pass" id="pass" type="password" class="pass" />
			</p>
			<p><label for="remember">Remember Me:</label>
				<input name="remember" class="remember" type="checkbox" id="remember" />
				<input type="hidden" name="bounce" value="'.md5(WW_BOUNCE_WEB_ROOT).'" />
			</p>
			<p>
				<input type="submit" name="Submit" value="Login" />
			</p>
			<p>
				<a href="'.WW_BOUNCE_WEB_ROOT.'/pass_form.php?forgotpass">Forgotten your password? Click here.</a>
			</p>
			
		</form>';
		return $mini_login;
	}

/**
 * login_form
 * 
 * generates complete login form page
 *
 * @return $login_form - complete html code
 */
	
	function login_form() {
		
		global $bouncer_message;
		global $bouncer_page;

		// put together html
		$login_form = 
			html_header("Login Page").
			'<div id="content">';
			// error messages
			if(isset($bouncer_message['error'])) {
				$login_form .=  '
				<div id="login_message">'.$bouncer_message['error'].'</div>';
			}	
			// any general messages
			if(!empty($bouncer_message['general'])) {
				$login_form .=  "
				<div id=\"general_message\">".$bouncer_message['general']."</div>";
			}
			// login form itself	
			$login_form .=  '			
				<h2>Please enter your email address and password to login</h2>
				
				<form id="bouncer_loginform" class="bouncer_form" method="post" action="'.targetpage().'">
				
					<p><label for="email">Email address:</label><br/>
						<input name="email" type="email" id="email" /></p>
					
					<p><label for="pass">Password:</label><br/>
						<input name="pass" id="pass" type="password" /></p>
					
					<p><label for="remember">Remember Me:</label>
						<input name="remember" type="checkbox" id="remember" /></p>
					
					<p><input type="hidden" name="bounce" value="'.md5(WW_BOUNCE_WEB_ROOT).'" />
						<input type="submit" name="login" value="Login" /></p>
				
				</form>
				
				<h2>
				<a href="'.$bouncer_page['password_forgot'].'">Forgotten your password?</a>
				</h2>
		
			</div>
			'.html_footer();
		return $login_form;
	}
	

/**
 * forgotpass_form
 * 
 * generates complete login form page
 *
 * @return $login_form - complete html code
 */
	
	function forgotpass_form() {
		
		global $bouncer_message;
		global $bouncer_page;

		$email = (isset($_GET['email'])) ? $_GET['email'] : '' ;

		// put together html
		$login_form = html_header("Forgotten Password").'
			<div id="content">';
			// error messages
			if(isset($bouncer_message['error'])) {
				$login_form .=  '
				<div id="login_message">'.$bouncer_message['error'].'</div>';
			}	
			$login_form .=  '			
				<h2>Please enter your email address - details for changing your password will be emailed to you</h2>
				
				<form id="bouncer_forgotpassform" class="bouncer_form" method="post" action="'.targetpage().'">
				
					<p><label for="email">Email address:</label><br/>
						<input name="email" type="email" value="'.$email.'" /></p>
					
					<p><input type="hidden" name="bounce" value="'.md5(WW_BOUNCE_WEB_ROOT).'" />
						<input type="submit" name="forgotpass" value="Submit" /></p>
						
				</form>
				
				<h2>
					After receiving your email you will be able to <a href="'.$bouncer_page['password_change'].'">change your password</a>
				</h2>
		
			</div>
		'.html_footer();
		return $login_form;
	}

/**
 * changepass_form
 * 
 */
	
	function changepass_form() {
		
		global $bouncer_message;

		$email 	= (isset($_GET['email'])) ? $_GET['email'] : '' ;
		$auth 	= (isset($_GET['auth'])) ? $_GET['auth'] : '' ;
		$key 	= (isset($_GET['key'])) ? $_GET['key'] : '' ;

		// put together html
		$login_form = html_header("Change Password").'
			<div id="content">';
			// error messages
			if(isset($bouncer_message['error'])) {
				$login_form .=  '
				<div id="login_message">'.$bouncer_message['error'].'</div>';
			}	
			$login_form .=  '			
				<h2>Please enter your the auth code and key from your email</h2>
				
				<form id="bouncer_changepassform" class="bouncer_form" method="post" action="'.targetpage().'">
				
					<p><label for="auth">Auth code:</label><br/>
						<input name="auth" type="text" value="'.$auth.'" /></p>
						
					<p><label for="key">Key:</label><br/>
						<input name="key" type="text" value="'.$key.'" /></p>
					
					<p>Now enter your new password (needs to be at least 8 characters long)</p>
					
					<p><label for="newpass">New Password:</label><br/>
						<input name="newpass" id="newpass" type="password" /></p>
						
					<p><label for="confirmpass">Confirm Password:</label><br/>
						<input name="confirmpass" id="confirmpass" type="password" /></p>
					
					<p><input type="hidden" name="bounce" value="'.md5(WW_BOUNCE_WEB_ROOT).'" />
						<input type="submit" name="changepass" value="Submit" /></p>
				
				</form>
				
				<h2>Go to <a href="'.$_SERVER["PHP_SELF"].'">login page</a></h2>
		
			</div>
		'.html_footer();
		return $login_form;
	}
?>