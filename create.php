<?php
include_once('ww_config/model_functions.php');

	$sql 		= array();
	$finished 	= 1;
	$error 		= '';
	$conn 		= author_connect();
	
// add author form

	if( (isset($_POST)) && (!empty($_POST['add_author'])) ) {
		
		if( (empty($_POST['author_name'])) || (empty($_POST['author_email']))
			|| (empty($_POST['author_pass'])) || (empty($_POST['author_pass_confirm'])) ) {
			$error = '<p>All author fields need to be filled in</p>';
		} else {
			
			$author_name 	= $_POST['author_name'];
			$author_url 	= urlencode(str_replace(' ','_',$author_name));
			$author_email 	= $_POST['author_email'];
			$epattern = '^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})';
			if (!eregi($epattern, $author_email)) {
				$error = '<p>Invalid email entered</p>';
			}
			$author_pass = $_POST['author_pass'];
			$pass_len = strlen($author_pass);
			if($pass_len < 6) {
				$error = '<p>Password needs to be at least 6 characters long</p>';
			}
			if($author_pass != $_POST['author_pass_confirm']) {
				$error = '<p>Passwords don\'t match</p>';
			}
			$hash_pass = hash_password($author_pass);
			
			// site details
			
			$site_query = array();
			$site_query[] = "UPDATE settings 
							SET property_value = '".$conn->real_escape_string($author_email)."'
							WHERE property_name = 'email'";
			if(!empty($_POST['site_title'])) {
				$site_query[] = "UPDATE settings 
							SET property_value = '".$conn->real_escape_string($_POST['site_title'])."'
							WHERE property_name = 'title'";				
			}
			if(!empty($_POST['site_tagline'])) {
				$site_query[] = "UPDATE settings 
							SET property_value = '".$conn->real_escape_string($_POST['site_tagline'])."'
							WHERE property_name = 'subtitle'";				
			}
			if(!empty($_POST['site_description'])) {
				$site_query[] = "UPDATE settings 
							SET property_value = '".$conn->real_escape_string($_POST['site_description'])."'
							WHERE property_name = 'description'";				
			}
		
		}

		if(empty($error)) {
			$query = "INSERT INTO authors
					(name, url, email, pass)
					VALUES
					(
						'".$conn->real_escape_string($author_name)."',
						'".$conn->real_escape_string($author_url)."',
						'".$conn->real_escape_string($author_email)."',
						'".$conn->real_escape_string($hash_pass)."'
					)";
			$result = $conn->query($query);
			if(!$result) {
				$error = '<p>Sorry - there was a problem updating the author details - try again</p>';
			} else {
				$finished = 1;
			}
			
			// add site details
			foreach($site_query as $sq) {
				$site_result = $conn->query($sq);
			}
				
		}
		
	}

	$add_author_form = '
		<h2>Last step:</h2>
		<h4>you need to add an author account so you can, you know, log in and stuff</h4>
		<form action="'.$_SERVER["PHP_SELF"].'" method="post">
			'.$error.'
			<p><label for="author_name">Author name:</label> 
				<input name="author_name" id="author_name" size="20" type="text"/>
				<br/>(this is the name that will appear on your articles)
			</p>
			
			<p><label for="author_email">Email address:</label> 
				<input name="author_email" id="author_email" size="20" type="text"/>
				<br/>(make sure this is your real email address!)
			</p>
			
			<p><label for="author_pass">Password:</label> 
				<input name="author_pass" id="author_pass" size="20" type="password"/>
				<br/>(must be at least 6 characters)
			</p>

			<p><label for="author_pass_confirm">Confirm Password:</label> 
				<input name="author_pass_confirm" id="author_pass_confirm" size="20" type="password"/>
			</p>
			
			<p><em>You can optionally add some site details here - you can also add (or edit) these at any time in the admin section</em></p>
				
			
			<p><label for="site_title">Site Title:</label> 
				<input name="site_title" id="site_title" size="40" type="text"/>
				<br/>(yep, you were right: this is the title of your site)
			</p>
			
			<p><label for="site_tagline">Site Tagline:</label> 
				<textarea name="site_tagline" id="site_tagline" rows="3" cols="25"></textarea>
				<br/>(an optional tagline or one line description to go below the title)
			</p>
			
			<p><label for="site_description">Site Description:</label> 
				<textarea name="site_description" id="site_description" rows="5" cols="25"></textarea>
				<br/>(a longer description for search engines)
			</p>
	
		<input name="add_author" value="Add Author" type="submit"/>
		</form>
		';

// articles
	
	$sql['articles'] = "
	CREATE TABLE IF NOT EXISTS articles (
		id smallint(5) unsigned NOT NULL auto_increment,
		title varchar(255) NOT NULL default '',
		url varchar(255) NOT NULL,
		summary text,
		body mediumtext NOT NULL,
		category_id smallint(3) NOT NULL default '0',
		author_id smallint(3) NOT NULL default '0',
		`status` varchar(2) NOT NULL default 'D',
		date_uploaded datetime NOT NULL,
		date_amended datetime NOT NULL,
		seo_title varchar(150) default NULL,
		seo_desc varchar(255) default NULL,
		seo_keywords varchar(255) default NULL,
		redirect_code smallint(3) default NULL,
		redirect_url varchar(255) default NULL,
		view_count smallint(6) unsigned NOT NULL default '0',
		visit_count smallint(6) unsigned NOT NULL default '0',
		comments_disable tinyint(1) NOT NULL default '0',
		comments_hide tinyint(1) NOT NULL default '0',
		PRIMARY KEY  (id),
		FULLTEXT KEY `fulltext` (title,summary,body)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=101;
	";

// attachments
	
	$sql['attachments'] = "
	CREATE TABLE IF NOT EXISTS attachments (
	  id smallint(5) unsigned NOT NULL auto_increment,
	  filename varchar(50) NOT NULL default '',
	  title varchar(200) default NULL,
	  summary text,
	  author_id smallint(3) NOT NULL,
	  ext varchar(4) NOT NULL default 0,
	  mime varchar(80) default NULL,
	  size int(9) NOT NULL,
	  downloads smallint(6) NOT NULL,
	  date_uploaded datetime NOT NULL default '0000-00-00 00:00:00',
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// attachments map
	
	$sql['attachments_map'] = "
	CREATE TABLE IF NOT EXISTS attachments_map (
	  id smallint(5) unsigned NOT NULL auto_increment,
	  attachment_id smallint(5) unsigned NOT NULL,
	  article_id smallint(5) unsigned NOT NULL,
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// authors

	$sql['authors'] = "
	CREATE TABLE IF NOT EXISTS authors (
	  id smallint(3) unsigned NOT NULL auto_increment,
	  name varchar(50) NOT NULL default '',
	  url varchar(50) default NULL,
	  summary varchar(255) default NULL,
	  biography text,
	  email varchar(80) NOT NULL default '',
	  pass varchar(140) NOT NULL default '',
	  guest_flag tinyint(1) NOT NULL default 0,
	  guest_areas varchar(50) default NULL,
	  sub_expiry datetime NOT NULL default '0000-00-00 00:00:00',
	  last_login datetime default NULL,
	  last_ip varchar(16) default NULL,
	  last_sess varchar(32) default NULL,
	  image varchar(35) default NULL,
	  contact_flag tinyint(1) default 0,
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// categories
	
	$sql['categories'] = "
	CREATE TABLE IF NOT EXISTS categories (
	  id smallint(3) unsigned NOT NULL auto_increment,
	  category_id smallint(3) unsigned NOT NULL default 0,
	  title varchar(25) NOT NULL,
	  url varchar(25) NOT NULL,
	  summary varchar(255) default NULL,
	  description text,
	  type varchar(255) default NULL,
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// comments

	$sql['comments'] = "
	CREATE TABLE IF NOT EXISTS comments (
	  id int(11) NOT NULL auto_increment,
	  reply_id int(11) default NULL,
	  author_id smallint(3) default NULL,
	  article_id int(6) NOT NULL default 0,
	  title varchar(255) default NULL,
	  body text NOT NULL,
	  date_uploaded datetime NOT NULL,
	  poster_name varchar(30) NOT NULL,
	  poster_link varchar(150) default NULL,
	  poster_email varchar(160) default NULL,
	  poster_IP varchar(16) default NULL,
	  approved tinyint(1) NOT NULL default 0,
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// edits

	$sql['edits'] = "
	CREATE TABLE IF NOT EXISTS edits (
	  id int(5) unsigned NOT NULL auto_increment,
	  author_id smallint(3) unsigned NOT NULL,
	  article_id smallint(5) NOT NULL,
	  body mediumtext NOT NULL,
	  date_edited datetime NOT NULL,
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// images
	
	$sql['images'] = "
	CREATE TABLE IF NOT EXISTS images (
	  id smallint(3) unsigned NOT NULL auto_increment,
	  filename varchar(50) NOT NULL default '',
	  title varchar(200) default NULL,
	  alt varchar(255) default NULL,
	  credit varchar(255) default NULL,
	  caption text,
	  author_id smallint(3) unsigned NOT NULL,
	  width int(4) unsigned NOT NULL default 0,
	  height int(5) unsigned NOT NULL default 0,
	  ext varchar(4) NOT NULL,
	  mime varchar(30) default NULL,
	  size mediumint(7) unsigned NOT NULL,
	  date_uploaded datetime NOT NULL default '0000-00-00 00:00:00',
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// links

	$sql['links'] = "
	CREATE TABLE IF NOT EXISTS links (
	  id smallint(3) NOT NULL auto_increment,
	  title varchar(100) default NULL,
	  url varchar(255) NOT NULL default '',
	  attributes text,
	  summary text,
	  category varchar(30) default NULL,
	  sort smallint(3) unsigned NOT NULL default 0,
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// settings

	$sql['settings'] = "
	CREATE TABLE IF NOT EXISTS settings (
	  element_name varchar(30) NOT NULL,
	  property_name varchar(30) NOT NULL,
	  property_value text default NULL,
	  default_value varchar(255) default NULL,
	  formtype varchar(20) default NULL,
	  options varchar(255) default NULL,
	  summary varchar(255) default NULL,
	  PRIMARY KEY  (property_name)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// tags

	$sql['tags'] = "
	CREATE TABLE IF NOT EXISTS tags (
	  id smallint(3) unsigned NOT NULL auto_increment,
	  title varchar(25) NOT NULL,
	  url varchar(25) NOT NULL,
	  summary varchar(255) default NULL,
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// tags map

	$sql['tags_map'] = "
	CREATE TABLE IF NOT EXISTS tags_map (
	  id smallint(5) unsigned NOT NULL auto_increment,
	  tag_id smallint(3) unsigned NOT NULL,
	  article_id smallint(5) unsigned NOT NULL,
	  PRIMARY KEY  (id)
	) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
	";

// settings data

	$settings_insert = "
	REPLACE INTO settings (element_name, property_name, property_value, default_value, formtype, options, summary) VALUES
	('admin', 'shutdown', '0', '0', 'checkbox', NULL, 'use this to temporarily suspend public access to your site - a message will be displayed on the front page'),
	('admin', 'email', 'wickedwordscms@gmail.com', 'wickedwordscms@gmail.com', 'text', NULL, 'An admin email address, mainly used for comment moderation. Overwritten for articles by the article author.'),
	('admin', 'redirect_url', '', NULL, 'text', NULL, 'If you\'re using mod_rewrite to point to a different (virtual) directory than the one wickedwords is stored in then enter the full URL suffix here (NOTE must not end with one)'),
	('admin', 'timezone', 'Australia/Perth', 'Europe/London', 'text', NULL, 'see <a href=\"http://php.net/manual/en/timezones.php\">http://php.net/manual/en/timezones.php</a> for supported values (this setting will be ignored if you don\'t have permission to set the timezone on your mysql database'),
	('admin', 'feed_url', '', '', 'text', NULL, 'alternative URL for rss feed if using feedburner or similar'),
	('admin', 'show_all_feeds', '1', '1', 'checkbox', NULL, 'show all available feeds (if no then only the link for the main feed is shown'),
	('admin', 'complete_feed', '1', '1', 'checkbox', NULL, 'show complete article in feed (if no only a summary is shown)'),
	('site', 'title', 'Wicked Words', 'Wicked Words', 'text', NULL, 'the title of your site'),
	('site', 'subtitle', 'A new site powered by Wicked Words, an Evil Chicken production', 'A new site powered by Wicked Words, an Evil Chicken production', 'textarea', NULL, 'a tagline, or subtitle, for your site'),	
	('site', 'theme', '/default', '/default', 'select', NULL, 'theme to use for site'),	
	('site', 'doctype', 'xhtml11', 'xhtml11', 'select', 'xhtml10,xhtml11,html5', 'the doctype for your site'),
	('site', 'html_lang', 'en', 'en', 'text', NULL, 'the default language for your site (ISO 2 letter language code)'),
	('site', 'header_panel_html', '', '', 'textarea', NULL, 'enter any html you wish to place in the header_panel section'),	
	('site', 'xml_namespaces', '', '', 'textarea', NULL, 'enter any additional namespaces you require as a string'),
	('meta', 'author', 'Wicked Words', 'Wicked Words', 'text', NULL, 'The author of the site. Overwritten on article pages by the article author.'),
	('meta', 'Content-Type', 'text/html;charset=UTF-8', 'text/html;charset=UTF-8', 'text', NULL, NULL),
	('meta', 'description', 'Wicked Words - new installation', 'Wicked Words - new installation', 'textarea', NULL, 'A keyword rich description of your site\'s content'),
	('meta', 'keywords', 'wicked words, blog, cms', 'wicked words, blog, cms', 'textarea', NULL, 'Any keywords relevant to your site (separated by commas)'),
	('comments', 'allow_html', '0', '0', 'checkbox', NULL, 'allow html in comments or not'),
	('comments', 'form_protection', '1', '1', 'checkbox', NULL, 'add spam protection to comment form'),
	('comments', 'moderate', '0', '0', 'checkbox', NULL, 'moderate comments - an email will be sent to the article author each time a comment is posted'),
	('comments', 'site_disable', '0', '0', 'checkbox', NULL, 'disable posting of comments site-wide'),
	('comments', 'site_hide', '0', '0', 'checkbox', NULL, 'hide existing comments sitewide'),	
	('files', 'max_file_size', '10485760', '10485760', 'text', NULL, 'Maximum allowable size for file/attachment uploads (in bytes)'),
	('files', 'max_image_size', '102400', '102400', 'text', NULL, 'Maximum allowable image size for uploads (in bytes)'),		
	('files', 'max_image_width', '640', '640', 'text', NULL, 'Maximum width for images - oversized images will be resized to this value (set to 0 to allow any width)'),
	('files', 'thumb_width', '120', '120', 'text', NULL, 'default width for generated image thumbnails'),	
	('files', 'allowed_formats', NULL, NULL, 'text', NULL, 'list of file formats allowed as attachments'),
	('files', 'podcast_formats', 'mp3,mp4,m4a,m4v,mov,aac,pdf', 'mp3,mp4,m4a,m4v,mov,aac,pdf', 'text', NULL, 'list of file formats compatible with podcasts'),	
	('front', 'article_id', '0', '', 'select', NULL, 'ID of article to be placed permanently on front page. This overrides all other front page settings.'),
	('front', 'front_list_style', 'summary', 'summary', 'select', 'summary, intro, full', 'How much of each article to display on the front page. Only applicable when latestmonth or per_page is selected as front_view'),
	('front', 'page_style', 'per_page', 'per_page', 'select', 'latest_post,latest_month,per_page', 'Display options for the first page of your site'),
	('layout', 'list_style', 'summary', 'summary', 'select', 'basic,summary,intro,full', 'Display options for results pages (or listings pages)'),
	('layout', 'main_menu', '0', '0', 'select', 'none,header,navbar,aside', 'Position of main menu: none, at top of page (header); below header (navbar); or in column (aside).'),
	('layout', 'per_page', '20', '20', 'text', NULL, 'Number of posts to show per page.'),
	('layout', 'url_style', 'cms', 'cms', 'select', 'cms,blog', NULL),	
	('cache', 'cache_ext', 'cache', 'cache', 'text', NULL, 'file extension to give cached files'),
	('cache', 'cache_time', '600', '600', 'text', NULL, 'Caching time in seconds'),
	('cache', 'caching_on', '0', '0', 'checkbox', NULL, 'Select yes to turn caching on'),
	('connections', 'sharing_bookmarks', '1', '1', 'checkbox', NULL, 'whether to show social media sharing bookmarks'),
	('connections', 'sharing_position', 'article_footer', 'article_footer', 'select', 'article_footer,article_top', 'position of social media sharing bookmarks'),
	('connections', 'sharing_custom', '', '', 'textarea', NULL, 'paste custom sharing script (e.g. AddThis) here - this will automatically override the default sharing bookmarks'),
	('connections', 'google_analytics', NULL, NULL, 'text', NULL, 'id for google analytics - http://www.google.com/analytics/'),
	('connections', 'compete_analytics', NULL, NULL, 'text', NULL, 'id for compete analytics - https://my.compete.com/'),
	('connections', 'getclicky_analytics', NULL, NULL, 'text', NULL, 'id for getclicky analytics - http://getclicky.com/'),
	('connections', 'quantcast_analytics', NULL, NULL, 'text', NULL, 'id for quantcast analytics - http://www.quantcast.com/'),
	('connections', 'woopra_analytics', '0', '0', 'checkbox', NULL, 'check here to use woopra analytics (N.B. you will need to have registered your website first) - http://www.woopra.com/'),
	('connections', 'twitter_username', NULL, NULL, 'text', NULL, 'your twitter username - used for the twitter snippet, leave blank if you don\'t want your twitter feed displayed on your site'),
	('connections', 'disqus_shortname', NULL, NULL, 'text', NULL, 'if you have a Disqus account enter the short name here to have disqus process all comments on the site')
	;
	";
	
/* design elements removed
	('design', 'site_width', '960', '960', 'text', NULL, 'overall width of site'),
	('design', 'margins', '10', '10', 'text', NULL, 'space between main content and outer elements (columns)'),	
	('design', 'title_height', '200', '200', 'text', NULL, 'height of title bar'),
	('design', 'aside_width', '220', '220', 'text', NULL, 'total width of sidebar'),
	('design', 'footer_height', '80', '80', 'text', NULL, 'height of footer'),
*/

echo '
<html>
<head>
<style type="text/css">
body {
	font-family: Helvetica, Arial, sans-serif;
	font-size: 100%;
}
p {
	font-size: 0.875em;
}
form {
	margin: 0 auto;
	width: 440px;
}
label {
	display: block;
	clear: both;
	font-weight: bold;
}
	
</style>
</head>
<body>';

// create folders

	if(@mkdir(WW_ROOT."/ww_files/_cache/",0755,true)) {
		echo 'created cache folder<br />';		
	} else {
		echo 'could not create cache folder<br />';
	}
	if(@mkdir(WW_ROOT."/ww_files/attachments/",0755,true)) {
		echo 'created attachments folder<br />';		
	} else {
		echo 'could not create attachments folder<br />';
	}
	if(@mkdir(WW_ROOT."/ww_files/images/authors/",0755,true)) {
		echo 'created author images folder<br />';		
	} else {
		echo 'could not create author images folder<br />';
	}
	if(@mkdir(WW_ROOT."/ww_files/images/thumbs/",0755,true)) {
		echo 'created thumbnail images folder<br />';		
	} else {
		echo 'could not create thumbnail images folder<br />';
	}

// get existing tables list

	$table_query = "SHOW tables";
	$table_result = $conn->query($table_query);
	$tables = array();
	while($row = $table_result->fetch_array()) {
		$tables[] = $row[0];
	}

// check/create tables

	foreach($sql as $table_name => $create) {
		$check = "SELECT * FROM ".$table_name;
		$check_result = $conn->query($check);
		if(in_array($table_name, $tables)) {
			// echo 'table '.$table_name.' already created<br />';
			continue;
		}
		$finished = 0;
		echo 'creating table '.$table_name.':';
		$result = $conn->query($create);
		if(!$result) {
			echo $conn->error.'<br />';
		} else {
			echo ' -> success!<br/>';
		}
	}
	
// insert settings data if the settings table is empty

	$settings_query = "SELECT COUNT(property_name) as total FROM settings";
	$settings_result = $conn->query($settings_query);
	$settings_rows = $settings_result->fetch_assoc();
	$settings_total = $settings_rows['total'];
	if(empty($settings_total)) {
		$finished = 0;
		$insert_result = $conn->query($settings_insert);
		if(!$result) {
			echo 'error inserting settings data: '.$insert_result->error.'<br />';
		} else {
			echo 'inserting settings data -> success!<br/>';
		}
	}


// finally insert some author data

	$author_query = "SELECT COUNT(id) as total FROM authors";
	$author_result = $conn->query($author_query);
	$author_rows = $author_result->fetch_assoc();
	$author_total = $author_rows['total'];
	if(empty($author_total)) {
		$finished = 0;
		echo $add_author_form;
	}

// link to homepage if everything is sorted

	if(!empty($finished)) {	
		$link = str_replace('create.php','',$_SERVER['PHP_SELF']);
		echo '<p>it appears our work here is done - please <a href="'.$link.'">go about your business</a>...</p>';
	}
	
echo '
</body>
</html>';	
?>