<?php
/**
 *  titolo: Sardegnapoi_Bot
 *  autore: Matteo Enna
 *  licenza GPL3
 **/

define('BOT_TOKEN', '<token>');
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

$content = file_get_contents("php://input");
$update = json_decode($content, true);
$chatID = $update["message"]["chat"]["id"];
		
$benvenuto="Benvenuto su Punti di interesse in Sardegna, \ndigitando il nome di un comune o inviando la tua posizione visualizzerai l'elenco di tutti i luoghi di interesse. \n \nIl bot è stato realizzato utilizzando gli Opendata messi a disposizione da Sardegna Territorio. \n \nRealizzato da Matteo Enna, \nRilasciato sotto licenza GPL3, potete trovare il progetto su: http://matteoenna.it/punti-interesse-sardegna-bot-telegram";
$help ="Digita il nome del comune e invia il messaggio oppure invia la tua posizione. \n\nPer qualsiasi dubbio, informazione o chiarimento puoi scrivermi su telegram @matteoenna oppure mandarmi una mail: matteo.enna89@gmail.com";
		
if (!array_key_exists('text', $update["message"]) && !array_key_exists('location', $update["message"])) {
	$type_ns="Formato non supportato, digita il nome di un comune Sardo o invia la tua posizione";
    $sendto =API_URL."sendmessage?chat_id=".$chatID."&text=".urlencode($type_ns);
	file_get_contents($sendto);
	die();
}

if(array_key_exists('location', $update["message"])){
	
	$bidda=array(
		"latitude"	=> $update["message"]["location"]["latitude"],
		"longitude"	=> $update["message"]["location"]["longitude"],
	);
}else{
	$bidda=$update["message"]["text"];
}

$reply =  sendMessage($bidda,$chatID);
		
header("Content-Type: application/json");
$parameters = array('chat_id' => $chatID, "text" => $reply['testo']);
$parameters["method"] = "sendMessage";
$parameters["reply_markup"] = '{ "keyboard": '.$reply['key'].'}';

echo json_encode($parameters);

function sendMessage($bidda, $chatID){
	
		
	$benvenuto="Benvenuto su Sardegna e Archeologia, \ndigitando il nome di un comune o inviando la tua posizione visualizzerai l'elenco di tutti i luoghi di interesse di carattere storico o archeologico. \n \nIl bot è stato realizzato utilizzando gli Opendata messi a disposizione de Nurnet. \n \nRealizzato da Matteo Enna, \nRilasciato sotto licenza GPL3, potete trovare il progetto su GitHub: https://github.com/Ellusu/nuraghebot";
	$help ="Digita il nome del comune e invia il messaggio oppure invia la tua posizione. \n\nPer qualsiasi dubbio, informazione o chiarimento puoi scrivermi su telegram @matteoenna oppure mandarmi una mail: matteo.enna89@gmail.com";

	if(strlen($bidda)<4 && !is_array($bidda)){
		$sendto =API_URL."sendmessage?chat_id=".$chatID."&text=".urlencode("Zona non trovata");
		file_get_contents($sendto);
		die();
	}
	
	$risultati =array();
	
	$simple = file_get_contents("data/poi.csv");
	
	$righe=explode(chr(10),$simple);
	
	$search_mode = FALSE;
	
	if (is_array($bidda))
	{
		$sendto =API_URL."sendmessage?chat_id=".$chatID."&text=Cerco per coordinate";
		file_get_contents($sendto);
		
		$search_mode =TRUE;
		
		foreach($righe as $s){
			$response =array();
			$col = explode(';',$s);
			
			$col[3]=str_replace('"','',$col[3]);
			$col[1]=str_replace('"','',$col[1]);
			
			$lat   =  str_replace('"', '', $col[3]);
			$long  =  str_replace('"', '', $col[1]);
			
			if(($col[1]<$bidda["latitude"]+0.02 && $col[1]>$bidda["latitude"] - 0.02) && ($col[3]<$bidda["longitude"]+0.02 && $col[3]>$bidda["longitude"] - 0.02)){ /*|| stripos($col[2],$bidda) || stripos($col[5],$bidda)*/
				$response = array (
					'id'=>  str_replace('"', '', $col[4]),
					'tipo'=>  str_replace('"', '', $col[0]),
					'comune'=>  str_replace('"', '', $col[9]),
					'lat'=>  $lat,
					'long'=>  $long,
					'nome'=>  str_replace('"', '', $col[5]),
					'provincia'=>  str_replace('"', '', $col[8]),
					'zona'=>  str_replace('"', '', $col[2]),   
					//'gmaps'=>  'https://www.google.com/maps/place/'.$long.'+'.$lat.'/@'.$long.','.$lat.',15z'
				);
				$risultati[]=$response;
			}
			
		}
	}elseif($bidda=="/start" || $bidda=="Inizio"){
		$testo = $benvenuto;
	}elseif($bidda=="/help" || $bidda=="Aiuto"){
		$testo = $help;	
	}elseif(stripos($bidda, "Mappa")!==FALSE){
		foreach($righe as $s){
			$response =array();
			$col = explode(';',$s);
			$id =str_ireplace("Mappa: ","",$bidda);
			if($col[4]=='"'.$id.'"') {
				$lat   =  str_replace('"', '', $col[3]);
				$long  =  str_replace('"', '', $col[1]);
				$testo = 'https://www.google.com/maps/place/'.$long.'+'.$lat.'/@'.$long.','.$lat.',15z';
			}
			
		}
	}elseif(stripos($bidda, "Scheda")!==FALSE){
		foreach($righe as $s){
			$response =array();
			$col = explode(';',$s);
			$id =str_ireplace("Scheda: ","",$bidda);
			if($col[4]==$id) {
				$testo = $col[7];
			}
			
		}
		
	}elseif($bidda=="Altri bot"){
		$lista =array(
			'Storia e archeologia in Sardegna: @nuragheBot',
			'Biblioteche in Sardegna: @sardegnabiblioteche_bot',
			'Trasporti pubblici della Sardegna: @sardegnatrasportibot',
			'Musica libera con jamendo: @jamendo_SearchBot',
			'Prontuario da bar: @Cumbido_bot',
			'A Christmas Carol: @achristmascarol_bot'
		);
		$testo = implode(chr(10),$lista);	
	}else{		
		$sendto =API_URL."sendmessage?chat_id=".$chatID."&text=Cerco ".$bidda;
		file_get_contents($sendto);
		
		$search_mode =TRUE;
		$i=0;
		foreach($righe as $s){
			$response =array();
			$col = explode(';',$s);
			
			if(stripos($col[5],$bidda) || stripos($col[10],$bidda)){
				$lat   =  str_replace('"', '', $col[3]);
				$long  =  str_replace('"', '', $col[1]);
				
				$response = array (
					'id'=>  str_replace('"', '', $col[4]),
					'tipo'=>  str_replace('"', '', $col[0]),
					'comune'=>  str_replace('"', '', $col[9]),
					'lat'=>  $lat,
					'long'=>  $long,
					'nome'=>  str_replace('"', '', $col[5]),
					'provincia'=>  str_replace('"', '', $col[8]),
					'zona'=>  str_replace('"', '', $col[2]),   
					//'gmaps'=>  'https://www.google.com/maps/place/'.$long.'+'.$lat.'/@'.$long.','.$lat.',15z'
				);
				$risultati[]=$response;
				$i++;
				
				
			}
			
		}
	}
	$acapo="\n";
	$sug = array();
	$sug[] = '["Inizio","Aiuto"]';
	if($search_mode) {
		$tot = count($risultati);
		$testo = '';
		foreach ($risultati as $k => $res){
			//$link = array();
			$testo .= $res['id'].' - '.$res['nome'];
			$testo .= $acapo;
			$testo .= $res['tipo'];
			$testo .= $acapo;
			$testo .= $res['comune'].' ('.$res['provincia'].')';
			//$testo .= $acapo;
			//$testo .= $res['gmaps'];
			$testo .= $acapo;
			$testo .= ($k+1).'/'.$tot;
			$testo .= $acapo;
			$testo .= $acapo;
			
			$link = array(
					'"Mappa: '.$res['id'].'"',
					'"Scheda: '.$res['id'].'"'
			);
			/*
			 :globe_with_meridians: 
			 :bookmark_tabs: 
			*/
			
			$sug[] = '['.implode(",",$link).']';
		}
	}
	
	$sug[] = '["Altri bot"]';
	$testo = $testo ? $testo : "Nessun risultato";
	return array(
		'testo'=> $testo,
		'key' => '['.implode(',',$sug).']'
	);
}

?>
