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

    public function recibeAvaluo($file, $folio_Interno, $idUsuario, $usuario, $password)
    {  
        $myfile = fopen($file, "r");
        $contents = fread($myfile, filesize($file));   echo $contents; exit();
        fclose($myfile);

        $client = new \nusoap_client(env("WSDL_SOLUCION"), 'wsdl');
        
        $authHeaders = $client->getHeader(); //var_dump($authHeaders); exit();
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
        $res = $client->call('BandejaAvaluoXML', array('AvaluoXML'=>$contents,'Folio_Interno'=>$folio_Interno,'Folio_Usuario'=>$idUsuario));
        error_log(json_encode($res)); echo "SOY LA RESPUESTA ".var_dump($res); exit();
        return $res;
    }    
}