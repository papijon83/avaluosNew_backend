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

        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);           
            return response()->json(['mensaje' => 'Error al obtener la Información'], 500);    
        }
    }

}