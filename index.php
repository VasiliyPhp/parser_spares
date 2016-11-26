<?php

require 'phpQuery/phpQuery.php';
require 'helper_functions.php';
const SITE = 'http://euroauto.ru';
set_time_limit(-1);
touch('checker.dd');
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
	//название марки
	$_marka = $cat['title'];
	// поиск моделей
	$models = file_get_contents(SITE . $cat['href']);
	$models = phpQuery::newDocument($models);
	$models = pq('.model-list a');
	foreach($models as $model){
		
		// $img   = pq($model)->find('img')->attr('src');
		//название модели 
		$_model = trim(pq($model)->find('.item-model-info')->text());
		$href  = trim(pq($model)->attr('href'));
		
		if(!$href){
			continue;
		}
		phpQuery::unloadDocuments();
		
		// поиск подмоделей 
		$submodelsDoc = file_get_contents(SITE . $href);
		
		$submodelsDoc = phpQuery::newDocument($submodelsDoc)->find('.model-list a');
		$cars = [];
		$i = 0;
		foreach($submodelsDoc as $subm){
			$subm_href = trim(pq($subm)->attr('href'));
			$_submodel = trim(pq($subm)->text());
		  $img       = trim(pq($subm)->find('img')->attr('src'));
	   	save_img($_marka, $_model, $_submodel, $img);
			// поиск категорий для подмоделей
			$sub_cats = get_cats(SITE . $subm_href);
			phpQuery::unloadDocuments();
			foreach($sub_cats as $sub_cat){
				$_category = $sub_cat['title'];
				$_href = $sub_cat['href'];
				find_subcats(array_map('trim',compact('_marka','_model','_submodel','_category','_href')));
			}
		}
		// j($cars);
		
	}
	
	// echo $models;
	// j($models);
}
//поиск подкатегории в катерии запчасти 
function find_subcats($ar){
	extract($ar);
	$doc = file_get_contents($_href);
	$doc = phpQuery::newDocument($doc);
	$doc = pq('.parts_left .ulplusminus a');
	$cats = [];
	$i = 0;
	foreach($doc as $cat){
		$cats[$i]['title'] = $_subcat = pq($cat)->text();
		$cats[$i]['href'] = $_href2 = pq($cat)->attr('href');
		$ar['_href'] = SITE . $_href2;
		$ar['_subcat'] = $_subcat;
		phpQuery::unloadDocuments();
		find_spares($ar);
		$i++;
		
	}
	// j($cats);
	// exit;
}


function find_spares($ar, $page = 1){
	if(!file_exists('checker.dd')){
		s('Вызвана остановка',1); exit;
	}
	extract($ar);
	if($page>1){
		$_href .= '?page=' . $page;
	}
	s(str_repeat('&nbsp;', ($page-1)*3). "$_marka, $_submodel, $_subcat, page $page");
	$doc = @file_get_contents($_href);
	if(!$doc){
		continue;
	}
	phpQuery::unloadDocuments();
	$doc = phpQuery::newDocument($doc);
	if($pagi = pq('#pagination-block')){
		$cur_page = (int)$pagi->attr('data-currentpage');
		$end_page = (int)$pagi->attr('data-endpage');
		if($cur_page < $end_page){
			find_spares($ar, ++$cur_page);
		}
	}
	$list = pq('.parts-list .parts-item_box');
	foreach($list as $item){
		if( !($label = pq('.label-new',$item)->text()) ){
  		s('Пропускаем б/у запчасть',1);
			continue;
		}
		s('Сохнаняем новую запчасть '. $label . ' ' . $_href);
		$ar['_title'] = pq('a[itemprop=name]',$item)->text();
		$ar['_img']   = pq('.item-img img',$item)->attr('content');
		$ar['_sku']   = trim(str_replace('Ориг. номер:','', pq('.item-line_info:eq(0)',$item)->text()));
		save_spare($ar);
	}
	
	// exit;
}

function save_spare($ar){
	extract(array_map('trim',$ar));
	$csv_path = "csv/$_marka/";
	$img_path = 'spares_img/';
	$img_name = substr( md5( mt_rand(1,1000) . time() . $_sku),0,9 ) . '.jpg';
	file_exists($img_path) || mkdir($img_path,null,1);
	file_exists($csv_path) || mkdir($csv_path,null,1);
	$img_bin = @file_get_contents($_img);
	if($img_bin){
		file_put_contents($img_path.$img_name,$img_bin);
	}else{
		s('нет картинки ' . $_img,1);
		$img_name = 'no image';
	}
	if(!file_exists($csv_path.$_model.'.csv')){
	  $header = ['IE_XML_ID','IE_NAME','IP_PROP9','IP_PROP13','IC_GROUP0','IC_GROUP1','IC_GROUP2','CV_PRICE_1','CV_CURRENCY_1'];
		$fd = fopen($csv_path.$_model.'.csv', 'a');
		fputcsv($fd,$header,';');
	}else{
	  $fd = fopen($csv_path.$_model.'.csv', 'a');
	}
	$data = array_map(function($i){
		return iconv('utf-8','cp1251',$i);
	},[id(), $_title,$_sku,$img_name,$_submodel,$_category,$_subcat,'','RUB']);
	fputcsv($fd,$data,';');
}
function id(){
	$id = 0;
	if(file_exists('iddata')){
		$id = (int)file_get_contents('iddata');
	}
	if($id<240){
		$id = 240;
	}
	$id++;
	file_put_contents('iddata', $id);
	return $id;
}
function get_cats($href){
	//список подкатегорий для выбранной подмодели
	$list = file_get_contents($href);
	$doc = phpQuery::newDocument($list);
	$list = pq('.parts_left a');
	$subc = [];
	$i = 0;
	foreach($list as $item){
		$subc[$i]['title'] = trim(pq('.group', $item)->text());
		$subc[$i]['href'] = SITE . pq($item)->attr('href');
		$i++;
	}
	return $subc;
	j($subc);
	
}

function save_img($marka, $model, $submodel, $img){
	
	$path = "imgs/$marka/$model/$submodel/";
	file_exists($path) || mkdir($path, null, 1);
	file_exists($path . 'auto.jpg') || file_put_contents($path . 'auto.jpg', file_get_contents($img));
	
}