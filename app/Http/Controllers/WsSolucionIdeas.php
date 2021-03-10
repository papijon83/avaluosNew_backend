<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;
use App\Http\ThirdParty\SolucionIdeas;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Hamcrest\Arrays\IsArray;
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

    public function __construct()
    {
        //
    }

    public function wsRecibeAvaluo(Request $request)
    {
        try{
            
            /*$authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            }

            $resToken = Crypt::decrypt($authToken); 

            if (empty($resToken['idUsuario'])) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            }
           
            $idUsuario = $resToken['idUsuario'];

            $file = $request->file('files');   */
            $folio_Interno = $request->input('numeroUnico');
            $idUsuario = $request->input('idUsuario');
            $file = $request->input('files');
            $contents = base64_decode($file);
            
            $nombreArchivo = $folio_Interno.".txt";
            $rutaArchivos = getcwd()."/XMLS/";
            $fileXml = fopen($rutaArchivos."/".$nombreArchivo, "w");
            fwrite($fileXml,$contents);
            fclose($fileXml);

            return response()->json(['Estado' => 'Recibido'], 200);

            /*$solucion = new SolucionIdeas;
            $response = $solucion->recibeAvaluo($file, $folio_Interno, $idUsuario);

            return response()->json(['Estado' => $response], 200);*/
            
        }catch (\Throwable $th){
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error en el consumo del servicio BandejaAvaluoXML'], 500);
        }
        
        
    }


    public function wsRecibeAvaluoMi(Request $request)
    {
        try{
            
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

            $solucion = new SolucionIdeas;
            $response = $solucion->recibeAvaluo($file, $folio_Interno, $idUsuario);

            return response()->json(['Estado' => $response], 200);
            
        }catch (\Throwable $th){
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error en el consumo del servicio BandejaAvaluoXML'], 500);
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
        $response = $solucion->recibeAvaluo($folio_Interno, $fecha_Pago, $monto_Pago, $folio_Usuario, $linea_Captura);

        return response()->json(['Estado' => $response], 200);
       
        
    }

    public function getToken(Request $request){ 
        $idUsuario = $request->input('idUsuario');
        $numeroUnico = $request->input('numeroUnico');
        $fecha_Pago = $request->input('fechaPago');
        $monto_Pago = $request->input('montoPago');    
        $linea_Captura = $request->input('lineaCaptura');

        if(isset($idUsuario) && isset($numeroUnico)){
            $token = Crypt::encrypt(['numeroUnico'=>$numeroUnico,'idUsuario'=>$idUsuario]);
        }

        if(isset($idUsuario) && isset($numeroUnico) && isset($fecha_Pago) && isset($monto_Pago) && isset($linea_Captura)){
            $token = Crypt::encrypt(['numeroUnico'=>$numeroUnico,'idUsuario'=>$idUsuario, 'fechaPago'=>$fecha_Pago, 'montoPago'=>$monto_Pago, 'lineaCaptura'=>$linea_Captura]);
        }
        
        return response()->json($token, 200);
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

        return $token;
    }
}