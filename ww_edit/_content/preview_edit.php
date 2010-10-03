<?php
	session_start();

	/*	check author session 
	
	 	the author id and session_id are sent via url params to the iframe
	 	
		using the author id we derive the cookie name - then we can check if the 
		cookie value matches the session id that was sent via the url	 	
	*/
	
	$author_id = (isset($_GET['author_id'])) ? (int)$_GET['author_id'] : '' ;
	$sess_val = (isset($_GET['sess'])) ? htmlspecialchars_decode($_GET['sess']) : '' ;
	
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
	include_once(WW_ROOT.'/ww_config/author_controller_functions.php');
	include_once(WW_ROOT.'/ww_config/author_view_functions.php');
	
	$edit_id = (isset($_GET['edit_id'])) ? (int)$_GET['edit_id'] : '' ;
	
	if(!empty($edit_id)) {
		$edit = get_article_edit($edit_id);
	} else {
		$error = '<p>No edit selected</p>';
	}
	
	// delete
	if( (isset($_GET['action'])) && ($_GET['action'] == 'delete_edit') ) {
		$delete_edit =  delete_article_edit($edit_id);
		if($delete_edit === true) {
			$error = 'Edit has been deleted - you can now close this window.';
		} else {
			$error = $delete_edit;
		}
	}
	
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
	<script type="text/javascript" src="<?php echo WW_WEB_ROOT; ?>/ww_edit/_js/jquery.js"></script>
	<script type="text/javascript">
		
		$(function(){
		    $('a#use_edit').click(function(){
		        var new_body = $('div#edit_body').html();
		        // change both text editor and tinymce instance
		        window.opener.$('#body').html(new_body);
		        window.opener.tinymce.activeEditor.setContent(new_body);
		        // move to #tab_article tab
		        window.opener.$("#article_tabs").tabs('select', 0)
				window.close(); 
		    });
		});	
		
	</script>
	</head>
<body>
<?php	
// show edit

	if(!empty($edit)) {
		
		$body = (function_exists('gzinflate')) ? gzinflate($edit['body']) : $edit['body'];
					
		echo '
		<a id="top"</a>
		<h2>'.stripslashes($edit['title']).'</h2>
		<p>
			<em>version created on '.date('d M Y H:i',strtotime($edit['date_edited'])).' by '.$edit['name'].'</em>
			&#124; <a href="javascript:void(0);" id="use_edit">use this</a>
			&#124; <a href="#delete">delete</a>
		</p>
		<hr />
		<div id="edit_body">'.stripslashes($body).'</div>
		<hr />
		<a id="delete"</a>
		<p>really delete it? are you sure?</p>
		<p><a href="'.current_url().'&amp;action=delete_edit">yes</a> &#124; <a href="#top">no</a></p>';
		
	} else {
		
		echo $error;
		
	}
?>
</body>
</html>