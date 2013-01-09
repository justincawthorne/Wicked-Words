<?php
echo '
<script type="text/javascript">
	tinyMCE.init({
		// General options
		mode : "exact",
		elements : "body,author_bio",
		height : "620",
		width : "532",
		cleanup: true,
		content_css : "'.WW_REAL_WEB_ROOT.'/ww_edit/themes/'.$theme.'/tinymce.css",
		extended_valid_elements : "small,iframe[src|title|width|height|allowfullscreen|frameborder]",
		
		// theme and button settings
		theme : "advanced",
		theme_advanced_blockformats : "h2,h3,h4,h5,h6,pre",
		theme_advanced_fonts : "Arial=arial,helvetica,sans-serif;Courier New=courier new,courier,monospace;Times New Roman=times new roman, times, serif;Verdana=verdana, geneva, sans-serif",
		theme_advanced_styles : "Left pullquote=pullquote left;Right pullquote=pullquote right;Image left=left;Image right=right;Image center=center;Code=code",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_statusbar_location : "bottom",
		plugins : "pagebreak,endintro,media,paste,autosave,print,safari,searchreplace",
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,bullist,numlist,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,fontselect,formatselect",
		theme_advanced_buttons2 : "pastetext,code,|,link,unlink,image,media,|,outdent,indent,blockquote,sub,sup,|,forecolor,backcolor,|,undo,redo,|,search,replace,|,pagebreak,endintro,print",
		theme_advanced_buttons3 : "",
		
		// the following lines will convert ensure absolute paths are maintained
		// some local paths are rewritten to relative ones during the save process
		// this ensures the site can be migrated to a new url if needed
		convert_urls : false,
		relative_urls : false,
		remove_script_host : false
		// need to add ww_files path otherwise url gets messed up
		// document_base_url : "'.WW_REAL_WEB_ROOT.'/ww_files/" 
	});
</script>';
?>