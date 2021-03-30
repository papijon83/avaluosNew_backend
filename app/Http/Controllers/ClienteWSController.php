<?php

namespace App\Http\Controllers;


class ClienteWSController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
    }

    public function sendAvaluo($folio)
    {

        $usuario = "U4566";
        $auth = '<usuario xmlns="IDEAS.Avametrica">' . env('USUARIO_WSDL') . '</usuario>';
        $auth .= '<contrasenia xmlns="IDEAS.Avametrica">' . env('PASS_WSDL') . '</contrasenia>';
        $file = storage_path('app/24042020_PRUEBA SAF.xml');
        $xml = simplexml_load_file($file);
        $client = new \SoapClient(env('WSDL_RECIBE_AVALUO'), ["debug" => true, "trace" => true, "exception" => true]);
        $auth_block = new \SoapVar($auth, XSD_ANYXML, NULL, NULL, NULL, NULL);
        $header = new \SoapHeader('http://schemas.xmlsoap.org/soap/envelope/', 'Header', $auth_block);
        $client->__setSoapHeaders($header);
        try {
            $client->__soapCall('obtenertoken', ['obtenertoken' => ['folio_avaluo' => $folio]]);
        } catch (\Exception $e) {
        }
        $restClient = new \GuzzleHttp\Client();
        $response = $restClient->request('POST', env('API_WEBHOOK') . $folio);

        if ($response->getStatusCode() == 200) {
            try {
                $res = json_decode($response->getBody());

                $enviado = $client->__soapCall('BandejaAvaluoXML', ['BandejaAvaluoXML' => [
                    'datos' => [
                        'AvaluoXML' => $xml->asXML(),
                        'Folio_Interno' => $folio,
                        'Folio_Usuario' => $usuario,
                        'token' => $res->token,
                    ]
                ]]);
                if($enviado->BandejaAvaluoXMLResult){
                    return response()->json(['mensaje' => 'El avalúo fue entregado a la bandeja'], 200);
                }else{
                    return response()->json(['mensaje' => 'El avalúo no pudo ser entregado'], 400);
                }
                
            } catch (\Exception $e) {
                return response()->json(['mensaje' => $e], 500);
            }
        } else {
            return response()->json($response->getBody(), $response->getStatusCode());
        }
    }

    public function token($folio)
    {

        $usuario = "U4566";
        $auth = '<usuario xmlns="IDEAS.Avametrica">' . env('USUARIO_WSDL') . '</usuario>';
        $auth .= '<contrasenia xmlns="IDEAS.Avametrica">' . env('PASS_WSDL') . '</contrasenia>';
        //$file = storage_path('app/24042020_PRUEBA SAF.xml');
        //$xml = simplexml_load_file($file);
        $client = new \SoapClient(env('WSDL_RECIBE_AVALUO'), ["debug" => true, "trace" => true, "exception" => true]);
        $auth_block = new \SoapVar($auth, XSD_ANYXML, NULL, NULL, NULL, NULL);
        $header = new \SoapHeader('http://schemas.xmlsoap.org/soap/envelope/', 'Header', $auth_block);
        $client->__setSoapHeaders($header);
        try {
            $client->__soapCall('obtenertoken', ['obtenertoken' => ['folio_avaluo' => $folio]]);
        } catch (\Exception $e) {
        }
        $restClient = new \GuzzleHttp\Client();
        $response = $restClient->request('POST', env('API_WEBHOOK') . $folio);

        if ($response->getStatusCode() == 200) {
            try {
                echo "ENTREEEEEE"; exit();
                
            } catch (\Exception $e) {
                return response()->json(['mensaje' => $e], 500);
            }
        } else {
            return response()->json($response->getBody(), $response->getStatusCode());
        }
    }
}
