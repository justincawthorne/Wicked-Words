<?php
	if(!defined('WW_SESS')) {
		exit();
	}
	
	$cookie_name = 'author'.(int)$_SESSION[WW_SESS]['user_id'];
	
	setcookie($cookie_name, session_id(), 0, "/");

// page title - if undefined the site title is displayed by default

	$page_title = 'Write';


// confirm delete article

	if( (isset($_POST['confirm_delete_article'])) && ($_POST['confirm_delete_article'] == 'Yes') ) {
	
		$right_text = 'Deleting';
		
		$article_id = (isset($_POST['article_id'])) ? (int)$_POST['article_id'] : 0 ;
		
		if(!empty($article_id)) {
			$delete = delete_article($article_id);
			if(!empty($delete)) {
				header('Location: '.WW_WEB_ROOT.'/ww_edit/index.php?page_name=articles');
			} else {
				$error = $delete;
			}
		}	
	
	}
		
// cancel delete article
	
	if( (isset($_POST['cancel_delete_article'])) && ($_POST['cancel_delete_article'] == 'No') ) {

		$article_id = (int)$_POST['article_id'];
		header('Location: '.WW_WEB_ROOT.'/ww_edit/index.php?page_name=write&article_id='.$article_id);

	}

	
// validate post data or show form

	if( (isset($_POST['publish'])) || (isset($_POST['draft'])) ){
		
		$article_data = validate_article_post_data();
		
		$left_text = 'validating post';
		$right_text = 'posting error';
		
		// received inserted or updated flag
		
		if(isset($article_data['action'])) {
		
			$left_text = '';
			$right_text = 'article '.$article_data['action'];
			
		}
		
	} else {
		
		$article_data = get_article_form_data();

		// header status text
		
		if(!isset($_GET['article_id'])) {
			
			$left_text = 'new article';
			$right_text = 'no comments';
			
		} else {
			
			$right_text = ($article_data['comment_count'] == 1) 
				? $article_data['comment_count'].' comment' 
				:  $article_data['comment_count'].' comments';
			
		}			
	}			

	
// get text for article status
	
	$status_list = define_article_status();
	
	$status_text = $status_list[$article_data['status']];
	
	if($article_data['status'] == 'P') {
		$status_text = (strtotime($article_data['date_uploaded']) > time()) ? 'postdated' : 'published' ;
	}

	$left_text = (empty($left_text)) ? $status_text.' article' : $left_text ;


// show page header
	
	$page_header = show_page_header($left_text, $right_text);

	$main_content = $page_header;
	
	
// if an action parameter is set in the array then the article has been posted

	if(isset($article_data['action'])) {
		
	// default base text
		
		$main_content .= '
			<h2>Your article has been '.$article_data['action'].'</h2>
			<p><strong>Title:</strong> '.stripslashes($article_data['title']).'</p>';

			$main_content .= '
			<p>
				<a href="'.$_SERVER["PHP_SELF"].'?page_name=write&amp;article_id='.$article_data['id'].'">Edit your article</a>
			</p>';		
	
	// postdated article	
		
		if($status_text == 'postdated') {
			
			$main_content .= '
			<p>This article is postdated and will be available on the site from '.from_mysql_date($article_data['date_uploaded'],'d M Y \a\t H:i').' GMT</p>';

	// published or archived (i.e. still visible from main site
			
		} elseif( ($article_data['status'] == 'P') || ($article_data['status'] == 'A') ){
			
		// compile url
				
			if($config['layout']['url_style'] == 'blog') {
				$article_url = WW_REAL_WEB_ROOT.'/'.date('Y/m/d',strtotime($article_data['date_uploaded'])).'/'.$row['url'].'/';
			} else {
				// get category url
				$conn = author_connect();
				$query = "SELECT url FROM categories WHERE id = ".(int)($article_data['category_id']);
				$result = $conn->query($query);
				$row = $result->fetch_assoc();				
				$article_url = WW_REAL_WEB_ROOT.'/'.$row['url'].'/'.$article_data['url'].'/';
			}
				
			$action_text = ($article_data['action'] == 'updated') ? 'Updated' : 'New' ;
			
			$main_content .= '	
			<p>
				Review your article: <a href="'.$article_url.'">'.$article_url.'</a>
			</p>';
			
			// show twitter button
			
			if(!empty($config['connections']['twitter_username'])) {
				$main_content .= '
				<p><a href="http://twitter.com/share" 
					class="twitter-share-button" 
					data-url="'.$article_url.'" 
					data-text="'.$action_text.' article at '.$config['site']['title'].': '.stripslashes($article_data['title']).'" 
					data-count="horizontal" 
					data-via="'.$config['connections']['twitter_username'].'">Tweet</a></p>
				<script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>';				
			}
		
		} else {
			
			// draft article
			
			$main_content .= '	
			<p>This article is currently in '.$status_text.' status and is not visible from the main site.</p>';
			
		}

		
	} else {

		// if not then we display the form - with delete confirmation if requested

		if( (isset($_GET['action'])) && ($_GET['action'] == 'delete') ) {

			$main_content .= '
				<h2>Are you sure you want to delete this article?</h2>
				<form action="'.$action_url.'" method="post" name="confirm_article_attachment_form">
					<input name="article_id" type="hidden" value="'.(int)$article_data['id'].'"/>
					<input name="confirm_delete_article" type="submit" value="Yes"/>
					<input name="cancel_delete_article" type="submit" value="No" />
				</form>
				';
			
		}
		
		$main_content .= build_write_form($article_data, $config);
				
	}


// output aside content - into $aside_content variable

	$aside_content = '<iframe style="width: 200px;border: none;" 
						id="image_browser_frame" 
						height="1600"
						frameborder="1" 
						scrolling="auto"
						src="_content/image_browser.php?sess='.htmlspecialchars(session_id()).'&amp;author_id='.(int)$_SESSION[WW_SESS]['user_id'].'">
					</iframe>';

?>