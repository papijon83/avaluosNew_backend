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

    public function recibeAvaluo($pregunta,$usuario,$password)
    {  
        $client = new \nusoap_client(env("WSDL_SOLUCION"), 'wsdl');
        //$authHeaders = $client->getHeader();
        $client->setCredentials($usuario,$password,'basic');
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = FALSE;
        $res = $client->call('WS_Recibe_Avaluo', $pregunta);
        error_log(json_encode($res));
        return $res;
    }    
}