<?php
// initialise arrays

include_once(WW_ROOT.'/ww_view/_content/_aside.php');
		
// get the content for our new snippet

$news = '
<script src="http://www.gmodules.com/ig/ifr?url=http://shazingo.com/lig/lg/142002.xml&amp;up_extrafeed=http%3A%2F%2Fnewsrss.bbc.co.uk%2Frss%2Fnewsonline_uk_edition%2Fhealth%2Frss.xml&amp;up_extratitle=Health&amp;up_subject=BBC%20News&amp;up_entries=4&amp;up_summaries=100&amp;up_selectedTab=&amp;synd=open&amp;w=200&amp;h=360&amp;title=news&amp;border=%23ffffff%7C3px%2C1px+solid+%23999999&amp;output=js"></script>';

// use the build_snippet() function to build the actual snippet
	
	$aside_snippet['news'] = build_snippet('BBC News',$news);
	
	
// just to be difficult we'll put this at the top of the aside inner section
	
	// first we capture the existing $aside_content['inner'] array into a variable
	
	$inner_asides = $aside_content['inner'];
	
	// then we initialize (reset) the array
	
	$aside_content['inner'] = array();
	
	// add in our new snippet first
	
	$aside_content['inner'] = array (
							$aside_snippet['news']
							);
							
	// then put the rest of the entries back in
	
	foreach($inner_asides as $inner) {
		$aside_content['inner'][] = $inner;
	}

	
	// clear our variables just in case
	
	unset($inner);
	unset($inner_asides);
	
// finally output the content
	
	echo insert_aside($aside_content);
?>