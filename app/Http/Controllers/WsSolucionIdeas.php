<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;
use App\Http\ThirdParty\SolucionIdeas;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Hamcrest\Arrays\IsArray;
use App\Models\Documentos;
use Illuminate\Support\Facades\DB;
use Log;

class WsSolucionIdeas extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    //protected $modelReimpresionNuevo;
    private $errors;
    private $doc;
    private $fileXML;
    protected $modelDocumentos;

    public function __construct()
    {
        
    }

    public function wsRecibeAvaluo(Request $request)
    {
            $folio_Interno = $request->input('numeroUnico');
            $idUsuario = $request->input('idUsuario');
            $file = $request->input('files');
            $contents = base64_decode($file);       
                   
            $nombreArchivo = $folio_Interno.".xml";
            $path = storage_path();
            $rutaArchivos = $path."/XMLS/";        
            $fileXml = fopen($rutaArchivos."/".$nombreArchivo, "w");
            fwrite($fileXml,$contents);
            fclose($fileXml);

            /*$myfile = fopen($rutaArchivos."/".$nombreArchivo, "r");
            $contents = fread($myfile, filesize($file));
            fclose($myfile);*/

            //return response()->json(['Estado' => 'Recibido'], 200);
        
            $auth = '<usuario xmlns="IDEAS.Avametrica">' . env('USUARIO_WSDL') . '</usuario>';
            $auth .= '<contrasenia xmlns="IDEAS.Avametrica">' . env('PASS_WSDL') . '</contrasenia>';
            
            $client = new \SoapClient(env('WSDL_RECIBE_AVALUO'), ["debug" => true, "trace" => true, "exception" => true]);
            $auth_block = new \SoapVar($auth, XSD_ANYXML, NULL, NULL, NULL, NULL);
            $header = new \SoapHeader('http://schemas.xmlsoap.org/soap/envelope/', 'Header', $auth_block);
            $client->__setSoapHeaders($header);
            try {
                $client->__soapCall('obtenertoken', ['obtenertoken' => ['folio_avaluo' => $folio_Interno]]);
            } catch (\Exception $e) {
            }
            $restClient = new \GuzzleHttp\Client();
            $response = $restClient->request('POST', env('API_WEBHOOK') . $folio_Interno);
    
            if ($response->getStatusCode() == 200) {
                try {
                    $res = json_decode($response->getBody());
    
                    $enviado = $client->__soapCall('BandejaAvaluoXML', ['BandejaAvaluoXML' => [
                        'datos' => [
                            'AvaluoXML' => (String)($contents), //$xml->asXML(),
                            'Folio_Interno' => $folio_Interno,
                            'Folio_Usuario' => $idUsuario,
                            'token' => $res->token,
                        ]
                    ]]);

                    $this->modelDocumentos = new Documentos();
                    $idAvaluo = $this->modelDocumentos->get_idavaluo_db($folio_Interno);

                    if($enviado->BandejaAvaluoXMLResult){                        
                        $response = $this->modelDocumentos->guardaResultado($idAvaluo, $idUsuario, $enviado->BandejaAvaluoXMLResult, 'El avalúo fue entregado a la bandeja');
                        return response()->json(['mensaje' => 'El avalúo fue entregado a la bandeja '.$response], 200);
                    }else{                        
                        $response = $this->modelDocumentos->guardaResultado($idAvaluo, $idUsuario, $enviado->BandejaAvaluoXMLResult, 'El avalúo no pudo ser entregado');
                        return response()->json(['mensaje' => 'El avalúo no pudo ser entregado '.$response], 400);
                    }
                    
                } catch (\Throwable $th) {
                    error_log($th);
                    
                    return response()->json(['mensaje' => 'Error al consumir el servicio'], 500);
                }
            } else {
                return response()->json($response->getBody(), $response->getStatusCode());
            }
        
    }


    public function wsRecibeAvaluoMi(Request $request)
    {
        
            
            $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            }

            $resToken = Crypt::decrypt($authToken); 

            if (empty($resToken['idUsuario'])) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            }
           
            $idUsuario = $resToken['idUsuario'];

            $file = $request->file('files');   

            /*$file = $request->input('files');
            $contents = base64_decode($file);*/
            $folio_Interno =$resToken['numeroUnico'];            

            /*$solucion = new SolucionIdeas;
            $response = $solucion->recibeAvaluo($file, $folio_Interno, $idUsuario);*/

            $myfile = fopen($file, "r");
            $contents = fread($myfile, filesize($file));
            fclose($myfile);

        //$usuario = "U4566";
        $auth = '<usuario xmlns="IDEAS.Avametrica">' . env('USUARIO_WSDL') . '</usuario>';
        $auth .= '<contrasenia xmlns="IDEAS.Avametrica">' . env('PASS_WSDL') . '</contrasenia>';
        /*$file = storage_path('app/24042020_PRUEBA SAF.xml');
        $xml = simplexml_load_file($file);*/
        $client = new \SoapClient(env('WSDL_RECIBE_AVALUO'), ["debug" => true, "trace" => true, "exception" => true]);
        $auth_block = new \SoapVar($auth, XSD_ANYXML, NULL, NULL, NULL, NULL);
        $header = new \SoapHeader('http://schemas.xmlsoap.org/soap/envelope/', 'Header', $auth_block);
        $client->__setSoapHeaders($header);
        try {
            $client->__soapCall('obtenertoken', ['obtenertoken' => ['folio_avaluo' => $folio_Interno]]);
        } catch (\Exception $e) {
        }
        $restClient = new \GuzzleHttp\Client();
        $response = $restClient->request('POST', env('API_WEBHOOK') . $folio_Interno);

        if ($response->getStatusCode() == 200) {
            try {
                $res = json_decode($response->getBody());

                $enviado = $client->__soapCall('BandejaAvaluoXML', ['BandejaAvaluoXML' => [
                    'datos' => [
                        'AvaluoXML' => (String)($contents), //$xml->asXML(),
                        'Folio_Interno' => $folio_Interno,
                        'Folio_Usuario' => $idUsuario,
                        'token' => $res->token,
                    ]
                ]]);
                    $this->modelDocumentos = new Documentos();
                    $idAvaluo = $this->modelDocumentos->get_idavaluo_db($folio_Interno);

                    if($enviado->BandejaAvaluoXMLResult){                        
                        $response = $this->modelDocumentos->guardaResultado($idAvaluo, $idUsuario, $enviado->BandejaAvaluoXMLResult, 'El avalúo fue entregado a la bandeja');
                        return response()->json(['mensaje' => 'El avalúo fue entregado a la bandeja '.$response], 200);
                    }else{                        
                        $response = $this->modelDocumentos->guardaResultado($idAvaluo, $idUsuario, $enviado->BandejaAvaluoXMLResult, 'El avalúo no pudo ser entregado');
                        return response()->json(['mensaje' => 'El avalúo no pudo ser entregado '.$response], 400);
                    }
                //print_r($enviado->BandejaAvaluoXMLResult);
                //dd($client);
            } catch (\Throwable $th) {
                error_log($th);
                
                return response()->json(['mensaje' => 'Error al consumir el servicio'], 500);
            }
        } else {
            return response()->json($response->getBody(), $response->getStatusCode());
        }    
        
    }

    public function wsActualizarEnAvaluoXML(Request $request)
    {
        $authToken = $request->header('Authorization');
        if (!$authToken) {
            return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
        }

        $resToken = Crypt::decrypt($authToken); 

        if (empty($resToken['idUsuario'])) {
            return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
        }

        $idUsuario = $resToken['idUsuario'];

        $file = $request->file('files');   

        /*$file = $request->input('files');
        $contents = base64_decode($file);*/
        $folio_Interno =$resToken['numeroUnico'];
        $fecha_Pago =$resToken['fechaPago'];
        $monto_Pago =$resToken['montoPago'];
        $folio_Usuario =$resToken['idUsuario'];
        $linea_Captura =$resToken['lineaCaptura'];

        $solucion = new SolucionIdeas;
        //$response = $solucion->recibeAvaluo($folio_Interno, $fecha_Pago, $monto_Pago, $folio_Usuario, $linea_Captura);

        return response()->json(['Estado' => $response], 200);
       
        
    }

    public function getToken(Request $request){ 

        $numero_Unico = $request->query('Numero_Unico');
        $cuenta_Catast = $request->query('Cuenta_Catast');
        $usuario = $request->query('Usuario');
        $contrasenia = $request->query('Contrasenia');
        $proceso = $request->query('Proceso');
        
        
        if(isset($numero_Unico) && trim($numero_Unico) != '' && isset($cuenta_Catast) && isset($usuario) && trim($usuario) != '' && isset($contrasenia) && trim($contrasenia) != '' && trim($proceso) != ''){
            if(base64_decode($usuario) == env('USUCONSULTAVA') && base64_decode($contrasenia) == env('PASSCONSULTAVA')){
                $token = Crypt::encrypt(['Numero_Unico'=>$numero_Unico,'Cuenta_Catast'=>$cuenta_Catast,'Usuario'=>$usuario,'Contrasenia'=>$contrasenia,'Proceso'=>$proceso]);
                return response()->json($token, 200);
            }else{
                return response()->json(['mensaje'=>'Usuario o contraseña incorrectos'], 404);
            }
            
        }

        
    }

    public function obtenerTokenGuardado(Request $request){
        $folio_Interno = $request->input('folio_Interno');
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
        
        if($token){
            return response()->json(['token'=>$token], 200);
        }else{
            return response()->json(['mensaje'=>'No se encontró un token para ese folio'], 404);
        }
    }

    public function estadoEnvio(Request $request){
        $folio_Interno = $request->query('numeroUnico');
        $this->modelDocumentos = new Documentos();
        $idAvaluo = $this->modelDocumentos->get_idavaluo_db($folio_Interno);
        //echo "SOY IDASVALUO ".$idAvaluo."\n";
        if($idAvaluo){
            $registroRes = convierte_a_arreglo(DB::select("SELECT MENSAJE_RESPUESTA_WS FROM FEXAVA_ENVIOXMLWS WHERE IDAVALUO = ".$idAvaluo));
            if($registroRes){
                return "RESPUESTA: ".trim($registroRes[0]['mensaje_respuesta_ws']);
            }else{
                return "NO SE ENCUENTRA INFORMACION PARA EL NUMEROUNICO ".$folio_Interno;
            }
        }
    }
}