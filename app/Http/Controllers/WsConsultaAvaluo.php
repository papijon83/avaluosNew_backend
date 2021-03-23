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
        try{
            $arrayRes = array();
            $authToken = $request->header('Authorization');
                if (!$authToken) {
                    return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
                }

                $resToken = Crypt::decrypt($authToken); 

                if (empty($resToken['Numero_Unico'])) {
                    return response()->json(['mensaje' => 'Numero_Unico Requerido'], 403);
                }
            
                $numeroUnico = (String)($resToken['Numero_Unico']);    
                $cuentaCat = (String)($resToken['Cuenta_Catast']);            
                //echo $numeroUnico." ".$cuentaCat; exit();
                if(trim($cuentaCat) == ''){
                    $cuentaCat = null;
                }

                if(trim($cuentaCat) != ''){
                    $region = substr($cuentaCat,0,3);
                    $manzana = substr($cuentaCat,3,3);
                    $lote = substr($cuentaCat,6,2);
                    $unidadPrivativa = substr($cuentaCat,8,3);
                    $digitoVerificador = substr($cuentaCat,11,1);
                }

                /*var_dump($numeroUnico);
                var_dump($cuentaCat); exit();*/
            
            $cursorAvaluos = null;
            $cursorDatos = null;

            $procedure = 'BEGIN
            FEXAVA.FEXAVA_AVALUOS_PKG.FEXAVA_SEL_V_INFODGPC_P(
                :PAR_NUMEROUNICO,
                :PAR_CUENTA,
                :c_avaluos,
                :c_avaluos_DAT
            ); END;';
            $pdo = DB::getPdo();
            $stmt = $pdo->prepare($procedure);
            $stmt->bindParam(':PAR_NUMEROUNICO', $numeroUnico, \PDO::PARAM_STR,2000);
            $stmt->bindParam(':PAR_CUENTA',$cuentaCat,\PDO::PARAM_STR,12);    
            $stmt->bindParam(':c_avaluos',$cursorAvaluos, \PDO::PARAM_STMT);
            $stmt->bindParam(':c_avaluos_DAT',$cursorDatos,\PDO::PARAM_STMT);
            $stmt->execute();
            oci_execute($cursorAvaluos, OCI_DEFAULT);
            oci_fetch_all($cursorAvaluos, $infoAvaluos, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursorAvaluos);

            oci_execute($cursorDatos, OCI_DEFAULT);
            oci_fetch_all($cursorDatos, $infoDatos, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursorDatos);
            $stmt->closeCursor();
            $pdo->commit();
            $pdo->close();

            DB::commit();
            DB::reconnect();
            //print_r($infoAvaluos); exit();
            unset($infoAvaluos[0]['FOLIO_REAL']);
            unset($infoAvaluos[0]['ANTECEDENTE_REGISTRAL']);
            $arrayRes['AVALUOS_CONSULTA_GETAVALUOS_p'] = $infoAvaluos;
            $arrayRes['AVALUO'] = $infoDatos;    
            
            return response()->json($arrayRes, 200); 
        } catch (\Throwable $th) {
            error_log($th);
            Log::info($th);            
            return response()->json(['mensaje' => 'No se ha encontrado ningún dato'], 500);
        } 
        

    }

    public function avaluosVista($idAvaluo){
        
        $infoAValuoVista = DB::select("SELECT * FROM FEXAVA.FEXAVA_AVALUOS_V WHERE IDAVALUO = $idAvaluo");
        return $infoAValuoVista[0];

    }
}