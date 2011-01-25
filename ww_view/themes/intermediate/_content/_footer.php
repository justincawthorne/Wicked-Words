<?php
/* 
	this example demonstrates inserting some custom text and a date variable into the footer
	
	the built in 'insert_footer' function accepts only one paramter: the content for the footer
*/

// set values

$footer_content = '<p>Wicked Words - this is the intermediate theme<br />today\'s date is: '.date('d M Y').'</p>';

// use built-in function to wrap around the html markup and output

echo insert_footer($footer_content);
?>