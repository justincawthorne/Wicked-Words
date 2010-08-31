<?php
/**
 * combined functions
 * 
 * a limited subset of functions which are used in both author and reader pages
 * 
 * @package wickedwords
 * 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License version 3
 */
 
/*
		
		from_mysql_date
		current_url
		detect_smartphone
		get_articles_basic
		get_article_attachments
		build_snippet
		clean_input
		get_folders
		get_files
		get_kbsize
			
*/

/**
 * from_mysql_date
 * 
 * takes a mysql date (or any date in string format) and converts to a readable format
 *
 * @param 	string	$mydate		the mysql date
 * @param	strong	$format		the selected format
 * @return	string	$date		the formatted date string
 */
	
	function from_mysql_date($mydate, $format = 'd M Y \a\t H:i') {
		$ts = strtotime($mydate);
		if(empty($ts)) {
			return 'not published';
		}
		$date = date($format, $ts);
		return $date;
	}

/**
 * current_url
 * 
 * this will get the URL for whichever page it is called from
 * mainly useful for form actions and page reloads
 * 
 * @return string	$current_url	the url of the current page
 */	
 
	function current_url() {
		// get current url
		$host = (substr($_SERVER['HTTP_HOST'],0,7) != "http://") 
			? 'http://'.$_SERVER['HTTP_HOST'] 
			: $_SERVER['HTTP_HOST'] ;
		$current_url = $host.$_SERVER["REQUEST_URI"];
		return $current_url;
	}
	
 /**
 * detect_smartphone
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function detect_smartphone() {
		// check for bots
		$bot_array = array(	'iphone',
							'ipod',
							'android',
							'symbian',
							'webos');
		$useragent = (isset($_SERVER['HTTP_USER_AGENT'])) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '' ;
		$ignore = 0;
		// if user agent matches any of the bots then we don't count it
		foreach($bot_array as $bot) {
			if((!empty($useragent)) && (stripos($useragent, $bot)!== false)) { 
				return true; 
			}
		}
		return false;			
	}

/**
 * get_category_details
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function get_category_details($id) {
		$id = (int)$id;
		if(empty($id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT category_id, title, url, summary, description, type 
					FROM categories
					WHERE id = ".(int)$id;
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		$result->close();
		return $row;
	}

/**
 * get_tag_details
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_tag_details($id) {
		$id = (int)$id;
		if(empty($id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT title, url, summary
					FROM tags
					WHERE id = ".(int)$id;
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		$result->close();
		return $row;
	}

/**
 * get_articles_basic
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function get_articles_basic(	$url_style = 'blog',
									$where = '', 
									$order = 'date_uploaded DESC', 
									$limit = '10'
								) {
		$conn = reader_connect();
		$query = "SELECT
					articles.id, 
				 	articles.title, 
					articles.url, 
					articles.date_uploaded, 
					categories.url AS category_url
				FROM articles 
					LEFT JOIN categories ON articles.category_id = categories.id 
				WHERE status = 'P'
					AND date_uploaded <= NOW()";
		$query .= (!empty($where)) ? ' AND '.$where : '' ;
		$query .= ' ORDER BY '.$order;
		$query .= (!empty($limit)) ? ' LIMIT 0,'.$limit : '' ;
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row = stripslashes_deep($row);
			// create links
			$link = ($url_style == 'blog') 
				? WW_REAL_WEB_ROOT.'/'.date('Y/m/d',strtotime($row['date_uploaded'])).'/'.$row['url'].'/'
				: WW_REAL_WEB_ROOT.'/'.$row['category_url'].'/'.$row['url'].'/';
			$row['link'] = $link;
			$data[] = $row;
		}
		$result->close();
		return $data;		
	}

/**
 * get_article_attachments
 * 
 * 
 * 
 * 
 * 
 * 
 */	


	function get_article_attachments($id) {
		if(empty($id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT 
					attachments.id, attachments.title, attachments.filename,
					attachments.ext, attachments.size, attachments.mime
				FROM attachments_map
					LEFT JOIN attachments ON attachments.id = attachments_map.attachment_id
				WHERE article_id = ".(int)$id;
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row['itunes_link'] = WW_WEB_ROOT.'/download/'.$row['ext'].'/'.$row['filename'];
			$row['file_link'] = WW_WEB_ROOT.'/ww_files/attachments/'.$row['ext'].'/'.$row['filename'];
			$row['link'] = WW_WEB_ROOT.'/download/'.$row['id'].'/';
			$data[] = $row;
		}
		return $data;		
	}

/**
 * build_snippet
 * 
 * 
 * 
 * 
 * 
 * 
 */

 	function build_snippet($title = '', $content = '') {
		if(empty($content)) {
			return false;
		}
		if( (is_array($content)) && (count($content) < 2) ) {
			return false;
		}
		$current_uri = current_url();
		// start building
		$snippet = '
		<div class="snippet">
		';
		// title
		$snippet .= (!empty($title)) 
			? '<h6>'.$title.'</h6>' : '' ;
		// content
		if(is_array($content)) {
			$snippet .= '
			<ul>';
			foreach($content as $snip) {
				$current = ($snip['link'] == $current_uri) ? ' class="current"' : '' ;
				$link_title = (!empty($snip['link_title'])) ? $snip['link_title'] : $snip['title'] ;
				$snippet .= '
				<li'.$current.'>';
				if(isset($snip['total'])) {
					$snippet .= '
					<span class="list_item">
						<a href="'.$snip['link'].'" title="'.$link_title.'">'.$snip['title'].'</a>
					</span>
					<span class="list_total">
						'.$snip['total'].'
					</span>';
				} else {
					$snippet .= '
					<a href="'.$snip['link'].'" title="'.$link_title.'">'.$snip['title'].'</a>';
				}
				// child array
				if(isset($snip['child'])) {
					$snippet .= '
					<ul>';
					foreach($snip['child'] as $child) {
						$current = ($child['link'] == $current_uri) ? ' class="current"' : '' ;
						$link_title = (!empty($child['link_title'])) ? $child['link_title'] : $child['title'] ;
						$snippet .= '
						<li'.$current.'>';
						if(isset($child['total'])) {
							$snippet .= '
							<span class="list_item">
								<a href="'.$child['link'].'" title="'.$link_title.'">'.$child['title'].'</a>
							</span>
							<span class="list_total">
								'.$child['total'].'
							</span>';
						} else {
							$snippet .= '
							<a href="'.$child['link'].'" title="'.$link_title.'">'.$child['title'].'</a>';
						}
						$snippet .= '
						</li>';
					}
					$snippet .= '
					</ul>';
				}
				// end child array
				$snippet .= '
				</li>';
			}
			$snippet .= '
			</ul>';
		} else {
			$snippet .= $content;
		}
		// close wrapper
		$snippet .= '
		</div>';
		return $snippet;
	}

/**
 * clean_input
 *
 * function to clean up text entered in comments
 * optionally strips all html out
 * converts appropriate characters to entities
 * trims whitespace
 *
 * @param	string	$value	Input string
 * @param 	bool	$html	0 to strip html, 1 to leave html in
 *
 * @return	string	$value	Safe input
*/

	function clean_input($string, $html = 0) {
		$string = (empty($html)) ? strip_tags($string) : $string ;
		$string = htmlentities($string,ENT_QUOTES);
		$string = trim($string);
		return $string;
	}

 /**
 * get_folders
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_folders($dir) {
		$handle = opendir($dir);
		$folder_array = array();
		while (false !== ($file = readdir($handle))) {
			if ( (is_dir($dir."/".$file)) && ($file != ".") && ($file != "..") ) {
				$folder_array[] = $file;
			}
		}
		return $folder_array;
	}
 
 /**
 * get_files
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_files($folder, $ext_type = '') {
		if( (empty($folder)) || (!is_dir($folder)) ) {
			return false;
		}
		// folder path
		$dir = str_replace(WW_REAL_WEB_ROOT,WW_ROOT,$folder);
		$handle = opendir($dir);
		$file_data = array();
		while (false !== ($file = readdir($handle))) {
			//if (!is_dir("$dir/$file")) {
			if (!is_dir($dir."/".$file)) {
				// read file details into array
				$name = $file;
				$path = pathinfo($file);
				$ext = $path['extension'];
				if( (!empty($ext_type)) && ($ext != $ext_type) ) {
					continue;
				}
				$date = filemtime($dir.$file);
				$size = filesize($dir.$file);
				$file_data[] = array(
								'path'		=> $dir,
								'link'		=> str_replace(WW_ROOT,WW_REAL_WEB_ROOT,$dir).$name,
								'filename' 	=> $name,
								'size' 		=> $size,
								'ext' 		=> $ext,
								'date_uploaded' => $date
								);					


			}
		}
		return $file_data;
	}

 /**
 * get_kb_size
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function get_kb_size($bytes) {
		$kbsize = $bytes/1024;
		$kbsize = round($kbsize, 2);
		return $kbsize;		
	}
	
 /**
 * get_kb_size
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 
	function get_file_details($filepath) {
		$details = array();
		if( (empty($filepath)) || (!file_exists($filepath)) ) {
			return $details;
		}
		$pathinfo = pathinfo($filepath);
		$details['title'] 		= $pathinfo['basename'];
		$details['path'] 		= $pathinfo['dirname'];
		$details['filename'] 	= $pathinfo['basename'];
		$details['ext'] 		= $pathinfo['extension'];
		$details['size'] 		= filesize($filepath);
		$details['date_uploaded'] = filemtime($filepath);
		$details['id']			= 0;
		// author details
		$details['author_name']	= $_SESSION[WW_SESS]['name'];
		// additional details for an image
		$img = array('jpg','gif','jpeg','png');
		if(in_array($pathinfo['extension'], $img)) {
			$img_details = getimagesize($filepath);
			$details['width'] 	= $img_details[0];
			$details['height'] 	= $img_details[1];
			$details['mime'] 	= $img_details['mime'];
			// other details to avoid errors
			$details['caption']	= '';
			$details['credit']	= '';
			$details['alt']		= $details['title'];
			$details['src']		= WW_WEB_ROOT.'/ww_files/images/'.$details['filename'];
			$details['thumb_src']		= WW_WEB_ROOT.'/ww_files/images/thumbs/'.$details['filename'];
		}
		return $details;	
	}
 ?>