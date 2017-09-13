#!/usr/bin/php -f
<?php 
require_once(__DIR__."/TextToSpeachYandex.php");

class Dialer{
	private $settings;
	private $TTS;
	
	function __construct($settings_ptath) {
		date_default_timezone_set('Europe/Moscow');

		$this->settings=json_decode(file_get_contents($settings_ptath), true);
		$this->TTS = new TextToSpeachYandex($this->settings);
  	}
  	
  	public function start(){
		while(true){
		    sleep(3);
			$array_expression = $this->get_task_from_1c();
			
			if($array_expression == null){
				continue;
			}			
			foreach ($array_expression as $value){
				$fname = $this->TTS->text2speechYandex( $value['text'] );
				if(null == $fname){
					$this->send_status_call_1c($value['number'], 'fail_generate_speach');
					continue;
				}
				$this->Verbose("---  ${value['number']} $fname\n");
				$this->callback($value['number'], "ttsfname=$fname") + "\n";
			}			
		}
  	}

	private function callback($usernum, $vars){
		if(!is_array($this->settings)){
			return 'ERROR: settings';
		}
		$result = '';
		
		$strwebnum 		= 'dialer';
		$usernum 		= preg_replace('~\D+~', '', $usernum);
		$strHost 		= $this->settings['host'];
		$strUser 		= $this->settings['username'];
		$strSecret 		= $this->settings['secret'];
	
		$dst_Context 	= $this->settings['dst_context'];
		$local_context 	= $this->settings['local_context'];
	
		$oSocket = fsockopen($strHost, 5038, $errnum, $errdesc,30) or die("Сервер не вернул ответ.");
		stream_set_timeout($oSocket, 0, 500000);
		
		$command = "Action: login\r\n"
				  ."Events: off\r\n"
				  ."Username: $strUser\r\n"
				  ."Secret: $strSecret\r\n\r\n";
		fputs($oSocket, $command);	
		while ($line = fgets($oSocket)){ 
			$result .= $line;
		}
		$result .= '---';
	
		$command = "Action: originate\r\n"
				  ."Channel: Local/$usernum@$local_context\r\n"
				  ."WaitTime: 30\r\n"
				  ."CallerId: Dialer <$strwebnum>\r\n"
				  ."Exten: $strwebnum\r\n"
				  ."Context: $dst_Context\r\n"
				  ."Priority: 1\r\n"
				  ."Async: 1\r\n"
				  ."Variable: $vars\r\n\r\n";
		fputs($oSocket, $command);		
		while ($line = fgets($oSocket)){ 
			$result .= $line;
		}
	
		fputs($oSocket, "Action: Logoff\r\n\r\n");
		
		while ($line = fgets($oSocket)) 
			$result .= $line;
		
		fclose($oSocket);
		return $result;
	}
	
	private function Verbose($value){
	  	// echo ''.date("H:i:s").' -- '.$value; 
		file_put_contents($this->settings['logfile'], ''.date("H:i:s").':> '.$value, FILE_APPEND);
	}
	
	private function get_task_from_1c(){

		$result = null;
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, 		  $this->settings['path_to_1c'].'?event=getpart&');
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
		
		try{
			$result = json_decode("$server_output", true);
		} catch (Exception $e) {
			$this->Verbose("ОшибкаJSON ".$e->getMessage()."\n");
		}
		return $result;
	}

	private function send_status_call_1c($number, $status){

		$result = null;
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, 		  $this->settings['path_to_1c']."?event=putresult&DIALERSTATUS=$status&CID=$number");
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

} 



if('s'==$argv[1]){
	$d = new Dialer(__DIR__.'/settings.json');
	$d->start();
}

//*/
	
?>