<?php
error_reporting(E_ALL);
ini_set('display_errors', true);
?>

<!doctype html>
<html>
	<head>
		<title><?=isset($_POST['cats'][0]) && $_POST['cats'][0] ? $_POST['cats'][0]
		. ' - поиск запчастей' : 'Парсер запчастей'?></title>
	</head>
	<body>
<?php


$allCats = [
	// 'Audi',
	//'Chery',
	// 'Citroen',
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
	// 'Fiat',
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
require 'vendor/autoload.php';
$necessary = array_filter(array_map('trim', file('needed_cars.txt', FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES )));
// j($necessary);
const SITE = 'http://euroauto.ru';
if(isset($_POST['cats'])){
	set_time_limit(-1);
	touch('checker.dd');
	if(isset($_POST['id'])){
		id($_POST['id']);
	}
	// remove('cats');
	$needle = $_POST['cats'];
	$cats = get_main_categories();

	foreach($cats as $cat){
		if(!in_array($cat['title'], $needle)){
			s($cat['title'] . ' - нет в списке', 1);
			continue;
		}
		$catsList = parse($cat);
		find_subcats($catsList);
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
