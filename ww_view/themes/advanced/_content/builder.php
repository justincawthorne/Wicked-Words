<?php
	
	$config['site']['doctype'] = 'html5';
	
	$head_content = '';
	show_head($head_content, $config);

?>
<body>

	<div id="page_wrapper">
	

	
	<div id="content_wrapper">
	
		<?php echo insert_header($config['site']['title'],$config['site']['subtitle']); ?> 	
		<div id="aside">the aside</div>		
		<?php
		echo insert_main_content($body_content['main']);
		?>
		

		
	</div>
	
	</div>
<div id="footer">this is the footer</div>	
</body>
</html>