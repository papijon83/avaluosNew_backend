<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Log;

class GuardaenBD
{
    protected $idTerrenoMercado;
    public function insertAvaluo($arrAvaluo){
        //return TRUE;
        //print_r($arrAvaluo); exit();
        $arrNoTabla = array('IDAVALUO','FECHAAVALUO','VIDRERIA','CERRAJERIA','FACHADAS','CUCODCLASESCONSTRUCCION');
        $arrFexavaAvaluo = array();
        foreach($arrAvaluo as $idElementoPrincipal => $arrElementoPrincipal){
            if(!is_array($arrElementoPrincipal) && !in_array($idElementoPrincipal,$arrNoTabla)){
                if(stristr($idElementoPrincipal,'FECHA') != ''){                    
                    $fechaf = new Carbon($arrElementoPrincipal);
                    $fecha = $fechaf->format('Y/m/d');
                    $arrFexavaAvaluo[$idElementoPrincipal] = $fecha;
                }else{
                    if($arrElementoPrincipal == 'true' || $arrElementoPrincipal == 'TRUE'){
                        $arrElementoPrincipal = 1;
                    }
                    if($arrElementoPrincipal == 'false' || $arrElementoPrincipal == 'FALSE'){
                        $arrElementoPrincipal = 0;
                    }
                    $arrFexavaAvaluo[$idElementoPrincipal] = $arrElementoPrincipal;
                }
                
            }
        }
        //print_r($arrFexavaAvaluo); exit();
        $resInsert = $this->insertDatosFexavaAvaluo('FEXAVA_AVALUO',$arrFexavaAvaluo,$arrAvaluo['IDAVALUO']);
        if(strpos($resInsert, 'Error') != FALSE){
            return $resInsert;
        }
        //$this->insertDatosFexavaAvaluo($arrAvaluo);
       // exit();
              
       $arrTablas = array('FEXAVA_DATOSPERSONAS','FEXAVA_FUENTEINFORMACIONLEG','FEXAVA_ESCRITURA','FEXAVA_SUPERFICIE','FEXAVA_TIPOCONSTRUCCION','FEXAVA_ELEMENTOSCONST','FEXAVA_TERRENOMERCADO','FEXAVA_DATOSTERRENOS','FEXAVA_CONSTRUCCIONESMER','FEXAVA_ENFOQUECOSTESCAT','FEXAVA_FOTOAVALUO','FEXAVA_FOTOCOMPARABLE');
       $arrElementosConst = array('FEXAVA_OBRANEGRA','FEXAVA_REVESTIMIENTOACABADO','FEXAVA_CARPINTERIA','FEXAVA_INSTALACIONHIDSAN','FEXAVA_PUERTASYVENTANERIA','FEXAVA_ELEMENTOSEXTRA');
       foreach($arrTablas as $tabla){        
                 
        switch ($tabla) {
           case 'FEXAVA_DATOSPERSONAS':               
                if(isset($arrAvaluo[$tabla])){
                    foreach($arrAvaluo[$tabla] as $idTabla => $elementosTabla){
                        if($idTabla != 'CuentaCatastral' && $idTabla != 'PROPOSITO' && $idTabla != 'OBJETO'){
                            $resInsert = $this->insertDatos($tabla,$elementosTabla,$arrAvaluo['IDAVALUO']);
                            if(strpos($resInsert, 'Error') != FALSE){
                                return $resInsert;
                            }
                        }
                    }
                    
                }                
            break;
            
            case 'FEXAVA_FUENTEINFORMACIONLEG':                
                if(isset($arrAvaluo[$tabla])){
                    $fechaf = new Carbon($arrAvaluo[$tabla]['FECHA']);
                    $fecha = $fechaf->format('Y/m/d');
                    $arrAvaluo[$tabla]['FECHA'] = $fecha;                                          
                    $resInsert = $this->insertDatos($tabla,$arrAvaluo[$tabla],$arrAvaluo['IDAVALUO']);    
                }
            break;

            case 'FEXAVA_ESCRITURA':                
                if(isset($arrAvaluo[$tabla])){                                           
                    $resInsert = $this->insertDatos($tabla,$arrAvaluo[$tabla],$arrAvaluo['IDAVALUO']);
                    if(strpos($resInsert, 'Error') != FALSE){
                        return $resInsert;
                    }    
                }
            break;

            case 'FEXAVA_TIPOCONSTRUCCION':               
                if(isset($arrAvaluo[$tabla])){
                    foreach($arrAvaluo[$tabla] as $idTabla => $elementosTabla){
                        
                        $resInsert = $this->insertDatos($tabla,$elementosTabla,$arrAvaluo['IDAVALUO']);
                        if(strpos($resInsert, 'Error') != FALSE){
                            return $resInsert;
                        }
                        
                    }
                    
                }                
            break;

            case 'FEXAVA_ELEMENTOSCONST':
                $elementosTabla = array();
                $elementos = array('VIDRERIA','CERRAJERIA','FACHADAS');

                foreach($elementos as $elemento){
                    if(isset($arrAvaluo[$elemento])){
                        $elementosTabla[$elemento] = $arrAvaluo[$elemento];
                    }
                }
                //$elementosTabla = array('VIDRERIA' => $arrAvaluo['VIDRERIA'],'CERRAJERIA' => $arrAvaluo['CERRAJERIA'],'FACHADAS' => $arrAvaluo['FACHADAS']);

                if(count($elementosTabla) > 0){
                    $resInsert = $this->insertDatos($tabla,$elementosTabla,$arrAvaluo['IDAVALUO']);
                    if(strpos($resInsert, 'Error') != FALSE){
                        return $resInsert;
                    }
                }                
                
                foreach($arrAvaluo[$tabla] as $idElementoPrincipal => $arrElementoPrincipal){
                    if(is_array($arrElementoPrincipal) && in_array($idElementoPrincipal,$arrElementosConst)){

                        if($idElementoPrincipal == 'FEXAVA_ELEMENTOSEXTRA'){
                            foreach($arrAvaluo[$tabla][$idElementoPrincipal] as $arrElementoExtra){
                                $resInsert = $this->insertDatosElementosExtra($idElementoPrincipal,$arrElementoExtra,$arrAvaluo['IDAVALUO']);
                                if(strpos($resInsert, 'Error') != FALSE){
                                    return $resInsert;
                                }
                            }
                        }else{                            
                            $resInsert = $this->insertDatos($idElementoPrincipal,$arrElementoPrincipal,$arrAvaluo['IDAVALUO']);
                            if(strpos($resInsert, 'Error') != FALSE){
                                return $resInsert;
                            }   
                        }    
                        
                    }
                }

            break;

            case 'FEXAVA_TERRENOMERCADO':
                if(isset($arrAvaluo[$tabla]) && count($arrAvaluo[$tabla]) > 0){
                    $this->idTerrenoMercado = $this->insertTerrenoMercado($arrAvaluo[$tabla],$arrAvaluo['IDAVALUO']);
                    if(strpos($this->idTerrenoMercado, 'Error') != FALSE){
                        return $this->idTerrenoMercado;
                    }
                    if(isset($arrAvaluo['FEXAVA_DATOSTERRENOS'])){
                        foreach($arrAvaluo['FEXAVA_DATOSTERRENOS'] as $idTabla => $elementosTabla){
                            $elementosTabla['IDTERRENOMERCADO'] = $this->idTerrenoMercado;
                            $resInsert = $this->insertDatosTerreno('FEXAVA_DATOSTERRENOS',$elementosTabla,$arrAvaluo['IDAVALUO']);
                            if(strpos($resInsert, 'Error') != FALSE){
                                return $resInsert;
                            }
                            
                        }
                    }
                     
                    //$this->insertDatos($tabla,$arrAvaluo[$tabla],$arrAvaluo['IDAVALUO']);    
                }
            break;   

            case 'FEXAVA_CONSTRUCCIONESMER':
                if(isset($arrAvaluo[$tabla])){
                    
                    foreach($arrAvaluo[$tabla] as $idTabla => $elementosTabla){
                        $arrElementosConstruccionesMer = array();
                        foreach($elementosTabla as $id => $elementos){
                            if(!is_array($elementos)){
                                $arrElementosConstruccionesMer[$id] = $elementos;
                            }                            
                        }
                        //print_r($arrElementosConstruccionesMer); exit();
                        $idconstruccionesmercado = $this->insertConstruccionesMer($arrElementosConstruccionesMer,$arrAvaluo['IDAVALUO']);
                            if(strpos($idconstruccionesmercado, 'Error') != FALSE){
                                return $idconstruccionesmercado;
                            }
                        //echo "SOY idconstruccionesmercado ".$idconstruccionesmercado; exit();
                        foreach($elementosTabla as $id => $elementos){
                            if(is_array($elementos)){
                                $resInsert = $this->insertDatosProductos('FEXAVA_INVESTPRODUCTOSCOMP',$elementos,$idconstruccionesmercado);
                                if(strpos($resInsert, 'Error') != FALSE){
                                    return $resInsert;
                                }
                            }                            
                        }                            
                    }                    
                }
            break;
            
            case 'FEXAVA_ENFOQUECOSTESCAT':
                if(isset($arrAvaluo[$tabla]) && count($arrAvaluo[$tabla]) > 0){                                            
                    $resInsert = $this->insertDatos($tabla,$arrAvaluo[$tabla],$arrAvaluo['IDAVALUO']);
                    if(strpos($resInsert, 'Error') != FALSE){
                        return $resInsert;
                    }      
                }
            break;

            case 'FEXAVA_FOTOAVALUO':
                if(isset($arrAvaluo[$tabla])){
                    foreach($arrAvaluo[$tabla] as $idTabla => $elementosTabla){                        
                        $resInsert = $this->insertFexavaFotoAvaluo($elementosTabla,$arrAvaluo['IDAVALUO']);
                        if(strpos($resInsert, 'Error') != FALSE){
                            return $resInsert;
                        }                        
                    }                    
                }
            break;

            case 'FEXAVA_FOTOCOMPARABLE':
                if(isset($arrAvaluo[$tabla])){ 
                    $idinvestproductoscomparables = '';                   
                    foreach($arrAvaluo[$tabla] as $id => $elemento){                                                    
                        $idinvestproductoscomparables = $this->insertFexavaInvestProductosComp($elemento['FEXAVA_INVESTPRODUCTOSCOMP']);
                        if(strpos($idinvestproductoscomparables, 'Error') != FALSE){
                            return $idinvestproductoscomparables;
                        }                           
                        $arrFotoComparable = array('IDINVESTPRODUCTOSCOMPARABLES' => $idinvestproductoscomparables,'IDDOCUMENTOFOTO' => $elemento['IDDOCUMENTOFOTO']);
                        $idFotosComparables = $this->insertFexavaFotoComparable($arrFotoComparable);
                        if(strpos($idFotosComparables, 'Error') != FALSE){
                            return $idFotosComparables;
                        }                           
                    }                    
                }
            break; 
        }
        
       }
       
       return TRUE;
       
    }

    public function insertDatos($tabla,$elementosTabla,$idAvaluo){    
        $iniQuery = "INSERT INTO ".$tabla;
        $campos = '(IDAVALUO,';
        $valores = '('.$idAvaluo.",";
        foreach($elementosTabla as $idElemento => $elemento){
            $campos .= $idElemento.",";
            $valores .= "'".$elemento."',";
        }
        $campos = substr($campos,0,strlen($campos) - 1);
        $valores = substr($valores,0,strlen($valores) - 1);
        $query = $iniQuery.$campos.") VALUES ".$valores.")";
        /*if($tabla == 'FEXAVA_ELEMENTOSCONST'){
            echo $query."\n\n"; exit();
        }*/
        //echo $query."\n\n"; 
        return $this->ejecutaQuery($query,$tabla);
    }

    

    public function insertDatosFexavaAvaluo($tabla,$elementosTabla,$idAvaluo){    
        $iniQuery = "INSERT INTO ".$tabla;
        $campos = '(IDAVALUO,CUIDCLASESEJERCICIO,';
        $valores = '('.$idAvaluo.",391,";
        foreach($elementosTabla as $idElemento => $elemento){
            $campos .= $idElemento.",";
            if($idElemento == 'FECHA_PRESENTACION'){
                $valores .= "SYSDATE,";
            }else{
                $valores .= "'".$elemento."',";
            }
           
        }
        $campos = substr($campos,0,strlen($campos) - 1);
        $valores = substr($valores,0,strlen($valores) - 1);
        $query = $iniQuery.$campos.") VALUES ".$valores.")";
        //echo $query."\n\n";  exit();
        return $this->ejecutaQuery($query,$tabla);
    }

    public function insertDatosElementosExtra($tabla,$elementosTabla,$idAvaluo){
        //print_r($elementosTabla); exit();
        $iniQuery = "INSERT INTO ".$tabla;
        $campos = '(';
        $valores = '(';
        foreach($elementosTabla as $idElemento => $elemento){
            $campos .= $idElemento.",";
            $valores .= "'".$elemento."',";
        }
        $campos = substr($campos,0,strlen($campos) - 1);
        $valores = substr($valores,0,strlen($valores) - 1);
        $query = $iniQuery.$campos.") VALUES ".$valores.")";
        //echo $query."\n\n"; 
        return $this->ejecutaQuery($query,$tabla);
    }

    public function insertDatosTerreno($tabla,$elementosTabla,$idAvaluo){
        $iniQuery = "INSERT INTO ".$tabla;
        $campos = '(';
        $valores = '(';
        foreach($elementosTabla as $idElemento => $elemento){
            $campos .= $idElemento.",";
            $valores .= "'".$elemento."',";
        }
        $campos = substr($campos,0,strlen($campos) - 1);
        $valores = substr($valores,0,strlen($valores) - 1);
        $query = $iniQuery.$campos.") VALUES ".$valores.")";
        //echo $query."\n\n"; 
        return $this->ejecutaQuery($query,$tabla);
    }

    public function insertDatosProductos($tabla,$elementosTabla,$idconstruccionesmercado){
        /*echo "TABLA ".$tabla."\n";
        print_r($elementosTabla);
        echo " idconstruccionesmercado".$idconstruccionesmercado; exit();*/
        
        foreach($elementosTabla as $idElemento => $elemento){
            $iniQuery = "INSERT INTO ".$tabla;
            $campos = '(IDCONSTRUCCIONESMERCADO,';
            $valores = '('.$idconstruccionesmercado.",";
             
            foreach($elemento as $id => $dato){
                $campos .= $id.",";
                $valores .= "'".$dato."',";
            }
            $campos = substr($campos,0,strlen($campos) - 1);
            $valores = substr($valores,0,strlen($valores) - 1);
            $query = $iniQuery.$campos.") VALUES ".$valores.")";
            //echo $query."\n\n";
            $resEjec = $this->ejecutaQuery($query,$tabla);

            if(strpos($resEjec, 'Error') != FALSE){
                return $resEjec;
            }
            
        }
        
        return TRUE;
    }

    public function insertFexavaInvestSuperficieAux($par_identificadorFraccion,$par_superficiefraccion,$par_fzo,$par_fub,$par_ffr,$par_ffo,$par_fsu,$par_idavaluo,$par_fotvalor,$par_fotdescripcion,$par_valcatastraltierra,$par_codtipo){
        $cursor = null;
        try {
            $par_identificadorFraccion = $par_identificadorFraccion == null ? 'null' : $par_identificadorFraccion;
            $par_superficiefraccion = $par_superficiefraccion == null ? 'null' : $par_superficiefraccion;
            $par_fzo = $par_fzo == null ? null : $par_fzo;
            $par_fub = $par_fub == null ? null : $par_fub;
            $par_ffr = $par_ffr == null ? null : $par_ffr;
            $par_ffo = $par_ffo == null ? null : $par_ffo;
            $par_fsu = $par_fsu == null ? null : $par_fsu;
            $par_idavaluo = $par_idavaluo == null ? null : $par_idavaluo;
            $par_fotvalor = $par_fotvalor == null ? null : $par_fotvalor;
            $par_fotdescripcion = $par_fotdescripcion == null ? null : (String)($par_fotdescripcion);
            $par_valcatastraltierra = $par_valcatastraltierra == null ? null : $par_valcatastraltierra;
            $par_codtipo = $par_codtipo == null ? null : (String)($par_codtipo);


            //echo $par_identificadorFraccion.", ".$par_superficiefraccion.", ".$par_fzo.", ".$par_fub.", ".$par_ffr.", ".$par_ffo.", ".$par_fsu.", ".$par_fotvalor.", ".$par_fotdescripcion.", ".$par_idavaluo.", ".$par_valcatastraltierra.", ".$par_codtipo;
            //exit();

            //print_r($arrElementos['calle']); exit();
                    
            $procedure = 'BEGIN
            FEXAVA.FEXAVA_DATOSESTADISTICOS_PKG.FEXAVA_INSERT_SUPERFICIEAUX_PN(
                :PAR_IDENTIFICADORFRACCION,
                :PAR_SUPERFICIEFRACCION,
                :PAR_FZO,
                :PAR_FUB,
                :PAR_FFR,
                :PAR_FFO,
                :PAR_FSU,
                :PAR_IDAVALUO,
                :PAR_FOTVALOR,
                :PAR_FOTDESCRIPCION,
                :PAR_VALCATASTRALTIERRA,
                :PAR_CODTIPO,
                :C_AVALUO
            ); END;';

            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            oci_execute(oci_parse($conn,"ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'"));
            $stmt = oci_parse($conn, $procedure);            
            oci_bind_by_name($stmt, ':PAR_IDENTIFICADORFRACCION',$par_identificadorFraccion,200);
            oci_bind_by_name($stmt, ':PAR_SUPERFICIEFRACCION',$par_superficiefraccion,24);
            oci_bind_by_name($stmt, ':PAR_FZO',$par_fzo,5);
            oci_bind_by_name($stmt, ':PAR_FUB',$par_fub,5);
            oci_bind_by_name($stmt, ':PAR_FFR',$par_ffr,5);
            oci_bind_by_name($stmt, ':PAR_FFO',$par_ffo,5);
            oci_bind_by_name($stmt, ':PAR_FSU',$par_fsu,5);
            oci_bind_by_name($stmt, ':PAR_IDAVALUO',$par_idavaluo,200);
            oci_bind_by_name($stmt, ':PAR_FOTVALOR',$par_fotvalor,5);
            oci_bind_by_name($stmt, ':PAR_FOTDESCRIPCION',$par_fotdescripcion,200);        
            oci_bind_by_name($stmt, ':PAR_VALCATASTRALTIERRA',$par_valcatastraltierra,200);
            oci_bind_by_name($stmt, ':PAR_CODTIPO',$par_codtipo,2);        
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUO", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $resInsertFexsuperficie, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);
            //print_r($resInsertFexava); exit();
            return $resInsertFexsuperficie;
        }catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            return 'Error al insertar con FEXAVA_INSERT_SUPERFICIEAUX_P';
        }
    }

    public function insertFexavaInvestProductosComp($arrElementos){
        //print_r($arrElementos['calle']); exit();
        try {            
            $procedure = 'BEGIN
            FEXAVA.FEXAVA_DATOSESTADISTICOS_PKG.FEXAVA_INSERT_INVPRODUCCOMP_P(
                :PAR_CALLE,
                :PAR_CODIGOPOSTAL,
                :PAR_TELEFONO,
                :PAR_INFORMANTE,
                :PAR_DESCRIPCION,
                :PAR_SUPERFVENDPORUNID,
                :PAR_PRECIOSOLICITADO,
                :PAR_IDCONSTRUCCIONESMERCADO,
                :PAR_IDDELEGACION,
                :PAR_IDCOLONIA,
                :PAR_REGION,
                :PAR_MANZANA,
                :PAR_LOTE,
                :PAR_UNIDADPRIVATIVA,
                :PAR_CODTIPOCOMPARABLE,
                :C_AVALUO
            ); END;';

            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_CALLE',$arrElementos->calle);
            oci_bind_by_name($stmt, ':PAR_CODIGOPOSTAL',$arrElementos->codigopostal);
            oci_bind_by_name($stmt, ':PAR_TELEFONO',$arrElementos->telefono);
            oci_bind_by_name($stmt, ':PAR_INFORMANTE',$arrElementos->informante);
            oci_bind_by_name($stmt, ':PAR_DESCRIPCION',$arrElementos->descripcion);
            oci_bind_by_name($stmt, ':PAR_SUPERFVENDPORUNID',$arrElementos->superficievendibleporunidad);
            oci_bind_by_name($stmt, ':PAR_PRECIOSOLICITADO',$arrElementos->preciosolicitado);
            oci_bind_by_name($stmt, ':PAR_IDCONSTRUCCIONESMERCADO',$arrElementos->idconstruccionesmercado);
            oci_bind_by_name($stmt, ':PAR_IDDELEGACION',$arrElementos->iddelegacion);
            oci_bind_by_name($stmt, ':PAR_IDCOLONIA',$arrElementos->idcolonia);        
            oci_bind_by_name($stmt, ':PAR_REGION',$arrElementos->region);
            oci_bind_by_name($stmt, ':PAR_MANZANA',$arrElementos->manzana);
            oci_bind_by_name($stmt, ':PAR_LOTE',$arrElementos->lote);
            oci_bind_by_name($stmt, ':PAR_UNIDADPRIVATIVA',$arrElementos->unidadprivativa);
            oci_bind_by_name($stmt, ':PAR_CODTIPOCOMPARABLE',$arrElementos->codtipocomparable);     
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUO", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $resInsertFexava, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);
            //print_r($resInsertFexava); exit();
            return $resInsertFexava[0]['IDINVESTPRODUCTOSCOMPARABLES'];
        }catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            return 'Error al insertar en la tabla insertFexavaInvestProductosComp';
        }
    }

    public function insertFexavaFotoAvaluo($arrElementos,$idAvaluo){
        try{
            //print_r($arrElementos); exit();
            //$cursor = null;        
            $procedure = 'BEGIN
            FEXAVA.FEXAVA_DATOSESTADISTICOS_PKG.FEXAVA_INSERT_FOTOAVALUO_P(
                :PAR_IDDOCUMENTOFOTO,
                :PAR_IDAVALUO,
                :C_AVALUO
            ); END;';

            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_IDDOCUMENTOFOTO',$arrElementos['IDDOCUMENTOFOTO']);
            oci_bind_by_name($stmt, ':PAR_IDAVALUO',$idAvaluo);         
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUO", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $resInsertFexava, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);
            return $resInsertFexava[0]['IDFOTOAVALUO'];
        }catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            return 'Error al insertar en la tabla FEXAVA_FOTOCOMPARABLE';
        }
    }
    
    public function insertFexavaFotoComparable($arrElementos){
        try{
            //print_r($arrFexavaAvaluo); exit();
            //$cursor = null;        
            $procedure = 'BEGIN
            FEXAVA.FEXAVA_DATOSESTADISTICOS_PKG.FEXAVA_INSERT_FOTOCOMPARBLE_P(
                :PAR_IDDOCUMENTOFOTO,
                :PAR_IDINVESTPRODUCTOSCOMP,
                :C_AVALUO
            ); END;';

            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_IDDOCUMENTOFOTO',$arrElementos['IDDOCUMENTOFOTO']);
            oci_bind_by_name($stmt, ':PAR_IDINVESTPRODUCTOSCOMP',$arrElementos['IDINVESTPRODUCTOSCOMPARABLES']);         
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUO", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $resInsertFexava, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);
            return $resInsertFexava[0]['IDFOTOSCOMPARABLES'];
        }catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            return 'Error al insertar en la tabla FEXAVA_FOTOCOMPARABLE';
        }
    }

    /*public function insertTerrenoMercado($arrElementos,$idAvaluo){
        try{
            $valNull = NULL;
            $procedure = 'BEGIN
            FEXAVA.FEXAVA_DATOSESTADISTICOS_PKG.FEXAVA_INSERT_TERRENOMERCADO_P(
                :PAR_VALORUNITARIOTIERRAPROMED,
                :PAR_VALORUNITARIOTIERRAHOMOL,
                :PAR_CODTIPOTERRENO,
                :PAR_VALORUNITARIORESIDUAL,
                :PAR_IDAVALUO,
                :C_AVALUO
            ); END;';

            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_VALORUNITARIOTIERRAPROMED',$arrElementos['VALORUNITARIOTIERRAPROMEDIO']);
            oci_bind_by_name($stmt, ':PAR_VALORUNITARIOTIERRAHOMOL',$arrElementos['VALORUNITARIOTIERRAHOMOLOGADO']);
            oci_bind_by_name($stmt, ':PAR_CODTIPOTERRENO',$arrElementos['CODTIPOTERRENO']);
            oci_bind_by_name($stmt, ':PAR_VALORUNITARIORESIDUAL',$valNull);        
            oci_bind_by_name($stmt, ':PAR_IDAVALUO',$idAvaluo);   
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUO", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $resInsertTerreno, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);
            return $resInsertTerreno[0]['IDTERRENOMERCADO'];
        }catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            return $th;
            return 'Error al insertar en la tabla FEXAVA_TERRENOMERCADO';
        }
    }*/
    
    public function insertTerrenoMercado($elementosTabla,$idAvaluo){
        try{
            $elementosTabla['IDAVALUO'] = $idAvaluo;
            if(isset($arrElementos['VALORUNITARIORESIDUAL'])){

            }else{
                $elementosTabla['VALORUNITARIORESIDUAL'] = 'NULL';
            }
            $iniQuery = "INSERT INTO FEXAVA_TERRENOMERCADO";
            $campos = '(';
            $valores = '(';
            foreach($elementosTabla as $idElemento => $elemento){
                $campos .= $idElemento.",";
                if($elemento == 'NULL'){
                    $valores .= $elemento.","; 
                }else{
                    $valores .= "'".$elemento."',";
                }
                
            }
            $campos = substr($campos,0,strlen($campos) - 1);
            $valores = substr($valores,0,strlen($valores) - 1);
            $query = $iniQuery.$campos.") VALUES ".$valores.")";
            //echo $query."\n\n"; 
            $resInsert = $this->ejecutaQuery($query,'FEXAVA_TERRENOMERCADO');

            if(strpos($resInsert, 'Error') != FALSE){
                return $resInsert;
            }else{
                if($elementosTabla['VALORUNITARIORESIDUAL'] == 'NULL'){
                    //echo "SELECT IDTERRENOMERCADO  FROM FEXAVA_TERRENOMERCADO WHERE VALORUNITARIOTIERRAPROMEDIO = '".$elementosTabla['VALORUNITARIOTIERRAPROMEDIO']."' AND VALORUNITARIOTIERRAHOMOLOGADO = '".$elementosTabla['VALORUNITARIOTIERRAHOMOLOGADO']."' AND CODTIPOTERRENO = '".$elementosTabla['CODTIPOTERRENO']."' AND VALORUNITARIORESIDUAL IS NULL AND IDAVALUO = $idAvaluo";
                    $resTerrenoMercado =  DB::select("SELECT IDTERRENOMERCADO  FROM FEXAVA_TERRENOMERCADO WHERE VALORUNITARIOTIERRAPROMEDIO = '".$elementosTabla['VALORUNITARIOTIERRAPROMEDIO']."' AND VALORUNITARIOTIERRAHOMOLOGADO = '".$elementosTabla['VALORUNITARIOTIERRAHOMOLOGADO']."' AND CODTIPOTERRENO = '".$elementosTabla['CODTIPOTERRENO']."' AND VALORUNITARIORESIDUAL IS NULL AND IDAVALUO = $idAvaluo"); 
                }else{
                    $resTerrenoMercado = DB::select("SELECT IDTERRENOMERCADO  FROM FEXAVA_TERRENOMERCADO WHERE VALORUNITARIOTIERRAPROMEDIO = '".$elementosTabla['VALORUNITARIOTIERRAPROMEDIO']."' AND VALORUNITARIOTIERRAHOMOLOGADO = '".$elementosTabla['VALORUNITARIOTIERRAHOMOLOGADO']."' AND CODTIPOTERRENO = '".$elementosTabla['CODTIPOTERRENO']."' AND VALORUNITARIORESIDUAL = '".$elementosTabla['VALORUNITARIORESIDUAL']."' AND IDAVALUO = $idAvaluo"); 
                }
                return $resTerrenoMercado[0]->idterrenomercado; 
                
            }

        }catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            
            //return $th;
            return 'Error al insertar en la tabla FEXAVA_TERRENOMERCADO';
        }
    }

    

    /*public function insertConstruccionesMer($arrElementos,$idAvaluo){
        try {            
            $valNull = NULL;
            $procedure = 'BEGIN
            FEXAVA.FEXAVA_DATOSESTADISTICOS_PKG.FEXAVA_INSERT_CONSTRUCMER_P(
                :PAR_VALORUNITARIOPROMEDIO,
                :PAR_VALORUNITARIOHOMOLOGADO,
                :PAR_VALORUNITARIOAPLICABLE,
                :PAR_IDMODOCONSTRUCCION,
                :PAR_IDAVALUO,
                :C_AVALUO
            ); END;';

            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_VALORUNITARIOPROMEDIO',$arrElementos['VALORUNITARIOPROMEDIO']);
            oci_bind_by_name($stmt, ':PAR_VALORUNITARIOHOMOLOGADO',$arrElementos['VALORUNITARIOHOMOLOGADO']);
            oci_bind_by_name($stmt, ':PAR_VALORUNITARIOAPLICABLE',$arrElementos['VALORUNITARIOAPLICABLE']);
            oci_bind_by_name($stmt, ':PAR_IDMODOCONSTRUCCION',$arrElementos['IDMODOCONSTRUCCION']);        
            oci_bind_by_name($stmt, ':PAR_IDAVALUO',$idAvaluo);   
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUO", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $resInsertConstruccionesMer, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);
            return $resInsertConstruccionesMer[0]['IDCONSTRUCCIONESMERCADO'];
        }catch (\Throwable $th) {
            error_log($th);
            Log::info($th);            
            return 'Error al insertar en la tabla FEXAVA_CONSTRUCCIONESMER';
        }
    }*/

    public function insertConstruccionesMer($elementosTabla,$idAvaluo){
        try{
            //print_r($elementosTabla);
            $elementosTabla['IDAVALUO'] = $idAvaluo;
            
            $iniQuery = "INSERT INTO FEXAVA_CONSTRUCCIONESMER";
            $campos = '(';
            $valores = '(';
            foreach($elementosTabla as $idElemento => $elemento){
                $campos .= $idElemento.",";                
                $valores .= "'".$elemento."',";    
            }
            $campos = substr($campos,0,strlen($campos) - 1);
            $valores = substr($valores,0,strlen($valores) - 1);
            $query = $iniQuery.$campos.") VALUES ".$valores.")";
            //echo $query."\n\n"; 
            $resInsert = $this->ejecutaQuery($query,'FEXAVA_CONSTRUCCIONESMER');

            if(strpos($resInsert, 'Error') != FALSE){
                return $resInsert;
            }else{
                //echo "SELECT IDCONSTRUCCIONESMERCADO  FROM FEXAVA_CONSTRUCCIONESMER WHERE VALORUNITARIOPROMEDIO = '".$elementosTabla['VALORUNITARIOPROMEDIO']."' AND VALORUNITARIOHOMOLOGADO = '".$elementosTabla['VALORUNITARIOHOMOLOGADO']."' AND VALORUNITARIOAPLICABLE = '".$elementosTabla['VALORUNITARIOAPLICABLE']."' AND IDMODOCONSTRUCCION = '".$elementosTabla['IDMODOCONSTRUCCION']."' AND IDAVALUO = $idAvaluo";                
                $resConstruccionMer = DB::select("SELECT IDCONSTRUCCIONESMERCADO  FROM FEXAVA_CONSTRUCCIONESMER WHERE VALORUNITARIOPROMEDIO = '".$elementosTabla['VALORUNITARIOPROMEDIO']."' AND VALORUNITARIOHOMOLOGADO = '".$elementosTabla['VALORUNITARIOHOMOLOGADO']."' AND VALORUNITARIOAPLICABLE = '".$elementosTabla['VALORUNITARIOAPLICABLE']."' AND IDMODOCONSTRUCCION = '".$elementosTabla['IDMODOCONSTRUCCION']."' AND IDAVALUO = $idAvaluo");                 
                //print_r($resConstruccionMer); exit();
                return $resConstruccionMer[0]->idconstruccionesmercado;                
            }

        }catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            
            //return $th;
            return 'Error al insertar en la tabla FEXAVA_TERRENOMERCADO';
        }
    }

    private function ejecutaQuery($query, $tabla){
        try{
            DB::insert($query);
            DB::commit();
            return TRUE;
        }catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            return 'Error al insertar en la tabla '.$tabla;
        }
        
    }

    public function pruebaCat(){
        $conn = oci_connect(env("DB_USERNAME_CAS"), env("DB_PASSWORD_CAS"), env("DB_TNS"));        
        $sqlcadena = oci_parse($conn, "SELECT * FROM CAS.CAS_CATINSTESPECIALES WHERE CODINSTESPECIALES = '30'");
        oci_execute($sqlcadena);

        $fila = oci_fetch_array($sqlcadena, OCI_ASSOC+OCI_RETURN_NULLS);
        oci_free_statement($sqlcadena);
        oci_close($conn);
        print_r($fila); exit();
        return $fila;
    }
    
}