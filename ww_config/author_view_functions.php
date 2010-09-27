<?php
/**
 * author view functions
 * 
 * @package wickedwords
 * 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License version 3
 */

/**
 * -----------------------------------------------------------------------------
 * PAGE STRUCTURE
 * -----------------------------------------------------------------------------
 */


/**
 * show_admin_head
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 */

function show_admin_head($site_title, $page_title = '', $theme = 'desktop') {
	$css_href = WW_WEB_ROOT.'/ww_edit/themes/'.$theme.'/';
	$meta_title = (!empty($page_title)) ? $page_title.' - '.$site_title.' edit room' : $site_title.' - edit room';
// doctype
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>'.$meta_title.'</title>
		<meta http-equiv="Content-type" content="text/html;charset=UTF-8"/>
	';

// jquery & miscellaneous javascript functions
	echo '
	<script type="text/javascript" src="'.WW_WEB_ROOT.'/ww_edit/_js/jquery.js"></script>
	<script type="text/javascript" src="'.WW_WEB_ROOT.'/ww_edit/_js/jquery-ui.js"></script>
	<script type="text/javascript" src="'.WW_WEB_ROOT.'/ww_edit/_functions/misc_functions.js"></script>
	';

// include tiny mce script and configuration

	if( (isset($_GET['page_name'])) && ($_GET['page_name'] == 'write') ) {
		echo '
		<script type="text/javascript" src="'.WW_WEB_ROOT.'/ww_edit/_js/tiny_mce/tiny_mce.js"></script>';
		// configuration options for tinyMCE
		include('_snippets/tinymce_config.php'); 
	}
	
// grab specific js for any other pages
	
	// prevents a 'not defined' error on the front admin page
	$_GET['page_name'] = (!isset($_GET['page_name'])) ? 'front' : $_GET['page_name'] ;
	
	if(file_exists(WW_ROOT.'/ww_edit/_js/'.$_GET['page_name'].'.js')) {
		echo '
		<script type="text/javascript" src="'.WW_WEB_ROOT.'/ww_edit/_js/'.$_GET['page_name'].'.js"></script>
		';
	}


// include css files
	echo '
	<link href="'.$css_href.'structure.css" rel="stylesheet" media="screen" type="text/css" />
	<link href="'.$css_href.'style.css" rel="stylesheet" media="screen" type="text/css" />
	<!--[if IE 7]>
	<link href="'.$css_href.'ie7.css" rel="stylesheet" media="screen" type="text/css" />
	<![endif]-->
	<!--[if lt IE 7]>
	<link href="'.$css_href.'ie6.css" rel="stylesheet" media="screen" type="text/css" />
	<![endif]-->
	';

// smartphone user
	if(detect_smartphone() == true) {
		
	echo '
	<link href="'.$css_href.'iphone.css" rel="stylesheet" media="screen" type="text/css" />
	';
	}

	echo '
	</head>';
}

/**
 * show_admin_page_header
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function show_admin_page_header($site_title) {
		echo '
		<body>
		<div id="outer_wrapper">
		<div id="inner_wrapper">
		
		<!-- header section -->
		
		<div id="header">
		
		<div id="header_content">
		
			<div id="header_text">
				<p>
					<a href="index.php" title="edit room home page">Wicked Words Edit Room</a>
				</p>
				<small><a href="'.WW_WEB_ROOT.'" title="click here to view your site">'.$site_title.'</a></small>
			</div>
		
			<div id="header_panel">
				<p>you\'re using <a href="http://www.evilchicken.biz">wicked words</a></p>
				<p>PHP/local time: '.date('d/m/y H:i').'</p>
				<p>MySQL/server time: '.date('d/m/y H:i',strtotime(get_mysql_time())).'</p>';
			// show additional timezone info if we're not in GMT
			if(date('T') != 'GMT') {
				echo '
				<div class="gmt_info">
				<p>'.date('T').' &#124; offset from GMT: '.date('O').'</p>
				<p>GMT: '.gmdate('d/m/y H:i').'</p>
				</div>';
			}
		echo '			
			</div>
			
		</div>
		
		</div>';
		
		// nav
		
		echo '
		<!-- nav section -->
		
		<div id="nav">
			<ul id="nav_links">
				<li><a href="'.WW_REAL_WEB_ROOT.'/ww_edit/index.php?page_name=write">Write!</a></li>
				<li><a href="'.WW_REAL_WEB_ROOT.'/ww_edit/index.php?page_name=articles">Articles</a></li>
				<li><a href="'.WW_REAL_WEB_ROOT.'/ww_edit/index.php?page_name=comments">Comments</a></li>
				';
		
		if(empty($_SESSION[WW_SESS]['guest'])) { 
		// author only links	
			echo '
				<li><a href="'.WW_REAL_WEB_ROOT.'/ww_edit/index.php?page_name=files">Files</a></li>
				<li><a href="'.WW_REAL_WEB_ROOT.'/ww_edit/index.php?page_name=links">Links</a></li>
				<li><a href="'.WW_REAL_WEB_ROOT.'/ww_edit/index.php?page_name=settings">Settings</a></li>';
		} else {
		// editor / contributor links
			echo '
				<li><a href="'.WW_REAL_WEB_ROOT.'/ww_edit/index.php?page_name=images">Images</a></li>
				<li><a href="'.WW_REAL_WEB_ROOT.'/ww_edit/index.php?page_name=attachments">Attachments</a></li>';
		} 
		echo '
			</ul>
		</div>';
		
		// start main content section
		echo '
		<!-- content wrapper section -->
		
		<div id="content_wrapper">';
	}

/**
 * build_admin_main
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function insert_main_content_admin($html_content = '') {
		echo '
		<!-- main content section -->
		
		<div id="main_content">
			'.$html_content.'
		</div>';
	}

/**
 * build_admin_aside
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function insert_aside_admin($html_content = '') {
		echo '
		<!-- aside section -->
		
		<div id="aside">
			'.$html_content.'
		</div>';
	}

/**
 * show_page_header
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_page_header($left_text, $right_text) {
		$html = '
		<!-- page header -->
		<div id="page_header">
			<span class="header_left">'.$left_text.'</span>
			<span class="header_right">'.$right_text.'</span>
		</div>
		';
		return $html;

	}

/**
 * show_admin_page_footer
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_admin_page_footer() {
		// close main content, inner, outer wrappers
		
		echo '
		</div>
		</div>
		</div>';
		
		// show footer
		echo '
		<!-- footer section -->
		
		<div id="footer"> 
			<p>logged in as: <strong>'.$_SESSION[WW_SESS]['name'].'</strong> 
		  &#124; email: <strong>'.$_SESSION[WW_SESS]['email'].'</strong> 
		  &#124; previous login: <strong>'.date('d/m/y H:i:s' ,strtotime($_SESSION[WW_SESS]['last_login'])).'</strong> 
		  &#124; <strong>'.$_SESSION[WW_SESS]['level'].'</strong>
		  &#124; <strong><a href="'.$_SERVER['PHP_SELF'].'?logout">logout</a></strong></p>
		</div>';
		
		// close page
		echo '
		</body>
	</html>';
	}

/**
 * -----------------------------------------------------------------------------
 * ARTICLE LISTING
 * -----------------------------------------------------------------------------
 */


/**
 * build_admin_article_listing
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function build_admin_article_listing($articles) {
		if(empty($articles)) {
			$html = '<p>No articles found</p>';
			return false;
		}
		$html = '<ul class="article_listing">';
		foreach($articles as $article) {
			$comments = (!empty($article['comment_count'])) 
				? '<a href="'.$_SERVER["PHP_SELF"].'?page_name=comments&amp;article_id='.$article['id'].'">
					'.$article['comment_count'].'</a>' 
				: $article['comment_count'] ;
			$html .= '
			<li class="'.$article['style'].'">
				<div class="article_title">
					<a href="'.$_SERVER["PHP_SELF"].'?page_name=write&amp;article_id='.$article['id'].'">
					'.$article['title'].'</a>
				</div>
				<div class="article_author">
					<a href="'.$_SERVER["PHP_SELF"].'?page_name=articles&amp;author_id='.$article['author_id'].'">
					'.$article['author_name'].'</a>
				</div>
				<div class="article_category">
					<a href="'.$_SERVER["PHP_SELF"].'?page_name=articles&amp;category_id='.$article['category_id'].'">
					'.$article['category_title'].'</a>
				</div>
				<div class="article_views">
					views: '.$article['view_count'].'
					</div>
				<div class="article_visits">
					visits: '.$article['visit_count'].'
				</div>
				<div class="article_comments">
					comments: '.$comments.'
				</div>
				<div class="article_date">
					'.from_mysql_date($article['date_uploaded']).'
				</div>
				<div class="article_delete">
					<a href="'.$_SERVER["PHP_SELF"].'?page_name=write&amp;action=delete&amp;article_id='.$article['id'].'">
					delete</a>
				</div>
			</li>';
		}
		$html .= '</ul>';
		return $html;		
	}

/**
 * show_admin_listing_nav
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_admin_listing_nav($total, $per_page) {
		// page params
		$current_page 	= (empty($_GET['page'])) ? '1' : (int)$_GET['page'] ;
		$previous_page	= ($current_page > 1) ? $current_page-1 : 0 ;
		$next_page		= ($current_page < $total) ? $current_page+1 : 0 ;
		// get url and strip page number if needed
		$url = current_url();
		$pattern = "%&page=[0-9]+%";
		$url = preg_replace($pattern,'',$url);
		// build html
		$html = '
		<!-- listing navigation -->
		
		<div id="page_nav" class="listing_page_nav">';
		if(!empty($previous_page)) { 
			$html .= '
				<span id="previous">
					<a href="'.$url.'&amp;page='.$previous_page.'" title="go to previous page">previous</a>
				</span>'; 
		}
		if(!empty($next_page)) { 
			$html .= '
				<span id="next">
					<a href="'.$url.'&amp;page='.$next_page.'" title="go to next page">next</a>
				</span>'; 
		}		
		$html .= '
				<ul>';
		for($pp = 1; $pp <= $total; ++$pp) {
			$html .=  '
					<li><a href="'.$url.'&amp;page='.$pp.'" title="go to page '.$pp.'">'.$pp.'</a></li>';
		}
		$html .= '
				</ul>';

		$html .= '
		</div>'."\n";
		return $html;
	}

/**
 * -----------------------------------------------------------------------------
 * COMMENT LISTING
 * -----------------------------------------------------------------------------
 */


/**
 * build_admin_comment_listing
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function build_admin_comment_listing($comments) {
		if(empty($comments)) {
			$html = '<p>No comments found</p>';
			return false;
		}
		$html = '<ul class="comment_listing">';
		foreach($comments as $comment) {
			$style = (!empty($comment['author_id'])) ? 'author_comment' : '' ;
			$style = ($comment['date_uploaded'] > $_SESSION[WW_SESS]['last_login']) ? $style.' new_comment' : $style ;
			$style = (empty($comment['approved'])) ? $style.' disapproved' : $style ;
			$html .= '
			<li class="'.$style.'">
				<div class="comment_details">
					<span class="date_uploaded">
						'.from_mysql_date($comment['date_uploaded'],'d M Y H:i').'
					</span>
					<span class="poster_name">
						'.$comment['poster_name'].'
					</span>';
				// poster link if provided
				if(!empty($comment['poster_link'])) {	
					$html .= '
					<span class="poster_link">
						<a href="'.$comment['poster_link'].'" title="'.$comment['poster_link'].'">
							website
						</a>
					</span>';
				}
				if(!empty($comment['poster_email'])) {	
					$html .= '
					<span class="poster_email">
						<a href="mailto:'.$comment['poster_email'].'" title="'.$comment['poster_email'].'">
							email
						</a>
					</span>';
				}
				$html .= '
					<span class="poster_email">
						<a href="'.$_SERVER["PHP_SELF"].'?page_name=comments&amp;ip='.$comment['poster_IP'].'">
						'.$comment['poster_IP'].'
						</a>
					</span>
					
					<span class="article_title">
						<a href="'.$_SERVER["PHP_SELF"].'?page_name=comments&amp;article_id='.$comment['article_id'].'">
						'.$comment['article_title'].'
						</a>
					</span>
				</div>
				<div class="comment_text">';
				if(!empty($comment['title'])) {
					$html .= '
					<p><strong>'.$comment['title'].'</strong></p>';
				}
				$html .= nl2br($comment['body']).'
				</div>
				<div class="comment_actions">
					
					<form action="'.current_url().'" method="post" id="comment_actions_'.$comment['id'].'">
					<input name="comment_id" type="hidden" value="'.$comment['id'].'"/>';
					$html .= (empty($comment['approved'])) 
						? '<input name="approve_comment" type="submit" value="approve"/>' 
						: '<input name="disapprove_comment" type="submit" value="disapprove"/>' ;
					$html .= '</form>';
					
					if( (empty($comment['reply_id'])) && (!empty($comment['approved'])) ) {
						$html .= '
						<span class="reply_link">
							<a href="'.$_SERVER["PHP_SELF"].'?page_name=comments&amp;article_id='.$comment['article_id'].'&amp;comment_id='.$comment['id'].'&amp;action=reply">
							reply
							</a>
						</span>';
					}
					$html .= '
					<span class="delete_link">
						<a href="'.$_SERVER["PHP_SELF"].'?page_name=comments&amp;comment_id='.$comment['id'].'&amp;action=delete">
						delete
						</a>
					</span>
				</div>
			</li>';
		}
		$html .= '</ul>';
		return $html;		
	}

/**
 * build_write_form
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function show_comment_form_admin($article_id = 0, $reply_id = 0) {
		
		// validate params
		$article_id = (isset($article_id)) ? (int)$article_id : 0 ;
		$reply_id = (isset($reply_id)) ? (int)$reply_id : 0 ;
		$comment_body = (isset($_POST['body'])) ? $_POST['body'] : '' ;
		
		// work out how to display article_id elements on the form
		if( (!empty($reply_id)) && (!empty($article_id)) ) {
			// hard code the parameters into the form
			$article_chunk = '
			<input name="article_id" id="article_id" value="'.$article_id.'" type="hidden">			
			';
		} else {
			$articles = $articles = get_articles_basic('','','date_uploaded DESC', 0);
			$article_chunk = '
			<label for="article_id">Article</label>
			<select name="article_id">
				<option value="0">select article...</option>';
			foreach($articles as $article) {
				$selected = ($article['id'] == $article_id) ? ' selected="selected"' : '' ; 
				$article_chunk .= '
				<option value="'.$article['id'].'"'.$selected.'>'.$article['title'].'</option>
				';				
			}
			$article_chunk .= '
			</select></p>
			<p>';
		}
		
		// start building main form
		$html = '
		<form action="'.current_url().'" method="post" id="comment_form">
		
		<p><label for="body">Comment</label>
			<textarea name="body" title="your comment" id="body" cols="32" rows="8">'.$comment_body.'</textarea></p>
		<p>	
			'.$article_chunk.'
			<input name="reply_id" id="reply_id" value="'.$reply_id.'" type="hidden">
			<input name="title" id="title" value="" type="hidden">
			<input name="submit_comment" id="submit_comment" value="Submit" type="submit">
		</p>
		</form>
		';
		return $html;
	}

/**
 * -----------------------------------------------------------------------------
 * MAIN ARTICLE FORM
 * -----------------------------------------------------------------------------
 */

/**
 * build_write_form
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function build_write_form($article_data, $config) {
		// construct link
		if(!empty($article_data['id'])) {
			$article_data['link'] = ($config['layout']['url_style'] == 'blog') 
				? WW_REAL_WEB_ROOT.'/'.date('Y/m/d',strtotime($article_data['date_uploaded'])).'/'.$article_data['url'].'/'
				: WW_REAL_WEB_ROOT.'/'.$article_data['category_url'].'/'.$article_data['url'].'/';
		}
		// start tabs
		$attachments_total = count($article_data['attachments']);
		$form = '
				<div id="article_tabs">
				<ul>
					<li><a href="#tab_article">Article</a></li>
					<li><a href="#tab_attachments">Attachments ('.$attachments_total.')</a></li>
					<li><a href="#tab_seo">SEO</a></li>
					<li><a href="#tab_comments">Comments</a></li>
					<li><a href="#tab_edits">Edits</a></li>
				</ul>';
		// start form
		$form .= '
				<form method="post" enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'" id="write_article">';
		// message window - for errors and autosave status
		$form .= '
				<div id="messages">
					<span id="last_autosave"></span>';
		// errors
		if(!empty($article_data['error'])) {
			$form .= '
					<ul id="errors">
						<li>The following errors prevented your article from being saved:</li>
						<li>'.implode('</li><li>',$article_data['error']).'</li>
					</ul>
			';
		}
		$form .= '
				</div>';

		// output form		
		$form .= form_article_tab($article_data);
		$form .= form_attachments_tab($article_data, $config);
		$form .= form_seo_tab($article_data);
		$form .= form_comments_tab($article_data, $config);
		$form .= form_edits_tab($article_data);
		// submit buttons
		$form .= '
				<p>
					<input name="current_author_sess" type="hidden" value="'.htmlspecialchars(session_id()).'"/>
					<input name="current_author_id" type="hidden" value="'.(int)$_SESSION[WW_SESS]['user_id'].'"/>
					<span class="note">
					<input type="button" name="preview" value="Preview" />
				';
		// only show draft option if article hasn't been published
		if(isset($_GET['article_id'])) {
			$form .= '
					<input type="hidden" name="article_id" value="'.$_GET['article_id'].'"/>';
		}
		if($article_data['status'] == 'D') {
			$form .= '		
					<input type="submit" name="draft" value="Save As Draft" />
					<input type="submit" name="publish" value="Publish Article" />';
		} else {
			$form .= '		
					<input type="submit" name="publish" value="Update Article" />';
		}
		$form .= '
					</span>
				</p>
				</form>
				</div>';
		return $form;
	}

/**
 * form_article_tab
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function form_article_tab($article_data) {
		
		// link outout
		if(isset($article_data['link'])) {
			$link = '<p><span class="note">'.$article_data['link'].'</span></p>';
		} else {
			$link = '';
		}
		
		// authors
		if( (empty($_SESSION[WW_SESS]['guest'])) || ($_SESSION[WW_SESS]['level'] == 'editor') ) {
			
			$author_list = get_authors_admin();
			$author_select = '
				<select name="author_id" title="select author">
				';
			foreach($author_list as $author) {
				$a_selected = ($article_data['author_id'] == $author['id']) ? ' selected="selected"' : '' ;
				$author_select .= '
					<option value="'.$author['id'].'"'.$a_selected.'>'.$author['title'].'</option>
				';
			}
			$author_select .= '				
				</select>';
				
		} else {
			
			$author_select = '
				<input type="hidden" name="author_id" value="'.$_SESSION[WW_SESS]['user_id'].'"/>
				<input type="text" name="author_name" value="'.$_SESSION[WW_SESS]['name'].'" readonly="readonly"/>';
		}
		
		// categories
		$category_list = get_categories_admin();
		$category_select = '
				<select name="category_id">
				';
			foreach($category_list as $category) {
				$c_selected = ($article_data['category_id'] == $category['id']) ? ' selected="selected"' : '' ;
				$category_select .= '
					<option value="'.$category['id'].'"'.$c_selected.'>'.$category['title'].'</option>
				';
				// nested items
				if(isset($category['child'])) {
					foreach($category['child'] as $child) {
						$c_ch_selected = ($article_data['category_id'] == $child['id']) ? ' selected="selected"' : '' ;
						$category_select .= '
							<option value="'.$child['id'].'"'.$c_ch_selected.'> ... '.$child['title'].'</option>
						';						
					}
				}
				// end nested items
			}
		$category_select .= '				
				</select>';
		
		// tags
		$tag_checkboxes = '';
		$tags_list = get_tags_admin();
		foreach($tags_list as $tag_id => $tag) {
			$tag_checked = (isset($article_data['tags'])) && (in_array($tag_id, $article_data['tags'])) ? ' checked="checked"' : '' ;
			$tag_checkboxes .= '
				<li>
					<label for="tags['.$tag_id.']">
						<input type="checkbox" id="tags['.$tag_id.']" name="tags['.$tag_id.']" value="'.$tag_id.'"'.$tag_checked.'/>'.$tag['title'].'
					</label>
				</li>';
		}
		
		// build form
		$html = '
			<div id="tab_article">

					'.$link.'
				<p>
					<label for="title">Title</label>
					<input type="text" name="title" title="type article title" value="'.$article_data['title'].'" />
				</p>
				<p>
					<label for="summary">Summary/Intro</label>
					<textarea name="summary" id="summary" title="type article summary" cols="40" rows="4">'.$article_data['summary'].'</textarea>
				</p>
				<p>
					<label for="body">Article</label>
					<textarea name="body" id="body" cols="40" rows="3">'.htmlentities(stripslashes($article_data['body'])).'</textarea>
				</p>
				<p>
					<label for="author_id">Author</label>
					'.$author_select.'
				</p>
				<p>
					<label for="category_id">Category</label>
					'.$category_select.'
					<span class="note">or type in a new category below</span>
					<input type="text" name="category_new" id="category_new" class="unlabelled"/>
					<input type="hidden" name="category_url" id="category_url" value="'.$article_data['category_url'].'"/>
				</p>
				<p>
					<label for="tags">Tags</label>
				</p>
					<ul id="tags" class="checkbox_list">
					'.$tag_checkboxes.'
					</ul>
				<p>
					<span class="note">or type in a new tag below (separate with commas)</span>
					<input type="text" name="tag_new" id="tag_new" class="unlabelled"/>
				</p>
				';
		// show date and time dropdowns - only for draft and postdated articles
		if( ($article_data['status'] == 'D') || (strtotime($article_data['date_uploaded']) > time()) ) {
			$html .= form_insert_date_fields($article_data);
		} else {
			// show publish date in hidden field
			$html .= '
			<input type="hidden" name="date_uploaded" value="'.$article_data['date_uploaded'].'"/>';
		}

		// status options - only available non-draft articles
		if($article_data['status'] != 'D') {
			$a_selected = ($article_data['status'] == 'A')? ' selected="selected"':'';
			$p_selected = ($article_data['status'] == 'P')? ' selected="selected"':'';
			$w_selected = ($article_data['status'] == 'W')? ' selected="selected"':'';
			$html .= '
				<p>
					<label for="status">Status</label>
					<select name="status">
						<option value="P"'.$p_selected.'>Published</option>
						<option value="A"'.$a_selected.'>Archived</option>
						<option value="W"'.$w_selected.'>Withdrawn</option>
					</select>
				</p>
			';

		}
		// option to update url version of post title
		if(!empty($article_data['url'])) {
			$pre_checked = ($article_data['status'] == 'D') ? ' checked="checked"' : '' ;
			$html .= '
			<p>
				<label for="url">URL title</label>
				<input type="text" name="url" value="'.$article_data['url'].'" readonly="readonly"/>
				<span class="note">
					<input type="checkbox" name="update_url" value="1"'.$pre_checked.'/>&nbsp;tick here if you want the url-friendly version of the post title updated<br />(note that this will change the permanent url for your article)
				</span>
			</p>';
		}
		$html .= '</div>';
		return $html;
	}

/**
 * form_insert_date_fields
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function form_insert_date_fields($article_data) {
		// select for day
		$html = '
				<p>
					<label for="day">Publish Date:</label>
					<select name="day" id="day">';
					for ($d = 1; $d<=31; $d++) {
						$day_select 	= ((int)$d == (int)$article_data['day']) ? ' selected="selected"' : '' ;
						$d = str_pad($d, 2, '0', STR_PAD_LEFT);
						$html .= '
						<option value="'.$d.'"'.$day_select.'>'.$d.'</option>';
					}
					$html .= '
					</select>';
			
		// select for month
		$html .= '
					<select name="month" id="month">';
					for ($m = 1; $m<=12; $m++) {
						$month_select 	= ((int)$m == (int)$article_data['month']) ? ' selected="selected"' : '' ;
						$m = str_pad($m, 2, '0', STR_PAD_LEFT);
						$month_name = date("F", mktime(0,0,0,$m, 1));
						$html .= '
						<option value="'.$m.'"'.$month_select.'>'.$month_name.'</option>';
					}
					$html .= '
					</select>';	
		
	
		// select for year
		$html .= '
					<select name="year" id="year">';
					for ($y = 2005; $y<=date('Y')+1; $y++) {
						$year_select 	= ((int)$y == (int)$article_data['year']) ? ' selected="selected"' : '' ;
						$html .= '
						<option value="'.$y.'"'.$year_select.'>'.$y.'</option>';
					}
					$html .= '
					</select>';
		$html .= ' at ';

		// select for hour
		$html .= '
					<select name="hour" id="hour">';
					for ($h = 00; $h<=23; $h++) {
						$hour_select 	= ((int)$h == (int)$article_data['hour']) ? ' selected="selected"' : '' ;
						$h = str_pad($h, 2, '0', STR_PAD_LEFT);
						$html .= '
						<option value="'.$h.'"'.$hour_select.'>'.$h.'</option>';
					}
					$html .= '
					</select> : ';

		// select for minute
		$html .= '
					<select name="minute" id="minute">';
					for ($min = 01; $min<=59; $min++) {
						$minute_select 	= ((int)$min == (int)$article_data['minute']) ? ' selected="selected"' : '' ;
						$min = str_pad($min, 2, '0', STR_PAD_LEFT);
						$html .= '
						<option value="'.$min.'"'.$minute_select.'>'.$min.'</option>';
					}
					$html .= '
					</select>
			</p>';
		return $html;	
	}
	
/**
 * form_attachments_tab
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function form_attachments_tab($article_data, $config) {
		$all_attachments = get_attachments();
	
		// first show current attachments
	
		$html = '
			<div id="tab_attachments">

				<p>
					<label for="attachments">Current</label>
					';
				if(empty($article_data['attachments'])) {
					$html .= '
					<input type="text" name="no_attachments" value="No attachments" readonly="readonly"/>
				</p>
					<ul id="attachments" class="checkbox_list">
					</ul>
					';	
				} else {
					$html .= '
				</p>
					<ul id="attachments" class="checkbox_list">';
					
					foreach($article_data['attachments'] as $attach) {
						$html .= '
						<li>
							<label for="attachments['.$attach['id'].']">
							<input type="checkbox" name="attachments['.$attach['id'].']" id="attachments['.$attach['id'].']" value="'.$attach['id'].'" checked="checked"/>
							'.$attach['title'].'</label>
						</li>
						';
					}
					$html .= '
					</ul>';
				}
				
			// add an existing attachment		
				
				$html .= '
				<p>
					<label for="add_attachment">Add</label>
					<select name="add_attachment">';
			foreach($all_attachments as $files){
				$html .= '
						<option value="'.$files['id'].'">'.$files['title'].' /'.$files['ext'].'</option>';
			}
			$html .= '	</select>
					<input type="button" name="queue_attachment" value="Add to article" />
				</p>';
				
			// upload a new attachment
				
			$html .= '
				<p>
					<label for="attachment_file">Upload</label>
						<input name="attachment_file" type="file" id="attachment_file"/>
						<input type="button" name="upload_attachment" value="Upload and Add to article"/>
				</p>
			</div>';
		return $html;
	}


/**
 * form_seo_tab
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function form_seo_tab($article_data) {
		$html = '
			<div id="tab_seo">

				<p class="descriptor">The following fields allow you to optionally specify a keyword-rich page title and/or description for this article, as well as relevant keywords.</p>
				<p>
					<label for="seo_title">SEO Title</label>
					<textarea name="seo_title" cols="40" rows="3" class="optional">'.$article_data['seo_title'].'</textarea>
					<span class="note">(main article title used if left blank)</span>
				</p>
				<p>
					<label for="seo_desc">SEO Description</label>
					<textarea name="seo_desc" cols="40" rows="3" class="optional">'.$article_data['seo_desc'].'</textarea>
					<span class="note">(main article summary used if left blank)</span>
				</p>
				<p>
					<label for="seo_keywords">SEO Keywords</label>
					<textarea name="seo_keywords" cols="40" rows="3" class="optional">'.$article_data['seo_keywords'].'</textarea>
					<span class="note">(site keywords used if left blank)</span>
				</p>
			</div>';
		return $html;
	}

/**
 * form_comments_tab
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function form_comments_tab($article_data, $config) {
		$hide_check = (!empty($article_data['comments_hide'])) ? ' checked="checked"' : '' ;
		$disable_check = (!empty($article_data['comments_disable'])) ? ' checked="checked"' : '' ;
		$hidden = (empty($config['comments']['site_hide'])) ? 'visible' : '<strong>hidden</strong>';
		$disabled = (empty($config['comments']['site_disable'])) ? 'enabled' : '<strong>disabled</strong>';
		$html = '
			<div id="tab_comments">
				<h2>Comments</h2>
				<p>This section enables you to hide existing comments or disable posting of further comments (for this article only). These settings will be overridden if comments are hidden or disabled sitewide (comments are currently <em>'.$disabled.'</em> and <em>'.$hidden.'</em> across the site).</p>
				<p>
					<label for="comments">Comments</label>
				</p>
				<ul id="comments" class="checkbox_list">
					<li>
						<label for="comments_hide">
							<input type="checkbox" name="comments_hide" id="comments_hide" value="1"'.$hide_check.'/>
						Hide</label>
					</li>
					<li>
						<label for="comments_disable">
							<input type="checkbox" name="comments_disable" id="comments_disable" value="1"'.$disable_check.'/>
						Disable</label>
					</li>
				</ul>
				<p>
					<span class="note">Note: If comments are hidden then the comment form will also be hidden.<br />
					Disabling comments will hide the comment form but won\'t affect existing comments</span>
				</p>
			</div>';
		return $html;
	}

/**
 * form_edits_tab
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function form_edits_tab($article_data) {
		$edits = get_article_edits($article_data['id']);
		$html = '
			<div id="tab_edits">
				<h2>Edits</h2>';
		if(empty($edits)) {
			$html .= '
				<p>There are no previous edits for this article.</p>';
		} else {
			$html .= '
				<ul class="article_listing">';
			foreach($edits as $edit) {
				$html .= '
					<li>
						<div class="article_title">'.from_mysql_date($edit['date_edited'],'d M Y H:i').'</div>
						<div class="article_author"> by '.$edit['name'].'</div>
						<span>
							<a class="preview_edit" href="'.WW_WEB_ROOT.'/ww_edit/_content/preview_edit.php?edit_id='.$edit['id'].'&amp;author_id='.(int)$_SESSION[WW_SESS]['user_id'].'&amp;sess='.htmlspecialchars(session_id()).'">view</a>
						</span>
					</li>';
			}
			$html .= '
				</ul>';
		}
		$html .= '
			</div>';
		return $html;
	}

/**
 * build_file_listing
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function build_file_listing($files) {
		if(empty($files)) {
			$html = '<p>No files found</p>';
			return false;
		}
		$html = '<ul class="file_listing">';
		foreach($files as $file) {
			if(!is_array($file)) {
				continue;
			}
			// determine type of listing
			if($_GET['page_name'] == 'attachments') {
				// check file exists
				$file_check = WW_ROOT.'/ww_files/attachments/'.$file['ext'].'/'.$file['filename'];
				$class = (!file_exists($file_check)) ? ' class="notfound"' : '' ;
				// output
				$html .= '
				<li'.$class.'>
					
					<div class="file_name">
						<a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments&amp;attachment_id='.$file['id'].'">
						'.$file['title'].'</a>
					</div>
					
					<div class="file_title">
						'.$file['filename'].'
					</div>
	
					<div class="file_type">
						
						<a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments&amp;ext='.$file['ext'].'">
						'.$file['ext'].'</a> : '.$file['mime'].'
					</div>
					
					<div class="file_size">
						size: '.get_kb_size($file['size']).'kb
					</div>	
								
					<div class="file_downloads">
						downloads: '.$file['downloads'].'
					</div>
					
					<div class="file_date">
						uploaded: '.from_mysql_date($file['date_uploaded']).'
					</div>	
								
					<div class="file_author">
						by '.$file['author_name'].'
					</div>
		
					<div class="file_delete">
						<a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments&amp;action=delete&amp;attachment_id='.$file['id'].'">
						delete</a>
					</div>
					
				</li>';
			} else {
				$html .= '
				<li>
					
					<div class="file_name">
						<a href="'.$file['link'].'">
						'.$file['filename'].'</a>
					</div>
					
					<div class="file_title">
						folder: <a href="'.$_SERVER["PHP_SELF"].'?page_name=files&amp;folder='.$_GET['folder'].'">
						'.$_GET['folder'].'</a>
					</div>
					
					<div class="file_size">
						size: '.get_kb_size($file['size']).'kb
					</div>	
					
					<div class="file_date">
						uploaded: '.date('d F Y',$file['date_uploaded']).'
					</div>
					
					<div class="file_author">
						type: '.$file['ext'].'
					</div>	
		
					<div class="file_delete">
						<a href="'.$_SERVER["PHP_SELF"].'?page_name=files&amp;action=delete&amp;folder='.$_GET['folder'].'&amp;filename='.$file['filename'].'">
						delete</a>
					</div>
					
				</li>';				
			}
		}
		$html .= '</ul>';
		return $html;		
	}


?>