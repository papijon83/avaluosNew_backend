<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatosExtrasAvaluo
{
    public function IdPeritoSociedadByRegistro($registroPerito, $registroSoci, $esPerito)
    { 
        $dsePeritosSociedades = array();

        if ($esPerito)
        { 
            $dsePeritosSociedades = $this->getPeritoById($registroPerito);
            //if(count($dsePeritosSociedades) > 0)
            if(isset($dsePeritosSociedades))
            {
                return $dsePeritosSociedades['idpersona'];
            }
        }
        else
        {
            $dsePeritosSociedades = $this->getSociedadByIdPerito($registroPerito, $registroSoci);

            if(count($dsePeritosSociedades) > 0)
            {
                return $dsePeritosSociedades['idpersona'];
            }
        }

        return -1;
    }

    public function getPeritoById($registroPerito){
        /* $res = DB::table('RCON.RCON_PERITO')
        ->join('RCON.RCON_PERSONAFISICA', 'RCON.RCON_PERSONAFISICA.idpersona', '=', 'RCON.RCON_PERITO.idpersona')
        ->where('RCON.RCON_PERITO.idpersona',$idPersona)->first();
        return convierte_a_arreglo($res); */
        $res = DB::table('RCON.RCON_PERITO')
        ->join('RCON.RCON_PERSONAFISICA', 'RCON.RCON_PERSONAFISICA.idpersona', '=', 'RCON.RCON_PERITO.idpersona')
        ->where('RCON.RCON_PERITO.REGISTRO',$registroPerito)->first();
        return convierte_a_arreglo($res);

    }

    public function getSociedadByIdPerito($registroPerito, $registroSoci){
        $perito = $this->getPeritoById($registroPerito);
        $idPerito = $perito['idpersona']; 
        /*$res = DB::table('RCON.RCON_SOCIEDADPERITO')
        ->join('RCON.RCON_PERSONAMORAL', 'RCON.RCON_PERSONAMORAL.idpersona', '=', 'RCON.RCON_SOCIEDADPERITO.IDSOCIEDAD')
        ->where('RCON.RCON_SOCIEDADPERITO.idperito',$idPersona)->first();
        //return convierte_a_arreglo($res);
        return array('idsocperito' => null);*/
        $res = DB::table('RCON.RCON_SOCIEDADVALUACION')
        ->join('RCON.RCON_PERSONAMORAL', 'RCON.RCON_PERSONAMORAL.IDPERSONA', '=', 'RCON.RCON_SOCIEDADVALUACION.IDPERSONA')
        ->where('RCON.RCON_SOCIEDADVALUACION.REGISTRO',$registroSoci)->first();
        $sociedadValuacion = convierte_a_arreglo($res);
        return $sociedadValuacion;
        //Comentado porque en la tabla FEXAVA_AVALUO existe CONSTRAINT "FEXAVA_SOCIEDAD_FK" FOREIGN KEY ("IDPERSONASOCIEDAD")
        /*$idSociedadValuacion = $sociedadValuacion['idpersona'];

        $resSociedad = DB::select("SELECT * FROM RCON.RCON_SOCIEDADPERITO WHERE IDPERITO = $idPerito AND IDSOCIEDAD = $idSociedadValuacion");
        $sociedadPerito = convierte_a_arreglo($resSociedad);
        return $sociedadPerito[0];*/
        
    }       

    public function ObtenerIdDelegacionPorNombre($nombreDelegacion)
    {
        $nombreDelegacion = strtoupper($nombreDelegacion);
        
        $rowsDelegaciones = DB::select("SELECT * FROM CAS.CAS_DELEGACION WHERE NOMBRE = '$nombreDelegacion'");

        if (count($rowsDelegaciones) > 0)
        {
            $idDelegacion = $rowsDelegaciones[0]->iddelegacion;
        }
        else
        {
            return -1;
        }

        return $idDelegacion;
    }

    public function ObtenerIdDelegacionPorClave($codDelegacion)
    {        
        $rowsDelegaciones = DB::select("SELECT * FROM CAS.CAS_DELEGACION WHERE CLAVE = '$codDelegacion'");

        if (count($rowsDelegaciones) > 0)
        {
            $idDelegacion = $rowsDelegaciones[0]->iddelegacion;
        }
        else
        {
            return -1;
        }

        return $idDelegacion;
    }

    public function ObtenerIdColoniaPorNombreyDelegacion($nombreColonia, $codDelegacion)
    {
        if(strlen($codDelegacion) == 3){
            $codDelegacion = substr($codDelegacion,1,2);
        }
        
        $nombreColonia = strtoupper($nombreColonia);

        $rowsDelegaciones = DB::select("SELECT * FROM CAS.CAS_DELEGACION WHERE CLAVE = '$codDelegacion'");

        if (count($rowsDelegaciones) > 0)
        {
            $idDelegacion = $rowsDelegaciones[0]->iddelegacion;
        }
        else
        {
            return -1;
        }

        $rowsColonias = DB::select("SELECT * FROM CAS.CAS_COLONIA WHERE NOMBRE = '$nombreColonia' AND IDDELEGACION = '$idDelegacion'");

        if (count($rowsColonias) > 0)
        {
            $idColonia = $rowsColonias[0]->idcolonia;
        }
        else
        {
            return -1;
        }

        return $idColonia;
    }

    public function SolicitarObtenerIdClasesByCodeAndAno($fecha, $codClase)
    {
        try{
            /*SELECT rne.idclasesejercicio 
            FROM fis_clasesejercicio rne 
            INNER JOIN fis_ejercicio fe ON rne.idejercicio = fe.idejercicio 
            INNER JOIN fis_catclases crne ON crne.idclases = rne.idclases 
            WHERE TO_DATE('$fecha','DD/MM/YYYY') BETWEEN fe.fechainicio AND fe.fechafin AND upper(crne.codclase) = $codClase*/
        $query = "SELECT rne.idclasesejercicio    
            FROM fis_clasesejercicio rne 
            INNER JOIN fis_ejercicio fe ON rne.idejercicio = fe.idejercicio
            INNER JOIN fis_catclases crne ON crne.idclases = rne.idclases
            WHERE TO_DATE('$fecha','DD/MM/YYYY') BETWEEN fe.fechainicio AND fe.fechafin
            AND upper (crne.codclase) = '$codClase'";

            $conn = oci_connect(env("DB_USERNAME_FIS"), env("DB_PASSWORD_FIS"), env("DB_TNS")); 
            $sqlcadena = oci_parse($conn, $query);            
            oci_execute($sqlcadena);         
            $fila = oci_fetch_array($sqlcadena, OCI_ASSOC+OCI_RETURN_NULLS);            
            oci_free_statement($sqlcadena);
            oci_close($conn);
            if (isset($fila['IDCLASESEJERCICIO'])){     
                return $fila['IDCLASESEJERCICIO'];
            } else {    
                return 0;
            }
        }catch (\Throwable $th){
            error_log($th);
            Log::info($th);
            return 'Error al obtener el IDUSOSEJERCICIO.';
            
        }
        //FIS_CLASESEJERCICIO
        /*$c_filtro = DB::select("SELECT * FROM FIS.FIS_CATCLASES WHERE CODCLASE = '$codClase'");

        if(count($c_filtro) == 0){            
            return "el codigo de clase ".$codClase." no existe en el catalogo de clases";
        }else{
            return $c_filtro[0]->idclases;
        }*/
    }

    public function SolicitarObtenerIdUsosByCodeAndAno($fecha, $codUso)
    {
        try{ //echo "SOY FECHA Y CODUSO ".$fecha." ".$cod." "; exit();
            /*$procedure = 'BEGIN
            FIS.FIS_USOSEJERCICIO_PKG.FIS_SELECT_BYANOCOD_P(
                TO_DATE(:PAR_FECHA,\'DD/MM/YYYY\'),
                :PAR_CODTIPO,
                :IDRANGO
            ); END;';
            
            $conn = oci_connect(env("DB_USERNAME_FIS"), env("DB_PASSWORD"), env("DB_TNS"));            
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_FECHA', $fecha, 10);
            oci_bind_by_name($stmt, ':PAR_CODTIPO', $cod, 3);
            oci_bind_by_name($stmt, ':IDRANGO', $idRango, 10);    
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);  echo "EL IDRANGO ".print_r($idRango); exit();                
            if (isset($idRango)) {    
                return $idRango;
            } else {
                return false;
            }*/
            if($codUso == 'ALL' ){
                $query = "SELECT rne.idusosejercicio                
                FROM fis_usosejercicio rne INNER JOIN fis_ejercicio fe ON rne.idejercicio = fe.idejercicio
                INNER JOIN fis_catusos crne ON crne.idusos = rne.idusos
                WHERE TO_DATE('$fecha','DD/MM/YYYY') BETWEEN fe.fechainicio AND fe.fechafin";
            }else{
                $query = "SELECT rne.idusosejercicio                
                FROM fis_usosejercicio rne INNER JOIN fis_ejercicio fe ON rne.idejercicio = fe.idejercicio
                INNER JOIN fis_catusos crne ON crne.idusos = rne.idusos
                WHERE TO_DATE('$fecha','DD/MM/YYYY') BETWEEN fe.fechainicio AND fe.fechafin
                AND upper (crne.coduso) = '$codUso'";
            }
            $conn = oci_connect("FIS", env("DB_PASSWORD"), env("DB_TNS"));        
            $sqlcadena = oci_parse($conn, $query);            
            oci_execute($sqlcadena);         
            $fila = oci_fetch_array($sqlcadena, OCI_ASSOC+OCI_RETURN_NULLS);            
            oci_free_statement($sqlcadena);
            oci_close($conn); //print_r($fila); exit();
            if (isset($fila['IDUSOSEJERCICIO'])){     
                return $fila['IDUSOSEJERCICIO'];
            } else {    
                return 0;
            }   
        }catch (\Throwable $th){

            error_log($th);
            Log::info($th);
            return 'Error al obtener el IDUSOSEJERCICIO.';
            
        }

        //FIS_USOSEJERCICIO
        /*$c_filtro = DB::select("SELECT * FROM FIS.FIS_CATUSOS WHERE CODUSO = '$codUso'");

        if(count($c_filtro) == 0){            
            return "el codigo de uso ".$codUso." no existe en el catalogo de usos";
        }else{
            return $c_filtro[0]->idusos;
        }*/
    }

    public function SolicitarObtenerIdRangoNivelesByCodeAndAno($fecha, $codRangoNiveles)
    {
        try{
            if($codRangoNiveles == 'ALL' ){
                $query = "SELECT rne.idrangonivelesejercicio                
                FROM fis_rangonivelesejercicio rne INNER JOIN fis_ejercicio fe ON rne.idejercicio = fe.idejercicio
                INNER JOIN fis_catrangoniveles crne ON crne.idrangoniveles = rne.idrangoniveles
                WHERE TO_DATE('$fecha','DD/MM/YYYY') BETWEEN fe.fechainicio AND fe.fechafin";
            }else{
                $query = "SELECT rne.idrangonivelesejercicio                
                FROM fis_rangonivelesejercicio rne INNER JOIN fis_ejercicio fe ON rne.idejercicio = fe.idejercicio
                INNER JOIN fis_catrangoniveles crne ON crne.idrangoniveles = rne.idrangoniveles
                WHERE TO_DATE('$fecha','DD/MM/YYYY') BETWEEN fe.fechainicio AND fe.fechafin
                AND upper (crne.codrangoniveles) = '$codRangoNiveles'";
            }
            $conn = oci_connect("FIS", env("DB_PASSWORD"), env("DB_TNS"));        
            $sqlcadena = oci_parse($conn, $query);            
            oci_execute($sqlcadena);         
            $fila = oci_fetch_array($sqlcadena, OCI_ASSOC+OCI_RETURN_NULLS);            
            oci_free_statement($sqlcadena);
            oci_close($conn); //print_r($fila); exit();
            if (isset($fila['IDRANGONIVELESEJERCICIO'])){     
                return $fila['IDRANGONIVELESEJERCICIO'];
            } else {    
                return 0;
            }   
        }catch (\Throwable $th){

            error_log($th);
            Log::info($th);
            return 'Error al obtener el IDRANGONIVELESEJERCICIO.';
            
        }
        /*$conn = oci_connect("FIS", env("DB_PASSWORD"), env("DB_TNS"));        
        $sqlcadena = oci_parse($conn, "SELECT * FROM FIS.FIS_RANGONIVELESEJERCICIO WHERE IDRANGONIVELESEJERCICIO  = '$codRangoNiveles'");
        oci_execute($sqlcadena);

        $fila = oci_fetch_array($sqlcadena, OCI_ASSOC+OCI_RETURN_NULLS);
        oci_free_statement($sqlcadena);
        oci_close($conn);

        if(count($fila) == 0){            
            return "el codigo de rango ".$codRangoNiveles." no existe en el catalogo de rangos";
        }else{
            return $fila['IDRANGONIVELESEJERCICIO'];
        }*/
    }

    public function ObtenerClaseUsoByIdUsoIdClase($idUsoEjercicio, $idClaseEjercicio)
    {
        //FEXAVA_CATCLASEUSO - ORA-00942: la tabla o vista no existe
        //$c_claseUso = DB::select("SELECT * FROM FEXAVA_CATCLASEUSO");
        $c_claseUso = DB::select("SELECT * FROM FIS.FIS_CATUSOS WHERE CODUSO = '$idUsoEjercicio'");
        return $c_claseUso;
    }

    public function select_catClaseUsoId_p($idUsoEjercicio, $idClaseEjercicio){   //echo $idUsoEjercicio." ".$idClaseEjercicio."\n"; exit();//$idUsoEjercicio = 753 ;
        $procedure = 'BEGIN
            FEXAVA.FEXAVA_CATALOGOS_PKG.fexava_select_catClaseUsoId_p(
                :par_idUsoEjercicio,
                :par_idClaseEjercicio,
                :c_catclaseuso
            ); END;';
            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));            
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':par_idUsoEjercicio', $idUsoEjercicio,3);
            oci_bind_by_name($stmt, ':par_idClaseEjercicio', $idClaseEjercicio, 3);            
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":c_catclaseuso", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $valores, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);            
            if (count($valores) > 0) {    
               return $valores;
            } else {
                return [];
            }

    }
}