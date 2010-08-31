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
 * INITIAL FUNCTIONS
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


/*
 * -----------------------------------------------------------------------------
 * STATS
 * -----------------------------------------------------------------------------
 */

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
 * admin_comment_stats
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
 * ARTICLES RETRIEVAL
 * -----------------------------------------------------------------------------
 */





/**
 * list_articles
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
			// add page counts
			$row['style'] = (strtotime($row['date_uploaded']) > time()) ? 'postdated' : $status[$row['status']];
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
 * list_admin_authors
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
 * list_admin_categories
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
 * list_tags
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
 * ARTICLE DATA RETRIEVAL
 * -----------------------------------------------------------------------------
 */

/**
 * admin_get_article
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
		$query = "SELECT * 
				FROM articles 
				WHERE id = ".(int)$article_id;
		$comment_query = "
				SELECT COUNT(id) as total
				FROM comments 
				WHERE article_id = ".(int)$article_id;
		$result = $conn->query($query);
		$comment_result = $conn->query($comment_query);
		$row = $result->fetch_assoc();
		$comments = $comment_result->fetch_assoc();
		$row['comment_count'] = $comments['total'];
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
		return $row;		
	}

/**
 * get_article_attachments_admin
 * 
 * 
 * 
 * 
 * 
 * 
 */	


	function get_article_attachments_admin($article_id) {
		
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

/**
 * -----------------------------------------------------------------------------
 * ARTICLE DATA INSERT
 * -----------------------------------------------------------------------------
 */

/**
 * get_article_data
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
			$article_data_ts = strtotime($article_data['date_uploaded']);
			$article_data['day'] 	= date('d',$article_data_ts);
			$article_data['month'] 	= date('m',$article_data_ts);
			$article_data['year'] 	= date('Y',$article_data_ts);
			$article_data['hour'] 	= date('H',$article_data_ts);
			$article_data['minute'] = date('i',$article_data_ts);
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
			$article_data['tags']			= array();
			$article_data['attachments']	= array();
			$article_data['seo_title'] 		= '';
			$article_data['seo_desc'] 		= '';
			$article_data['seo_keywords'] 	= '';
			$article_data['comments_hide'] 	= $config['comments']['site_hide'];
			$article_data['comments_disable'] = $config['comments']['site_disable'];
			$article_data['day'] 			= date('d');
			$article_data['month'] 			= date('m');
			$article_data['year'] 			= date('Y');
			$article_data['hour'] 			= date('H');
			$article_data['minute'] 		= date('i');
			
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
		// set status
		$status_list = array('A','D','P','W');
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
			// update url title only if article is new, or in draft status, or update_url is checked
			$article_data['url'] = ( (empty($article_data['id'])) || ($article_data['status'] == 'D') || (!empty($_POST['update_url'])) )
				? create_url_title($article_data['title']) 
				: clean_input($_POST['url']) ;
			// check for duplicates
			$article_data['url'] = check_url_title($article_data['url'],$article_data['id']);
		} else {
			$article_data['error'][] = "No title entered";
		}
		
		// summary
		$article_data['summary'] = (isset($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		
		// body - no need to clean html here
		$article_data['body'] = (isset($_POST['body'])) ? prepare_article_body($_POST['body']) : '' ;
		
		// author id
		$article_data['author_id'] = (int)$_POST['author_id'];
		
		// category new
		if(!empty($_POST['category_new'])) {
			$new_category = clean_input($_POST['category_new']);
			$article_data['category_id'] = quick_insert_category($new_category);
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
			$year 	= (empty($_POST['year'])) 	? date('Y') : $_POST['year'] ;
			$month 	= (empty($_POST['month'])) 	? date('m') : $_POST['month'] ;
			$day 	= (empty($_POST['day'])) 	? date('d')	: $_POST['day'] ;
			$hour 	= (empty($_POST['hour'])) 	? date('H') : $_POST['hour'] ;
			$minute = (empty($_POST['minute'])) ? date('i') : $_POST['minute'] ;
			$article_data['date_uploaded'] = $year."-".$month."-".$day." ".$hour.":".$minute.":00";
			// just to avoid messy errors we'll resend the date/time variables again
			$article_data['year'] 	= $year;
			$article_data['month'] 	= $month;
			$article_data['day'] 	= $day;
			$article_data['hour'] 	= $hour;
			$article_data['minute'] = $minute;
		}
		
		// date ammended
		$article_data['date_amended'] = date('Y-m-d H:i:s');
		
		// seo data
		$article_data['seo_title'] = (isset($_POST['seo_title'])) ? clean_input($_POST['seo_title']) : '' ;
		$article_data['seo_desc'] = (isset($_POST['seo_desc'])) ? clean_input($_POST['seo_desc']) : '' ;
		$article_data['seo_keywords'] = (isset($_POST['seo_keywords'])) ? clean_input($_POST['seo_keywords']) : '' ;
		
		// comment settings
		$article_data['comments_hide'] = ( (isset($_POST['comments_hide'])) && (!empty($_POST['comments_hide'])) ) ? 1 : 0 ;
		$article_data['comments_disable'] = ( (isset($_POST['comments_disable'])) && (!empty($_POST['comments_disable'])) ) ? 1 : 0 ;

		// tags
		$article_data['tags'] = ( (isset($_POST['tags'])) && (!empty($_POST['tags'])) ) ? $_POST['tags'] : array() ;
		
		// tag new
		if(!empty($_POST['tag_new'])) {
			$new_tag = clean_input($_POST['tag_new']);
			$new_tag_ids = quick_insert_tags($new_tag);
			foreach($new_tag_ids as $new_id) {
				$article_data['tags'][] = $new_id;
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
			return stripslashes_deep($article_data);
		}
	}

/**
 * insert_article_data
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
			$result['status'] = $post_data['status'];
			$result['category_id'] = $post_data['category_id'];
			$result['date_uploaded'] = $post_data['date_uploaded'];
		// update tags_map table
			if(!empty($post_data['tags'])) {
				update_article_tags($new_id, $post_data['tags']);
			}
		// update attachments_map table
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
			$result['status'] = $post_data['status'];
			$result['category_id'] = $post_data['category_id'];
			$result['date_uploaded'] = $post_data['date_uploaded'];
			// update tags_map table
			if(!empty($post_data['tags'])) {
				update_article_tags($post_data['id'], $post_data['tags']);
			}
			return $result;
		// update attachments_map table			
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
		
	}

/**
 * create_url_title
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
			$url = current_url();
			header('Location: '.$url);
		}
	}
	
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
			return false;
		} else {
			return true;
		}		
	}
	
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
						'".$conn->real_escape_string(date('Y-m-d H:i:s'))."',
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
			$url = $_SERVER["PHP_SELF"].'?page_name=comments';
			header('Location: '.$url);
		}
	}

/*
 * -----------------------------------------------------------------------------
 * AUTHOR UPDATE/INSERT/DELETE
 * -----------------------------------------------------------------------------
 */


 /**
 * admin_get_author_details
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
		$row = $result->fetch_assoc();
		$result->close();
		$level = (empty($row['guest_flag'])) ? 'author' : $row['guest_areas'] ;	
		$allowed_levels = array('author','editor','contributor');
		$row['level'] = (!in_array($level,$allowed_levels)) ? 'undefined' : $level ;
		return $row;
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

	function insert_author() {
		
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
 	
	function update_author($author_id) {
		
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
		if(empty($category_name)) {
			return 0;
		}
		$conn = author_connect();
		$category_title = clean_input($category_name);
		$category_url = create_url_title($category_title);
		$insert = "INSERT INTO categories 
					(title, url)
					VALUES 
					('".$category_title."','".$category_url."')";				
		$conn->query($insert);
		$new_id = $conn->insert_id;
		return $new_id;			
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
		$conn = author_connect();
		$title 		= clean_input($_POST['title']);
		$url 		= create_url_title($title);
		$parent_id 	= (!empty($_POST['category_id'])) ? (int)$_POST['category_id'] : 0 ;
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
		$conn->query($insert);
		$new_id = $conn->insert_id;
		return $new_id;			
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
		$conn = author_connect();
		// convert csv to array
		$title 		= clean_input($_POST['title']);
		$url 		= (!empty($_POST['update_url'])) ? create_url_title($title) : $_POST['url'] ;
		$parent_id 	= (!empty($_POST['category_id'])) ? (int)$_POST['category_id'] : 0 ;
		$summary 	= (!empty($_POST['summary'])) ? clean_input($_POST['summary']) : '' ;
		$description = (!empty($_POST['description'])) ? clean_input($_POST['description']) : '' ;
		$type 		= (!empty($_POST['type'])) ? clean_input($_POST['type']) : '' ;
		$query = "UPDATE categories SET
					title = '".$conn->real_escape_string($title)."',
					url = '".$conn->real_escape_string($url)."',
					category_id = ".(int)$parent_id.",
					summary = '".$conn->real_escape_string($summary)."',
					description = '".$conn->real_escape_string($description)."'.
					type = '".$conn->real_escape_string($type)."'
					WHERE id = ".(int)$category_id;
		echo $query;
		$conn->query($query);
		return $category_id;		
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
			$tag_title = clean_input($tag_name);
			$tag_url = create_url_title($tag_title);
			$insert = "INSERT INTO tags 
						(title, url)
						VALUES 
						('".$tag_title."','".$tag_url."')";
			$conn->query($insert);
			$new_tags_id[] = $conn->insert_id;
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
		$conn->query($insert);
		$new_tag_id = $conn->insert_id;
		return $new_tag_id;
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
		$conn->query($query);
		return $tag_id;		
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
		$conn->query($delete);
		// clean tags map
		$clean = "DELETE FROM tags_map WHERE tag_id = ".(int)$tag_id;
		$conn->query($clean);
		return true;	
	}

/**
 * -----------------------------------------------------------------------------
 * IMAGE EDIT/INSERT FUNCTIONS
 * -----------------------------------------------------------------------------
 */

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
		// get thumb details
		$thumb = WW_ROOT.'/ww_files/images/thumbs/'.$row['filename'];
		if(file_exists($thumb)) {
			$thumb_size 	= getimagesize($thumb);
			$thumb_width 	= $thumb_size[0];
			$thumb_height 	= $thumb_size[1];		
		}
		// get image url
		$url = WW_REAL_WEB_ROOT.'/ww_files/images/';
		// add to array
		$row['src'] = $url.$row['filename'];
		$row['thumb_src'] = $url.'thumbs/'.$row['filename'];
		$row['thumb_width'] = (isset($thumb_width)) ? $thumb_width : 0 ;
		$row['thumb_height'] = (isset($thumb_height)) ? $thumb_height : 0 ;
		$result->close();
		return $row;
	}

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
			$width = (isset($_POST['width'])) ? (int)$_POST['width'] : 0 ;
			$image_data = resize_image($image_file,$location,'',$width);
		}
		// check image was uploaded
		if(!is_array($image_data)) {
			return $image_data;
		}
		// thumbnail?
		$thumb_file = $_FILES['thumb_file'];
		$th_location = WW_ROOT.'/ww_files/images/thumbs/';		
		if(empty($thumb_file['error'])) {
			// user has uploaded thumbnail
			resize_image($thumb_file,$th_location);
		} else {
			// generate a thumbnail
			$th_width = $config['files']['thumb_width'];
			resize_image($image_file,$th_location,'',$th_width);	
		}	
		// now we can finally insert into the database
		$conn = author_connect();
		$title 	= (!empty($_POST['title'])) ? $_POST['title'] : $image_data['name'] ;
		$alt 	= (!empty($_POST['alt'])) ? $_POST['alt'] : $image_data['name'] ;
		$credit = (!empty($_POST['credit'])) ? $_POST['credit'] : '' ;
		$caption = (!empty($_POST['caption'])) ? $_POST['caption'] : '' ;
		$author_id = (defined('WW_SESS')) ? $_SESSION[WW_SESS]['user_id'] : $_GET['author_id'] ;
		$query = "INSERT INTO images 
				(filename, title, alt, credit, caption, 
				author_id, width, height, ext, mime, size, date_uploaded)
			VALUES 
				('".$conn->real_escape_string($image_data['name'])."',
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
				'".$conn->real_escape_string(date('Y-m-d H:i:s'))."')";
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
		$query = "UPDATE images SET 
							title 	= '".$conn->real_escape_string($title)."', 
							alt 	= '".$conn->real_escape_string($alt)."',
							caption = '".$conn->real_escape_string($caption)."',
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

	function replace_image($current, $new, $width = 0) {
		$width = (int)$width;
		$replacement = replace_file($current, $new, $width);
		if(!is_array($replacement)) {
			return $replacement;
		}
		$conn = author_connect();
		// prepare other variables
		$title 		= (empty($_POST['title'])) 		? $filename : clean_input($_POST['title']);
		$alt 		= (empty($_POST['alt'])) 		? $title 	: clean_input($_POST['alt']);
		$caption 	= (empty($_POST['caption'])) 	? '' 		: clean_input($_POST['caption']);
		$credit 	= (empty($_POST['credit']))		? '' 		: clean_input($_POST['credit']);
		$query = "UPDATE images SET size = '".(int)$replacement['size']."', 
									width = '".(int)$replacement['width']."',
									height = '".(int)$replacement['height']."',
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
			return 'there was a problem deleting the image';
		} else {
			return true;
		}
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
		// get list of images in images folder
		$image_folder = WW_ROOT.'/ww_files/images';
		$files = get_files($image_folder);
		$file_list = array();
		foreach($files as $file) {
			$file_list[] = $file['name'];
		}
		debug_array($db_list);
		debug_array($file_list);
		$orphans = array();
		$orphans['files']= array_diff($file_list, $db_list);
		$orphans['db'] = array_diff($db_list,$file_list);
		return $orphans;
	}

/**
 * -----------------------------------------------------------------------------
 * ATTACHMENT EDIT/INSERT FUNCTIONS
 * -----------------------------------------------------------------------------
 */

/**
 * get_attachment_full
 * 
 * 
 * 
 * 
 * 
 * 
 */	


	function get_attachment_full($attachment_id) {
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
		$row['link'] = WW_WEB_ROOT.'/ww_files/attachments/'.$row['ext'].'/'.$row['filename'];
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
		//echo $query;
		// add pagination
			$query_paginated = $query." LIMIT ".(int)$from.", ".(int)$per_page;
			$result = $conn->query($query_paginated);
		
		// get total results
			$total_result = $conn->query($query);
			$total_files = $total_result->num_rows;
			$total_pages = ceil($total_files / $per_page);

		$data['total_files'] = $total_files;
		$data['total_pages'] = $total_pages;
		// get image url
		$url = WW_REAL_WEB_ROOT.'/ww_files/attachments/';
		while($row = $total_result->fetch_assoc()) {
			$row['src'] = $url.$row['ext'].'/'.$row['filename'];
			$data[$row['id']] = $row;
		}
		$result->close();
		$total_result->close();
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
				('".$conn->real_escape_string($file_data['name'])."',
				'".$conn->real_escape_string($title)."',
				'".$conn->real_escape_string($summary)."',
				".(int)$author_id.",
				'".$conn->real_escape_string($file_data['ext'])."',
				'".$conn->real_escape_string($file_data['mime'])."',
				".(int)$file_data['size'].",
				'".$conn->real_escape_string(date('Y-m-d H:i:s'))."')";
		$result = $conn->query($query);
		if(!$result) {
			unlink($location.$file_data['name']);
			return $conn->error;
		} else {
			$new_id = $conn->insert_id;
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
		$title 		= (empty($_POST['title'])) 		? $_POST['filename'] : clean_input($_POST['title']);
		$summary 	= (empty($_POST['summary'])) 	? $title 	: clean_input($_POST['alt']);
		$query = "UPDATE attachments SET 
							title 	= '".$conn->real_escape_string($title)."', 
							summary = '".$conn->real_escape_string($summary)."'  
						WHERE id = ".$id;
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

	function delete_attachment() {
		
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
		$orphans['files']= array_diff($file_list, $db_list);
		$orphans['db'] = array_diff($db_list,$file_list);
		return $orphans;
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
				$new_file['name'] 	= $filename;
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

	function replace_file($current_file, $new_file, $new_width = 0) {
		if(!isset($_FILES)) {
			return 'No file uploaded';
		}
		// we need to check that the old file and new file are the same type
		$current_path = pathinfo($current_file);
		$location 	= $current_path['dirname'];
		$filename 	= $current_path['basename'];
		// check for end slash on location
		$location = end_slash($location);
		$current_ext 	= strtolower($current_path['extension']);
		// get extension of uploaded file
		$new_path 	= pathinfo($new_file['name']);
		$new_ext 	= strtolower($new_path['extension']);
		// unlink the old file
		if($current_ext != $new_ext) {
			return 'Uploaded file is a different type to existing file';
		}
		// check file upload
		$check_file = check_file_upload($new_file, $location);
		if(!empty($check_file)) {
			return $check_file;
		}
		// delete existing file
		if(file_exists($current_file)) {
			unlink($current_file);
		}
		$img_array = array('gif','jpg','png');
		if( (in_array($new_ext,$img_array)) && (!empty($new_width)) ) {
			$replaced_file = resize_image($new_file, $location, $filename, $new_width);
		} else {
			$replaced_file = upload_file($new_file, $location, $filename);
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
				$new_file['name'] 	= $filename;
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

?>