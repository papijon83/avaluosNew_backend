<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;
use App\Http\ThirdParty\SolucionIdeas;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Hamcrest\Arrays\IsArray;
use Log;

class WebhookController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    //protected $modelReimpresionNuevo;
    private $errors;    
    private $file;

    public function __construct()
    {
        //
    }

    public function recibeToken(Request $request)
    { Log::info($request);
        try{  
            $arrayRes = array();
            $arrayRes['id'] = $request->input('id');
            $arrayRes['folio_avaluo'] = $request->input('folio_avaluo');
            $arrayRes['token'] = $request->input('token');

            $arrayRes['idq'] = $request->query('id');
            $arrayRes['folio_avaluoq'] = $request->query('folio_avaluo');
            $arrayRes['tokenq'] = $request->query('token');
            /*foreach($request as $id => $req){
                if($id == "query" || $id == "input"){
                    foreach($req as $idInput => $elementoInput){
                        $arrayRes[$idInput] = $elementoInput;
                    }
                }    
            } */           
            //echo json_encode($arrayRes); exit();
            $nombreArchivo = "Token".date('Ymd').".txt";
            $rutaArchivos = getcwd()."/Tokens/";
            $file = fopen($rutaArchivos."/".$nombreArchivo, "a+");
            fwrite($file,json_encode($arrayRes)."\n");
            fclose($file);

            return response()->json(['Estado' => 'Correcto'], 200);

        }catch (\Throwable $th){
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error en la recepción'], 500);
        }
        
        
    }
    
}