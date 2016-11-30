<?php
set_time_limit(-1);
clear('imgs');
clear('spares_img');
clear('csv');
clear('');
// file_put_contents('iddata',0);
// print_r(glob('imgs/*'));
function clear($path){
	// echo '<b>' . $path . '</b><br>';
	$files = array_filter(scandir("$path"), function($item){
		return !in_array($item, ['.','..']);
	});
	// echo '<pre>';print_r($files); echo '</pre>';
	array_map(function($item) use ($path){
		$item = $path . '/'. $item;
		if(is_dir($item)){
			// echo ' - is dir '.$item.'<br>';
			clear("$item");
			rmdir($item);
		}else{
			// echo 'удаляем файл ' . $item .  '<br>';
			unlink($item);
		}
	},$files);
}

// unlink(

header('Location: .');