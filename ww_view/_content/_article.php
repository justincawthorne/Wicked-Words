<?php

// meta tags for head section

	$article_meta_title = (!empty($article['seo_title'])) ? $article['seo_title'] : $article['title'].' '.$config['site']['title'] ;
	$config['site']['meta_title'] = $article_meta_title;
	$config['site']['meta']['description'] = (!empty($article['seo_desc'])) ? $article['seo_desc'] : $article['summary'] ;
	$config['site']['meta']['keywords'] = (!empty($article['seo_keywords'])) ? $article['seo_keywords'] : $config['meta']['keywords'] ;
	$config['site']['meta']['author'] = $article['author_name'];
	
// hide or disable comments?

	$hide_comments = ($config['comments']['site_hide']) + ($article['comments_hide']);
		
	$disable_comments = ($config['comments']['site_disable']) + ($article['comments_disable']);
	
	if(empty($disable_comments)) {
		
		$post_errors = (isset($_POST['submit_comment'])) 
			? validate_comment($config['comments']['form_protection'], $article['id']) 
			: '' ;
			
		debug_array($post_errors);
		
	}
	
// output article
	
	echo show_article($article, $config);

// output article comments
	
	/*
		$config['comments']['site_hide']
		$config['comments']['site_disable']
		$config['comments']['form_protection']
		$config['comments']['moderate']
		$config['comments']['allow_html']
		
		$article['comments_disable']
		$article['comments_hide']
	*/
	

	
	
	if(empty($hide_comments)) {
	
		echo show_article_comments($article['comments']);	
			
	}

// output comment form
	


	if(empty($disable_comments)) {
		
		echo show_comment_form($config['comments'], $article['id'], $post_errors);
	
	}
/*	
	debug_array($article);
*/
?>