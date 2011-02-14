<?php 

	$date 	= date('d M Y'); 
	$title 	= "Today's Date";
	$content = "<p>the date today is ".$date.'</p>';
	
	
	echo build_snippet($title, $content);
?>