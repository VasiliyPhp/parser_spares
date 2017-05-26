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
		$mcurl->setConcurrency(2);
		$mcurl->setConnectTimeout(2);
		$mcurl->complete(function($instance) use ($mcurl){
			curl_close($instance->curl);
			curl_multi_remove_handle($mcurl->multiCurl, $instance->curl);
			// s('Отработали ' . $instance->curl);
		});
		$mcurl->success(function($instance) use (&$hrefs, $_marka){
			$doc = $instance->response;
			$submodelsDoc = phpQuery::newDocument($doc)->find('.model-list a');
			$_model = trim(str_replace('Запчасти для ' . $_marka, '', trim(pq('h1')->text())));
			
			foreach($submodelsDoc as $subm){
				$_submodel = trim(pq($subm)->find('.model-tile__title')->text());
				// $_submodel = str_replace('/','-',trim(pq($subm)->text()));
				if(exists($_marka,$_submodel)){
					s("Пропускаем $_marka $_submodel");
					continue;
				}
				$subm_href = trim(pq($subm)->attr('href'));
				$img       = trim(pq($subm)->find('img')->attr('src'));
				// save_img($subm_href,$img);
				$hrefs[SITE . $subm_href] = [
				'marka' => $_marka,
				'model' => $_model,
				'subm_href' => $subm_href,
				'submodel' => $_submodel,
				'img' => [str_replace('/auto/cars/','',$subm_href), $img],
				];
				// j($hrefs);
			}
			$submodelsDoc->unloadDocument();
		});
		// echo $models;die;
		foreach($models as $model){
			//название модели 
			$_model = trim(pq($model)->find('.model-tile__title')->text());
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
		$mcurl->close();
		unset($mcurl);
		gc_collect_cycles();
		$mcurl = new Curl\MultiCurl;
		$mcurl->setConcurrency(2);
		$mcurl->setConnectTimeout(2);
		// проходимся по каждой собранной ссылке подмодели 
		// собираем все картинки моделей
		foreach($hrefs as $__img_src){
			if(file_exists('_catalog/'.($__img_src['marka']) . '/imgs/cars_' . $__img_src['img'][0] . 'auto.jpg')){
				continue;
			}
			file_exists('_catalog/'.($__img_src['marka']) . '/imgs/cars_' . $__img_src['img'][0]) || mkdir('_catalog/'.($__img_src['marka']) . '/imgs/cars_' . $__img_src['img'][0],null,1);
			$mcurl->addDownload($__img_src['img'][1],('_catalog/'.$__img_src['marka']) . '/imgs/cars_' . $__img_src['img'][0] . 'auto.jpg');
		}
		$mcurl->start();
		$mcurl->close();
		unset($mcurl);
		gc_collect_cycles();
		$mcurl = new Curl\MultiCurl;
		$mcurl->setConcurrency(2);
		$mcurl->setConnectTimeout(2);
		
		// проходимся по каждой собранной ссылке подмодели 
		// собираем основные категории для каждой подмодели
		$catsList = [];
		$mcurl->complete(function($instance) use ($mcurl){
			curl_close($instance->curl);
			curl_multi_remove_handle($mcurl->multiCurl, $instance->curl);
			// s('Отработали ' . $instance->curl);
		});
		$mcurl->success(function ($instance) use (&$catsList, $hrefs){
			
			$doc = $instance->response;
			$submodel = $hrefs[$instance->url]['submodel'];
			$marka = $hrefs[$instance->url]['marka'];
			$model = $hrefs[$instance->url]['model'];
			$subcatsDoc = phpQuery::newDocument($doc);
			// echo $doc; die;
			$list = pq('.parts_left .tree-block');
			foreach($list as $item){
				$subcats = pq('ul li a', $item);
				$category = trim(pq('.title__content',$item)->text());
				foreach($subcats as $subcat){
					array_push($catsList, [
					'_marka'=>$marka, 
					'_model'=>$model, 
					'_submodel'=>$submodel, 
					'_category'=>$category, 
					'_subcategory'=>trim(pq($subcat)->text()),
					'_href' => SITE . pq($subcat)->attr('href')
					]);
				}
				// echo count($subcats);die;
			}
			$subcatsDoc->unloadDocument();
			// j($catsList);
		});
		
		foreach($hrefs as $__subm_href){
			
			$href = $__subm_href['subm_href'];
			
			$mcurl->addGet(SITE . $href);
			
		}
		
		$mcurl->start();
		$mcurl->close();
		gc_collect_cycles();
		unset($mcurl);
		// j($catsList);
		return $catsList;
	}
	//поиск подкатегории в катерии запчасти 
	function find_subcats($subcats_hrefs){
		if(!file_exists('checker.dd')){
			s('Вызвана остановка',1); exit;
		}
		$c_i = 0;
		// extract($subcats_hrefs);
		/*foreach($cats as $cat){
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
			$_model = $cat['model'];
			$_submodel = $cat['submodel'];
			$_category = $cat['category'];
			$curl = new Curl\curl($cat['href']);
			$doc = $curl->exec();
			// $cookie = $curl->getCookie('savedBrands');
			$curl->close();
			unset($curl);
			
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
			$subcats_hrefs[] = ['marka'=>$_marka,'model'=>$_model,'submodel'=>$_submodel,'category'=>$_category, 'subcategory'=> trim(pq($subcats)->text()), '_href'=>SITE . pq($subcats)->attr('href')];
			// $ar['_href'] = SITE . $_href2;
			// $ar['_subcat'] = $_subcat;
			// find_spare_links($ar, &$a_href);
			}
			$doc->unloadDocument();
			unset($doc, $list, $subcats);
			$i = 0;
		$c = count($subcats_hrefs);*/
		$c = count($subcats_hrefs);
		$i = 0;
		foreach($subcats_hrefs as $subcats_href){
			$i++;
			extract($subcats_href);
			if(exists($subcats_href['_href'], 'sub_cat/' . $_submodel)){
				s('пропускаем ' . $subcats_href['_href']);
				continue;
			}
			$links = [];
			$links = find_links($subcats_href);
			find_spares($links);
			$i++;
			save($subcats_href['_href'], 'sub_cat/' . $_submodel);
			s( sprintf( '%s%%', number_format( $i/$c*100 , 2 ) ) );
		}
		// j($links);
		// save($cat['href'], 'cat');
		// }
		return ;
		
	}
	function find_spares($links){
		if(!file_exists('checker.dd')){
			s('Вызвана остановка',1); exit;
		}
		global $new_links;
		$new_links = [];
		$mcurl = new Curl\MultiCurl;
		$mcurl->setConcurrency(4);
		$mcurl->setConnectTimeout(2);
		foreach(array_column($links,'href') as $link){
			
			// if(!exists($link, $links[$link]['marka'],$links[$link]['model'],$links[$link]['subcategory'])){
			$mcurl->addGet($link);
			// } 
			// else {
			// s('Пропускаем запчасть ' . $link);
			// }
		}
		$mcurl->error(function($instance) use (&$links, $mcurl){
			global $new_links;
			// s($links[$instance->url]['try']);
			if(isset($links[$instance->url]['try'])){
				if($links[$instance->url]['try'] < 50 ){
					++$links[$instance->url]['try'];
					$new_links[$instance->url] = $links[$instance->url];
				}
				} else {
				$links[$instance->url]['try'] = 0;
				$new_links[$instance->url] = $links[$instance->url];
			}
			s('Ошибка - ' . $instance->url . ' ' . $links[$instance->url]['try'],2);
			// s(print_r($new_links,1));
		});
		$mcurl->complete(function($instance) use ($mcurl){
			curl_close($instance->curl);
			curl_multi_remove_handle($mcurl->multiCurl, $instance->curl);
			// s('Отработали ' . $instance->url);
			gc_collect_cycles();
		});
		$mcurl->success(function($instance) use ($links, $mcurl){
			// static $count;
			// $count++;
			// echo $count . "<br>\n";
			// s('<hr>--< ' . memory_get_peak_usage());
			if(!file_exists('checker.dd')){
				s('Вызвана остановка',1); exit;
			}
			$item = $links[$instance->url];
			$spare['_marka']    = $item['marka'];
			$spare['_model'] 	= $item['model'] == "Accent/Verna/Solaris" ? "Accent" : $item['model'];
			$spare['_submodel'] = $item['submodel'];
			$spare['_category'] = $item['category'];
			$spare['_subcategory'] = $item['subcategory'];
			$spare['_title']    = $item['title'];
			$doc = phpQuery::newDocument($instance->response);
			$instance->response = null;
			$spare['_sku']      = pq('#orignr')->attr('value');
			$spare['_comment']  = html_entity_decode(trim(pq('[itemprop="description"]')->html()));
			$is_BU = strpos($instance->url, 'part/new/') === false;
			if(!$spare['_sku']){
				s('нет оригинального номера '.$instance->url,1);
				$doc->unloadDocument();
				return;
			}
			
			// save($instance->url, $item['marka'],$item['model'],$item['subcategory']);
			// поиск дубликата если БУ
			if($is_BU){
				if(exists('spare-bu' . $spare['_sku'], $spare['_marka'], $spare['_submodel'], $spare['_category'])){
					s('уже существует, пропусаем ' . $spare['_submodel'] . ' №' . $spare['_sku']);
					$doc->unloadDocument();
					return ;
				}
				save('spare-bu' . $spare['_sku'], $spare['_marka'], $spare['_submodel'], $spare['_category']);
			}		
			s('Сохраняем' . ($is_BU? ' б/у ' : ' new ') . $spare['_submodel'] . ' №' . $spare['_sku'], 1);
			// поиск производителя 
			$spare['_manufacturer'] = '';
			$tmp = pq('#theContent>.row>div:eq(1) div');
			foreach($tmp as $i){
				pq('label',$i)->text();
				if(strpos(trim(pq('label',$i)->text()), 'Производитель:')!==false ){
					$spare['_manufacturer'] = trim(str_replace( 'Производитель:', '', pq($i)->text()));
				}
			}
			
			$images = pq('#block_img .thumbnail img');
			$imgs = [];
			foreach($images as $image){
				$imgs[] = pq($image)->attr('src');
			}
			// j($imgs);
			
			$spare['count_images'] = count($imgs);
			$spare['is_BU'] = $is_BU;
			// j($spare);
			$id = null;
			if($is_BU || !count($imgs)){
				$imgs = [''];
			}
			
			foreach($imgs as $img){
				$spare["_id"] = $id;
				$spare['_img'] = $img;
				$id = save_spare($spare);
			}
			$doc->unloadDocument();
		}); 
		$mcurl->start();
		$mcurl->close();
		unset($mcurl);
		phpQuery::unloadDocuments();
		// s(print_r($new_links,1),1);
		if(count($new_links)){
			s('запуск ненайденых ' . count($new_links), 1);
			find_spares($new_links);
		}
		// else{
		// s('нет не найденных',1);
		// }
		gc_collect_cycles();
	}
	
	
	function find_links($config, $page = 1){
		if(!file_exists('checker.dd')){
			s('Вызвана остановка',1); exit;
		};
		// s('страница ' . $page);
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
		$list = pq('.parts-list .snippet-card');
		foreach($list as $item){
			
			$__href = SITE . pq('.info__header a',$item)->attr('href');
			$title  = trim(pq('.info__header a',$item)->text());
			$links[$__href] = ['category'=>$_category,'subcategory'=>$_subcategory,'marka'=>$_marka, 'model'=>$_model, 'submodel'=>$_submodel, 'href'=>$__href, 'title'=>$title];
			
		}
		if($pagi = pq('#pagination-block')){
			// s('есть пагинации');
			$cur_page = (int)$pagi->attr('data-currentpage');
			$end_page = (int)$pagi->attr('data-endpage');
			if($cur_page < $end_page){
				$doc->unloadDocument();
				$merged = find_links($config, ++$cur_page);
				$links = array_merge($links, $merged);
			}
		}
		else{
			s('нет пагинации');
			$doc->unloadDocument();
		}
		return $links;
		
	}
	function exists(){
		list($file, $path) = call_user_func_array('make_path', func_get_args());
		return file_exists($file);
	}
	function save(){
		list($file, $path) = call_user_func_array('make_path', func_get_args());
		file_exists($path) || mkdir($path, null, 1);
		touch($file);
	}
	
	function make_path(){
		$args = array_map(function($item){
			return iconv('utf-8','cp1251',translit($item));
		} , func_get_args());
		
		$path = count($args) > 1 ? 'check_dir/' . implode('/' , array_slice($args, 1)) . '/' : 'check_dir/';
		
		$file = $path . $args[0]; 
		
		return [$file, $path];
	}
	
	function save_spare($ar){
		extract(array_map('trim',$ar));
		$csv_path = "_catalog/$_marka/csv/";
		$img_path = "_catalog/$_marka/imgs/$_marka/" . translit($_submodel) . '/';
		file_exists($img_path) || mkdir($img_path,null,1);
		file_exists($csv_path) || mkdir($csv_path,null,1);
		if($_img && !$is_BU){
			$img_name = md5($_img) . '.jpg';
			if( !file_exists($img_path . $img_name) ){
				$img_bin = @file_get_contents($_img);
				if($img_bin){
					file_put_contents($img_path.$img_name,$img_bin);
					$img_name = "$_marka/" . translit($_submodel) . '/' . $img_name;
				}
				else{
					$img_name = '';
				}
			}
			else{
				$img_name = "$_marka/" . translit($_submodel) . '/' . $img_name;
			}
		}
		else{
			$img_name = '';
		}
		// if(!$img_name && $count_images > 0 && !$is_BU){
		// return;
		// }
		$csv_name = $csv_path . translit($_marka) . '.csv';
		if(!file_exists($csv_name)){
			// $header = ['IE_XML_ID','IE_NAME'/* ,'IE_CODE' */,'IP_PROP9','IP_PROP10','IP_PROP13','IP_PROP39','IC_GROUP0','IC_GROUP1','IC_GROUP2','IC_GROUP3','IC_GROUP4'];
			$header = [
			// 'IE_XML_ID', // id
			'IE_NAME', // название запчасти
			'IE_CODE', // символьный код товара (трансдлит)
			'IP_PROP2', // сюда копировать название элемента
			'IP_PROP4', //сюда копировать название элемента
			'IP_PROP9', // артикул
			'IP_PROP10', // производитель
			'IP_PROP13', // Путь к картинке
			'IP_PROP39', // Коментарий 
			'IP_PROP41', // оригинал ? 
			'IP_PROP42', // Аналог? 
			'IC_GROUP0', // марка
			'IC_CODE0', // символьный код (марка)
			'IC_GROUP1', // Модель
			'IC_CODE1', //  символьный код (модель)
			'IC_GROUP2', // подмодель
			'IC_CODE2', // символьный код (подмодель) 
			'IC_GROUP3', // категория запчасти
			'IC_CODE3', // символьный код (категория запчасти)
			'IC_GROUP4', // подкатегория
			'IC_CODE4', //  символьный код (покатегория)
			];
			$fd = fopen($csv_name, 'a');
			fputcsv($fd,$header,';');
		}
		else{
			$fd = fopen($csv_name, 'a');
		}
		// $_code = translit($_title);
		// $id = $_id? : id();
		$data = array_map(function($i){
			return iconv('utf-8','cp1251',$i);
		},[
		// $id, 
		$_title,
		translit($_title),
		$_title,
		$_title,
		$_sku, 
		$_manufacturer, 
		($img_name ? '/upload/' . $img_name : '' ),
		$_comment,
		($is_BU ? 'Y' : 'N'),
		($is_BU ? 'N' : 'Y'),
		$_marka,
		translit($_marka),
		str_replace('Запчасти для ', '', $_model), 
		translit(str_replace('Запчасти для ', '', $_model)),
		$_marka . ' ' . $_submodel,
		translit($_marka . ' ' . $_submodel),
		$_category,  
		translit($_category),  
		$_subcategory,
		translit($_subcategory),
		]);
		fputcsv($fd,$data,';');
		fclose($fd);
		return null;
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
		file_put_contents('iddata2',$id);
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