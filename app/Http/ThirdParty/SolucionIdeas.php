<?php

namespace App\Http\ThirdParty;
use Log;

class SolucionIdeas
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function recibeAvaluo($file, $folio_Interno, $idUsuario)
    {  
        try{
            
            $resGeneraToken = $this->obtenTokenIdeas($folio_Interno);
            //$resGeneraToken = 'OK'; 
            if($resGeneraToken == 'OK'){
                sleep(10);
                $path = storage_path();
                $rutaArchivos = $path."/Tokens/";
                $nombreArchivo = "Token".date('Ymd').".txt";
                $rutaArchivos = $rutaArchivos.$nombreArchivo; 
                $contenidoTokens = file($rutaArchivos);
                
                foreach($contenidoTokens as $contenidoToken){
                    //$arrToken = json_decode($contenidoTokens);
                    if(trim($contenidoToken) != ''){
                        $objToken = json_decode($contenidoToken);                        
                        if($objToken->folio_avaluo == $folio_Interno){
                            $token = $objToken->token;
                        }
                    }    
                                      
                }
                
            }

            $usuario = base64_encode(env("USUSOLUCIONQA"));
            $password = base64_encode(env("PASSOLUCIONQA"));

            $myfile = fopen($file, "r");
            $contents = fread($myfile, filesize($file));
            fclose($myfile); 

            $client = new \nusoap_client(env("WSDL_SOLUCION"), 'wsdl');            
            /*$authHeaders = $client->getHeader(); 
            if(isset($authHeaders['usuario']) && isset($authHeaders['contrasenia'])){*/
                
                /*$header = '<usuario xmlns="http://IDEAS.Avametrica">'.$usuario.'</usuario>';
                $header .= '<contrasenia xmlns="http://IDEAS.Avametrica">'.$password.'</contrasenia>';*/
                
                $header = '<SecurityHeader xmlns=\"http://IDEAS.Avametrica\"><usuario>'.$usuario.'</usuario><contrasenia>'.$password.'</contrasenia></SecurityHeader>';
                $client->setHeaders($header);
            /*}
            else{    
                $client->setCredentials($usuario,$password,'basic');
            }*/

            $token = "8B14CDD1-7113-4F73-84F5-1924DBB490F2";

            $client->soap_defencoding = 'UTF-8';
            $client->decode_utf8 = FALSE;
            //$res = $client->call('WS_Recibe_Avaluo', array('AvaluoXML'=>$contents,'Folio_Interno'=>$folio_Interno,'Folio_Usuario'=>$idUsuario));
            $datos = array('AvaluoXML'=>$contents,'Folio_Interno'=>$folio_Interno,'Folio_Usuario'=>$idUsuario,'token'=>$token);
            $res = $client->call('BandejaAvaluoXML', array('datos'=>$datos));
            //error_log(json_encode($res)); 
            return $res;
        }catch (\Throwable $th){
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error en el servidor'], 500);
        }
    }
    
    public function actualizarEnAvaluoXML($folio_Interno, $fecha_Pago, $monto_Pago, $folio_Usuario, $linea_Captura){

        $usuario = base64_encode(env("USUSOLUCIONQA"));
        $password = base64_encode(env("PASSOLUCIONQA"));

        $client = new \nusoap_client(env("WSDL_SOLUCION"), 'wsdl');    
        $authHeaders = $client->getHeader();
        if(isset($authHeaders['usuario']) && isset($authHeaders['contrasenia'])){
            $header = '<soapenv:Header><usuario xmlns="IDEAS.Avametrica">'.$usuario.'</usuario>';
            $header .= '<contrasenia xmlns="http://IDEAS.Avametrica">'.$password.'</contrasenia></soapenv:Header>'; 
            $client->setHeaders($header);
        }
        else{   
            $client->setCredentials($usuario,$password,'basic');
        }        

        $client->setCredentials($usuario,$password);
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = FALSE;
        //$res = $client->call('WS_Recibe_Avaluo', array('AvaluoXML'=>$contents,'Folio_Interno'=>$folio_Interno,'Folio_Usuario'=>$idUsuario));
        $res = $client->call('ActualizarEnAvaluoXML', array('Folio_Interno'=>$folio_Interno,'Fecha_Pago'=>$fecha_Pago,'Monto_Pago'=>$monto_Pago,'Folio_Usuario'=>$folio_Usuario,'Linea_Captura'=>$linea_Captura));
        //error_log(json_encode($res)); 
        return $res;
    }

    public function obtenTokenIdeas($folio_Interno){
        //try{
            $wsdl = "https://serviciosqa.solucionideas.com/WS_Recibe_Avaluo.svc?wsdl";
            
            $headerVar = new \SoapVar('<usuario xmlns="IDEAS.Avametrica">VXNycHJ1ZWJhcw==</usuario><contrasenia xmlns="IDEAS.Avametrica">cWE3MzUwcjNybGFjNm14LTQ1</contrasenia><wsa:Action>http://tempuri.org/IWS_Recibe_Avaluo/obtenertoken</wsa:Action><wsa:MessageID>uuid:627096bd-4441-4ae9-b62c-c8d6aabfa3ed</wsa:MessageID><wsa:To>https://serviciosqa.solucionideas.com/WS_Recibe_Avaluo.svc</wsa:To>',XSD_ANYXML);
            $header = new \SoapHeader('http://tempuri.org/','Header',$headerVar);
            
            $client = new \SoapClient($wsdl, array("trace" => 1, "exception" => 0));
            
            $client->__setSoapHeaders($header);
            try{
                $response = $client->__soapCall("obtenertoken", ['obtenertoken'=>['folio_avaluo' => $folio_Interno]]);
                dd($response);
            }catch(Exception $e){
                dd($client);
            }
            /*$usuario = base64_encode(env("USUSOLUCIONQA"));
            $password = base64_encode(env("PASSOLUCIONQA"));

            $client = new \nusoap_client(env("WSDL_SOLUCION"), 'wsdl');

            $header = '<usuario xmlns="IDEAS.Avametrica">VXNycHJ1ZWJhcw==</usuario><contrasenia xmlns="IDEAS.Avametrica">cWE3MzUwcjNybGFjNm14LTQ1</contrasenia> <wsa:Action>http://tempuri.org/IWS_Recibe_Avaluo/obtenertoken</wsa:Action><wsa:MessageID>uuid:627096bd-4441-4ae9-b62c-c8d6aabfa3ed</wsa:MessageID><wsa:To>https://serviciosqa.solucionideas.com/WS_Recibe_Avaluo.svc</wsa:To>';
            $client->setHeaders($header);

            
            $client->soap_defencoding = 'UTF-8';
            $client->decode_utf8 = FALSE;
            
            $res = $client->call('obtenertoken', array('Folio_Interno'=>$folio_Interno));
            
            return 'OK'; 
        }catch (\Throwable $th){
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error al obtener el token'], 500);
        }*/
    }
}