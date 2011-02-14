<?php
/*
	refer to ww_view/_content/aside.php to see all default aside snippets 
*/

	include_once(WW_ROOT.'/ww_view/_content/_aside.php');
	
	$aside_content['upper'][] = $aside_snippet['aside_test'];
	
	echo insert_aside($aside_content);
	
/*
	NOTES
	
	the basic theme includes one additional aside: _asides/aside_test.php this is automatically imported by
	the default aside.php page
	
	line 6 > in this example we call the default asides.php file - this gives us access to all the default
	asides and means we can maintain the default $aside_content structure
	
	line 8 > here we add our imported aside snippet to the upper array
	
	line 10 > finally we output the content
*/
	
// to define additional snippets	

	/*
		$aside_snippet['more_tags_list'] = build_snippet('MORE TAGS',$tags_list);
		
			'more tags-list'	this is the unique name for your snippet
			'MORE TAGS'			this is the snippet title that will appear on the page
			$tags_list			this is the snippet data - can be html or an array (which will be rendered as a list)
		
			you don't have to use the build snippet function - this just provides a wrapper
			for your snippet and converts arrays to lists - you can manually code the html
			if you prefer
	*/

// to add your new snippet to an existing aside_content array 

	/*
		$aside_content['upper'][] = $aside_snippet['more_tags_list'];
		
			here we're adding the snippet we created above to the aside_upper div
	*/
	
// to recreate the aside_content array (i.e. ensure default tags don't appear)

	/*
		$aside_content['upper'] = array ();  
								
			we reset the $aside_content['upper'] array first
		
		$aside_content['upper'][] = $aside_snippet['more_tags_list'];
		
			then add the snippet to the array as before
	*/
?>