<?php
/**
 * reader controller functions
 * 
 * @package wickedwords
 * 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License version 3
 */

/*
	outline:
	
		page direction functions
		
			process_request
			redirect_url
			show_404
			current_url
			
		css
		
			compile_css
			
		cache
		
			stop_timer
			start_caching
			end_caching
			clear_cached_page
		
		detection
		
			detect_bot
			detect_smartphone
		
		data validation
		
			get_article_id
			get_author_id
			get_category_id
			get_tag_id
			get_attachment_id
		
		author/category/date/tag listings
		
			get_authors
			get_author_name
			get_author_details
			
			get_categories
			get_categories_unnested
			get_category_title
			get_category_details
			
			get_tags
			get_tag_title
			get_tag_details
			
			get_months
			
			get_feeds
		
		article listings
		
			search_articles
			get_articles
			get_articles_basic
			get_podcasts
			
		single article
		
			get_article
			get_article_attachments
			get_article_tags
			get_article_comments
			get_feed_comments
			paginate_article_body
			update_counters
		
		comment processing
		
			validate_comment	
			insert_comment
			
		string processing
		
			get_intro	
			create_summary
			convert_relative_urls
			strip_inline_styles
			from_mysql_date			
			prepare_string
			clean_input
			count_words
			extract_words
			extract_sentences
			validate_email
			encode_email
			
		file retrieval
		
			get_attachment
			serve_attachment
	
*/

/**
 * -----------------------------------------------------------------------------
 * PROCESS REQUESTS AND/OR URL PARAMETERS
 * -----------------------------------------------------------------------------
 */

/**
 * process request
 * 
 * this function is called at the top of the main index page
 * it processes the GET parameters created by process_url()
 * and determines which data should be retrieved, creating additional
 * parameters if necessary for specific datasets
 */	

	function process_request() {
		
		// search request - redirect to keep tidy url
		if(isset($_POST['search'])) {
			$search_redirect = WW_WEB_ROOT.'/search/'.$_POST['search'].'/';
			header('HTTP/1.1 302 Moved Temporarily');
			header('Location: '.$search_redirect);
		}
		
		// redirect jump links from select boxes
		if(isset($_POST['select_link'])) {
			$select_redirect = $_POST['select_link'];
			header('HTTP/1.1 302 Moved Temporarily');
			header('Location: '.$select_redirect);
		}
		
		// now get urldata
		$urldata = process_url();
		
		// author url
		if( (isset($_GET['author_url'])) && (!empty($_GET['author_url'])) ) {
			$author_id = get_author_id($_GET['author_url']);
			if(!empty($author_id)) {	
				$_GET['author_id'] = $author_id;
			} else {
				$_GET['page_name'] = '404';
			}			
		}
		
		// category url
		if( (isset($_GET['category_url'])) && (!empty($_GET['category_url'])) ) {
			if(!isset($_GET['category_id'])) {
				$category_id = get_category_id($_GET['category_url']);
				if($category_id === false) {	
					$_GET['page_name'] = '404';
				} else {
					$_GET['category_id'] = $category_id;
				}				
			}
		}
		
		// tag url
		if( (isset($_GET['tag_url'])) && (!empty($_GET['tag_url'])) ) {
			$tag_id = get_tag_id($_GET['tag_url']);
			if(!empty($tag_id)) {
				$_GET['tag_id'] = $tag_id;
			} else {
				$_GET['page_name'] = '404';
			}
		}
		
		// have we already 404ed?
		if( (isset($_GET['page_name'])) && ($_GET['page_name'] == '404') ) {
			show_404($urldata);
			return false;
		}
		
		// start getting data
		
		// was a single article requested?
		if( (isset($_GET['article_id'])) && (!empty($_GET['article_id'])) 
			|| (isset($_GET['article_url'])) && (!empty($_GET['article_url']))	) {
			
			$article_id = get_article_id();
			if(!empty($article_id)) {
				// n.b. page_name could be 'feed' if looking for article comments rss
				$_GET['page_name'] = (isset($_GET['page_name'])) ? $_GET['page_name'] : 'article';
				$_GET['article_id'] = $article_id;
				return true;
			} else {
				show_404($urldata);
				return false;
			}
			
		// or a search term or feed request
		} elseif( (isset($_GET['feed'])) || (isset($_GET['feed_listing'])) ) {

			return true;
					
		// or did we get a valid author, category, or tag id - or a search, or date request?
		} elseif( (!empty($author_id)) || (isset($_GET['category_id'])) || (!empty($tag_id)) 
			|| (isset($_GET['year'])) || (isset($_GET['search'])) ) {
			
			$_GET['page_name'] = 'listing';
			return true;	
		
		// page number for a non listing page
		} elseif(isset($_GET['page'])) {
			
			$_GET['page_name'] = (isset($_GET['page_name'])) ? $_GET['page_name'] : 'front';
			return true;
			
		// if anything else has been requested we sure can't find it
		} elseif(!empty($urldata)) {

			show_404($urldata);
			return false;
			
		}
		// default
		$_GET['page_name'] = 'front';
		return false;
	}

/**
 * redirect_url
 * 
 * this function translates a url into a usable request
 * (obviously this is for mod-rewrite friendly urls - if a conventional
 * url query string is provided it returns that and doesn't process further)
 * 
 * this is called by process_request() on the index page
 */	
 
	function process_url() {
		
		// first check if we're using a standard query string
		if(!empty($_SERVER["QUERY_STRING"])) {
			return $_SERVER["QUERY_STRING"];
		}		
		// if not start to derive the actual request
		$url_request = str_replace(WW_ROOT_SUFFIX,'',$_SERVER['REQUEST_URI']);
		$url_request = clean_start_slash($url_request);
		$url_request = clean_end_slash($url_request);
		
		// if nothing is requested this must be the front page
		// index.php added for backwards compatibility
		if((empty($url_request)) || ($url_request == 'index.php')) {
			return false;
		}
		
		// break up urldata into an array
		$url_request = strtolower($url_request);
		$urldata = explode('/', $url_request);
		
		// theme option
		if($urldata[0] == 'theme') {
			if(isset($urldata[1])) {
				$_SESSION['theme'] = start_slash($urldata[1]);
				header('Location: '.WW_WEB_ROOT);
				exit();			
			} elseif(isset($_SESSION['theme'])) {
				unset($_SESSION['theme']);
				header('Location: '.WW_WEB_ROOT);
				exit();	
			} else {
				header('Location: '.WW_WEB_ROOT);
				exit();
			}
		}
		
		/*	
			author, page, p, tag - can all appear at various places within the url string,
			so we work out the parameter position and succeeding value first
			n.b. 'page' refers to a listing page number; 'p' is an article page number
		*/
		$var_pos = array('author', 'page', 'p', 'tag');
		foreach($var_pos as $vp) {
			if (in_array($vp,$urldata)) {
				$var_key = array_search($vp,$urldata);
				$val_key = $var_key+1;
				$var_value = $urldata[$val_key];
				// specific code for author
				if($vp == 'author') {
					$_GET['author_url'] = $var_value;
					if(empty($urldata[2])) {
						return $urldata;
					}
				// specific code for tag
				} elseif($vp == 'tag') {
					$_GET['tag_url'] = $var_value;
					if(empty($urldata[2])) {
						return $urldata;
					}
				// otherwise value must be page number
				} else {
					$var_value = (int)$var_value; 
					if(!empty($var_value)) {
						$_GET[$vp] = $var_value;
					} else {
						show_404($urldata);
						return $urldata;
					}
				}
			}			
		}
		// does the number of GET vars already match the size of the urldata array?
		if( (sizeof($urldata)) == ((sizeof($_GET))*2) ) {
			return $urldata;
		}
		
		/*	
			allowed values for first position defined first_pos array - if any other value 
			is sent then a string is assumed to be a category, while an integer is assumed to be a year
		*/
		$first_pos = array('about','author','admin','download','id', 'feeds','feed','page','podcast','rss','rss-external',
				'search','sitemap','tag');
	
		// now start checking for valid requests in the url string
		switch($urldata) {

			//	redirect to admin pages (e.g. www.domain.com/admin/)
			
			case $urldata[0] == 'admin':
				$location = WW_REAL_WEB_ROOT."/ww_edit/admin.php";
				header('Location: '.$location);
				exit();
			break;

			case $urldata[0] == 'about':
				$_GET['category_url'] = $urldata[0];
				$_GET['category_id'] = 0;
				if(!empty($urldata[1]) ) {
					$_GET['article_url'] = $urldata[1];
				}
			break;

			//	downloads (e.g. www.domain.com/download/mp3/sample/ OR www.domain.com/download/12/)
			
			case $urldata[0] == 'download':
				if(!empty($urldata[2])) {
					$download_id = get_attachment_id($urldata[1],$urldata[2]);
					serve_attachment($download_id); // ext/filename
				} elseif(!empty($urldata[1])) {
					serve_attachment($urldata[1]); // id only
				}
			break;

			// feeds listing (e.g. www.domain.com/feeds/)
			
			case $urldata[0] == 'feeds':
				$_GET['page_name'] = 'listing';
				$_GET['feed_listing'] = 1;
			break;
			
			//	article id - provides a quick way of accessing articles (e.g. www.domain.com/id/12/)
		
			case $urldata[0] == 'id':
				header('HTTP/1.1 302 Moved Temporarily');
				$_GET['article_id'] = (int)$urldata[1];
				break;
	
			//	podcast feed (e.g. www.domain.com/podcast/ OR www.domain.com/podcast/[category_url]/)
				
			case $urldata[0] == 'podcast':
				$_GET['feed'] = 'podcast';
				$_GET['page_name'] = 'feed';
				if(!empty($urldata[1])) {
					$_GET['category_url'] = $urldata[1];
				}
			break;

			//	redirect to rss feeds, check for additional parameters
			/*		e.g. www.domain.com/rss/
					e.g. www.domain.com/feed/author/[author_url]
					e.g. www.domain.com/rss/tag/[tag_url]
					e.g. www.domain.com/rss/[category_url]
					e.g. www.domain.com/rss/comments/[article_id - optional]
					e.g. www.domain.com/rss-external/GET/param
			*/
		
			case $urldata[0] == 'rss':
			case $urldata[0] == 'feed':
				if(!empty($feed_url)) {
					// redirect the main feed (i.e. no url parameters) if feed_url is specified
					header('HTTP/1.1 302 Moved Temporarily');
					header('Location: '.$feed_url);
				}
			// but keep rss-external URLs on site permanently
			case $urldata[0] == 'rss-external':
				$_GET['page_name'] = 'feed';
				$_GET['feed'] = 'articles';			
				// for comments
				if(!empty($urldata[1])) {
					if($urldata[1] == 'comments') {
						// defaults to all comments
						$_GET['feed'] = 'comments';
						// unless an article id is sent
						if(!empty($urldata[2])) {
							$_GET['article_id'] = (int)$urldata[2]; // article ID for comments
						}
					// for category (author or tag would have been picked up already
					} elseif(empty($urldata[2])) {
						// other option is a category url
						$_GET['category_url'] = $urldata[1];
					}
				}
			break;

			// redirect for searches (e.g. www.domain.com/search/[search term])
		
			case $urldata[0] == 'search':
				$_GET['search'] = $urldata[1];
			break;
			
					
			// redirect to sitemap
			/* 
				sitemap.xml is redirected in .htaccess but this line allows use to use
				www.domain.com/sitemap/ as well
			*/
			case $urldata[0] == 'sitemap':
				include(WW_ROOT.'/ww_view/sitemap-xml.php');
				exit();
			break;

			// translate months, years, days, permatitled posts
		
			case ($urldata[0] > '1900' && $urldata[0] < '2056'):
				$_GET['year'] = $urldata[0];
				// if we find a year, let's also check for month
				if( (!empty($urldata[1])) && ($urldata[1] >= '01' && $urldata[1] <= '12') ) {
					$_GET['month'] = $urldata[1];
					// now check for day
					if( (!empty($urldata[2])) && ($urldata[2] >= '01' && $urldata[2] <= '31') ) {
						$_GET['day'] = $urldata[2];	
						// check for a title
						if(!empty($urldata[3])) {
							$_GET['article_url'] = $urldata[3];
						}					
					}		
				}
			break;
		
			// our final option is a category

			case (!in_array($urldata[0],$first_pos)):
				$category_url = $urldata[0];
				$_GET['category_url'] = $category_url;
				$allowed_after = array('author','page','tag');
				if( (!empty($urldata[1])) && (!in_array($urldata[1],$allowed_after)) ) {
					$_GET['article_url'] = $urldata[1];
				}
			break;			
		
			// if nothing matches then 404 it
			
			default:
			show_404($urldata);
			return false;
			break;
		}

		return $urldata;
	}



/**
 * show_404
 * 
 * this function simply redirects to a 404 error page
 * and displays some (hopefully) useful data about the
 * failed url request
 */	

	function show_404($notfound_data) {
		$_GET['page_name'] = '404';
		$_GET['notfound'] = $notfound_data;
		header("HTTP/1.0 404 Not Found");
		return false;
	}
	
/**
 * -----------------------------------------------------------------------------
 * CACHE
 * -----------------------------------------------------------------------------
 */

	function stop_timer($start) {
		$end = time()+microtime();
		$elapsed = round($end - $start,4);
		return "loaded in ".$elapsed." milliseconds";
	}

	function start_caching($config_cache = '', $start = '') {
	    if(empty($start)) {
	    	$start = time() + microtime();
	    }
		if(empty($config_cache)) {
			$config = get_settings('cache');
			$config_cache = $config['cache'];
		}
		$cachedir = WW_ROOT."/ww_files/_cache/";
		if(is_writeable($cachedir)) {
			$this_page = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
			$cachefile = $cachedir.md5($this_page).".".$config_cache['cache_ext'];
			/*
			if a list of pages to igore is required check the tutorial at:
			http://www.addedbytes.com/php/caching-output-in-php/
			*/
			$cachefile_created = (@file_exists($cachefile)) ? filemtime($cachefile) : 0 ;
			clearstatcache();
			// show the cached file if it hasn't expired
			if (time() - $config_cache['cache_time'] < $cachefile_created) {
				readfile($cachefile);
				echo "
				<!-- cached page ".stop_timer($start)." -->";
				exit();
			} else {
			// otherwise create a new file and attempt to write-lock it
				$fp = fopen($cachefile , 'w');
				if( flock($fp, LOCK_EX)) {
					ob_start();
				} else {
				// if we cant write-lock then show the cached page
					readfile($cachefile);
					echo "
				<!-- file write-locked - showing cached page -->
				<!-- cached page ".stop_timer($start)." -->";
					exit();
				}	
			}
			return $fp;
		} else {
			return 0;
		}
	}

	function end_caching($fp, $start = '') {
		if(!empty($fp)) {
			echo "
			<!-- this page was cached on ".date('r')."-->";
			if(!empty($start)) {
				echo "
				<!-- uncached page ".stop_timer($start)." -->";
			}
			// open the cache file for writing 
			flock($fp, LOCK_UN); // unlock the file
			// save the contents of output buffer to the file
			fwrite($fp, ob_get_contents());
			fclose($fp);
			ob_end_flush();
		}
	}

	function clear_cached_page($config_cache = '') {
		if(empty($config_cache)) {
			$config = get_settings('cache');
			$config_cache = $config['cache'];
		}
		$current = current_url();
		// clear cached article page
		$cachedir = WW_ROOT."/ww_files/_cache/";
		$cachefile = $cachedir.md5($current).".".$config_cache['cache_ext'];
		if(file_exists($cachefile)) {
			unlink($cachefile);
		}
	}
/**
 * -----------------------------------------------------------------------------
 * DETECTION
 * -----------------------------------------------------------------------------
 */

 /**
 * detect_bot
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function detect_bot() {
		// check for bots
		$bot_array = array(	"archive",
							"bot",
							"crawl",
							"index",
							"jeeves",
							"link",
							"scooter",
							"slurp",
							"spider");
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
 * -----------------------------------------------------------------------------
 * DATA VALIDATION
 * -----------------------------------------------------------------------------
 */
 
/**
 * get_article_id
 * 
 * returns an article id based on the GET parameters supplied
 * ensures that an article exists before the app tries to retrieve
 * it from the database
 * 
 * @return	int	 $id	the database id of the article
 */	
 
	function get_article_id() {
		if(empty($_GET)) {
			return false;
		}
		$conn = reader_connect();

		$query = "	SELECT id 
					FROM articles 
					WHERE status IN ('A','P')
					AND date_uploaded <= UTC_TIMESTAMP()";
		// article url
		if (isset($_GET['article_url'])) {
			$query .= " AND url LIKE '".$conn->real_escape_string($_GET['article_url'])."'";
		}		
		// article id
		if (isset($_GET['article_id'])) {
			$query .= " AND id = ".(int)$_GET['article_id'];
		}	
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		$id = $row['id'];
		$result->close(); 
		return $id;
	}


/**
 * get_author_id
 * 
 * takes an author url and matches it to the author id
 * 
 * secure method for validating string requests
 * 
 * @param	string	$url	author url
 * @return	int		$id		author id from database
 */	
 	
	function get_author_id($url) {
		if(empty($url)) {
			return false;
		}
		$authors = get_authors();
		$data = array();
		foreach($authors as $author) { 
			$data[$author['id']] = strtolower($author['url']);
		}
		$id = array_search($url,$data);
		return $id;
	}


/**
 * get_category_id
 * 
 * takes a category url and matches it to the category id
 * 
 * secure method for validating string requests
 * 
 * @param	string	$url	category url
 * @return	int		$id		category id from database
 */	
 	
	function get_category_id($url) {
		if(empty($url)) {
			return false;
		}
		$categories = get_categories_basic();
		$data = array();
		foreach($categories as $cat) { 
			$data[$cat['id']] = strtolower($cat['url']);
		}
		$id = array_search($url,$data);
		if(empty($id)) {
			return false;
		}
		return $id;
	}

/**
 * get_tag_id
 * 
 * takes a tag url and matches it to the tag id
 * 
 * secure method for validating string requests
 * 
 * @param	string	$url	tag url
 * @return	int		$id		tag id from database
 */	
 	
	function get_tag_id($url) {
		if(empty($url)) {
			return false;
		}
		$tags = get_tags();
		$data = array();
		foreach($tags as $tag) {  
			$data[$tag['id']] = strtolower($tag['url']);
		}
		$id = array_search($url,$data);
		return $id;
	}

/**
 * get_attachment_id()
 * 
 * returns the database id for an attachment
 * 
 * @param	string		$ext		file extension
 * @param	string		$name		file name
 * 
 */

	function get_attachment_id($ext, $name) {
	 	// database connection
		$conn = reader_connect();
		$query = "SELECT id 
					FROM attachments 
					WHERE ext LIKE '".$conn->real_escape_string($ext)."'
					AND filename LIKE '".$conn->real_escape_string($name)."'";
		$result = $conn->query($query);
		$row 	= $result->fetch_assoc();
		$result->close();
		return $row['id'];
	}

/**
 * -----------------------------------------------------------------------------
 * AUTHOR / CATEGORY / DATE / TAG LISTINGS
 * -----------------------------------------------------------------------------
 */

/**
 * get_authors
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function get_authors() {
		$conn = reader_connect();
		$query = "SELECT COUNT(authors.id) as total,
						authors.id, 
						authors.url, 
						authors.name AS title
					FROM articles
					LEFT JOIN authors ON articles.author_id = authors.id
					WHERE articles.status = 'P'
					AND articles.date_uploaded <= UTC_TIMESTAMP()
					GROUP BY authors.id
					ORDER BY title";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row['link'] = WW_WEB_ROOT.'/author/'.$row['url'].'/';
			$data[] = $row;
		}
		return $data;
	}


 /**
 * get_author_details
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_author_details($id) {
		$id = (int)$id;
		if(empty($id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT name, url, summary, biography, email, image, contact_flag
					FROM authors
					WHERE id = ".(int)$id;
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		$result->close();
		return $row;
	}

/**
 * list_categories
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function get_categories() {
		$conn = reader_connect();
		$query = "SELECT COUNT(categories.id) as total,
				categories.id,
				categories.category_id,
				categories.url, 
				categories.title
			FROM categories
				LEFT JOIN articles ON articles.category_id = categories.id
			WHERE articles.status = 'P'
				AND articles.date_uploaded <= UTC_TIMESTAMP()
			GROUP BY categories.id 
				ORDER BY categories.url";	
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row['link'] = WW_WEB_ROOT.'/'.$row['url'].'/';
			if(!empty($row['category_id'])) {
				$data[$row['category_id']]['child'][$row['id']] = $row;
			} else {
				$data[$row['id']] = $row;
			}
			
		}
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
 	
	function get_tags() {
		$conn = reader_connect();
		$query = "SELECT COUNT(tags.id) as total,
						tags.id, 
						tags.url, 
						tags.title
					FROM tags
					LEFT JOIN tags_map on tag_id = tags.id
					LEFT JOIN articles ON tags_map.article_id = articles.id
					WHERE articles.status = 'P'
					AND articles.date_uploaded <= UTC_TIMESTAMP()
					GROUP BY tags.id
					ORDER BY title";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row['link'] = WW_WEB_ROOT.'/tag/'.$row['url'].'/';
			$data[] = $row;
		}
		return $data;
	}


/**
 * get_months
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_months() {
		$conn = reader_connect();
		$query = "SELECT 
					YEAR(date_uploaded) as year,
					MONTHNAME(date_uploaded) as monthname, 
					MONTH(date_uploaded) as month,
					CONCAT(MONTHNAME(date_uploaded),' ',YEAR(date_uploaded)) as title,
					COUNT(title) as total
				FROM articles
					WHERE status = 'P'
					AND date_uploaded <= UTC_TIMESTAMP()
				GROUP BY CONCAT(MONTHNAME(date_uploaded),' ',YEAR(date_uploaded))
				ORDER BY date_uploaded DESC";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row['link'] = WW_WEB_ROOT.'/'.$row['year'].'/'.$row['month'].'/';
			$data[] = $row;
		}
		return $data;
	}

/**
 * get_feeds
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function get_feeds($show_all_feeds = 1) {
		$feed_list = array();
		/* 	section
			description
			feeds
				title
				url
		*/
		// main feed
		$feed_list['all'] = array(
			'section' => 'All Articles',
			'description' => 'The main feed for this site'
		);
		$feed_list['all']['feeds'][] = array (
				'title' => 'Main Feed',
				'url' => WW_WEB_ROOT.'/rss/'
		);
		// get additional rss feeds via links table
		$rss_links = get_links('site_rss');
		if(!empty($rss_links)) {
			$feed_list['additional'] = array(
				'section' => 'Additional Feeds',
				'description' => 'Other feeds for this site'
			);
			foreach($rss_links['site_rss'] as $cat => $link) {
				$feed_list['additional']['feeds'][] = array (
						'title' => $link['title'],
						'url' => $link['url']
				);
			}		
		}
		// show other links
		if(!empty($show_all_feeds)) {
			// category feeds
			$categories = get_categories();
			$feed_list['categories'] = array(
				'section' => 'Categories',
				'description' => 'Feeds for individual categories'
			);
			foreach($categories as $cat) {
				$feed_list['categories']['feeds'][] = array (
						'title' => $cat['title'],
						'url' => WW_WEB_ROOT.'/rss/'.$cat['url']
				);
			}
			// author feeds
			$authors = get_authors();
			$feed_list['authors'] = array(
				'section' => 'Authors',
				'description' => 'Feeds for individual authors'
			);
			foreach($authors as $author) {
				$feed_list['authors']['feeds'][] = array (
						'title' => $author['title'],
						'url' => WW_WEB_ROOT.'/rss/author/'.$author['url']
				);
			}
			// tag feeds
			$tags = get_tags();
			$feed_list['tags'] = array(
				'section' => 'Tags',
				'description' => 'Feeds for individual tags'
			);
			foreach($tags as $tag) {
				$feed_list['tags']['feeds'][] = array (
						'title' => $tag['title'],
						'url' => WW_WEB_ROOT.'/rss/tag/'.$tag['url']
				);
			}
		}
		return $feed_list;
	}

/**
 * get_links
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_links($type = '') {
		$conn = reader_connect();
		$query = "SELECT *, IF(sort = '0', 1, 0) AS nullsort FROM links";
		if(!empty($type)) {
			$query .= " WHERE category LIKE '".$conn->real_escape_string($type)."'";	
		} else {
			$query .= " WHERE category NOT IN('site_rss','site_menu','site_head') OR category IS NULL ";
		}
		$query .= " ORDER BY category, nullsort, sort, title";
		$result = $conn->query($query);
		$data = array();
		if(empty($result)) {
			return $data;
		}
		while($row = $result->fetch_assoc()) { 
			$row = stripslashes_deep($row);
			$row['category'] = (!empty($row['category'])) ? $row['category'] : 'links' ;
			$row['link'] = $row['url'];
			$data[$row['category']][] = $row;
		}
		$result->close();
		return $data;		
	}

/**
 * -----------------------------------------------------------------------------
 * ARTICLE SEARCH / LISTINGS
 * -----------------------------------------------------------------------------
 */

/**
 * search_articles
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function search_articles($term = '', $config_layout = '') {
		if(empty($term)) {
			return false;
		}
		// get layout config from database
		if(empty($config_layout)) {
			$config = get_settings('layout');
			$config_layout = $config['layout'];
		}
		$conn = reader_connect();
		// sanitize search term
		$term = trim($term);
		$term = (strlen($term) < 4) ? $term.'*' : $term ;
		$safe_term = $conn->real_escape_string($term);
		// build query
		$query = "SELECT
					articles.id, 
				 	articles.title, 
					articles.url, 
					articles.summary,
					articles.body,
					articles.date_uploaded, 
					categories.title AS category_title, 
					categories.url AS category_url,
					authors.name AS author_name, 
					authors.url AS author_url,
					(SELECT COUNT(id) 
						FROM comments 
						WHERE approved = 1
					AND article_id = articles.id) AS comment_count,
					( 	(1.4 * (MATCH(articles.title) AGAINST ('".$safe_term."' IN BOOLEAN MODE))) + 
						(1.2 * (MATCH(articles.summary) AGAINST ('".$safe_term."' IN BOOLEAN MODE))) + 
						(0.8 * (MATCH(articles.body) AGAINST ('".$safe_term."' IN BOOLEAN MODE))) 
					) AS score	
				FROM articles 
					LEFT JOIN authors ON articles.author_id = authors.id 
					LEFT JOIN categories ON articles.category_id = categories.id 
				WHERE status IN ('A','P')
					AND date_uploaded <= UTC_TIMESTAMP()
					AND MATCH (articles.title, articles.summary, articles.body) 
						AGAINST ('".$safe_term."' in boolean mode) 
					HAVING score > 0 
				ORDER BY score DESC, date_uploaded DESC";
		$data = compile_article_listing($query, $config_layout);
		return $data;
	}

/**
 * list_articles
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_articles($config_layout = '') {
		// get layout config from database
		if(empty($config_layout)) {
			$config = get_settings('layout');
			$config_layout = $config['layout'];
		}
		$query = "SELECT
					articles.id, 
				 	articles.title, 
					articles.url, 
					articles.summary,
					articles.body,
					articles.date_uploaded, 
					categories.title AS category_title, 
					categories.url AS category_url,
					parent.title AS parent_category_title,
					parent.url AS parent_category_url,
					authors.name AS author_name, 
					authors.url AS author_url,
					(SELECT COUNT(id) 
						FROM comments 
						WHERE approved = 1
					AND article_id = articles.id) AS comment_count
				FROM articles 
					LEFT JOIN authors ON articles.author_id = authors.id 
					LEFT JOIN categories ON articles.category_id = categories.id
					LEFT JOIN categories AS parent ON categories.category_id = parent.id  ";
		if (isset($_GET['tag_id'])) {
			$query .= " LEFT JOIN tags_map ON tags_map.article_id = articles.id";
		}
		$query .= " WHERE status = 'P'
					AND date_uploaded <= UTC_TIMESTAMP()";
		// author id
		if (isset($_GET['author_id'])) {
			$query .= " AND articles.author_id = ".(int)$_GET['author_id'];
		}
		// category url
		if (isset($_GET['category_id'])) {
			$query .= " AND articles.category_id = ".(int)$_GET['category_id'];
		} else {
			$query .= " AND articles.category_id != 0 ";
		}
		// tag
		if (isset($_GET['tag_id'])) {
			$query .= " AND tags_map.tag_id = ".(int)$_GET['tag_id'];
		}
		// year
		if (isset($_GET['year'])) {
			$query .= " AND YEAR(date_uploaded) = ".(int)$_GET['year'];
		}
		// month
		if (isset($_GET['month'])) {
			$query .= " AND MONTH(date_uploaded) = ".(int)$_GET['month'];
		}
		// day
		if (isset($_GET['day'])) {
			$query .= " AND DAY(date_uploaded) = ".(int)$_GET['day'];
		}
		// sort order
		$query .= " ORDER BY date_uploaded DESC";
		$data = compile_article_listing($query, $config_layout);
		return $data;
	}

/**
 * compile_article_listing
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function compile_article_listing($query, $config_layout = '') {
		if(empty($query)) {
			return false;
		}
		// get layout config from database
		if(empty($config_layout)) {
			$config = get_settings('layout');
			$config_layout = $config['layout'];
		}
		
		// set up pagination
		$per_page 	= $config_layout['per_page'];
		$page_no 	= (isset($_GET['page'])) ? (int)$_GET['page'] : '1';
		$from 		= (($page_no * $per_page) - $per_page);
		// no pagination if per page value set to 0
		$query_paginated = (!empty($per_page)) ? $query." LIMIT ".(int)$from.", ".(int)$per_page : $query ;	
		
		// run query
		$conn = reader_connect();
		$result = $conn->query($query_paginated);
		// get total results
		$total_result 	= $conn->query($query);
		$total_articles = $total_result->num_rows;
		$total_pages 	= (!empty($per_page)) ? ceil($total_articles / $per_page) : 1 ;
		
		// get a complete id list of returned articles for filtered results
		$id_list 	= array();
		while($total_row = $total_result->fetch_assoc()) {
			$id_list[] = $total_row['id'];
		}
		
		// get results
		$data 		= array();
		while($row = $result->fetch_assoc()) {
			$row = stripslashes_deep($row);
			$row['category_url'] = (!empty($row['category_url'])) ? $row['category_url'] : $_GET['category_url'] ;
			// adjust times to local timezone if necessary
			$ts = strtotime($row['date_uploaded']);
			$offset = date('Z');
			$row['date_ts'] = $ts+$offset;
			$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
			
			// create links
			$row['link'] = ($config_layout['url_style'] == 'blog') 
				? WW_REAL_WEB_ROOT.'/'.date('Y/m/d',$row['date_ts']).'/'.$row['url'].'/'
				: WW_REAL_WEB_ROOT.'/'.$row['category_url'].'/'.$row['url'].'/';
		
			// do we need a summary?
			if( (empty($row['summary'])) && ($config_layout['list_style'] == 'summary') ) {
				$row['summary'] = create_summary($row['body']);
			}
			
			// do we need an intro
			if($config_layout['list_style'] == 'intro') {
				$row['intro'] = get_intro($row['body']);
				if(empty($row['intro'])) {
					$row['intro'] = 
					'<p>'.extract_sentences($row['body'],50).' <a href="'.$row['link'].'">(continues...)</a></p>';
				}
			}
			
			// do we need the full body?
			if($config_layout['list_style'] != 'full') {
				unset($row['body']);
			} else {
				$row['body'] = convert_relative_urls($row['body']);
			}
	
			// get tags
			$tags = get_article_tags($row['id']);
			$row['tags'] = (!empty($tags)) ? $tags : '' ;
			// add page counts and compile into array
			$row['total_pages'] = $total_pages;
			$row['total_found'] = $total_articles;
			$row['id_list'] 	= $id_list;
			$data[] = $row;
		}
		$result->close();
		$total_result->close(); 
		return $data;			
	}

/**
 * get_podcasts
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 	
	function get_podcasts($config_layout, $formats = '') {
		// get layout config from database
		if(empty($config_layout)) {
			$config = get_settings('layout');
			$config_layout = $config['layout'];
		}
		if(is_array($formats)) {
			$formats = "'".implode("','",$formats)."'";
		} else {
			$formats = "'mp3','mp4','m4a','m4b','m4p','m4v','mov','aac','pdf'";
		} 
		$conn = reader_connect();
		$query = "SELECT
					articles.id, 
				 	articles.title, 
					articles.url, 
					articles.summary,
					articles.body,
					articles.date_uploaded,
					articles.seo_keywords,
					categories.title AS category_title, 
					categories.url AS category_url,
					categories.type AS category_type,
					categories.summary AS category_summary,
					categories.description AS category_description,
					authors.name AS author_name, 
					authors.url AS author_url,
					attachments.title AS attachment_title, 
					attachments.filename,
					attachments.ext, 
					attachments.size, 
					attachments.mime
				FROM attachments_map 
					LEFT JOIN articles ON attachments_map.article_id = articles.id
					LEFT JOIN attachments ON attachments_map.attachment_id = attachments.id
					LEFT JOIN authors ON articles.author_id = authors.id 
					LEFT JOIN categories ON articles.category_id = categories.id 
				WHERE articles.status = 'P'
					AND articles.date_uploaded <= UTC_TIMESTAMP()
					AND attachments.ext IN (".$formats.")";
		// category url
		if (isset($_GET['category_id'])) {
			$query .= " AND articles.category_id = ".(int)$_GET['category_id'];
		}
		// sort order
		$query .= " ORDER BY date_uploaded DESC
					LIMIT 0, ".(int)$config_layout['per_page'];
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) {
			$row = stripslashes_deep($row);
			
			// adjust times to local timezone if necessary
			$ts = strtotime($row['date_uploaded']);
			$offset = date('Z');
			$row['date_ts'] = $ts+$offset;
			$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
			
			// do we need a summary?
			if( (empty($row['summary'])) && ($config_layout['list_style'] == 'summary') ) {
				$row['summary'] = create_summary($row['body']);
			}
			// do we need the full body?
			if($config_layout['list_style'] != 'full') {
				unset($row['body']);
			} else {
				$row['body'] = convert_relative_urls($row['body']);
			}
			// create links
			$link = ($config_layout['url_style'] == 'blog') 
				? WW_REAL_WEB_ROOT.'/'.date('Y/m/d',$row['date_ts']).'/'.$row['url'].'/'
				: WW_REAL_WEB_ROOT.'/'.$row['category_url'].'/'.$row['url'].'/';
			$row['link'] = $link;
			$row['itunes_link'] = WW_WEB_ROOT.'/download/'.$row['ext'].'/'.$row['filename'];
			$data[] = $row;
		}
		$result->close();
		return $data;		
	}

/**
 * get_author_filters
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	function get_author_filters($article_ids) {
		if(empty($article_ids)) {
			return false;
		}
		$id_list = implode(',',$article_ids);
		$conn = reader_connect();
		$query = "SELECT DISTINCT authors.name, authors.url
					FROM articles
					LEFT JOIN authors ON authors.id = articles.author_id
					WHERE articles.id IN (".$id_list.")
					ORDER BY authors.name ASC";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		$result->close();
		return $data;
	}
	
/**
 * get_category_filters
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	function get_category_filters($article_ids) {
		if(empty($article_ids)) {
			return false;
		}
		$id_list = implode(',',$article_ids);
		$conn = reader_connect();
		$query = "SELECT DISTINCT categories.title, categories.url
					FROM articles
					LEFT JOIN categories ON categories.id = articles.category_id
					WHERE articles.id IN (".$id_list.")
					ORDER BY categories.title ASC";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		$result->close();
		return $data;
	}

/**
 * get_tag_filters
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	function get_tag_filters($article_ids) {
		if(empty($article_ids)) {
			return false;
		}
		$id_list = implode(',',$article_ids);
		$conn = reader_connect();
		$query = "SELECT DISTINCT tags.title, tags.url
					FROM tags_map
					LEFT JOIN tags ON tags.id = tags_map.tag_id
					WHERE tags_map.article_id IN (".$id_list.")
					ORDER BY tags.title ASC";
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
		$result->close();
		return $data;
	}
		
/**
 * -----------------------------------------------------------------------------
 * SINGLE ARTICLE
 * -----------------------------------------------------------------------------
 */

	function redirect_article($url, $code = '301'){
		$http = array (
			100 => "HTTP/1.1 100 Continue",
			101 => "HTTP/1.1 101 Switching Protocols",
			200 => "HTTP/1.1 200 OK",
			201 => "HTTP/1.1 201 Created",
			202 => "HTTP/1.1 202 Accepted",
			203 => "HTTP/1.1 203 Non-Authoritative Information",
			204 => "HTTP/1.1 204 No Content",
			205 => "HTTP/1.1 205 Reset Content",
			206 => "HTTP/1.1 206 Partial Content",
			300 => "HTTP/1.1 300 Multiple Choices",
			301 => "HTTP/1.1 301 Moved Permanently",
			302 => "HTTP/1.1 302 Found",
			303 => "HTTP/1.1 303 See Other",
			304 => "HTTP/1.1 304 Not Modified",
			305 => "HTTP/1.1 305 Use Proxy",
			307 => "HTTP/1.1 307 Temporary Redirect",
			400 => "HTTP/1.1 400 Bad Request",
			401 => "HTTP/1.1 401 Unauthorized",
			402 => "HTTP/1.1 402 Payment Required",
			403 => "HTTP/1.1 403 Forbidden",
			404 => "HTTP/1.1 404 Not Found",
			405 => "HTTP/1.1 405 Method Not Allowed",
			406 => "HTTP/1.1 406 Not Acceptable",
			407 => "HTTP/1.1 407 Proxy Authentication Required",
			408 => "HTTP/1.1 408 Request Time-out",
			409 => "HTTP/1.1 409 Conflict",
			410 => "HTTP/1.1 410 Gone",
			411 => "HTTP/1.1 411 Length Required",
			412 => "HTTP/1.1 412 Precondition Failed",
			413 => "HTTP/1.1 413 Request Entity Too Large",
			414 => "HTTP/1.1 414 Request-URI Too Large",
			415 => "HTTP/1.1 415 Unsupported Media Type",
			416 => "HTTP/1.1 416 Requested range not satisfiable",
			417 => "HTTP/1.1 417 Expectation Failed",
			500 => "HTTP/1.1 500 Internal Server Error",
			501 => "HTTP/1.1 501 Not Implemented",
			502 => "HTTP/1.1 502 Bad Gateway",
			503 => "HTTP/1.1 503 Service Unavailable",
			504 => "HTTP/1.1 504 Gateway Time-out"
		);
		if(!empty($http[$code])) {
			header($http[$code]);
		} else {
			header($http[301]);
		}
		header("Location: ".$url);
		exit();
	}

/**
 * get_article
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_article($article_id = '') {
		$article_id = (int)$article_id;
		$conn = reader_connect();
		$query = "SELECT
					articles.id, 
				 	articles.title, 
					articles.url, 
					articles.summary, 
					articles.body, 
					articles.date_uploaded, 
					articles.date_amended,
					categories.title AS category_title, 
					categories.url AS category_url,
					parent.title AS parent_category_title,
					parent.url AS parent_category_url,
					authors.name AS author_name, 
					authors.url AS author_url,
					authors.email AS author_email,
					authors.contact_flag, 
					articles.seo_title,
					articles.seo_desc, 
					articles.seo_keywords,
					articles.redirect_url,
					articles.redirect_code,
					articles.comments_disable, 
					articles.comments_hide
				FROM articles
					LEFT JOIN authors ON articles.author_id = authors.id 
					LEFT JOIN categories ON articles.category_id = categories.id
					LEFT JOIN categories AS parent ON categories.category_id = parent.id
				WHERE articles.status IN('P','A')
					AND articles.date_uploaded <= UTC_TIMESTAMP()";
		$query .= (!empty($article_id)) 
			?  " AND articles.id = ".$article_id 
			: " ORDER BY articles.id DESC LIMIT 0,1" ;
		$result = $conn->query($query);
		$row = $result->fetch_assoc();
		// if a redirect url has been set we redirect right here
		if(!empty($row['redirect_url'])) {
			// send 301 redirect unless another valid code has been set
			$redirect_code = (int)$row['redirect_code'];
			$redirect_code = (!empty($redirect_code)) ? $redirect_code : 301 ;
			redirect_article($row['redirect_url'], $redirect_code);
		}
		$row = stripslashes_deep($row);
		$result->close();
		// adjust times to local timezone if necessary
		$ts = strtotime($row['date_uploaded']);
		$offset = date('Z');
		$row['date_ts'] = $ts+$offset;
		$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
		// fix relative urls
		$row['body'] = convert_relative_urls($row['body']);
		// check pagination
		$row['pages'] = paginate_article_body($row['body']);
		$row['total_pages'] = count($row['pages']);
		// create links
		$row['link']['blog'] = WW_REAL_WEB_ROOT.'/'.date('Y/m/d',$row['date_ts']).'/'.$row['url'].'/';
		$row['link']['cms'] = WW_REAL_WEB_ROOT.'/'.$row['category_url'].'/'.$row['url'].'/';
		// put category url in a get param if not set (for custom css styles)
		$_GET['category_url'] = (isset($_GET['category_url'])) ? $_GET['category_url'] : $row['category_url'] ;
		// get tags
		$tags = get_article_tags($article_id);
		$row['tags'] = (!empty($tags)) ? $tags : '' ;
		// get attachments
		$attachments = get_article_attachments($article_id);
		$row['attachments'] = (!empty($attachments)) ? $attachments : '' ;
		// get comments
		if(empty($row['comments_hide'])) {
			$comments = get_article_comments($article_id);
			$row['comments'] = (!empty($comments)) ? $comments : '' ;
		}
		return $row;	
	}

/**
 * get_article_tags
 * 
 * 
 * 
 * 
 * 
 * 
 */	


	function get_article_tags($id) {
		if(empty($id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT 
					tags.id, tags.title, tags.url
				FROM tags_map
					LEFT JOIN tags ON tags.id = tags_map.tag_id
				WHERE article_id = ".(int)$id;
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$data[] = $row;
		}
		return $data;		
	}

/**
 * get_article_comments
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function get_article_comments($id) {
		if(empty($id)) {
			return false;
		}
		$conn = reader_connect();
		$query = 'SELECT 
					id, reply_id, author_id, title, body, date_uploaded,
					poster_name, poster_link, poster_email 
				FROM comments
				WHERE approved = 1
				AND article_id = '.(int)$id;
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row = stripslashes_deep($row);
			// adjust times to local timezone if necessary
			$ts = strtotime($row['date_uploaded']);
			$offset = date('Z');
			$row['date_ts'] = $ts+$offset;
			$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
			$data[$row['id']] = $row;
		}
		return $data;		
	}

/**
 * get_feed_comments
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function get_feed_comments($id = 0, $limit = 20) {
		$conn = reader_connect();
		$query = 'SELECT 
					article_id, comments.id, comments.title, reply_id, comments.author_id, 
					comments.body, comments.date_uploaded,
					poster_name, poster_link, poster_email,
					articles.title as article_title
				FROM comments
				LEFT JOIN articles on articles.id = comments.article_id
				WHERE approved = 1';
		if(!empty($id)) {
			$query .= ' AND article_id = '.(int)$id;
		}
		$query .= ' ORDER BY date_uploaded DESC LIMIT 0,'.$limit;
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) { 
			$row = stripslashes_deep($row);
			// adjust times to local timezone if necessary
			$ts = strtotime($row['date_uploaded']);
			$offset = date('Z');
			$row['date_ts'] = $ts+$offset;
			$row['date_uploaded'] = date('Y-m-d H:i:s',$row['date_ts']);
			// format text
			$type = (!empty($row['author_id'])) ? '[AUTHOR] ' : '';
			$type = (!empty($row['reply_id'])) ? $type.'[REPLY] ' : $type ;
			$row['title'] = (!empty($row['title'])) ? $row['title'] : 're: '.$row['article_title'] ;
			$row['summary'] = $type.$row['body'].'<br />(posted to article: <strong>'.$row['article_title'].'</strong>)';
			$row['link'] = WW_WEB_ROOT.'/id/'.$row['article_id'].'#comment_'.$row['id'];
			$row['body'] = '';
			$data[] = $row;
		}
		return $data;		
	}

/**
 * paginate_article_body
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function paginate_article_body($body) {
		if(strpos($body,'<!-- pagebreak -->')) {
			$body = explode('<!-- pagebreak -->',$body);
		}
		return $body;		
	}

 /**
 * update_counters
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function update_counters($article_id) {
	 	// database connection
		$conn = author_connect();
		// increment view count
		if(detect_bot() == false) {	
			$update_count = "UPDATE articles 
							SET view_count = view_count+1 
							WHERE id = ".$article_id;
			$conn->query($update_count);	
			if (empty($_SESSION['visited'.$article_id])) { 
				// increment visitor count
				$update_visit = "UPDATE articles 
									SET visit_count = visit_count+1 
									WHERE id=".$article_id;
				$conn->query($update_visit);
				$_SESSION['visited'.$article_id] = $article_id;				
			}
		}		
	}

/**
 * -----------------------------------------------------------------------------
 * COMMENT PROCESSING
 * -----------------------------------------------------------------------------
 */

/**
 * validate_comment
 * 
 * 
 * 
 * 
 * 
 * 
 */	
 
	function validate_comment($config_comments, $article) {
		if (!session_id()) session_start();
		if(!isset($_POST)) {
			return false;
		}
		if(!isset($_SESSION['comment_data'])) {
			return false;
		}
		$form_data = array();
		// recreate token values
		$salt = $_SESSION['comment_data']['salt'];
		$now = gmdate('Ymdh');
		$token_name = md5($article['id'].$now.$salt);
		$token_value = md5($salt.$now.$article['id']);
	
	// check comment posted
		if( (isset($_SESSION['comment_posted'])) && ($_SESSION['comment_posted'] > time()) ) {
			$delay = $_SESSION['comment_posted'] - time();
			$form_data['error'][] = "You need to wait ".$delay." seconds before posting another comment";
		}
				
	// check session/POST token
	
		if(isset($_SESSION['comment_data'][$token_name])) {
			if($_SESSION['comment_data'][$token_name] != $token_value) {
				$form_data['error'][] = "<!--session value not set correctly-->";
			}
			if(isset($_POST[$token_name])) {
				if($_POST[$token_name] != $_SESSION['comment_data'][$token_name]) {
					$form_data['error'][] = "<!--post token does not match session-->";
				}
			} else {
				$form_data['error'][] = "<!--post token not set-->";
			}
			unset($_SESSION['comment_data'][$token_name]);
		// one hour's grace
		} else {
			$prev_now = gmdate('Ymdh',strtotime('1 hour ago'));
			$prev_name = md5($article['id'].$prev_now.$salt);
			$prev_value = md5($salt.$prev_now.$article['id']);
			if(isset($_SESSION['comment_data'][$prev_name]))  {
				if($_SESSION['comment_data'][$prev_name] != $prev_value)  {
					$form_data['error'][] = "<!--prev session value not set correctly-->";	
				}
				if(isset($_POST[$token_name])) {
					if($_POST[$token_name] != $_SESSION['comment_data'][$token_name]) {
						$form_data['error'][] = "<!--post token does not match prev session-->";
					}
				} else {
					$form_data['error'][] = "<!--prev post token not set-->";
				}			
			} else {
				$form_data['error'][] = "<!--session not correctly set-->";
			}
			unset($_SESSION['comment_data'][$prev_name]);
		}
		
	// check maths answer
	
		if(isset($_SESSION['comment_data']['comment_answer'])) {
			if(!empty($_POST['comment_answer'])) {
				if($_POST['comment_answer'] != $_SESSION['comment_data']['comment_answer']) {
					$form_data['error'][] = "Security answer was incorrect";
				}
			} else {
				$form_data['error'][] = "Security answer not given";
			}
		}
		
	// standard validation
	
		// moderate
		$form_data['approved'] = (empty($config_comments['moderate'])) ? 1 : 0 ;
	
		// poster_name - required/URLs not allowed

		if (!empty($_POST['poster_name'])) {
			$poster_name = clean_input($_POST['poster_name']);
			if(stripos($poster_name, '://') !== false) {
				$poster_name = '';
			}
			if(empty($poster_name)) {
				$form_data['error'][] = "Invalid name entered";
			}
		} else {
			$poster_name = 'No-one of consequence';
		}
		$form_data['poster_name'] = $poster_name;
		
		// poster_link - valid web address/strip html
		
		$form_data['poster_link'] = (!empty($_POST['poster_link'])) ? clean_input($_POST['poster_link']) : '';
		
		// poster_email - must be email address
		
		if (!empty($_POST['poster_email'])) {
			$poster_email = clean_input($_POST['poster_email']);
			if (validate_email($poster_email) == false) {
				$form_data['error'][] = "Please enter a valid email address";
			} else {
				$form_data['poster_email'] = $poster_email;
			}
		} else {
			$form_data['poster_email'] = '';
		}		
		
		// comment title - optional
		
		if (!empty($_POST['comment_title'])) {
			$form_data['comment_title'] = clean_input($_POST['comment_title']);
			if(stripos($form_data['comment_title'], $disallowed) !== false) {
				$form_data['comment_title'] = '';
			}
		} else {
			$form_data['comment_title'] = '';	
		}
		
		// comment body - required / strip html?
		
		$allow_html = $config_comments['allow_html'];
		if (!empty($_POST['comment_body'])) {
			$form_data['body'] = clean_input($_POST['comment_body'],$allow_html);
			if(empty($form_data['body'])) {
				$form_data['error'][] = "HTML code is not allowed - as a result your comment has been deleted";
			}		
		} else {
			$form_data['error'][] = "No comment entered";
		}
		
		// article_id - required
		
		$form_data['article_id'] = $article['id'];
		
		// reply id - optional / integer

		$form_data['reply_id'] = (!empty($_POST['reply_id'])) ? (int)$_POST['reply_id'] : 0 ;
		
		// others
		
		$form_data['poster_IP'] = $_SERVER['REMOTE_ADDR'];
		$form_data['date_uploaded'] = gmdate('Y-m-d H:i:s');
		$form_data['author_id'] = (isset($_SESSION['ad_user_id'])) ? (int)$_SESSION['ad_user_id'] : 0 ;
		
		// data for comment approval email
		if(empty($form_data['approved'])) {
			$form_data['article_title'] = $article['title'];
			$form_data['author_email'] 	= $article['author_email'];
			$form_data['author_name'] 	= $article['author_name'];			
		}
		
		// clear comment session
		unset($_SESSION['comment_data']);		
		if(!empty($form_data['error'])) {
			return $form_data['error'];
		} else {
			insert_comment($form_data);
			return false;
		}
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
	
	function insert_comment($form_data) {
		if(empty($form_data)) {
			return false;
		}
		$conn = reader_connect();
		$query = "	INSERT INTO comments
						(reply_id, author_id, article_id, 
						title, body, date_uploaded,
						poster_name, poster_link, poster_email, poster_IP,
						approved)
					VALUES
						(?,?,?,?,?,?,?,?,?,?,?)";
		$stmt = $conn->prepare($query);
		if($stmt === false) { die('stmt: '.$mysqli->error); }
		$bind = $stmt->bind_param('iiisssssssi',
						$form_data['reply_id'],
						$form_data['author_id'],
						$form_data['article_id'],
						$form_data['title'],
						$form_data['body'],
						$form_data['date_uploaded'],
						$form_data['poster_name'],
						$form_data['poster_link'],
						$form_data['poster_email'],
						$form_data['poster_IP'],
						$form_data['approved']);
		if($bind === false) { die('bind: '.$stmt->error); }
		$ex = $stmt->execute();
		if($ex === false) { die('execute: '.$stmt->error); }
		$new_id = $stmt->insert_id;
		$stmt->close();
		// return error or update comment count for article
		if(empty($new_id)) {
			echo "no id returned";
			return false;
		} else {
			unset($_POST);
			// set a session to deter bulk posting
			$_SESSION['comment_posted'] = time()+30;
			// email author
			if(empty($form_data['approved'])) {
				$config = get_settings();
				// get details
				$edit_link = WW_WEB_ROOT.'/ww_edit/index.php?page_name=comments&comment_id='.$new_id;
				// compose mail
				require(WW_ROOT.'/ww_edit/_snippets/class.phpmailer-lite.php');
				$mail = new PHPMailerLite();
				$mail->AddAddress($form_data['author_email'], $form_data['author_name']);
				$mail->SetFrom($config['admin']['email'], $config['site']['title']);
				$mail->Subject = 'A new comment needs approval';
				// html body
				$html_body = '<p>The following comment has been posted to your article: <strong>'.$form_data['article_title'].'</strong></p>';
				if(!empty($form_data['title'])) {
					$html_body .= '<blockquote><em>'.$form_data['title'].'</em><blockquote>';	
				}
				$html_body .= '
				<blockquote>'.$form_data['body'].'</blockquote>
				<p>Submitted by: <em>'.$form_data['poster_name'].'</em> on  <em>'.from_mysql_date($form_data['date_uploaded']).'</em></p>
				<p><strong><a href="'.$edit_link.'">click here to approve or delete this comment</a></strong></p>';
				// text body
				$mail->AltBody = 'The following comment has been posted to your article: '.$form_data['article_title']."\n\n";
				if(!empty($form_data['title'])) {
					$mail->AltBody .= $form_data['title']."\n\n";	
				}
				$mail->AltBody .= $form_data['body']."\n\n";
				$mail->AltBody .= 'Submitted by: '.$form_data['poster_name'].' on  '.from_mysql_date($form_data['date_uploaded'])."\n\n";
				$mail->AltBody .= 'To approve or delete this comment visit this link: '.$edit_link;
				$mail->MsgHTML($html_body);
				$mail->Send();
			}
			clear_cached_page();
			$reload = current_url();		
			header('Location: '.$reload);
			return true;
		}
		
	}

/**
 * -----------------------------------------------------------------------------
 * STRING PROCESSING
 * -----------------------------------------------------------------------------
 */

/**
 * get_intro
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function get_intro($body){
		if(strpos($body,'<!-- more -->')) {
			$body = explode('<!-- more -->',$body);
			return $body[0];
		}
		return false;		
	}

/**
 * create_summary
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function create_summary($body) {
		$summary = extract_sentences($body, 20);
		return $summary;
	}

/**
 * convert_relative_urls()
 * 
 * function to convert relative src to absolute src
 * 
 * takes all links to items in the ww_files folder within an
 * article and ensures they are converted to absolute urls - leaves
 * offsite links alone
 * 
 * @param	string	$html		article body
 * @return	string	$abs_html	article with amended links
 * 
 */	
 	function convert_relative_urls($html) {
		$relative_src = '~(href|src)="(?!http|mailto|javascript)[./]*([^\"]+)~';
		$absolute_src = '$1="'.WW_REAL_WEB_ROOT.'/$2';
		$abs_html = preg_replace($relative_src, $absolute_src, $html);
		return $abs_html;
	}

/**
 * strip_inline_styles
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function strip_inline_styles($string) {
		$pattern[0] = '/( style)="[^"]+"/i';
		$pattern[1] = '/( class)="[^"]+"/i';
		$string = preg_replace($pattern, '', $string);
		return $string;	
	}


/**
 * prepare_string
 * 
 * 
 * 
 * 
 * 
 * 
 */
	
	function prepare_string($string) {
		$string = str_replace("><", "> <", $string);	// add spaces between html tags
		$string = str_replace('&nbsp;',' ',$string);
		$string = strip_tags($string);					// strip html tags
		$string = eregi_replace(" +", " ", $string);	// get rid of multiple spaces as a precaution
		$string = trim($string);						// remove any spaces from the ends
		return $string;			
	}
	

/**
 * count_words
 * 
 * 
 * 
 * 
 * 
 * 
 */
		
	function count_words($string) {
		if(!empty($string)) {
			$string = prepare_string($string);			// prepare string
			$string_arr = explode(' ', $string);	// split string into array of words
			$count = count($string_arr);			// count array elements, i.e. words
			return $count;
		}
		return false;		
	}

/**
 * extract_words
 * 
 * takes a string, cleans it, then extracts a substring, 
 * ensuring that the resulting string ends on a complete word
 *
 * @param 	string 	$string		the string to be counted
 * @param 	int 	$max_chars	(default = 100) maximum no. of characters in returned string
 * @return	string	$extract	the resulting substring
 */

	function extract_words($string, $max_chars=100, $appendor = ' ... ') {
		$string = prepare_string($string);			// prepare string
		$string = $string.' ';					// append space to cater for very short sentences
		$string = substr($string,0,$max_chars);	// limit string to maximum words
		$string_arr = explode(' ', $string);	// split resulting substring into array of words
		$size = count($string_arr);				// count number of whole words in substring
		// loop through each word, skipping the last array element (likely to be a word fragment)
		$extract = '';
		for($i=0;$i<$size-1;$i++){
			$extract .= $string_arr[$i]." ";
		}	
		return $extract.$appendor;
	}


/**
 * extract_sentences
 * 
 * takes a string, cleans it, then returns a substring, 
 * ensuring that only complete sentences are returned and
 * the string is at least $min_words long
 *
 * @param 	string	$string		the original string
 * @param 	int		$min_words	the minimum words to be included in the returned string
 * @return	string	$extract	the resulting extract
 */

	function extract_sentences($string, $min_words = 50) {
		$string = prepare_string($string);				// prepare string
		$words = explode(' ', $string);				// split string into words
													// recompile into a string with min number of words
		$init_string = implode(" ",array_slice($words, 0, $min_words-1));
		$init_size = strlen($init_string);			// count initial string length (in characters)
		$stop = strcspn($string, ".!?",$init_size);	// get the next chunk of text, stopping at sentence terminator
		$stop = $init_size+$stop+1;					// this calculates the final length of the string
		$extract = substr($string,0,$stop);			// finally we get the substring
		return $extract;
	}


	
/**
 * encode_email
 *
 * function to obsure email addresses and avoid spam harvesting
 *
 * @param	string	$e	aemail address to obscure
 * @return	string	$output	obscured email address
 *
*/

	function encode_email($email) {
		$output = '';
		for ($i = 0; $i < strlen($email); $i++) { 
			$output .= '&#'.ord($email[$i]).';'; 
		}
		return $output;
	}


/**
 * -----------------------------------------------------------------------------
 * RSS IMPORT
 * -----------------------------------------------------------------------------
 */

/**
 * import_data
 * 
 * 
 * 
 * 
 */

	function import_data($url) {
		if(empty($url)) {
			return false;
		}
		$ch = curl_init();
		$timeout = 5; // set to zero for no timeout
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		if(curl_errno($ch)) {
			curl_close($ch);
			return false;
		}
		$data = curl_exec($ch);
		curl_close($ch);
		if($data === false) {
			return false;
		}
		return $data;	
	}

/**
 * import_rss
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function import_rss($url, $limit = 10) {
		if(empty($url)) {
			return false;
		}
		$data = import_data($url);
		if($data === false) {
			return false;
		}
		$xml = @simplexml_load_string($data);
		if( ($xml == false) || (empty($xml)) || ($xml->error) ) {
			return false;
		}
		$articles = array();
		$total_items = count($xml->channel->item);
		$limit = ($total_items < $limit) ? $total_items : $limit ;
		for($i = 0; $i < $limit; $i++) {
			$articles[] = array(
			    'title' 		=> (string)$xml->channel->item[$i]->title,
			    'description' 	=> (string)$xml->channel->item[$i]->description,
		    	'link' 			=> (string)$xml->channel->item[$i]->link,
		    	'date' 			=> (string)$xml->channel->item[$i]->pubDate
			);
		}
		/**/
		/*
		foreach ($xml->channel->item as $item) {
		    $articles[] = array(
			    'title' 		=> (string)$item->title,
			    'description' 	=> (string)$item->description,
		    	'link' 			=> (string)$item->link,
		    	'date' 			=> (string)$item->pubDate
			);
		}
		*/
		if(empty($articles)) {
			return false;
		}
		return $articles;
	}

/**
 * -----------------------------------------------------------------------------
 * FILE RETRIEVAL
 * -----------------------------------------------------------------------------
 */


/**
 * get_attachment
 * 
 * 
 * 
 * 
 * 
 * 
 */	


	function get_attachment($id) {
		if(empty($id)) {
			return false;
		}
		$conn = reader_connect();
		$query = "SELECT 
					title, filename, ext, size, mime
				FROM attachments
				WHERE id = ".(int)$id;
		$result = $conn->query($query);
		$data = array();
		$row = $result->fetch_assoc();
		return $row;		
	}

/**
 * serve attachment()
 * 
 * serves an attachment and updates counter
 * 
 * @param	int/string		$url2		if only url2 is provided this should be the database id of the file
 * @param	string			$url3		if provided this should be the filename
 * 
 */	 

	function serve_attachment($id) {
	 	// database connection
		$conn = author_connect();
		// validate id
		$id = (int)$id;
		if(empty($id)) {
			return;
		}
		// get attachment details
		$file = get_attachment($id);
		// update counter
		$query = "UPDATE attachments 
						SET downloads = downloads+1 
						WHERE id = ".$id;
		$conn->query($query);
		// serve attachment
		$file_to_download = WW_ROOT."/ww_files/attachments/".$file['ext']."/".$file['filename'];
		header('Content-Type: '.$file['mime'].'');
		header('Content-Disposition: attachment; filename='.$file['filename'].'');
		// update download counter
		readfile($file_to_download);
		$result->close();
		return;
	}


/**
 * -----------------------------------------------------------------------------
 * DEPRECATED
 * -----------------------------------------------------------------------------
 */	
	
/**
 * get_article_ids
 * 
 * gets a list of valid article ids based on url parameters
 */	
 /*
	function get_article_ids() {
		/*
		if(empty($_GET)) {
			return false;
		}
		$conn = reader_connect();

		$query = "	SELECT id 
					FROM articles
					WHERE status = 'P'
					AND date_uploaded <= UTC_TIMESTAMP()";
		// author id
		if (isset($_GET['author_id'])) {
			$query .= " AND author_id = ".(int)$_GET['author_id'];
		}
		// category url
		if (isset($_GET['category_id'])) {
			$query .= " AND category_id = ".(int)$_GET['category_id'];
		}
		// year
		if (isset($_GET['year'])) {
			$query .= " AND YEAR(date_uploaded) = ".(int)$_GET['year'];
		}
		// month
		if (isset($_GET['month'])) {
			$query .= " AND MONTH(date_uploaded) = ".(int)$_GET['month'];
		}
		// day
		if (isset($_GET['day'])) {
			$query .= " AND DAY(date_uploaded) = ".(int)$_GET['day'];
		}
		$query .= " ORDER BY date_uploaded DESC";
		// echo $query;
		$result = $conn->query($query);
		$data = array();
		while($row = $result->fetch_assoc()) {
			$data[] = $row['id'];
		}
		$result->close(); 
		return $data;
	}
*/
?>