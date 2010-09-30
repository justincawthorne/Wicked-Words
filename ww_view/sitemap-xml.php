<?php
header('Content-type: text/xml'); 
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" 
xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" 
xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n";

// get site data and connections
include_once('../ww_config/model_functions.php');
include_once('../ww_config/controller_functions.php');
include_once('../ww_config/combined_functions.php');

	$layout = get_settings('layout');
	$url_style = $layout['layout']['url_style'];
	
// get all articles

	$articles_xml_list = get_articles_basic($url_style,'','date_uploaded DESC');

	if(empty($articles_xml_list)) {
		echo "</urlset>";
		exit();
	}
	
// set sitemap header
	$site_link = constant('WW_WEB_ROOT');
	$most_recent_ts = strtotime($articles_xml_list[0]['date_uploaded']);
	$most_recent = date('Y-m-d',$most_recent_ts);
	
echo "
   <url>
      <loc>".WW_WEB_ROOT."</loc>
      <lastmod>".$most_recent."</lastmod>
      <changefreq>weekly</changefreq>
      <priority>0.8</priority>
   </url>";

foreach($articles_xml_list as $article) {

	echo "
	<url>
		<loc>".$article['link']."</loc>
		<lastmod>".date('Y-m-d',strtotime($article['date_uploaded']))."</lastmod>
		<priority>0.8</priority>
		<changefreq>weekly</changefreq>
	</url>";
} 

// add category index

	$cats_xml_list = get_categories_basic();
	foreach($cats_xml_list as $cat_xml) { 
	echo "
		<url>
			<loc>".$cat_xml['link']."</loc>
			<priority>0.3</priority>
			<changefreq>monthly</changefreq>
		</url>";
	} 

// add tag index

	$tags_xml_list = get_tags();
	foreach($tags_xml_list as $tag_xml) { 
		echo "
		<url>
			<loc>".$tag_xml['link']."</loc>
			<priority>0.3</priority>
			<changefreq>monthly</changefreq>
		</url>";
	} 

// add author index

	$authors_xml_list = get_authors();
	foreach($authors_xml_list as $author_xml) { 
	echo "
		<url>
			<loc>".$author_xml['link']."</loc>
			<priority>0.3</priority>
			<changefreq>monthly</changefreq>
		</url>";
	} 
// end sitemap
echo "</urlset>";
?>