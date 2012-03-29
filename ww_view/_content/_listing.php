<?php
// some variables

	$show_subcategories = 0;
	$show_filters 		= 0;


// meta tags for head section - slightly different ones for a search results page
	
	$page_title_text 	= array();
	$page_header_text 	= array();
	$list_title_text 	= array();

	if(!empty($_GET['search'])) {	
		
		$config['site']['meta_title'] = 'Search results for &quot;'.$_GET['search'].'&quot; - '.$config['site']['title'];
		$config['site']['meta']['description'] = 'Search results for '.$_GET['search'].' on '.$config['site']['meta_title'];
		$config['site']['meta']['keywords'] = $_GET['search'];
		
		$page_title_text[] = 'containing the term &quot;'.$_GET['search'].'&quot;';
		$page_header_text[] = 'search: '.$_GET['search'];
		
	} else {
		
		// category
		
		if( (isset($_GET['category_id'])) && (isset($_GET['category_url'])) ) {
			
			// slightly more complex for categories in case of parent/child categories

			$category_details = get_category_details($_GET['category_id']);

			if($_GET['category_id'] == 0) {
				$category_details['title'] = 'about';
			}
			
			$parent_category = (!empty($category_details['parent_title'])) 
				? '<a href="'.WW_WEB_ROOT.'/'.$category_details['parent_url'].'/">'.$category_details['parent_title'].'</a> &gt; ' 
				: '' ;
			
			$page_title_text[] = "filed under ".$category_details['title'];
			$page_header_text[] = $parent_category.$category_details['title'];
			
			// add optional link to feed for category
	
			if(!empty($config['admin']['show_all_feeds'])) {
				$config['site']['link'][] = array(
							'rel' 	=>	'alternate',
							'type'	=> 'application/rss+xml',
							'title' =>	'RSS Feed for '.$category_details['title'].' category',
							'href'	=>	WW_WEB_ROOT.'/rss/'.$_GET['category_url']
										);				
			}

			
		}
		
		// author
		
		if( (isset($_GET['author_id'])) && (isset($_GET['author_url'])) ) {
			
			$author_name = get_author_name($_GET['author_id']);
			
			$page_title_text[] = 'by '.$author_name;
			$page_header_text[] = 'author: '.$author_name;
			
			// add optional link to feed for author
	
			if(!empty($config['admin']['show_all_feeds'])) {
				$config['site']['link'][] = array(
							'rel' 	=>	'alternate',
							'type'	=> 	'application/rss+xml',
							'title' =>	'RSS Feed for '.$author_name,
							'href'	=>	WW_WEB_ROOT.'/rss/author/'.$_GET['author_url']
										);				
			}
			
		}
		
		// tag
		
		if( (isset($_GET['tag_id'])) && (isset($_GET['tag_url'])) ) {
			
			$tag_title = get_tag_title($_GET['tag_id']);
			
			$page_title_text[] = 'tagged '.$tag_title;
			$page_header_text[] = 'tag: '.$tag_title;

			// add optional link to feed for tag
	
			if(!empty($config['admin']['show_all_feeds'])) {
				$config['site']['link'][] = array(
							'rel' 	=>	'alternate',
							'type'	=> 	'application/rss+xml',
							'title' =>	'RSS Feed for tag '.$tag_title,
							'href'	=>	WW_WEB_ROOT.'/rss/tag/'.$_GET['tag_url']
										);				
			}
			
		}
		
		// date
		if(isset($_GET['year'])) {
			if(isset($_GET['day'])) {
				$query_ts = strtotime($_GET['year'].'-'.$_GET['month'].'-'.$_GET['day']);
				$page_title_text[] = "posted on ".date('d M Y',$query_ts);
				$page_header_text[] = 'date: '.date('d M Y',$query_ts);
			} elseif(isset($_GET['month'])) {
				$query_ts = strtotime($_GET['year'].'-'.$_GET['month']);
				$page_title_text[] = "posted during ".date('F Y',$query_ts);
				$page_header_text[] = 'month: '.date('F Y',$query_ts);
			} else {
				$page_title_text[] = "posted during ".$_GET['year'];
				$page_header_text[] = 'year: '.$_GET['year'];
			}
		}
		
		// meta tag
		$meta_title_text = implode(', ',$page_title_text);
		$config['site']['meta_title'] = 'Articles '.strip_tags($meta_title_text).' - '.$config['site']['title'];
		
	}
	
	// total results string
	
	$page_header 	= implode(', ',$page_header_text);
	$page_title 	= 'Showing articles '.implode(', ',$page_title_text);
	
	$total			= (!empty($articles[0]['total_found'])) ? $articles[0]['total_found'] : '0' ;
	$total_articles = ($total == 1) ? $total.' article found' : $total.' articles found' ;
	
	// feed listing
	
	if(isset($_GET['feed_listing'])) {
		$config['site']['meta_title'] = 'RSS Feeds for '.$config['site']['title'];
		$page_title = 'RSS Feeds';
		$sub_header = "RSS";
		$total_articles = '';			
	}

	echo show_page_header($page_header,$total_articles);
	
	// any sub categories to list

	if(!empty($show_subcategories)) {
		if(isset($category_details['child'])) {
			echo '
			<ul class="filter_list">
				<li class="filter_heading">Sub-categories:</li> ';
			foreach($category_details['child'] as $child) {
				echo '
				<li><a href="'.WW_WEB_ROOT.'/'.$child['url'].'">'.$child['title'].'</a></li>';
			}
			echo '</ul>';
		}
	}
	
	// any filters to show
	
	if(!empty($show_filters)) {
		
		$id_list = $articles[0]['id_list'];
		
		$link_base = (isset($_GET['category_url'])) ? WW_WEB_ROOT.'/'.$_GET['category_url'] : WW_WEB_ROOT ;
		
		$author_param = (isset($_GET['author_url'])) ? '/author/'.$_GET['author_url'] : '' ;
		$tag_param = (isset($_GET['tag_url'])) ? '/tag/'.$_GET['tag_url'] : '' ;
		
		// author filters
		
		if(!isset($_GET['author_url'])) {
			$author_filters = get_author_filters($id_list);
			if(!empty($author_filters)) {
				echo '
				<ul class="filter_list">
					<li class="filter_heading">Filter by author:</li> ';
				foreach($author_filters as $af) {
					echo '
					<li><a href="'.$link_base.'/author/'.$af['url'].$tag_param.'/">'.$af['name'].'</a></li>';
				}
				echo '</ul>';				
			}
			
		}

		// category filters
		
		if(!isset($_GET['category_url'])) {
			$category_filters = get_category_filters($id_list);
			if(!empty($category_filters)) {
				
				echo '
				<ul class="filter_list">
					<li class="filter_heading">Filter by category:</li> ';
				foreach($category_filters as $cf) {
					echo '
					<li><a href="'.WW_WEB_ROOT.'/'.$cf['url'].$author_param.$tag_param.'/">'.$cf['title'].'</a></li>';
				}
				echo '</ul>';				
			}
			
		}

		// author filters
		
		if(!isset($_GET['tag_url'])) {
			$tag_filters = get_tag_filters($id_list);
			if(!empty($tag_filters)) {
				echo '
				<ul class="filter_list">
					<li class="filter_heading">Filter by tag:</li> ';
				foreach($tag_filters as $tf) {
					echo '
					<li><a href="'.$link_base.$author_param.'/tag/'.$tf['url'].'/">'.$tf['title'].'</a></li>';
				}
				echo '</ul>';				
			}
			
		}
		
	// remove parameters
		echo '
		<ul class="filter_list remove_filter_list">
			<li class="filter_heading">Remove:</li> ';
		if(isset($_GET['author_url'])) {
			echo '
			<li>Author <a href="'.$link_base.$tag_param.'">'.$author_name.'</a></li>';
		}
		if(isset($_GET['category_url'])) {
			echo '
			<li>Category <a href="'.WW_WEB_ROOT.$author_param.$tag_param.'">'.$category_details['title'].'</a></li>';
		}
		if(isset($_GET['tag_url'])) {
			echo '
			<li>Tag <a href="'.$link_base.$author_param.'">'.$tag_title.'</a></li>';
		}
		echo '</ul>';		
		

	}
	
		
	
	
	// output listing
	
	echo show_listing($articles, $page_title, $config['connections']['disqus_shortname']);
	
	if($total > $config['layout']['per_page']) {
		echo show_listing_nav($articles[0]['total_pages'], $config['layout']['per_page']);
	} 
?>