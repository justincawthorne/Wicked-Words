<?php
	if(!defined('WW_SESS')) {
		exit();
	}
// page title - if undefined the site title is displayed by default

	$page_title = 'Files';
	
// create a folder

	if(isset($_POST['create_folder'])) { 
		if (!empty($_POST['new_folder'])) {
			$new_folder = create_url_title($_POST['new_folder']);
		}
		$new_folder = WW_ROOT.'/ww_files/'.$new_folder;
		mkdir($new_folder, 0755);
	}
	
// upload file to custom folder

	if(isset($_POST['upload_file'])) {
		
		$file = $_FILES['new_file'];
		$location = WW_ROOT.'/ww_files/'.$_POST['folder'];
		$uploaded = upload_file($file, $location);
		if(is_array($uploaded)) {
			header('Location: '.$_SERVER["PHP_SELF"].'?page_name=files&folder='.$folder);
		} else {
			$error = $uploaded;
		}
	}
	
// delete file

	if( (isset($_POST['confirm_delete_file'])) && ($_POST['confirm_delete_file'] == 'Yes') ) {
		
		$location = WW_ROOT.'/ww_files/'.$_POST['folder'].'/'.$_POST['filename'];
		if(file_exists($location)) {
			unlink($location);
			header('Location: '.$_SERVER["PHP_SELF"].'?page_name=files&folder='.$_POST['folder']);
		}	
		
	}

	// cancel delete file
		
	if( (isset($_POST['cancel_delete_file'])) && ($_POST['cancel_delete_file'] == 'No') ) {
	
		header('Location: '.$_SERVER["PHP_SELF"].'?page_name=files');

	}

// delete folder

	if(isset($_POST['remove_folder'])) {
		
		if(!empty($_GET['folder'])) {
			$rm_folder = WW_ROOT.'/ww_files/'.$_GET['folder'].'/';
			rmdir($rm_folder);	
			header('Location: '.WW_WEB_ROOT.'/ww_edit/index.php?page_name=files');		
		}

	}	


// show a 'portal' page if no folder is selected

	if(!isset($_GET['folder'])) {	
		
		// list attachment folders
		
		$attachment_folders = get_folders(WW_ROOT."/ww_files/attachments/");
		
		// create link list of custom folders
		
		$file_folders = get_folders(WW_ROOT."/ww_files/");
		$exclude = array('_cache','attachments','images');
		$custom_folders = array();
		foreach($file_folders as $ff) {
			if(!in_array($ff, $exclude)) {
				$custom_folders[] = array(
					'title' => $ff,
					'link' => $_SERVER["PHP_SELF"].'?page_name=files&amp;folder='.$ff
				);
			}
		}
		$right_text = 'files home';
				
	} elseif(isset($_GET['folder'])) {
		
		$files = get_files(WW_ROOT.'/ww_files/'.$_GET['folder'].'/');
		$total_files = count($files);
		$right_text = $total_files.' found';
		
	} elseif(isset($_GET['filename'])) {
		
		$file_details = get_file_details(WW_ROOT.'/ww_files/'.$_GET['folder'].'/'.$_GET['filename']);
		$right_text = $_GET['filename'];
		
	}


// construct page header
	
	$left_text = '<a href="'.$_SERVER["PHP_SELF"].'?page_name=files">files</a>';
	if(isset($_GET['folder'])) {
		$left_text .= (isset($_GET['filename'])) 
				? ': <a href="'.$_SERVER["PHP_SELF"].'?page_name=files&amp;folder='.$_GET['folder'].'">'.$_GET['folder'].'</a>' 
				:  ': '.$_GET['folder'] ;
	}
	
	$page_header = show_page_header($left_text, $right_text);

// output main content

	$main_content = $page_header;

	
	if(!isset($_GET['folder'])) {
		
	// if no folder selected then summarise all folders
		
		// images
		
		$main_content .= '
		
		<h4>images</h4>
		
			<p><a href="'.$_SERVER["PHP_SELF"].'?page_name=images">Manage your images</a></p>';
			
		// attachments
		
		$main_content .= '	
		
		<hr />
		<h4>attachment folders</h4>';
		if(!empty($attachment_folders)) {
			$main_content .= '<ul>';
			foreach($attachment_folders as $folder) {
				$main_content .= '
				<li>
					<a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments&amp;ext='.$folder.'">'.$folder.'</a>
				</li>';
			}
			$main_content .= '</ul>';
		} else {
			$main_content .= '<p>No attachments have been uploaded</p>';
		}
		
		// custom folders
		
		$main_content .= '
		<hr />
		<h4>custom folders</h4>';
		if(!empty($custom_folders)) {
			$main_content .= '<ul>';
			foreach($custom_folders as $custom_folder) {
				$main_content .= '
				<li>
					<a href="'.$custom_folder['link'].'">'.$custom_folder['title'].'</a>
				</li>';
			}
			$main_content .= '</ul>';
		} else {
			$main_content .= '<p>No custom folders have been created</p>';
		}
		
	} else {
		
		// get single files details if filename set via url

		if(isset($_GET['filename'])) {
	
			$file_details = get_file_details(WW_ROOT.'/ww_files/'.$_GET['folder'].'/'.$_GET['filename']);
			
			if( (isset($_GET['action'])) && ($_GET['action'] == 'delete') ) {
	
				$main_content .= '
					<h2>Are you sure you want to delete this file?</h2>
					<form action="'.$action_url.'" method="post" name="confirm_delete_file_form">
						<input name="filename" type="hidden" value="'.$file_details['filename'].'"/>
						<input name="folder" type="hidden" value="'.$_GET['folder'].'"/>
						<input name="confirm_delete_file" type="submit" value="Yes"/>
						<input name="cancel_delete_file" type="submit" value="No" />
					</form>
					';
				
			}
			
			$main_content .= "
				<hr />
				<h4>file details</h4>
				
				<ul>
					<li><strong>filename: </strong><a href=\"".$file_details['path']."\">".$file_details['filename']."</a></li>
					<li><strong>size: </strong>".get_kb_size($file_details['size'])."</li>
					<li><strong>date uploaded: </strong>".date('d F Y',$file_details['date_uploaded'])."</li>
				</ul>";
	
		// get file listing if a folder name is set via url
	
		} elseif(isset($_GET['folder'])) { 
			

			if(!empty($files)) {
				
				$main_content .= build_file_listing($files);
				
			} else {
			
				$main_content .= '<h4>Aha! An empty folder... wanna delete it?
				
				<form action="'.$action_url.'" method="post" id="delete_folder_form">
					<p>
						<span class="note">
						<input name="remove_folder" type="submit" value="yes - make this an ex-folder"/>
						</span>
					</p>
				</form>
				';
				
			}
			
		}
		
	}
	
// output aside content - into $aside_content variable

	$quicklinks = '
			<ul>
				<li><a href="'.$_SERVER["PHP_SELF"].'?page_name=files">Files</a></li>
				<li><a href="'.$_SERVER["PHP_SELF"].'?page_name=images">Images</a></li>
				<li><a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments">Attachments</a></li>
			</ul>';
	
	// show other folders
	
	$aside_content = build_snippet('Quick Links', $quicklinks);
			
	if(!empty($custom_folders)) {
		
		$aside_content .= build_snippet('Custom folders',$custom_folders);

	}
	
	// upload a file to selected folder
	if(isset($_GET['folder'])) {
		$folder_title = $_GET['folder'];
		$folder_select = 
		'<input type="hidden" name="folder" value="'.$_GET['folder'].'">';
	} else {
		$folder_title = 'custom';
		$folder_select = '
		<p>
		<label for="folder">Select folder:</label>
		<select name="folder">
			<option value="">select...</option>';
		foreach($custom_folders as $cf_select) {
			$folder_select .= '
			<option value="'.$cf_select['title'].'">'.$cf_select['title'].'</option>';
		}
		$folder_select .= '
		</select>
		</p>
		';
	}
	$aside_content .= '
	<div class="snippet">
	
		<h6>upload file to '.$folder_title.' folder</h6>
		
		<form action="'.$_SERVER['PHP_SELF'].'?page_name=files" method="post" enctype="multipart/form-data" id="upload_file_form">
		<p><label for="new_file">Select file: </label>
			<input name="new_file" type="file" size="12">
		</p>
		'.$folder_select.'
		<p>	
		<input type="submit" name="upload_file" value="Upload">
		</p>
		</form>
		
	</div>';
	
	// create a new folder
	
	$aside_content .= '
	<div class="snippet">
	
		<h6>create a new folder</h6>
	
		<form action="'.$_SERVER['PHP_SELF'].'?page_name=files" method="post" name="create_folder_form">
		<p><label for="new_folder">Folder name:</label> 
			<input name="new_folder" type="text" size="24">
		</p>
		<p>
			<input type="submit" name="create_folder" value="Create">
		</p>
		</form>
	
	</div>
	';

?>