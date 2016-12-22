<?php
$allCats = [
	'Audi',
	//'Chery',
	'Citroen',
	//'Geely',
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
	//'Infiniti',
	//'Volvo',
];
$not_necessary = [
	'Daewoo Tico',
	'Daewoo Rezzo',
	'Citroen AX',
	'Citroen BX',
	'Citroen C15',
	'Citroen C25',
	'Citroen C35',
	'Citroen C8',
	'Citroen DS4',
	'Citroen Evasion',
	'Citroen DS3',
	'Citroen C6',
	'Citroen DS5',
	'Audi 80/90',
	'Audi 50',
	'Audi V8',
	'Audi R8',
	'Audi TT',
	'Chevrolet Blazer',
	'Chevrolet Caprice',
	'Chevrolet Camaro',
	'Chevrolet Lumina',
	'Chevrolet Metro',
	'Chevrolet Rezzo',
	'Chevrolet Evanda',
	'Chevrolet Malibu',
	'Chevrolet Silverado',
	'Chevrolet Tahoe',
	'Chevrolet Tracker',
	'BMW Z3',
	'BMW Z4',
	'BMW 2-серия F45/F46 Active Tourer',
	'BMW 4-Series',
	'BMW 6-Series',
	'BMW 8-Series',
	'BMW GT',
	'Chery Boo',
	'Chery CrossEastar',
	'Chery Indis',
	'Chery Kimo',
	'Chery M11',
];
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
	global $not_necessary, $necessary;
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
			$hrefs[SITE . $subm_href] = [
				'marka' => $_marka,
				'subm_href' => $subm_href,
				'submodel' => $_submodel,
				'img' => [str_replace('/auto/cars/','',$subm_href), $img],
			];
			// s($subm_href);
		}
		$submodelsDoc->unloadDocument();
	});
	foreach($models as $model){
		//название модели 
		$_model = trim(pq($model)->find('.item-model-info')->text());
		if(!in_array("$_marka $_model", $necessary)){
			s("$_marka $_model - не нужно парсить", 1);
			continue;
		}
		$_model = str_replace('/','-',$_model);
		$href  = trim(pq($model)->attr('href'));
		if(!$href){
			continue;
		}
		$mcurl->addGet(SITE . $href);
	}
	$mcurl->start();

	
	$mcurl = new Curl\MultiCurl;
	$mcurl->setConcurrency(100);
	$mcurl->setConnectTimeout(2);
	// проходимся по каждой собранной ссылке подмодели 
	// собираем все картинки моделей
	foreach($hrefs as $__img_src){
		if(file_exists('imgs/' . $__img_src['img'][0] . 'auto.jpg')){
			continue;
		}
		file_exists('imgs/' . $__img_src['img'][0]) || mkdir('imgs/' . $__img_src['img'][0],777,1);
		$mcurl->addDownload($__img_src['img'][1],'imgs/' . $__img_src['img'][0] . 'auto.jpg');
	}
	$mcurl->start();

	$mcurl = new Curl\MultiCurl;
	$mcurl->setConcurrency(100);
	$mcurl->setConnectTimeout(2);
	
	// проходимся по каждой собранной ссылке подмодели 
	// собираем основные категории для каждой подмодели
	$catsList = [];
	$mcurl->success(function ($instance) use (&$catsList, $hrefs){
		
		$doc = $instance->response;
		$submodel = $hrefs[$instance->url]['submodel'];
		$marka = $hrefs[$instance->url]['marka'];
		$subcatsDoc = phpQuery::newDocument($doc)->find('.model-list a');
		$list = pq('.parts_left a');
		// echo $list;exit;
		foreach($list as $item){
			array_push($catsList, ['marka'=>$marka, 'submodel'=>$submodel, 'category'=>pq($item)->text(), 'href' => SITE . pq($item)->attr('href')]);
		}
		$subcatsDoc->unloadDocument();
		
	});
	foreach($hrefs as $__subm_href){
		
		$href = $__subm_href['subm_href'];
		
		$mcurl->addGet(SITE . $href);
		
	}
	
	$mcurl->start();
	$mcurl->close();
	return $catsList;
}
//поиск подкатегории в катерии запчасти 
function find_subcats($cats){
	if(!file_exists('checker.dd')){
		s('Вызвана остановка',1); exit;
	}
	$c_i = 0;
	foreach($cats as $cat){
		$c_i++;
		if(exists($cat['href'], 'cat')){
			s('повтор ' . $cat['href']);
			continue;
		}
		s("Поиск подкатегории в " . implode(' '  , $cat));
		if(!file_exists('checker.dd')){
			s('Вызвана остановка',1); exit;
		}
		$_marka = $cat['marka'];
		$_submodel = $cat['submodel'];
		$_category = $cat['category'];
		$doc = @file_get_contents($cat['href']);
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
			$subcats_hrefs[] = ['marka'=>$_marka,'submodel'=>$_submodel,'category'=>$_category, '_href'=>SITE . pq($subcats)->attr('href')];
			// $ar['_href'] = SITE . $_href2;
			// $ar['_subcat'] = $_subcat;
			// find_spare_links($ar, &$a_href);
		}
		$doc->unloadDocument();
		unset($doc, $list, $subcats);
		$i = 0;
		$c = count($subcats_hrefs);
		foreach($subcats_hrefs as $subcats_href){
			if(exists($subcats_href['_href'], 'sub_cat/' . $_submodel)){
				s('пропускаем ' . $subcats_href['_href']);
				continue;
			}
			$links = [];
			$links = find_links($subcats_href);
			find_spares($links);
			$i++;
			save($subcats_href['_href'], 'sub_cat/' . $_submodel);
			s( sprintf( '%s%%. Категория %s из %s', number_format( $i/$c*100 , 2 ), $c_i, count($cats) ) );
		}
		save($cat['href'], 'cat');
	}
	return ;
	
}
function find_spares($links){
	
	$mcurl = new Curl\MultiCurl;
	$mcurl->setConcurrency(25);
	$mcurl->setConnectTimeout(2);
	$curvy = [];
	foreach(array_column($links,'href') as $link){
		$mcurl->addGet($link);
	}
	$mcurl->success(function($instance) use ($links, $mcurl){
		
		// static $count;
		// $count++;
		// echo $count . "<br>\n";
		// s('<hr>--< ' . memory_get_peak_usage());
		if(!file_exists('checker.dd')){
			s('Вызвана остановка',1); exit;
		}
		$item = $links[$instance->url];
		$spare['_marka'] = $item['marka'];
		$spare['_submodel'] = $item['submodel'];
		$spare['_category'] = $item['category'];
		$doc = phpQuery::newDocument($instance->response);
		$instance->response = null;
		$spare['_sku']          = pq('#orignr')->attr('value');
		$spare['_title']        = trim(pq('#theContent .page-header h1')->text());
		curl_close($instance->curl);
		curl_multi_remove_handle($mcurl->multiCurl, $instance->curl);
		// die;
		// s('середмна       < ' . memory_get_peak_usage());
		// phpQuery::unloadDocuments();
		// $instance = null;
		// unset($doc);
		// unset($instance);
		// return;
		$is_BU = strpos($instance->url, 'part/new/') === false;
		if(!$spare['_sku']){
			// s('нет оригинального номера '.$instance->url,1);
			$doc->unloadDocument();
			return;
		}
		// поиск дубликата если БУ
		if($is_BU){
			if(exists('spare-' . $spare['_sku'])){
				// s('уже существует, пропусаем ' . $spare['_title']);
  			    $doc->unloadDocument();
				return ;
			}
		}		
		// save('spare-' . $spare['_sku']);
		// поиск производителя 
		$spare['_manufacturer'] = '';
		$tmp = pq('#theContent>.row>div:eq(1) div');
		foreach($tmp as $i){
			pq('label',$i)->text();
			if(strpos(trim(pq('label',$i)->text()), 'Производитель:')!==false ){
				$spare['_manufacturer'] = trim(str_replace( 'Производитель:', '', pq($i)->text()));
			}
		}
		if(!$spare['_manufacturer']){
			// s('нет проиводителя '.$instance->url,1);
		}
		$images = pq('#block_img .thumbnail img');
		$imgs = [];
		foreach($images as $image){
			$imgs[] = pq($image)->attr('src');
		}
		// j($imgs);
		
		$spare['count_images'] = count($imgs);
		// j($spare);
		$id = null;
		foreach($imgs as $img){
			$spare["_id"] = $id;
			$spare['_img'] = $img;
			$id = save_spare($spare);
		}
		$doc->unloadDocument();
		// s('konec function ' . memory_get_peak_usage());
	}); 
	
	$mcurl->start();
	$mcurl->close();
	unset($mcurl);
	phpQuery::unloadDocuments();
}
function find_links($config, $page = 1){
	if(!file_exists('checker.dd')){
		s('Вызвана остановка',1); exit;
	};
	extract($config);
	$links = [];
	if($page>1){
		$_href .= '?page=' . $page;
	}
	$doc = @file_get_contents($_href);
	if(!$doc){
		return [];
	}
	$doc = phpQuery::newDocument($doc);
	$list = pq('.parts-list .parts-item_box');
	foreach($list as $item){
		
	    $__href = SITE . pq('.boxheader a',$item)->attr('href');
		$links[$__href] = ['category'=>$category,'marka'=>$marka, 'submodel'=>$submodel, 'href'=>$__href];
		
	}
	if($pagi = pq('#pagination-block')){
		$cur_page = (int)$pagi->attr('data-currentpage');
		$end_page = (int)$pagi->attr('data-endpage');
		if($cur_page < $end_page){
    	$doc->unloadDocument();
		$merged = find_links($config, ++$cur_page);
		$links = array_merge($links, $merged);
		}
	}else{
		$doc->unloadDocument();
	}
	return $links;

}
function exists(){
	$args = array_map(function($item){
		return iconv('utf-8','cp1251',translit($item));
	} , func_get_args());
	$path = isset($args[1])? "check_dir/".$args[1]."/":"check_dir/";
	$file =  $path.$args[0];
	return file_exists($file);
}
function save(){
	$args = array_map(function($item){
		return iconv('utf-8','cp1251',translit($item));
	} , func_get_args());
	$path = isset($args[1])? "check_dir/".$args[1]."/":"check_dir/";
	$file =  $path.$args[0] ;
	file_exists($path) || mkdir($path, '0777', 1);
	touch($file);
}
function save_spare($ar){
	extract(array_map('trim',$ar));
	$csv_path = "csv/csv_$_marka/";
	$img_path = "spares_img/zapchasti_$_marka/" . translit($_submodel) . '/';
	file_exists($img_path) || mkdir($img_path,'0777',1);
	file_exists($csv_path) || mkdir($csv_path,'0777',1);
	if($_img){
		$img_name = md5($_img) . '.jpg';
		$img_bin = @file_get_contents($_img);
		if($img_bin){
			file_put_contents($img_path.$img_name,$img_bin);
		  $img_name = "$_marka/" . translit($_submodel) . '/' . $img_name;
		}else{
			$img_name = '';
		}
	}else{
		$img_name = '';
	}
	if(!$img_name && $count_images > 0){
		return;
	}
	$csv_name = $csv_path . translit($_submodel) . '.csv';
	if(!file_exists($csv_name)){
	  $header = ['IE_XML_ID','IE_NAME'/* ,'IE_CODE' */,'IP_PROP9','IP_PROP10','IP_PROP13','IC_GROUP0','IC_GROUP1','IC_GROUP2'];
		$fd = fopen($csv_name, 'a');
		fputcsv($fd,$header,';');
	}else{
	  $fd = fopen($csv_name, 'a');
	}
	// $_code = translit($_title);
	$id = $_id? : id();
	$data = array_map(function($i){
		return iconv('utf-8','cp1251',$i);
	},[$id, $_title, /* $_code,  */$_sku, $_manufacturer, $img_name, 'Запчасти '. $_marka, 'Запчасти '. $_marka . ' ' . $_submodel,  $_category . ' ' . $_marka . ' ' . $_submodel]);
	fputcsv($fd,$data,';');
	fclose($fd);
	return $id;
}
function id($id = null){
	if($id){
		file_put_contents('iddata', $id);
		return $id;
	}

	$id = file_get_contents('iddata');
	
	if(!$id){
		sleep(2);
		$id = file_get_contents('iddata');
	}
	
	$id = (int)$id;
	
	if( $id < 235 ) {
		s('Не удалось прочитать id');
		exit();
		s($id .'<' . 235) ;
		$id = 235;
	}
	$id++;
	// s($id);
	file_put_contents('iddata',$id);
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
	file_exists($path) || mkdir($path, '0777', 1);
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