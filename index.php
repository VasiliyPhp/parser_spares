<?php
	ignore_user_abort(true);
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
			'Ssang Yong',
			'Jeep',
			'Jaguar',
			'Lexus',
			'Mercedes Benz',
			'Fiat',
			'Infiniti',
			'Volvo',
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
			}
			else{
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
				