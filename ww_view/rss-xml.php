<?php
header('Content-type: application/rss+xml');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

// additional headers, including podcast relevant headers

echo '<rss xmlns:content="http://purl.org/rss/1.0/modules/content/" 
	xmlns:atom="http://www.w3.org/2005/Atom" 
	xmlns:dc="http://purl.org/dc/elements/1.1/" 
	xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" 
	version="2.0" xml:lang="en-US">';

// get theme image folder for logos

	/*
		rss_logo.jpg - default logo for all rss feeds
		podcast_logo.jpg - default logo for podcasts
		
		[category_url]_rss_logo.jpg - for specific categories
		[category_url]_podcast_logo.jpg - for specific podcast categories
	*/

	$theme_img_path = WW_ROOT.'/ww_view/themes'.$config['site']['theme'].'/_img';
	$theme_img_folder = WW_WEB_ROOT.'/ww_view/themes'.$config['site']['theme'].'/_img';

// some parameters and variables

	$this_feed = current_url();	// get url of this page
	
// get valid podcast attachment formats
	
	$podcast_formats = $config['files']['podcast_formats'];
	/*
	if(!empty($podcast_formats)) {
		$podcast_formats = explode(',',$podcast_formats);
	}
	*/
// get complete or partial article
	
	$config['layout']['list_style'] = (!empty($config['admin']['complete_feed'])) ? 'full' : 'summary' ;
	
// determine what feed to display

	if($_GET['feed'] == 'comments') {
		
		$article_id = (isset($_GET['article_id'])) ? (int)$_GET['article_id'] : 0 ;
		$feed_content = get_feed_comments($article_id);
		$feed_description = "comments feed";
			
	} elseif( ($_GET['feed'] == 'articles') || ($_GET['feed'] == 'podcast') ) {
		
		$feed_content = ($_GET['feed'] == 'podcast') 
			? get_podcasts($config['layout'],$podcast_formats) 
			: get_articles($config['layout']);
			
		// default description
		$feed_description = ($_GET['feed'] == 'podcast') 
			? "The ".$config['site']['title']." podcast" 
			: "All articles posted on ".$config['site']['title'] ;
		
		// category
		if( (isset($_GET['category_id'])) && (isset($_GET['category_url'])) ) {
			$details = get_category_details($_GET['category_id']);
			// default description
			$feed_description = (!empty($details['description'])) 
				? $details['description']
				: "Articles filed under the category ".$details['title'] ;
			// podcast description
			if($_GET['feed'] == 'podcast'){
				$feed_description = (!empty($details['description'])) 
				?  $details['description']
				: "The ".$details['title']." podcast - hosted by ".$config['site']['title'] ;				
			}

		}
		
		// author
		if( (isset($_GET['author_id'])) && (isset($_GET['author_url'])) ) {
			$details = get_author_details($_GET['author_id']);
			$config['site']['meta']['author'] = $details['name'];
			$feed_description = "Articles written by ".$details['name'];
		}
		
		// tag
		if( (isset($_GET['tag_id'])) && (isset($_GET['tag_url'])) ) {
			$details = get_tag_details($_GET['tag_id']);
			$feed_description = "Articles tagged ".$details['title'];

		}		
		
	} else {
		
		header("HTTP/1.0 404 Not Found");
		header('Location: '.WW_WEB_ROOT);
		
	}

// start the xml feed

	echo '
	<channel>
		<title>'.$config['site']['title'].'</title>
		<description>'.$feed_description.'</description>
		<link>'.WW_WEB_ROOT.'</link>
		<copyright>'.$config['site']['title'].' - '.$config['meta']['author'].'</copyright>
		<generator>Wicked Words RSS Generator</generator>
		<atom:link href="'.$this_feed.'" rel="self" type="application/rss+xml" />';

// display rss logo if one exists

	if( (isset($_GET['category_url'])) && (file_exists($theme_img_path.'/'.$_GET['category_url'].'_rss_logo.jpg')) ) {
		echo "
		<image>
			<title>".$feed_title."</title> 
			<url>".$theme_img_folder."/".$_GET['category_url']."_rss_logo.jpg</url> 
			<link>".WW_WEB_ROOT."</link> 
		</image>";	
	} elseif(file_exists($theme_img_path."/rss_logo.jpg")) {
		echo "
		<image>
			<title>".$feed_title."</title> 
			<url>".$theme_img_folder."/rss_logo.jpg</url> 
			<link>".WW_WEB_ROOT."</link> 
		</image>";
	}

// add some further tags for a podcast

	if($_GET['feed'] == 'podcast') {
		echo '
		<itunes:owner>
			<itunes:name>'.$config['site']['meta']['author'].'</itunes:name>
			<itunes:email>'.$config['admin']['email'].'</itunes:email>
		</itunes:owner>
		<itunes:author>'.$config['site']['meta']['author'].'</itunes:author>';
	
	// add logo if we have one
		if( (isset($_GET['category_url'])) && (file_exists($theme_img_path.'/'.$_GET['category_url'].'_podcast_logo.jpg')) ) {
			echo "
			<itunes:image href=".$theme_img_folder.'/'.$_GET['category_url'].'_podcast_logo.jpg" />';	
		} elseif(file_exists($theme_img_path.'/podcast_logo.jpg')) {
			echo "
			<itunes:image href=".$theme_img_folder.'/podcast_logo.jpg" />';	
		}
	
	// itunes category	
		if(!empty($details['type'])) {
			echo '
		<itunes:category text="'.$details['type'].'"/>';
		}
	
	// itunes subtitle
		if(!empty($details['summary'])) {
			echo '
		<itunes:subtitle text="'.$details['summary'].'"/>';
		}
	
	// itunes summary
		if(!empty($details['description'])) {
			echo '
		<itunes:summary text="'.$details['description'].'"/>';
		}
		echo '
		<itunes:explicit>no</itunes:explicit>';
	}
	
// loop through content

	foreach($feed_content as $item) {
		
		$item_title = $item['title'];
		$item_description = (empty($item['summary'])) ? create_summary($item['body']) : prepare_string($item['summary']) ;
		
		// show complete article?
		
		if(!empty($config['admin']['complete_feed'])){
			$summary = (!empty($item['summary'])) ? "<p><strong>".prepare_string($item['summary'])."</strong></p>" : '' ;
			$body = strip_inline_styles($item['body']);
			$item_description = "<![CDATA[".$summary.$body."]]>";
		}
		$item_date = date('r',strtotime($item['date_uploaded']));
		
		// start output to browser
		
		echo '
		<item>
			<title>'.$item_title.'</title>
			<description>'.$item_description.'</description>
			<link>'.$item['link'].'</link>';
			
		// some additional entries for podcasts
	
		if($_GET['feed'] == 'podcast') {
	
			echo "
			<itunes:author>".$item['author_name']."</itunes:author>
			<dc:creator>".$item['author_name']."</dc:creator>";
			if(!empty($item['seo_keywords'])) {
				echo '
			<itunes:keywords>'.$item['seo_keywords'].'</itunes:keywords>';
			}
			
			// add enclosure
			echo "
			<enclosure url=\"".$item['itunes_link']."\" length=\"".$item['size']."\" type=\"".$item['mime']."\"/>";
	
		} else {
		
			// check for attachments
			
			$attachments = get_article_attachments($item['id']);
			if(!empty($attachments) ) {
				foreach($attachments as $attach) {
					// if this is a podcast feed we need to include the filename to trick itunes
					$url = ($_GET['feed'] == 'podcast') ? $attach['itunes_link'] : $attach['link'] ;
					// show enclosure
					echo "
					<enclosure url=\"".$url."\" length=\"".$attach['size']."\" type=\"".$attach['mime']."\"/>";	
				}
			}
			
		}
		// close off item
	
		echo "
			<guid isPermaLink=\"true\">".$item['link']."</guid> 
			<pubDate>".$item_date."</pubDate>
		</item>";
		
	}

// close xml feed

	echo '
	</channel>
	</rss>';	
?>