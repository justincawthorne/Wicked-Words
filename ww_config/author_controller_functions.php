<?php
/**
 * author controller functions
 * 
 * @package wickedwords
 * 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License version 3
 */

/*
	outline:
	
		initial functions
		
			create_author_session
			define_article_status
		
		stats
		
			get_articles_stats
			get_comments_stats
			get_new_comments
		
		article lists
		
			get_articles_admin
			
		author/category/date/tag lists
		
			filter_admin_lists
			get_authors_admin
			get_categories_admin
			get_tags_admin
		
		single article
		
			get_article_admin
			get_article_edits
			get_article_edit
			get_article_atachments_admin
			get_article_tags_admin
			get_article_comments_admin
		
		article data insert
		
			get_article_form_data
			validate_article_post_data
			insert_article
			update_article
			update_article_tags
			update_article_attachments
			update_article_status
			delete_article
			
			prepare_article_body
			convert_absolute_urls
			create_url_title
			check_url_title
		
		comment management
		
		author
		
			get_author
			insert_author
			update_author
			delete_author
		
		category
		
			quick_insert_category
			insert_category
			update_category
			delete_category
		
		tag
		
			quick_insert_tags
			insert_tag
			update_tag
			delete_tag	
		
		file/image edit
			
			get_attachments
			get_images
			update_attachment
			update_image
			delete_attachment
			delete_image
				
		file/image upload
		
			check_file_upload
			resize_img
			upload_file
			
*/

/*
 * -----------------------------------------------------------------------------
 * GENERAL FUNCTIONS
 * -----------------------------------------------------------------------------
 */
 
/**
 * create_author_session
 * 
 * takes the login session and creates an author session which we can 
 * use to store certain author details such as email and access level
 */	
 
	function create_author_session() {
		$login = $_SESSION[WW_SESS];
		$conn = author_connect();
		$query = "SELECT name, email, guest_areas 
					FROM authors 
					WHERE id = ".(int)$_SESSION[WW_SESS]['user_id'];
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
			$_SESSION[WW_SESS]['name'] = $row['name'];
			$_SESSION[WW_SESS]['email'] = strtolower($row['email']);
		$result->close();
		// access level
		$level = (empty($_SESSION[WW_SESS]['guest'])) ? 'author' : $row['guest_areas'] ;	
		$allowed_levels = array('author','editor','contributor');
		$level = (!in_array($level,$allowed_levels)) ? 'contributor' : $level ;
		$_SESSION[WW_SESS]['level'] = $level;
		return true;
	}




/*
 * -----------------------------------------------------------------------------
 * FRONT
 * -----------------------------------------------------------------------------
 */
 
/**
 * define_article_status
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function define_article_status() {
		$status = array();
		$status['D'] = 'draft';
		$status['P'] = 'published';
		$status['A'] = 'archived';
		$status['W'] = 'withdrawn';
		return $status;
	}

/**
 * get_articles_status
 * 
 * 
 * 
 * @params	bool	$filter		set to 1 to allow this to be used in conjunction with other
 * 								article filters (e.g. searching for author and category)
 * 
 * 
 */

	function get_articles_stats($filter = 0) {
		$conn = author_connect();
		$query = "SELECT COUNT(articles.id) as total,
					status AS url,
					MIN(date_uploaded) as first_post,
					MAX(date_uploaded) as last_post
					FROM articles";
		if(!empty($filter)) {
			if (isset($_GET['tag_id'])) {
				$query .= " LEFT JOIN tags_map ON tags_map.article_id = articles.id";
			}
			$query .= filter_admin_lists();			
		}
		$query .= "	GROUP BY articles.status
					ORDER BY articles.status";
		$result = $conn->query($query);
		$data = array();
		$link_base = $_SERVER["PHP_SELF"].'?';
		// we might need to add the page_name url param if this is called from the front page
		if( (!isset($_GET['page_name'])) || ($_GET['page_name'] != 'articles') ) {
			$link_base .= 'page_name=articles&amp;';
		}
		$status = define_article_status();
		foreach($_GET as $param => $value) {
			if($param == 'status') {
				continue;
			}
			$link_base .= $param.'='.$value.'&amp;';
		}
		while($row = $result->fetch_assoc()) { 
			$row['title'] = $status[$row['url']];
			$row['link'] = $link_base.'status='.$row['url'];
			$data[$row['url']] = $row;
		}
		$result->close();
		// now get postdated too
		$query = "SELECT COUNT(articles.id) as total,
					'PD' AS url,
					MIN(date_uploaded) as first_post,
					MAX(date_uploaded) as last_post
					FROM articles ";
		$where_string = '';
		if(!empty($filter)) {
			if (isset($_GET['tag_id'])) {
				$query .= " LEFT JOIN tags_map ON tags_map.article_id = articles.id";
			}
			$where_string .= filter_admin_lists();			
		}
		$query .= (empty($where_string)) ? " WHERE date_uploaded >= NOW()" : $where_string." AND date_uploaded >= NOW()" ;
		$result = $conn->query($query);
		unset($_GET['page']);
		foreach($_GET as $param => $value) {
			if($param == 'status') {
				continue;
			}
			$link_base .= $param.'='.$value.'&amp;';
		}
		$row = $result->fetch_assoc();
		if(!empty($row['total'])) {
			$row['title'] = 'postdated';
			$row['link'] = $link_base.'postdated';
			$data['PD'] = $row;
		}
		$result->close();	
		return $data;		
	}

/**
 * get_comments_stats
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_comments_stats($author_id = 0) {
		$author_id = (int)$author_id;
		$article_id = (isset($_GET['article_id'])) ? (int)$_GET['article_id'] : 0 ;
		$conn = reader_connect();
		$where = array();
		$query = "SELECT 
				CASE approved
					WHEN 1 THEN 'approved'
					WHEN 0 THEN 'not approved'
				END as title,
				approved,
				COUNT(comments.approved) as total
				FROM comments ";
		if(!empty($author_id)) {
				$query .= " 
				LEFT JOIN articles on articles.id = comments.article_id";
				$where[] = "articles.author_id = ".$author_id;	
		}
		if(!empty($article_id)) {
			$where[] = " article_id = ".$article_id;
		}
		$query .= (!empty($where)) ? " WHERE ".implode(' AND ',$where) : '' ;
		$query .= " 
				GROUP BY approved";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) {
			$row['link'] = $_SERVER["PHP_SELF"].'?page_name=comments&amp;approved='.$row['approved'];
			$row['link'] .= (!empty($article_id)) ? '&amp;article_id='.$article_id : '' ;
			$data[$row['title']] = $row;
		}
		$result->close();
		return $data;
	}

/**
 * admin_new_comments
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function get_new_comments($author_id = 0) {
		$author_id = (int)$author_id;
		$last_login = $_SESSION[WW_SESS]['last_login'];
		$conn = reader_connect();
		$query = "
			SELECT comments.id 
			FROM comments";
		if(!empty($author_id)) {
				$query .= " 
				LEFT JOIN articles on articles.id = comments.article_id
				WHERE articles.author_id = ".$author_id." 
				AND comments.date_uploaded > '".$last_login."'";	
		} else {
			$query .= "	WHERE comments.date_uploaded > '".$last_login."'";
		}
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		$result->close();
		return $row;		
	}

/**
 * -----------------------------------------------------------------------------
 * CACHE
 * -----------------------------------------------------------------------------
 */

	function clear_cache() {
		$cachedir = WW_ROOT."/ww_files/_cache/";
		$cache_files = get_files($cachedir);
		$cleaned = 0;
		foreach($cache_files as $clean_file) {
			if(file_exists($cachedir.$clean_file['filename'])) {
				unlink($cachedir.$clean_file['filename']);
				$cleaned = $cleaned + 1;
			}
		}
		return $cleaned;
	}
	
	function cache_size() {
		$cachedir = WW_ROOT."/ww_files/_cache/";
		$cache_files = get_files($cachedir);
		$totalsize = 0;
		foreach($cache_files as $cached_file) {
			$totalsize += $cached_file['size'];
		}
		$cache = array();
		$cache['size'] 	= get_kb_size($totalsize);
		$cache['count'] = count($cache_files);
		return $cache;
	}

/**
 * -----------------------------------------------------------------------------
 * ARTICLES RETRIEVAL
 * -----------------------------------------------------------------------------
 */





/**
 * get_articles_admin
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_articles_admin() {
		// get layout config from database
		$per_page = ( (!isset($_GET['per_page'])) || (empty($_GET['per_page'])) ) 
			? 15 
			: (int)$_GET['per_page'] ;
		$conn = reader_connect();
		// set up pagination
		$page_no = (isset($_GET['page'])) ? (int)$_GET['page'] : '1';
		// calculate lower query limit value
		$from = (($page_no * $per_page) - $per_page);
		$query = "SELECT
					articles.id,
					articles.status,
				 	articles.title, 
					articles.url, 
					articles.date_uploaded,
					articles.category_id, 
					categories.title AS category_title, 
					categories.url AS category_url,
					articles.author_id,
					authors.name AS author_name, 
					authors.url AS author_url,
					articles.view_count,
					articles.visit_count,
					(SELECT COUNT(id) 
						FROM comments 
						WHERE article_id = articles.id) AS comment_count
				FROM articles 
					LEFT JOIN authors ON articles.author_id = authors.id 
					LEFT JOIN categories ON articles.category_id = categories.id ";
		$where = array();
		// article status
		if (isset($_GET['status'])) {
			$where[] = " articles.status = '".$conn->real_escape_string($_GET['status'])."'";
		}
		// postdated	
		if (isset($_GET['postdated'])) {
			$where[] = " articles.date_uploaded >= NOW()";
		}
		// author id
		if (isset($_GET['author_id'])) {
			$where[] = " articles.author_id = ".(int)$_GET['author_id'];
		}
		// category url
		if (isset($_GET['category_id'])) {
			$where[] = " articles.category_id = ".(int)$_GET['category_id'];
		}
		// tag
		if (isset($_GET['tag_id'])) {
			$query .= " LEFT JOIN tags_map ON tags_map.article_id = articles.id";
			$where[] = " tags_map.tag_id = ".(int)$_GET['tag_id'];
		}
		// year
		if (isset($_GET['year'])) {
			$where[] = " YEAR(date_uploaded) = ".(int)$_GET['year'];
		}
		// month
		if (isset($_GET['month'])) {
			$where[] = " MONTH(date_uploaded) = ".(int)$_GET['month'];
		}
		// day
		if (isset($_GET['day'])) {
			$where[] = " DAY(date_uploaded) = ".(int)$_GET['day'];
		}
		// compile where clause
		if(!empty($where)) {
			$query .= " WHERE";
			$query .= implode(' AND ', $where); // compile WHERE array into select statement
		}
		// sort order
		$query .= " ORDER BY date_uploaded DESC";
		// add pagination
		$query_paginated = $query." LIMIT ".(int)$from.", ".(int)$per_page;
		$result = $conn->query($query_paginated);
		// get total results
		$total_result = $conn->query($query);
		$total_articles = $total_result->num_rows;
		$total_pages = ceil($total_articles / $per_page);
		$status = define_article_status();
		$data = array();
		while($row = $result->fetch_assoc()) {
			$row = stripslashes_deep($row);
			// adjust times to local timezone if necessary
			$ts = strtotime($row['date_uploaded']);
			$offset = date('Z');
			$row['date_ts'] = $ts+$offset;
			$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
			// add status and style
			$row['style'] = ($row['date_ts'] > time()) ? 'postdated' : $status[$row['status']];
			// add page counts
			$row['total_pages'] = $total_pages;
			$row['total_found'] = $total_articles;
			$data[] = $row;
		}
		$result->close();
		$total_result->close();
		return $data;
	}



/**
 * -----------------------------------------------------------------------------
 * AUTHOR / CATEGORY / DATE / TAG LISTINGS
 * -----------------------------------------------------------------------------
 */

	
 /**
 * filter_admin_lists
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 
	function filter_admin_lists() {
		$conn = author_connect();
		$filter = '';
		$where = array();
		// article status
		if (isset($_GET['status'])) {
			$where[] = " articles.status = '".$conn->real_escape_string($_GET['status'])."'";
		}
		// postdated	
		if (isset($_GET['postdated'])) {
			$where[] = " articles.date_uploaded >= NOW()";
		}
		// author id
		if (isset($_GET['author_id'])) {
			$where[] = " articles.author_id = ".(int)$_GET['author_id'];
		}
		// category url
		if (isset($_GET['category_id'])) {
			$where[] = " articles.category_id = ".(int)$_GET['category_id'];
		}
		// tag
		if (isset($_GET['tag_id'])) {
			$where[] = " tags_map.tag_id = ".(int)$_GET['tag_id'];
		}
		// year
		if (isset($_GET['year'])) {
			$where[] = " YEAR(date_uploaded) = ".(int)$_GET['year'];
		}
		// month
		if (isset($_GET['month'])) {
			$where[] = " MONTH(date_uploaded) = ".(int)$_GET['month'];
		}
		// day
		if (isset($_GET['day'])) {
			$where[] = " DAY(date_uploaded) = ".(int)$_GET['day'];
		}	
		// compile where clause
		if(!empty($where)) {
			$filter = " WHERE".implode(' AND ', $where); // compile WHERE array into select statement
		}
		return $filter;			
	}

/**
 * get_authors_admin
 * 
 * 
 * 
 * @params	bool	$filter		set to 1 to allow this to be used in conjunction with other
 * 								article filters (e.g. searching for author and category)
 * 
 * 
 */	
 	
	function get_authors_admin($filter = 0) {
		$conn = author_connect();
		$query = "SELECT COUNT(articles.id) as total,
						authors.id, 
						authors.url, 
						authors.name AS title
					FROM authors
					LEFT JOIN articles ON articles.author_id = authors.id";
		if(!empty($filter)) {
			if (isset($_GET['tag_id'])) {
				$query .= " LEFT JOIN tags_map ON tags_map.article_id = articles.id";
			}
			$query .= filter_admin_lists();			
		}
		$query .= "	GROUP BY authors.id
					ORDER BY title";
		$result = $conn->query($query);
		$data = array();
		$link_base = $_SERVER["PHP_SELF"].'?';
		unset($_GET['page']);
		foreach($_GET as $param => $value) {
			if($param == 'author_id') {
				continue;
			}
			$link_base .= $param.'='.$value.'&amp;';
		}
		while($row = $result->fetch_assoc()) { 
			$row['link'] = $link_base.'author_id='.$row['id'];
			$data[$row['id']] = $row;
		}
		$result->close();
		return $data;
	}

/**
 * get_dates_admin
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_dates_admin($filter = 0) {
		
	}

/**
 * get_categories_admin
 * 
 * 
 * 
 * @params	bool	$filter		set to 1 to allow this to be used in conjunction with other
 * 								article filters (e.g. searching for author and category)
 * 
 * 
 */	
 	
	function get_categories_admin($filter = 0) {
		$conn = author_connect();
		$query = "SELECT COUNT(articles.id) as total,
						categories.id,
						categories.category_id,
						categories.url, 
						categories.title
					FROM categories
					LEFT JOIN articles ON articles.category_id = categories.id";
		if(!empty($filter)) {
			if (isset($_GET['tag_id'])) {
				$query .= " LEFT JOIN tags_map ON tags_map.article_id = articles.id";
			}
			$query .= filter_admin_lists();					
		}
		$query .= "	GROUP BY categories.id
					ORDER BY categories.category_id, categories.url";
		$result = $conn->query($query);
		$data = array();
		$link_base = $_SERVER["PHP_SELF"].'?';
		unset($_GET['page']);
		foreach($_GET as $param => $value) {
			if($param == 'category_id') {
				continue;
			}
			$link_base .= $param.'='.$value.'&amp;';
		}
		while($row = $result->fetch_assoc()) { 
			$row['link'] = $link_base.'category_id='.$row['id'];
			if(!empty($row['category_id'])) {
				$data[$row['category_id']]['child'][$row['id']] = $row;
			} else {
				$data[$row['id']] = $row;
			}
		}
		$result->close();
		return $data;
	}


/**
 * get_tags_admin
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function get_tags_admin($filter = 0) {
		$conn = author_connect();
		$query = "SELECT COUNT(articles.id) as total,
						tags.id, 
						tags.url, 
						tags.title
					FROM tags
					LEFT JOIN tags_map on tags_map.tag_id = tags.id
					LEFT JOIN articles on tags_map.article_id = articles.id";
		if(!empty($filter)) {
			$query .= filter_admin_lists();					
		}
		$query .= "	GROUP BY tags.id
					ORDER BY title";
		$result = $conn->query($query);
		$data = array();
		$link_base = $_SERVER["PHP_SELF"].'?';
		unset($_GET['page']);
		foreach($_GET as $param => $value) {
			if($param == 'tag_id') {
				continue;
			}
			$link_base .= $param.'='.$value.'&amp;';
		}
		while($row = $result->fetch_assoc()) { 
			$row['link'] = $link_base.'tag_id='.$row['id'];
			$data[$row['id']] = $row;
		}
		$result->close();
		return $data;
	}



/**
 * -----------------------------------------------------------------------------
 * ARTICLE (SINGLE)
 * -----------------------------------------------------------------------------
 */

/**
 * get_article_admin
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_article_admin($article_id) {
		if(empty($article_id)) {
			return false;
		}
		$conn = author_connect();
		$query = "SELECT articles.*, categories.url as category_url
				FROM articles 
				LEFT JOIN categories ON articles.category_id = categories.id
				WHERE articles.id = ".(int)$article_id;
		$comment_query = "
				SELECT COUNT(id) as total
				FROM comments 
				WHERE article_id = ".(int)$article_id;
		$result = $conn->query($query);
		$comment_result = $conn->query($comment_query);
		$row = $result->fetch_assoc();
		$row = stripslashes_deep($row);
		$comments = $comment_result->fetch_assoc();
		$row['comment_count'] = $comments['total'];
		$result->close();
		return $row;
	}

/**
 * get_article_edits
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function get_article_edits($article_id) {
		if(empty($article_id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT edits.id, author_id, authors.name,
					date_edited
				FROM edits
					LEFT JOIN authors ON author_id = authors.id
				WHERE article_id = ".(int)$article_id."
				ORDER BY date_edited DESC";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$data[] = $row;
		}
		$result->close();
		return $data;		
	}

/**
 * get_article_edit
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function get_article_edit($edit_id) {
		if(empty($edit_id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT edits.author_id, authors.name,
					articles.title, edits.body, date_edited
				FROM edits
					LEFT JOIN authors ON author_id = authors.id
					LEFT JOIN articles ON article_id = articles.id
				WHERE edits.id = ".(int)$edit_id;
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		$result->close();
		return $row;		
	}

/**
 * get_article_tags_admin
 * 
 * 
 * 
 * 
 * 
 * 
 */	


	function get_article_tags_admin($article_id) {
		if(empty($article_id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT 
					tag_id
				FROM tags_map
				WHERE article_id = ".(int)$article_id;
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$data[] = $row['tag_id'];
		}
		$result->close();
		return $data;		
	}

/**
 * get_article_comments_admin
 * 
 * 
 * 
 * 
 * 
 * 
 */	
/*
	function get_article_comments_admin($article_id) {
		if(empty($article_id)) {
			return false;
		}
		$conn = author_connect();
		$query = 'SELECT 
					id, reply_id, author_id, title, body, date_uploaded,
					approved, poster_name, poster_link, poster_email, poster_IP
				FROM comments
				AND article_id = '.(int)$article_id;
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row = stripslashes_deep($row);
			$data[$row['id']] = $row;
		}
		return $data;		
	}
*/
/**
 * -----------------------------------------------------------------------------
 * ARTICLE INSERT
 * -----------------------------------------------------------------------------
 */

/**
 * get_article_form_data
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_article_form_data() {
		$article_data = array();
		if(!empty($_GET['article_id'])) {
			
			// editing an article
			$article_id = (int)$_GET['article_id'];
			$article_data = get_article_admin($article_id);
			$article_data = stripslashes_deep($article_data);
			// date fields - probably don't need these
			$article_data['day'] 	= date('d',$article_data['date_ts']);
			$article_data['month'] 	= date('m',$article_data['date_ts']);
			$article_data['year'] 	= date('Y',$article_data['date_ts']);
			$article_data['hour'] 	= date('H',$article_data['date_ts']);
			$article_data['minute'] = date('i',$article_data['date_ts']);
			// tags
			$article_data['tags']			= get_article_tags_admin($article_id);
			// attachments
			$article_data['attachments']	= get_article_attachments($article_id);
		
		} else {
			
			// brand new article
			
			// get default comments config from database
			$config = get_settings('comments');
			$article_data['id'] 			= 0;
			$article_data['title'] 			= 'New article';
			$article_data['url'] 			= '';
			$article_data['summary'] 		= '';
			$article_data['body'] 			= '';
			$article_data['status'] 		= 'D';
			$article_data['author_id'] 		= $_SESSION[WW_SESS]['user_id'];
			$article_data['category_id'] 	= 0;
			$article_data['category_url']	= '';
			$article_data['tags']			= array();
			$article_data['attachments']	= array();
			$article_data['seo_title'] 		= '';
			$article_data['seo_desc'] 		= '';
			$article_data['seo_keywords'] 	= '';
			$article_data['comments_hide'] 	= $config['comments']['site_hide'];
			$article_data['comments_disable'] = $config['comments']['site_disable'];
			// $now = strtotime(get_mysql_time());
			$now = time();
			$article_data['day'] 			= date('d',$now);
			$article_data['month'] 			= date('m',$now);
			$article_data['year'] 			= date('Y',$now);
			$article_data['hour'] 			= date('H',$now);
			$article_data['minute'] 		= date('i',$now);
			
		}
		return $article_data;
	}

/**
 * validate_article_data
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function validate_article_post_data() {
		$article_data['error'] = array();
		// set status - archive, draft, published, withdrawn
		$status_list = array('A','D','P','W');
		$article_data = $_POST;
		if(isset($_POST['draft'])) {
			$article_data['status'] =  'D';
		} else {
			$article_data['status'] =  'P';
			if(isset($_POST['status'])) {
				$post_status = $_POST['status'];
				$article_data['status'] = (in_array($post_status,$status_list)) ? $_POST['status'] : 'A' ;
			}
		}
		
		// id
		$article_data['id'] = (isset($_GET['article_id'])) ? (int)$_GET['article_id'] : 0 ;
		
		// title - required
		if( (isset($_POST['title'])) && (!empty($_POST['title'])) ) {
			$article_data['title'] = clean_input($_POST['title']);
		} else {
			$article_data['title'] = 'New article';
			$article_data['error'][] = "No title entered";
		}
		
		// url title - update url title only if article is new, url is empty, or update_url is checked
		if( (empty($article_data['id'])) || (!empty($_POST['update_url'])) || (empty($_POST['url'])) ) {
		
			$article_data['url'] = create_url_title($article_data['title']);
			
		} else {
		
			$article_data['url'] = clean_input($_POST['url']) ;
			
		}
		
		// check for url duplicates
		$article_data['url'] = check_url_title($article_data['url'],$article_data['id']);
					
		// summary
		$article_data['summary'] = (isset($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		
		// body - no need to clean html here
		$article_data['body'] = (isset($_POST['body'])) ? prepare_article_body($_POST['body']) : '' ;
		
		// author id
		$article_data['author_id'] = (int)$_POST['author_id'];
		
		// author id
		$article_data['category_url'] = $_POST['category_url'];
		
		// category new
		if(!empty($_POST['category_new'])) {
			$new_category = clean_input($_POST['category_new']);
			$article_data['category_id'] = quick_insert_category($new_category);
			if(!is_int($article_data['category_id'])) {
				$article_data['error'][] = $article_data['category_id'];
			}
		} else {
		// category id
			$article_data['category_id'] = (int)$_POST['category_id'];		
		}

		// error check category
		if(empty($article_data['category_id'])) {
			$article_data['error'][] = "No category selected (or no new category entered)";
		}
		
		// date_uploaded
		if(isset($_POST['date_uploaded'])) {
			$article_data['date_uploaded'] = $_POST['date_uploaded'];
		} else {
			$year 	= (empty($_POST['year'])) 	? gmdate('Y') : $_POST['year'] ;
			$month 	= (empty($_POST['month'])) 	? gmdate('m') : $_POST['month'] ;
			$day 	= (empty($_POST['day'])) 	? gmdate('d') : $_POST['day'] ;
			$hour 	= (empty($_POST['hour'])) 	? gmdate('H') : $_POST['hour'] ;
			$minute = (empty($_POST['minute'])) ? gmdate('i') : $_POST['minute'] ;
			// calculate GMT timestamp
			$ts_uploaded = strtotime($year."-".$month."-".$day." ".$hour.":".$minute.":00");
			$article_data['date_uploaded'] = gmdate('Y-m-d H:i:s', $ts_uploaded);
			// just to avoid messy errors we'll resend the date/time variables again
			$article_data['year'] 	= $_POST['year'];
			$article_data['month'] 	= $_POST['month'];
			$article_data['day'] 	= $_POST['day'];
			$article_data['hour'] 	= $_POST['hour'];
			$article_data['minute'] = $_POST['minute'];
		}
		
		// date ammended
		$article_data['date_amended'] = gmdate('Y-m-d H:i:s');
		
		// seo data
		$article_data['seo_title'] = (isset($_POST['seo_title'])) ? clean_input($_POST['seo_title']) : '' ;
		$article_data['seo_desc'] = (isset($_POST['seo_desc'])) ? clean_input($_POST['seo_desc']) : '' ;
		$article_data['seo_keywords'] = (isset($_POST['seo_keywords'])) ? clean_input($_POST['seo_keywords']) : '' ;
		
		// comment settings
		$article_data['comments_hide'] = ( (isset($_POST['comments_hide'])) && (!empty($_POST['comments_hide'])) ) ? 1 : 0 ;
		$article_data['comments_disable'] = ( (isset($_POST['comments_disable'])) && (!empty($_POST['comments_disable'])) ) ? 1 : 0 ;

		// tags
		$article_data['tags'] = ( (isset($_POST['tags'])) && (!empty($_POST['tags'])) ) 
			? $_POST['tags'] : array() ;
		
		// attachments
		$article_data['attachments'] = ( (isset($_POST['attachments'])) && (!empty($_POST['attachments'])) ) 
			? $_POST['attachments'] : array() ;
		
		// tag new
		if(!empty($_POST['tag_new'])) {
			$new_tag = clean_input($_POST['tag_new']);
			$new_tag_ids = quick_insert_tags($new_tag);
			foreach($new_tag_ids as $new_id) {
				if(is_int($new_id)) {
					$article_data['tags'][] = $new_id;
				} else {
					$article_data['error'][] = $new_id;
				}
				
			}
		}
		// any errors
		if(empty($article_data['error'])) {
			if(empty($article_data['id'])) {
				return insert_article($article_data);
			} else {
				return update_article($article_data);
			}
		} else {
			$article_data['status'] =  'D';
			return stripslashes_deep($article_data);
		}
	}

/**
 * insert_article
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function insert_article($post_data) {
		$conn = author_connect();
		$query = "INSERT INTO articles 
					(title, url, summary, body,
					category_id, author_id, status,
					date_uploaded, date_amended,
					seo_title, seo_desc, seo_keywords,
					comments_disable,comments_hide)
					VALUES
					('".$conn->real_escape_string($post_data['title'])."',
					'".$conn->real_escape_string($post_data['url'])."',
					'".$conn->real_escape_string($post_data['summary'])."',
					'".$conn->real_escape_string($post_data['body'])."',
					".(int)$post_data['category_id'].",
					".(int)$post_data['author_id'].",
					'".$conn->real_escape_string($post_data['status'])."',
					'".$conn->real_escape_string($post_data['date_uploaded'])."',
					'".$conn->real_escape_string($post_data['date_amended'])."',
					'".$conn->real_escape_string($post_data['seo_title'])."',
					'".$conn->real_escape_string($post_data['seo_desc'])."',
					'".$conn->real_escape_string($post_data['seo_keywords'])."',
					".(int)$post_data['comments_disable'].",
					".(int)$post_data['comments_hide'].")";
		$conn->query($query);
		$new_id = $conn->insert_id;
		if(empty($new_id)) {
			$post_data['error'][] = 'There was a problem inserting the article: '.$conn->error;
			return stripslashes_deep($post_data);			
		} else {
			$result = array();
			$result['action'] = 'inserted';
			$result['id'] = $new_id;
			$result['title'] = $post_data['title'];
			$result['url'] = $post_data['url'];
			$result['status'] = $post_data['status'];
			$result['category_id'] = $post_data['category_id'];
			$result['date_uploaded'] = $post_data['date_uploaded'];
		// update tags_map table
			if(!empty($post_data['tags'])) {
				update_article_tags($new_id, $post_data['tags']);
			}
		// update attachments table
			if(!empty($post_data['attachments'])) {
				update_article_attachments($new_id, $post_data['attachments']);
			}
		// update edits table and delete autosave cookie if set
			if(isset($_COOKIE['autosave_new'])) {
				$edit_id = (int)$_COOKIE['autosave_new'];
				$update = "
				UPDATE edits SET article_id = ".(int)$new_id." WHERE id = ".$edit_id;	
				$conn->query($update);
				setcookie("autosave_new", "", time()-3600, "/"); // 30 minutes	
			}
			clear_cache();
			return $result;	
		}

	}

/**
 * update_article_data
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function update_article($post_data) {
		$conn = author_connect();
		$query = "UPDATE articles SET
					title 			= '".$conn->real_escape_string($post_data['title'])."', 
					url 			= '".$conn->real_escape_string($post_data['url'])."', 
					summary 		= '".$conn->real_escape_string($post_data['summary'])."', 
					body 			= '".$conn->real_escape_string($post_data['body'])."',
					category_id 	= ".(int)$post_data['category_id'].", 
					author_id 		= ".(int)$post_data['author_id'].", 
					status 			= '".$conn->real_escape_string($post_data['status'])."',
					date_uploaded 	= '".$conn->real_escape_string($post_data['date_uploaded'])."', 
					date_amended 	= '".$conn->real_escape_string($post_data['date_amended'])."',
					seo_title 		= '".$conn->real_escape_string($post_data['seo_title'])."', 
					seo_desc 		= '".$conn->real_escape_string($post_data['seo_desc'])."', 
					seo_keywords 	= '".$conn->real_escape_string($post_data['seo_keywords'])."',
					comments_disable 	= ".(int)$post_data['comments_disable'].",
					comments_hide 		= ".(int)$post_data['comments_hide']."
					WHERE id = ".(int)$post_data['id'];
		$result = $conn->query($query);
		if(!$result) {
			$post_data['error'][] = 'There was a problem updating the article: '.$conn->error;
			return stripslashes_deep($post_data);
		} else {
			$result = array();
			$result['action'] = 'updated';
			$result['id'] = $post_data['id'];
			$result['title'] = $post_data['title'];
			$result['url'] = $post_data['url'];
			$result['status'] = $post_data['status'];
			$result['category_id'] = $post_data['category_id'];
			$result['date_uploaded'] = $post_data['date_uploaded'];
		// update tags_map table
			update_article_tags($post_data['id'], $post_data['tags']);
		// update attachments_map table
			update_article_attachments($post_data['id'], $post_data['attachments']);
		// delete autosave cookie if set
			$autosave_cookie = 'autosave_'.(int)$post_data['id'];
			if(isset($_COOKIE[$autosave_cookie])) {
				setcookie($autosave_cookie, "", time()-3600, "/"); // 30 minutes	
			}
			clear_cache();
			return $result;	
		}

	}

/**
 * update_article_attachments
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function update_article_attachments($article_id, $attachments_array) {
		if(empty($article_id)) {
			return array();
		}
		$conn = author_connect();
		// easiest to delete current tags for this article first
		$delete = 'DELETE FROM attachments_map 
					WHERE article_id = '.(int)$article_id;
		$conn->query($delete);
		// now insert all the new tags
		if(!empty($attachments_array)) {
			foreach($attachments_array as $attachment => $attachment_id) {
				$insert = '
				INSERT INTO attachments_map 
				(article_id, attachment_id)
				VALUES
				('.(int)$article_id.','.(int)$attachment_id.')';
				$conn->query($insert);
			}
		}
		return true;		
	}
	
/**
 * update_article_tags
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function update_article_tags($article_id, $tags_array) {
		if(empty($article_id)) {
			return array();
		}
		$conn = author_connect();
		// easiest to delete current tags for this article first
		$delete = 'DELETE FROM tags_map 
					WHERE article_id = '.(int)$article_id;
		$conn->query($delete);
		// now insert all the new tags
		if(!empty($tags_array)) {
			foreach($tags_array as $tag => $tag_id) {
				$insert = '
				INSERT INTO tags_map 
				(article_id, tag_id)
				VALUES
				('.(int)$article_id.','.(int)$tag_id.')';
				$conn->query($insert);
			}
		}
		return true;
	}

/**
 * update_article_status
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function update_article_status($article_id, $status) {
		
	}

/**
 * delete_article
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function delete_article($article_id) {
		$article_id = (int)$article_id;
		if(empty($article_id)) {
			echo 'no id';
			return false;
		}
		$conn = author_connect();
		$query = "DELETE FROM articles WHERE id = ".$article_id;
		$result = $conn->query($query);
		if(!$result) {
			return $conn->error;
		} else {
			clear_cache();
			return true;
		}		
	}

/**
 * -----------------------------------------------------------------------------
 * STRING PROCESSING
 * -----------------------------------------------------------------------------
 */

/**
 * prepare_article_body
 * 
 * a few functions to get the article body ready for posting
 * 
 * 
 * 
 * 
 */

	function prepare_article_body($body) {
		$body = convert_absolute_urls($body);
		$body = str_replace('<p><!-- pagebreak --></p>','<!-- pagebreak -->',$body);
		$body = str_replace('<p><!-- more --></p>','<!-- more -->',$body);
		return $body;
	}


/**
 * convert_absolute_urls
 * 
 * takes any absolute urls (pointing within the current site) 
 * and converts to relative urls - ensuring the site can be migrated to
 * another domain if needed
 * 
 * 
 */	

 	function convert_absolute_urls($article) {
		$absolute = '"'.WW_REAL_WEB_ROOT;
		$relative = '"..';
		$article = str_replace($absolute, $relative, $article);
		return $article;
	}

/**
 * create_url_title
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function create_url_title($string) {
		$string = strtolower($string);			// Convert to lowercase
		$string = strip_tags($string);				// strip html
		$string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);  
												// Remove all punctuation
		$string = ereg_replace(" +", " ", $string);	// Remove multiple spaces
		$string = str_replace(' ', '-', $string);	// Convert spaces to hyphens
		return $string;		
	}

/**
 * check_url_title
 * 
 * function to check for duplicate url titles
 * 
 * 
 * 
 * 
 */	
	
	function check_url_title($url, $article_id = 0) {
		if(empty($url)) {
			return;
		}
		$conn = author_connect();
		$query = "SELECT COUNT(id) AS total
					FROM articles 
					WHERE url LIKE '".$url."%'";
		if(!empty($article_id)) {
			$query .= " AND id <> ".$article_id;
		}
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		if(!empty($row['total'])) {
			return $url.'_'.($row['total'] + 1);
		} else {
			return $url;
		}
	}

/*
 * -----------------------------------------------------------------------------
 * COMMENT MANAGEMENT
 * -----------------------------------------------------------------------------
 */

 /**
 * get_comments
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_comments($author_id = 0) {
		// get layout config from database
		$per_page = ( (!isset($_GET['per_page'])) || (empty($_GET['per_page'])) ) 
			? 15 
			: (int)$_GET['per_page'] ;
		$conn = author_connect();
		// set up pagination
		$page_no = (isset($_GET['page'])) ? (int)$_GET['page'] : '1';
		// calculate lower query limit value
		$from = (($page_no * $per_page) - $per_page);
		$query = "SELECT comments.id, reply_id, comments.author_id, article_id, comments.title, comments.body, comments.date_uploaded,
					poster_name, poster_link, poster_email, poster_IP, approved,
					articles.title as article_title
							FROM comments 
							LEFT JOIN articles ON comments.article_id = articles.id ";
		// GET variables
		if(!empty($author_id)) {
			$where[] = " articles.author_id = ".(int)$author_id;
		}
		if(!empty($_GET['article_id'])) {
			$where[] = " comments.article_id = ".(int)$_GET['article_id'];
		}
		if(!empty($_GET['comment_id'])) {
			$where[] = " comments.id = ".(int)$_GET['comment_id'];
		}
		if(!empty($_GET['ip'])) {
			$where[] = " poster_IP LIKE '".$conn->real_escape_string($_GET['ip'])."'";
		}
		if( (isset($_GET['approved'])) && (!empty($_GET['approved'])) ) {
			$where[] = " approved = 1";
		}
		if( (isset($_GET['approved'])) && (empty($_GET['approved'])) ) {
			$where[] = " approved <> 1";
		}
		if(isset($_GET['new'])) {
			$where[] = " comments.date_uploaded > '".$_SESSION[WW_SESS]['last_login']."'";
		}
		// construct WHERE clause if needed
		if (!empty($where)) {
			$query .= " WHERE";
			$query .= implode(' AND', $where); // compile WHERE array into select statement
		}
		$query .= " ORDER BY comments.date_uploaded DESC ";
		// add pagination
		$query_paginated = $query." LIMIT ".(int)$from.", ".(int)$per_page;
		$result = $conn->query($query_paginated);
		// get total results
		$total_result = $conn->query($query);
		$total_comments = $total_result->num_rows;
		$total_pages = ceil($total_comments / $per_page);
		// build array
		$data = array();
		while($row = $result->fetch_assoc()) { 
			// adjust times to local timezone if necessary
			$ts = strtotime($row['date_uploaded']);
			$offset = date('Z');
			$row['date_ts'] = $ts+$offset;
			$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
			$row['total_pages'] = $total_pages;
			$row['total_found'] = $total_comments;
			$row['link'] = $_SERVER["PHP_SELF"].'?page_name=comments&comment_id='.$row['id'];
			$row = stripslashes_deep($row);
			$data[] = $row;
		}
		$result->close();
		$total_result->close();
		return $data;
	}

 /**
 * get_comment
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_comment() {
		
	}

 /**
 * get_commented_articles
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function get_commented_articles($author_id = 0) {
		$author_id = (int)$author_id;
		$where = array();
		$conn = author_connect();
		$query = 'SELECT articles.id, articles.title, COUNT(articles.id) AS total
				FROM comments
				LEFT JOIN articles ON comments.article_id = articles.id';
		if(!empty($author_id)) {
			$where[]= ' articles.author_id = '.(int)$author_id;
		}
		if( (isset($_GET['approved'])) && (!empty($_GET['approved'])) ) {
			$where[] = " approved = 1";
		}
		if( (isset($_GET['approved'])) && (empty($_GET['approved'])) ) {
			$where[] = " approved <> 1";
		}
		$query .= (!empty($where)) ? " WHERE ".implode(' AND ', $where) : '' ;
		$query .= '		
				GROUP BY articles.id
				ORDER BY comments.date_uploaded DESC
				';
		$result = $conn->query($query);
		$data = array();
		$total = 0;
		while($row = $result->fetch_assoc()) { 
			$row['link'] = $_SERVER["PHP_SELF"].'?page_name=comments&article_id='.$row['id'];
			$data[$row['id']] = $row;
		}
		$result->close();
		return $data;
	}

 /**
 * insert_comment_admin
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_comment_admin() {
		$conn = author_connect();
		$query = "	INSERT INTO comments
						(reply_id, author_id, article_id, 
						title, body, date_uploaded,
						poster_name, poster_link, poster_email, poster_IP,
						approved)
					VALUES
						(
						".(int)$_POST['reply_id'].",
						".(int)$_SESSION[WW_SESS]['user_id'].",
						".(int)$_POST['article_id'].",
						'".$conn->real_escape_string($_POST['title'])."',
						'".$conn->real_escape_string($_POST['body'])."',
						'".$conn->real_escape_string(gmdate('Y-m-d H:i:s'))."',
						'".$conn->real_escape_string($_SESSION[WW_SESS]['name'])."',
						'".$conn->real_escape_string(WW_WEB_ROOT)."',
						'".$conn->real_escape_string($_SESSION[WW_SESS]['email'])."',
						'".$conn->real_escape_string($_SERVER['REMOTE_ADDR'])."',
						1)";
		$result = $conn->query($query);
		if(!$result) {
			return $conn->error;
		} else {
			unset($_POST);
			clear_cache();
			$url = $_SERVER["PHP_SELF"].'?page_name=comments';
			header('Location: '.$url);
		}
	}

 /**
 * approve_comment
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function approve_comment($comment_id, $disapprove = 0) {
		$comment_id = (int)$comment_id;
		if(empty($comment_id)) {
			return false;
		}
		$approved = (empty($disapprove)) ? 1 : 0 ;
		$conn = author_connect();
		$query = "UPDATE comments SET approved = ".$approved."
					WHERE id = ".$comment_id;
		$result = $conn->query($query);
		if(!$result) {
			return $conn->error;
		} else {
			clear_cache();
			return true;
		}
	}

 /**
 * delete_comment
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function delete_comment($comment_id) {
		$comment_id = (int)$comment_id;
		if(empty($comment_id)) {
			echo 'no id';
			return false;
		}
		$conn = author_connect();
		$query = "DELETE FROM comments WHERE id = ".$comment_id;
		$result = $conn->query($query);
		if(!$result) {
			return $conn->error;
		} else {
			clear_cache();
			return true;
		}		
	}
	


/*
 * -----------------------------------------------------------------------------
 * AUTHOR UPDATE/INSERT/DELETE
 * -----------------------------------------------------------------------------
 */


 /**
 * get_author
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_author($author_id) {
		$author_id = (int)$author_id;
		if(empty($author_id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT *
					FROM authors
					WHERE id = ".(int)$author_id;
		$result = $conn->query($query);
		if(!$result) {
			return false;
		}
		$row = $result->fetch_assoc();
		if(empty($row)) {
			return false;
		}
		$result->close();
		$level = (empty($row['guest_flag'])) ? 'author' : $row['guest_areas'] ;	
		$allowed_levels = array('author','editor','contributor');
		$row['level'] = (!in_array($level,$allowed_levels)) ? 'contributor' : $level ;
		return $row;
	}

/**
 * insert_author
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_author() {
		if(!isset($_POST)) {
			return false;
		}
		if(empty($_POST['name'])) {
			return 'an author name is required';
		}
		if(validate_email($_POST['email']) !== true) {
			return 'invalid email provided';
		} else {
			$email = $_POST['email'];
		}
		$conn = author_connect();
		$name 		= clean_input($_POST['name']);
		$url 		= create_url_title($name);
		$summary 	= (isset($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		$biography 	= (isset($_POST['biography'])) ? clean_input($_POST['biography']) : '' ;
		$sub_expiry = (isset($_POST['sub_expiry'])) ? date('Y-m-d H:i:s',strtotime($_POST['sub_expiry'])) : '' ;
		$contact_flag = ((isset($_POST['contact_flag'])) && (!empty($_POST['contact_flag']))) ? 1 : 0 ;
		// create a password
		$pass = ((!isset($_POST['pass'])) && (empty($_POST['pass']))) ? substr(md5(time()),0,8) : $_POST['pass'] ;
		// access level
		switch($_POST['author_level']) {
			case 'author':
			$guest_flag = 0;
			$guest_areas = 'author';
			break;
			case 'editor':
			$guest_flag = 1;
			$guest_areas = 'editor';
			break;
			case 'contributor':
			default:
			$guest_flag = 1;
			$guest_areas = 'contributor';
			break;
		}
		// add an image if supplied
		$author_image = '';
		if(isset($_FILES['author_image'])) {
			if(empty($_FILES['author_image']['error'])) {
				$image_file = $_FILES['author_image'];
				$location = WW_ROOT.'/ww_files/images/authors/';
				$filename = $url;
				$image_data = resize_image($image_file, $location, $filename);
				// add thumbnail
				$th_filename = 'th_'.$filename;
				$th_width = $_POST['thumb_width'];
				resize_image($image_file, $location, $th_filename, $th_width);
				if(is_array($image_data)) {
					$author_image = $image_data['filename'];
				}	
			}			
		}
		// insert data		
		$insert = "INSERT INTO authors 
					(name, url, summary, biography, 
					email, pass, sub_expiry,
					guest_flag, guest_areas, image, contact_flag)
					VALUES 
					(
					'".$conn->real_escape_string($name)."',
					'".$conn->real_escape_string($url)."',
					'".$conn->real_escape_string($summary)."',
					'".$conn->real_escape_string($biography)."',
					'".$conn->real_escape_string($email)."',
					'".$conn->real_escape_string($pass)."',
					'".$conn->real_escape_string($sub_expiry)."',
					".(int)$guest_flag.",
					'".$conn->real_escape_string($guest_areas)."',
					'".$conn->real_escape_string($author_image)."',
					".(int)$contact_flag."
					)";
		$result = $conn->query($insert);
		if(!$result) {
			return $conn->error;
		} else {
			$new_id = $conn->insert_id;
			return $new_id;
		}		
	}
	
/**
 * update_author
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function update_author($author_id) {
		if(!isset($_POST)) {
			return false;
		}
		if(empty($_POST['name'])) {
			return 'an author name is required';
		}
		if(validate_email($_POST['email']) !== true) {
			return 'invalid email provided';
		} else {
			$email = $_POST['email'];
		}
		$conn = author_connect();
		$name 		= clean_input($_POST['name']);
		$url 		= create_url_title($name);
		$summary 	= (isset($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		$biography 	= (isset($_POST['biography'])) ? clean_input($_POST['biography']) : '' ;
		$contact_flag = ((isset($_POST['contact_flag'])) && (!empty($_POST['contact_flag']))) ? 1 : 0 ;
		// add an image if supplied
		$author_image = (isset($_POST['current_image'])) ? clean_input($_POST['current_image']) : '' ;
		// delete image
		if( (isset($_FILES['author_image'])) || (!empty($_POST['delete_author_image'])) ) {
			// delete existing files
			if(!empty($author_image)) {
				$location = WW_ROOT.'/ww_files/images/authors/';
				$main = $location.$author_image;
				$thumb = $location.'th_'.$author_image;
				if(file_exists($main)) {
					unlink($main);
				}
				if(file_exists($thumb)) {
					unlink($thumb);
				}
				$author_image = '';
			}		
		}
		// upload new image
		if(isset($_FILES['author_image'])) {
			if(empty($_FILES['author_image']['error'])) {
				echo 'inserting new image<br/>';
				$image_file = $_FILES['author_image'];
				$location = WW_ROOT.'/ww_files/images/authors/';
				$filename = $url;
				$th_filename = 'th_'.$filename;
				// add new image
				$image_data = resize_image($image_file, $location, $filename);
				// add thumbnail
				$th_width = $_POST['thumb_width'];
				resize_image($image_file, $location, $th_filename, $th_width);
				if(is_array($image_data)) {
					$author_image = $image_data['filename'];
				}
			}			
		}
		// update for non-author level
		if(!empty($_SESSION[WW_SESS]['guest'])) {
			$update = "UPDATE authors SET
						name = '".$conn->real_escape_string($name)."',
						url = '".$conn->real_escape_string($url)."',
						email = '".$conn->real_escape_string($email)."',
						summary = '".$conn->real_escape_string($summary)."',
						biography = '".$conn->real_escape_string($biography)."',
						image = '".$conn->real_escape_string($author_image)."',
						contact_flag = ".(int)$contact_flag."
					WHERE id = ".(int)$author_id;
		} else {
			// set expiry date
			$expiry_date = (!empty($_POST['block_author'])) ? date('Y-m-d H:i:s',strtotime('yesterday')) : '0000-00-00 00:00:00' ;
			// set author level
			switch($_POST['author_level']) {
				case 'author':
				$guest_flag = 0;
				$guest_areas = 'author';
				break;
				case 'editor':
				$guest_flag = 1;
				$guest_areas = 'editor';
				break;
				case 'contributor':
				default:
				$guest_flag = 1;
				$guest_areas = 'contributor';
				break;
			}
		// query for editor/contributor level
			$update = "UPDATE authors SET
						name = '".$conn->real_escape_string($name)."',
						url = '".$conn->real_escape_string($url)."',
						email = '".$conn->real_escape_string($email)."',
						summary = '".$conn->real_escape_string($summary)."',
						biography = '".$conn->real_escape_string($biography)."',
						image = '".$conn->real_escape_string($author_image)."',
						contact_flag = ".(int)$contact_flag.",
						sub_expiry = '".$conn->real_escape_string($expiry_date)."',
						guest_flag = ".(int)$guest_flag.",
						guest_areas = '".$conn->real_escape_string($guest_areas)."'
					WHERE id = ".(int)$author_id;
		}
		// now run query
		$update_result = $conn->query($update);
		if(!$update_result) {
			return $conn->error;
		} else {
			clear_cache();
			// get config settings
			$config = get_settings();
			// send an email to the user
			$mail = new PHPMailerLite();
			$mail->SetFrom($config['admin']['email'], $config['site']['title']);
			$mail->AddAddress($email, $name);
			$mail->AddAddress($config['admin']['email'], $config['site']['title'].' admin');
			$mail->Subject = "Your author details for ".$config['site']['title']." have been changed";
			$mail->Body = "Dear ".$name.","."\n\n";
			$mail->Body .= "Your details for ".$config['site']['title']." have been changed. Here's everything we have on you:"."\n\n";
			$mail->Body .= "\t"."Name: "."\n\t\t".$name."\n\n";
			$mail->Body .= "\t"."Email address: "."\n\t\t".$email."\n\n";
			$mail->Body .= (!empty($summary)) ? "\t"."Summary: "."\n\t\t".$summary."\n\n" : '' ;
			$mail->Body .= (!empty($biography)) ? "\t"."Biography: "."\n\t\t".$biography."\n\n" : '' ;
			$mail->Body .= "\t"."Access level: "."\n\t\t".$_POST['author_level']."\n\n";
			$block_status = (!empty($_POST['block_author'])) ? " DO NOT" : '' ;
			$mail->Body .= "\t"."You currently".$block_status." have access to the site"."\n\n";
			$contact_status = (empty($contact_flag)) ? " NOT" : '' ;
			$mail->Body .= "\t"."You are currently".$contact_status." contactable by readers of the site"."\n";
			$mail->Send();
			return true;
		}	
	}

/**
 * change_password
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function change_author_password($author_id) {
		if(!isset($_POST)) {
			return false;
		}
		$author_id = (int)$author_id;
		if(empty($author_id)) {
			return false;
		}
		if(empty($_POST['old_pass'])) {
			return 'old password must be entered';
		}
		if(empty($_POST['new_pass'])) {
			return 'new password must be entered';
		}
		if(empty($_POST['confirm_pass'])) {
			return 'confirm password must be entered';
		}
		// check new pass and confirm pass match
		$new_check = (strcmp($_POST['new_pass'],$_POST['confirm_pass']) == 0) ? 1 : 0 ;
		if(empty($new_check)) {
			return 'new pass and confirm pass do not match';
		}
		// get existing password
		$conn = reader_connect();
		$query = "SELECT pass, email, name
					FROM authors
					WHERE id = ".(int)$author_id;
		$result = $conn->query($query);
		$author = $result->fetch_assoc();
		$result->close();
		if(empty($author)) {
			return 'selected author not found';
		}
		$password_check = (strcmp($_POST['old_pass'],$author['pass']) == 0) ? 1 : 0 ;
		if(empty($password_check)) {
			return 'existing password incorrect';
		}
		// we can now proceed
		$update = "UPDATE authors SET 
					pass = '".$conn->real_escape_string($_POST['new_pass'])."'
					WHERE id = ".(int)$author_id;
		$update_result = $conn->query($update);
		if(!$update_result) {
			return $conn->error;
		} else {
			// get config settings
			$config = get_settings();
			// send an email to the user
			$mail = new PHPMailerLite();
			$mail->SetFrom($config['admin']['email'], $config['site']['title']);
			$mail->AddAddress($author['email'], $author['name']);
			$mail->Subject = "Your password for ".$config['site']['title']." has been changed";
			$mail->Body = "Dear ".$author['name'].","."\n\n";
			$mail->Body .= "Your password for ".$config['site']['title']." has been changed to:"."\n\n";
			$mail->Body .= "\t".$_POST['new_pass'];
			$mail->Send();
			return true;
		}
	}

/**
 * delete_author
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function delete_author($author_id) {
		
	}

/*
 * -----------------------------------------------------------------------------
 * CATEGORY UPDATE/INSERT/DELETE
 * -----------------------------------------------------------------------------
 */

/**
 * quick_insert_category
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function quick_insert_category($category_name) {
		$check = validate_new_category($category_name);
		if($check !== true) {
			return $check;
		}
		$conn = author_connect();
		$category_title = clean_input($category_name);
		$category_url = create_url_title($category_title);
		$insert = "INSERT INTO categories 
					(title, url)
					VALUES 
					('".$category_title."','".$category_url."')";				
		$result = $conn->query($insert);
		if(!$result) {
			return $conn->error;
		} else {
			$new_id = $conn->insert_id;
			return $new_id;
		}		
	}

/**
 * insert_category
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_category() {
		if(!isset($_POST)) {
			return false;
		}
		$check = validate_new_category($_POST['title']);
		if($check !== true) {
			return $check;
		}
		$conn = author_connect();
		$title 		= clean_input($_POST['title']);
		$url 		= create_url_title($title);
		$parent_id = 0;
		if(!empty($_POST['category_id'])) {
			$parent_id = (int)$_POST['category_id'];
			// get url value of parent
			$query = "SELECT url FROM categories WHERE id = ".$parent_id;
			$result = $conn->query($query);
			$row = $result->fetch_assoc();
			$url = $row['url'].'-'.$url;
		}
		$summary 	= (!empty($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		$description = (!empty($_POST['description'])) ? clean_input($_POST['description']) : '' ;
		$type 		= (!empty($_POST['type'])) ? clean_input($_POST['type']) : '' ;
		$insert = "INSERT INTO categories 
					(title, url, category_id, summary, description, type)
					VALUES 
					(
					'".$conn->real_escape_string($title)."',
					'".$conn->real_escape_string($url)."',
					".(int)$parent_id.",
					'".$conn->real_escape_string($summary)."',
					'".$conn->real_escape_string($description)."',
					'".$conn->real_escape_string($type)."'
					)";
		$result = $conn->query($insert);
		if(!$result) {
			return $conn->error;
		} else {
			$new_id = $conn->insert_id;
			return $new_id;
		}		
	}

/**
 * validate_new_category
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function validate_new_category($title) {
		// check for content
		if(empty($title)) {
			return 'No category title entered';
		}
		// check category is not an integer
		if(is_int($title)) {
			return 'Category cannot be an integer';
		}
		// reserved system terms
		$reserved = array('author','admin','download','id', 'feeds','feed','podcast','rss','rss-external',
				'search','sitemap','tag');
		$title = trim(strtolower($title));
		if(in_array($title, $reserved)) {
			return $title.' is a reserved system term and cannot be used as a category';
		}
		return true;
	}

/**
 * update_category
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function update_category($category_id) {
		if(empty($category_id)) {
			return false;
		}
		$check = validate_new_category($_POST['title']);
		if($check !== true) {
			return $check;
		}
		$conn = author_connect();
		// convert csv to array
		$title 		= clean_input($_POST['title']);
		$url 		= (!empty($_POST['update_url'])) ? create_url_title($title) : $_POST['url'] ;
		$parent_id = 0;
		if(!empty($_POST['category_id'])) {
			$parent_id = (int)$_POST['category_id'];
			// get url value of parent
			$query = "SELECT url FROM categories WHERE id = ".$parent_id;
			$result = $conn->query($query);
			$row = $result->fetch_assoc();
			$url = $row['url'].'-'.$url;
		}
		$summary 	= (!empty($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		$description = (!empty($_POST['description'])) ? clean_input($_POST['description']) : '' ;
		$type 		= (!empty($_POST['type'])) ? clean_input($_POST['type']) : '' ;
		$query = "UPDATE categories SET
					title = '".$conn->real_escape_string($title)."',
					url = '".$conn->real_escape_string($url)."',
					category_id = ".(int)$parent_id.",
					summary = '".$conn->real_escape_string($summary)."',
					description = '".$conn->real_escape_string($description)."',
					type = '".$conn->real_escape_string($type)."'
					WHERE id = ".(int)$category_id;
		$result = $conn->query($query);
		if(!$result) {
			return $conn->error;
		} else {
			clear_cache();
			return true;
		}		
	}

/**
 * delete_category
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function delete_category($category_id) {
		if(empty($category_id)) {
			return false;
		}
		$conn = author_connect();
		// delete tag
		$delete = "DELETE FROM categories WHERE id = ".(int)$category_id;
		$result = $conn->query($delete);
		if(!$result) {
			return $conn->error;
		} else {
			return true;
		}		
	}

/*
 * -----------------------------------------------------------------------------
 * TAGS UPDATE/INSERT/DELETE
 * -----------------------------------------------------------------------------
 */

/**
 * quick_insert_tags
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function quick_insert_tags($tags_string) {
		if(empty($tags_string)) {
			return 0;
		}
		$conn = author_connect();
		// convert csv to array
		$tags_array = explode(",",$tags_string);
		$new_tags_id = array();
		foreach($tags_array as $tag_name) {
			$check = validate_new_tag($tag_name);
			if($check !== true) {
				$new_tags_id[] = $check;
			} else {
				$tag_title = clean_input($tag_name);
				$tag_url = create_url_title($tag_title);
				$insert = "INSERT INTO tags 
							(title, url)
							VALUES 
							('".$tag_title."','".$tag_url."')";
				$conn->query($insert);
				$new_tags_id[] = $conn->insert_id;				
			}
		}
		return $new_tags_id;
	}

/**
 * insert_tag
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_tag() {
		if(!isset($_POST)) {
			return false;
		}
		$check = validate_new_tag($_POST['title']);
		if($check !== true) {
			return $check;
		}
		$conn = author_connect();
		// convert csv to array
		$title 	= clean_input($_POST['title']);
		$url 	= create_url_title($title);
		$summary = (!empty($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		$insert = "INSERT INTO tags 
					(title, url, summary)
					VALUES 
					('".$conn->real_escape_string($title)."',
					'".$conn->real_escape_string($url)."',
					'".$conn->real_escape_string($summary)."')";
		$result = $conn->query($insert);
		if(!$result) {
			return $conn->error;
		} else {
			$new_tag_id = $conn->insert_id;
			return $new_tag_id;
		}
	}

/**
 * validate_new_tag
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function validate_new_tag($title) {
		// check for content
		if(empty($title)) {
			return 'No tag name entered';
		}
		// check category is not an integer
		if(is_int($title)) {
			return 'Tag cannot be an integer';
		}
		// reserved system terms
		$reserved = array('author', 'page', 'p', 'tag');
		$title = trim(strtolower($title));
		if(in_array($title, $reserved)) {
			return $title.' is a reserved system term and cannot be used as a tag';
		}
		return true;
	}

/**
 * update_tag
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function update_tag($tag_id) {
		if(empty($tag_id)) {
			return false;
		}
		$check = validate_new_tag($_POST['title']);
		if($check !== true) {
			return $check;
		}
		$conn = author_connect();
		// convert csv to array
		$title 	= clean_input($_POST['title']);
		$url 	= create_url_title($title);
		$summary = (!empty($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		$query = "UPDATE tags SET
					title = '".$conn->real_escape_string($title)."',
					url = '".$conn->real_escape_string($url)."',
					summary = '".$conn->real_escape_string($summary)."'
					WHERE id = ".(int)$tag_id;
		$result = $conn->query($query);
		if(!$result) {
			return $conn->error;
		} else {
			clear_cache();
			return true;
		}		
	}

/**
 * delete_tag
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function delete_tag($tag_id) {
		if(empty($tag_id)) {
			return false;
		}
		$conn = author_connect();
		// delete tag
		$delete = "DELETE FROM tags WHERE id = ".(int)$tag_id;
		$result = $conn->query($delete);
		if(!$result) {
			return $conn->error;
		}
		// clean tags map
		$clean = "DELETE FROM tags_map WHERE tag_id = ".(int)$tag_id;
		$result_c = $conn->query($clean);
		if(!$result_c) {
			return $conn->error;
		} else {
			clear_cache();
			return true;
		}
	}

/**
 * -----------------------------------------------------------------------------
 * IMAGE EDIT/INSERT FUNCTIONS
 * -----------------------------------------------------------------------------
 */

/**
 * get_images
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_images($per_page = 15, $author_only = 0) {
		$conn = author_connect();
		// pagination
		$page_no 	= (empty($_GET['page'])) ? '1' : (int)$_GET['page'] ;
		$from 		= (($page_no * $per_page) - $per_page);
		$to			= ($page_no * $per_page);		
		// query
		$query = "SELECT * FROM images";
		if(!empty($author_only)) {
			$query .= " WHERE author_id = ".(int)$_GET['author_id'];		
		}
		$query .= " ORDER BY date_uploaded DESC";
		
		// add pagination
			$query_paginated = $query." LIMIT ".(int)$from.", ".(int)$per_page;
			$result = $conn->query($query_paginated);
		// get total results
			$total_result = $conn->query($query);
			$total_images = $total_result->num_rows;
			$total_pages = ceil($total_images / $per_page);
		$data = array();
		// get image url
		$url = WW_REAL_WEB_ROOT.'/ww_files/images/';
		while($row = $result->fetch_assoc()) {
			$row = stripslashes_deep($row);
			// adjust times to local timezone if necessary
			$ts = strtotime($row['date_uploaded']);
			$offset = date('Z');
			$row['date_ts'] = $ts+$offset;
			$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
			$row['total_images'] = $total_images;
			$row['total_pages'] = $total_pages;
			$row['src'] = $url.$row['filename'];
			$row['thumb_src'] = $url.'thumbs/'.$row['filename'];
			$data[] = $row;
		}
		$result->close();
		$total_result->close();
		return $data;		
	}

/**
 * get_image_orphans
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_image_orphans() {
		// get list of images in database
		$conn = author_connect();
		$query = "SELECT filename FROM images";
		$result = $conn->query($query);
		$db_list = array();
		while($row = $result->fetch_assoc()) { 
			$db_list[] = $row['filename'];
		}
		$result->close();
		// get list of images in images folder
		$image_folder = WW_ROOT.'/ww_files/images/';
		$files = get_files($image_folder);
		$file_list = array();
		foreach($files as $file) {
			$file_list[] = $file['filename'];
		}
		$orphans = array();
		$files_diff = array_diff($file_list, $db_list);
		if(!empty($files_diff)) {
			$orphans['files'] = $files_diff;
		}
		$db_diff 	= array_diff($db_list,$file_list);
		if(!empty($db_diff)) {
			$orphans['db'] = $db_diff;
		}
		return $orphans;
	}

/**
 * get_image
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function get_image($image_id) {
		$conn = author_connect();
		$query = "SELECT images.id, filename, images.title, alt,
					credit, caption, author_id, authors.name as author_name,
					width, height, ext, mime, size, date_uploaded
					FROM images
					LEFT JOIN authors on authors.id = author_id
					WHERE images.id = ".$image_id;		
		$result = $conn->query($query);
		$data = array();
		$row = $result->fetch_assoc();
		$row = stripslashes_deep($row);
		// get thumb details
		$thumb = WW_ROOT.'/ww_files/images/thumbs/'.$row['filename'];
		if(file_exists($thumb)) {
			$thumb_size 	= getimagesize($thumb);
			$thumb_width 	= $thumb_size[0];
			$thumb_height 	= $thumb_size[1];		
		}
		// get image url
		$url = WW_REAL_WEB_ROOT.'/ww_files/images/';
		// adjust times to local timezone if necessary
		$ts = strtotime($row['date_uploaded']);
		$offset = date('Z');
		$row['date_ts'] = $ts+$offset;
		$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
		// add to array
		$row['src'] = $url.$row['filename'];
		$row['thumb_src'] = $url.'thumbs/'.$row['filename'];
		$row['thumb_width'] = (isset($thumb_width)) ? $thumb_width : 0 ;
		$row['thumb_height'] = (isset($thumb_height)) ? $thumb_height : 0 ;
		$result->close();
		return $row;
	}

/**
 * insert_image
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function insert_image() {
		// check data was sent
		if(!isset($_POST)) {
			return 'no post data sent';
		}
		if(!isset($_FILES)) {
			return 'no file data sent';
		}
		// get default settings
		$config = get_settings('files');
		// resize / upload image
		$image_file = $_FILES['image_file'];
		if(!empty($image_file['error'])) {
			return 'no image uploaded';
		} else {
			if($image_file['size'] > $config['files']['max_image_size']) {
				return 'image file is too large';
			}
			$location = WW_ROOT.'/ww_files/images/';
			// check posted width
			$width = (isset($_POST['width'])) ? (int)$_POST['width'] : 0 ;
			$width = ($width > $config['files']['max_image_width']) ? $config['files']['max_image_width'] : $width ;
			// check file width
			if(empty($width)) {
				list($o_width)= getimagesize($image_file['tmp_name']);
				$width = ($o_width > $config['files']['max_image_width']) ? $config['files']['max_image_width'] : $width ;
			}
			$image_data = resize_image($image_file,$location,'',$width);
		}
		// check image was uploaded
		if(!is_array($image_data)) {
			return $image_data;
		}
		// thumbnail?
		$th_location = WW_ROOT.'/ww_files/images/thumbs/';
		if( (isset($_FILES['thumb_file'])) && (empty($_FILES['thumb_file']['error'])) ) {
			$thumb_file = $_FILES['thumb_file'];
			resize_image($thumb_file,$th_location);				
		} else {
			// generate a thumbnail
			$th_width = $config['files']['thumb_width'];
			resize_image($image_file,$th_location,'',$th_width);	
		}	
		// now we can finally insert into the database
		insert_image_details($image_data);
		return;
	}

/**
 * insert_image_details
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function insert_image_details($image_data) {
		// check data was sent
		if(!isset($_POST)) {
			return 'no post data sent';
		}	
		$conn = author_connect();
		$title 	= (!empty($_POST['title'])) ? $_POST['title'] 	: $image_data['filename'] ;
		$alt 	= (!empty($_POST['alt'])) 	? $_POST['alt'] 	: $image_data['filename'] ;
		$credit = (!empty($_POST['credit'])) ? $_POST['credit'] : '' ;
		$caption = (!empty($_POST['caption'])) ? $_POST['caption'] : '' ;
		$author_id = (defined('WW_SESS')) ? $_SESSION[WW_SESS]['user_id'] : $_GET['author_id'] ;
		$query = "INSERT INTO images 
				(filename, title, alt, credit, caption, 
				author_id, width, height, ext, mime, size, date_uploaded)
			VALUES 
				('".$conn->real_escape_string($image_data['filename'])."',
				'".$conn->real_escape_string($title)."',
				'".$conn->real_escape_string($alt)."',
				'".$conn->real_escape_string($credit)."',
				'".$conn->real_escape_string($caption)."',
				".(int)$author_id.",
				".(int)$image_data['width'].",
				".(int)$image_data['height'].",
				'".$conn->real_escape_string($image_data['ext'])."',
				'".$conn->real_escape_string($image_data['mime'])."',
				".(int)$image_data['size'].",
				'".$conn->real_escape_string(gmdate('Y-m-d H:i:s'))."')";
		$result = $conn->query($query);
		$new_id = $conn->insert_id;
		if(!$result) {
			return $conn->error;
		} else {
			return $new_id;
		}	
	}

/**
 * update_image
 * 
 * takes POSTed details and updated details of image in database
 * 
 * @param	array	$post		array of POSTed
 * @return	bool	$result		result of insert query
 */


	function update_image($id) {
		$id = (int)$id;
		if(empty($id)) {
			return false;
		}
		$conn = author_connect();
		// prepare other variables
		$title 		= (empty($_POST['title'])) 	? $_POST['filename'] : clean_input($_POST['title']);
		$alt 		= (empty($_POST['alt'])) 	? $title 	: clean_input($_POST['alt']);
		$caption 	= (empty($_POST['caption']))? '' 		: clean_input($_POST['caption']);
		$credit 	= (empty($_POST['credit']))	? '' 		: clean_input($_POST['credit']);
		$ext 		= (empty($_POST['ext']))	? '' 		: clean_input($_POST['ext']);
		$mime	 	= (empty($_POST['mime']))	? '' 		: clean_input($_POST['mime']);
		$query = "UPDATE images SET 
							title 	= '".$conn->real_escape_string($title)."', 
							alt 	= '".$conn->real_escape_string($alt)."',
							caption = '".$conn->real_escape_string($caption)."',
							ext 	= '".$conn->real_escape_string($ext)."',
							mime 	= '".$conn->real_escape_string($mime)."',
							credit 	= '".$conn->real_escape_string($credit)."'  
						WHERE id = ".$id;
		$conn->query($query);
		return true;
	}

/**
 * replace_image
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function replace_image($location, $new, $current, $width = 0) {
		$width = (int)$width;
		$replacement = replace_file($location, $new, $current, $width);
		if(!is_array($replacement)) {
			return $replacement;
		}
		$conn = author_connect();
		$query = "UPDATE images SET size = '".(int)$replacement['size']."', 
									width = '".(int)$replacement['width']."',
									height = '".(int)$replacement['height']."',
									ext = '".$conn->real_escape_string($replacement['ext'])."',
									mime = '".$conn->real_escape_string($replacement['mime'])."'
									WHERE filename LIKE '".$conn->real_escape_string($replacement['name'])."'";
		$conn->query($query);
		return true;
	}

/**
 * delete_image
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function delete_image($filename) {
		if(empty($filename)) {
			return 'No filename specified';
		}
		$path = WW_ROOT.'/ww_files/images';
		$thumb = $path.'/thumbs/'.$filename;
		$image = $path.'/'.$filename;
		// delete thumb
		if(file_exists($thumb)) {
			unlink($thumb);
		}
		// delete image
		if(file_exists($image)) {
			unlink($image);
		}
		// delete database entry
		$conn = author_connect();
		$query = "DELETE FROM images WHERE filename LIKE '".$filename."'";
		$result = $conn->query($query);
		if(!$result) {
			return 'There was a problem deleting the image: '.$conn->error;
		} else {
			return true;
		}
	}

/**
 * -----------------------------------------------------------------------------
 * ATTACHMENT EDIT/INSERT FUNCTIONS
 * -----------------------------------------------------------------------------
 */

/**
 * get_attachments
 * 
 * 
 * 
 * 
 * 
 * 
 */	


	function get_attachments($per_page = 15, $author_only = 0) {
		$conn = author_connect();
		
		// pagination
		$page_no 	= (empty($_GET['page'])) ? '1' : (int)$_GET['page'] ;
		$from 		= (($page_no * $per_page) - $per_page);
		$to			= ($page_no * $per_page);		
		
		// query
		$where = array();
		$query = "SELECT 
					attachments.id, attachments.title, filename, 
					author_id, authors.name as author_name,
					ext, size, mime, downloads, attachments.date_uploaded
				FROM attachments 
				LEFT JOIN authors on authors.id = author_id";
		if(!empty($author_only)) {
			$where[] = " author_id = ".(int)$_GET['author_id'];		
		}
		if(isset($_GET['ext'])) {
			$where[] = " ext = '".$conn->real_escape_string($_GET['ext'])."'";		
		}
		$query .= (!empty($where)) ? ' WHERE '.implode(' AND ', $where) : '' ;
		$query .= " ORDER BY date_uploaded DESC";
		// echo $query;
		// add pagination
			$query_paginated = $query." LIMIT ".(int)$from.", ".(int)$per_page;
			$result = $conn->query($query_paginated);
		
		// get total results
			$total_result = $conn->query($query);
			$total_files = $total_result->num_rows;
			$total_pages = ceil($total_files / $per_page);

		// get image url
		$data = array();
		$url = WW_REAL_WEB_ROOT.'/ww_files/attachments/';
		while($row = $total_result->fetch_assoc()) {
			// adjust times to local timezone if necessary
			$ts = strtotime($row['date_uploaded']);
			$offset = date('Z');
			$row['date_ts'] = $ts+$offset;
			$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
			$row['total_files'] = $total_files;
			$row['total_pages'] = $total_pages;			
			$row['src'] = $url.$row['ext'].'/'.$row['filename'];
			$data[] = $row;
		}
		$result->close();
		$total_result->close();
		return $data;		
	}

/**
 * get_attachment_orphans
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_attachment_orphans($ext) {
		// get list of images in database
		$conn = author_connect();
		$query = "SELECT filename 
					FROM attachments 
					WHERE ext LIKE '".$conn->real_escape_string($ext)."'";
		$result = $conn->query($query);
		$db_list = array();
		while($row = $result->fetch_assoc()) { 
			$db_list[] = $row['filename'];
		}
		// get list of images in images folder
		$attachment_folder = WW_ROOT.'/ww_files/attachments/'.$ext.'/';
		$files = get_files($attachment_folder);
		$file_list = array();
		foreach($files as $file) {
			$file_list[] = $file['filename'];
		}
		$orphans = array();
		$files_diff = array_diff($file_list, $db_list);
		if(!empty($files_diff)) {
			$orphans['files'] = $files_diff;
		}
		$db_diff 	= array_diff($db_list,$file_list);
		if(!empty($db_diff)) {
			$orphans['db'] = $db_diff;
		}
		return $orphans;
	}

/**
 * get_attachment
 * 
 * 
 * 
 * 
 * 
 * 
 */	


	function get_attachment($attachment_id) {
		if(empty($attachment_id)) {
			return false;
		}
		$conn = author_connect();
		$query = "SELECT 
					attachments.id, attachments.title, filename, 
					author_id, authors.name as author_name, attachments.summary,
					ext, size, mime, downloads, attachments.date_uploaded
				FROM attachments 
				LEFT JOIN authors on authors.id = author_id
				WHERE attachments.id = ".(int)$attachment_id;
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		// adjust times to local timezone if necessary
		$ts = strtotime($row['date_uploaded']);
		$offset = date('Z');
		$row['date_ts'] = $ts+$offset;
		$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
		$row['src'] = WW_WEB_ROOT.'/ww_files/attachments/'.$row['ext'].'/'.$row['filename'];
		return $row;		
	}

/**
 * attachment_usage
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function attachment_usage($attachment_id) {
		if(empty($attachment_id)) {
			return false;
		}
		$conn = author_connect();
		$query = "SELECT article_id, articles.url, articles.title
					FROM attachments_map
					LEFT JOIN articles ON article_id = articles.id
					WHERE attachment_id = ".(int)$attachment_id;
		$result = $conn->query($query);
		$data = array();
		if(empty($result)) {
			return $data;
		}
		while($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		$result->close();
		return $data;
	}



/**
 * insert_attachment
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_attachment() {
		// check data was sent
		if(!isset($_POST)) {
			return 'no post data sent';
		}
		if(!isset($_FILES)) {
			return 'no file data sent';
		}
		// get default settings
		$config = get_settings('files');
		// resize / upload image
		$attachment_file = $_FILES['attachment_file'];
		if(!empty($attachment_file['error'])) {
			return 'no image uploaded';
		} else {
			if($attachment_file['size'] > $config['files']['max_file_size']) {
				return 'file is too large';
			}
			$ext = pathinfo($attachment_file['name']);
			$ext = strtolower($ext['extension']);
			$location = WW_ROOT.'/ww_files/attachments/'.$ext.'/';
			$file_data = upload_file($attachment_file,$location);
		}
		// check image was uploaded
		if(!is_array($file_data)) {
			return $file_data;
		}
		// now we can insert into the database
		$conn = author_connect();
		$title 		= (!empty($_POST['title'])) ? $_POST['title'] : $file_data['name'] ;
		$summary	= (!empty($_POST['summary'])) ? $_POST['summary'] : '' ;
		$author_id 	= (defined('WW_SESS')) ? $_SESSION[WW_SESS]['user_id'] : $_POST['author_id'] ;
		$query = "INSERT INTO attachments 
				(filename, title, summary, 
				author_id, ext, mime, size, date_uploaded)
			VALUES 
				('".$conn->real_escape_string($file_data['filename'])."',
				'".$conn->real_escape_string($title)."',
				'".$conn->real_escape_string($summary)."',
				".(int)$author_id.",
				'".$conn->real_escape_string($file_data['ext'])."',
				'".$conn->real_escape_string($file_data['mime'])."',
				".(int)$file_data['size'].",
				'".$conn->real_escape_string(gmdate('Y-m-d H:i:s'))."')";
		$result = $conn->query($query);
		if(!$result) {
			unlink($location.$file_data['filename']);
			return $conn->error;
		} else {
			$new_id = $conn->insert_id;
			return $new_id;
		}		
	}


/**
 * insert_attachment_details
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_attachment_details($attachment_data) {
		// check data was sent
		if(!isset($_POST)) {
			return 'no post data sent';
		}	
		$conn = author_connect();
		$title 	= (!empty($_POST['title'])) ? $_POST['title'] 	: $attachment_data['filename'] ;
		$summary = (!empty($_POST['summary'])) ? $_POST['summary'] : '' ;
		$author_id = (defined('WW_SESS')) ? $_SESSION[WW_SESS]['user_id'] : $_GET['author_id'] ;
		$query = "INSERT INTO attachments 
				(filename, title, summary, 
				author_id, ext, mime, size, date_uploaded)
			VALUES 
				('".$conn->real_escape_string($attachment_data['filename'])."',
				'".$conn->real_escape_string($title)."',
				'".$conn->real_escape_string($summary)."',
				".(int)$author_id.",
				'".$conn->real_escape_string($attachment_data['ext'])."',
				'".$conn->real_escape_string($attachment_data['mime'])."',
				".(int)$attachment_data['size'].",
				'".$conn->real_escape_string(gmdate('Y-m-d H:i:s'))."')";
		$result = $conn->query($query);
		$new_id = $conn->insert_id;
		if(!$result) {
			return $conn->error;
		} else {
			return $new_id;
		}		
	}

/**
 * update_attachment
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function update_attachment($id) {
		$id = (int)$id;
		if(empty($id)) {
			return false;
		}
		$conn = author_connect();
		// prepare other variables
		$title 		= (empty($_POST['title'])) 		? clean_input($_POST['filename']) : clean_input($_POST['title']);
		$summary 	= (empty($_POST['summary'])) 	? '' 	: clean_input($_POST['summary']);
		$query = "UPDATE attachments SET 
							title 	= '".$conn->real_escape_string($title)."', 
							summary = '".$conn->real_escape_string($summary)."'  
						WHERE id = ".$id;
		$conn->query($query);
		return true;		
	}

/**
 * replace_attachment
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function replace_attachment($location, $new, $current) {
		$replacement = replace_file($location, $new, $current);
		if(!is_array($replacement)) {
			return $replacement;
		}
		$conn = author_connect();
		$query = "UPDATE attachments SET size = '".(int)$replacement['size']."', 
									ext = '".$conn->real_escape_string($replacement['ext'])."',
									mime = '".$conn->real_escape_string($replacement['mime'])."'
									WHERE filename LIKE '".$conn->real_escape_string($replacement['filename'])."'";
		$conn->query($query);
		return true;
	}
	
/**
 * delete_attachment
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function delete_attachment($filename, $ext) {
		if(empty($filename)) {
			return 'No filename specified';
		}
		$path = WW_ROOT.'/ww_files/attachments/'.$ext;
		$file = $path.'/'.$filename;
		// delete file
		if(file_exists($file)) {
			unlink($file);
		}
		// delete database entry
		$conn = author_connect();
		$query = "DELETE FROM attachments WHERE filename LIKE '".$filename."'";
		$result = $conn->query($query);
		if(!$result) {
			return 'There was a problem deleting the attachment: '.$conn->error;
		} else {
			return true;
		}
	}



/**
 * -----------------------------------------------------------------------------
 * FILE UPLOAD/IMAGE RESIZE
 * -----------------------------------------------------------------------------
 */

/**
 * file_usage
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function file_usage($filename) {
		$conn = author_connect();
		$query = "SELECT DISTINCT id, title, url 
					FROM articles 
					WHERE MATCH(art_body) 
					AGAINST ('\"".$filename."\"' IN BOOLEAN MODE)";					
		$result = $conn->query($query);
		$data = array();
		if(empty($result)) {
			return $data;
		}
		while ($row = $result->fetch_assoc()) { 
		$data[$row['id']] = array (
			'id'	=> $row['id'],
			'title'	=> stripslashes($row['title']),
			'url'	=> $row['url'],
			);
		}
		$result->close();
		return $data;		
	}



/**
 * upload_file()
 * 
 * function to take an image upload and resize (if required) 
 * will copy processed image to specified folder
 * requires function check_file_upload() for initial upload validation
 * 
 * @param	array	$file			uploaded $_FILES array
 * @param	array	$allowed		optional array of allowed filetypes
 * @param	int		$max_filesize	maximum filesize of uploaded file
 * @param	string	$newlocation	location for file to be uploaded to
 * @param	string	$newfilename	new filename (optional)
 * 
 * @return	mixed	$result|$new_file	returns error string if error encountered
 * 										otherwise returns array holding file details
 */
 
	function upload_file (	$file, // e.g. $_FILES['file']	
							$location 		= '/ww_files/unsorted/',
							$setfilename 	= '') {
			
		// check file uploaded and is in correct format
			$upload_error = check_file_upload($file, $location);
			if(!empty($upload_error)) {
				return $upload_error;
			}

		// get mime and filetype
			$path = pathinfo($file['name']);
			$ext = strtolower($path['extension']);

		// if a new filename isn't specified then use original filename
			$filename = (empty($setfilename)) ? $file['name'] : $setfilename ;

		// make sure extension is appended
			$extlen = strlen($ext);
			if (substr($filename,(0-$extlen)) != $ext) {
				$filename = $filename.".".$ext;
			}
			
		// strip spaces from filename
			$filename = str_replace(' ','_',$filename);
			
		// check for end slash on location
			$location = end_slash($location);
			
		// check file doesn't already exist 
			if (file_exists($location.$filename)) {
				$result = "Upload error: file ".$filename." already exists";
				return $result;
			}
			
		// move file
			$upload_file = $location.$filename;
			if (!move_uploaded_file($file['tmp_name'], $upload_file)) {
				return "Upload error: There was a problem uploading the file";
			} else {
				// chmod($upload_file, 0644);
				$new_file = array();
				$new_file['filename'] 	= $filename;
				$new_file['size'] 	= $file['size'];
				$new_file['mime'] 	= $file['type'];
				$new_file['ext'] 	= $ext;
				// if image file then get width and height
				$img_ext = array('gif','png','jpg');
				if(in_array($ext, $img_ext)) {
					$img_details = getimagesize($upload_file);
					$new_file['width'] 	= $img_details[0];
					$new_file['height'] = $img_details[1];
					$new_file['mime'] 	= $img_details['mime'];
				}
				return $new_file;
			}
			return false;
	}

/**
 * replace_file
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function replace_file($location, $new_file, $current_file, $new_width = 0) {
		if(!isset($_FILES)) {
			return 'No file uploaded';
		}
		// check for end slash on location
		$location 	= end_slash($location);
		// get extension of uploaded file
		$new_path 	= pathinfo($new_file['name']);
		$new_ext 	= strtolower($new_path['extension']);
		// get current filename
		$current = 	pathinfo($current_file);
		$current_ext = $current['extension'];
		// check extensions match
		if($new_ext != $current_ext) {
			return 'File types don\'t match';
		}
		// delete existing file	
		$current_file_path = $location.$current_file;	
		if(file_exists($current_file_path)) {
			unlink($current_file_path);
		}
		// check file upload
		$check_file = check_file_upload($new_file, $location);
		if(!empty($check_file)) {
			return $check_file;
		}
		// is it an image
		$img_array = array('gif','jpg','png');
		if( (in_array($new_ext,$img_array)) && (!empty($new_width)) ) {
			$replaced_file = resize_image($new_file, $location, $current_file, $new_width);
		} else {
			$replaced_file = upload_file($new_file, $location, $current_file);
		}
		return $replaced_file;
	}

/**
 * resize_img()
 * 
 * function to take an image upload and resize (if required) 
 * will copy processed image to specified folder
 * requires function check_file_upload() for initial upload validation
 * 
 * @param	array	$file			uploaded $_FILES array	
 * @param	string	$newlocation	location for file to be uploaded to
 * @param	int		$width			new width of file (optional)
 * @param	string	$newfilename	new filename (optional)
 * @param	int		$quality		0-100 (only applicable to jpg uploads)
 * @param	string	$credit			option to burn in a credit on the final image
 * 
 * @return	mixed	$result|$new_file	returns error string if error encountered
 * 										otherwise returns array holding image details
 */
 
	function resize_image (	$file, // e.g. $_FILES['file']
							$location = "images/",
							$setfilename = 0, // default of 0 will cause original filename to be used
							$setwidth = 0, // default of 0 will retain original width
							$quality = 75,
							$credit = '') {
			
		//Check if GD extension is loaded
			if (!extension_loaded('gd') && !extension_loaded('gd2')) {
			    if(!empty($setwidth)) {
			  		return "Image resize: GD extension is not loaded - image cannot be resized";			    	
			    } else {
			    	$uploaded = upload_file ($new_file, $location, $filename);
			    	return $uploaded;
			    }

			} // should possibly switch to regular file upload here
		
		// check for end slash on location
			$location = end_slash($location);
		
		// check file uploaded and is in correct format
			$allowed = array('jpg','gif','jpeg','png');
			$upload_error = check_file_upload($file, $location, $allowed);
			if(!empty($upload_error)) {
				return $upload_error;
			}
			
		// grab some basic file info
			$ext = pathinfo($file['name']);
			$ext = strtolower($ext['extension']);
			$tempfile = $file['tmp_name'];
			list($o_width,$o_height,$img_type)= getimagesize($tempfile);
			
		// if a new width hasn't been set keep original width
			$width = (empty($setwidth)) ? $o_width : $setwidth ;
			
		// if a new filename isn't specified then use original filename
			$filename = (empty($setfilename)) ? $file['name'] : $setfilename ;
			
		// make sure extension is appended
			$extlen = strlen($ext);
			if (substr($filename,(0-$extlen)) != $ext) {
				$filename = $filename.".".$ext;
			}
			
		// strip spaces from filename
			$filename = str_replace(' ','_',$filename);

		// check file doesn't already exist 
			if (file_exists($location.$filename)) {
				return "Image resize: file ".$filename." already exists";
			}
		
		// which image type are we processing?
			switch ($img_type) {
				case 1: $src = imagecreatefromgif($tempfile); 	break; // type 1 = gif
				case 2: $src = imagecreatefromjpeg($tempfile);  break; // type 2 = jpeg
				case 3: $src = imagecreatefrompng($tempfile); 	break;// type 3 = png
				default: $result = "Image resize: Unsupported filetype"; return $result; break;
			}
			
		// process image
			$height=($o_height/$o_width)*$width;
			$tmp = imagecreatetruecolor($width,$height);	
			// check if this image is PNG or GIF, then set if Transparent
		    if(($img_type == 1) OR ($img_type == 3)) {
				imagealphablending($tmp, false);
				imagesavealpha($tmp,true);
				$transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
				imagefilledrectangle($tmp, 0, 0, $width, $height, $transparent);
		    }
			imagecopyresampled($tmp,$src,0,0,0,0,$width,$height,$o_width,$o_height);
			// add copyright if provided
			if(!empty($credit)) {
				$textheight = ($newheight-4);
				// create a colour including opacity (last param)
				$grey = imagecolorallocatealpha ($tmp, 170, 170, 170, 50);
				imagestring($tmp, 2, 4, 4, $credit, $grey);
			}
			$newfile = $location.$filename;
				
			switch ($img_type) {
				case 1: $new_img = imagegif($tmp,$newfile); break; // type 1 = gif
				case 2: $new_img = imagejpeg($tmp,$newfile,$quality);  break; // type 2 = jpeg
				case 3: $new_img = imagepng($tmp,$newfile); break;// type 3 = png
				default: $result = "Image resize: resize failed"; return $result; break;
			}				
			imagedestroy($tmp);
			imagedestroy($src);
			// return image data, or false if upload failed
			if(empty($new_img)) {
				return "Image resize: upload failed";
			} else {
				$new_file = array();
				$img_details = getimagesize($newfile);
				$new_file['filename'] 	= $filename;
				$new_file['size'] 	= filesize($newfile);
				$new_file['width'] 	= $img_details[0];
				$new_file['height'] = $img_details[1];
				$new_file['mime'] 	= $img_details['mime'];
				$new_file['ext'] 	= $ext;
				return $new_file;
			}
	}
		

/**
 * check_file_upload()
 * 
 * performs initial validation on a file upload
 * (e.g. to ensure a file has actually been uploaded)
 * 
 * @param	array	$file	the uploaded $_FILE array
 * @return	mixed	$error	returns error text if an error is encountered,
 * 					otherwise returns false
 */

	function check_file_upload($file, $location = 0) {
		
		// get default settings
		$config = get_settings('files');
		
		// check a file has actually been uploaded
		if (empty($file['size'])) {
			return "File check: No file uploaded";
		}
		
		if (!is_uploaded_file($file['tmp_name'])) {
			return "File check: Could not save file!";
		}
		
		// check file is a in an allowed format - values set in database
		if(!empty($config['files']['allowed_formats'])) {
			$ext = pathinfo($file['name']);
			$ext = strtolower($ext['extension']);
			if (!in_array($ext,$config['files']['allowed_formats'])) {
				return "File check: File needs to be in ".implode(', ',$allowed)." format, not ".$ext;
			}
		}
		
		// check file is under allowed size limit
		$max_size = $config['files']['max_file_size'];
		if(!empty($max_size)) {
			if($file['size'] > $max_size) {
				return "File check: File exceeds the allowed size (".$max_size." bytes)";
			}
		}
					
		// check upload location exists		
		if(!empty($location)) {
			
			// check for end slash on location
			$location = end_slash($location);
			
			if (!is_dir($location)) {
				// can we create it?
				if(!mkdir($location, 0777)) {
					return "File check: The directory (".$location.") does not exist";
				}
			}

		// check location is writeable
			if (!is_writable($location)) {
				return "File check: The file location (".$location.") is not writeable";
			}
			
		}
		
		// check for general FILE errors
		if(!empty($file['error'])) {
			return "Upload error:".$file['error'];
		}
		return 0; // send empty value
	}

/**
 * -----------------------------------------------------------------------------
 * LINKS
 * -----------------------------------------------------------------------------
 */
	
/**
 * get_links
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function get_links() {
		$conn = reader_connect();
		$query = "SELECT 
					*, IF(sort = '0', 1, 0) AS nullsort
					FROM links ORDER BY category, nullsort, sort, id DESC";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row = stripslashes_deep($row);
			$data[$row['category']][] = $row;
		}
		$result->close();
		return $data;		
	}

/**
 * get_link_categories
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function get_link_categories() {
		$conn = author_connect();
		$query = "SELECT category 
					FROM links 
					WHERE category NOT IN('site_rss','site_menu','site_head')
					GROUP BY category";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$data[] = $row;
		}
		$result->close();
		return $data;		
	}
	
/**
 * get_link
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function get_link($link_id) {
		if(empty($link_id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT * FROM links WHERE id = ".(int)$link_id;
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		return stripslashes_deep($row);
	}

/**
 * validate_url
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function validate_url($url) {
		if (preg_match('|^\S+://\S+\.\S+.+$|', $url)) {
			return true;
		} else {
			return false;
		}		
	}

/**
 * insert_link
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function insert_link() {
		if(!isset($_POST)) {
			return 'no post data sent';
		}
		if(validate_url($_POST['url']) === false) {
			return 'invalid url provided';
		}
		$conn = author_connect();
		$title 	= (!empty($_POST['title'])) ? clean_input($_POST['title']) : $_POST['url'] ;
		$attributes	= (!empty($_POST['attributes'])) ? clean_input($_POST['attributes']) : '' ;
		$summary 	= (!empty($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		$category 	= (!empty($_POST['category'])) ? clean_input($_POST['category']) : '' ;
		$category 	= (!empty($_POST['new_category'])) ? clean_input($_POST['new_category']) : $category ;
		$sort		= (!empty($_POST['sort'])) ? (int)$_POST['sort'] : 0 ;
		$insert = "INSERT INTO links
				(title, url, attributes, summary, category)
				VALUES
				(
				'".$conn->real_escape_string($title)."',
				'".$conn->real_escape_string($_POST['url'])."',
				'".$conn->real_escape_string($attributes)."',
				'".$conn->real_escape_string($summary)."',
				'".$conn->real_escape_string($category)."',
				'".(int)$sort."'
				)";
		$result = $conn->query($insert);
		if(!$result) {
			return $conn->error;
		} else {
			$new_id = $conn->insert_id;
			return $new_id;
		}
	}
	
/**
 * update_link
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function update_link($link_id) {
		if(empty($link_id)) {
			return false;
		}
		if(!isset($_POST)) {
			return 'no post data sent';
		}
		if(validate_url($_POST['url']) === false) {
			return 'invalid url provided';
		}
		$conn = author_connect();
		$title 	= (!empty($_POST['title'])) ? clean_input($_POST['title']) : $_POST['url'] ;
		$attributes	= (!empty($_POST['attributes'])) ? clean_input($_POST['attributes']) : '' ;
		$summary 	= (!empty($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		$category 	= (!empty($_POST['category'])) ? clean_input($_POST['category']) : '' ;
		$sort		= (!empty($_POST['sort'])) ? (int)$_POST['sort'] : 0 ;
		$query = "UPDATE links SET
					title = '".$conn->real_escape_string($title)."',
					url = '".$conn->real_escape_string($_POST['url'])."',
					attributes = '".$conn->real_escape_string($attributes)."',
					summary = '".$conn->real_escape_string($summary)."',
					category = '".$conn->real_escape_string($category)."',
					sort = '".(int)$sort."'
					WHERE id = ".(int)$link_id;
		$result = $conn->query($query);
		if(!$result) {
			return $conn->error;
		} else {
			return true;
		}
	}
	
/**
 * delete_link
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function delete_link($link_id) {
		if(empty($link_id)) {
			return false;
		}
		$conn = author_connect();
		$query = "DELETE FROM links WHERE id = ".(int)$link_id;
		$result = $conn->query($query);
		if(!$result) {
			return $conn->error;
		} else {
			return true;
		}
	}
?>