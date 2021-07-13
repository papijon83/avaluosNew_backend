<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Log;

class Fis
{

    public function getDataByObtenerValorUnitarioConstruccion($codUso,$codClase,$codRangoNiveles,$numeroNiveles,$anio,$periodo){
        
        try{
            //$anio = date('Y');
            $valUno = '1';
            $procedure = 'BEGIN
            FIS.FIS_VUC_PKG.fis_select_valorunitario_p(
                :par_coduso,
                :par_codclase,
                :par_codrangoniveles,
                :par_numeroniveles,
                :par_ano,
                :par_periodo,
                :c_vuc
            ); END;';
            $conn = oci_connect(env("DB_USERNAME_FIS"), env("DB_PASSWORD_FIS"), env("DB_TNS"));
            oci_execute(oci_parse($conn,"ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':par_coduso', $codUso,3);
            oci_bind_by_name($stmt, ':par_codclase', $codClase, 3);
            oci_bind_by_name($stmt, ':par_codrangoniveles', $codRangoNiveles, 2);
            oci_bind_by_name($stmt, ':par_numeroniveles', $numeroNiveles, 3);
            oci_bind_by_name($stmt, ':par_ano',$anio);
            oci_bind_by_name($stmt, ':par_periodo',$valUno);
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":c_vuc", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $valores, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);            
            if (count($valores) > 0) {    
                return $valores[0]['VALORUNITARIO'];
            } else {
                return [];
            }

        }catch (\Throwable $th){

            error_log($th);
            Log::info($th);
            return 'Error al obtener la desviación estandar.';
            
        }         

    } 

    function solicitarObtenerIdUsosByCodeAndAno($fecha, $cod){
        try{ //echo "SOY FECHA Y CODUSO ".$fecha." ".$cod." "; exit();
            /*$procedure = 'BEGIN
            FIS.FIS_RANGONIVELESEJERCICIO_PKG.FIS_SELECT_BYANOCOD_P(
                TO_DATE(:PAR_FECHA,\'DD/MM/YYYY\'),
                :PAR_CODTIPO,
                :IDRANGO
            ); END;';
            
            $conn = oci_connect(env("DB_USERNAME_FIS"), env("DB_PASSWORD_FIS"), env("DB_TNS"));            
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_FECHA', $fecha, 10);
            oci_bind_by_name($stmt, ':PAR_CODTIPO', $cod, 3);
            oci_bind_by_name($stmt, ':IDRANGO', $idRango, 10);    
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);  echo "EL IDRANGO ".$idRango; exit();                
            if (isset($idRango)) {    
                return $idRango;
            } else {
                return false;
            }*/

            $query = "SELECT rne.idclasesejercicio 
            FROM fis_clasesejercicio rne 
            INNER JOIN fis_ejercicio fe ON rne.idejercicio = fe.idejercicio 
            INNER JOIN fis_catclases crne ON crne.idclases = rne.idclases 
            WHERE TO_DATE('$fecha','DD/MM/YYYY') BETWEEN fe.fechainicio AND fe.fechafin AND upper(crne.codclase) = '$cod'"; //print_r($query)."\n";
            $conn = oci_connect("FIS", env("DB_PASSWORD"), env("DB_TNS"));        
            $sqlcadena = oci_parse($conn, $query);
            //oci_define_by_name($sqlcadena, 'text', $text);
            oci_execute($sqlcadena);         
            $fila = oci_fetch_array($sqlcadena, OCI_ASSOC+OCI_RETURN_NULLS);            
            oci_free_statement($sqlcadena);
            oci_close($conn); //print_r($fila); exit();
            if (isset($fila['IDCLASESEJERCICIO'])){     
                return $fila['IDCLASESEJERCICIO'];
            } else {
                //echo "ENTRE AQUIIII";
                return 0;
            }    
        }catch (\Throwable $th){

            error_log($th);
            Log::info($th);
            return 'Error al obtener el idUsoEjercicio.';
            
        } 
        
    }

    function getClase($codigo){
        $infoClase = DB::select("SELECT CLASE FROM FIS.FIS_CATCLASES WHERE CODCLASE = '$codigo'");
        $arrInfo = $infoClase[0];
        return $arrInfo->clase;
    }

    public function getClaseEjercicio($codClaseGeneral){
        
        try{
            //$anio = date('Y');
            $fecha = Carbon::today()->format('Y/m/d'); error_log("LA FECHA   ".$fecha);
            $valUno = '1';
            $procedure = 'BEGIN
            fis.fis_clasesejercicio_pkg.fis_select_byanocod_p(            
                TO_DATE(:par_fecha,\'YYYY-MM-DD\'),
                :par_codtipo,
                :idrango
            ); END;';
            $conn = oci_connect(env("DB_USERNAME_FIS"), env("DB_PASSWORD_FIS"), env("DB_TNS"));
            //oci_execute(oci_parse($conn,"ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':par_fecha', $fecha, 20);
            oci_bind_by_name($stmt, ':par_codtipo', $codClaseGeneral, 10);
            oci_bind_by_name($stmt, ':idrango', $cuidclasesejercicio, 10);  
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);   
            oci_free_statement($stmt);
            oci_close($conn);
                  
           return $cuidclasesejercicio;

        }catch (\Throwable $th){

            error_log($th);
            Log::info($th);
            return 'Error al obtener la desviación estandar.';
            
        }         

    }

}