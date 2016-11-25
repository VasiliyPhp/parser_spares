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
	$cats = get('cats');
	if(!$cats){
		$tmp = file_get_contents(SITE);
		$document = phpQuery::newDocument($tmp);
		$i = 0;
		foreach(pq('#lightCars a') as $item){
			
			$cats[$i]['href']  = pq($item)->attr('href');
			$cats[$i]['title'] = pq($item)->text();
			$i++;
		}
		set('cats', $cats);
	}
	phpQuery::unloadDocuments();
	return ($cats) ? : [];
}

function parse($cat){
	$marka = $cat['title'];
	$models = file_get_contents(SITE . $cat['href']);
	$models = phpQuery::newDocument($models);
	$models = pq('.model-list a');
	foreach($models as $model){
		
		$img   = pq($model)->find('img')->attr('src');
		$_model = trim(pq($model)->find('.item-model-info')->text());
		$href  = trim(pq($model)->attr('href'));
		
		save_img($marka, $_model, $img);
		if(!$href){
			continue;
		}
		phpQuery::unloadDocuments();
		
		$submodelsDoc = file_get_contents(SITE . $href);
		
		$submodelsDoc = phpQuery::newDocument($submodelsDoc)->find('.model-list a');
		echo $submodelsDoc; exit;
		
		
		
	}
	
	echo $models;
	j($models);
}


function save_img($marka, $model, $img){
	
	$path = "imgs/$marka/$model/";
	file_exists($path) || mkdir($path, null, 1);
	file_exists($path . 'auto.jpg')|| file_put_contents($path . 'auto.jpg', file_get_contents($img));
	
}