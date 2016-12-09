<?php
exec('chcp 65001');

const SITE = 'http://euroauto.ru';

	require 'vendor/autoload.php';
	set_time_limit(-1);
	touch('checker.dd');
	$needle = $allCats;
	$cats = get_main_categories();

	foreach($cats as $cat){
		if(!in_array($cat['title'], $needle)){
			s($cat['title'] . ' - нет в списке', 1);
			continue;
		}
		parse($cat);
	}
exec('pause');