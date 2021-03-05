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
            /*echo $contents."<<>>".$folio_Interno."<<>>".$idUsuario."<<>>".$usuario."<<>>".$password;
            exit();*/

        }catch (\Throwable $th){
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error en el servidor'], 500);
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
       /*echo $contents."<<>>".$folio_Interno."<<>>".$idUsuario."<<>>".$usuario."<<>>".$password;
        exit();*/
        
    }

    public function getToken(Request $request){    
        $idUsuario = $request->input('idUsuario');
        $numeroUnico = $request->input('numeroUnico');
        $fecha_Pago = $request->input('fechaPago');
        $monto_Pago = $request->input('montoPago');    
        $linea_Captura = $request->input('lineaCaptura');

        if(isset($idUsuario) && isset($numeroUnico)){
            $token = Crypt::encrypt(['numeroUnico'=>$idUsuario,'idUsuario'=>$numeroUnico]);
        }

        if(isset($idUsuario) && isset($numeroUnico) && isset($fecha_Pago) && isset($monto_Pago) && isset($linea_Captura)){
            $token = Crypt::encrypt(['numeroUnico'=>$idUsuario,'idUsuario'=>$numeroUnico, 'fechaPago'=>$fecha_Pago, 'montoPago'=>$monto_Pago, 'lineaCaptura'=>$linea_Captura]);
        }
        
        return response()->json($token, 200);
    }
}