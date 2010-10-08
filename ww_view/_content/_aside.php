<?php
// get config from database

	if(empty($config)) {
		$config = get_settings();
	}
		
// initialise arrays

	$aside_snippet = array();
	$aside_content = array();

	$new_asides = import_asides($config['site']['theme']);
	if(!empty($new_asides)) {
		foreach($new_asides as $aside_title => $aside_data) {
			$aside_snippet[$aside_title] = $aside_data;
		}
	}

// define snippets content

	// generate some basic lists and a search form

	$authors_list 			= get_authors();
	
	$categories_list 		= get_categories();
	
	$categories_basic_list 	= get_categories_basic();
	
	$tags_list 				= get_tags();
	
	$months_list 			= get_months();
	
	$search_form 			= show_search_form();
	
	$aside_snippet['twitter_feed'] = '';
	
	if(!empty($config['connections']['twitter_username'])) {
		
		$twitter_username = $config['connections']['twitter_username'];
		$twitter_feed = get_twitter_feed($twitter_username);
		$aside_snippet['twitter_feed'] = build_snippet('Recent twitterings...',$twitter_feed);
	}



	// now use a ready made function to put the above data arrays into some drop down lists
	
	$months_select_form 	= build_select_form('months_select_form', $months_list);
	
	$categories_select_form = build_select_form('categories_select_form', $categories_list);
	
	$authors_select_form 	= build_select_form('authors_select_form', $authors_list);
	
	$tags_select_form 		= build_select_form('tags_select_form', $tags_list);
	
	// get two lists of articles - the latest and the most popule
	
	$latest_articles 		= get_articles_basic($config['layout']['url_style']);
	
	$popular_articles 		= get_articles_basic($config['layout']['url_style'],'','view_count DESC','5');
	
	
// feeds - this is slightly different - we'll write out the html and put it in a variable
	
	$feeds_img = (file_exists(WW_ROOT.'/ww_view/_img/feed-icon16x16.png')) 
	? '<img src="'.WW_WEB_ROOT.'/ww_view/_img/feed-icon16x16.png" alt="RSS logo" width="16" height="16"/>' 
	: '' ;

	$feeds_list = '
		<ul>
			<li>
				<a href="'.WW_WEB_ROOT.'/rss/" rel="alternate" type="application/rss+xml">'.$feeds_img.'Main feed</a>
			</li>
			<li>
				<a title="RSS feeds for '.$config['site']['title'].'" href="'.WW_WEB_ROOT.'/feeds/">Click here to view all available feeds</a>
			</li>
		</ul>';
		
// get content for menu aside
	
	$aside_snippet['main_menu'] = '';
	
	if($config['layout']['main_menu'] == 'aside') {
		$menu_links = get_links('site_menu');
		$menu_links = (isset($menu_links['site_menu'])) ? $menu_links['site_menu'] : '' ;
		$aside_snippet['main_menu'] = build_snippet('Menu',$menu_links);
	}
	
// get content for blogroll / links aside
	
	$aside_snippet['blogroll'] = '';
	
	$blogroll_links = get_links();
	if(!empty($blogroll_links)) {
		$blogroll_snip = array();
		foreach($blogroll_links as $bl_cat => $bl_links) {
			$links_list = show_links($bl_links);
			$blogroll_snip[] = build_snippet($bl_cat,$links_list);
		}
		$aside_snippet['blogroll'] = implode('',$blogroll_snip);
	}



// now we start formatting the above data into snippet-style html using the build_snippet function

	if(detect_smartphone() == true) {
	
		// if the user is using a mobile we'll only show the dropdown lists
	
		// first we define each snippet and store in a variable
	

		
		// here we build the actual html for each snippet
		
		$aside_snippet['search'] 			= build_snippet('Search',$search_form);
		$aside_snippet['categories_select'] = build_snippet('Categories',$categories_select_form);
		$aside_snippet['months_select'] 	= build_snippet('Months',$months_select_form);
		$aside_snippet['authors_select'] 	= build_snippet('Authors',$authors_select_form);
		$aside_snippet['tags_select'] 		= build_snippet('Tags',$tags_select_form);
		
		// then we insert each snippet variable into an array
		// upper/inner/outer/lower correlates to the divs within the aside section of the html page
		
		$aside_content['upper'] = array($aside_snippet['search']);
		$aside_content['inner']	= array(
										$aside_snippet['categories_select'],
										$aside_snippet['months_select'],
										$aside_snippet['authors_select'],
										$aside_snippet['tags_select']
										
										);
		$aside_content['outer'] = array(); 	// nothing to go in the aside_outer div
		$aside_content['lower']	= array();	// nothing to go in the aside_lower div
	
	} else {

		// we can show a more comprehensive range of snippets if the user is not on a phone
	
		$aside_snippet['authors_list'] 		= build_snippet('Authors',$authors_list);
		$aside_snippet['authors_select'] 	= build_snippet('Authors',$authors_select_form);
		$aside_snippet['categories_list'] 	= build_snippet('Categories',$categories_list);
		$aside_snippet['categories_select'] = build_snippet('Categories',$categories_select_form);
		$aside_snippet['tags_list'] 		= build_snippet('Tags',$tags_list);
		$aside_snippet['tags_select'] 		= build_snippet('Tags',$tags_select_form);
		$aside_snippet['months_list'] 		= build_snippet('Archives',$months_list);
		$aside_snippet['months_select'] 	= build_snippet('Months',$months_select_form);
		$aside_snippet['search'] 			= build_snippet('Search',$search_form);		
		$aside_snippet['latest_articles'] 	= build_snippet('Latest Articles',$latest_articles);
		$aside_snippet['popular_articles'] 	= build_snippet('Most Popular',$popular_articles);
		$aside_snippet['feeds'] 			= build_snippet('Feeds',$feeds_list);
		$aside_snippet['twitter'] 			= '';
	
		// default layout here
	
		$aside_content['upper'] = array($aside_snippet['search']);
		$aside_content['inner']	= array(
										$aside_snippet['main_menu'],
										$aside_snippet['authors_select'],
										$aside_snippet['categories_select'],
										$aside_snippet['tags_select'],
										$aside_snippet['months_select'],
										$aside_snippet['latest_articles'],
										$aside_snippet['popular_articles'],
										$aside_snippet['authors_list'],
										$aside_snippet['categories_list'],
										$aside_snippet['tags_list'],
										$aside_snippet['months_list'],
										$aside_snippet['feeds'],
										$aside_snippet['twitter_feed'],
										$aside_snippet['blogroll']
										);
		$aside_content['outer'] = array();
		$aside_content['lower']	= array();	
	
	}
	
// any aside files to import?



/*
	what happens next?

	ww_view/index.php uses the insert_aside() function to place everything stored within the
	$aside_content array (created above) into $body_content['aside']

*/
?>