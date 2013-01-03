<?php
	if(!defined('WW_SESS')) {
		exit();
	}
// page title - if undefined the site title is displayed by default

	$page_title = 'Images';
	
	$images_per_page = 12;

// upload image
	
	if( (isset($_POST['upload_image'])) && ($_POST['upload_image'] == 'upload') ) {
		
		$upload_status = insert_image();
		if(is_int($upload_status)) {
			header('Location: '.$_SERVER["PHP_SELF"].'?page_name=images&image_id='.$upload_status);
		} else {
			$error = $upload_status;
		}
		
	}
	
// update image details

	if( (isset($_POST['update'])) && ($_POST['update'] == 'update details') ) {
		
		$image_id = (int)$_POST['image_id'];
		if(!empty($image_id)) {
			$update_status = update_image($image_id);
			if($update_status == true) {
				header('Location: '.$url);
			} else {
				$error = $update_status;
			}			
		}
	
	}

// insert image details -  for rogue images

	if( (isset($_POST['insert'])) && ($_POST['insert'] == 'insert details') ) {
		
		$insert_status = insert_image_details($_POST);
		if($insert_status == true) {
			header('Location: '.$_SERVER["PHP_SELF"].'?page_name=images&image_id='.$insert_status);
		} else {
			$error = $insert_status;
		}			
	}
	
// replace thumbnail

	if(isset($_POST['replace_thumb'])) {
		
		if( (isset($_FILES['new_thumb'])) && (empty($_FILES['new_thumb']['error'])) ) {
			$location 	= WW_ROOT.'/ww_files/images/thumbs/';
			$current 	= $_POST['current_thumb'];
			$new		= $_FILES['new_thumb'];
			$th_replace_status = replace_file($location, $new, $current);
			if($th_replace_status == true) {
				header('Location: '.$url);
			} else {
				$error = $th_replace_status;
			}
		}
		
	}

// replace image

	if(isset($_POST['replace_image'])) {
		
		if( (isset($_FILES['new_image'])) && (empty($_FILES['new_image']['error'])) ) {
			$location 	= WW_ROOT.'/ww_files/images/';
			$current 	= $_POST['current_image'];
			$new 		= $_FILES['new_image'];
			$image_width = (int)$_POST['image_width'];
			$image_width = ($image_width > $config['files']['max_image_width']) ? $config['files']['max_image_width'] : $image_width ;
			$replace_status = replace_image($location, $new, $current, $image_width);
			if($replace_status == true) {
				// if there is no thumb let's also upload a thumb
				if(!file_exists($location.'thumbs/'.$current)) {
					$th_width = $config['files']['thumb_width'];
					$th_new = resize_image($new, $location.'thumbs/', $current, $th_width);
				}
				header('Location: '.$url);
			} else {
				$error = $replace_status;
			}
		}
		
	}
	
// confirm delete image
	
	if( (isset($_POST['confirm_delete_image'])) && ($_POST['confirm_delete_image'] == 'Yes') ) {

		$image_delete = delete_image($_POST['filename']);
		if(!empty($image_delete)) {
			header('Location: '.$_SERVER["PHP_SELF"].'?page_name=images');
		}

	}
	
// cancel delete attachment
	
	if( (isset($_POST['cancel_delete_image'])) && ($_POST['cancel_delete_image'] == 'No') ) {

		$image_id = (int)$_GET['image_id'];
		header('Location: '.$_SERVER["PHP_SELF"].'?page_name=images&image_id='.$image_id);

	}

// get image details or list of images
	
	$image_id = (isset($_GET['image_id'])) ? (int)$_GET['image_id'] : 0 ;
	
	if(isset($_GET['filename'])) {
		$filepath = WW_ROOT.'/ww_files/images/'.$_GET['filename'];
		if(file_exists($filepath)) {
			$filename = $_GET['filename'];
		}
	}
	
	if(!empty($image_id)) {
		
		$image = get_image($_GET['image_id']);
		
		// file usage
		
		$usage = file_usage($image['filename']);
	
	} elseif(isset($filename)) {
		
		$image = get_file_details($filepath);
		
		// file usage
		
		$usage = file_usage($image['filename']);		
		
	} else {
		
		$images = get_images($images_per_page);
		$orphans = get_image_orphans();
		$total_images = (!empty($images)) ? $images[0]['total_images'] : 0 ;
		$total_pages = (!empty($images)) ? $images[0]['total_pages'] : 0 ;
				
	}
	
// construct page header	
	
	$left_text = '<a href="'.$_SERVER["PHP_SELF"].'?page_name=files">files</a>';
	$left_text .= (isset($image)) ? ': <a href="'.$_SERVER["PHP_SELF"].'?page_name=images">images</a>' : ': images' ;
	$right_text = (isset($image)) ? $image['filename'] : $total_images.' found' ;
	$page_header = show_page_header($left_text, $right_text);


// output main content

	$main_content = $page_header;

	// if a single image is selected then show it
	
	if( (isset($image)) && (!empty($image)) ) {
		
	// confirm file delete
		
		if( (isset($_POST['action'])) && ($_POST['action'] == 'delete') ) {

			$main_content .= '
				<h2>Are you sure you want to delete this image?</h2>
				<form action="'.$url.'" method="post" name="confirm_delete_image_form">
					<input name="filename" type="hidden" value="'.$image['filename'].'"/>
					<input name="confirm_delete_image" type="submit" value="Yes"/>
					<input name="cancel_delete_image" type="submit" value="No" />
				</form>
				';
			
		}
		
	// jump options:
		
		$main_content .= '
		<p><strong> Jump to:</strong>
			<a href="#full_image">preview full image</a> &#124;
			<a href="#usage">usage</a> &#124;
			<a href="#replace_thumbnail">replace thumbnail image</a> &#124;
			<a href="#replace_image">replace image</a> &#124;
			<a href="#delete_image">delete image</a>
		</p>';
		
	// show errors
	
		if( (isset($error)) && (!empty($error)) ) {
			$main_content .= '
			<p><strong>'.$error.'</strong></p>';
		}
		
	// check both image and thumbnail are found
	
		if(!file_exists(WW_ROOT.'/ww_files/images/'.$image['filename'])) {
			$main_content .= '
			<p><strong>Image missing!</strong> This image cannot be found in the images folder - it will either need to be uploaded again or deleted from the database</p>';
		} else {
			if(!file_exists(WW_ROOT.'/ww_files/images/thumbs/'.$image['filename'])) {
				$main_content .= '
				<p><strong>Thumb missing!</strong> The thumbnail for this image is missing - you should upload a new one.</p>';
			}				
		}
			
	// rogue image notice
	
		if(isset($filename)) {
			$main_content .= '
			<p><strong>Rogue image!</strong> This image is present in the images folder, but doesn\'t have an entry in the database. You can either scroll to the bottom of the page to remove it entirely, or use the form directly below to insert it into the database.</p>';	
		}

	// set preview image style
	
		$preview_style = ($image['width'] < 270) 
			? 'width:'.$image['width'].'px; height:auto; float:right;'
			: 'width:680px; height:auto; overflow: auto;' ;
		
	// make mime/ext editable if we need to update them
	
		$mime_edit = (!empty($image['mime'])) ? ' readonly="readonly"' : '' ;
		$ext_edit = (!empty($image['ext'])) ? ' readonly="readonly"' : '' ;
		
	// set temp ext / mime
	
		if(empty($image['ext'])) {
			$temp_ext = pathinfo($image['filename']);
			$temp_ext = strtolower($temp_ext['extension']);
			$image['ext'] = $temp_ext;
		}
		if(empty($image['mime'])) {
			$mime = array();
			$mime['jpg'] = 'image/jpeg';
			$mime['png'] = 'image/png';
			$mime['gif'] = 'image/gif';
			$image['mime'] = $mime[$image['ext']];
		}
	
	// output image details form
		
		$main_content .= '
			<hr />
			<h4>image details</h4>';
		
		if(file_exists(WW_ROOT.'/ww_files/images/thumbs/'.$image['filename'])) {
			
			$main_content .= '	
			<a href="#full_image">
			<img style="float: right; margin-bottom: 12px;" 
				alt="'.$image['alt'].'" 
				title="'.$image['title'].'" 
				src="'.$image['thumb_src'].'"/>
			</a>';
			
		}	
			
		$main_content .= '	
			<p>This image was uploaded by '.$image['author_name'].' on '.from_mysql_date($image['date_uploaded']).'</p>
			<p>Form items with dotted borders are non-editable</p>
			<form id="image_details" method="post" action="'.$action_url.'">

				<p>
					<label for="title">Title</label>
					<input type="text" name="title" id="title" value="'.$image['title'].'"/>
				</p>
				<p>
					<label for="filename">Filename</label>
					<input type="text" name="filename" id="filename" value="'.$image['filename'].'" readonly="readonly"/>
				</p>
				<p>
					<label for="alt">Alt text</label>
					<input type="text" name="alt" id="alt" value="'.$image['alt'].'"/>
				</p>
				<p>
					<label for="caption">Caption</label>
					<textarea name="caption" id="caption" rows="3" cols="18">'.$image['caption'].'</textarea>
				</p>
				<p>
					<label for="credit">Credit</label>
					<input type="text" name="credit" id="credit" value="'.$image['credit'].'"/>
				</p>
				<p>
					<label for="ext">Extension</label>
					<input type="text" name="ext" id="ext" value="'.$image['ext'].'"'.$ext_edit.'/>
				</p>
				<p>
					<label for="mime">Mime type</label>
					<input type="text" name="mime" id="mime" value="'.$image['mime'].'"'.$mime_edit.'/>
				</p>
				<p>
					<label for="size">Size (bytes)</label>
					<input type="text" name="size" id="size" value="'.$image['size'].'" readonly="readonly"/>
				</p>
				<p>
					<label for="width">Width (pixels)</label>
					<input type="text" name="width" id="width" value="'.$image['width'].'" readonly="readonly"/>
				</p>
				<p>
					<label for="height">Height (pixels)</label>
					<input type="text" name="height" id="height" value="'.$image['height'].'" readonly="readonly"/>
				</p>
				<p>
					<span class="note">
					<input type="hidden" name="image_id" value="'.$image['id'].'"/>';
				if(empty($image['id'])) {
					$main_content .= '<input type="submit" name="insert" value="insert details" />';
				} else {
					$main_content .= '<input type="submit" name="update" value="update details" />';
				}
				$main_content .= '
					</span>
				</p>
			</form>';
	
	// show full size image
			
		if(file_exists(WW_ROOT.'/ww_files/images/'.$image['filename'])) {
			
			$main_content .= '
			<div class="image_preview" style="'.$preview_style.'">
				<a id="full_image"></a>
				<img style="float: left;" alt="'.$image['alt'].'" title="'.$image['title'].'" src="'.$image['src'].'"/>
			</div>
			';
			
		}
		
	// show image usage (i.e. which articles it appears in
		
		$main_content .= '	
			<hr />
			<h4><a id="usage"></a>usage</h4>';
			
			if(empty($usage)) {
				$main_content .= '
				<p>This image isn\'t currently used in any articles.</p>';
			}
	
	// form to replace main image
		
		$main_content .= '
			<hr />
			
			<h4><a id="replace_image"></a>replace image</h4>
			
			<p>The image width field defaults to the width of the current image - if your new image has different dimensions to the current one you should edit any articles that use it in order to prevent the images displaying incorrectly.</p>
			
			<form action="'.$action_url.'" method="post" enctype="multipart/form-data" id="image_replace_form">
				<p>
					<label for="new_image">Image:</label>
					<input name="new_image" name="new_image" type="file" size="12" id="new_image"/>
				</p>
				<p>
					<label for="image_width">Image width:</label>
					<input name="image_width" id="image_width" type="text" style="width:40px;" value="'.$image['width'].'"/> px
				</p>
				<p>
					<span class="note">
					<input name="current_image" type="hidden" value="'.$image['filename'].'"/>
					<input type="submit" name="replace_image" value="replace" />
					</span>
				</p>
			</form>	';
			
	// form to replace thumbnail image
		
		$main_content .= '		
			<hr />
				
			<h4><a id="replace_thumbnail"></a>replace thumbnail</h4>
			
			<p>If you prefer not to use the automatically generated thumbnail you can upload your own thumbnail for this image below.</p>
			
			<form action="'.$action_url.'" method="post" enctype="multipart/form-data" id="thumb_replace_form">
				<p>
					<label for="new_thumb">Thumbnail:</label>
					<input name="new_thumb" name="new_thumb" type="file" id="new_thumb"/>
				</p>
				<p>
					<span class="note">
					<input name="current_thumb" type="hidden" value="'.$image['filename'].'"/>
					<input type="submit" name="replace_thumb" value="replace" />
					</span>
				</p>
			</form>

			';
	
	// button to delete image
			
		$main_content .= '
			<hr />
			<h4><a id="delete_image"></a>delete image</h4>';
		
		if(!empty($usage)) {
			
			$main_content .= '
			<p>WARNING: you should delete the image from the articles that are currently using it prior to deletion.</p>';
		}
		
		$main_content .= '
			<form action="'.$action_url.'" method="post" id="delete_image_form">
				<p>
					<span class="note">
					<input name="action" type="submit" value="delete"/>
					</span>
				</p>
			</form>
			';

// show image list if no single image selected
			
	} else {
		
	// also need to check for rogues and orphans
		
		if(isset($orphans)) {
			
			if(isset($orphans['files'])) {

				$main_content .= '
				<h4>rogue files</h4>
				<p>The following rogue images were found in the <em>/ww_files/images/</em> folder (you can either destroy these without mercy or add them to the database - click on the filename to take action):
					<ul>';
				foreach($orphans['files'] as $orphan) {
					$main_content .= '
					<li>
						<a href="'.$_SERVER["PHP_SELF"].'?page_name=images&amp;filename='.$orphan.'">
							'.$orphan.'
						</a>
					</li>';
				}
				$main_content .= '
				</ul>
				';
			}
			
			if(isset($orphans['db'])) {
				$main_content .= 
				'<h4>orphaned database entries</h4>
				<p>There are orphaned entries in the database (too sad!). They are highlighted below - you should either delete these pitiful entries or re-upload the files.</p>';
			}
			
		}
		
	// show all images in a grid

		foreach($images as $img) {

			$file_check = WW_ROOT.'/ww_files/images/'.$img['filename'];
			$notfound = (!file_exists($file_check)) ? ' notfound' : '' ;
			
			$main_content .= '
			<div class="gallery_item">
				<div class="thumb'.$notfound.'">
					<a href="'.$_SERVER["PHP_SELF"].'?page_name=images&amp;image_id='.$img['id'].'">
					<img src="'.WW_WEB_ROOT.'/ww_files/images/thumbs/'.$img['filename'].'"/>
					</a>
				</div>
				<div class="caption">
					<span class="img_title">
						<a href="'.$_SERVER["PHP_SELF"].'?page_name=images&amp;image_id='.$img['id'].'">
							'.$img['title'].'
						</a>
					</span>
					<span class="img_size">'.$img['width'].' x '.$img['height'].'</span>
				</div>
			</div>';
		}
	
	// show page navigation
		
		if($total_images > $images_per_page) {
			$main_content .=  show_admin_listing_nav($total_pages, $images_per_page);
		} 
		
	}


// output aside content - into $aside_content variable

	$quicklinks = '
			<ul>
				<li><a href="'.$_SERVER["PHP_SELF"].'?page_name=files">Files</a></li>
				<li><a href="'.$_SERVER["PHP_SELF"].'?page_name=images">Images</a></li>
				<li><a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments">Attachments</a></li>
			</ul>';

	$aside_content = build_snippet('Quick Links', $quicklinks);	

	// upload an image	
	
	$aside_content .= '
	<div class="snippet">
		<h6>Upload new image</h6>';
	
	if(isset($upload_status)) {
		$aside_content .= '<p>'.$upload_status.'</p>';
	}
	
	$max_size = (!empty($config['files']['max_image_size'])) ? get_kb_size($config['files']['max_image_size']).'kb' : 'none' ;
	$max_width = (!empty($config['files']['max_image_width'])) ? $config['files']['max_image_width'].'px (wider images will be resized)' : 'none' ;
	
	$aside_content .= '
	
		<form action="'.$action_url.'" method="post" enctype="multipart/form-data" id="upload_image_form">
		
		<p><label for="image_file">Select image</label>
			<input name="image_file" type="file" size="12" id="image_file"/></p>
			
		<p><label for="width">Image width (optional)</label> 
			<input name="width" type="text" style="width:40px;" />px
		</p>
		<!--			
		<p><label for="thumb_file">Thumbnail (leave blank to auto-generate thumbnail)</label>
			<input name="thumb_file" type="file" size="12" id="thumb_file"/></p>

		<p><label for="thumb_width">Thumb width (optional)</label> 
			<input name="thumb_width" type="text" style="width:40px;" value="'.$config['files']['thumb_width'].'"/>px
		</p>
		-->
		<p><label for="title">Image title (optional)</label> 
			<input name="title" type="text" size="24"/></p>
		
		<p><label for="alt">Alt text (optional)</label> 
			<input name="alt" type="text" size="24"/></p>
			
		<p><label for="caption">Caption (optional)</label> 
			<input name="caption" type="text" size="24"/></p>
			
		<p><label for="credit">Credit (optional)</label> 
			<input name="credit" type="text" size="24"/></p>
			
		<p>
			<label>Max image size: '.$max_size.'</label>
		</p>
		<p>
			<label>Max image width: '.$max_width.'</label>
		</p>
		
		<p><input type="submit" name="upload_image" value="upload" /></p>
	</form>
	</div>';
?>