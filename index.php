<?php

$allCats = [
	'Audi',
	'Chery',
	'Citroen',
	'Geely',
	'Hyundai',
	'BMW',
	'Chevrolet',
	'Daewoo',
	'Ford',
	'Honda',
	'Kia',
	'Lifan',
	'Mazda',
	'Mitsubishi',
	'Nissan',
	'Opel',
	'Peugeot',
	'Renault',
	'Skoda',
	'Toyota',
	'VW',
	'Fiat',
	'Infiniti',
	'Volvo',
];
const SITE = 'http://euroauto.ru';

if(isset($_POST['cats'])){
	$needle = $_POST['cats'];
	id($_POST['id']);
	require 'phpQuery/phpQuery.php';
	require 'helper_functions.php';
	set_time_limit(-1);
	touch('checker.dd');
	// remove('cats');
	$cats = get_main_categories();

	foreach($cats as $cat){
		if(!in_array($cat['title'], $needle)){
			s($cat['title'] . ' - нет в списке', 1);
			continue;
		}
		parse($cat);
	}
}else{
	echo "<form method=post target='_blank' >";
	foreach($allCats as $cat ){
		echo '<label><input name="cats[]" type=checkbox value="'.$cat.'" > '.$cat.'</label><br/>';
	}
	echo '<label> начальный id <input name=id  type=number /></label><br/>';
	echo '<input type=submit value=start />';
	echo '</form>';
	echo '<a href="stop.php" >stop</a></br>';
	echo '<a onclick="return confirm(\'Are you shure\');" href="clear.php" >clear</a>';
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
	$submodelsDoc = null;
	$models = file_get_contents(SITE . $cat['href']);
	$models = phpQuery::newDocument($models);
	$models = pq('.model-list a');
	foreach($models as $model){
		
		// $img   = pq($model)->find('img')->attr('src');
		//название модели 
		$_model = trim(pq($model)->find('.item-model-info')->text());
		$_model = str_replace('/','-',$_model);
		$href  = trim(pq($model)->attr('href'));
		if(!$href){
			continue;
		}
		
		// поиск подмоделей 
		$submodelsDoc && $submodelsDoc->unloadDocument();
		$submodelsDoc = file_get_contents(SITE . $href);
		$submodelsDoc = phpQuery::newDocument($submodelsDoc)->find('.model-list a');
		$cars = [];
		$i = 0;
		foreach($submodelsDoc as $subm){
			$_submodel = str_replace('/','-',trim(pq($subm)->text()));
			if(exists($_marka,$_submodel)){
				s("Пропускаем $_marka $_submodel");
				continue;
			}
			$subm_href = trim(pq($subm)->attr('href'));
		  $img       = trim(pq($subm)->find('img')->attr('src'));
	   	save_img($_marka, $_model, $_submodel, $img);
			// поиск категорий для подмоделей
			$sub_cats = get_cats(SITE . $subm_href);
			foreach($sub_cats as $sub_cat){
				$_category = $sub_cat['title'];
				if(exists($_marka,$_submodel,$_category)){
					s("Пропускаем $_marka $_submodel $_category");
					continue;
				}
			  $_href = $sub_cat['href'];
				find_subcats(array_map('trim',compact('_marka','_model','_submodel','_category','_href')));
				save($_marka,$_submodel,$_category);
			}
		  save($_marka,$_submodel);
		}
		// j($cars);
		
	}
	
	// echo $models;
	// j($models);
}
//поиск подкатегории в катерии запчасти 
function find_subcats($ar){
	extract($ar);
	$doc = @file_get_contents($_href);
	if(!$doc){
		return false;
	}
	s("$_marka, $_submodel, $_category");
	$doc = phpQuery::newDocument($doc);
	$list = pq('.parts_left .ulplusminus a');
	$cats = [];
	$i = 0;
	foreach($list as $cat){
		$cats[$i]['title'] = $_subcat = pq($cat)->text();
		$cats[$i]['href'] = $_href2 = pq($cat)->attr('href');
		$ar['_href'] = SITE . $_href2;
		$ar['_subcat'] = $_subcat;
		find_spares($ar);
		$i++;
	}
	$doc->unloadDocument();
	// j($cats);
	// exit;
}


function find_spares($ar, $page = 1){
	if(!file_exists('checker.dd')){
		s('Вызвана остановка',1); exit;
	}
	$found = 0;
	extract($ar);
	if($page>1){
		$_href .= '?page=' . $page;
	}
	$doc = @file_get_contents($_href);
	if(!$doc){
		return ;
	}
	$doc = phpQuery::newDocument($doc);
	$list = pq('.parts-list .parts-item_box');
	foreach($list as $item){
		if( !($label = pq('.label-new',$item)->text()) ){
  		// s('Пропускаем б/у запчасть',1);
			continue;
		}
		$found++;
		// s('Сохнаняем новую запчасть '. $label . ' ' . $_href);
		$ar['_title'] = pq('a[itemprop=name]',$item)->text();
		$images = find_images(pq('.item-img img',$item)->attr('content'));
		$ar['_sku']   = trim(str_replace('Ориг. номер:','', pq('.item-line_info:eq(0)',$item)->text()));
		$id = null;
		foreach($images as $image){
			$ar['_id'] = $id;
			$ar['_img'] = $image;
		  $id = save_spare($ar);
		}
	}
	if($pagi = pq('#pagination-block')){
		$cur_page = (int)$pagi->attr('data-currentpage');
		$end_page = (int)$pagi->attr('data-endpage');
		if($cur_page < $end_page){
    	$doc->unloadDocument();
			find_spares($ar, ++$cur_page);
		}
	}
	$doc->unloadDocument();
	if($found){
		// s("Найдено $found запчастей");
	}
	// exit;
}
function exists(){
	$s = iconv('utf-8','cp1251',translit(implode('-',func_get_args())));
	return file_exists("check_dir/$s");
}
function save(){
	$s = iconv('utf-8','cp1251',translit(implode('-',func_get_args())));
	file_exists('check_dir') || mkdir('check_dir');
	touch("check_dir/$s");
}
function save_spare($ar){
	extract(array_map('trim',$ar));
	$csv_path = "csv/$_marka/";
	$img_path = 'spares_img/';
	file_exists($img_path) || mkdir($img_path,null,1);
	file_exists($csv_path) || mkdir($csv_path,null,1);
	if($_img){
		$img_name = md5($_img) . '.jpg';
		$img_bin = @file_get_contents($_img);
		if($img_bin){
			file_put_contents($img_path.$img_name,$img_bin);
		}else{
			$img_name = '';
		}
	}else{
		$img_name = '';
	}
	if(!file_exists($csv_path.$_model.'.csv')){
	  $header = ['IE_XML_ID','IE_NAME','IE_CODE','IP_PROP9','IP_PROP13','IC_GROUP0','IC_GROUP1','IC_GROUP2'];
		$fd = fopen($csv_path.$_model.'.csv', 'a');
		fputcsv($fd,$header,';');
	}else{
	  $fd = fopen($csv_path.$_model.'.csv', 'a');
	}
	$_code = translit($_title);
	$id = $_id? : id();
	$data = array_map(function($i){
		return iconv('utf-8','cp1251',$i);
	},[$id, $_title, $_code, $_sku, $img_name, 'Запчасти '. $_marka, 'Запчасти '. $_marka . ' ' . $_model,  $_submodel]);
	fputcsv($fd,$data,';');
	return $id;
}
function id($id = null){
	if($id){
		file_put_contents('iddata', $id);
		return $id;
	}
	$id = 0;
	if( file_exists('iddata') ){
		$id = (int)file_get_contents('iddata');
	}
	if( $id < 235 ) {
		$id = 235;
	}
	$id++;
	file_put_contents('iddata', $id);
	return $id;
}
function get_cats($href){
	//список подкатегорий для выбранной подмодели
	$list = @file_get_contents($href);
	if(!$list){
		return [];
	}
	$doc = phpQuery::newDocument($list);
	$list = pq('.parts_left a');
	$subc = [];
	$i = 0;
	foreach($list as $item){
		$subc[$i]['title'] = trim(pq('.group', $item)->text());
		$subc[$i]['href'] = SITE . pq($item)->attr('href');
		$i++;
	}
	$doc->unloadDocument();
	return $subc;
	j($subc);
	
}
function translit($s) {
  $s = (string) $s; // преобразуем в строковое значение
  $s = strip_tags($s); // убираем HTML-теги
  $s = trim($s); // убираем пробелы в начале и конце строки
  $s = preg_replace("/\s+/", ' ', $s); // удаляем повторяющие пробелы
  $s = function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); // переводим строку в нижний регистр (иногда надо задать локаль)
  $s = strtr($s, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
  $s = preg_replace("/[^A-z\d]/i", "_", $s); // заменяем все двойные подчеркивания на одно
  $s = preg_replace("/_+/i", "_", $s); // заменяем все двойные подчеркивания на одно
  // $s = preg_replace("/^_(.*)_$/i", "$1", $s); // заменяем подчеркивания в конце и в начале слова на ''
  return $s; // возвращаем результат
}
function save_img($marka, $model, $submodel, $img){
	$submodel = str_replace(['<','>'],['','',],$submodel);
	$path = "imgs/$marka/$model/$submodel/";
	file_exists($path) || mkdir($path, null, 1);
	file_exists($path . 'auto.jpg') || file_put_contents($path . 'auto.jpg', file_get_contents($img));
}
function find_images($url){
	if(strpos($url, '/photo/parts/new') === false){
		return [$url];
	}
	$rs =  @file_get_contents(preg_replace('~/\d+\.jpg$~','',$url));
	if($rs){
		$items = json_decode($rs)->items;
		if(!count($items)){
			return [null];
		}
		return array_map(function($i){
			return SITE . '/' . $i->uri;
		}, $items);
	}
	return [null];
	// exit();
  $s = preg_replace("/[^0-9a-z]/i", "_", $s); // очищаем строку от недопустимых символов
}