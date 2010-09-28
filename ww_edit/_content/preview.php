<?php
	session_start();

	/*	check author session 
	
	 	the author id and session_id are sent via post params to the popup
	 	
		using the author id we derive the cookie name - then we can check if the 
		cookie value matches the session id that was sent via the form	 	
	*/
	
	$author_id = (isset($_POST['current_author_id'])) ? (int)$_POST['current_author_id'] : '' ;
	$sess_val = (isset($_POST['current_author_sess'])) ? htmlspecialchars_decode($_POST['current_author_sess']) : '' ;
	
	// exit if params not sent
		
	$params_check = ($author_id.$sess_val);

	if(empty($params_check)) {
	
		exit();			
	}

	$cookie_name 	= (!empty($author_id)) ? 'author'.$author_id : '' ;
	$cookie_val 	= (!empty($cookie_name)) ? $_COOKIE[$cookie_name] : '' ;

	// exit if cookie value is empty or session/cookie values don't match

	if( (empty($cookie_val)) || ($cookie_val != $sess_val) ) {
	
		exit();			
	}
	
	/*
		if we've gotten this far we're good to go on :)
	*/
	
// get root constants

	if(!defined('WW_ROOT')) {
		include_once('../../ww_config/model_functions.php');
	}
	
// now bring in our functions	
	
	include_once(WW_ROOT.'/ww_config/model_functions.php');
	include_once(WW_ROOT.'/ww_config/combined_functions.php');
	include_once(WW_ROOT.'/ww_config/controller_functions.php');
	include_once(WW_ROOT.'/ww_config/view_functions.php');	

// get article data
	
	// debug_array($_POST);
	
	$article = stripslashes_deep($_POST);
	
	$article['id'] = (isset($_POST['article_id'])) ? $_POST['article_id'] : 0 ;
	$article['url'] = (isset($_POST['url'])) ? $_POST['url'] : 'temp_url_title' ;
	$article['date_uploaded'] = (isset($_POST['date_uploaded'])) ? $_POST['date_uploaded'] : date('Y-m-d H:i:s') ;
	$article['date_ts'] = strtotime($article['date_uploaded']);
	
	// get author name
	$article['author_name'] = get_author_name($article['author_id']);
	$article['author_url'] 	= 'temp';
	
	// get category name
	$article['category_title'] 	= get_category_title($article['category_id']);
	$article['category_url'] 	= 'temp';
	
	// comments and other fields
	$article['comments_hide'] 	= 1;
	$article['comments_disable']= 1;
	$article['total_pages'] 	= 1;
	$article['pages'] 			= 1;
	$article['tags'] 			=  '' ;
	$article['attachments'] 	=  '' ;
	$article['comments'] 		=  '' ;
		
	// page name
	
	$_GET['page_name'] = 'article';
	
// get content partial - checking for theme versions as well
	
		$theme_content_folder = WW_ROOT.'/ww_view/themes'.$config['site']['theme'].'/_content';
		
		$content_partial = (file_exists($theme_content_folder.'/_article.php')) 
			? $theme_content_folder.'/_article.php'
			: WW_ROOT.'/ww_view/_content/_article.php';
			
		if(file_exists($theme_content_folder.'/_header.php')) {
		    ob_start();
			include($theme_content_folder.'/_header.php');
		    $body_content['header'] = ob_get_contents();
		    ob_end_clean();	
		}
		
	// nav content - below is just for example
	
		/* $body_content['nav'] = '<div id="nav">yeah, nav content</div>'; */

		if(file_exists($theme_content_folder.'/_nav.php')) {
		    ob_start();
			include($theme_content_folder.'/_nav.php');
		    $body_content['nav'] = ob_get_contents();
		    ob_end_clean();	
		}
		
	// footer content - below is just for example
		
		/* $body_content['footer'] = '<div id="footer">test footer</div>'; */
		
		if(file_exists($theme_content_folder.'/_footer.php')) {
			ob_start();
			include($theme_content_folder.'/_footer.php');
		    $body_content['footer'] = ob_get_contents();
		    ob_end_clean();	
		}
			
	// get aside content

		if(file_exists($theme_content_folder.'/_aside.php')) {
			ob_start();
			include($theme_content_folder.'/_aside.php');
		    $body_content['aside'] = ob_get_contents();
		    ob_end_clean();	
		}
	
		//$body_content['aside'] = insert_aside($aside_content);
		
	// buffer main content and insert into a variable

	    ob_start();
		include $content_partial;
	    $body_content['main'] = ob_get_contents();
	    ob_end_clean();	


	/*
		with a builder file we can use a different html structure
	*/
	
		if(file_exists($theme_content_folder.'/builder.php')) {
			
			include($theme_content_folder.'/builder.php');
			
		} else {

		// output default head section
		
			$head_content = '';
			show_head($head_content, $config);
		
		// output default body section
		
			show_body($body_content, $config);
			
		}
?>