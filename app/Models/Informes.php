<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Informes 
{

    public function getInvestigacionMercado($region,$manzana,$tipo,$delegacion,$colonia,$fechainicio,$fechafin){
        try{

            $fechaIni = new Carbon($fechainicio);
            $fechaInicio = $fechaIni->format('d/m/Y');

            $fechaF = new Carbon($fechafin);
            $fechaFin = $fechaF->format('d/m/Y'); //echo $fechaInicio." ".$fechaFin; exit(); 
            //error_log($fechaInicio." ".$fechaFin);
            $valNull = NULL;
            $procedure = 'BEGIN
            FEXAVA.FEXAVA_INVMERCADO_PKG_MX.FEXAVA_OBTENDATOSINVESTMERCADO(
                :P_REGION,
                :P_MANZANA,
                :P_TIPO,
                :P_DELEGACION,
                :P_COLONIA,
                TO_DATE(:P_fECHAINICIO,\'DD/MM/YYYY\'),
                TO_DATE(:P_FECHAFIN,\'DD/MM/YYYY\'),           
                :P_CONSULTA
            ); END;';
            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':P_REGION', $region,5);
            oci_bind_by_name($stmt, ':P_MANZANA', $manzana, 5);
            oci_bind_by_name($stmt, ':P_TIPO', $tipo, 10);
            oci_bind_by_name($stmt, ':P_DELEGACION', $delegacion, 20);
            oci_bind_by_name($stmt, ':P_COLONIA',$colonia, 10);
            oci_bind_by_name($stmt, ':P_fECHAINICIO',$fechaInicio, 20);
            oci_bind_by_name($stmt, ':P_FECHAFIN',$fechaFin, 20);
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":P_CONSULTA", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $valores, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);
            //print_r($valores); exit();
            if (count($valores) > 0) {
                return $valores;
            } else {
                return [];
            }

        }catch (\Throwable $th){

            error_log($th);
            Log::info($th);
            return 'Error al obtener la desviación estandar.';
            
        }         

    }

    public function getCuentasDuplicadas($fechaInicio, $fechaFin, $region, $manzana, $lote, $unidad, $registro, $completa){
        try{

            $fechaIni = new Carbon($fechaInicio);
            $fechainicio = $fechaIni->format('d/m/Y');

            $fechaF = new Carbon($fechaFin);
            $fechafin = $fechaF->format('d/m/Y'); //echo $fechaInicio." ".$fechaFin; exit(); 
            //error_log($fechaInicio." ".$fechaFin);
            $valNull = NULL;
            $procedure = 'BEGIN
            FEXAVA.FEXAVA_AVALUOS_PKG.fexava_cuentasduplicadas_p(
                TO_DATE(:P_FECHAINICIAL,\'DD/MM/YYYY\'),
                TO_DATE(:P_FECHAFINAL,\'DD/MM/YYYY\'),
                :P_REGION,
                :P_MANZANA,
                :P_LOTE,
                :P_UNIDAD,
                :P_REGISTRO,
                :P_TIPOCONSULTA,                   
                :C_AVALUOS
            ); END;';
            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':P_FECHAINICIAL',$fechaInicio, 20);
            oci_bind_by_name($stmt, ':P_FECHAFINAL',$fechaFin, 20);
            oci_bind_by_name($stmt, ':P_REGION', $region,5);
            oci_bind_by_name($stmt, ':P_MANZANA', $manzana, 5);
            oci_bind_by_name($stmt, ':P_LOTE', $lote, 5);
            oci_bind_by_name($stmt, ':P_UNIDAD', $unidad, 5);
            oci_bind_by_name($stmt, ':P_REGISTRO', $registro, 5);
            oci_bind_by_name($stmt, ':P_TIPOCONSULTA', $completa, 10);    
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUOS", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $valores, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);
            print_r($valores); exit();
            if (count($valores) > 0) {
                return $valores;
            } else {
                return [];
            }

        }catch (\Throwable $th){

            error_log($th);
            Log::info($th);
            return 'Error al obtener la desviación estandar.';
            
        }  
    }

    public function getDelegaciones(){
        $delegaciones = DB::select("SELECT * FROM CAS.CAS_DELEGACION");
        return $delegaciones;
    }

    public function getColonias($idDelegacion){
        $colonias = DB::select("SELECT * FROM CAS.CAS_COLONIA WHERE IDDELEGACION = '$idDelegacion'");
        return $colonias;
    }
    
}