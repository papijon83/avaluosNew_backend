<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;
use App\Models\Informes;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Hamcrest\Arrays\IsArray;
use Log;

class InformesController extends Controller
{

    protected $modelInformes;

    public function __construct()
    {
        $this->modelInformes = new Informes();
    }

    public function GetInvestigacionMercado(Request $request){
        try{

            $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            } 
            $resToken = Crypt::decrypt($authToken);
            
            $idPersona = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona']; //$idPersona = 264;

            $region = trim($request->query('region')) == '' ? NULL : trim($request->query('region'));
            $manzana = trim($request->query('manzana')) == '' ? NULL : trim($request->query('manzana'));
            $tipo = trim($request->query('tipo')) == '' ? NULL : trim($request->query('tipo'));
            $delegacion = trim($request->query('delegacion')) == '' ? NULL : trim($request->query('delegacion'));
            $colonia = trim($request->query('colonia')) == '' ? NULL : trim($request->query('colonia'));
            $fechainicio = trim($request->query('fechainicio')) == '' ? NULL : trim($request->query('fechainicio'));
            $fechafin = trim($request->query('fechafin')) == '' ? NULL : trim($request->query('fechafin'));
            
            
           $res = $this->modelInformes->getInvestigacionMercado($region,$manzana,$tipo,$delegacion,$colonia,$fechainicio,$fechafin);
           return response()->json([$res], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);           
            return response()->json(['mensaje' => 'Error al obtener la Información'], 500);    
        }
    }

    public function GetCuentasDuplicadas(Request $request){
        try{

            $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            } 
            $resToken = Crypt::decrypt($authToken);
            
            $idPersona = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona']; //$idPersona = 264;

            $fechaInicio = trim($request->query('fechainicio')) == '' ? NULL : trim($request->query('fechainicio'));
            $fechaFin = trim($request->query('fechafin')) == '' ? NULL : trim($request->query('fechafin'));
            $region = trim($request->query('region')) == '' ? NULL : trim($request->query('region'));
            $manzana = trim($request->query('manzana')) == '' ? NULL : trim($request->query('manzana'));
            $lote = trim($request->query('lote')) == '' ? NULL : trim($request->query('lote'));
            $unidad = trim($request->query('unidad')) == '' ? NULL : trim($request->query('unidad'));
            $registro = trim($request->query('registro')) == '' ? NULL : trim($request->query('registro'));
            $completa = trim($request->query('completa')) == '' ? NULL : trim($request->query('completa'));
                
            
           $res = $this->modelInformes->getCuentasDuplicadas($fechaInicio, $fechaFin, $region, $manzana, $lote, $unidad, $registro, $completa);
           return response()->json([$res], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);           
            return response()->json(['mensaje' => 'Error al obtener la Información'], 500);    
        }
    }

    public function GetDelegaciones(Request $request){
        try{

            $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            } 
            $resToken = Crypt::decrypt($authToken);
            
            $idPersona = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona']; //$idPersona = 264;                            
            
           $res = $this->modelInformes->getDelegaciones();
           return response()->json([$res], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);           
            return response()->json(['mensaje' => 'Error al obtener la Información'], 500);    
        }
    }

    public function GetColonias(Request $request){
        try{

            $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            } 
            $resToken = Crypt::decrypt($authToken);
            
            $idPersona = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona']; //$idPersona = 264;           
            $idDelegacion = trim($request->query('idDelegacion')) == '' ? NULL : trim($request->query('idDelegacion'));
            
           $res = $this->modelInformes->getColonias($idDelegacion);
           return response()->json([$res], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);           
            return response()->json(['mensaje' => 'Error al obtener la Información'], 500);    
        }
    }

    public function GetTiposComparable(Request $request){
        try{

            $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            } 
            $resToken = Crypt::decrypt($authToken);
            
            $idPersona = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona']; //$idPersona = 264;                            
            
           $res = $this->modelInformes->getTiposComparable();
           return response()->json([$res], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);           
            return response()->json(['mensaje' => 'Error al obtener la Información'], 500);    
        }
    }

}