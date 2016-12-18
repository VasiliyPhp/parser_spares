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

if(!function_exists('s')){
	function s($s, $t=0){
		if(php_sapi_name() == 'cli'){
			if($t){
				echo '~~~~~~~~~' . PHP_EOL;
			  echo $s . PHP_EOL;
				echo '~~~~~~~~~' . PHP_EOL;
			}else{
			  echo $s . PHP_EOL;
			}
			return ;
		}
		switch($t){
		case 0:
		  $color='#485';break;
		case 1:
		  $color='#944';break;
		}
		?>
		<div style="margin:3px;color:<?=$color;?>;">
		  <?=$s. ' ; memory usage: ' . number_format(memory_get_usage() / 1000000, 1, '.', ' ') . ' Mb';?>
		</div>
	<?php 
	ob_flush();
	flush();
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
// set_error_handler('myErrorhandler');
function myErrorHandler($errno, $errstr, $errfile, $errline)
{
	if(stripos($errstr,'array to')!==false)
    debug_print_backtrace(null,5);
	return false;
    // switch ($errno) {
    // case E_USER_ERROR:
        // echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        // echo "  Фатальная ошибка в строке $errline файла $errfile";
    // debug_print_backtrace();    
	// exit(1);break;
 
    // case E_USER_WARNING:
        // echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
     // debug_print_backtrace();   
	// exit(1);break;
 
    // case E_USER_NOTICE:
        // echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
   // debug_print_backtrace();  
   // break;
 
    // default:
        // echo "Неизвестная ошибка: [$errno] $errstr<br />\n";
        // break;
    // }
	
}