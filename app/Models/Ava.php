<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Ava 
{

    public function valorUnitarioDesviacion($region,$manzana,$lote,$unidadPriv){
        try{

            $valNull = NULL;
            $procedure = 'BEGIN
            AVA.AVA_SERVINMOBILIARIO_PKG.ava_select_servAvaluobyAval_p(
                :par_region,
                :par_manzana,
                :par_lote,
                :par_unidadPrivativa,
                :par_codAreaValor,
                :c_servAvaluo
            ); END;';
            $conn = oci_connect(env("DB_USERNAME_AVA"), env("DB_PASSWORD_AVA"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':par_region', $region,3);
            oci_bind_by_name($stmt, ':par_manzana', $manzana, 3);
            oci_bind_by_name($stmt, ':par_lote', $lote, 2);
            oci_bind_by_name($stmt, ':par_unidadPrivativa', $unidadPriv, 3);
            oci_bind_by_name($stmt, ':par_codAreaValor',$valNull);
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":c_servAvaluo", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $valores, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);
            //print_r($valores); exit();
            if (count($valores) > 0) {
                return $valores[0];
            } else {
                return [];
            }

        }catch (\Throwable $th){

            error_log($th);
            Log::info($th);
            return 'Error al obtener la desviación estandar.';
            
        }         

    }
    
}