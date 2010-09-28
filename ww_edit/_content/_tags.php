<?php
	if(!defined('WW_SESS')) {
		exit();
	}
// page title - if undefined the site title is displayed by default

	$page_title = 'Tags';


// process post actions

	// insert tag

	if( (isset($_POST['insert_tag'])) && ($_POST['insert_tag'] == 'add') ) {
		
		$insert_status = insert_tag();
		if(is_int($insert_status)) {
			header('Location: '.$_SERVER["PHP_SELF"].'?page_name=tags&tag_id='.$insert_status);
		} else {
			$insert_error = $insert_status;
		}
		
	}
	
	// update tag
		
	if( (isset($_POST['update_tag'])) && ($_POST['update_tag'] == 'update') ) {
		
		$tag_id = (int)$_GET['tag_id'];
		$update_status = update_tag($tag_id);
		if($update_status === true) {
			header('Location: '.$url);
		} else {
			$error = $update_status;
		}
		
	}
	
	// delete tag
		
	if( (isset($_POST['confirm_delete_tag'])) && ($_POST['confirm_delete_tag'] == 'Yes') ) {
		
		$tag_id = (int)$_GET['tag_id'];
		$delete_status = delete_tag($tag_id);
		if($delete_status === true) {
			header('Location: '.$_SERVER["PHP_SELF"].'?page_name=tags');
		} else {
			$error = $delete_status;
		}
	}
	
	// cancel delete tag
		
	if( (isset($_POST['cancel_delete_tag'])) && ($_POST['cancel_delete_tag'] == 'No') ) {
	
		$tag_id = (int)$_GET['tag_id'];
		header('Location: '.$_SERVER["PHP_SELF"].'?page_name=tags&tag_id='.$tag_id);

	}	


// get main data
	
	$tag_id = (isset($_GET['tag_id'])) ? (int)$_GET['tag_id'] : 0 ;
	
	if(empty($tag_id)) {

		$left_text = 'Tags';
		$right_text = '';
		
		$page_header = show_page_header($left_text, $right_text);
		
		$main_content = $page_header;
		$main_content .= '<p>No tag selected: choose a tag to edit from the right hand menu.</p>';
		
	} else {
		
		$tag = get_tag_details($tag_id);
		
		$left_text = 'Tags: '.$tag['title'];
		$right_text = $tag['url'];
		
		$page_header = show_page_header($left_text, $right_text);
		
		$main_content = $page_header;
		
	// confirm file delete
		
		if( (isset($_GET['action'])) && ($_GET['action'] == 'delete') ) {

			$main_content .= '
				<h2>Are you sure you want to delete this tag?</h2>
				<form action="'.$action_url.'" method="post" name="confirm_delete_tag_form">
					<input name="confirm_delete_tag" type="submit" value="Yes"/>
					<input name="cancel_delete_tag" type="submit" value="No" />
				</form>
				<hr />
				';
			
		}
	
	// show errors
	
		if( (isset($error)) && (!empty($error)) ) {
			$main_content .= '
			<p><strong>'.$error.'</strong></p>';
		}
	
	// show form
		
		$main_content .= '
				<h4>edit tag</h4>
				
				<p><a href="'.$_SERVER["PHP_SELF"].'?page_name=articles&amp;tag_id='.$tag_id.'">
					Check which articles use this tag</a>
				</p>
				
				<form action="'.$_SERVER["PHP_SELF"].'?page_name=tags&amp;tag_id='.$tag_id.'" method="post" id="edit_tag_form">
				<p>
					<label for="title">Title</label>
					<input name="title" type="text" value="'.$tag['title'].'"/>
				</p>
				<p>
					<label for="url">Url</label>
					<input name="url" type="text" value="'.$tag['url'].'" readonly/>
					<span class="note">this will be updated automatically if you change the tag title</span>
				</p>
				<p>
					<label for="summary">Summary</label>
					<textarea name="summary" cols="21" rows="3" class="optional">'.$tag['summary'].'</textarea>
					<span class="note">optional</span>
				</p>
				<p>
					<span class="note">
					<input name="update_tag" value="update" type="submit"/>
					</span>
				</p>
				</form>
		';
		
	// delete tag form	
		
		$main_content .= '
			<hr />
			<h4>delete tag</h4>
			
			<p>NOTE: this will also remove this tag from any articles it is currently attached to...</p>
			
			<form action="'.$action_url.'" method="get" id="delete_tag_form">
				<p>
					<span class="note">
					<input type="hidden" name="page_name" value="tags"/>
					<input type="hidden" name="tag_id" value="'.$tag_id.'"/>
					<input name="action" type="submit" value="delete"/>
					</span>
				</p>
			</form>
			';
		
	}


// get aside content
	
	$tags_list 		= get_tags_admin();
	
	$add_tag_form = ( (isset($insert_error)) && (!empty($insert_error)) ) ? '<p><strong>'.$insert_error.'</strong></p>' : '';
	
	$add_tag_form .= '
				<form action="'.$action_url.'" method="post" id="add_tag_form">
				<p>
					<label for="title">Tag title</label>
					<input name="title" type="text" size="20"/>
				</p>
				<p>
					<label for="summary">Tag summary (optional)</label>
					<textarea name="summary" cols="21" rows="3"></textarea>
				</p>
				<p>
					<input name="insert_tag" value="add" type="submit"/>
				</p>
				</form>
	';

	// output aside content

	$aside_content = build_snippet('Tags',$tags_list);
	$aside_content .= build_snippet('Add a Tag',$add_tag_form);
?>