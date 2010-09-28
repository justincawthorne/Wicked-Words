<?php
	if(!defined('WW_SESS')) {
		exit();
	}
// page title - if undefined the site title is displayed by default

	$page_title = 'CSS Editor';

// get main data
	
	$edit_theme = (isset($_GET['theme'])) ? $_GET['theme'] : $config['site']['theme'] ;
	$edit_theme = clean_start_slash($edit_theme);
	
	$file = (isset($_GET['file'])) ? $_GET['file'] : '' ;	
	
	$theme_dir = WW_ROOT.'/ww_view/themes/'.$edit_theme.'/';
	$file_path = $theme_dir.$file;
	
	if(isset($_GET['create_file'])) {
		$file_path = $theme_dir.$_GET['create_file'];
	}
	

// process post actions

	// create backup

	if( (isset($_POST['backup_file'])) && ($_POST['backup_file'] == 'backup') ) {
		

			$pathinfo = pathinfo($file_path);
			$filename = $pathinfo['filename'];
			$bu_filename = $filename.'-'.time();
			$backup = str_replace($filename, $bu_filename, $file_path);
			$bu_fh = fopen($backup, 'w');
			if(!$bu_fh) {
				$error = "Couldn't create file - check CHMOD permissions";
			} else {
				fclose($bu_fh);
				header('Location: '.$url);
			}			
		
	}
	
	// update file
		
	if( (isset($_POST['update_file'])) && ($_POST['update_file'] == 'update') ) {
	
		$fh = fopen($file_path, 'w');
		if(!$fh) {
			$error = "File couldn't be opened for writing - check CHMOD permissions";
		} else {
			$towrite = $_POST['edit_file'];
			fwrite($fh, $towrite);
			fclose($fh);
			header('Location: '.$url);			
		}


	}
	
	// create file
		
	if( ( (isset($_POST['create_file'])) && ($_POST['create_file'] == 'create') ) || (isset($_GET['create_file'])) ) {
		
	
		if(file_exists($file_path)){
			$error = "File already exists";
		} else {
			$new_file_name = (isset($_GET['create_file'])) ? $_GET['create_file'] : $_POST['new_file'] ;
			$new_file_name = str_replace(' ', '-', $new_file_name);
			if (substr($new_file_name,(0-3)) != 'css') {
				$new_file_name = $new_file_name.'.css';
			}
			$new_file = $theme_dir.$new_file_name;
			$new_fh = fopen($new_file, 'w');
			if(!$new_fh) {
				$error = "Couldn't create file - check CHMOD permissions";
			} else {
				fclose($new_fh);
				$reload = $_SERVER["PHP_SELF"].'?page_name=editor&theme='.$edit_theme.'&file='.$new_file_name;
				header('Location: '.$reload);
			}			
		}
	}	



	
	if(empty($file)) {

		$left_text = 'Browsing: '.$edit_theme;
		$right_text = 'no file selected';
		
		$page_header = show_page_header($left_text, $right_text);
		
		$main_content = $page_header;
		$main_content .= '<p>No file selected: choose a file to edit from the right hand menu.</p>';
		
	} else {
		
		$left_text = 'Editing: '.$edit_theme;
		$right_text = $file;
		
		$page_header = show_page_header($left_text, $right_text);
		
		$main_content = $page_header;
		
		// get file contents for editing
		
		$file_html = file_get_contents($file_path);
		
		
	// show errors
	
		if( (isset($error)) && (!empty($error)) ) {
			$main_content .= '
			<p><strong>'.$error.'</strong></p>';
		}
		
		if (!is_writable($file_path)) {
			$main_content .= '
			<h2>The selected file is not writable!</h2>';			
		}
	
	// show form
		
		$main_content .= '
				<!--<p><strong>path:</strong> '.$file_path.'</p>-->
				
				<h4>edit file</h4>
				
				<form action="'.$action_url.'" method="post" id="edit_file_form">

				<p>
					<span class="note">
					you should backup your file before making any changes: <input name="backup_file" value="backup" type="submit"/>
					</span>
				</p>

				<p>
					<label for="edit_file">'.$file.'</label>
					<textarea name="edit_file" cols="28" rows="30" style="width:480px;">'.$file_html.'</textarea>
				</p>
				
				<p>
					<span class="note">
					<input name="update_file" value="update" type="submit"/>
					</span>
				</p>
				</form>
		';
		
	}


// get aside content
	
	$list_files = '';
	$theme_dir = WW_ROOT.'/ww_view/themes/'.$edit_theme.'/';
	$theme_files = get_files($theme_dir,'css');
	sort($theme_files);
	if(!empty($theme_files)) {
		$list_files = '
		<ul>';
		foreach($theme_files as $th_f) {
			$file_link = $_SERVER["PHP_SELF"].'?page_name=editor&amp;theme='.$edit_theme.'&amp;file=';
			$list_files .= '
			<li>
				<a href="'.$file_link.$th_f['filename'].'">'.$th_f['filename'].'</a> ('.$th_f['size'].'b)
			</li>';
		}
		$list_files .= '
		</ul>';
	}
	
	$list_themes = '';
	$themes_dir = WW_ROOT."/ww_view/themes/";
	$themes = get_folders($themes_dir);
	if(!empty($themes)) {
		$list_themes .= '<ul>';
		foreach($themes as $th) {
			$theme_link = $_SERVER["PHP_SELF"].'?page_name=editor&amp;theme='.$th;
			$list_themes .= '
			<li>
				<a href="'.$theme_link.'">'.$th.'</a>
			</li>';
		}
		$list_themes .= '</ul>';
	}
	
	$create_file = '
	<p>Create a new css file for '.$edit_theme.'</p>
		<form action="'.$action_url.'" method="post" name="create_file_form">
		<p><label for="new_file">File name:</label> 
			<input name="new_file" type="text" size="24">
		</p>
		<p>
			<input type="submit" name="create_file" value="create">
		</p>
		</form>
	';
	
	// output aside content

	$aside_content = build_snippet('Files for '.$edit_theme,$list_files);
	$aside_content .= build_snippet('Create new file',$create_file);
	$aside_content .= build_snippet('Themes',$list_themes);
	
	$aside_content .= '
		<h4>Suggestions</h4>
		<p>You can also create the following page and category specific stylesheets:</p>';
	
	// get a list of page names
	
	$page_names = array('feed','front','404','article','listing');
	$page_name_css = array();
	foreach($page_names as $page_name){
		if(!file_exists(WW_ROOT.'/ww_view/themes/'.$edit_theme.'/'.$page_name.'.css')) {
			$new_link = $_SERVER["PHP_SELF"].'?page_name=editor&amp;theme='.$edit_theme.'&amp;create_file=';
			$page_name_css[] = array(
				'title' => $page_name.'.css',
				'link' => $new_link.$page_name.'.css'
			);
		}
	}
	
	$aside_content .= build_snippet('',$page_name_css);

	// get a list of categories
	
	$categories_list = get_categories_basic();
	
	$category_css = array();
	foreach($categories_list as $category){
		if(!file_exists(WW_ROOT.'/ww_view/themes/'.$edit_theme.'/'.$category.'.css')) {
			$new_link = $_SERVER["PHP_SELF"].'?page_name=editor&amp;theme='.$edit_theme.'&amp;create_file=';
			$category_css[] = array(
				'title' => $category.'.css',
				'link' => $new_link.$category.'.css'
			);
		}
	}
	
	$aside_content .= build_snippet('',$category_css);
?>