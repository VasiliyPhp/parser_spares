<?php

exec('chcp 65001');

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
	require 'vendor/autoload.php';
	set_time_limit(-1);
	touch('checker.dd');
	// remove('cats');
	$needle = $allCats;
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
}if(isset($_POST['cats'])){
