<?php

/**
 * -----------------------------------------------------------------------------
 * HEAD SECTION
 * -----------------------------------------------------------------------------
 */

/**
 * get_includes
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	function get_includes($theme) {
	    // browse for js files in the current theme folder
	    $theme_folder = "/ww_view/themes".$theme;
		$list_php = get_files(WW_ROOT.$theme_folder.'/_includes/','php');
	    if($list_php == false) {
	    	return false;
	    }
	    $includes = array();
		foreach($list_php as $add_php) {
			$includes[] = $add_php['path'].$add_php['name'];
	    }
	    return $includes;
	}

/**
 * show_head
 * 
 * 
 * 
 * 
 * 
 * 
 */	

function show_head($head_content = '', $config = '') {

	// get default config from database if needed
	if(empty($config)) {
		$config = get_settings();
	 	/*
	 	$config['site']['meta'] = $config['meta'];
	 	unset($config['meta']);
		$config_site = $config['site'];
		unset($config);
		*/
	}
	
	// doctype
	echo (!empty($head_content['doctype'])) 
	? $head_content['doctype'] 
	: declare_doctype($config['site']['doctype']) ;
	
	// html lang
	echo (!empty($head_content['html_lang'])) 
	? $head_content['html_lang'] 
	: declare_html_lang($config['site']['html_lang'], $config['site']['xml_namespaces']) ;
	
	// start head section
	
	$title = (!empty($config['site']['meta_title'])) ? $config['site']['meta_title'] : $config['site']['title'] ;
	echo "\n".'<head>
	<title>'.$title.'</title>';
	
	// base href
	echo '
	<base href="'.constant('WW_WEB_ROOT').'" />'."\n";		
	
	// meta tags
	echo (!empty($head_content['meta'])) 
	? $head_content['meta'] 
	: declare_meta($config['meta']) ;
	
	// head links
	$links_array = (isset($config['site']['link'])) ? $config['site']['link'] : array() ;
	echo (!empty($head_content['links'])) 
	? $head_content['links'] 
	: insert_links($links_array);
	
	// favicon
	echo (!empty($head_content['favicon'])) 
	? $head_content['favicon'] 
	: insert_favicon($config['site']['theme']);
	
	// css
	echo (!empty($head_content['css'])) 
	? $head_content['css'] 
	: insert_css($config['site']['theme']);
	
	// js
	echo (!empty($head_content['js'])) 
	? $head_content['js'] 
	: insert_js($config['site']['theme']);
		
	// analytics
	if(!empty($config['connections']['google_analytics'])) {
		echo insert_google_analytics($config['connections']['google_analytics']);		
	}

	// close head section
	echo "\n".'</head>';
	return;
}

/**
 * -----------------------------------------------------------------------------
 * HEAD SECTION - NESTED FUNCTIONS
 * -----------------------------------------------------------------------------
 */

/**
 * declare_doctype
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
function declare_doctype($type = 'xhtml11') {
	switch ($type) {

		case 'html5':
		$doctype = '<!DOCTYPE html>';
		break;
		
		case 'xhtml-RDFa':
		// this needed to validated creative commons license
		$doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" ';
		$doctype .= '"http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">';
		break;
		
		case 'xhtml11':
		// default xhtml 1.1
		default:
		$doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" ';
		$doctype .= '"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
		break;
		
	}
	return $doctype;
}	

/**
 * declare_html_lang
 * 
 * 
 * 
 * 
 * 
 * 
 */	

function declare_html_lang($lang = 'en', $namespaces = '') {
	$lang = (empty($lang)) ? 'en' : $lang ;
	$namespaces = (empty($namespaces)) ? '' : ' '.trim($namespaces) ;
	$html_lang = "\n".'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$lang.'" lang="'.$lang.'"'.$namespaces.'>';
	return $html_lang;		
}

/**
 * declare_meta
 * 
 * 
 * 
 * 
 * 
 * 
 */	

function declare_meta($meta = array()) {
	if(count($meta) == 0) {
		return false;
	}
	$equiv = array('Allow','Content-Encoding','Content-Length',
					'Content-Type','Date','Expires','Last-Modified',
					'Location','Refresh','Set-Cookie','WWW-Authenticate');
	$metatags = '
	<!-- meta tags -->
	';
	foreach($meta as $name => $content) {
		if(in_array($name,$equiv)) {
		$metatags .= '
	<meta http-equiv="'.$name.'" content="'.$content.'" />';
		} else {	
		$metatags .= '
	<meta name="'.$name.'" content="'.$content.'" />';
		}
	}
	return $metatags."\n";		
}

/**
 * insert_links
 * 
 * 
 * 
 * 
 * 
 * 
 */	

function insert_links($links_array = array()) {
	$link_html = '
	<!-- links -->
	
	<link rel="alternate" type="application/rss+xml" title="Main Feed - RSS 2.0" href="'.WW_WEB_ROOT.'/rss/" />';
	// check for head links in the links table
	$head_links = get_links('site_head');
	if(!empty($head_links)) {
		foreach($head_links['site_head'] as $cat => $data){
			$link_html .= '
	<link '.htmlspecialchars_decode($data['attributes']).' title="'.$data['title'].'" href="'.$data['url'].'" />';
		}
	}
	if(empty($links_array)) {
		return $link_html;
	}
	// otherwise loop through supplied links
	foreach($links_array as $h_link) {
		$link_html .= '
	<link';
		foreach($h_link as $attr => $val) {
			$link_html .= ' '.$attr.'="'.$val.'"';
		}
		$link_html .= ' />';
	}
	return $link_html."\n";
}

 
/**
 * insert_css
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
function insert_css($theme = 'default') {
	// set paths
	$theme_folder = "/ww_view/themes".$theme;
	$css = '
	<!-- css -->';
	
	// use structure.css if provided, otherwise use dynamic structure
	if (file_exists(WW_ROOT.$theme_folder.'/structure.css')) {
		$css .= '
	<link rel="stylesheet" type="text/css" href="'.WW_REAL_WEB_ROOT.$theme_folder.'/structure.css" />'."\n";
	} else {
		$css .= '
	<link rel="stylesheet" type="text/css" href="'.WW_REAL_WEB_ROOT.'/ww_view/themes/structure_css.php" />'."\n";
	}
	
	// styles
	if (file_exists(WW_ROOT.$theme_folder.'/style.css')) {
		$css .= '
	<link rel="stylesheet" type="text/css" href="'.WW_REAL_WEB_ROOT.$theme_folder.'/style.css" />'."\n";
	}
	
	// page name specific styles
	if (file_exists(WW_ROOT.$theme_folder.'/'.$_GET['page_name'].'.css')) {
		$css .= '
	<link rel="stylesheet" type="text/css" href="'.WW_REAL_WEB_ROOT.$theme_folder.'/'.$_GET['page_name'].'.css" />'."\n";
	}
	
	// category specific styles
	if(isset($_GET['category_url'])) {
		if (file_exists(WW_ROOT.$theme_folder.'/'.$_GET['category_url'].'.css')) {
			$css .= '
		<link rel="stylesheet" type="text/css" href="'.WW_REAL_WEB_ROOT.$theme_folder.'/'.$_GET['category_url'].'.css" />'."\n";
		}
	}
	
	// IE 7 fixes
	if (file_exists(WW_ROOT.$theme_folder.'/ie7.css')) {
		$css .= '
	<!--[if IE 7]>
	<link rel="stylesheet" type="text/css" href="'.WW_REAL_WEB_ROOT.$theme_folder.'/ie7.css" />
	<![endif]-->'."\n";
	}
	
	// other IE fixes
	if (file_exists(WW_ROOT.$theme_folder.'/ie6.css')) {
		$css .= '
	<!--[if lt IE 7]>
	<link rel="stylesheet" type="text/css" href="'.WW_REAL_WEB_ROOT.$theme_folder.'/ie6.css" />
	<![endif]-->'."\n";
	}
	
	// print
	if (file_exists(WW_ROOT.$theme_folder.'/print.css')) {
		$css .= '
	<link rel="stylesheet" media="print" type="text/css" href="'.WW_REAL_WEB_ROOT.$theme_folder.'/print.css" />'."\n";
	}
	
	// pda
	if (file_exists(WW_ROOT.$theme_folder.'/pda.css')) {
		$css .= '
	<link rel="stylesheet" media="handheld" type="text/css" href="'.WW_REAL_WEB_ROOT.$theme_folder.'/pda.css" />'."\n";
	}
	
	// smartphone user
	if(detect_smartphone() == true) {
		
		if (file_exists(WW_ROOT.$theme_folder.'/iphone.css')) {
			$css .= '
		<link rel="stylesheet" media="screen" type="text/css" href="'.WW_REAL_WEB_ROOT.$theme_folder.'/iphone.css" />
		<meta name="viewport" content="width=device-width" />'."\n";
		}
		
	}
	return $css;	
}

/**
 * insert_js
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
function insert_js($theme = 'default') {
	$js = '
	<!-- javascript -->
	<script type="text/javascript" src="'.WW_REAL_WEB_ROOT.'/ww_view/_js/jquery.js"></script>';
    // browse for js files in the current theme folder
    $theme_folder = "/ww_view/themes".$theme;
	$list_js = get_files(WW_ROOT.$theme_folder.'/_js/','js');
    if($list_js != false) {
		foreach($list_js as $add_js) {
	    	$js .= '
		<script type="text/javascript" src="'.$add_js['link'].'"></script>';
    	}
	}
	// add default js if themed version not included
	if(!file_exists(WW_ROOT.$theme_folder.'/_js/default.js')) {
		$js .= '
		<script type="text/javascript" src="'.WW_REAL_WEB_ROOT.'/ww_view/_js/default.js"></script>';		
	}
    return $js;
}

/**
 * insert_favicon
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
function insert_favicon($theme = 'default') {
	$favicon = '';
	$theme_folder = "/ww_view/themes".$theme;
	// look for a favicon in the theme folder
	if(file_exists(WW_ROOT.$theme_folder."/_img/favicon.ico")) {
		$favicon = "
	<!-- favicon -->
	<link rel=\"icon\" type=\"image/x-icon\" href=\"".WW_REAL_WEB_ROOT.$theme_folder."/_img/favicon.ico\" />
	<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"".WW_REAL_WEB_ROOT.$theme_folder."/_img/favicon.ico\" />\n";
	} elseif(file_exists(WW_ROOT."/ww_view/_img/favicon.ico")) {
		$favicon = "
	<!-- favicon -->
	<link rel=\"icon\" type=\"image/x-icon\" href=\"".WW_REAL_WEB_ROOT."/ww_view/_img/favicon.ico\" />
	<link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"".WW_REAL_WEB_ROOT."/ww_view/_img/favicon.ico\" />\n";		
	}
	return $favicon;		
}


/**
 * -----------------------------------------------------------------------------
 * BODY SECTION
 * -----------------------------------------------------------------------------
 */

/**
 * show_body
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function show_body($body_content, $config = 0) {
		// get config from database
		if(empty($config)) {
			$config = get_settings();
		}
		// start body html
		echo '
	<body>
		<div id="outer_wrapper">
		<div id="inner_wrapper">';
		
		// header
		$panel 	= (!empty($config['site']['header_panel_html'])) ? $config['site']['header_panel_html'] : '' ;
		$nav	= ($config['layout']['main_menu'] == 'header') ? insert_nav() : '' ;
		echo (!empty($body_content['header'])) 
			? $body_content['header'] 
			: insert_header($config['site']['title'],$config['site']['subtitle'], $panel, $nav) ;

		// nav - only insert automatically if main_menu position is set to 'navbar'
		if($config['layout']['main_menu'] == 'navbar') {
			echo (!empty($body_content['nav']))
			? $body_content['nav']
			: insert_nav() ;			
		}
		
		// start content wrapper		
		echo '	
		<div id="content_wrapper">';
		
		// main content
		echo insert_main_content($body_content['main']);
		
		// aside
		echo (!empty($body_content['aside'])) 
			? $body_content['aside'] 
			: insert_aside() ;
		
		// close content wrapper
		echo '
		</div> <!-- close content_wrapper -->
		</div> <!-- close inner_wrapper -->
		</div> <!-- close outer_wrapper -->';
		
		// footer
		echo (!empty($body_content['footer'])) 
			? $body_content['footer'] 
			: insert_footer() ;
		
		// analytics
		if(!empty($config['connections']['woopra_analytics'])) {
			echo insert_woopra_analytics();		
		}
		if(!empty($config['connections']['compete_analytics'])) {
			echo insert_compete_analytics($config['connections']['compete_analytics']);		
		}
		if(!empty($config['connections']['getclicky_analytics'])) {
			echo insert_getclicky_analytics($config['connections']['getclicky_analytics']);		
		}
		if(!empty($config['connections']['quantcast_analytics'])) {
			echo insert_quantcast_analytics($config['connections']['quantcast_analytics']);		
		}
		if(!empty($config['connections']['disqus_shortname'])) {
			echo insert_disqus_comment_count($config['connections']['disqus_shortname']);
		}
		echo '	
	</body>
	</html>';
	}

/**
 * -----------------------------------------------------------------------------
 * BODY SECTION - NESTED FUNCTIONS
 * -----------------------------------------------------------------------------
 */


/**
 * insert_header
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_header($title = '', $subtitle = '', $panel = '', $nav = '') {
		$header = '
		<!-- header section -->
		<div id="header">
		
		<div id="header_content">

			'.$nav.'

			<div id="header_text">
				<p>
					<a title="back to the homepage for '.$title.'" href="'.WW_WEB_ROOT.'">'.$title.'</a>
				</p>
				<small>'.$subtitle.'</small>
			</div>
		
			<div id="header_link">
				<p>
					<a title="back to the homepage for '.$title.'" href="'.WW_WEB_ROOT.'">'.$title.'</a>
				</p>
			</div>
		
			<div id="header_panel">
				'.$panel.'
			</div>
		
		</div>
		
		</div>';
		return $header;
	}


/**
 * insert_nav
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_nav() {
		$menu_links = get_links('site_menu');
		if(empty($menu_links)) {
			return;
		}
		$nav = '
			<!-- nav section -->
			<div id="nav">
			<ul id="nav_links">';
		foreach($menu_links['site_menu'] as $cat => $data) {
			$title_attr = (!empty($data['summary'])) ? $data['title'].' - '.$data['summary'] : $data['title'] ;
			$nav .= '
				<li>
					<a href="'.$data['url'].'" title="'.$title_attr.'">'.$data['title'].'</a>
				</li>
			';
		}	
		$nav .= '
			</ul>
			</div>';
		return $nav;
	}

/**
 * insert_main_content
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function insert_main_content($html = '') {
		$main = '
		<!-- main content section -->
		<div id="main_content">'.
			$html.'
		</div>
		<!-- close main_content -->
			';
		return $main;
	}

/**
 * import asides
 * 
 * usage: $aside_snippet = import_asides($config['site']['theme']);
 * 
 * 
 * 
 * 
 */	

	function import_asides($theme) {
		$theme_asides_folder = WW_ROOT.'/ww_view/themes'.$theme.'/_asides/';
		$aside_files = get_files($theme_asides_folder);
		if(empty($aside_files)) {
			return;
		}
		foreach($aside_files as $aside_import) {

			$aside_title = basename($aside_import['filename'],".php");
			ob_start();
			include($aside_import['path'].$aside_import['filename']);
			${$aside_import['filename']} = ob_get_contents();
			ob_end_clean();
			$aside_snippet[$aside_title] = ${$aside_import['filename']};

		}
		return $aside_snippet;
	}

/**
 * insert_aside
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function insert_aside($aside_content = array()) {
		if(empty($aside_content)) {
			include_once(WW_ROOT.'/ww_view/_content/_aside.php');
		}
		$aside = '
		<!-- aside section -->
		<div id="aside">
			<div id="aside_upper">'.implode('',$aside_content['upper']).'</div>
			<div id="aside_inner">'.implode('',$aside_content['inner']).'</div>
			<div id="aside_outer">'.implode('',$aside_content['outer']).'</div>
			<div id="aside_lower">'.implode('',$aside_content['lower']).'</div>
		</div>';
		return $aside;
	}

/**
 * insert_footer
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_footer($html = '') {
		if(empty($html)) {
			$html = 
			'<p>Thank you for taking the time to look at this footer. We hope you have enjoyed this website.<br />
			- powered by Wicked Words -</p>';
		}
		$footer = '
		<!-- footer section -->
		<div id="footer">';
		if(!empty($html)) {
			$footer .= $html;
		}
		$footer .= '
		</div>';
		return $footer;
	}

/**
 * -----------------------------------------------------------------------------
 * CONNECTIONS - ANALYTICS, DISQUS
 * -----------------------------------------------------------------------------
 */

/**
 * insert_google_analytics
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_google_analytics($google_analytics_id) {
		if(empty($google_analytics_id)) {
			return false;
		}
		$ga_code = "
		<!-- google analytics -->
		<script type=\"text/javascript\">
		//<![CDATA[
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', '".$google_analytics_id."']);
		_gaq.push(['_trackPageview']);
		
		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
		//]]>
		</script>
		<!-- end google analytics -->";
		return $ga_code;	
	}

/**
 * insert_getclicky_analytics
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_getclicky_analytics($getclicky_id) {
		if(empty($getclicky_id)) {
			return false;
		}
		$getclicky = "
		<!-- getclicky analytics -->
		<script type=\"text/javascript\">
		var clicky = { log: function(){ return; }, goal: function(){ return; }};
		var clicky_site_id = ".$getclicky_id.";
		(function() {
		  var s = document.createElement('script');
		  s.type = 'text/javascript';
		  s.async = true;
		  s.src = ( document.location.protocol == 'https:' ? 'https://static.getclicky.com/js' : 'http://static.getclicky.com/js' );
		  ( document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0] ).appendChild( s );
		})();
		</script>
		<a title=\"Web Analytics\" href=\"http://getclicky.com/".$getclicky_id."\"></a>
		<noscript>
		<p>
			<img alt=\"Clicky\" width=\"1\" height=\"1\" src=\"http://in.getclicky.com/".$getclicky_id."ns.gif\" />
		</p>
		</noscript>
		<!-- end getclicky analytics -->
		";
		return $getclicky;
	}

/**
 * insert_quantcast_analytics
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function insert_quantcast_analytics($quantcast_id) {
		if(empty($quantcast_id)) {
			return false;
		}
		$quantcast = '
		<!-- quantcast analytics -->
		<script type="text/javascript">
		_qoptions={
		qacct:"'.$quantcast_id.'"
		};
		</script>
		<script type="text/javascript" src="http://edge.quantserve.com/quant.js"></script>
		<noscript>
		<img src="http://pixel.quantserve.com/pixel/'.$quantcast_id.'.gif" style="display: none;" border="0" height="1" width="1" alt="Quantcast"/>
		</noscript>
		<!-- end quantcast analytics -->
		';
		return $quantcast;
	}

/**
 * insert_compete_analytics
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function insert_compete_analytics($compete_id) {
		if(empty($compete_id)) {
			return false;
		}
		$compete = "
		<!-- compete analytics -->
		<script type=\"text/javascript\">
	    __compete_code = '".$compete_id."';
	    (function () {
	        var s = document.createElement('script'),
	            d = document.getElementsByTagName('head')[0] ||
	                document.getElementsByTagName('body')[0],
	            t = 'https:' == document.location.protocol ? 
	                'https://c.compete.com/bootstrap/' : 
	                'http://c.compete.com/bootstrap/';
	        s.src = t + __compete_code + '/bootstrap.js';
	        s.type = 'text/javascript';
	        s.async = 'async'; 
	        if (d) { d.appendChild(s); }
	    }());
		</script>
		<!-- end compete analytics -->
		";
		return $compete;
	}

/**
 * insert_woopra_analytics
 * 
 * 
 * 
 * 
 * 
 * 
 */	
	
	function insert_woopra_analytics() {
		$woopra = '
		<!-- woopra analytics -->
		<script type="text/javascript" src="//static.woopra.com/js/woopra.v2.js"></script>
		<script type="text/javascript">
		woopraTracker.track();
		</script>
		<!-- end woopra analytics -->
		';
		return $woopra;
	}

/**
 * insert_bookmarks
 * 
 * 
 * 
 * 
 * 
 */	

	function insert_bookmarks($title, $link) {
		// store information about the bookmarking sites here, so we can easily change this later
		$social_sites = array(
			"del.icio.us" 	=> "http://del.icio.us/post?url={url}&amp;title={title}",
			"Digg" 			=> "http://digg.com/submit?phase=2&amp;url={url}&amp;title={title}",
			"Facebook" 		=> "http://www.facebook.com/sharer.php?u={url}&amp;t={title}",
			"Google Bookmarks" => "http://www.google.com/bookmarks/mark?op=edit&amp;bkmk={url}&amp;title={title}",
			"Reddit" 		=> "http://reddit.com/submit?url={url}&amp;title={title}",
			"StumbleUpon" 	=> "http://www.stumbleupon.com/submit?url={url}&amp;title={title}",
			"Technorati" 	=> "http://www.technorati.com/faves?add=".WW_WEB_ROOT."",
			"Twitter"		=> "http://www.twitter.com/home?status=Just+read:+{title}+at+".WW_WEB_ROOT,
			"Yahoo MyWeb" 	=> "http://myweb2.search.yahoo.com/myresults/bookmarklet?u={url}&amp;t={title}"
		); 	
		// start list
		$bg_pos = 0; // variable for incrementing sb background sprite		
		$bookmarks_html = "
			<ul id=\"sharing_bookmarks\">";

		foreach($social_sites as $social_site=>$social_url){
			
			// set link to text or icon depending on bookmark_style		
			$link_text = (empty($icons)) ? $social_site : '' ; 
			$style = " style=\"background-image:url('".WW_REAL_WEB_ROOT."/ww_view/_img/sb_sprite.gif');background-position: ".$bg_pos."px 0px;\" "; 
			
			// replace vars {title} and {url} in $social_url
			$url = str_replace("{title}",urlencode($title), str_replace("{url}",$link,$social_url) );
		 
			$bookmarks_html .=  "
				<li>
					<a title=\"Add to ".$social_site."\" 
						href=\"".$link."\"
						target=\"_blank\"
						".$style.">
					</a>
				</li>\n";
				
			$bg_pos = $bg_pos-22; // each icon is 22px wide in the sprite, so we move the bg 22px each time
		}
		$bookmarks_html .=  "
			</ul>\n";
		return $bookmarks_html;		
	}

/**
 * insert_disqus
 * 
 * placed wherever comments need to be
 * 
 * 
 * 
 * 
 */	
	
	function insert_disqus($disqus_name) {
		if(empty($disqus_name)) {
			return false;
		}
		$disqus = "
		<!-- disqus commenting -->
		<div id=\"disqus_thread\"></div>
		<script type=\"text/javascript\">
			/**
			* var disqus_identifier; [Optional but recommended: Define a unique identifier (e.g. post id or slug) for this thread] 
			*/
			(function() {
			var dsq = document.createElement('script'); dsq.type = 'text/javascript'; dsq.async = true;
			dsq.src = 'http://".$disqus_name.".disqus.com/embed.js';
			(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
			})();
		</script>
		<noscript>Please enable JavaScript to view the <a href=\"http://disqus.com/?ref_noscript=".$disqus_name."\">comments powered by Disqus.</a></noscript>
		<a href=\"http://disqus.com\" class=\"dsq-brlink\">blog comments powered by <span class=\"logo-disqus\">Disqus</span></a>
		";
		return $disqus;	
	}

/**
 * insert_disqus_comment_count
 * 
 * placed right before closing </body> tag
 * 
 * 
 * 
 * 
 */	
	
	function insert_disqus_comment_count($disqus_name) {
		if(empty($disqus_name)) {
			return false;
		}
		$disqus = "
		<!-- disqus commenting -->
		<script type=\"text/javascript\">
			var disqus_shortname = '".$disqus_name."';
			(function () {
			var s = document.createElement('script'); s.async = true;
			s.src = 'http://disqus.com/forums/".$disqus_name."/count.js';
			(document.getElementsByTagName('HEAD')[0] || document.getElementsByTagName('BODY')[0]).appendChild(s);
			}());
		</script>
		";
		return $disqus;	
	}

/**
 * -----------------------------------------------------------------------------
 * ARTICLE SECTION
 * -----------------------------------------------------------------------------
 */

/**
 * show_article
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function show_article($article, $config) {
		if(empty($article)) {
			return false;
		}

		update_counters($article['id']);
		$article_pre_url = ($config['layout']['url_style'] == 'cms') ? $article['category_url'] : date('Y/m/d' ,strtotime($article['date_uploaded'])) ;
		$article['link'] = WW_WEB_ROOT."/".$article_pre_url."/".$article['url']."/";
		$article['current_page'] = (!empty($_GET['p'])) ? (int)$_GET['p'] : 0 ;
		// generate bookmarks
		$sharing_bookmarks = '';
		if(!empty($config['connections']['sharing_bookmarks'])) {
			if(empty($config['connections']['sharing_custom'])) {
				$sharing_bookmarks = insert_bookmarks($article['title'], $article['link']);
			} else {
				$sharing_bookmarks = $config['connections']['sharing_custom'];	
			}
		}		
		// echo insert_page_header();
		echo show_page_header($article['category_title'], date('d M Y',strtotime($article['date_uploaded'])));
		echo '
		<div class="article_wrapper">';
		echo show_article_title($article);
		echo show_article_byline($article);
		if($config['connections']['sharing_position'] == 'article_top') {
			echo $sharing_bookmarks;
		}
		echo show_article_summary($article);
		echo show_article_body($article);
		if($config['connections']['sharing_position'] == 'article_footer') {
			echo $sharing_bookmarks;
		}
		echo show_article_attachments($article['attachments']);
		echo show_article_tags($article['tags']);
		echo '
		</div> <!-- close article wrapper -->
		';
	}
 

/**
 * -----------------------------------------------------------------------------
 * ARTICLE SECTION - NESTED FUNCTIONS
 * -----------------------------------------------------------------------------
 */

/**
 * show_page_header
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_page_header($left_text, $right_text) {
		$html = '
		<!-- page header -->
		<div id="page_header">
			<span class="header_left">'.$left_text.'</span>
			<span class="header_right">'.$right_text.'</span>
		</div>
		';
		return $html;

	}	

/**
 * show_article_title
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_article_title($article) {
		// add appendor if this is second page or greater
		$title = ($article['current_page'] > 1) ? $article['title']." (cont.)" : $article['title'] ;
		$html = "
		<!-- article title -->
		<h1>
			<a href=\"".$article['link']."\" rel=\"bookmark\" title=\"permalink to ".$article['title']."\">
			".$title."</a>
		</h1>";
		return $html;	
	}

/**
 * show_article_byline
 * 
 * 
 * 
 * 
 * 
 * 
 */
 	
	function show_article_byline($article) {
		$html = "
		<!-- article byline -->
		<p class=\"byline\">
			<span class=\"byline_author\">by <a href=\"".WW_WEB_ROOT."/author/".$article['author_url']."\">".$article['author_name']."</a></span>
			<span class=\"byline_date\">".date('d F Y' ,strtotime($article['date_uploaded']))."</span>
			<span class=\"byline_category\"><a href=\"".WW_WEB_ROOT."/".$article['category_url']."\">".$article['category_title']."</a></span>
		</p>
		";
		return $html;	
	}

/**
 * show_article_summary
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_article_summary($article) {
		// only show on the first page
		$current_page = (isset($article['current_page'])) ? $article['current_page'] : 1 ;
		if( (!empty($article['summary'])) && ($current_page < 2) ) {
			$html = 
		'<!-- article summary -->
		<p class="summary">'.$article['summary'].'</p>'."\n";
			return $html;
		}
		return false;	
	}

/**
 * show_article_body
 * 
 * 
 * 
 * 
 * 
 * 
 */
 	
	function show_article_body($article) {
		// show full article for print version
		// paginated article for screen version
		if($article['total_pages'] == 1) {
			return $article['body'];
		}
		$current_page 	= (empty($_GET['p'])) ? '1' : (int)$_GET['p'] ;
		$body = '
		<!-- article body -->';
		foreach($article['pages'] as $page=>$content) {
			$hide = ($page == ($current_page-1)) ? '' : ' hide' ;
			$body .= '
		<div class="article_page'.$hide.'">
			'.$content.'
		</div>';
		}
		//$body 			= $article['pages'][$current_page-1];
	// set up page links
		$body .= show_article_nav($article);
		return $body;
	}

/**
 * show_article_nav
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_article_nav($article) {
		// page params
		$current_page 	= (empty($_GET['p'])) ? '1' : (int)$_GET['p'] ;
		$previous_page	= ($current_page > 1) ? $current_page-1 : 0 ;
		$next_page		= ($current_page < $article['total_pages']) ? $current_page+1 : 0 ;
		// build html
		$html = '
		<!-- article navigation -->
		<div id="page_nav" class="article_page_nav">';
		if(!empty($previous_page)) { 
			$html .= '
				<span id="previous">
					<a href="'.$article['link'].'p/'.$previous_page.'/" title="go to previous page">previous</a>
				</span>'; 
		}
		if(!empty($next_page)) { 
			$html .= '
				<span id="next">
					<a href="'.$article['link'].'p/'.$next_page.'/" title="go to next page">next</a>
				</span>'; 
		}		
		$html .= '
				<ul>';
		for($pp = 1; $pp <= $article['total_pages']; ++$pp) {
			$html .=  '
					<li><a href="'.$article['link'].'p/'.$pp.'/" title="go to page '.$pp.'">page'.$pp.'</a></li>';
		}
		$html .= '
				</ul>';

		$html .= '
		</div>'."\n";
		return $html;
	}
 
/**
 * show_article_tags
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_article_tags($tags_array) {
		if(empty($tags_array)) {
			return false;
		}
		$html = '
		<!-- article tags -->
		<ul id="article_tags">
			<li>Tags:</li>';
		foreach($tags_array as $key => $tag) {
			$html .= '
			<li>
				<a href="'.WW_WEB_ROOT.'/tag/'.$tag['url'].'/">'.$tag['title'].'</a>
			</li>';
		}
		$html .= '
		</ul>'."\n";
		return $html;
	}

/**
 * show_article_attachments
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_article_attachments($attachments_array) {
		if(empty($attachments_array)) {
			return false;
		}
		$html = '
		<!-- article attachments -->
		<ul id="article_attachments">
			<li>Attachments:</li>';
		foreach($attachments_array as $key => $att) {
			$html .= '
			<li>
				<a href="'.$att['link'].'">'.$att['title'].'</a>
			</li>';
		}
		$html .= '
		</ul>'."\n";
		return $html;
	}


/**
 * show_article_comments
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function show_article_comments($article_comments) {
		echo '
		<div id="comments_wrapper">
			<h2><a id="comments"></a>Comments</h2>';
		if(empty($article_comments)) {
			echo '<p>no comments</p>';
		} else {
			foreach($article_comments as $comment) {

				$author_style = (!empty($comment['author_id'])) ? ' author_comment' : '' ;
				$reply_style = (!empty($comment['reply_id'])) ? ' reply_comment' : '' ;	
				echo '
			
			<div class="comment'.$author_style.$reply_style.'" id="comment_wrapper_'.$comment['id'].'">
				<a id="comment_'.$comment['id'].'"></a>
				<p class="comment_header">
					<span class="comment_name">'.$comment['poster_name'].'</span>
					<span class="comment_date"> '.date('d M Y',strtotime($comment['date_uploaded'])).'</span>
					<span class="comment_time"> '.date('H:i',strtotime($comment['date_uploaded'])).'</span>
				</p>';
					echo (!empty($comment['title'])) ? '<p class="comment_title">'.$comment['title'].'</p>' : '' ;
					echo '
				<p class="comment_body">'.$comment['body'].'</p>
			</div>';
			}
		}
		echo '</div>';
	}



/**
 * -----------------------------------------------------------------------------
 * COMMENTS SECTION
 * -----------------------------------------------------------------------------
 */



/**
 * prepare_comment_form
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function prepare_comment_form($form_protection, $article_id) {
		if (!session_id()) session_start();
		unset($_SESSION['comment_data']);
		// prepare security data
		$salt = $article_id.substr(WW_WEB_ROOT,7,12);
		$_SESSION['comment_data']['salt'] = $salt;
  		// mathematical protection
		if(!empty($form_protection)) {
			// numbers
			$numbers = array("zero","one","two","three","four","five","six","seven","eight","nine");
			$a = rand(1,9);
			$b = rand(1,9);
			$comment_data['a'] = $a;
			$comment_data['b'] = $b;
			$_SESSION['comment_data']['comment_answer'] = $a+$b;
		} else {
			unset($_SESSION['comment_data']['comment_answer']);
		} 	
		// security session
		$now = date('Ymdh');
		$token_name = md5($article_id.$now.$salt);
		$token_value = md5($salt.$now.$article_id);
		$comment_data['token_name'] = $token_name;
		$comment_data['token_value'] = $token_value;
		$_SESSION['comment_data'][$token_name] = $token_value;
		// prepare data for form prefilling
		$comment_data['comment_body'] = (isset($_POST['comment_body'])) ? $_POST['comment_body'] : '' ;
		$comment_data['poster_name'] = (isset($_POST['poster_name'])) ? $_POST['poster_name'] : '' ;
		$comment_data['poster_link'] = (isset($_POST['poster_link'])) ? $_POST['poster_link'] : '' ;
		return $comment_data;
	}

/**
 * show_comment_form
 * 
 * 
 * 
 * 
 * 
 * 
 */	

	function show_comment_form($config_comments, $article_id, $errors = '') {
		$comment_data = prepare_comment_form($config_comments['form_protection'], $article_id);
		// has any comment data already been posted?
		if(!empty($config_comments['moderate'])) {
			$advisory[] = 'All comments are subject to approval prior to appearing on the site.';
		}
		if(empty($config_comments['allow_html'])) {
			$advisory[] = 'HTML code is NOT allowed and will be stripped out.';
		}
		$adv_text = (!empty($advisory)) ? '<p>'.implode("<br />",$advisory).'</p>' : '' ;
		// errors
		$err_text = '';
		if(!empty($errors)) {
			$err_text = '
			<div id="comment_errors">
				<ul>
					<li>'.implode('</li><li>',$errors).'</li>
				</ul>
			</div>';
		}
		// show form
		echo '
			<h2>Add a comment</h2>
			'.$adv_text.'
			
			<form action="'.current_url().'" method="post" id="comment_form">
			'.$err_text.'
			<div id="reply_text"></div>
			
			<p><label for="comment_body">Your comment:</label>
				<textarea name="comment_body" title="your comment" id="comment_body" cols="32" rows="8">'.$comment_data['comment_body'].'</textarea></p>
			
			<p><label for="poster_name">Your name:</label>
				<input type="text" name="poster_name" title="your name" placeholder="type your name" id="poster_name" size="36" value="'.$comment_data['poster_name'].'"/></p>
			
			<p><label for="poster_link" class="optional">Your website:</label>
				<input type="url" name="poster_link" title="your website" id="poster_link" size="36" value="'.$comment_data['poster_link'].'"/></p>';
		// only prompt for question if session is set
		if(isset($_SESSION['comment_data']['comment_answer'])) {		
			echo '
			<p><label for="comment_answer">Security:</label>
				<input type="number" min="0" max="20" step="1" name="comment_answer" id="comment_answer" size="2"/>
				<span id="comment_question">Please enter the sum of 
				<span>'.$comment_data['a'].' plus '.$comment_data['b'].'</span>
				in digits (e.g \'19\')</span>
			</p>';
		}
		// back to regular form...
		echo '	
			<p>
				<input name="'.$comment_data['token_name'].'" value="'.$comment_data['token_value'].'" type="hidden"/>
				<input name="reply_id" id="reply_id" type="hidden"/>
				<input name="submit_comment" id="submit_comment" value="Submit Comment" type="submit"/>
			</p>

			</form>';
	}
	
/**
 * -----------------------------------------------------------------------------
 * LISTING SECTION
 * -----------------------------------------------------------------------------
 */


/**
 * show_listing
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_listing($listing_array, $title = '', $disqus = '') {
		$html = '
		<div id="listing_wrapper">';
		if(!empty($title)) {
			$html .= '
			<h1>'.$title.'</h1>';
		}
		if(empty($listing_array)) {
			$html .= 
			'<p>No articles found.</p>	
		</div>';
			return $html;
		}				
		foreach($listing_array as $item) {
			if(!empty($disqus)) {
				$comments = '
				<a href="'.$item['link'].'#disqus_thread" title="disqus comments for this article">Comments</a>';	
			} elseif(isset($item['comment_count'])) {
				$comments = (empty($item['comment_count'])) ? 'no comments' : $item['comment_count'].' comments' ;
				$comments = ($item['comment_count'] == 1) ? '1 comment' : $comments ;
				if($item['comment_count'] > 1) {
					$comments = '
					<a href="'.$item['link'].'#comments" title="jump to comments for this article">'.$comments.'</a>';
				}				
			} else {
				$comments = '';
			}
			// derive listing style and body content
			if(isset($item['intro'])) { 
				$listing_style = ' intro_listing';
				$body = show_article_byline($item);
				$body .= show_article_summary($item);
				$body .= '<div class="body">'.$item['intro'].'</div>'; 
			} elseif(isset($item['body'])) { 
				$listing_style = ' full_listing';
				$body = show_article_byline($item);
				$body .= show_article_summary($item);
				$body .= '<div class="body">'.$item['body'].'</div>'; 
			} elseif(isset($item['listing'])) {
				$listing_style = ' summary_listing';
				$body = show_article_summary($item);
				$body .= '<div class="body">'.$item['listing'].'</div>';				
			} elseif(!empty($item['summary'])) {
				$listing_style = ' summary_listing';
				$body = show_article_summary($item);
			}
			// title - in case no link is provided
			if(!empty($item['link'])) {
				$item_title = '<a href="'.$item['link'].'" title="link to full article">'.$item['title'].'</a>';	
			} else {
				$item_title = $item['title'];
			}	
			// output html
			$html .= '
		<!-- listing item -->
			
		<div class="listing'.$listing_style.'">
			<h2>
				'.$item_title.'
			</h2>';
			$html .= $body;
			$html .= '	
			<p class="footer">
				<span class="listing_date">';
					$html .= (isset($item['date_uploaded'])) ? date('j M Y \a\t H:i',strtotime($item['date_uploaded'])) : '' ;
			$html .= '
				</span>
				<span class="listing_category">';
					$html .= (!empty($item['parent_category_url'])) ? '<a href="'.WW_WEB_ROOT.'/'.$item['parent_category_url'].'/" title="see all articles in this category">'.$item['parent_category_title'].'</a> &gt; ' : '' ;
					$html .= (isset($item['category_url'])) ? '<a href="'.WW_WEB_ROOT.'/'.$item['category_url'].'/" title="see all articles in this category">'.$item['category_title'].'</a>' : '' ;
			$html .= '
				</span>
				<span class="listing_comments">'.$comments.'</span>
			</p>
		</div>
			';
		}
		//$html .= insert_page_nav();
		$html .= 
		'</div>';
		return $html;
	}

/**
 * show_listing_nav
 * 
 * 
 * 
 * 
 * 
 * 
 */
 
	function show_listing_nav($total, $per_page) {
		// page params
		$current_page 	= (empty($_GET['page'])) ? '1' : (int)$_GET['page'] ;
		$previous_page	= ($current_page > 1) ? $current_page-1 : 0 ;
		$next_page		= ($current_page < $total) ? $current_page+1 : 0 ;
		// get url and strip page number if needed
		$url = current_url();
		$pattern = "%/page/[0-9]+%";
		$url = preg_replace($pattern,'',$url);
		// build html
		$html = '
		<!-- listing navigation -->
		
		<div id="page_nav" class="listing_page_nav">';
		if(!empty($previous_page)) { 
			$html .= '
				<span id="previous">
					<a href="'.$url.'page/'.$previous_page.'/" title="go to previous page">previous</a>
				</span>'; 
		}
		if(!empty($next_page)) { 
			$html .= '
				<span id="next">
					<a href="'.$url.'page/'.$next_page.'/" title="go to next page">next</a>
				</span>'; 
		}		
		$html .= '
				<ul>';
		for($pp = 1; $pp <= $total; ++$pp) {
			$html .=  '
					<li><a href="'.$url.'page/'.$pp.'/" title="go to page '.$pp.'">page'.$pp.'</a></li>';
		}
		$html .= '
				</ul>';

		$html .= '
		</div>'."\n";
		return $html;
	}

/**
 * format_feeds_list
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function format_feeds_list($feeds = '') {
		$feeds_list = (!empty($feeds)) ? $feeds : get_feeds();
		$listing = array();
		foreach($feeds_list as $section => $details) {
			$feeds = $details['feeds'];
			$intro = '<ul>';
			foreach($feeds as $feed){
				$intro .= '
				<li><a href="'.$feed['url'].'" title="rss feed for '.$feed['title'].'">'.$feed['title'].'</a></li>';
			}
			$intro .= '</ul>';
			$listing[] = array(	'title' => $details['section'],
								'summary' => $details['description'],
								'listing'	=> $intro
								);
		}
		return $listing;
	}

/**
 * -----------------------------------------------------------------------------
 * ASIDE SNIPPETS
 * -----------------------------------------------------------------------------
 */

	
/**
 * build_select_form
 * 
 * 
 * 
 * 
 * 
 * 
 */

	function build_select_form($form_id, $option_array) {
		// don't bother if less than 2 entries
		if(count($option_array) < 2) {
			return false;
		}
		$form = '
			<form action="'.WW_WEB_ROOT.'/" method="post" id="'.$form_id.'">
				<p>
				<select name="select_link" onchange="location.href=this.options[selectedIndex].value">
					<option value="0">select...</option>
				';
			foreach($option_array as $option) {
				$total = (isset($option['total'])) ? ' ('.$option['total'].')' : '' ;
				$form .= '
					<option value="'.$option['link'].'">'.$option['title'].$total.'</option>
				';
				// nested items
				if(isset($option['child'])) {
					foreach($option['child'] as $child) {
						$total = (isset($child['total'])) ? ' ('.$child['total'].')' : '' ;
						$form .= '
							<option value="'.$child['link'].'">-- '.$child['title'].$total.'</option>
						';						
					}
				}
				// end nested items
			}
		$form .= '				
				</select>
				<noscript>
				<div><input type="submit" value="Go" /></div>
				</noscript>
				</p>
			</form>';
		return $form;		
	}

/**
 * show_twitter_feed
 * 
 * 
 * 
 * 
 * 
 */

	function get_twitter_feed($username, $limit = 10) {
		if(empty($username)) {
			return false;
		}
		$url = 'http://twitter.com/statuses/user_timeline/'.$username.'.rss';
		$tweets = import_rss($url, $limit);
		if(empty($tweets)) {
			return false;
		}
		$html = '
		<ul class="twitter_feed">';
		foreach($tweets as $tweet) {
			// format date
			$tweet_date = '<a href="'.$tweet['link'].'">'.date('F d \a\t H:i',strtotime($tweet['date'])).'</a>';
			// get rid of the 'username: ' prepending each tweet
			$tweet_text = str_replace($username.": ", "", $tweet['description']);
			// replace @replies with link to other user's Twitter page
			//$reply_pattern = '/@([A-Za-z0-9_-]+) /';
			//$reply_link = '<a href="http://twitter.com/$1/" target="_blank">@$1</a> ';
			//$tweet_text = preg_replace($reply_pattern, $reply_link, $tweet_text);
			$tweet_text = twitterify($tweet_text);
			// output
			$html .= '
			<li>
				<span class="tweet">'.$tweet_text.'</span>
				<span class="tweet_date">'.$tweet_date.'</span>
			</li>';
		}
		$html .= '
			<li>
				<span class="twitter_link">
				<a href="http://twitter.com/'.$username.'">follow me on Twitter</a>
				</span>
			</li>
		</ul>';
		return $html;
	}

/**
 * twitterify
 * 
 * http://www.snipe.net/2009/09/php-twitter-clickable-links/
 * 
 * 
 * 
 */

	function twitterify($ret) {
		$ret = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $ret);
		$ret = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $ret);
		$ret = preg_replace("/@(\w+)/", "<a href=\"http://www.twitter.com/\\1\" target=\"_blank\">@\\1</a>", $ret);
		$ret = preg_replace("/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\" target=\"_blank\">#\\1</a>", $ret);
		return $ret;
	}
	
/**
 * show_search_form
 * 
 * 
 * 
 * this alternate version of the search form can be 
 * used in cases where .htaccess rewrites aren't working
 * 
 */
 
	function show_search_form($non_rewrite = 0) {
		$search_form = '
			<form action="'.WW_WEB_ROOT.'/search/" method="post" id="search_form">
				<p>
				<label for="search">Enter search term:</label>
				<input type="text" name="search" id="search"/>
				<input type="submit" name="submit_search" value="Go" />
				</p>
			</form>';
		$non_rewrite_search_form = '
			<form action="'.WW_WEB_ROOT.'/ww_view/index.php" method="get" id="search_form">
				<p>
				<label for="search">Enter search term:</label>
				<input type="text" name="search" id="search"/>
				<input type="submit" value="Go" />
				</p>
			</form>';
		if(empty($non_rewrite)) {
			return $search_form;
		}
		return $non_rewrite_search_form;	
	}
 
?>