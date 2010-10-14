<?php
/*
	Copyright (C) 2010  Justin Cawthorne
	
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>
*/
/*
	error_reporting(E_ALL | E_STRICT);
	ini_set('display_errors', 1);
*/
if(!file_exists('../ww_config/model_functions.php')) {
	echo 'did you forget to set up your model_functions.php file? I think so...';
	exit();
}

// set page loading start time

	$start = time() + microtime();

// load functions

	include_once('../ww_config/model_functions.php');
	include_once('../ww_config/combined_functions.php');
	include_once('../ww_config/controller_functions.php');
	include_once('../ww_config/view_functions.php');

	if (!session_id()) session_start();
	
// set theme and check for includes

	if(empty($config)) {
		echo '
		<div style="margin:0 auto;width:440px;margin-top: 120px;text-align:center;">
		<p>it appears this is a new installation of Wicked Words</p>
		<h4>Well done you!</h4>
		<p>now go to the <a href="create.php">create page</a> to create your database tables...</p>
		</div>';
		exit();
	}
	
	if(!empty($config['admin']['shutdown'])) {
		header('HTTP/1.1 503 Service Temporarily Unavailable',true,503);
		header('Status: 503 Service Temporarily Unavailable');
		header('Retry-After: 172800');
		echo "
		".$config['site']['title']." is temporarily closed for maintenance...";
		exit();		
	}

// start caching
	
	if(!empty($config['cache']['caching_on'])) {
		$cache_fp = (!isset($_POST['submit_comment'])) ? start_caching($config['cache'], $start) : 0 ;
	}
	
// process url

	$request = process_request();
	
	$config['site']['theme'] = (isset($_SESSION['theme'])) ? $_SESSION['theme'] : $config['site']['theme'] ;

// check for includes

	$includes = get_includes($config['site']['theme']);
	if(!empty($includes)) {
		foreach($includes as $inc){
			include_once($inc);
		}
	}
	
// create a 'meta title' from the main site title (used for browser titlebar and <title> tag

// redirect as needed

	if(isset($_GET['page_name'])) {

	// rss feed - redirect to xml page
		
		if($_GET['page_name'] == 'feed') {

			include(WW_ROOT.'/ww_view/rss-xml.php');
			exit();
			
		}
		
	// front page
		
		if($_GET['page_name'] == 'front') {

			if(!empty($config['front']['article_id'])) {
				
				// this is a specific article, selected in the edit room
				
				$article = get_article($config['front']['article_id']);
				
			} else {
		
				// determine what content goes on front page, and which list style
				
				switch($config['front']['page_style']) {
					
					case 'custom':
					break;
					
					case 'latest_post':
					$article = get_article();
					break;
					
					case 'latest_month':
					
					// get latest article to check dates
					$latest_article = get_articles_basic($config['layout']['url_style'],0,0,1);

					// configure date parameters
					$latest_ts = strtotime($latest_article[0]['date_uploaded']);
					$_GET['month'] = date('m',$latest_ts);
					$_GET['year'] = date('Y',$latest_ts);

					// use front list style setting
					$config['layout']['list_style'] = $config['front']['front_list_style'];
					$articles = get_articles($config['layout']);
					break;
					
					case 'per_page':
					default:
					$config['layout']['list_style'] = $config['front']['front_list_style'];
					$articles = get_articles($config['layout']);
					break;
				}

			}
			
		}
	
	
	// 404 error
		
		if($_GET['page_name'] == '404') {
			
			// no content
			// we set up the meta tags and parse the request string on the partial page
			
		} 
	
	
	// single article
		
		if($_GET['page_name'] == 'article') {
			
			$article = get_article($_GET['article_id']);
	
		}
	
	
	// multiple articles - either a search or listings request
		
		if($_GET['page_name'] == 'listing') {
	
			if(isset($_GET['feed_listing'])) {
				
				// for a list of all available feeds we'll borrow the listing page
				$feeds = get_feeds($config['admin']['show_all_feeds']);
				$articles = format_feeds_list($feeds);
				
			} elseif(!empty($_GET['search'])) {
				
				// run a search
				$articles = search_articles($_GET['search'],$config['layout']);
				
			} else {
				
				// standard article listing
				$articles =  get_articles($config['layout']);
			}
		
		}


	// allowed pages array - redirect to 404 if any other page name is attempted
		
		$allowed_pages = array('feed','front','404','article','listing');
		
		$_GET['page_name'] = (in_array($_GET['page_name'],$allowed_pages)) ? $_GET['page_name'] : '404' ;


	// get content partial - checking for theme versions as well
	
		$theme_content_folder = WW_ROOT.'/ww_view/themes'.$config['site']['theme'].'/_content';
		
		$content_partial = (file_exists($theme_content_folder.'/_'.$_GET['page_name'].'.php')) 
			? $theme_content_folder.'/_'.$_GET['page_name'].'.php'
			: WW_ROOT.'/ww_view/_content/_'.$_GET['page_name'].'.php';

// at this point we could look for a custom 'builder' file in the theme folder

	
		/*	reference:
		$body_content['header'] = '';
		$body_content['nav'] = '';
		$body_content['main'] = '';
		$body_content['aside'] = '';
		$body_content['footer'] = '';
		*/
			
	// header content - below is just for example
	
		/* $body_content['header'] = '<div id="header">yeah, header</div>'; */
		
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

		/* $body_content['aside'] = insert_aside($aside_content); */

		if(file_exists($theme_content_folder.'/_aside.php')) {
			ob_start();
			include($theme_content_folder.'/_aside.php');
		    $body_content['aside'] = ob_get_contents();
		    ob_end_clean();	
		}
	
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
		
	} // end checking for $_GET['page_name']
	
// close caching routine - create file

	if(!empty($config['cache']['caching_on'])) {
		end_caching($cache_fp, $start);
	}
?>