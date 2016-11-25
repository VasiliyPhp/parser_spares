<?php



if(!function_exists('x')){
	function x(){
		
		$ar = func_get_args();
		if(count($ar)==1){
			$ar = $ar[0];
		}
		printf('<pre>%s</pre>', print_r($ar, 1));
	}
}
if(!function_exists('j')){
	function j(){
		
		$ar = func_get_args();
		if(count($ar)==1){
			$ar = $ar[0];
		}
		x($ar);
		die;
	}

}


function get($name){
	$name = 'tmp_' . $name;
	return file_exists($name) ? unserialize(file_get_contents($name)) : false;
}

function set($name, $val){
	$name = 'tmp_' . $name;
	file_put_contents($name, serialize($val));
}

function remove($name){
	$name = 'tmp_'. $name;
	file_exists($name) && unlink($name);
}