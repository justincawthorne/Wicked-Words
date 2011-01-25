<?php
/* 
	this example uses the default values as entered in admin alng with the built-in 'insert_header'
	function to build the header
	
	clearly, with the exception of 'intermediate theme' appended to the subtitle, this example has the same 
	result as using the default header, but it illustrates the values that can be set manually if required
	
	for instance - you may wish to include a date variable, which wouldn't be possible via the settings pages
*/

// custom nav array - this will override anything in the Links > Menu

	$link = array();
	$nav_links['site_menu'] = array (
	
		$link[] = array('title' => 'Custom Header Nav:','link' => '#'),
		$link[] = array('title' => 'Facebook','link' => 'http://facebook.com'),
		$link[] = array('title' => 'Google','link' => 'http://google.com')
	
	);

// set values

	$title 		= $config['site']['title'];
	$subtitle 	= $config['site']['subtitle'].' - intermediate theme';
	$panel 		= $config['site']['header_panel_html'];
	$nav		= insert_nav('header',$nav_links);

// use built-in function to wrap around the html markup and output

	echo insert_header($title, $subtitle, $panel, $nav)
?>