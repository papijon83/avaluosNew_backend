<?php

namespace App\Http\ThirdParty;

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
            $usuario = base64_encode(env("USUSOLUCIONQA"));
            $password = base64_encode(env("PASSOLUCIONQA"));

            $myfile = fopen($file, "r");
            $contents = fread($myfile, filesize($file));
            fclose($myfile);

            $client = new \nusoap_client(env("WSDL_SOLUCION"), 'wsdl');
            var_dump($client);    
            $authHeaders = $client->getHeader(); //var_dump($authHeaders); exit();
            if(isset($authHeaders['usuario']) && isset($authHeaders['contrasenia'])){
                $header = '<usuario>'.$usuario.'</usuario>';
                $header .= '<contrasenia>'.$password.'</contrasenia>'; //echo $header; exit();
                $client->setHeaders($header);
            }
            else{    
                $client->setCredentials($usuario,$password,'basic');
            }

            $client->soap_defencoding = 'ISO-8859-1';
            $client->decode_utf8 = FALSE;
            //$res = $client->call('WS_Recibe_Avaluo', array('AvaluoXML'=>$contents,'Folio_Interno'=>$folio_Interno,'Folio_Usuario'=>$idUsuario));
            $datos = array('AvaluoXML'=>$contents,'Folio_Interno'=>$folio_Interno,'Folio_Usuario'=>$idUsuario,'token'=>'');
            $res = $client->call('BandejaAvaluoXML', array($datos));
            //error_log(json_encode($res)); //echo "SOY LA RESPUESTA ".var_dump($res); exit();
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
            $header = '<usuario>'.$usuario.'</usuario>';
            $header .= '<contrasenia>'.$password.'</contrasenia>'; //echo $header; exit();
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
        //error_log(json_encode($res)); //echo "SOY LA RESPUESTA ".var_dump($res); exit();
        return $res;
    }
}