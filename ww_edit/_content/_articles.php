<?php
	if(!defined('WW_SESS')) {
		exit();
	}


// page title - if undefined the site title is displayed by default

	$page_title = 'Article Listings';


// get main content
	
	// any functions
	
	/* 
		contributors can only see their own articles
		authors and editors have access to all articles
	*/
	
	
	if( (!empty($_SESSION[WW_SESS]['guest'])) && ($_SESSION[WW_SESS]['level'] == 'contributor') ) {
		
		$_GET['author_id'] = $_SESSION[WW_SESS]['user_id'];
			
	}
			
	$articles =	get_articles_admin();
			
	$total_articles = (!empty($articles)) ? $articles[0]['total_found'] : 0 ;
	$total_pages = (!empty($articles)) ? $articles[0]['total_pages'] : 0 ;

// per page - same definition as list_admin_articles()
	
	$per_page = ( (!isset($_GET['per_page'])) || (empty($_GET['per_page'])) ) 
		? 15 
		: (int)$_GET['per_page'] ;
	
	// sub header text

		$title_text = array();
		$status = '';
		// category
		if(isset($_GET['category_id'])) {
			$title_text[] = " filed under ".get_category_title($_GET['category_id']);
			$sub_header_text[] = get_category_title($_GET['category_id']);
		}
		
		// author
		if(isset($_GET['author_id'])) {
			$title_text[] = " by ".get_author_name($_GET['author_id']);
			$sub_header_text[] = 'author: '.get_author_name($_GET['author_id']);
		}
		
		// tag
		if(isset($_GET['tag_id'])) {
			$title_text[] = " tagged ".get_tag_title($_GET['tag_id']);
			$sub_header_text[] = 'tag: '.get_tag_title($_GET['tag_id']);
		}
		
		// postdated
		if(isset($_GET['postdated'])) {
			$status = ' postdated';
		}
		
		// other statuses
		if(isset($_GET['status'])) {
			
			$status_list = define_article_status();
			$status = ' '.$status_list[$_GET['status']];
			
		}
		
		
		
	
	// any content generation
	
	$left_header = $total_articles.' articles found';
	$right_header = '<a href="'.$_SERVER["PHP_SELF"].'?page_name=write">/Write a new article</a>';
	$page_header = show_page_header($left_header, $right_header);


// get aside content

	// any functions
	
	$status_list 	= get_articles_stats(1);
	$author_list 	= get_authors_admin(1);
	$category_list 	= get_categories_admin(1);
	$tags_list 		= get_tags_admin(1);

	
	// any content generation


// output main content - into $main_content variable

	$main_content = $page_header;
	
	$main_content .= '<h4>Showing all'.$status.' articles'.implode(', ',$title_text).'</h4>';
	
	$main_content .= build_admin_article_listing($articles);
	
	if($total_articles > $per_page) {
		$main_content .=  show_admin_listing_nav($total_pages, $per_page);
	} 


// output aside content - into $aside_content variable

	$aside_content = '<h4>Filter articles</h4>';
	$aside_content .= '<p><a href="'.$_SERVER["PHP_SELF"].'?page_name=articles">show all articles</a></p>';
	
	$aside_content .= build_snippet('Status',$status_list);
	$aside_content .= build_snippet('Authors',$author_list);
	$aside_content .= build_snippet('Categories',$category_list);
	$aside_content .= build_snippet('Tags',$tags_list);

?>