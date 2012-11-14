<?php
// add compression since mod_deflate doesn't appear to like this file

	ob_start("ob_gzhandler");
	header("content-type: text/css; charset: UTF-8");
	$offset = 60 * 60 * 1; // one hour
	$offset = 0; // one hour
	header("Cache-Control: max-age=" . $offset . ", must-revalidate");
	header("Expires: " . gmdate ("D, d M Y H:i:s", time() + $offset) . " GMT");

// required files

	require_once('../ww_config/model_functions.php');
	require_once('../ww_config/controller_functions.php');
	
// start caching

if(!empty($config['cache']['caching_on'])) {
	$cache_css = start_caching();
}

// get theme via GET parameter

	$theme = (isset($_GET['theme'])) ? '/'.$_GET['theme'] : '/default' ;
	$theme_folder = "/ww_view/themes".$theme;
	
	// revert to default theme if selected theme is not found
	if (!is_dir(WW_ROOT.$theme_folder)) {
		$theme_folder = '/ww_view/themes/default';
	}

// include styles

	$css = '';

	// structure
	
	if (file_exists(WW_ROOT.$theme_folder.'/structure.css')) {
		$css .= '/***************************'."\n";
		$css .= "\t".'structure'."\n";
		$css .= '****************************/'."\n\n";
		$css .= file_get_contents(WW_ROOT.$theme_folder.'/structure.css');
	}
	
	// primary styles
	
	if (file_exists(WW_ROOT.$theme_folder.'/style.css')) {
		$css .= "\n\n".'/***************************'."\n";
		$css .= "\t".'base styles'."\n";
		$css .= '****************************/'."\n\n";
		$css .= file_get_contents(WW_ROOT.$theme_folder.'/style.css');
	}
	
	// page name specific styles
	
	if ( (isset($_GET['page_name'])) && (file_exists(WW_ROOT.$theme_folder.'/'.$_GET['page_name'].'.css')) ) {
		$css .= "\n\n".'/***************************'."\n";
		$css .= "\t".$_GET['page_name'].' styles'."\n";
		$css .= '****************************/'."\n\n";
		$css .= file_get_contents(WW_ROOT.$theme_folder.'/'.$_GET['page_name'].'.css');
	}
	
	// category specific styles
	
	if( (isset($_GET['category_url'])) && (file_exists(WW_ROOT.$theme_folder.'/'.$_GET['category_url'].'.css')) ) {
		$css .= "\n\n".'/***************************'."\n";
		$css .= "\t".$_GET['category_url'].' styles'."\n";
		$css .= '****************************/'."\n\n";
		$css .= file_get_contents(WW_ROOT.$theme_folder.'/'.$_GET['category_url'].'.css');
	}
    
    // media queries - tablet, portrait, mobile

	// tablet styles
	
	if (file_exists(WW_ROOT.$theme_folder.'/tablet.css')) {
		$css .= "\n\n".'@media only screen and (max-width : 980px) {';
        $css .= "\n\n".'/***************************'."\n";
		$css .= "\t".'tablet styles'."\n";
		$css .= '****************************/'."\n\n";
		$css .= file_get_contents(WW_ROOT.$theme_folder.'/tablet.css');
        $css .= "\n\n".'}';
	}

	// tablet portrait styles
	
	if (file_exists(WW_ROOT.$theme_folder.'/portrait.css')) {
		$css .= "\n\n".'@media only screen and (max-width : 768px) {';
        $css .= "\n\n".'/***************************'."\n";
		$css .= "\t".'portrait styles'."\n";
		$css .= '****************************/'."\n\n";
		$css .= file_get_contents(WW_ROOT.$theme_folder.'/portrait.css');
        $css .= "\n\n".'}';
	}
    
   	// mobile styles
	
	if (file_exists(WW_ROOT.$theme_folder.'/mobile.css')) {
		$css .= "\n\n".'@media only screen and (max-width : 480px) {';
        $css .= "\n\n".'/***************************'."\n";
		$css .= "\t".'mobile styles'."\n";
		$css .= '****************************/'."\n\n";
		$css .= file_get_contents(WW_ROOT.$theme_folder.'/mobile.css');
        $css .= "\n\n".'}';
	}	
	echo $css;
	
if(!empty($config['cache']['caching_on'])) {
	end_caching($cache_css);
}
?>