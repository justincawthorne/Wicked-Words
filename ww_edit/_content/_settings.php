<?php
	


// page title - if undefined the site title is displayed by default

	$page_title = 'Settings';


// get main content

	// clear the cache
	
	if(isset($_GET['clearcache'])) {
	
		$cache_cleared = clear_cache();
		$url = str_replace('&clearcache','',$url);
		$action_url = str_replace('&amp;clearcache','',$action_url);
	}

	// update settings
	
	$messages = '';
	if(isset($_POST['update'])) {

		foreach($_POST as $element => $value) {
			
			if($element == 'update') {
				continue;
			} else {
				$conn = author_connect();
				$update = "
					UPDATE settings 
					SET property_value='".$conn->real_escape_string($value)."' 
					WHERE property_name LIKE '".$conn->real_escape_string($element)."'";

				$result = $conn->query($update);
				if(!$result) {
					$messages = $conn->error;
				} else {
					header('Location: '.$url.$_POST['tab']);
				}
			}
			
		}
	}
	
	// edit custom tag
	
	if(isset($_POST['update_custom_setting'])) {
		$conn = author_connect();
		$custom_update = "
			UPDATE settings SET 
				property_name='".$conn->real_escape_string($_POST['property_name'])."', 
				property_value='".$conn->real_escape_string($_POST['property_value'])."' 
			WHERE id = ".(int)$_POST['id'];
		$custom_result = $conn->query($custom_update);
		if(!$custom_result) {
			$messages = $conn->error;
		}
	}
	
	// insert meta tag	

	if( (isset($_POST['insert_meta'])) || (isset($_POST['insert_custom'])) ) {
			
		if( (!empty($_POST['property_name'])) && (!empty($_POST['property_value'])) ) {
			$conn = author_connect();
			$insert = "
				INSERT INTO settings 
					(element_name, property_name, property_value, formtype, summary)
					VALUES
					(
					'".$conn->real_escape_string($_POST['element_name'])."',
					'".$conn->real_escape_string($_POST['property_name'])."',
					'".$conn->real_escape_string($_POST['property_value'])."',
					'custom',
					'".$conn->real_escape_string($_POST['summary'])."'
					)";
			$result = $conn->query($insert);
			if(!$result) {
				$messages = $conn->error;
			}
		}

	}
	
	// any functions

/**
 * admin_get_settings
 * 
 * 
 * 
 */	
	
	function admin_get_settings() {
		$conn = reader_connect();
		$query = "	SELECT * FROM settings";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row = stripslashes_deep($row);
			$element = strtolower($row['element_name']);
			$data[$element][] = $row;
		}
		return $data;	
 	}

/**
 * insert_meta_form
 * 
 * 
 * 
 */	
 	
 	function insert_meta_form() {
		$form = '
		<h4>insert a new meta tag</h4>
		<form id="meta_insert_form" method="post" action="'.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].'#tab_meta" class="settings_form">
			<p>
				<label for="property_name">name/http-equiv</label>
					<input type="text" name="property_name"/>
					<span class="note">
					The following will automatically be picked up as http-equiv tags (everything else will be rendered as a content meta tag):
					Allow, Content-Encoding, Content-Length, Content-Type, Date, Expires, Last-Modified, Location, Refresh, Set-Cookie, WWW-Authenticate
					</span>				
			</p>
			<p>
				<label for="property_value">content</label>
					<input type="text" name="property_value"/>
					<input type="hidden" name="summary" value="custom meta tag" />
					<input type="hidden" name="element_name" value="meta" />				
			</p>
			<p>
				<span class="note">
				<input type="submit" name="insert_meta" value="insert meta tag" />
				</span>
			</p>
		</form>	';
		return $form;
 	}

/**
 * build_settings_formtype
 * 
 * 
 * 
 */	

	function build_settings_formtype($setting_data) {
		
		// options for themes dropdown
		if($setting_data['property_name'] == 'theme') {
			$theme_dir = WW_ROOT."/ww_view/themes/";
			$themes = get_folders($theme_dir);
			$setting_data['options'] = '/'.implode(',/',$themes);		
		}
		
		switch($setting_data['formtype']) {
		
		// text
			
			case 'text':
			case 'custom':
				$default = (!empty($setting_data['default_value'])) 
					? $setting_data['default_value'] 
					: '[no default]' ;
				$default = ($setting_data['formtype'] == 'custom') ? '[custom field]' : $default ;
				$form_field = '
				<p>
					<label for="'.$setting_data['property_name'].'">
						'.str_replace('_',' ',$setting_data['property_name']).'
					</label>
					<input type="text"
						name="'.$setting_data['property_name'].'" 
						id="'.$setting_data['property_name'].'" 
						value="'.$setting_data['property_value'].'"/>
					<span class="default">'.$default.'</span>
					<span class="note">'.$setting_data['summary'].'</span>
				</p>';
				break;
		
		// textarea
				
			case 'textarea':
				$default = (!empty($setting_data['default_value'])) 
					? $setting_data['default_value'] 
					: '[no default]' ;
					
				$form_field = '
				<p>
					<label for="'.$setting_data['property_name'].'">
						'.str_replace('_',' ',$setting_data['property_name']).'
					</label>
					<textarea name="'.$setting_data['property_name'].'"
						id="'.$setting_data['property_name'].'" 
						cols="50" rows="3">'.$setting_data['property_value'].'</textarea>
					<span class="default">'.$default.'</span>
					<span class="note">'.$setting_data['summary'].'</span>
				</p>';
				break;
		
		// select
					
			case 'select':
				$default = (!empty($setting_data['default_value'])) 
					? $setting_data['default_value'] 
					: '[no default]' ;
					
				$form_field = '
				<p>
					<label for="'.$setting_data['property_name'].'">
						'.str_replace('_',' ',$setting_data['property_name']).'
					</label>
					<select name="'.$setting_data['property_name'].'"
						id="'.$setting_data['property_name'].'" >
							<option value="">select...</option>';
				//options for front article dropdown
				if($setting_data['property_name'] == 'article_id') {
					$articles = get_articles_basic('','','date_uploaded DESC',25);
					foreach($articles as $article) {
						$selected = ($article['id'] == $setting_data['property_value']) 
							? ' selected="selected"' 
							: '' ; 
							
						$form_field .= '
								<option value="'.$article['id'].'"'.$selected.'>'.$article['title'].'</option>';
					}
				} else {							
				// generic options
					$options = explode(",",$setting_data['options']);
					foreach($options as $option) {
						$option = trim($option);
						$selected = ($option == $setting_data['property_value']) ? ' selected="selected"' : '' ; 
						$form_field .= '
								<option value="'.$option.'"'.$selected.'>'.$option.'</option>';
					}
				}		
				$form_field .= '
					</select>
					<span class="default">'.$default.'</span>
					<span class="note">'.$setting_data['summary'].'</span>
				</p>';
				break;
				
		// checkbox
		/*	
			case 'checkbox':
				$checked = (!empty($setting_data['property_value'])) ? ' checked="checked"' : '' ;
				$default = (!empty($setting_data['default_value'])) ? 'yes' : 'no' ;
				$form_field = '
				<p>
					<label for="'.$setting_data['property_name'].'">
						'.str_replace('_',' ',$setting_data['property_name']).'
					</label>
					<span class="checkbox">
					<input type="'.$setting_data['formtype'].'"
						name="'.$setting_data['property_name'].'" 
						id="'.$setting_data['property_name'].'" 
						value="1"'.$checked.'/>
					</span>
					<span class="default">'.$default.'</span>
					<span class="note">'.$setting_data['summary'].'</span>
				</p>';
				break;
		*/		
			case 'checkbox':
				$yes_selected = (!empty($setting_data['property_value'])) ? ' selected="selected"' : '' ;
				$no_selected = (empty($setting_data['property_value'])) ? ' selected="selected"' : '' ;
				$default = (!empty($setting_data['default_value'])) ? 'yes' : 'no' ;
				$opt_class = (!empty($setting_data['property_value'])) ? ' opt_yes' : '' ;
				$form_field = '
				<p>
					<label for="'.$setting_data['property_name'].'">
						'.str_replace('_',' ',$setting_data['property_name']).'
					</label>
					<select name="'.$setting_data['property_name'].'"
						id="'.$setting_data['property_name'].'" class="select_checkbox'.$opt_class.'">
						<option value="0"'.$no_selected.'>No</option>
						<option value="1"'.$yes_selected.'>Yes</option>
					</select>
					<span class="default">'.$default.'</span>
					<span class="note">'.$setting_data['summary'].'</span>
				</p>';
				break;
				
			default:
			$form_field = '';

		} // end switch
		
		return $form_field;	
	}
	


// any content generation
	
	$settings_data = admin_get_settings();
	
 	// intro text for each tab
 	
	$intro = array();
	
	$intro['admin'] 	= 'Set the sitewide contact email, alter the timezone, add account details for Twitter, Google Analytics, specify a alternative url.';
	
	$intro['connections'] = 'If you have any analytics accounts add the ids here to automatically switch on tracking for those accounts. You can also add usernames for selected social media sites here.';
	
	$intro['cache'] 	= 'If your site experiences heavy traffic you can switch on caching here.';
			
	$intro['comments'] 	= 'The below settings allow you to control comments, including hiding existing comments or completely disabling posting of comments.';
		
	$intro['design'] 	= 'The below settings only take effect if dynamic css is used. If you prefer to use your own structure.css file then changing these settings will have no effect.';
			
	$intro['files'] 	= 'Here you can set the maximum size of images and attachments uploaded to your site.';
		
	$intro['front'] 	= 'These settings control how the default front page of your site appears.';
		
	$intro['layout'] 	= 'The below settings affect how elements are arranged on your page, specifically: the position of the main menu (if used), the layout of results/listings pages, and the number of results to show per page.';
					
	$intro['meta'] 		= 'This allows you to select an alternative theme for your site, if multiple themes are available.';
				
	$intro['site'] 		= 'The settings on this page largely affect the <head> section of your site.';
	
	$left_text = 'settings';
	$right_text = 'yep... still settings';
	$page_header = show_page_header($left_text, $right_text);


// get aside content

	// any functions
	
	// any content generation


// output main content - into $main_content variable

	$main_content = $page_header;
	
	// start tabs
	
	$main_content .= '
		<div id="settings_tabs">
			<ul>';
			foreach($settings_data as $section => $junk) {
			if($section == 'custom') {
				continue;
			}
			$main_content .= '
				<li><a href="#tab_'.$section.'">'.$section.'</a></li>';	
			}
	$main_content .= '			
				<li><a href="#tab_custom">custom</a></li>
			</ul>';
			
	// tab content
	
	$custom_data = array();
	
	foreach($settings_data as $header => $data) {
		// ignore custom header here
		if($header == 'custom') {
			$custom_data[$header][] = $setting;
			continue;
		}		
		$main_content .= '
		<div id="tab_'.$header.'">
			<span class="messages">'.$messages.'</span>
			<form method="post" 
				action="'.$action_url.'#tab_'.$header.'" 
				class="settings_form" 
				id="'.$header.'_settings">
			<h2>'.$header.'</h2>
			<p>'.$intro[$header].'</p>';
			//debug_array($data);
			foreach($data as $id => $setting) {
				// grab any custom settings first
				if($setting['formtype'] == 'custom') {
					$custom_data[$header][] = $setting;
				}
				// output form
				$main_content .= build_settings_formtype($setting);
			}
		$main_content .= '	
			<p>
				<span class="note">
				<input name="tab" value="#tab_'.$header.'" type="hidden"/>
				<input type="submit" name="update" value="update '.$header.' settings" />
				</span>
			</p>
			</form>';
		// insert meta tag form
		if($header == 'meta') {
			$main_content .= insert_meta_form();
		}	
		$main_content .= '	
		</div>
		';
	}
	
	// add custom tab
	
	$main_content .= '
		<div id="tab_custom">';
			if(empty($custom_data)) {
				$main_content .= '
				<p>no custom settings</p>';
			} else {
				$main_content .= '
				<h2>custom configurations</h2>
				<p>any custom settings you have added are listed below - you can use this page to edit or delete these settings</p>';
				foreach($custom_data as $custom_header => $custom) {
					$main_content .= '
					<h4>'.$custom_header.'</h4>';
					foreach($custom as $custom_setting) {
						$main_content .= '
						<form id="custom_setting_'.$custom_setting['id'].'_form" 
							method="post" 
							action="'.$action_url.'#tab_custom" 
							class="settings_form">
						<p>
							<input name="property_name" value="'.$custom_setting['property_name'].'" type="text" style="font-weight: bold; color: #847964;text-align: right;"/>
							<input name="property_value" value="'.$custom_setting['property_value'].'" type="text"/>
							<input name="id" value="'.$custom_setting['id'].'" type="hidden"/>
							<input name="update_custom_setting" value="update" type="submit"/>
							<input name="delete_custom_setting" value="delete" type="submit"/>
						</p>
						</form>
						';
					}
				}
			}
	$main_content .= '		
		<h4>insert a new custom variable</h4>
		<p>n.b. to insert a custom meta tag use the form in the meta section</p>
			<form id="custom_insert_form" method="post" action="'.$action_url.'#tab_custom" class="settings_form">
				<p>
					<label for="property_name">name</label>
						<input type="text" name="property_name"/>
						<span class="note">this will be the name of your custom variable</span>				
				</p>
				<p>
					<label for="property_value">value</label>
						<input type="text" name="property_value"/>
						<input type="hidden" name="summary" value="custom setting" />
						<input type="hidden" name="element_name" value="custom" />
						<span class="note">this will be the value of your custom variable</span>					
				</p>
				<p>
					<span class="note">
					<input type="submit" name="insert_custom" value="insert custom setting" />
					</span>
				</p>
			</form>
		</div>';
	
	// close tabs
	$main_content .= '
		</div>';


// output aside content - into $aside_content variable
	
	$aside_content = '';

	// show current cache
	
	$current_cache = cache_size();
	if(!empty($current_cache['count'])) {
		$pages_string = ($current_cache['count'] == 1) ? '1 cached page' : $current_cache['count'].' cached pages' ;
		$aside_content .= '
		<h4>cache</h4>
		<p>you have '.$pages_string.' totalling '.$current_cache['size'].'kb &#124; <a href="'.$url.'&amp;clearcache">clear the cache</a></p>';		
	}
	
	// cleared cache results
	
	if(isset($cache_cleared)) {
		$files_string = ($cache_cleared == 1) ? '1 cached page cleared' : $cache_cleared.' cached pages cleared' ;
		$aside_content .= '
		<h4>cache cleared</h4>
		<p>'.$files_string.'</p>';			
	}

	$aside_content .= '
		<h4>quick reference</h4>
		<p>tip: click on a header to jump to that section in the main form</p>';
	
	$tab_count = 0;
	foreach($settings_data as $header => $data) {
		$aside_content .= '
		<div class="snippet">
			<h6 class="settings_header" id="tab_'.$tab_count.'">'.$header.'</h6>
			<ul>';
		foreach($data as $id => $setting) {
			$aside_content .= '
				<li>
				<span style="float:left; clear: left; width: 112px;">
					'.$setting['property_name'].'
				</span>';
			
			switch($setting['property_value']) {
				
				case '0':
				$val = 'no';
				break;
				
				case '1':
				$val = 'yes';
				break;
				
				case '':
				$val = '(not set)';
				break;
				
				default:
				$val = $setting['property_value'];
				break;
				
			}
			$len = strlen($val);
			$clear = ($len > 12) ?  'clear:left; ' : ' ' ;
			$aside_content .= '
				<span style="float: left; '.$clear.' margin-left: 4px; font-weight: bold;">'.$val.'</span>
				</li>';
		}
		$aside_content .= '
			</ul>
		</div>';
		$tab_count++;
	}

?>