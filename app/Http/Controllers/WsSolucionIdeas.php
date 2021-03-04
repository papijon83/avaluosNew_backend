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
        $myfile = fopen($file, "r");
        $contents = fread($myfile, filesize($file));   
        fclose($myfile);

        /*$file = $request->input('files');
        $contents = base64_decode($file);*/
        $folio_Interno =$resToken['numeroUnico'];
        $usuario = base64_encode(env("USUSOLUCION"));
        $password = base64_encode(env("PASSOLUCION"));

        $pregunta = ['AvaluoXML' => $file,'Folio_Interno' => $folio_Interno, 'Folio_usuario' => $idUsuario];

        $solucion = new SolucionIdeas;
        $response = $solucion->recibeAvaluo($pregunta,$usuario,$password);

        return response()->json(['Estado' => $response], 200);
       /*echo $contents."<<>>".$folio_Interno."<<>>".$idUsuario."<<>>".$usuario."<<>>".$password;
        exit();*/
        
    }

    public function getToken(Request $request){    
        $idUsuario = $request->input('idUsuario');
        $numeroUnico = $request->input('numeroUnico');
        $token = Crypt::encrypt(['numeroUnico'=>$idUsuario,'idUsuario'=>$numeroUnico]);
        return response()->json($token, 200);
    }
}