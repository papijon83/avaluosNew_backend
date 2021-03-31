<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Hamcrest\Arrays\IsArray;
use App\Models\Documentos;

class ClienteWSController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    protected $modelDocumentos;

    public function __construct()
    {
    }

    public function sendAvaluo($folio)
    {

        $usuario = "264";
        $auth = '<usuario xmlns="IDEAS.Avametrica">' . env('USUARIO_WSDL') . '</usuario>';
        $auth .= '<contrasenia xmlns="IDEAS.Avametrica">' . env('PASS_WSDL') . '</contrasenia>';
        $file = storage_path('app/A-COM-2021-13869.xml');
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

                $this->modelDocumentos = new Documentos();
                $idAvaluo = $this->modelDocumentos->get_idavaluo_db($folio);
               
                if($enviado->BandejaAvaluoXMLResult){                        
                    $response = $this->modelDocumentos->guardaResultado($idAvaluo, $usuario, $enviado->BandejaAvaluoXMLResult, 'El avalúo fue entregado a la bandeja');
                    return response()->json(['mensaje' => 'El avalúo fue entregado a la bandeja '.$response], 200);
                }else{                        
                    $response = $this->modelDocumentos->guardaResultado($idAvaluo, $usuario, $enviado->BandejaAvaluoXMLResult, 'El avalúo no pudo ser entregado');
                    return response()->json(['mensaje' => 'El avalúo no pudo ser entregado '.$response], 400);
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
                echo "ENTREEEEEE";
                
            } catch (\Exception $e) {
                return response()->json(['mensaje' => $e], 500);
            }
        } else {
            return response()->json($response->getBody(), $response->getStatusCode());
        }
    }
}
