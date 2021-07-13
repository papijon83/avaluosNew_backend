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
            //$arrayRes['AVALUOS_CONSULTA_GETAVALUOS_p'] = $this->limpiaConsulta($infoAvaluos);
            $arrayRes['AVALUOS'] = $this->limpiarAvaluos($infoDatos,$infoAvaluos[0]['CUENTA']);
            //return response()->json(['mensaje' => 'No se ha encontrado ningún dato'], 500);
            return response()->json($arrayRes, 200); 
        } catch (\Throwable $th) {
            error_log($th);
            Log::info($th);            
            return response()->json(['mensaje' => 'No se ha encontrado ningún dato'], 500);
        } 
        

    }

    public function limpiaConsulta($info){
        //print_r($info);
        $arrMostrar = array('CUENTA');
        foreach($info[0] as $llave => $valor){
            if(!in_array($llave, $arrMostrar)){
                unset($info[0][$llave]);
            }else{
                $info[0][$llave] = trim($valor); 
            }
        }
        return $info;
    }

    public function limpiarAvaluos($info,$cuenta){
        $arrMostrar = array('NUMERO_UNICO_AVALUO','MONTO_AVALUO');
        //print_r($info);
        foreach($info as $llaveP => $valP){
            foreach($valP as $llaveD => $valD){
                if(!in_array($llaveD, $arrMostrar)){
                    unset($info[$llaveP][$llaveD]);
                }else{
                    $info[$llaveP][$llaveD] = trim($valD);
                }
            }
            $info[$llaveP]['CUENTA'] = $cuenta;            
        }

        foreach($info as $llaveP => $valP){
            $info[$llaveP] = array_reverse($valP);
        }
        return $info;
    }

    public function getToken(Request $request){

        $numero_Unico = $request->query('Numero_Unico');
        $cuenta_Catast = $request->query('Cuenta_Catast');
        $usuario = $request->query('Usuario');
        $contrasenia = $request->query('Contrasenia');
        $proceso = $request->query('Proceso');
        
        $res = array();
                
        if(isset($numero_Unico) && trim($numero_Unico) != '' && isset($cuenta_Catast) && isset($usuario) && trim($usuario) != '' && isset($contrasenia) && trim($contrasenia) != '' && trim($proceso) != ''){
            /*error_log(base64_decode($usuario));
            error_log(env('USUCONSULTAVA'));
            error_log(base64_decode($contrasenia));
            error_log(env('PASSCONSULTAVA'));*/
            
                if(base64_decode($usuario) == env('USUCONSULTAVA') && base64_decode($contrasenia) == env('PASSCONSULTAVA')){
                    $proceso = trim($proceso);
                    $token = Crypt::encrypt(['Numero_Unico'=>$numero_Unico,'Cuenta_Catast'=>$cuenta_Catast,'Usuario'=>$usuario,'Contrasenia'=>$contrasenia]);
                    $res['token_consulta'] = $token;
                    return $res;
                    //echo env('WS_COLEGIO_NOTARIOS')."/".env('TOKEN_WS_COLEGIO_NOTARIOS')."/".$proceso."/".$token; exit();
                    /*try{

                        $headers = array("X-Requested-Search: Token_HttpsRequest");
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, env('WS_COLEGIO_NOTARIOS')."/".env('TOKEN_WS_COLEGIO_NOTARIOS')."/".$proceso."/".$token);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $res = curl_exec($ch); 

                        //return response()->json($res, 200);
                        return $res;

                    } catch (\Throwable $th) {
                        error_log($th);
                        
                        return response()->json(['mensaje' => 'Error al entregar el token'], 500);
                    }*/
                    
                }else{
                    return response()->json(['mensaje'=>'Usuario o contraseña incorrectos'], 404);
                }    
            
        }else{
            return response()->json(['mensaje'=>'Falta alguno de los parámetros necesario'], 404); 
        }

        
    }

    public function getTokenPrueba(Request $request){

        $numero_Unico = $request->query('Numero_Unico');
        $cuenta_Catast = $request->query('Cuenta_Catast');
        $usuario = $request->query('Usuario');
        $contrasenia = $request->query('Contrasenia');
        $proceso = $request->query('Proceso');
        
        $res = array();
                
        if(isset($numero_Unico) && trim($numero_Unico) != '' && isset($cuenta_Catast) && isset($usuario) && trim($usuario) != '' && isset($contrasenia) && trim($contrasenia) != '' && trim($proceso) != ''){
            /*error_log(base64_decode($usuario));
            error_log(env('USUCONSULTAVA'));
            error_log(base64_decode($contrasenia));
            error_log(env('PASSCONSULTAVA'));*/
            
                if(base64_decode($usuario) == env('USUCONSULTAVA') && base64_decode($contrasenia) == env('PASSCONSULTAVA')){
                    $proceso = trim($proceso);
                    $token = Crypt::encrypt(['Numero_Unico'=>$numero_Unico,'Cuenta_Catast'=>$cuenta_Catast,'Usuario'=>$usuario,'Contrasenia'=>$contrasenia]);
                    error_log($token);
                    //echo env('WS_COLEGIO_NOTARIOS')."/".env('TOKEN_WS_COLEGIO_NOTARIOS')."/".$proceso."/".$token; exit();
                    try{

                        $headers = array("X-Requested-Search: Token_HttpsRequest");
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, env('WS_COLEGIO_NOTARIOS')."/".env('TOKEN_WS_COLEGIO_NOTARIOS')."/".$proceso."/".$token);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                        $res = curl_exec($ch); 

                        //return response()->json($res, 200);
                        return $res;

                    } catch (\Throwable $th) {
                        error_log($th);
                        
                        return response()->json(['mensaje' => 'Error al entregar el token'], 500);
                    }
                    
                }else{
                    return response()->json(['mensaje'=>'Usuario o contraseña incorrectos'], 404);
                }    
            
        }else{
            return response()->json(['mensaje'=>'Falta alguno de los parámetros necesario'], 404); 
        }

        
    }

    public function avaluosVista($idAvaluo){
        
        $infoAValuoVista = DB::select("SELECT * FROM FEXAVA.FEXAVA_AVALUOS_V WHERE IDAVALUO = $idAvaluo");
        return $infoAValuoVista[0];

    }
}