#!/usr/bin/php -f
<?php
/*
	v.1.3. 2017-07-31
*/
require_once __DIR__.'/PAMI/Autoloader/Autoloader.php'; 
\PAMI\Autoloader\Autoloader::register(); 

////////////////////////////////////////////////////////////////////////////////
// Функции и классы

class AMI_Listner implements PAMI\Listener\IEventListener {
	private $settings;
	
	function __construct() {
		$this->settings=json_decode(file_get_contents(__DIR__.'/settings.json'), true);
		date_default_timezone_set('Europe/Moscow');
  	}
	private function Verbose($value){
		file_put_contents($this->settings['logfile'], ''.date("H:i:s").':> '.$value, FILE_APPEND);
	}
  	
    public function handle(PAMI\Message\Event\EventMessage $event)
    {
        $dialstatus = $event->getKey('dialstatus');
        $linkedid   = urlencode($event->getKey('idcall'));
        $cid   		= $event->getKey('cid');
        $time   	= $event->getKey('time');
		
	    $this->Verbose("$userevent - $dialstatus $time $linkedid\n");
	    $this->send_status_call_1c("?event=putresult&DIALERSTATUS=$dialstatus&CID=$cid&ID=$linkedid&TIME=$time");
    }
	
	private function send_status_call_1c($data_string){

		$result = null;
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, 		  $this->settings['path_to_1c'].$data_string);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl, CURLOPT_TIMEOUT, 	  3);
		curl_setopt($curl, CURLOPT_USERPWD, 	  $this->settings['login_1c'].":".$this->settings['pass_1c']);
		

		$server_output = curl_exec($curl);
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		if("$code" == '200'){
			// $this->Verbose('HTTP code = '.$code."\n");
		}else{
		  	$this->Verbose('ERROR http code = '.$code."\n");
		}
		curl_close($curl);
		
	}
   
    public function start(){
		$client = new PAMI\Client\Impl\ClientImpl($this->settings);
		$client->registerEventListener($this, 	function($event) {
													return ($event->getName() == 'UserEvent' && 'DialState' == $event->getKey('userevent') ) ;
												});
		$client->open();
		
		$time = time();
		while(true)
		{
		    usleep(20000); 
		    $client->process();
		}
		$client->close();
    }

}
if('s'==$argv[1]){
	$worker = new AMI_Listner();	    
	$worker->start();
}

?>