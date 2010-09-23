<?php
// page title - if undefined the site title is displayed by default

	$page_title = 'comments';


// get any url parameters

	$article_id 	= (isset($_GET['article_id'])) ? (int)$_GET['article_id'] : 0 ;
	$author_id 		= (isset($_GET['author_id'])) ? (int)$_GET['author_id'] : 0 ;
	$comment_id 	= (isset($_GET['comment_id'])) ? (int)$_GET['comment_id'] : 0 ;
	$approved 		= ( (isset($_GET['approved'])) && (!empty($_GET['approved'])) ) ? 1 : 0 ;
	$ip_address 	= (isset($_GET['ip_address'])) ? $_GET['ip_address'] : 0 ;

	$is_contributor = ($_SESSION[WW_SESS]['level'] == 'contributor') ? 1 : 0 ;


// get articles list up here so we can use article titles

	$articles_list = (empty($is_contributor)) 
		? get_commented_articles() 
		: get_commented_articles($_SESSION[WW_SESS]['user_id']) ;
	
	$total_comments = 0;
	foreach($articles_list as $cnt) {
		$total_comments = $total_comments + $cnt['total'];
	}	

// process post events

		// post a new comment
		
		if( (isset($_POST['submit_comment'])) && ($_POST['submit_comment'] == 'Submit') ) {
			
			$error = insert_comment_admin();
			$initial_content = '<p>'.$error.'</p>';
			
		}
	
		// approve a comment
		
		if( (isset($_POST['approve_comment'])) && ($_POST['approve_comment'] == 'approve') ) {
		
			$mod_comment_id = (isset($_POST['comment_id'])) ? (int)$_POST['comment_id'] : 0 ;
			if(!empty($mod_comment_id)) {
				approve_comment($mod_comment_id);
			}	
		
		}
		
		// disapprove a comment
		
		if( (isset($_POST['disapprove_comment'])) && ($_POST['disapprove_comment'] == 'disapprove') ) {
		
			$mod_comment_id = (isset($_POST['comment_id'])) ? (int)$_POST['comment_id'] : 0 ;
			if(!empty($mod_comment_id)) {
				approve_comment($mod_comment_id, 1);
			}	
		
		}


// which main content are we displaying?

		if( (isset($_GET['action'])) && ($_GET['action'] == 'post') ) {
		
		// post a new comment to an article
			
			$right_text = 'Posting' ;
			$title = (!empty($article_id)) ? ' to '.$articles_list[$article_id]['title'] : '' ;
			$initial_content = '
					<h4>Posting a new comment'.$title.'</h4>'.
					show_comment_form_admin($article_id);
		
		} elseif( (isset($_GET['action'])) && ($_GET['action'] == 'reply') ) {
		
		// reply to an existing comment
			
			$right_text = 'Replying';
			
			$initial_content = '
					<h4>Replying to a comment</h4>
					<p>(n.b. the comment you\'re replying to is listed below)</p>'.
					show_comment_form_admin($article_id, $comment_id);			
			
		} elseif( (isset($_GET['action'])) && ($_GET['action'] == 'delete') ) {
		
		// deletion of a comment

			if( (isset($_POST['confirm_delete'])) && ($_POST['confirm_delete'] == 'Yes') ) {
			
				$right_text = 'Deleting';
				
				$del_comment_id = (isset($_POST['comment_id'])) ? (int)$_POST['comment_id'] : 0 ;
				
				if(!empty($del_comment_id)) {
					$delete = delete_comment($del_comment_id);
					$initial_content = ($delete == true) 
						? '<h4>The comment has been deleted</h4>' 
						: '<h4>The comment was not deleted</h4>' ;
		
				} else {
					$initial_content = '
							<h4>The comment was not deleted because an invalid id was sent</h4>';
				}	
			
			} else {
		
				$right_text = 'Delete';
				
				$initial_content = '
					<h2>Are you sure you want to delete this comment?</h2>
					<form action="'.current_url().'" method="post" name="confirm_delete">
						<input name="comment_id" type="hidden" value="'.(int)$comment_id.'"/>
						<input name="confirm_delete" type="submit" value="Yes"/>
						<input name="cancel_delete" type="button" value="No" onclick="javascript:history.back();"/>
					</form>
					';
					
			}
			
		} else {
			
		// list comments
		
			$right_text = 'Listing';
			
			// if an article id sent we show a link to post a comment to that article
			
			if(!empty($article_id)) {
				$initial_content = '
					<h4>Showing comments for article: '.$articles_list[$article_id]['title'].'</h4>
					<p><a href="'.$_SERVER["PHP_SELF"].'?page_name=comments&amp;article_id='.$article_id.'&amp;action=post">Post a new comment for this article?</a>
					</p>';
					
			} elseif( (isset($_GET['approved'])) && (empty($initial_content)) ) {
				
				$initial_content = (empty($_GET['approved'])) 
					? '<h4>Showing disapproved comments only</h4>'
					: '<h4>Showing approved comments only</h4>';
			
			} else {
				
				$initial_content = '<h4>Showing all comments</h4>';
				
			}
			
		}
		

// grab whichever comments we need to list
		
	$comments = (empty($is_contributor)) ? get_comments() : get_comments($_SESSION[WW_SESS]['user_id']) ;
	$total_found = (!empty($comments)) ? $comments[0]['total_found'] : 0 ;
	$total_pages = (!empty($comments)) ? $comments[0]['total_pages'] : 0 ;
		
	// per page - same definition as get_comments()
			
	$per_page = ( (!isset($_GET['per_page'])) || (empty($_GET['per_page'])) ) 
		? 15 
		: (int)$_GET['per_page'] ;


// get aside content

	// get list of comment stats and articles
	
	$comment_stats = (empty($is_contributor)) 
		? get_comments_stats()
		: get_comments_stats($_SESSION[WW_SESS]['user_id']);
		
	$new_comments  = (empty($is_contributor)) 
		? get_new_comments()
		: get_new_comments($_SESSION[WW_SESS]['user_id']);

// output main content - into $main_content variable

	$left_text = (isset($_GET['comment_id'])) ? '<a href="'.$_SERVER["PHP_SELF"].'?page_name=comments">Comments</a>' : 'Comments' ;

	$main_content = show_page_header($left_text, $right_text);;
	$main_content .= (isset($initial_content)) ? $initial_content : '' ;
	$main_content .= build_admin_comment_listing($comments);
	
	if($total_found > $per_page) {
		$main_content .=  show_admin_listing_nav($total_pages, $per_page);
	} 


// output aside content - into $aside_content variable

	$aside_content = '
			
			<h4><a href="'.$_SERVER["PHP_SELF"].'?page_name=comments&amp;action=post">post a comment</a></h4>
			<h4>Filter comments</h4>
			<div class="snippet">
			<ul>
			<li>
				<span class="list_item">
					<a href="'.$_SERVER["PHP_SELF"].'?page_name=comments">show all comments</a>
				</span>
				<span class="list_total">
					'.$total_comments.'
			</li>
			';
	if(!empty($new_comments)) {
		$aside_content .= '
			<li>
				<span class="list_item">
					<a href="'.$_SERVER["PHP_SELF"].'?page_name=comments&amp;new">show new comments</a>
				</span>
				<span class="list_total">
					'.count($new_comments).'
				</span>
			</li>
			';
	}
	$aside_content .= '
			</ul>
			</div>';
	
	// don't show status if all comments are approved or all comments are not approved
	
	if( (!empty($comment_stats['approved']['total'])) && (!empty($comment_stats['not approved']['total'])) ){
		$aside_content .= build_snippet('By status', $comment_stats);		
	}
	$aside_content .= build_snippet('By article', $articles_list);

?>