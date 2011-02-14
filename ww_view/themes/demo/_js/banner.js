jQuery(document).ready(function(){

	var page_el = $('#main_content')
	var new_el = '<div id="banner" style="width:640px;height:60px;background-color:#eee;margin-bottom: 8px;">'
	new_el = new_el + '<h4>our new banner!</h4>'
	new_el = new_el + '<p>generated using javascript</p></div>';
	page_el.prepend(new_el)
	
});
