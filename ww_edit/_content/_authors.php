<?php
	if(!defined('WW_SESS')) {
		exit();
	}
// page title - if undefined the site title is displayed by default

	$page_title = 'Authors';
	
	require('_snippets/class.phpmailer-lite.php');


// contributors can only select themselves, so let's lock down the author id url parameter
	
	if( ( (!empty($_SESSION[WW_SESS]['guest'])) && ($_SESSION[WW_SESS]['level'] == 'contributor') ) 
		|| (!isset($_GET['author_id'])) ) {
		
		$_GET['author_id'] = $_SESSION[WW_SESS]['user_id'];
			
	}

// process post actions
	
	// insert author

	if( (isset($_POST['add_author'])) && ($_POST['add_author'] == 'add') ) {
		$insert_status = insert_author();
		if(is_int($insert_status)) {
			$reload = $_SERVER["PHP_SELF"].'?page_name=authors&author_id='.$insert_status;
			header('Location: '.$reload);	
			echo $reload;		
		} else {
			$insert_error = $insert_status;
		}
		
	}
	
	// update author

	if( (isset($_POST['update_author'])) && ($_POST['update_author'] == 'update') ) {

		$author_id = (int)$_GET['author_id'];
		$update_status = update_author($author_id);
		if($update_status === true) {
			header('Location: '.$url);
		} else {
			$error = $update_status;
		}
		
	}

	// change password

	if( (isset($_POST['change_password'])) && ($_POST['change_password'] == 'change') ) {
		
		$author_id = (int)$_GET['author_id'];
		$change_status = change_author_password($author_id);
		if($change_status === true) {
			$reload = $_SERVER["PHP_SELF"].'?page_name=authors&author_id='.$author_id.'&passwordchanged';
			header('Location: '.$reload);
		} else {
			$error = 'Error changing password: '.$change_status;
		}
		
	}

	// delete author
	
	if( (isset($_POST['confirm_delete_author'])) && ($_POST['confirm_delete_author'] == 'Yes') ) {
		
		$author_id = (int)$_GET['author_id'];
		$delete_status = delete_author($author_id);
		if($delete_status === true) {
			$reload = $_SERVER["PHP_SELF"].'?page_name=authors';
			header('Location: '.$reload);
		} else {
			$error = $delete_status;
		}
	}

	// author list
	
	$author_id = (isset($_GET['author_id'])) ? (int)$_GET['author_id'] : $_SESSION[WW_SESS]['user_id'] ;	
	
	$author = get_author($author_id);
	
	// check if author has written any articles
	
	$usage 	= get_articles_stats(1);
	
	// redirect if author details not found
	
	if(empty($author)) {
		$reload = $_SERVER["PHP_SELF"].'?page_name=authors';
		header('Location: '.$reload);		
	}
	
	// any content generation
	
	$left_text = $author['name'];
	$right_text = $author['level'];
	$page_header = show_page_header($left_text, $right_text);
	
	$main_content = $page_header;
	
	// confirm file delete
		
		if( (isset($_GET['action'])) && ($_GET['action'] == 'delete') && (empty($usage)) ) {

			$main_content .= '
				<h2>Are you sure you want to delete this author?</h2>
				<form action="'.$action_url.'" method="post" name="confirm_delete_author_form">
					<input name="confirm_delete_category" type="submit" value="Yes"/>
					<input name="cancel_delete_category" type="submit" value="No" />
				</form>
				<hr />
				';
			
		}

	// errors

		if( (isset($error)) && (!empty($error)) ) {
			$main_content .= '
			<p><strong>'.$error.'</strong></p>';
		}
		
	// password change notification

		if(isset($_GET['passwordchanged'])) {
			$main_content .= '
			<p><strong>The password for this author has been changed</strong></p>';
		}
		
	// show usage
		$total_usage = count($usage);
		$usage_text = ($total_usage == 1) ? $total_usage.' article' : $total_usage.' articles' ;
		
		if(!empty($total_usage)) {
			$usage_text = '
			<a href="'.$_SERVER["PHP_SELF"].'?page_name=articles&amp;author_id='.$author_id.'">'.$usage_text.'</a>';
		}
		
		$main_content .= '
		<p>'.$author['name'].' has written '.$usage_text.'.</p>';
	
	// contact flag
	
		$contact_checked = (!empty($author['contact_flag'])) ? ' checked="checked"' : '' ;
		$image_form_content = '';
		
		if(!empty($author['image'])) {
			
			$main_content .= '	
			<div style="width:260px; height:auto; float:right;">
				<a href="#author_image">
				<img style="float: right; margin-bottom: 12px;" 
					alt="image for '.$author['name'].'" 
					title="'.$author['image'].'" 
					src="'.WW_WEB_ROOT.'/ww_files/images/authors/'.$author['image'].'"/>
				</a>
				<a href="#author_thumb">
				<img style="float: right; margin-bottom: 12px;" 
					alt="thumbnail image for '.$author['name'].'" 
					title="'.$author['image'].'" 
					src="'.WW_WEB_ROOT.'/ww_files/images/authors/th_'.$author['image'].'"/>
				</a>
			</div>';
			
			$image_form_content = '
				<p>
					<label for="delete_author_image">Delete Image</label>
					<input name="delete_author_image" type="checkbox" value="1" />
					<span class="note">
					If you wish to delete the current image without uploading a new one then check this box.<br /><br />
					image src:<br />'.WW_WEB_ROOT.'/ww_files/images/authors/'.$author['image'].'<br /><br />
					thumb src:<br />'.WW_WEB_ROOT.'/ww_files/images/authors/th_'.$author['image'].'
					</span>
				</p>			
			';
					
		}
	
		$main_content .= '
		<form action="'.$action_url.'" method="post" enctype="multipart/form-data" id="edit_author_form">

				<p>
					<label for="name">Author Name</label>
					<input name="name" type="text" value="'.$author['name'].'"/>
				</p>
					
				<p>
					<label for="email">Email Address</label>
					<input name="email" type="text" value="'.$author['email'].'"/>
				</p>
				
				<p>
					<label for="summary">Author Summary</label>
					<textarea name="summary" class="optional" cols="56" rows="3">'.$author['summary'].'</textarea></p>
					
				<p>
					<label for="biography">Biography</label>
					<textarea name="biography" class="optional" cols="56" rows="13">'.$author['biography'].'</textarea>
				</p>
				
				<p>
					<label for="author_image">Image</label>
					<input name="author_image" type="file" id="author_image" />
					<input name="thumb_width" type="hidden" value="'.$config['files']['thumb_width'].'"/>
					<input name="current_image" type="hidden" value="'.$author['image'].'"/>
					<span class="note">Your image will be uploaded at its original size, but a thumbnail will also be created ('.$config['files']['thumb_width'].'px wide). Any existing image for this author will be deleted if a new one is uploaded.</span>
				</p>
				
				'.$image_form_content.'
				
				<p>
					<label for="contact_flag">Contactable?</label>
					<span class="checkbox">
						<input type="checkbox" name="contact_flag" value="1"'.$contact_checked.'/>
					</span>
				</p>';	
					
	if(empty($_SESSION[WW_SESS]['guest'])) {
		
		// block/expiry status
		$current_ts = time();
		$author_expiry = $author['sub_expiry'];
		$expiry_ts = strtotime($author_expiry);
		// default to unblocked
		$block_checked = '';
		
		if( ($author_expiry == '0000-00-00 00:00:00') || (empty($author_expiry)) ) {
			$block_checked = '';
		} elseif($current_ts > $expiry_ts) {
			$block_checked = ' checked="checked"';	
		}
		
		$author_selected = (empty($author['guest_flag'])) ? ' selected="selected"' : '' ;
		$editor_selected = ($author['guest_areas'] == 'editor') ? ' selected="selected"' : '' ;
		$contributor_selected = ($author['guest_areas'] == 'contributor') ? ' selected="selected"' : '' ;
		
		$main_content .= '
				<p><label for="author_level">Access Level</label>
					<select name="author_level">
						<option value="contributor"'.$contributor_selected.'>Contributor</option>
						<option value="editor"'.$editor_selected.'>Editor</option>
						<option value="author"'.$author_selected.'>Author</option>
					</select>
				</p>
					
				<p>
					<label for="block_author">Block Author</label>
					<span class="checkbox">
						<input type="checkbox" name="block_author" value="1"'.$block_checked.'/>
					</span>
					<span class="note">Blocking an author sets their subscription expiry date to the current time
				 - when the author tries to log in they will get a message saying their subscription has expired
				 and won\'t be able to access the admin site</span>
				</p>';
	}	
		$main_content .= '
				<p>
					<label for="last_login">Last Logged In</label>
					<input name="last_login" type="text" value="2010-07-17 20:09:16" readonly="readonly"/>
				</p>
				<p>
					<label for="last_ip">Last IP</label>
					<input name="last_ip" type="text" value="203.206.40.48" readonly="readonly"/>
				</p>
				
				<p>
					<span class="note">
					A confirmation email, containing all the author\'s details, will be sent to both the author and the site admin following submission.
					</span>
				</p>
				<p>
					<span class="note">
					<input type="submit" name="update_author" value="update" />
					</span>
				</p>
			
			</form>
	';
	
	// change password - only for author level (can change any password) or for own password
	
	if( (empty($_SESSION[WW_SESS]['guest'])) || ($author_id == $_SESSION[WW_SESS]['user_id']) ) {
		
		$main_content .= '
		<hr />
		<h4>change password</h4>
		
		<form action="'.$action_url.'" method="post" id="change_password_form">

				<p>
					<label for="old_pass">Current</label>
					<input name="old_pass" type="password"/>
				</p>

				<p>
					<label for="new_pass">New</label>
					<input name="new_pass" type="password"/>
				</p>
				<p>
					<label for="confirm_pass">Confirm new</label>
					<input name="confirm_pass" type="password"/>
				</p>
				
				<p>
					<span class="note">
					A confirmation email will be sent to the author following a password change.
					</span>
				</p>
				<p>
					<span class="note">
					<input type="submit" name="change_password" value="change" />
					</span>
				</p>
			
			</form>';
			
		}
		
	// delete an author
	
			$main_content .= '
			<hr />
			<h4>delete author</h4>';
			
		if(!empty($usage)) {
			
			$main_content .= '
			<p>There are articles currently attached to this author, therefore this author cannot be deleted. If you wish to prevent them having further access to the site you can use the form above to block the author. Alternatively, if you reassign all of this author\'s articles to another current author you will be able to proceed with deleting them.</p>';
			
		} else {

			$main_content .= '
			<form action="'.$action_url.'" method="get" id="delete_author_form">
				<p>
					<span class="note">
					<input type="hidden" name="page_name" value="authors"/>
					<input type="hidden" name="author_id" value="'.$author_id.'"/>
					<input name="action" type="submit" value="delete"/>
					</span>
				</p>
			</form>
				';
			
		}

// get aside content

	// any functions
	
	$author_list 	= get_authors_admin();
	
	// any content generation
	
	$add_author_form = '';
	
	if( (isset($insert_error)) && (!empty($insert_error)) ) {
		$add_author_form .= '
		<p><strong>'.$insert_error.'</strong></p>';
	}
	
	$add_author_form .= '
			<form action="'.$action_url.'" method="post" id="add_author_form">
		
				<p><label for="name">Author name:</label> 
					<input name="name" size="20" type="text"/>
				</p>
			
				
				<p><label for="email">Email address:</label> 
					<input name="email" size="20" type="text"/>
				</p>
				
				<p><label for="author_level">Access Level:</label>
					<select name="author_level">
						<option value="contributor">Contributor</option>
						<option value="editor">Editor</option>
						<option value="author">Author</option>
					</select>
				</p>
				<p>
					<input name="add_author" value="add" type="submit"/>
				</p>
			</form>
			';
			
		$author_levels_text = '
			<ul>
				<li>
					<strong>Author</strong><br />
					access to all areas
				</li>
				<li>
					<strong>Editor</strong><br />
					access to write/edit all articles, manage authors, categories, tags, images and attachments
					<br /><em>no access to files, links or settings</em>
				</li>
				<li>
					<strong>Contributor</strong><br />
					access to write/edit own articles, images and attachments only
					<br /><em>no access to authors (excepting own details), categories, tags, files, links or settings</em>
				</li>
			</ul>
		';	



// output aside content - into $aside_content variable
	if(empty($_SESSION[WW_SESS]['guest'])) {
		
		$aside_content = build_snippet('Authors',$author_list);
		$aside_content .= build_snippet('Add an author',$add_author_form);
		
	} elseif($_SESSION[WW_SESS]['level'] == 'editor') {
		
		$aside_content = build_snippet('Authors',$author_list);
		
	}	
	
	$aside_content .= build_snippet('Access levels',$author_levels_text);

?>