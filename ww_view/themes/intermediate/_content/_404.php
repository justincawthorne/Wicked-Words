<?php
/*
	this is an example of a custom 404 page
	
	for the intermediate theme this works in conjunction with the 404.css stylesheet
*/

// 'hide' the header, footer and aside by setting them to display empty divs

	$body_content['header'] = '<div></div>';
	$body_content['aside'] = '<div></div>';
	$body_content['footer'] = '<div></div>';

// now output some basic content

	echo '
	<h1>404!</h1>
	<p>This is a custom 404 error page for the intermediate theme</p>';
?>