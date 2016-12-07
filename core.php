<?php

// поиск всех марок авто
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

// поиск подмоделей
function parse($cat){
	//название марки
	$_marka = $cat['title'];
	// поиск моделей
	$submodelsDoc = null;
	$models = file_get_contents(SITE . $cat['href']);
	$models = phpQuery::newDocument($models);
	$models = pq('.model-list a');
	
	$hrefs = [];
	$mcurl = new Curl\MultiCurl;
	$mcurl->setConcurrency(100);
	$mcurl->setConnectTimeout(2);
	$mcurl->success(function($instance) use (&$hrefs, $_marka){
		$doc = $instance->response;
		$submodelsDoc = phpQuery::newDocument($doc)->find('.model-list a');
		foreach($submodelsDoc as $subm){
			$_submodel = str_replace('/','-',trim(pq($subm)->text()));
			if(exists($_marka,$_submodel)){
				s("Пропускаем $_marka $_submodel");
				continue;
			}
			$subm_href = trim(pq($subm)->attr('href'));
			$img       = trim(pq($subm)->find('img')->attr('src'));
			// save_img($subm_href,$img);
			$hrefs[] = [
				'subm_href' => $subm_href,
				'img' => [str_replace('/auto/cars/','',$subm_href), $img],
			];
			// s($subm_href);
		}
		$submodelsDoc->unloadDocument();
	});
	foreach($models as $model){
		
		//название модели 
		$_model = trim(pq($model)->find('.item-model-info')->text());
		$_model = str_replace('/','-',$_model);
		$href  = trim(pq($model)->attr('href'));
		if(!$href){
			continue;
		}
		$mcurl->addGet(SITE . $href);
	}
	$mcurl->start();
	
	// проходимся по каждой собранной ссылке подмодели 
	// собираем все картинки поделей
	foreach($hrefs as $__img_src){
		file_exists('imgs/' . $__img_src['img'][0]) || mkdir('imgs/' . $__img_src['img'][0],null,1);
		$mcurl->addDownload($__img_src['img'][1],'imgs/' . $__img_src['img'][0] . 'auto.jpg');
	}
	$mcurl->start();
	
	// проходимся по каждой собранной ссылке подмодели 
	// собираем основные категории для каждой подмодели
	$catsList = [];
	$mcurl->success(function ($instance) use (&$catsList){
		
		$doc = $instance->response;
		
		$subcatsDoc = phpQuery::newDocument($doc)->find('.model-list a');
		$list = pq('.parts_left a');
		// echo $list;exit;
		foreach($list as $item){
			array_push($catsList, SITE . pq($item)->attr('href'));
		}
		// j($catsList);
		$subcatsDoc->unloadDocument();
		
	});
	foreach($hrefs as $__subm_href){
		
		$href = $__subm_href['subm_href'];
		
		$mcurl->addGet(SITE . $href);
		
		// foreach($sub_cats as $sub_cat){
			// $_category = $sub_cat['title'];
			// if(exists($_marka,$_submodel,$_category)){
				// s("Пропускаем $_marka $_submodel $_category");
				// continue;
			// }
		  // $_href = $sub_cat['href'];
		// find_subcats(array_map('trim',compact('_marka','_model','_submodel','_category','_href')));
			// save($_marka,$_submodel,$_category);
		// }
	 // save($_marka,$_submodel);
	}
	
	$mcurl->start();
	$mcurl->close();
	find_subcats($catsList);
	
	
}
//поиск подкатегории в катерии запчасти 
function find_subcats($cats){
	if(!file_exists('checker.dd')){
		s('Вызвана остановка',1); exit;
	}
	foreach($cats as $cat){
		$doc = @file_get_contents($cat);
		if(!$doc){
			continue;
		}
		$doc = phpQuery::newDocument($doc);
		$list = pq('.parts_left .ulplusminus a');
		$subcats_hrefs = [];
		foreach($list as $subcats){
			// $a_href = [];
			// $cats[$i]['title'] = $_subcat = pq($cat)->text();
			// $cats[$i]['href'] = $_href2 = pq($cat)->attr('href');
			$subcats_hrefs[] = SITE . pq($subcats)->attr('href');
			// $ar['_href'] = SITE . $_href2;
			// $ar['_subcat'] = $_subcat;
			// find_spare_links($ar, &$a_href);
		}
		$doc->unloadDocument();
		foreach($subcats_hrefs as $subcats_href){
			$links = [];
			find_links($subcats_href,$links);
			// j($links);
			find_spares($links);
		}
	}
	return ;
	
}
function find_spares($links){
	
	$mcurl = new Curl\MultiCurl;
	$mcurl->setConcurrency(100);
	$mcurl->setConnectTimeout(2);
	
	foreach($links as $link){
		$mcurl->addGet($link);
	}
	$mcurl->success(function($instance){
		echo '<pre>';
		echo $instance->url;
		echo htmlspecialchars($instance->response); die;
		echo $doc = phpQuery::newDocument($instance->response);die;
		echo '<br>' ,$oem = pq('#orignr')->attr('value');
		echo pq('.breadcrumb');
		echo '<br>' ,$oem = pq('.breadcrumb li:eq(2)')->text(); 
		echo '<br>' ,$oem = pq('.breadcrumb li:eq(3)')->text(); 
		echo '<br>' ,$oem = pq('.breadcrumb li:eq(4)')->text(); 
		echo '<br>' ,$oem = pq('.breadcrumb li:eq(5)')->text(); 
		echo '<br>' ,$oem = pq('.breadcrumb li:eq(6)')->text(); die;
		echo $oem = pq('#theContent>.row');die;
		
		echo $doc;
		$doc->unloadDocument();
	});
	
	$mcurl->start();
	$mcurl->close();
	
}
function find_links($_href, &$links, $page = 1){
	if(!file_exists('checker.dd')){
		s('Вызвана остановка',1); exit;
	}
	
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
		// if( !($label = pq('.label-new',$item)->text()) ){
  		// s('Пропускаем б/у запчасть',1);
			// continue;
		// }
	
		$links[] = SITE . pq('.boxheader a',$item)->attr('href');
		// $ar['_title'] = pq('a[itemprop=name]',$item)->text();
		// $images = find_images(pq('.item-img img',$item)->attr('content'));
		// $ar['_sku']   = trim(str_replace('Ориг. номер:','', pq('.item-line_info:eq(0)',$item)->text()));
		// $id = null;
		// foreach($images as $image){
			// $ar['_id'] = $id;
			// $ar['_img'] = $image;
		  // $id = save_spare($ar);
		// }
	}
	if($pagi = pq('#pagination-block')){
		$cur_page = (int)$pagi->attr('data-currentpage');
		$end_page = (int)$pagi->attr('data-endpage');
		if($cur_page < $end_page){
    	$doc->unloadDocument();
			find_links($_href, $links, ++$cur_page);
		}
	}else{
		$doc->unloadDocument();
	}
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
	$fp = fopen('iddata','a');
	if (flock($fp, LOCK_EX)) { // выполняем эксклюзивную блокировку
		$id = fread($fp,1024);
		ftruncate($fp, 0); // очищаем файл
		if( $id < 235 ) {
			$id = 235;
		}
		$id++;
		fwrite($fp, $id);
		fflush($fp);        // очищаем вывод перед отменой блокировки
		flock($fp, LOCK_UN); // отпираем файл
	} else {
		die('не удалось открыть файлс ид'); 
	}
	return $id;
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
function save_img($href, $img){
	$path = str_replace('/auto/cars/','',$href);
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