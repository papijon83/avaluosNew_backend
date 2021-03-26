<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Documentos
{

    /*public function insertDocumentoDigital($cuentaCatastral,$tipoDocumentoDigital,$idUsuario){
        
        $idDocumentoDigital = 0;
        $descripcion = "Avaluo_".$cuentaCatastral;
        $fecha_hoy = new Carbon(date('Y-m-d'));
        $fecha = $fecha_hoy->format('d/m/y');
        //echo "SOY FECHA ".$fecha; exit();
        $procedure = 'BEGIN
        DOC.DOC_DOCUMENTOS_DIGITALES_PCKG.DOC_INSERTDOCUMENTODIGITAL_P(
            :P_DESCRIPCION,
            :P_IDTIPODOCUMENTODIGITAL,
            :P_FECHA,
            :P_IDUSUARIO,
            :P_IDDOCUMENTODIGITAL
        ); END;';
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($procedure);
        $stmt->bindParam(':P_DESCRIPCION', $descripcion, \PDO::PARAM_STR);
        $stmt->bindParam(':P_IDTIPODOCUMENTODIGITAL',$tipoDocumentoDigital, \PDO::PARAM_INT);
        $stmt->bindParam(':P_FECHA',$fecha,\PDO::PARAM_STR);
        $stmt->bindParam(':P_IDUSUARIO',$idUsuario,\PDO::PARAM_INT);
        $stmt->bindParam(':P_IDDOCUMENTODIGITAL',$idDocumentoDigital,\PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
        $pdo->commit();
        $pdo->close();
        DB::commit();
        DB::reconnect();
        return $idDocumentoDigital ? $idDocumentoDigital : 0;    

    }*/

    public function tran_InsertAvaluo($descripcion,$tipoDocumentoDigital,$fecha,$idUsuario){
        try{
            $idDocumentoDigital = 0;
            //$descripcion = "Avaluo_".$cuentaCatastral;
            $fecha_hoy = new Carbon($fecha);
            $fecha = $fecha_hoy->format('Y/m/d');
            //echo $descripcion." ".$tipoDocumentoDigital." ".$fecha." ".$idUsuario; exit();
            //echo "SOY FECHA ".$fecha; exit();
            $procedure = 'BEGIN
            DOC.DOC_DOCUMENTOS_DIGITALES_PCKG.DOC_INSERTDOCUMENTODIGITAL_P(
                :P_DESCRIPCION,
                :P_IDTIPODOCUMENTODIGITAL,
                :P_FECHA,
                :P_IDUSUARIO,
                :P_IDDOCUMENTODIGITAL
            ); END;';
            $pdo = DB::getPdo();
            $stmt = $pdo->prepare($procedure);
            $stmt->bindParam(':P_DESCRIPCION', $descripcion, \PDO::PARAM_STR);
            $stmt->bindParam(':P_IDTIPODOCUMENTODIGITAL',$tipoDocumentoDigital, \PDO::PARAM_INT);
            $stmt->bindParam(':P_FECHA',$fecha,\PDO::PARAM_STR);
            $stmt->bindParam(':P_IDUSUARIO',$idUsuario,\PDO::PARAM_INT);
            $stmt->bindParam(':P_IDDOCUMENTODIGITAL',$idDocumentoDigital,\PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            $pdo->commit();
            $pdo->close();
            DB::commit();
            DB::reconnect();
            return $idDocumentoDigital ? $idDocumentoDigital : 0; 
        }catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error en el servidor'], 500);
        }
           

    }

    public function insertDocumentoDigitalFoto($descripcion,$tipoDocumentoDigital,$fechaAvaluo,$idUsuario){
        
        $idDocumentoDigital = 0;
        //$descripcion = $nombreFoto;
        $fecha_ini = new Carbon($fechaAvaluo);
        $fecha = $fecha_ini->format('Y/m/d');
        //echo "SOY FECHA ".$fecha; exit();
        $procedure = 'BEGIN
        DOC.DOC_DOCUMENTOS_DIGITALES_PCKG.DOC_INSERTDOCUMENTODIGITAL_P(
            :P_DESCRIPCION,
            :P_IDTIPODOCUMENTODIGITAL,
            :P_FECHA,
            :P_IDUSUARIO,
            :P_IDDOCUMENTODIGITAL
        ); END;';
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($procedure);
        $stmt->bindParam(':P_DESCRIPCION', $descripcion, \PDO::PARAM_STR);
        $stmt->bindParam(':P_IDTIPODOCUMENTODIGITAL',$tipoDocumentoDigital, \PDO::PARAM_INT);
        $stmt->bindParam(':P_FECHA',$fecha,\PDO::PARAM_STR);
        $stmt->bindParam(':P_IDUSUARIO',$idUsuario,\PDO::PARAM_INT);
        $stmt->bindParam(':P_IDDOCUMENTODIGITAL',$idDocumentoDigital,\PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
        $pdo->commit();
        $pdo->close();
        DB::commit();
        DB::reconnect();
        return $idDocumentoDigital ? $idDocumentoDigital : 0;    

    }

    public function tran_InsertFichero($idDocumentoDigital,$nombre,$descripcion,$binarioDatos){
        
        $idficherodoc = 0;     
        $procedure = 'BEGIN
        DOC.DOC_DOCUMENTOS_DIGITALES_PCKG.DOC_INSERTFICHERODOCUMENTO_P(
            :P_IDDOCUMENTODIGITAL,
            :P_NOMBRE,
            :P_DESCRIPCION,
            :P_BINARIODATOS,
            :P_IDFICHERODOC
        ); END;';
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($procedure);
        $stmt->bindParam(':P_IDDOCUMENTODIGITAL', $idDocumentoDigital, \PDO::PARAM_INT);
        $stmt->bindParam(':P_NOMBRE',$nombre, \PDO::PARAM_STR);
        $stmt->bindParam(':P_DESCRIPCION',$descripcion,\PDO::PARAM_STR);
        $stmt->bindParam(':P_BINARIODATOS',$binarioDatos,\PDO::PARAM_LOB);
        $stmt->bindParam(':P_IDFICHERODOC',$idficherodoc,\PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
        $pdo->commit();
        $pdo->close();
        DB::commit();
        DB::reconnect();
        return $idficherodoc ? $idficherodoc : 0;    

    }    

    public function tran_DeleteFichero($idFicheroDocumento){
        
        $idficherodoc = 0;     
        $procedure = 'BEGIN
        DOC.DOC_DOCUMENTOS_DIGITALES_PCKG.DOC_DELETEFICHERODOCUMENTO_P(
            :P_LISTAIDFICHEROSDOCUMENTO
        ); END;';
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($procedure);
        $stmt->bindParam(':P_LISTAIDFICHEROSDOCUMENTO', $idFicheroDocumento, \PDO::PARAM_INT);        
        $stmt->execute();
        $stmt->closeCursor();
        $pdo->commit();
        $pdo->close();
        DB::commit();
        DB::reconnect();
        //return $idficherodoc ? $idficherodoc : 0;    

    }

    public function tran_InsertFicheroAvaluo($idDocumentoDigital,$nombre,$descripcion,$binarioDatos){
        
        $idficherodoc = 0;
        $rutaArchivos = getcwd();
        $nombreDes = $binarioDatos;

        $myfile = fopen($rutaArchivos."/".$nombreDes, "r");
        $binarioDatos = fread($myfile, filesize($rutaArchivos."/".$nombreDes));        
        fclose($myfile);  

        $procedure = 'BEGIN
        DOC.DOC_DOCUMENTOS_DIGITALES_PCKG.DOC_INSERTFICHERODOCUMENTO_P(
            :P_IDDOCUMENTODIGITAL,
            :P_NOMBRE,
            :P_DESCRIPCION,
            :P_BINARIODATOS,
            :P_IDFICHERODOC
        ); END;';
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($procedure);
        $stmt->bindParam(':P_IDDOCUMENTODIGITAL', $idDocumentoDigital, \PDO::PARAM_INT);
        $stmt->bindParam(':P_NOMBRE',$nombre, \PDO::PARAM_STR);
        $stmt->bindParam(':P_DESCRIPCION',$descripcion,\PDO::PARAM_STR);
        $stmt->bindParam(':P_BINARIODATOS',$binarioDatos,\PDO::PARAM_LOB);
        $stmt->bindParam(':P_IDFICHERODOC',$idficherodoc,\PDO::PARAM_INT);
        $stmt->execute();
        $stmt->closeCursor();
        $pdo->commit();
        $pdo->close();
        DB::commit();
        DB::reconnect();

        shell_exec("rm -f ".$rutaArchivos."/".str_replace(" ","\ ",$nombreDes));

        return $idficherodoc ? $idficherodoc : 0;    

    }

    public function tran_UpdateFicheroAvaluo($idFicheroDocumento,$nombre,$descripcion,$binarioDatos,$fecha){
        
        $rutaArchivos = getcwd();
        $nombreDes = $binarioDatos;

        $myfile = fopen($rutaArchivos."/".$nombreDes, "r");
        $binarioDatos = fread($myfile, filesize($rutaArchivos."/".$nombreDes));        
        fclose($myfile);

        $procedure = 'BEGIN
        DOC.DOC_DOCUMENTOS_DIGITALES_PCKG.DOC_UPDATEFICHERODOCUMENTO_P(
            :P_IDFICHERODOCUMENTO,
            :P_FICHERO,
            :P_NOMBREFICHERO,
            :P_DESCRIPCION,            
            :P_FECHA
        ); END;';
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($procedure);
        $stmt->bindParam(':P_IDFICHERODOCUMENTO', $idFicheroDocumento, \PDO::PARAM_INT);
        $stmt->bindParam(':P_FICHERO',$binarioDatos,\PDO::PARAM_LOB);
        $stmt->bindParam(':P_NOMBREFICHERO',$nombre, \PDO::PARAM_STR);
        $stmt->bindParam(':P_DESCRIPCION',$descripcion,\PDO::PARAM_STR);        
        $stmt->bindParam(':P_FECHA',$fecha,\PDO::PARAM_STR);
        $stmt->execute();
        $stmt->closeCursor();
        $pdo->commit();
        $pdo->close();
        DB::commit();
        DB::reconnect();

        shell_exec("rm -f ".$rutaArchivos."/".str_replace(" ","\ ",$nombreDes));    

    }

    public function InsertFotoInmueble($idDocumentoDigital, $tipoFotoInm){

        $procedure = 'BEGIN
        DOC.DOC_DOCUMENTOS_DIGITALES_PCKG.DOC_INSERTDOCFOTOINMUEBLE_P(
            :P_IDDOCUMENTODIGITAL,
            :P_CODTIPOFOTOINMUEBLE
        ); END;';
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($procedure);
        $stmt->bindParam(':P_IDDOCUMENTODIGITAL', $idDocumentoDigital, \PDO::PARAM_INT);
        $stmt->bindParam(':P_CODTIPOFOTOINMUEBLE',$tipoFotoInm, \PDO::PARAM_STR);        
        $stmt->execute();
        $stmt->closeCursor();
        $pdo->commit();
        $pdo->close();
        DB::commit();
        DB::reconnect();
    }

    public function tran_InsertFotoInmueble($fichero, $nombreFoto, $descripcion, $fechaAvaluo, $tipoFoto, $idUsuario){
        //$descripcion = "Foto_".$nombreFoto;
        $idDocumentoDigital = $this->insertDocumentoDigitalFoto($descripcion,3,$fechaAvaluo,$idUsuario); //error_log("IDDOCUMENTODIG ".$idDocumentoDigital);
        $idficherodoc = $this->tran_InsertFichero($idDocumentoDigital,$nombreFoto,$nombreFoto,$fichero); //error_log("idficherodoc ".$idficherodoc);
        $this->InsertFotoInmueble($idDocumentoDigital, $tipoFoto);
        return $idDocumentoDigital ? $idDocumentoDigital : 0;
    }

    public function get_numero_unico_db($idAvaluo){         
        $numeroUnicoAvaluo = convierte_a_arreglo(DB::select("SELECT NUMEROUNICO FROM FEXAVA_AVALUO WHERE IDAVALUO = $idAvaluo"));
        return trim($numeroUnicoAvaluo[0]['numerounico']);
    }

    public function get_idavaluo_db($numeroUnico){
        $numeroUnicoAvaluo = convierte_a_arreglo(DB::select("SELECT IDAVALUO FROM FEXAVA_AVALUO WHERE NUMEROUNICO = '$numeroUnico'"));
        return trim($numeroUnicoAvaluo[0]['idavaluo']);
    }

    public function valida_existencia($numeroAvaluo,$idpersona_perito){
        $rowsAvaluos = convierte_a_arreglo(DB::select("SELECT * FROM FEXAVA_AVALUO WHERE NUMEROAVALUO = '$numeroAvaluo' AND IDPERSONAPERITO = $idpersona_perito AND CODESTADOAVALUO != 2"));
        if(count($rowsAvaluos) > 0){
            return TRUE;
        }else{
            return FALSE;
        }
    }

    public function get_valuador($idPeritoSociedad){
        $claveValuador = DB::select("SELECT REGISTRO FROM RCON.RCON_PERITO WHERE IDPERSONA = $idPeritoSociedad");
        $arrClaveValuador = convierte_a_arreglo($claveValuador);
        return $arrClaveValuador[0]['registro'];
    }

    public function get_regimen_propiedad($codRegimenPropiedad){
        $regimenPropiedad = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATREGIMENPROPIEDAD WHERE CODREGIMENPROPIEDAD = $codRegimenPropiedad");
        $arrRegimenPropiedad = convierte_a_arreglo($regimenPropiedad);
        return $arrRegimenPropiedad[0]['descripcion'];
    }

    public function get_clasificacion_zona($codClasificacionZona){
        $clasificacionZona = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATCLASIFICACIONZONA WHERE CODCLASIFICACIONZONA = $codClasificacionZona");
        $arrClasificacionZona = convierte_a_arreglo($clasificacionZona);
        return $arrClasificacionZona[0]['descripcion'];
    }

    public function get_densidad_poblacion($codDensidadPoblacion){
        $densidadPoblacion = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATDENSIDADPOB WHERE CODDENSIDADPOBLACION = $codDensidadPoblacion");
        $arrDensidadPoblacion = convierte_a_arreglo($densidadPoblacion);
        return $arrDensidadPoblacion[0]['descripcion'];
    }

    public function get_densidad_habitacional($codDensidadHabitacional){
        $densidadHabitacional = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATDENSIDADHAB WHERE CODDENSIDADHABITACIONAL = $codDensidadHabitacional");
        $arrDensidadHabitacional = convierte_a_arreglo($densidadHabitacional);
        return $arrDensidadHabitacional[0]['descripcion'];
    }

    public function get_nivel_socioeconomico_zona($codNivelSocioeconomico){
        $nivelSocioecon = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATNIVELSOCIOECON WHERE CODNIVELSOCIOECONOMICO = $codNivelSocioeconomico");
        $arrNivelSocioecon = convierte_a_arreglo($nivelSocioecon);
        return $arrNivelSocioecon[0]['descripcion'];
    }

    public function get_red_agua_potable($codRedAguaPotable){
        $aguaPotable = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATAGUAPOTABLE WHERE CODAGUAPOTABLE = $codRedAguaPotable");
        $arrAguaPotable = convierte_a_arreglo($aguaPotable);
        if(isset($arrAguaPotable[0]['descripcion'])){
            return  $arrAguaPotable[0]['descripcion'];
        }    
    }

    public function get_drenaje_pluvial_calle_zona($codDrenajePluvial){
        $drenajePluvial = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATDRENAJEPLUVIAL WHERE CODDRENAJEPLUVIAL = $codDrenajePluvial");
        $arrDrenajePluvial = convierte_a_arreglo($drenajePluvial);
        return $arrDrenajePluvial[0]['descripcion'];
    }

    public function get_drenaje_mixto($codRedAguaResidual){
        $drenajeInmueble = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATDRENAJEINMUEBLE WHERE CODDRENAJEINMUEBLE = $codRedAguaResidual");
        $arrDrenajeInmueble = convierte_a_arreglo($drenajeInmueble);
        return $arrDrenajeInmueble[0]['descripcion'];
    }

    public function get_suministro_electrico($codSuministroElec){
        $suministroElectrico = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATSUMINISTROELEC WHERE CODSUMINISTROELECTRICO = $codSuministroElec");
        $arrSuministroElectrico = convierte_a_arreglo($suministroElectrico);
        return $arrSuministroElectrico[0]['descripcion'];
    }

    public function get_acometida_inmueble($codAcometidaInmueble){
        $acometida = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATACOMETIDAINM WHERE CODACOMETIDAINMUEBLE = $codAcometidaInmueble");
        $arrAcometida = convierte_a_arreglo($acometida);
        return $arrAcometida[0]['descripcion'];
    }

    public function get_alumbrado_publico($codAlumbradoPublico){
        $alumbradoPublico = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATALUMBRADOPUBLICO WHERE CODALUMBRADOPUBLICO = $codAlumbradoPublico");
        $arrAlumbradoPublico = convierte_a_arreglo($alumbradoPublico);
        return $arrAlumbradoPublico[0]['descripcion'];
    }

    public function get_vialidades($codVialidades){
        $vialidades = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATVIALIDADES WHERE CODVIALIDADES = $codVialidades");
        $arrVialidades = convierte_a_arreglo($vialidades);
        return $arrVialidades[0]['descripcion'];
    }

    public function get_banquetas($codBanqueta){
        $banquetas = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATBANQUETAS WHERE CODBANQUETAS = $codBanqueta");
        $arrBanquetas = convierte_a_arreglo($banquetas);
        return $arrBanquetas[0]['descripcion'];
    }

    public function get_guarniciones($codGuarnicion){
        $guarniciones = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATGUARNICIONES WHERE CODGUARNICIONES = $codGuarnicion");
        $arrGuarniciones = convierte_a_arreglo($guarniciones);
        return $arrGuarniciones[0]['descripcion'];
    }

    public function get_gas_natural($codGasNatural){
        $gasNatural = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATGASNATURAL WHERE CODGASNATURAL = $codGasNatural");
        $arrGasNatural = convierte_a_arreglo($gasNatural);
        return $arrGasNatural[0]['descripcion'];
    }

    public function get_suministro_tel($codSuministroTel){
        $suministroTel = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATSUMINISTROTEL WHERE CODSUMINISTROTELEFONICA = $codSuministroTel");
        $arrSuministroTel = convierte_a_arreglo($suministroTel);
        return $arrSuministroTel[0]['descripcion'];
    }

    public function get_senal_vias($codSenalVias){
        $senalVias = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATSENALIZACIONVIAS WHERE CODSENALIZACIONVIAS = $codSenalVias");
        $arrSenalVias = convierte_a_arreglo($senalVias);
        return $arrSenalVias[0]['descripcion'];
    }

    public function get_acometida_inmueble_tel($codAcometidaInmueble){
        $acometida = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATACOMETIDAINM WHERE CODACOMETIDAINMUEBLE = $codAcometidaInmueble");
        $arrAcometida = convierte_a_arreglo($acometida);
        return $arrAcometida[0]['descripcion'];
    }

    public function get_vigilancia_zona($codVigilancia){
        $vigilancia = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATVIGILANCIAZONA WHERE CODVIGILANCIAZONA = $codVigilancia");
        $arrVigilancia = convierte_a_arreglo($vigilancia);
        return $arrVigilancia[0]['descripcion'];
    }

    public function get_recoleccion_basura($cod){
        $info = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATRECOLECCIONBASURA WHERE CODRECOLECCIONBASURA = $cod");
        $arrInfo = convierte_a_arreglo($info);
        return $arrInfo[0]['descripcion'];
    }

    public function get_nomenclatura_calle($cod){
        $info = DB::select("SELECT DESCRIPCION FROM FEXAVA_CATNOMENCLATURACALLE WHERE CODNOMENCLATURACALLE = $cod");
        $arrInfo = convierte_a_arreglo($info);
        return $arrInfo[0]['descripcion'];
    }

    public function get_fichero_documento($idFichero){ //echo "SELECT BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDFICHERODOCUMENTO = $idFichero"; exit();
        $info = DB::select("SELECT BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDFICHERODOCUMENTO = $idFichero");
        $arrInfo = $info[0]; //print_r($arrInfo->binariodatos); exit();
        return $arrInfo->binariodatos;
    }

    public function get_fichero_foto($idFichero){ //echo "SELECT BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDFICHERODOCUMENTO = $idFichero"; exit();
        $info = DB::select("SELECT BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idFichero");
        $arrInfo = $info[0]; //print_r($arrInfo->binariodatos); exit();
        return $arrInfo->binariodatos;
    }

    public function get_nombre_perito($claveValuador){ //echo "SELECT IDPERSONA FROM RCON.RCON_PERITO WHERE REGISTRO = '$claveValuador'"; exit();
        $info = DB::select("SELECT IDPERSONA FROM RCON.RCON_PERITO WHERE REGISTRO = '$claveValuador'");
        $arrInfo = convierte_a_arreglo($info); //print_r($arrInfo->binariodatos); exit();
        
        if(count($arrInfo) > 0){
            $idpersona = $arrInfo[0]['idpersona'];
            $infoNombre = DB::select("SELECT APELLIDOPATERNO, APELLIDOMATERNO, NOMBRE FROM RCON.RCON_PERSONAFISICA WHERE IDPERSONA = $idpersona");
            $arrInfoPersona = convierte_a_arreglo($infoNombre); //print_r($arrInfo->binariodatos); exit();
            $nombrePerito = $arrInfoPersona[0]['apellidopaterno']." ".$arrInfoPersona[0]['apellidomaterno']." ".$arrInfoPersona[0]['nombre'];
            return $nombrePerito;
        }else{
            //Esto puede suceder debido a que en algunos XML ya cargados la clave del valuador tiene ceros de mas o ceros de menos comparadas con las claves que estan en la BD
            return '';
        }
        
    }

    public function get_nombre_sociedad($claveSociedad){ //echo "SELECT IDPERSONA FROM RCON.RCON_PERITO WHERE REGISTRO = '$claveValuador'"; exit();
        $info = DB::select("SELECT IDPERSONA FROM RCON.RCON_SOCIEDADVALUACION WHERE REGISTRO = '$claveSociedad'");
        $arrInfo = convierte_a_arreglo($info); //print_r($arrInfo->binariodatos); exit();
        
        if(count($arrInfo) > 0){
            $idpersona = $arrInfo[0]['idpersona'];
            $infoNombre = DB::select("SELECT NOMBRE FROM RCON.RCON_PERSONAMORAL WHERE IDPERSONA = $idpersona");
            $arrInfoPersona = convierte_a_arreglo($infoNombre); //print_r($arrInfo->binariodatos); exit();
            $nombrePerito = $arrInfoPersona[0]['nombre'];
            return $nombrePerito;
        }else{
            //Esto puede suceder debido a que en algunos XML ya cargados la clave del valuador tiene ceros de mas o ceros de menos comparadas con las claves que estan en la BD
            return '';
        }
        
    }

    public function ObtenerNombreDelegacionPorClave($clave)
    {

        if(strlen($clave) == 3){
            $clave = substr($clave,1,2);
        }

        $arr = array('02' => 'AZCAPOTZALCO', '03' => 'COYOACÁN', '04' => 'CUAJIMALPA DE MORELOS', '05' => 'GUSTAVO A. MADERO', '06' => 'IZTACALCO', '07' => 'IZTAPALAPA', '08' => 'MAGDALENA CONTRERAS', '09' => 'MILPA ALTA', '10' => 'ÁLVARO OBREGON', '11' => 'TLÁHUAC', '12' => 'TLALPAN', '13' => 'XOCHIMILCO', '14' => 'BENITO JUÁREZ', '15' => 'CUAUHTÉMOC', '16' => 'MIGUEL HIDALGO', '17' => 'VENUSTIANO CARRANZA', '18' => 'OTROS');

        if(isset($arr[$clave])){
            return $arr[$clave];
        }else{
           return $clave;
        }

        /*$rowsDelegaciones = DB::select("SELECT nombre FROM CAS.CAS_DELEGACION WHERE CLAVE = '$clave'");

        if (count($rowsDelegaciones) > 0)
        {
            $nombre = $rowsDelegaciones[0]->nombre;
        }
        else
        {
            return $clave;
        }

        return $nombre;*/
    }

    public function guardaResultado($idAvaluo, $idUsuario, $estatus, $mensajeRespuesta){

        
        if(trim($estatus) == ''){
            $estatus = 0;
        }
        //echo "INFORMACION ".$idAvaluo." ".$idUsuario." ".$estatus." ".$mensajeRespuesta; //print_r($estatus); exit();
        $resCod = '';
        $resDesc = '';        
        $procedure = 'BEGIN
        FEXAVA.FEXAVA_AVALUOS_PKG.FEXAVA_INSERT_AVALU_ENVIOXML_P(
            :PAR_IDAVALUO,
            :PAR_IDPERSONAPERITO,
            :PAR_ESTATUS_ENVIO_WS,
            :PAR_MENSAJE_RESPUESTA_WS,
            :P_COD,
            :P_DESC
        ); END;';
        $pdo = DB::getPdo();
        $stmt = $pdo->prepare($procedure);
        $stmt->bindParam(':PAR_IDAVALUO', $idAvaluo, \PDO::PARAM_INT);
        $stmt->bindParam(':PAR_IDPERSONAPERITO',$idUsuario, \PDO::PARAM_INT);
        $stmt->bindParam(':PAR_ESTATUS_ENVIO_WS', $estatus, \PDO::PARAM_INT);
        $stmt->bindParam(':PAR_MENSAJE_RESPUESTA_WS', $mensajeRespuesta, \PDO::PARAM_STR);
        $stmt->bindParam(':P_COD', $resCod, \PDO::PARAM_INT);
        $stmt->bindParam(':P_DESC', $resDesc, \PDO::PARAM_STR,4000);
        $stmt->execute();
        $stmt->closeCursor();
        $pdo->commit();
        $pdo->close();
        DB::commit();
        DB::reconnect();    
        return $resCod." ".$resDesc;
    }
    
}