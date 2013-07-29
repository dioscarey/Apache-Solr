<?php
header('Content-Type: text/html; charset=utf-8');
header('Expires: Wed, 29 Aug 2012 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');


//Request
/*

	Las peticiones que se hacen desde AJAX puede ser search, delete e insert. Las demás funciones en la clase SOLR
	sobran para mi proposito de demostrar su fincionamienot, normalmente estás funciones son más usasdas. 

	Claramente se pued emodificar este archivo a las necesidades de cada quien.

*/


if(isset($_GET['request'])){
	$solr = new Solr('http://localhost:8983/solr/');
	switch($_GET['request']){
		case 'search':
			$arrayResult = $solr ->search($_GET["q"]);
			echo $arrayResult;
		break;
		case 'delete':
			if($solr->delete_by_id($_GET["d"]))	
				if($solr->commit())	
					echo '{"success":"true"}';	
				else echo 	'{"success":"false"}';
			else echo 	'{"success":"false"}';
		break;
		case 'insert':
		$num_register_user = $_GET['num_register_user'];
		$id_bus = $_GET['id_bus'];
		$num_bus = $_GET['num_bus'];
		$name_bus = $_GET['name_bus'];
		$mainpic_bus = $_GET['mainpic_bus'];
		$slogan_bus = $_GET['slogan_bus'];

			$doc = array(
				'num_register_user' => $num_register_user,
				'id_bus' => $id_bus,
				'num_bus' => $num_bus,
				'name_bus' => $name_bus,
				'mainpic_bus' => $mainpic_bus,
				'slogan_bus' => $slogan_bus
			);	
			if($solr->add_document($doc))
				if($solr->commit())
					echo '{"success":"true"}';
				else echo 	'{"success":"false"}';
			else echo 	'{"success":"false"}';
		break;

	}
}
	// Solr clase
/*
	Ene sta clase de puede Agregar, eliminar, optimiza, escribir, buscar, etc..
	La función privada POST es para realizar las acciones mediatne HTTP

	La función arr_to_solr_doc cambia un arreglo a un formato XML para posteriormente ser indexado.
	En esta función se puede hacer también con JSON, solamente es modfiicar.

*/
	class Solr {
 		
		function __construct($url){
			$this->url = $url;
			$this->httppost = TRUE;
		}
 	
		function arr_to_solr_doc($doc){
			$fields = '';
			foreach ($doc as $field_name => $value){
				$fields .= sprintf('<field name="%s">%s</field>',$field_name, $value);
			}
			return sprintf('<add><doc>%s</doc></add>',$fields);
		}
 
		private function post($xml){
			// Curl es una herramienta para usar en un intérprete de comandos para transferir archivos con sintaxis URL,
			// soporta FTP, FTPS, HTTP, HTTPS, TFTP, SCP, SFTP, Telnet, DICT, FILE y LDAP. cURL soporta certificados HTTPS, 
			// HTTP POST, HTTP PUT, subidas FTP

			//Chequense ene esta liga para más información
			//http://www.php.net/manual/es/book.curl.php
			$ch = curl_init();
			$post_url = $this->url.'update';
 
			$header = array("Content-type:text/xml; charset=utf-8");
			curl_setopt($ch, CURLOPT_URL, $post_url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
			curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
			curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
 
			$data = curl_exec($ch);
 
			if (curl_errno($ch)) {
			   throw new Exception ( "curl_error:" . curl_error($ch) );
			} else {
			   curl_close($ch);
			   return TRUE;
			}
		}
 
		function fetch_data($query){		
			$search_url = $this->url.'select';
 			//Esta variable puede ser modificada según sus necesidades. Ene este caso use highlighitng para marcar las palabras encontradas con la etiqueta STRONG
 			// y solamente mostraré el nombre del negocio y el slogan.
			$querystring = "stylesheet=&q=".trim(urlencode($query))."&version=4.0&hl=true&hl.fl=name_bus,slogan_bus&wt=json&hl.simple.pre=<strong>&hl.simple.post=<%2Fstrong>";
 
		        $selecturl = '';
		        if (!$this->httppost) $selecturl = "/?$querystring";
			$search_url .= $selecturl;
 			//En es´sta sección es para mostrar los archivos en JSON. Pero puede ser también en XML.
			$header[] = "Accept: text/json,application/json,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
			$header[] = "Accept-Language: en-us,en;q=0.5";
			$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
 
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $search_url); // set url to post to
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_ENCODING,"");          
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,10);
			curl_setopt($ch, CURLOPT_DNS_USE_GLOBAL_CACHE, 0);
 
			if ($this->httppost) {
				curl_setopt($ch, CURLOPT_POST, 1 );
				curl_setopt($ch, CURLOPT_POSTFIELDS,$querystring);
			}
 
			$data = curl_exec($ch);
 
			if (curl_errno($ch)) {

				throw new Exception(curl_error($ch));
			} else {
				curl_close($ch);
				//if ( strstr ( $data, '<status>0</status>')) {
					return $data;
				/*}
				else
					{
						echo "false";}
				*/
			} 
		}
 
		function handle_response($data) {
			if ($data) {
				$results =  $data;
			} else {
				$results=false;
			}
			return $results;
		}
 
		function search($query){
			$xml = $this->fetch_data( $query );
			return $this->handle_response($xml);
		}
 
		function commit(){
			return $this->post('<commit/>');
		}
 
		function optimize(){
			return $this->post('<optimize/>');
		}
 
		function add_document($document){
			$xml = $this->arr_to_solr_doc($document);
			return $this->post($xml);
		}	
 
		function delete_by_id($id){
			return $this->post(sprintf('<delete><id>%s</id></delete>', $id));
		}
 

	}



?>