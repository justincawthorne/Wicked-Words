<?php

$custom_list = array();
$child_list = array();

$child_list[] = array('title' => 'child item','link' => '#');

$custom_list[] = array('title' => 'first item','link' => '#');
$custom_list[] = array('title' => 'second item','link' => '#', 'total' => '2');
$custom_list[] = array('title' => 'third item','link' => '#', 'child' => $child_list);

echo build_snippet('List Test',$custom_list);

?>