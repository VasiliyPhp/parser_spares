<?php

require 'phpQuery/phpQuery.php';
require 'helper_functions.php';
const SITE = 'http://euroauto.ru';

// remove('cats');
$cats = get_main_categories();

foreach($cats as $cat){
	parse($cat);
}

function get_main_categories(){
	$tmp = file_get_contents(SITE);
	$document = phpQuery::newDocument($tmp);
	$cats = get('cats');
	if(!$cats){
		$i = 0;
		foreach(pq('#lightCars a') as $item){
			
			$cats[$i]['href']  = pq($item)->attr('href');
			$cats[$i]['title'] = pq($item)->text();
			$i++;
		}
		set('cats', $cats);
	}
	return ($cats) ? : [];
}

function parse($cat){
	$marka = $cat['title'];
	$models = file_get_contents(SITE . $cat['href']);
	$allModels = [];
	j($models);
}