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

	require 'phpQuery/phpQuery.php';
	require 'helper_functions.php';
	require 'core.php';
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

