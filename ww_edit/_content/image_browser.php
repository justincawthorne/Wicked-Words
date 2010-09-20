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
	
	$url = $_SERVER["PHP_SELF"].'?sess='.$_GET['sess'].'&amp;author_id='.$_GET['author_id'];
	
// now bring in our functions	
	
	//include_once(WW_ROOT.'/ww_config/model_functions.php');
	//include_once(WW_ROOT.'/ww_config/controller_functions.php');
	include_once(WW_ROOT.'/ww_config/author_controller_functions.php');
	include_once(WW_ROOT.'/ww_config/author_view_functions.php');
	include_once(WW_ROOT.'/ww_config/combined_functions.php');

// pagination parameters

	$page 	= (empty($_GET['page'])) ? '1' : (int)$_GET['page'] ;

// upload image
	
	if( (isset($_POST['upload_image'])) && ($_POST['upload_image'] == 'upload') ) {
		
		$upload_success = insert_image();
		
	}

// get images

	$all_images = get_images(7);
	// $author_images = list_images(7,1);
	
	$total_pages = (!empty($all_images)) ? $all_images[0]['total_pages'] : 0 ;
	$total_images = (!empty($all_images)) ? $all_images[0]['total_images'] : 0 ;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
	<script type="text/javascript" src="<?php echo WW_WEB_ROOT; ?>/ww_edit/_js/jquery.js"></script>
	<script type="text/javascript">
		
		$(document).ready(function(){  
		
			// toggle upload form
			
			$("#toggle").click(function(){  
	 
				$("#image_form").toggleClass("hide show");  
	 
			});
			
			// iframe height
			
			var theFrame = $("#image_browser_frame", parent.document.body);
			theFrame.height($(document.body).height() + 280); 
			
		});
		
		// image browser popup
		
		$(function(){
		    $('a.image_detail_link').click(function(){
		        window.open(this.href,'detail','width=640,height=480,location=no,scrollbars=no,toolbar=no,status=no,titlebar=no');
		        return false;
		    });
		});
		

	</script>
	<style type="text/css">
		body{
			font-family: Arial, Helvetica, sans-serif;
			font-size: 100%;
			width: 178px;
			margin: 0;
			padding: 0;
		}
		.show {
			display: block;
		}
		.hide {
			display: none;
		}
		p {
			font-size: 0.75em;  /* 13px */
			line-height: 1.5em;
		}
		a {
			text-decoration: none;
			color: #660000;
		}
		a:hover {
			color: #990000;
		}
		h6 {
			font-size: 0.875em;
			color: #444;
			border-bottom: solid 1px #ccc;
			overflow: auto;	
			margin: 0;		
		}
		h6 #toggle {
			float: right;
		}
		h6 #toggle a {
			outline: none;
		}
		form {

		}
		form label {
			color: #999;
		}
		form input[type="text"]{
			background-color: #f4f4f4;
			border: solid 1px #ccc;
			height: 16px;
			padding: 2px;
		}
		form select{
			background-color: #f4f4f4;
			border: solid 1px #ccc;
			padding: 2px;
		}
		form select:focus,
		form input:focus{
			background-color: #fff;
			border: solid 1px #666;
		}
		#image_form {
			border-bottom: solid 1px #aaa;
		}
		#image_form input[type="submit"]{
			float: right;
		}
		#paging_form {
			
		}
		#browser_wrapper {
			
		}
		.image_wrapper {
			border-bottom: dotted 1px #ccc;
			padding: 0.5em 0 0.25em;
		}
		.image_wrapper img {
			width: 140px;
			height: auto;
		}
		.image_wrapper p {
			color: #333;
			margin: 0.5em 0;
			line-height: 1.0em;
		}
	</style>
	</head>
<body>

	<h6>image browser<span id="toggle"><a href="javascript:void(0);">upload</a></span></h6>
	<?php 
		if(isset($upload_success)) { echo $upload_success; } 
		
		$max_size = (!empty($config['files']['max_image_size'])) ? get_kb_size($config['files']['max_image_size']).'kb' : 'none' ;
		$max_width = (!empty($config['files']['max_image_width'])) ? $config['files']['max_image_width'].'px (wider images will be resized)' : 'none' ;	
	?>
	<form action="<?php echo $url; ?>" method="post" enctype="multipart/form-data" id="image_form" class="hide">
		

		
		<p><label for="image_file">Select image</label>
			<input name="image_file" type="file" size="12" id="image_file"/></p>
			
		<p><label for="thumb_file">Thumbnail (optional)</label>
			<input name="thumb_file" type="file" size="12" id="thumb_file"/></p>
		
		<p><label for="title">Image title (optional)</label> 
			<input name="title" type="text" size="24"/></p>
		
		<p><label for="alt">Alt text (optional)</label> 
			<input name="alt" type="text" size="24"/></p>
			
		<p><label for="width">New width (px) (optional)</label> 
			<input name="width" type="text" size="3"/>	
					
			<input type="submit" name="upload_image" value="upload" /></p>
			
		<p>
			<label>Max image size: <?php echo $max_size; ?></label><br/>
			<label>Max image width: <?php echo $max_width; ?></label>

		</p>
		
			
			
	</form>
	
<?php

// show paging form

	if($total_pages > 1) {
		echo '
		<form action="'.$url.'" method="post" name="paging_form" id="paging_form">
		<p>page: <select name="page" id="page" onchange="location.href=this.options[selectedIndex].value">';
		for ($p = 1; $p <= $total_pages; $p++) {
			$page_selected = ($p == $page) ? ' selected="selected"' : '' ;
			echo '
			<option value="'.$url.'&page='.$p.'"'.$page_selected.'>&nbsp;'.$p.'</option>';
		}
		echo '
		</select> of '.$total_pages.'&nbsp;
		</p>
		</form>';
	}
	
// show images

	echo '
		<div id="browser_wrapper">';
		
		foreach($all_images as $image) {
			if(!is_array($image)) {
				continue;
			}
			$detail_url = 'image_detail.php?sess='.$_GET['sess'].'&amp;author_id='.$_GET['author_id'].'&amp;image_id='.$image['id'];
			echo '
			<div class="image_wrapper">
				<img src="'.$image['src'].'" 
					alt="'.$image['alt'].'" 
					title="'.$image['title'].'" 
					width="'.$image['width'].'" height="'.$image['height'].'" 
					class="left"/>';
			if($image['title'] != $image['filename']) {
				echo '		
				<p><b>'.$image['title'].'</b></p>';
			}
			echo '		
				<p>'.$image['filename'].'</p>
				<p>'.$image['width'].' x '.$image['height'].' &#124; 
					<a href="'.$detail_url.'" class="image_detail_link">more</a>
				</p>
			</div>';
		}
		
	echo '
		</div>'; // close browser wrapper
	
?>
</body>
</html>