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

class WsConsultaAvaluo extends Controller
{
    private $errors;
    private $doc;
    private $fileXML;
    protected $modelDocumentos;

    public function __construct()
    {
        
    }

    public function WS_ConsultaAvaluo(Request $request){

        $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            }

            $resToken = Crypt::decrypt($authToken); 

            if (empty($resToken['Numero_Unico'])) {
                return response()->json(['mensaje' => 'Numero_Unico Requerido'], 403);
            }
           
            $numeroUnico = $resToken['Numero_Unico'];    
            $cuentaCat = $resToken['Cuenta_Catast'];
            if(trim($cuentaCat) != ''){
                $region = substr($cuentaCat,0,3);
                $manzana = substr($cuentaCat,3,3);
                $lote = substr($cuentaCat,6,2);
                $unidadPrivativa = substr($cuentaCat,8,3);
                $digitoVerificador = substr($cuentaCat,11,1);
            }
        
    }

    public function avaluosVista($idAvaluo){
        
        $infoAValuoVista = DB::select("SELECT * FROM FEXAVA.FEXAVA_AVALUOS_V WHERE IDAVALUO = $idAvaluo");
        return $infoAValuoVista[0];

    }
}