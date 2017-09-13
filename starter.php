#!/usr/bin/php -f 
<?php
$amigo_action = $argv[1];
if('help' == $amigo_action){
	$text_help = 'v. 1.1
	# Стартовать сервисы
	php -f starter.php start
	# Остановить сервисы
	php -f starter.php stop
	# Старт / рестарт сервисов
	php -f starter.php restart
	
	# Показать текущие процессы.
	php -f starter.php show';
	echo "$text_help\n";
	exit;
}

function process_amigo($bin_name, $param, $action){

	$out = array();
	exec("/bin/ps | /bin/grep '$bin_name' | /bin/grep -v grep | /usr/bin/awk ' {print $1} '", $out);
	exec("/bin/ps | /bin/grep '$bin_name' | /bin/grep -v grep | /usr/bin/awk ' {print $1} '", $out);
	$WorkerPID = $out[0];
	
	if('show' == $action){
		echo "PID: -$WorkerPID- Skript: $bin_name \n";
		return;
	}
	
	if("$WorkerPID" != '' && ('stop' == $action || 'restart' == $action) ){
		exec("/bin/kill -9 $WorkerPID  > /dev/null 2> /dev/null &");
		$WorkerPID = '';
	}
	
	if("$WorkerPID" == '' && ('start' == $action || 'restart' == $action) ){
		exec("/usr/bin/nohup /usr/bin/php -f  $bin_name $param  > /dev/null 2>&1 &");
	}
}

function process_action($action){
	
	$bindir = __DIR__.'/';
	process_amigo($bindir.'AMI_Listner.php' , 's', $action);
	process_amigo($bindir.'Dialer.php'		, 's', $action);
}

if('' != "$amigo_action"){
	process_action($amigo_action);
}


?>