<?php

// meta tags for head section

	$config['site']['meta_title'] = $config['site']['title']." - Home Page";

// output content

	// $page_title = "front page: ".$config['site']['title'];
	
	if(isset($article)) {
		
		echo show_article($article, $config);
		
	} elseif(isset($articles)) {
		
		echo show_page_header('Home Page',$config['site']['title']);
		echo show_listing($articles);
		
		// show navigation
		
		if($config['front']['page_style'] == 'latest_month') {
			echo show_month_nav($months_list);
		} else {
		
			$total = (!empty($articles[0]['total_found'])) ? $articles[0]['total_found'] : '0' ;
			if($total > $config['layout']['per_page']) {
				echo show_front_nav($articles[0]['total_pages']);
			}
			
		}
		
	} elseif($config['front']['page_style'] == 'custom') {
		
		echo 'custom front page';
		
	}
?>