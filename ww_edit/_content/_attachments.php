<?php
	if(!defined('WW_SESS')) {
		exit();
	}
// page title - if undefined the site title is displayed by default

	$page_title = 'Attachments';

// upload attachment
	
	if( (isset($_POST['upload_attachment'])) && ($_POST['upload_attachment'] == 'upload') ) {
		
		$upload_status = insert_attachment();
		if(is_int($upload_status)) {
			header('Location: '.$_SERVER["PHP_SELF"].'?page_name=attachments&attachment_id='.$upload_status);
		} else {
			$error = $upload_status;
		}
		
	}
	
// update attachment details

	if( (isset($_POST['update'])) && ($_POST['update'] == 'update details') ) {
		
		$attachment_id = (int)$_POST['attachment_id'];
		if(!empty($attachment_id)) {
			$update_status = update_attachment($attachment_id);
			if($update_status == true) {
				header('Location: '.$url);
			} else {
				$error = $update_status;
			}			
		}
	
	}

// insert attachment details -  for rogue attachments

	if( (isset($_POST['insert'])) && ($_POST['insert'] == 'insert details') ) {
		
		$insert_status = insert_attachment_details($_POST);
		if(is_int($insert_status)) {
			header('Location: '.$_SERVER["PHP_SELF"].'?page_name=attachments&attachment_id='.$insert_status);
		} else {
			$error = $insert_status;
		}			
	}
	
// replace attachment

	if(isset($_POST['replace_attachment'])) {
		
		if( (isset($_FILES['new_attachment'])) && (empty($_FILES['new_attachment']['error'])) ) {
			// current file details
			$current 	= $_POST['current_attachment'];
			$current_path = pathinfo($current);
			$current_ext = $current_path['extension'];
			// location
			$location 	= WW_ROOT.'/ww_files/attachments/'.$current_ext.'/';
			// new file details
			$new_file	= $_FILES['new_attachment'];
			$replace_status = replace_attachment($location, $new_file, $current);
			if($replace_status === true) {
				header('Location: '.$url);
			} else {
				$error = $replace_status;
			}
		}
		
	}
	
// confirm delete attachment
	
	if( (isset($_POST['confirm_delete_attachment'])) && ($_POST['confirm_delete_attachment'] == 'Yes') ) {

		$attachment_delete = delete_attachment($_POST['filename'],$_POST['ext']);
		
		if(!empty($attachment_delete)) {
			header('Location: '.WW_WEB_ROOT.'/ww_edit/index.php?page_name=attachments');
		} else {
			$error = $attachment_delete;
		}

	}
	
// cancel delete attachment
	
	if( (isset($_POST['cancel_delete_attachment'])) && ($_POST['cancel_delete_attachment'] == 'No') ) {

		$attachment_id = (int)$_POST['attachment_id'];
		header('Location: '.WW_WEB_ROOT.'/ww_edit/index.php?page_name=attachments&attachment_id='.$attachment_id);

	}

// delete folder

	if(isset($_POST['remove_folder'])) {
		
		if(!empty($_GET['ext'])) {
			$rm_folder = WW_ROOT.'/ww_files/attachments/'.$_GET['ext'].'/';
			rmdir($rm_folder);	
			header('Location: '.WW_WEB_ROOT.'/ww_edit/index.php?page_name=attachments');		
		}

	}
	

// process our params and get main content

	$attachment_id = (isset($_GET['attachment_id'])) ? (int)$_GET['attachment_id'] : 0 ;
	
	if(isset($_GET['filename'])) {
		$filepath = WW_ROOT.'/ww_files/attachments/'.$_GET['filename'].'/'.$_GET['filename'];
		if(file_exists($filepath)) {
			$filename = $_GET['filename'];
		}
	}
	
// a single attachment
		
	if(!empty($attachment_id)) {
		
		$attachment = get_attachment($attachment_id);
		
		// file usage
		
		$usage = attachment_usage($attachment_id);

		// default to list if requested attachment not found

		if(empty($attachment)) {
			$attachments = get_attachments();	
		}

	} elseif(isset($_GET['filename'])) {
		
		// this is a rogue file
		$rogue_path = WW_ROOT.'/ww_files/attachments/'.$_GET['ext'].'/'.$_GET['filename'];
		
		$attachment = get_file_details($rogue_path);
		$attachment['src'] = WW_WEB_ROOT.'/ww_files/attachments/'.$_GET['ext'].'/'.$_GET['filename'];
		
		// file usage
		
		$usage = attachment_usage($attachment_id);
		
	} else {
		
	// if no single attachment is found/requested we return a list

		$attachments = get_attachments();

		// get total attachments and total pages
		
		$total_files = (!empty($attachments)) ? $attachments[0]['total_files'] : 0 ;
		$total_pages = (!empty($attachments)) ? $attachments[0]['total_pages'] : 0 ;
		
		// check for rogues and orphans
		
		if(isset($_GET['ext'])) {
			$orphans = get_attachment_orphans($_GET['ext']);
		} else {
			$rogue = get_files(WW_ROOT.'/ww_files/attachments/');
			if(!empty($rogue)) {
				foreach($rogue as $name){
					$orphans['files'][] = $name['filename'];
				}
			}
		}
	}
	
	
// construct page header

	$left_text = '<a href="'.$_SERVER["PHP_SELF"].'?page_name=files">files</a>';
	// attachments link
	
	// extension / optional link
	if(isset($attachments)) {
		
		if(isset($_GET['ext'])) {
			$left_text .= ': <a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments">attachments</a>';
			$left_text .= ': '.$_GET['ext'];
		} else {
			$left_text .= ': attachments';
		}
		
	} else {
		$left_text .= ': <a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments">attachments</a>';
		$left_text .= ': <a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments&amp;ext='.$attachment['ext'].'">'.$attachment['ext'].'</a>';
	}
	$right_text = (isset($attachment)) ? $attachment['filename'] : $total_files.' found' ;
	$page_header = show_page_header($left_text, $right_text);



// output main content - into $main_content variable

	$main_content = $page_header;
	
	if(isset($orphans)) {
		if(isset($orphans['files'])) {
			$current_folder = (isset($_GET['ext'])) ? '/ww_files/attachments/'.$_GET['ext'] : '/ww_files/attachments/' ;
			$link_path = $_SERVER["PHP_SELF"].'?page_name=attachments&amp;ext='.$_GET['ext'].'&amp;filename=';
			$main_content .= 
			'
			<h4>rogue files</h4>
			<p>The following rogue files were found in the <em>'.$current_folder.'</em> folder (you can either destroy these without mercy or add them to the database - click on the filename to take action):
				<ul>';
			foreach($orphans['files'] as $orphan) {
				$main_content .= '
				<li>
					<a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments&amp;ext='.$_GET['ext'].'&amp;filename='.$orphan.'">
						'.$orphan.'
					</a>
				</li>';
			}
			$main_content .= '
			</ul>
			';
		}
		if(isset($orphans['db'])) {
			$main_content .=  '
			<h4>orphaned database entries</h4>
			<p>There are orphaned entries in the database (too sad!). They are highlighted below - you should either delete these pitiful entries or re-upload the files.</p>';
		}
	}
	
	if(isset($attachments)) {
		
		if( (empty($attachments)) && (!isset($orphans['files'])) && (isset($_GET['ext'])) ) {
			$main_content .= '
			<h4>Oh noes - this appears to be an empty folder! Would you like to delete it?</h4>
			
			<form action="'.$action_url.'" method="post" id="delete_folder_form">
				<p>
					<span class="note">
					<input name="remove_folder" type="submit" value="yes - make this an ex-folder"/>
					</span>
				</p>
			</form>
			';
		} else {
			
			$main_content .= build_file_listing($attachments);
		}
		
	} else {

	// confirm file delete
		
		if( (isset($_GET['action'])) && ($_GET['action'] == 'delete') ) {

			$main_content .= '
				<h2>Are you sure you want to delete this attachment?</h2>
				<form action="'.$action_url.'" method="post" name="confirm_delete_attachment_form">
					<input name="filename" type="hidden" value="'.$attachment['filename'].'"/>
					<input name="ext" type="hidden" value="'.$attachment['ext'].'"/>
					<input name="attachment_id" type="hidden" value="'.$attachment['id'].'"/>
					<input name="confirm_delete_attachment" type="submit" value="Yes"/>
					<input name="cancel_delete_attachment" type="submit" value="No" />
				</form>
				';
			
		}
		
	// jump options:
		
		$main_content .= '
		<p><strong> Jump to:</strong>
			<a href="#usage">attachment usage</a> &#124;
			<a href="#replace">replace attachment</a> &#124;
			<a href="#delete">delete attachment</a>
		</p>';
		
	// show errors
	
		if( (isset($error)) && (!empty($error)) ) {
			$main_content .= '
			<p><strong>'.$error.'</strong></p>';
		}
		
		if(!file_exists(WW_ROOT.'/ww_files/attachments/'.$attachment['ext'].'/'.$attachment['filename'])) {
			$main_content .= '
			<p><strong>File missing!</strong> This attachment cannot be found in the attachments/'.$attachment['ext'].' folder - it will either need to be replaced or deleted from the database</p>';
		}
		
		// main attachment details
		
		$mime_edit = (!empty($attachment['mime'])) ? ' readonly="readonly"' : '' ;
		$ext_edit = (!empty($attachment['ext'])) ? ' readonly="readonly"' : '' ;
		
		$summary = (isset($attachment['summary'])) ? $attachment['summary'] : '' ;
		$downloads = (isset($attachment['downloads'])) ? $attachment['downloads'] : 0 ;
		
		$main_content .= '
			<hr />
			<h4>file details</h4>
			
			<p>This file was uploaded by '.$attachment['author_name'].' on '.from_mysql_date($attachment['date_uploaded']).' &#124; 
			<a href="'.$attachment['src'].'">download</a></p>
			<form id="attachment_details" method="post" action="'.$url.'">
				<p>
					<label for="title">Title</label>
					<input type="text" name="title" value="'.$attachment['title'].'"/>
				</p>
				<p>
					<label for="summary">Description</label>
					<textarea name="summary">'.$summary.'</textarea>
				</p>
				<p>
					<label for="filename">Filename</label>
					<input type="text" name="filename" value="'.$attachment['filename'].'" readonly="readonly"/>
				</p>
				<p>
					<label for="ext">Extension</label>
					<input type="text" name="ext" value="'.$attachment['ext'].'"'.$ext_edit.'/>
				</p>
				<p>
					<label for="mime">Mime type</label>
					<input type="text" name="mime" value="'.$attachment['mime'].'"'.$mime_edit.'/>
				</p>
				<p>
					<label for="size">Size (bytes)</label>
					<input type="text" name="size" value="'.$attachment['size'].'" readonly="readonly"/>
				</p>
				<p>
					<label for="downloads">Downloads</label>
					<input type="text" name="downloads" value="'.$downloads.'" readonly="readonly"/>
				</p>
				<p>
					<span class="note">
					<input type="hidden" name="attachment_id" value="'.$attachment['id'].'" />';
				if(empty($attachment['id'])) {
					$main_content .= '<input type="submit" name="insert" value="insert details" />';
				} else {
					$main_content .= '<input type="submit" name="update" value="update details" />';
				}
				$main_content .= '
				</span>
				</p>
			</form>';
			
		// attachment usage
		
		$main_content .= '	
			<hr />
			
			<h4><a id="usage"></a>usage</h4>';
			$usage_total = count($usage);
			$article_link = $_SERVER["PHP_SELF"].'?page_name=write&amp;article_id=';
			switch($usage_total) {
				
				case 0;
				$main_content .= '
				<p>This file isn\'t currently attached to any articles</p>';
				break;
				
				case 1;
				$main_content .= '
				<p>This file is attached to one article: <a href="'.$article_link.$usage[0]['article_id'].'">'.$usage[0]['title'].'</a></p>';
				break;
				
				default;
				$main_content .= '
				<p>This file is attached to '.$usage_total.' articles:</p>
					<ul>';
					foreach($usage as $used) {
						$main_content .= '
						<li><a href="'.$article_link.$used['article_id'].'">'.$used['title'].'</a></li>';
					}
				$main_content .= '
					</ul>';
				break;
			}

	// form to replace thumbnail image
		
		$main_content .= '		
			<hr />
				
			<h4><a id="replace"></a>replace attachment</h4>

			<form action="'.$action_url.'" method="post" enctype="multipart/form-data" id="attachment_replace_form">
				<p>
					<label for="new_attachment">New file:</label>
					<input name="new_attachment" id="new_attachment" type="file"/>
				</p>
				<p>
					<span class="note">
					<input name="current_attachment" type="hidden" value="'.$attachment['filename'].'"/>
					<input type="submit" name="replace_attachment" value="replace" />
					</span>
				</p>
			</form>

			';


	// button to delete file
			
		$main_content .= '
			<hr />
			<h4><a id="delete"></a>delete attachment</h4>';
		
		if(!empty($usage)) {
			
			$main_content .= '
			<p>NOTE: this will also remove this file from the articles it is currently attached to...</p>';
		}
		
		$main_content .= '
			<form action="'.$action_url.'" method="get" id="delete_attachment_form">
				<p>
					<span class="note">
					<input type="hidden" name="page_name" value="attachments"/>
					<input type="hidden" name="attachment_id" value="'.$attachment['id'].'"/>
					<input name="action" type="submit" value="delete"/>
					</span>
				</p>
			</form>
			';

	}

// output aside content - into $aside_content variable

	$quicklinks = '
			<ul>
				<li><a href="'.$_SERVER["PHP_SELF"].'?page_name=files">Files</a></li>
				<li><a href="'.$_SERVER["PHP_SELF"].'?page_name=images">Images</a></li>
				<li><a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments">Attachments</a></li>
			</ul>';

	$aside_content = build_snippet('Quick Links', $quicklinks);	

	// attachment subfolders
	
	$attachment_folders = get_folders(WW_ROOT."/ww_files/attachments");
	
	if(!empty($attachment_folders)) {
		
		$aside_content .= '
		<div class="snippet">
		<h6>Attachment folders</h6>
		<ul>';
		
		foreach($attachment_folders as $folder) {
			$aside_content .= '
			<li>
				<a href="'.$_SERVER["PHP_SELF"].'?page_name=attachments&amp;ext='.$folder.'">'.$folder.'</a>
			</li>';
		}
		
		$aside_content .= '
		</ul>
		</div>';
	}	
	
	// upload an attachment
	
	$aside_content .= '
		<div class="snippet">
		<h6>Upload new attachment</h6>';
	
	if(isset($upload_success)) {
		$aside_content .= '<p>'.$upload_success.'</p>';
	}
	
	$aside_content .= '
		<form action="'.$url.'" method="post" enctype="multipart/form-data" id="attachment_form">
		
			<p><label for="attachment_file">Select file</label>
				<input name="attachment_file" type="file" size="12" id="attachment_file"/></p>
			
			<p><label for="title">Attachment title (optional)</label> 
				<input name="title" type="text" name="title" size="24"/></p>
			
			<p><label for="summary">Description (optional)</label> 
				<textarea name="summary"></textarea></p>
			<p>			
				<input type="submit" name="upload_attachment" value="upload" /></p>
		</form>
	</div>';
?>