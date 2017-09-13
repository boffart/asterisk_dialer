<?php
/*
	
*/
class OtherFunc{
	// Проверяет, сушествует ли файл в файловой системе Askozia.
	//
  	public function rec_file_exists($filename, $size=null){
	  	if (@filetype($filename) == "file" && filesize($filename)>0){
		    return true;
	  	}else{
		    return false;
	  	}
  	}
}

class TextToSpeachYandex extends OtherFunc{
	private $ttsdir;
	private $api_key;
	private $text;
	private $verbose;
	
	function __construct($settings) {
		$this->ttsdir  = $settings['speach_dir'];
		$this->api_key = $settings['yandex_api_key'];
		$this->verbose = $settings['verbose'];
		
  	}
	private function Verbose($text){
		if(true == $this->verbose){
			echo "$text\n"; 
		}
	}	
	// Возвращает путь к папке с кешем сгенерированных текстовых фраз.
	//
	private function get_ttsdir(){
		if(!is_dir($this->ttsdir)){
			mkdir($this->ttsdir, 0700, true);
		}
		return $this->ttsdir;
	}// get_ttsdir
	
	private function curl_get_file($tmpfilemane, $url){
	  $fp = fopen($tmpfilemane, "w");
	  $curl = curl_init();
	
	  curl_setopt($curl, CURLOPT_FILE,    		$fp);
	  curl_setopt($curl, CURLOPT_TIMEOUT, 		3);
	  curl_setopt($curl, CURLOPT_CUSTOMREQUEST,	"GET");
	  curl_setopt($curl, CURLOPT_URL, 			$url);
	  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);
	  curl_exec($curl);
	  $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	  // $size 	 = curl_getinfo($curl, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
	  curl_close($curl);
	  fclose($fp);
	  return $http_code;
	}
	// Генерирует и скачивает в на внешний диск файл с речью.
	// $texttospeech - текст в urlencode
	// $api_key  - API ключ
	// $dictor  - диктор
	//
	public function text2speechYandex($texttospeech, $dictor = "jane"){
	  
	  $speech_extension = ".wav";
	  $speech_path     = $this->get_ttsdir();
	  $speech_filename   = md5($texttospeech.$dictor);
	
	  // Проверим вдург мы ранее уже генерировали такой файл.
	  if($this->rec_file_exists($speech_path.$speech_filename.$speech_extension)){
	     $this->Verbose("Фраза: ".urldecode($texttospeech)." есть в кеше. ".$speech_path.$speech_filename.$speech_extension );
	     return $speech_path.$speech_filename; // Есть файл записи в кеше
	  }
	  
	  // Файла нет в кеше, будем генерировать новый    
	  $url ="https://tts.voicetech.yandex.net/generate?text=\"$texttospeech\"&format=wav&lang=ru-RU&speaker=$dictor&key=".$this->api_key;
	  $tmpfilemane = "/tmp/$speech_filename.wav";
	  $http_code = $this->curl_get_file($tmpfilemane, $url);
	  
	  $this->Verbose($url);
	  if (0 == $http_code){
	    $this->Verbose("Нет доступа к Yandex (tts.voicetech.yandex.net:443). Проверьте наличие доступа в интернет.");
	    return null;
	  } 
	   
	  if($http_code  == 200 && $this->rec_file_exists($tmpfilemane) ){
	    $out = array();
	    exec("sox $tmpfilemane -e signed-integer -b 16 -c 1 -t wav -r 8k $speech_path$speech_filename$speech_extension", $out);
	    $this->Verbose("Успешная генерация в файл: $speech_path$speech_filename$speech_extension размер файла: ".filesize($tmpfilemane));
		unlink($tmpfilemane);
	    return $speech_path.$speech_filename;
	  }else{
	    $autherr = '';
	    if(401 == $http_code || 423 == $http_code){
	    	$autherr = ' Ошибка авторизации.';    
		}
		Verbose("Генерация звука Yandex: $autherr Код HTTP $http_code, API key $api_key (проверьте настройки в 1С:Предприятие).");
	  }
	  
	  if($this->rec_file_exists($speech_path.$speech_filename.$speech_extension)){
		  unlink($speech_path.$speech_filename.$speech_extension);
	  }
	  Verbose("Сбой при генерации фразы: ".urldecode($texttospeech)." код HTTP:".$http_code);
	  return null; // Мы не нашли файл записи и не смогли его сгенерировать
	} // text2speechYandex

}
/*
$settings= json_decode(file_get_contents(__DIR__.'/settings.json'), true);
$text	 = urlencode('Привет, как Твои дела?');

$TTS = new TextToSpeachYandex($settings);
$fname = $TTS->text2speechYandex($text);

echo "$fname";
*/

?>