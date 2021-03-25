<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Documentos;
use App\Models\Fis;
use Log;

class ReimpresionNuevo
{
    protected $modelDocumentos;
    protected $modelFis;

    public function infoAcuse($idavaluo){
        $this->modelDocumentos = new Documentos();      
        //echo "SELECT NOMBRE, BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idavaluo AND NOMBRE LIKE 'Avaluo_%'"; exit();
        $infoArchivo = DB::select("SELECT NOMBRE, BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idavaluo AND NOMBRE LIKE 'Avaluo_%'");
        //$arrInfoArchivo = convierte_a_arreglo($infoArchivo);
        $rutaArchivos = getcwd();
        $nombreArchivo = $infoArchivo[0]->nombre;
        $archivoComprimido = $infoArchivo[0]->binariodatos;
        $myfile = fopen($rutaArchivos."/".$nombreArchivo, "a+");
        fwrite($myfile,$archivoComprimido);
        fclose($myfile);
        $comandoNombre = "7z l ".$rutaArchivos."/".$nombreArchivo;
        $datosNombre = shell_exec($comandoNombre);
        $comandoDescomprimir = "7z e ".$rutaArchivos."/".$nombreArchivo;   
        shell_exec($comandoDescomprimir);
        $comandoRm = "rm ".$rutaArchivos."/".$nombreArchivo;
        shell_exec($comandoRm);
        if(file_exists($rutaArchivos."/default") === TRUE){    
            $myfile = fopen($rutaArchivos."/default", "r");
            $contenidoArchivo = fread($myfile, filesize($rutaArchivos."/default"));
            fclose($myfile);  

        }else{

            $comandols = "ls php*";
            $archphp = shell_exec($comandols);
            if(substr(trim($archphp),0,3) == 'php'){
                $comandoMv = "mv ".$rutaArchivos."/"."php* ".$rutaArchivos."/"."default";
                system($comandoMv);
            }

            $myfile = fopen($rutaArchivos."/default", "r");
            $contenidoArchivo = fread($myfile, filesize($rutaArchivos."/default"));
            fclose($myfile);
        }
                
        $xml = simplexml_load_string($contenidoArchivo,'SimpleXMLElement', LIBXML_NOCDATA);
        $comandoRmDefault = "rm ".$rutaArchivos."/default";
        shell_exec($comandoRmDefault);
        $arrXML = convierte_a_arreglo($xml); //echo $contenidoArchivo; exit();           

        if(isset($arrXML['Comercial'])){
            $elementoPrincipal = $arrXML['Comercial'];
            $tipoDeAvaluo =  "Comercial";
        }

        if(isset($arrXML['Catastral'])){
            $elementoPrincipal = $arrXML['Catastral'];
            $tipoDeAvaluo =  "Catastral";
        }
        //print($elementoPrincipal); exit();
        
        $arrInfoAcuse = array(); //echo "SELECT NUMEROUNICO FROM FEXAVA_AVALUO WHERE IDAVALUO = $idavaluo"; exit();
        $numeroUnico = DB::select("SELECT NUMEROUNICO FROM FEXAVA_AVALUO WHERE IDAVALUO = $idavaluo");
        $arrInfoAcuse['numeroUnico'] = $numeroUnico[0]->numerounico; //print_r($arrInfoAcuse); exit();
        $camposCuentaCatastral = array('region','manzana','lote','unidadprivativa','digitoverificador');
        $infoCuentaCatastral = array();
        foreach($camposCuentaCatastral as $campoBuscar){
            $arrDato = DB::select("SELECT ".$campoBuscar." FROM FEXAVA_AVALUO WHERE IDAVALUO = $idavaluo");            
            //$campoEncontrado = strtolower($campoBuscar);
            $infoCuentaCatastral[$campoBuscar] = $arrDato[0]->$campoBuscar;            
        }
        $arrInfoAcuse['cuentaCatastral'] = $infoCuentaCatastral;
        /* $cuentaAgua = 'NO SE PROPORCIONO.';
        $arrInfoAcuse['cuentaAgua'] = $cuentaAgua;*/

        $infoPropietario = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idavaluo AND CODTIPOFUNCION = 'P'");
        $arrInfoPropietario = array_map("convierte_a_arreglo",$infoPropietario);
        $arrInfoAcuse['propietario'] = $arrInfoPropietario[0];
        $infoSolicitante = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idavaluo AND CODTIPOFUNCION = 'S'");
        $arrInfoSolicitante = array_map("convierte_a_arreglo",$infoSolicitante);
        $arrInfoAcuse['solicitante'] = $arrInfoSolicitante[0];

        $arrInfoAcuse['Ubicacion_Inmueble'] = array();
        $ubicacionInmueble = $elementoPrincipal['Antecedentes']['InmuebleQueSeValua'];

        $arrInfoAcuse['Ubicacion_Inmueble']['Calle'] = $ubicacionInmueble['Calle'];
        $arrInfoAcuse['Ubicacion_Inmueble']['No_Exterior'] = $ubicacionInmueble['NumeroExterior'];
        $arrInfoAcuse['Ubicacion_Inmueble']['No_Interior'] = $ubicacionInmueble['NumeroInterior'];
        $arrInfoAcuse['Ubicacion_Inmueble']['Colonia'] = $ubicacionInmueble['Colonia'];
        $arrInfoAcuse['Ubicacion_Inmueble']['CP'] = $ubicacionInmueble['CodigoPostal'];
        $arrInfoAcuse['Ubicacion_Inmueble']['Delegacion'] = isset($ubicacionInmueble['Delegacion']) ? $this->modelDocumentos->ObtenerNombreDelegacionPorClave($ubicacionInmueble['Delegacion']) : $this->modelDocumentos->ObtenerNombreDelegacionPorClave($ubicacionInmueble['Alcaldia']);
        $arrInfoAcuse['Ubicacion_Inmueble']['Edificio'] = isset($ubicacionInmueble['Edificio']) && !is_array($ubicacionInmueble['Edificio']) ? $ubicacionInmueble['Edificio'] : '-';
        $arrInfoAcuse['Ubicacion_Inmueble']['Lote'] = isset($ubicacionInmueble['Lote']) && !is_array($ubicacionInmueble['Lote']) ? $ubicacionInmueble['Lote'] : 0;
        if(isset($ubicacionInmueble['CuentaDeAgua']) && !is_array($ubicacionInmueble['CuentaDeAgua'])){
            $arrInfoAcuse['Ubicacion_Inmueble']['Cuenta_agua'] = $ubicacionInmueble['CuentaDeAgua'];
        }else{
            $arrInfoAcuse['Ubicacion_Inmueble']['Cuenta_agua'] = 'NO SE PROPORCIONO.';
        }
        

        
        $infoEscritura = DB::select("SELECT * FROM FEXAVA_ESCRITURA WHERE IDAVALUO = $idavaluo");
        if(count($infoEscritura) > 0){
            $arrInfoEscritura = array_map("convierte_a_arreglo",$infoEscritura);
            $arrInfoAcuse['escritura'] = $arrInfoEscritura[0];
        }else{
            $arrInfoAcuse['escritura']['idavaluo'] = $idavaluo;
            $arrInfoAcuse['escritura']['numescritura'] = '';
            $arrInfoAcuse['escritura']['numvolumen'] = '';
            $arrInfoAcuse['escritura']['numnotario'] = '';
            $arrInfoAcuse['escritura']['nombrenotario'] = '';
            $arrInfoAcuse['escritura']['distritojudicialnotario'] = '';
        } 
        //print_r($arrInfoAcuse); exit();
        $infoFuenteInformacion = DB::select("SELECT * FROM FEXAVA_FUENTEINFORMACIONLEG WHERE IDAVALUO = $idavaluo");
        $arrinfoFuenteInformacion = array_map("convierte_a_arreglo",$infoFuenteInformacion);
        $arrInfoAcuse['fuenteInformacionLegal'] = $arrinfoFuenteInformacion[0];
        /*
        if(isset($elementoPrincipal['Antecedentes']['TipoDeInmueble'])) {
            $arrInfoAcuse['tipoDeInmueble'] = $this->cat_tipo_inmueble($elementoPrincipal['Antecedentes']['TipoDeInmueble']);
        } else{
            $arrInfoAcuse['tipoDeInmueble'] = '';
        } */         
        
        return $arrInfoAcuse;        
    }

    public function cat_tipo_inmueble($id){
        $id = intval($id);
        $cat = array("1" => "BODEGA",
        "2"=>"CAJÓN DE ESTACIONAMIENTO" ,
        "3"=>"CASA HABITACION",
        "4"=>"CASA HABITACION EN CONDOMINIO",
        "5"=>"CENTRO COMERCIAL",
        "6"=>"DEPARTAMENTO EN CONDOMINIO",
        "7"=>"EDIFICIO DE DEPARTAMENTOS",
        "8"=>"EDIFICIO DE ESTACIONAMIENTO",
        "9"=>"EDIFICIO DE PRODUCTOS",
        "10"=>"ESCUELA",
        "11"=>"HOSPITAL",
        "12"=>"HOTEL",
        "13"=>"LOCAL COMERCIAL",
        "14"=>"NAVE INDUSTRIAL",
        "15"=>"OFICINA AISLADA",
        "16"=>"OFICINA EN CONDOMINIO",
        "17"=>"TERRENO CON USO COMERCIAL",
        "18"=>"TERRENO HABITACIONAL",
        "19"=>"OTRO");

        return $cat[$id];

    }
    
    public function infoAvaluo($idAvaluo){

        try{
        $this->modelFis = new Fis();
        $this->modelDocumentos = new Documentos();

        //echo "EL QUE LLEGA "."SELECT NOMBRE, BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idAvaluo"; exit();
        $infoArchivo = DB::select("SELECT NOMBRE, BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idAvaluo AND NOMBRE LIKE 'Avaluo_%'");
        //$arrInfoArchivo = convierte_a_arreglo($infoArchivo);
        $rutaArchivos = getcwd();
        $nombreArchivo = $infoArchivo[0]->nombre;
        $archivoComprimido = $infoArchivo[0]->binariodatos;
        $myfile = fopen($rutaArchivos."/".$nombreArchivo, "a+");
        fwrite($myfile,$archivoComprimido);
        fclose($myfile);
        $comandoNombre = "7z l ".$rutaArchivos."/".$nombreArchivo;
        $datosNombre = shell_exec($comandoNombre);
        $comandoDescomprimir = "7z e ".$rutaArchivos."/".$nombreArchivo;   
        shell_exec($comandoDescomprimir);
        $comandoRm = "rm ".$rutaArchivos."/".$nombreArchivo;
        shell_exec($comandoRm);
        if(file_exists($rutaArchivos."/default") === TRUE){    
            $myfile = fopen($rutaArchivos."/default", "r");
            $contenidoArchivo = fread($myfile, filesize($rutaArchivos."/default"));
            fclose($myfile);  

        }else{

            $comandols = "ls php*";
            $archphp = shell_exec($comandols);
            if(substr(trim($archphp),0,3) == 'php'){
                $comandoMv = "mv ".$rutaArchivos."/"."php* ".$rutaArchivos."/"."default";
                system($comandoMv);
            }

            $myfile = fopen($rutaArchivos."/default", "r");
            $contenidoArchivo = fread($myfile, filesize($rutaArchivos."/default"));
            fclose($myfile);
        }
                
        $xml = simplexml_load_string($contenidoArchivo,'SimpleXMLElement', LIBXML_NOCDATA);
        $comandoRmDefault = "rm ".$rutaArchivos."/default";
        shell_exec($comandoRmDefault);
        $arrXML = convierte_a_arreglo($xml); //echo $contenidoArchivo; exit();

        $infoFexava = DB::select("SELECT * FROM FEXAVA_AVALUO WHERE IDAVALUO = $idAvaluo");
        $arrInfoFexava = array_map("convierte_a_arreglo",$infoFexava);
        $arrFexava = $arrInfoFexava[0];

        $infoReimpresion = array();

        if(isset($arrXML['Comercial'])){
            $elementoPrincipal = $arrXML['Comercial'];
            $tipoDeAvaluo =  "Comercial";
        }

        if(isset($arrXML['Catastral'])){
            $elementoPrincipal = $arrXML['Catastral'];
            $tipoDeAvaluo =  "Catastral";
        }

        $identificacion = $elementoPrincipal['Identificacion'];

        $infoReimpresion['Encabezado'] = array();

        $infoReimpresion['Encabezado']['Fecha'] = $identificacion['FechaAvaluo'];
        $infoReimpresion['Encabezado']['Avaluo_No'] = $identificacion['NumeroDeAvaluo'];
        $infoReimpresion['Encabezado']['No_Unico'] = $arrFexava['numerounico'];
        if(isset($identificacion['ClaveSociedad']) && !is_array($identificacion['ClaveSociedad'])){
            $infoReimpresion['Encabezado']['Registro_TDF'] = $identificacion['ClaveSociedad'];
        }
        else{
            $infoReimpresion['Encabezado']['Registro_TDF'] = $identificacion['ClaveValuador'];
        }

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Sociedad_Participa'] = array();
        //$identificacion['ClaveSociedad'] = 'S-0283';

        if(isset($identificacion['ClaveSociedad']) && !is_array($identificacion['ClaveSociedad'])){
            $infoReimpresion['Sociedad_Participa']['Sociedad_Que_Practica_Avaluo'] = $identificacion['ClaveSociedad'];
        }

        $antecedentes = $elementoPrincipal['Antecedentes'];
        $solicitante = $antecedentes['Solicitante']; 
        $arrSolicitante = array_map("convierte_a_arreglo",$solicitante); //print_r($arrSolicitante); exit();    

        /*$infoSolicitante = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idAvaluo AND CODTIPOFUNCION = 'S'");
        $arrinfoSolicitante = array_map("convierte_a_arreglo",$infoSolicitante);
        $arrSolicitante = $arrinfoSolicitante[0];*/

        $infoReimpresion['Sociedad_Participa']['Valuador'] = $identificacion['ClaveValuador'];
        $infoReimpresion['Sociedad_Participa']['Fecha_del_Avaluo'] = $identificacion['FechaAvaluo'];
        $infoReimpresion['Sociedad_Participa']['Solicitante'] = array();
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Tipo_persona'] = isset($arrSolicitante['A.Paterno']) && !is_array($arrSolicitante['A.Paterno']) ? "Física" : "Moral";
        if(isset($arrSolicitante['A.Paterno']) && !is_array($arrSolicitante['A.Paterno'])){
            $infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre'] = $arrSolicitante['Nombre']." ".$arrSolicitante['A.Paterno'];
            if(isset($arrPropietario['A.Materno']) && !is_array($arrPropietario['A.Materno'])){
                $infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre'] = $infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre']." ".$arrSolicitante['A.Materno'];
            }
            //$infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre'] = $arrSolicitante['Nombre']." ".$arrSolicitante['A.Paterno']." ".isset($arrSolicitante['A.Materno']) && !is_array($arrSolicitante['A.Materno']) ? $arrSolicitante['A.Materno'] : '';
        }else{
            $infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre'] = $arrSolicitante['Nombre'];
        }
        
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Calle'] = $arrSolicitante['Calle'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['No_Exterior'] = $arrSolicitante['NumeroExterior'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['No_Interior'] = $arrSolicitante['NumeroInterior'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Colonia'] = $arrSolicitante['Colonia'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['CP'] = $arrSolicitante['CodigoPostal'];
        if(isset($arrSolicitante['Delegacion']) && !is_array($arrSolicitante['Delegacion'])){
            $infoReimpresion['Sociedad_Participa']['Solicitante']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrSolicitante['Delegacion']);
        }
        
        if(isset($arrSolicitante['Alcaldia']) && !is_array($arrSolicitante['Alcaldia'])){
            $infoReimpresion['Sociedad_Participa']['Solicitante']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrSolicitante['Alcaldia']);
        }

        $infoReimpresion['Sociedad_Participa']['inmuebleQueSeValua'] = $arrFexava['region']."-".$arrFexava['manzana']."-".$arrFexava['lote']."-".$arrFexava['unidadprivativa']." ".$arrFexava['digitoverificador'];
        $infoReimpresion['Sociedad_Participa']['regimenDePropiedad'] = $this->modelDocumentos->get_regimen_propiedad($arrFexava['codregimenpropiedad']);

        /* $infoPropietario = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idAvaluo AND CODTIPOFUNCION = 'P'");
        $arrinfoPropietario = array_map("convierte_a_arreglo",$infoPropietario);
        $arrPropietario = $arrinfoPropietario[0]; */

        $propietario = $antecedentes['Propietario']; 
        $arrPropietario = array_map("convierte_a_arreglo",$propietario); //print_r($arrPropietario); exit();

        $infoReimpresion['Sociedad_Participa']['Propietario'] = array();
        $infoReimpresion['Sociedad_Participa']['Propietario']['Tipo_persona'] = isset($arrPropietario['A.Paterno']) && !is_array($arrPropietario['A.Paterno']) ? "Física" : "Moral";
                
        if(isset($arrPropietario['A.Paterno']) && !is_array($arrPropietario['A.Paterno'])){
            $infoReimpresion['Sociedad_Participa']['Propietario']['Nombre'] = $arrPropietario['Nombre']." ".$arrPropietario['A.Paterno'];
            if(isset($arrPropietario['A.Materno']) && !is_array($arrPropietario['A.Materno'])){
                $infoReimpresion['Sociedad_Participa']['Propietario']['Nombre'] = $infoReimpresion['Sociedad_Participa']['Propietario']['Nombre']." ".$arrPropietario['A.Materno'];
            }
        }else{
            $infoReimpresion['Sociedad_Participa']['Propietario']['Nombre'] = $arrPropietario['Nombre'];
        }
        
        $infoReimpresion['Sociedad_Participa']['Propietario']['Calle'] = $arrPropietario['Calle'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['No_Exterior'] = $arrPropietario['NumeroExterior'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['No_Interior'] = $arrPropietario['NumeroInterior'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['Colonia'] = $arrPropietario['Colonia'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['CP'] = $arrPropietario['CodigoPostal'];
        if(isset($arrPropietario['Delegacion']) && !is_array($arrPropietario['Delegacion'])){
            $infoReimpresion['Sociedad_Participa']['Propietario']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrPropietario['Delegacion']);
        }
        if(isset($arrPropietario['Alcaldia']) && !is_array($arrPropietario['Alcaldia'])){
            $infoReimpresion['Sociedad_Participa']['Propietario']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrPropietario['Alcaldia']);
        }
        

        $infoReimpresion['Sociedad_Participa']['Objeto_Avaluo'] = $arrFexava['objeto'];
        $infoReimpresion['Sociedad_Participa']['Proposito_Avaluo'] = $arrFexava['proposito'];

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Ubicacion_Inmueble'] = array();
        $ubicacionInmueble = $elementoPrincipal['Antecedentes']['InmuebleQueSeValua'];

        $infoReimpresion['Ubicacion_Inmueble']['Calle'] = $ubicacionInmueble['Calle'];
        $infoReimpresion['Ubicacion_Inmueble']['No_Exterior'] = $ubicacionInmueble['NumeroExterior'];
        $infoReimpresion['Ubicacion_Inmueble']['No_Interior'] = $ubicacionInmueble['NumeroInterior'];
        $infoReimpresion['Ubicacion_Inmueble']['Colonia'] = $ubicacionInmueble['Colonia'];
        $infoReimpresion['Ubicacion_Inmueble']['CP'] = $ubicacionInmueble['CodigoPostal'];
        $infoReimpresion['Ubicacion_Inmueble']['Delegacion'] = isset($ubicacionInmueble['Delegacion']) ? $this->modelDocumentos->ObtenerNombreDelegacionPorClave($ubicacionInmueble['Delegacion']) : $this->modelDocumentos->ObtenerNombreDelegacionPorClave($ubicacionInmueble['Alcaldia']);
        $infoReimpresion['Ubicacion_Inmueble']['Edificio'] = "-";
        $infoReimpresion['Ubicacion_Inmueble']['Lote'] = isset($ubicacionInmueble['Lote']) && !is_array($ubicacionInmueble['Lote']) ? $ubicacionInmueble['Lote'] : 0;
        if(isset($ubicacionInmueble['CuentaDeAgua']) && !is_array($ubicacionInmueble['CuentaDeAgua'])){
            $infoReimpresion['Ubicacion_Inmueble']['Cuenta_agua'] = $ubicacionInmueble['CuentaDeAgua'];
        }else{
            $infoReimpresion['Ubicacion_Inmueble']['Cuenta_agua'] = '';
        }    

        $infoReimpresion['Clasificacion_de_la_zona'] = $this->modelDocumentos->get_clasificacion_zona($arrFexava['cucodclasificacionzona']);
        $infoReimpresion['Indice_Saturacion_Zona'] = $arrFexava['cuindicesaturacionzona'] <= 1 ? $arrFexava['cuindicesaturacionzona'] * 100 : $arrFexava['cuindicesaturacionzona'];
        
        $caracterisiticasUrbanas = $elementoPrincipal['CaracteristicasUrbanas'];
        $infoReimpresion['Tipo_Construccion_Dominante'] = $this->modelFis->getClase($caracterisiticasUrbanas['ClaseGeneralDeInmueblesDeLaZona']);
        $infoReimpresion['Densidad_Poblacion'] = $this->modelDocumentos->get_densidad_poblacion($arrFexava['cucoddensidadpoblacion']);
        $infoReimpresion['Nivel_Socioeconomico_Zona'] = $this->modelDocumentos->get_nivel_socioeconomico_zona($arrFexava['cucodnivelsocioeconomico']);
        $infoReimpresion['Contaminacion_Medio_Ambiente'] = $caracterisiticasUrbanas['ContaminacionAmbientalEnLaZona'];
        $infoReimpresion['Clase_General_De_Inmuebles_Zona'] = $this->modelFis->getClase($caracterisiticasUrbanas['ClaseGeneralDeInmueblesDeLaZona']);
        $infoReimpresion['Uso_Suelo'] = $arrFexava['cuuso'];
        $infoReimpresion['Area_Libre_Obligatoria'] = $arrFexava['cuarealibreobligatorio'];
        $infoReimpresion['Vias_Acceso_E_Importancia'] = $caracterisiticasUrbanas['ViasDeAccesoEImportancia'];
        
        /************************************************************************************************************************************************************************/

        $infoReimpresion['Servicios_Publicos_Equipamiento'] = array();

        $infoReimpresion['Servicios_Publicos_Equipamiento']['Red_Agua_Potable'] = $this->modelDocumentos->get_red_agua_potable($arrFexava['cucodaguapotable']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Red_Aguas_Residuales'] = $this->modelDocumentos->get_red_agua_potable($arrFexava['cucodaguapotableresidual']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Red_Drenaje_Aguas_Pluviales_Calle'] = $this->modelDocumentos->get_drenaje_pluvial_calle_zona($arrFexava['cucoddrenajepluvialcalle']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Red_Drenaje_Aguas_Pluviales_Zona'] = $this->modelDocumentos->get_drenaje_pluvial_calle_zona($arrFexava['cucoddrenajepluvialzona']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Sistema_Mixto'] = $this->modelDocumentos->get_drenaje_mixto($arrFexava['cucoddrenajeinmueble']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Suministro_Electrico'] = $this->modelDocumentos->get_suministro_electrico($arrFexava['cucodsuministroelectrico']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Acometida_Inmueble'] = $this->modelDocumentos-> get_acometida_inmueble($arrFexava['cucodacometidainmueble']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Alumbrado_Publico'] = $this->modelDocumentos->get_alumbrado_publico($arrFexava['cucodalumbradopublico']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Vialidades'] = $this->modelDocumentos->get_vialidades($arrFexava['cucodvialidades']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Banquetas'] = $this->modelDocumentos->get_banquetas($arrFexava['cucodbanquetas']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Guarniciones'] = $this->modelDocumentos->get_guarniciones($arrFexava['cucodguarniciones']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Nivel_Infraestructura_Zona'] = $arrFexava['cuporcentajeinfraestructura'] <= 1 ? $arrFexava['cuporcentajeinfraestructura'] * 100 : $arrFexava['cuporcentajeinfraestructura'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Gas_Natutral'] = $this->modelDocumentos->get_gas_natural($arrFexava['cucodgasnatural']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Telefonos_Suministro'] = $this->modelDocumentos->get_suministro_tel($arrFexava['cucodsuministrotelefonica']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Senalizacion_Vias'] = $this->modelDocumentos->get_senal_vias($arrFexava['cucodsenalizacionvias']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Acometida_Inmueble_Tel'] = $this->modelDocumentos->get_acometida_inmueble_tel($arrFexava['cucodacometidainmuebletel']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Distancia_Transporte_Urbano'] =  $arrFexava['cudistanciatransporteurbano'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Frecuencia_Transporte_Urbano'] = $arrFexava['cufrecuenciatransporteurbano'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Distancia_Transporte_Suburbano'] = $arrFexava['cudistanciatransportesuburb'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Frecuencia_Transporte_Suburbano'] = $arrFexava['cufrecuenciatransportesuburb'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Vigilancia'] = $this->modelDocumentos->get_vigilancia_zona($arrFexava['cucodvigilanciazona']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Recoleccion_Basura'] = $this->modelDocumentos->get_recoleccion_basura($arrFexava['cucodrecoleccionbasura']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Templo'] = $arrFexava['cuexisteiglesia'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Mercados'] = $arrFexava['cuexistemercados'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Plazas_Publicas'] = $arrFexava['cuexisteplazaspublicos'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Parques_Jardines'] = $arrFexava['cuexisteparquesjardines'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Escuelas'] = $arrFexava['cuexisteescuelas'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Hospitales'] = $arrFexava['cuexistehospitales'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Bancos'] = $arrFexava['cuexistebancos'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Estacion_Transporte'] = $arrFexava['cuexisteestaciontransporte'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Nivel_Equipamiento_Urbano'] = $caracterisiticasUrbanas['ServiciosPublicosYEquipamientoUrbano']['NivelDeEquipamientoUrbano'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Nomenclatura_Calles'] = $this->modelDocumentos->get_nomenclatura_calle($arrFexava['cucodnomenclaturacalle']);

        $terreno = $elementoPrincipal['Terreno'];

        $infoReimpresion['Calles_Transversales_Limitrofes'] = $terreno['CallesTransversalesLimitrofesYOrientacion'];
        
        /************************************************************************************************************************************************************************/
        
        $infoReimpresion['Croquis_Localizacion'] = array(); //echo var_dump(base64_decode($this->modelDocumentos->get_fichero_documento($terreno['CroquisMicroLocalizacion']))); exit();
        $microlocalizacion = $this->modelDocumentos->get_fichero_documento($terreno['CroquisMicroLocalizacion']);
        $macrolocalizacion = $this->modelDocumentos->get_fichero_documento($terreno['CroquisMacroLocalizacion']);
        $infoReimpresion['Croquis_Localizacion']['Microlocalizacion'] = $microlocalizacion == base64_encode(base64_decode($microlocalizacion)) ? $microlocalizacion : base64_encode($microlocalizacion);
        $infoReimpresion['Croquis_Localizacion']['Macrolocalizacion'] = $macrolocalizacion == base64_encode(base64_decode($macrolocalizacion)) ? $macrolocalizacion : base64_encode($macrolocalizacion);
        
        /************************************************************************************************************************************************************************/

        $infoReimpresion['Medidas_Colindancias'] = array();
        $fuenteDeInformacion = $terreno['MedidasYColindancias']['FuenteDeInformacionLegal'];

        $infoReimpresion['Medidas_Colindancias']['Fuente'] = isset($fuenteDeInformacion['Escritura']) ? 'Escritura' : '';
        $infoReimpresion['Medidas_Colindancias']['Numero_Escritura'] = isset($fuenteDeInformacion['Escritura']['NumeroDeEscritura']) ? $fuenteDeInformacion['Escritura']['NumeroDeEscritura'] : '';
        $infoReimpresion['Medidas_Colindancias']['Numero_Notaria'] = isset($fuenteDeInformacion['Escritura']['NumeroNotaria']) ? $fuenteDeInformacion['Escritura']['NumeroNotaria'] : '';
        $infoReimpresion['Medidas_Colindancias']['Entidad_Federativa'] = isset($fuenteDeInformacion['Escritura']['DistritoJudicialNotario']) ? $fuenteDeInformacion['Escritura']['DistritoJudicialNotario'] : '';
        $infoReimpresion['Medidas_Colindancias']['Numero_Volumen'] = isset($fuenteDeInformacion['Escritura']['NumeroDeVolumen']) ? $fuenteDeInformacion['Escritura']['NumeroDeVolumen'] : '';
        $infoReimpresion['Medidas_Colindancias']['Nombre_Notario'] = isset($fuenteDeInformacion['Escritura']['NombreDelNotario']) ? $fuenteDeInformacion['Escritura']['NombreDelNotario'] : '';
        
        /************************************************************************************************************************************************************************/

        $colindancias = $terreno['MedidasYColindancias']['Colindancias'];
        $infoReimpresion['Colindancias'] = array();

        if(isset($colindancias['@attributes'])){
            unset($colindancias['@attributes']);
            $infoReimpresion['Colindancias'][] = $colindancias;
        }

        if(isset($colindancias[0])){
            foreach($colindancias as $colindancia){
                unset($colindancia['@attributes']);
                $infoReimpresion['Colindancias'][] = $colindancia;
            }
        }
        

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Superficie_Total_Segun'] = array();
        $infoReimpresion['Superficie_Total_Segun']['Totales'] = array();
        /*$infoSuperficie = DB::select("SELECT * FROM FEXAVA_SUPERFICIE WHERE IDAVALUO = $idAvaluo");
        $arrInfoSuperficie = array_map("convierte_a_arreglo",$infoSuperficie);
        $arrSuperficie = $arrInfoSuperficie[0];
        print_r($arrSuperficie); exit();*/
        $superficieDelTerreno = $terreno['SuperficieDelTerreno']; 
        if(isset($superficieDelTerreno[0])){
            $control = 0;
            foreach($superficieDelTerreno as $supDelTerreno){ //print_r($supDelTerreno); exit();
                $infoReimpresion['Superficie_Total_Segun'][$control]['Ident_Fraccion'] = $supDelTerreno['IdentificadorFraccionN1'];
                $infoReimpresion['Superficie_Total_Segun'][$control]['Sup_Fraccion'] = $supDelTerreno['SuperficieFraccionN1'];
                if(isset($supDelTerreno['Fzo'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Fzo'] = $supDelTerreno['Fzo'];
                }
                
                if(isset($supDelTerreno['Fub'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Fub'] = $supDelTerreno['Fub'];
                }

                if(isset($supDelTerreno['FFr'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['FFr'] = $supDelTerreno['FFr'];
                }

                if(isset($supDelTerreno['Ffo'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Ffo'] = $supDelTerreno['Ffo'];
                }

                if(isset($supDelTerreno['Fsu'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Fsu'] = $supDelTerreno['Fsu'];
                }

                if($tipoDeAvaluo == 'Catastral'){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Valor_Fraccion'] = $supDelTerreno['ValorDeLaFraccionN'];
                }
                
                $infoReimpresion['Superficie_Total_Segun'][$control]['Clave_Area_Valor'] = $supDelTerreno['ClaveDeAreaDeValor'];

                if(isset($supDelTerreno['Fot'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Valor'] = $supDelTerreno['Fot']['Valor'];
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Descripcion'] = $supDelTerreno['Fot']['Descripcion'];
                }

                if(isset($supDelTerreno['Fre'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Fre'] = $supDelTerreno['Fre'];
                }

                $control = $control + 1;
            }
                        
        }else{
            $infoReimpresion['Superficie_Total_Segun']['Ident_Fraccion'] = $superficieDelTerreno['IdentificadorFraccionN1'];
            $infoReimpresion['Superficie_Total_Segun']['Sup_Fraccion'] = $superficieDelTerreno['SuperficieFraccionN1'];
            if(isset($superficieDelTerreno['Fzo'])){
                $infoReimpresion['Superficie_Total_Segun']['Fzo'] = $superficieDelTerreno['Fzo'];
            }
            
            if(isset($superficieDelTerreno['Fub'])){
                $infoReimpresion['Superficie_Total_Segun']['Fub'] = $superficieDelTerreno['Fub'];
            }

            if(isset($superficieDelTerreno['FFr'])){
                $infoReimpresion['Superficie_Total_Segun']['FFr'] = $superficieDelTerreno['FFr'];
            }

            if(isset($superficieDelTerreno['Ffo'])){
                $infoReimpresion['Superficie_Total_Segun']['Ffo'] = $superficieDelTerreno['Ffo'];
            }

            if(isset($superficieDelTerreno['Fsu'])){
                $infoReimpresion['Superficie_Total_Segun']['Fsu'] = $superficieDelTerreno['Fsu'];
            }

            if($tipoDeAvaluo == 'Catastral'){
                $infoReimpresion['Superficie_Total_Segun']['Valor_Fraccion'] = $superficieDelTerreno['ValorDeLaFraccionN'];
            }
            
            $infoReimpresion['Superficie_Total_Segun']['Clave_Area_Valor'] = $superficieDelTerreno['ClaveDeAreaDeValor'];

            if(isset($superficieDelTerreno['Fot'])){
                $infoReimpresion['Superficie_Total_Segun']['Valor'] = $superficieDelTerreno['Fot']['Valor'];
                $infoReimpresion['Superficie_Total_Segun']['Descripcion'] = $superficieDelTerreno['Fot']['Descripcion'];
            }

            if(isset($superficieDelTerreno['Fre'])){
                $infoReimpresion['Superficie_Total_Segun']['Fre'] = $superficieDelTerreno['Fre'];
            }
        }    

        $infoReimpresion['Superficie_Total_Segun']['Totales']['Superficie_Total_Terreno'] =  $terreno['SuperficieTotalDelTerreno']; //print_r($infoReimpresion); exit();

        $infoReimpresion['Topografia_Configuracion'] = array();
        $infoReimpresion['Topografia_Configuracion']['Caracteristicas_Panoramicas'] = $arrFexava['tecaracteristicasparonamicas'];
        $infoReimpresion['Topografia_Configuracion']['Densidad_Habitacional'] = $this->modelDocumentos->get_densidad_habitacional($arrFexava['tecoddensidadhabitacional']);
        $infoReimpresion['Topografia_Configuracion']['Servidumbre_Restricciones'] = $arrFexava['teservidumbresorestricciones'];

        $infoReimpresion['Uso_Actual'] = $arrFexava['diusoactual'];

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Construcciones_Privativas'] = array();            
        
        $descripcionInmueble = $elementoPrincipal['DescripcionDelInmueble'];
        $tiposContruccion = $descripcionInmueble['TiposDeConstruccion'];

        if(isset($tiposContruccion['ConstruccionesPrivativas']['@attributes'])){
            $infoReimpresion['Construcciones_Privativas']['Tipo'] = 'P';
            $infoReimpresion['Construcciones_Privativas']['Descripcion'] = $tiposContruccion['ConstruccionesPrivativas']['Descripcion'];
            $infoReimpresion['Construcciones_Privativas']['Uso'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveUso'];
            $infoReimpresion['Construcciones_Privativas']['No_Niveles_Tipo'] = $tiposContruccion['ConstruccionesPrivativas']['NumeroDeNivelesDelTipo'];
            if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'])){
                $infoReimpresion['Construcciones_Privativas']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'];
            }
            if(isset($tiposContruccion['ConstruccionesPrivativas']['PuntajeDeClasificacion']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['PuntajeDeClasificacion'])){
                $infoReimpresion['Construcciones_Privativas']['Puntaje'] = $tiposContruccion['ConstruccionesPrivativas']['PuntajeDeClasificacion'];
            }
            if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveClase'])){
                $infoReimpresion['Construcciones_Privativas']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']);
            }
            if(isset($tiposContruccion['ConstruccionesPrivativas']['Edad']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['Edad'])){
                $infoReimpresion['Construcciones_Privativas']['Edad'] = $tiposContruccion['ConstruccionesPrivativas']['Edad'];
            }

            if(isset($tiposContruccion['ConstruccionesPrivativas']['VidaUtilTotalDelTipo']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['VidaUtilTotalDelTipo'])){
                $infoReimpresion['Construcciones_Privativas']['Vida_Util_Total_Tipo'] = $tiposContruccion['ConstruccionesPrivativas']['VidaUtilTotalDelTipo'];
            }
            if(isset($tiposContruccion['ConstruccionesPrivativas']['VidaUtilRemanente']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['VidaUtilRemanente'])){
                $infoReimpresion['Construcciones_Privativas']['Vida_Util_Remanente'] = $tiposContruccion['ConstruccionesPrivativas']['VidaUtilRemanente'];
            }
            if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveConservacion']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveConservacion'])){
                $infoReimpresion['Construcciones_Privativas']['Conservacion'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveConservacion'];
            }
            if(isset($tiposContruccion['ConstruccionesPrivativas']['Superficie']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['Superficie'])){
                $infoReimpresion['Construcciones_Privativas']['Sup'] = $tiposContruccion['ConstruccionesPrivativas']['Superficie'];
            }
        }

        if(isset($tiposContruccion['ConstruccionesPrivativas'][0])){
            $control = 0;
            foreach($tiposContruccion['ConstruccionesPrivativas'] as $construccionPrivativa){
                $infoReimpresion['Construcciones_Privativas'][$control]['Tipo'] = 'P';
                $infoReimpresion['Construcciones_Privativas'][$control]['Descripcion'] = $construccionPrivativa['Descripcion'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Uso'] = $construccionPrivativa['ClaveUso'];
                $infoReimpresion['Construcciones_Privativas'][$control]['No_Niveles_Tipo'] = $construccionPrivativa['NumeroDeNivelesDelTipo'];
                if(isset($construccionPrivativa['ClaveRangoDeNiveles']) && !is_array($construccionPrivativa['ClaveRangoDeNiveles'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Clave_Rango_Niveles'] = $construccionPrivativa['ClaveRangoDeNiveles'];
                }
                if(isset($construccionPrivativa['PuntajeDeClasificacion']) && !is_array($construccionPrivativa['PuntajeDeClasificacion'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Puntaje'] = $construccionPrivativa['PuntajeDeClasificacion'];
                }
                if(isset($construccionPrivativa['ClaveClase']) && !is_array($construccionPrivativa['ClaveClase'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Clase'] = $this->modelFis->getClase($construccionPrivativa['ClaveClase']);
                }
                if(isset($construccionPrivativa['Edad']) && !is_array($construccionPrivativa['Edad'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Edad'] = $construccionPrivativa['Edad'];
                }
                if(isset($construccionPrivativa['VidaUtilTotalDelTipo']) && !is_array($construccionPrivativa['VidaUtilTotalDelTipo'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Vida_Util_Total_Tipo'] = $construccionPrivativa['VidaUtilTotalDelTipo'];
                }
                if(isset($construccionPrivativa['VidaUtilRemanente']) && !is_array($construccionPrivativa['VidaUtilRemanente'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Vida_Util_Remanente'] = $construccionPrivativa['VidaUtilRemanente'];
                }
                if(isset($construccionPrivativa['ClaveConservacion']) && !is_array($construccionPrivativa['ClaveConservacion'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Conservacion'] = $construccionPrivativa['ClaveConservacion'];
                }
                if(isset($construccionPrivativa['Superficie']) && !is_array($construccionPrivativa['Superficie'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Sup'] = $construccionPrivativa['Superficie'];
                }
                $control = $control + 1;
            }
        }
        
        if(isset($tiposContruccion['ConstruccionesComunes']['@attributes'])){
            $infoReimpresion['Construcciones_Comunes']['Tipo'] = 'C';
            $infoReimpresion['Construcciones_Comunes']['Descripcion'] = $tiposContruccion['ConstruccionesComunes']['Descripcion'];
            $infoReimpresion['Construcciones_Comunes']['Uso'] = $tiposContruccion['ConstruccionesComunes']['ClaveUso'];
            $infoReimpresion['Construcciones_Comunes']['No_Niveles_Tipo'] = $tiposContruccion['ConstruccionesComunes']['NumeroDeNivelesDelTipo'];
            if(isset($tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'])){
                $infoReimpresion['Construcciones_Comunes']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'];
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['PuntajeDeClasificacion']) && !is_array($tiposContruccion['ConstruccionesComunes']['PuntajeDeClasificacion'])){
                $infoReimpresion['Construcciones_Comunes']['Puntaje'] = $tiposContruccion['ConstruccionesComunes']['PuntajeDeClasificacion'];
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['ClaveClase']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveClase'])){
                $infoReimpresion['Construcciones_Comunes']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesComunes']['ClaveClase']);
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['Edad']) && !is_array($tiposContruccion['ConstruccionesComunes']['Edad'])){
                $infoReimpresion['Construcciones_Comunes']['Edad'] = $tiposContruccion['ConstruccionesComunes']['Edad'];
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['VidaUtilTotalDelTipo']) && !is_array($tiposContruccion['ConstruccionesComunes']['VidaUtilTotalDelTipo'])){
                $infoReimpresion['Construcciones_Comunes']['Vida_Util_Total_Tipo'] = $tiposContruccion['ConstruccionesComunes']['VidaUtilTotalDelTipo'];
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['VidaUtilRemanente']) && !is_array($tiposContruccion['ConstruccionesComunes']['VidaUtilRemanente'])){
                $infoReimpresion['Construcciones_Comunes']['Vida_Util_Remanente'] = $tiposContruccion['ConstruccionesComunes']['VidaUtilRemanente'];
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['ClaveConservacion']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveConservacion'])){
                $infoReimpresion['Construcciones_Comunes']['Conservacion'] = $tiposContruccion['ConstruccionesComunes']['ClaveConservacion'];
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['Superficie']) && !is_array($tiposContruccion['ConstruccionesComunes']['Superficie'])){
                $infoReimpresion['Construcciones_Comunes']['Sup'] = $tiposContruccion['ConstruccionesComunes']['Superficie'];
            }
        }

        if(isset($tiposContruccion['ConstruccionesComunes'][0])){
            $control = 0;
            foreach($tiposContruccion['ConstruccionesComunes'] as $construccionComun){
                $infoReimpresion['Construcciones_Comunes'][$control]['Tipo'] = 'C';
                $infoReimpresion['Construcciones_Comunes'][$control]['Descripcion'] = $construccionComun['Descripcion'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Uso'] = $construccionComun['ClaveUso'];
                $infoReimpresion['Construcciones_Comunes'][$control]['No_Niveles_Tipo'] = $construccionComun['NumeroDeNivelesDelTipo'];
                if(isset($construccionComun['ClaveRangoDeNiveles']) && !is_array($construccionComun['ClaveRangoDeNiveles'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Clave_Rango_Niveles'] = $construccionComun['ClaveRangoDeNiveles'];
                }
                if(isset($construccionComun['PuntajeDeClasificacion']) && !is_array($construccionComun['PuntajeDeClasificacion'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Puntaje'] = $construccionComun['PuntajeDeClasificacion'];
                }
                if(isset($construccionComun['ClaveClase']) && !is_array($construccionComun['ClaveClase'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Clase'] = $this->modelFis->getClase($construccionComun['ClaveClase']);
                }
                if(isset($construccionComun['Edad']) && !is_array($construccionComun['Edad'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Edad'] = $construccionComun['Edad'];
                }
                if(isset($construccionComun['VidaUtilTotalDelTipo']) && !is_array($construccionComun['VidaUtilTotalDelTipo'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Vida_Util_Total_Tipo'] = $construccionComun['VidaUtilTotalDelTipo'];
                }
                if(isset($construccionComun['VidaUtilRemanente']) && !is_array($construccionComun['VidaUtilRemanente'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Vida_Util_Remanente'] = $construccionComun['VidaUtilRemanente'];
                }
                if(isset($construccionComun['ClaveConservacion']) && !is_array($construccionComun['ClaveConservacion'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Conservacion'] = $construccionComun['ClaveConservacion'];
                }
                if(isset($construccionComun['Superficie']) && !is_array($construccionComun['Superficie'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Sup'] = $construccionComun['Superficie'];
                }
                $control = $control + 1;
            }
        }
        
        if(isset($terreno['Indiviso']) && !is_array($terreno['Indiviso'])){
            $infoReimpresion['Indiviso'] = $terreno['Indiviso'] <= 1 ? $terreno['Indiviso'] * 100 : $terreno['Indiviso'];
        }        
        if(isset($descripcionInmueble['VidaUtilTotalPonderadaDelInmueble']) && !is_array($descripcionInmueble['VidaUtilTotalPonderadaDelInmueble'])){
            $infoReimpresion['Vida_Util_Promedio_Inmueble'] = $descripcionInmueble['VidaUtilTotalPonderadaDelInmueble'];
        }
        if(isset($descripcionInmueble['EdadPonderadaDelInmueble']) && !is_array($descripcionInmueble['EdadPonderadaDelInmueble'])){
            $infoReimpresion['Edad_Aproximada_Construccion'] = $descripcionInmueble['EdadPonderadaDelInmueble'];
        }
        if(isset($descripcionInmueble['VidaUtilRemanentePonderadaDelInmueble']) && !is_array($descripcionInmueble['VidaUtilRemanentePonderadaDelInmueble'])){
            $infoReimpresion['Vida_Util_Remanente'] = $descripcionInmueble['VidaUtilRemanentePonderadaDelInmueble'];
        }

        /************************************************************************************************************************************************************************/

        $elementosConstruccion = $elementoPrincipal['ElementosDeLaConstruccion'];
        if(isset($elementosConstruccion['ObraNegra'])){
            $obraNegra = $elementosConstruccion['ObraNegra']; //print_r($elementosConstruccion); exit();
        }    

        $infoReimpresion['Obra_Negra_Gruesa'] = array();

        if(isset($obraNegra['Cimentacion']) && !is_array($obraNegra['Cimentacion'])){
            $infoReimpresion['Obra_Negra_Gruesa']['Cimientos'] = $obraNegra['Cimentacion'];
        }/*else{
            $infoReimpresion['Obra_Negra_Gruesa']['Cimientos'] = '';
        }*/
        
        if(isset($obraNegra['Estructura']) && !is_array($obraNegra['Estructura'])){
            $infoReimpresion['Obra_Negra_Gruesa']['Estructura'] = $obraNegra['Estructura'];
        }

        if(isset($obraNegra['Muros']) && !is_array($obraNegra['Muros'])){
            $infoReimpresion['Obra_Negra_Gruesa']['Muros'] = $obraNegra['Muros'];
        }

        if(isset($obraNegra['Entrepisos']) && !is_array($obraNegra['Entrepisos'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Entrepiso'] = $obraNegra['Entrepisos'];
        }

        if(isset($obraNegra['Techos']) && !is_array($obraNegra['Techos'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Techos'] = $obraNegra['Techos'];
        }

        if(isset($obraNegra['Azoteas']) && !is_array($obraNegra['Azoteas'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Azoteas'] = $obraNegra['Azoteas'];
        }

        if(isset($obraNegra['Bardas']) && !is_array($obraNegra['Bardas'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Bardas'] = $obraNegra['Bardas'];
        }

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Revestimientos_Acabados_Interiores'] = array();
        if(isset($elementosConstruccion['RevestimientosYAcabadosInteriores'])){
            $revestimientosAcabados = $elementosConstruccion['RevestimientosYAcabadosInteriores'];
        }        

        if(isset($revestimientosAcabados['Aplanados']) && !is_array($revestimientosAcabados['Aplanados'])){
            $infoReimpresion['Revestimientos_Acabados_Interiores']['Aplanados'] = $revestimientosAcabados['Aplanados'];
        }
        
        if(isset($revestimientosAcabados['Plafones']) && !is_array($revestimientosAcabados['Plafones'])){
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Plafones'] = $revestimientosAcabados['Plafones'];
        }

        if(isset($revestimientosAcabados['Lambrines']) && !is_array($revestimientosAcabados['Lambrines'])){
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Lambrines'] = $revestimientosAcabados['Lambrines'];
        }

        if(isset($revestimientosAcabados['Pisos']) && !is_array($revestimientosAcabados['Pisos'])){
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Pisos'] = $revestimientosAcabados['Pisos'];
        }

        if(isset($revestimientosAcabados['Zoclos']) && !is_array($revestimientosAcabados['Zoclos'])){
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Zoclos'] = $revestimientosAcabados['Zoclos'];
        }

        if(isset($revestimientosAcabados['Escaleras']) && !is_array($revestimientosAcabados['Escaleras'])){
            $infoReimpresion['Revestimientos_Acabados_Interiores']['Escaleras'] = $revestimientosAcabados['Escaleras'];
        }

        if(isset($revestimientosAcabados['Pintura']) && !is_array($revestimientosAcabados['Pintura'])){
            $infoReimpresion['Revestimientos_Acabados_Interiores']['Pintura'] = $revestimientosAcabados['Pintura'];
        }

        if(isset($revestimientosAcabados['RecubrimientosEspeciales']) && !is_array($revestimientosAcabados['RecubrimientosEspeciales'])){
            $infoReimpresion['Revestimientos_Acabados_Interiores']['Recubrimientos_Especiales'] = $revestimientosAcabados['RecubrimientosEspeciales'];
        }
        /************************************************************************************************************************************************************************/

        $infoReimpresion['Carpinteria'] = array();
        if(isset($elementosConstruccion['Carpinteria'])){
            $carpinteria = $elementosConstruccion['Carpinteria'];
        }    

        if(isset($carpinteria['PuertasInteriores']) && !is_array($carpinteria['PuertasInteriores'])){
            $infoReimpresion['Carpinteria']['Puertas_Interiores'] = $carpinteria['PuertasInteriores'];
        }
        
        if(isset($carpinteria['Guardaropas']) && !is_array($carpinteria['Guardaropas'])){
            $infoReimpresion['Carpinteria']['Guardarropas'] = $carpinteria['Guardaropas'];
        }

        if(isset($carpinteria['MueblesEmpotradosOFijos']) && !is_array($carpinteria['MueblesEmpotradosOFijos'])){
            $infoReimpresion['Carpinteria']['Muebles_Empotrados'] = $carpinteria['MueblesEmpotradosOFijos'];
        }

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias'] = array();
        if(isset($elementosConstruccion['InstalacionesHidraulicasYSanitrias'])){
            $hidraulicasSanitarias = $elementosConstruccion['InstalacionesHidraulicasYSanitrias'];
        }    

        if(isset($hidraulicasSanitarias['MueblesDeBanno']) && !is_array($hidraulicasSanitarias['MueblesDeBanno'])){
            $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias']['Muebles_Banio'] = $hidraulicasSanitarias['MueblesDeBanno'];
        }
        
        if(isset($hidraulicasSanitarias['RamaleosHidraulicos']) && !is_array($hidraulicasSanitarias['RamaleosHidraulicos'])){
            $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Hidraulicos'] = $hidraulicasSanitarias['RamaleosHidraulicos'];
        }

        if(isset($hidraulicasSanitarias['RamaleosSanitarios']) && !is_array($hidraulicasSanitarias['RamaleosSanitarios'])){
            $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Sanitarios'] = $hidraulicasSanitarias['RamaleosSanitarios'];
        }

        if(isset($elementosConstruccion['InstalacionesElectricasYAlumbrado'])){
            $infoReimpresion['Instalaciones_Electricas_Alumbrados'] = $elementosConstruccion['InstalacionesElectricasYAlumbrado'];
        }    

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Puertas_Ventaneria_Metalica'] = array();
        if(isset($elementosConstruccion['PuertasYVentaneriaMetalica'])){
            $puertasVentaneria = $elementosConstruccion['PuertasYVentaneriaMetalica'];
        }    

        if(isset($puertasVentaneria['Herreria']) && !is_array($puertasVentaneria['Herreria'])){
            $infoReimpresion['Puertas_Ventaneria_Metalica']['Herreria'] = $puertasVentaneria['Herreria'];
        }
        
        if(isset($puertasVentaneria['Ventaneria']) && !is_array($puertasVentaneria['Ventaneria'])){
            $infoReimpresion['Puertas_Ventaneria_Metalica']['Ventaneria'] = $puertasVentaneria['Ventaneria'];
        }        

        if(isset($elementosConstruccion['Vidreria']) && !is_array($elementosConstruccion['Vidreria'])){
            $infoReimpresion['Vidrieria'] = $elementosConstruccion['Vidreria'];
        }
        
        if(isset($elementosConstruccion['Cerrajeria']) && !is_array($elementosConstruccion['Cerrajeria'])){
            $infoReimpresion['Cerrajeria'] = $elementosConstruccion['Cerrajeria'];
        }

        if(isset($elementosConstruccion['Fachadas']) && !is_array($elementosConstruccion['Fachadas'])){
            $infoReimpresion['Fachadas'] = $elementosConstruccion['Fachadas'];
        }

        /************************************************************************************************************************************************************************/
        if(isset($elementosConstruccion['InstalacionesEspeciales'])){
            $infoReimpresion['Instalaciones_Especiales'] = array();
            $instalacionesEspeciales = $elementosConstruccion['InstalacionesEspeciales'];

            if(isset($instalacionesEspeciales['Privativas']['@attributes'])){
                $infoReimpresion['Instalaciones_Especiales']['Privativas'] = array();
                if(isset($instalacionesEspeciales['Privativas']['ClaveInstalacionEspecial']) && !is_array($instalacionesEspeciales['Privativas']['ClaveInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales']['Privativas']['Clave'] = $instalacionesEspeciales['Privativas']['ClaveInstalacionEspecial'];
                }
                if(isset($instalacionesEspeciales['Privativas']['DescripcionInstalacionEspecial']) && !is_array($instalacionesEspeciales['Privativas']['DescripcionInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales']['Privativas']['Descripcion'] = $instalacionesEspeciales['Privativas']['DescripcionInstalacionEspecial'];
                }
                if(isset($instalacionesEspeciales['Privativas']['UnidadInstalacionEspecial']) && !is_array($instalacionesEspeciales['Privativas']['UnidadInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales']['Privativas']['Unidad'] = $instalacionesEspeciales['Privativas']['UnidadInstalacionEspecial'];
                }
                if(isset($instalacionesEspeciales['Privativas']['CantidadInstalacionEspecial']) && !is_array($instalacionesEspeciales['Privativas']['CantidadInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales']['Privativas']['Cantidad'] = $instalacionesEspeciales['Privativas']['CantidadInstalacionEspecial'];
                }
            }

            if(isset($instalacionesEspeciales['Privativas'][0])){
                $infoReimpresion['Instalaciones_Especiales']['Privativas'] = array();
                $control = 0;
                foreach($instalacionesEspeciales['Privativas'] as $instalacionEspecial){
                    if(isset($instalacionEspecial['ClaveInstalacionEspecial']) && !is_array($instalacionEspecial['ClaveInstalacionEspecial'])){
                        $infoReimpresion['Instalaciones_Especiales']['Privativas'][$control]['Clave'] = $instalacionEspecial['ClaveInstalacionEspecial'];
                    }
                    if(isset($instalacionEspecial['DescripcionInstalacionEspecial']) && !is_array($instalacionEspecial['DescripcionInstalacionEspecial'])){
                        $infoReimpresion['Instalaciones_Especiales']['Privativas'][$control]['Descripcion'] = $instalacionEspecial['DescripcionInstalacionEspecial'];
                    }
                    if(isset($instalacionEspecial['UnidadInstalacionEspecial']) && !is_array($instalacionEspecial['UnidadInstalacionEspecial'])){
                        $infoReimpresion['Instalaciones_Especiales']['Privativas'][$control]['Unidad'] = $instalacionEspecial['UnidadInstalacionEspecial'];
                    }
                    if(isset($instalacionEspecial['CantidadInstalacionEspecial']) && !is_array($instalacionEspecial['CantidadInstalacionEspecial'])){
                        $infoReimpresion['Instalaciones_Especiales']['Privativas'][$control]['Cantidad'] = $instalacionEspecial['CantidadInstalacionEspecial'];
                    }
                    $control = $control + 1;
                }
            }

            if(isset($instalacionesEspeciales['Comunes']['@attributes'])){
                $infoReimpresion['Instalaciones_Especiales']['Comunes'] = array();
                if(isset($instalacionesEspeciales['Comunes']['ClaveInstalacionEspecial']) && !is_array($instalacionesEspeciales['Comunes']['ClaveInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales']['Comunes']['Clave'] = $instalacionesEspeciales['Comunes']['ClaveInstalacionEspecial'];
                }
                if(isset($instalacionesEspeciales['Comunes']['DescripcionInstalacionEspecial']) && !is_array($instalacionesEspeciales['Comunes']['DescripcionInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales']['Comunes']['Descripcion'] = $instalacionesEspeciales['Comunes']['DescripcionInstalacionEspecial'];
                }
                if(isset($instalacionesEspeciales['Comunes']['UnidadInstalacionEspecial']) && !is_array($instalacionesEspeciales['Comunes']['UnidadInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales']['Comunes']['Unidad'] = $instalacionesEspeciales['Comunes']['UnidadInstalacionEspecial'];
                }
                if(isset($instalacionesEspeciales['Comunes']['CantidadInstalacionEspecial']) && !is_array($instalacionesEspeciales['Comunes']['CantidadInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales']['Comunes']['Cantidad'] = $instalacionesEspeciales['Comunes']['CantidadInstalacionEspecial'];
                }
            }

            if(isset($instalacionesEspeciales['Comunes'][0])){
                $infoReimpresion['Instalaciones_Especiales']['Comunes'] = array();
                $control = 0;
                foreach($instalacionesEspeciales['Comunes'] as $instalacionEspecial){
                    if(isset($instalacionEspecial['ClaveInstalacionEspecial']) && !is_array($instalacionEspecial['ClaveInstalacionEspecial'])){
                        $infoReimpresion['Instalaciones_Especiales']['Comunes'][$control]['Clave'] = $instalacionEspecial['ClaveInstalacionEspecial'];
                    }
                    if(isset($instalacionEspecial['DescripcionInstalacionEspecial']) && !is_array($instalacionEspecial['DescripcionInstalacionEspecial'])){
                        $infoReimpresion['Instalaciones_Especiales']['Comunes'][$control]['Descripcion'] = $instalacionEspecial['DescripcionInstalacionEspecial'];
                    }
                    if(isset($instalacionEspecial['UnidadInstalacionEspecial']) && !is_array($instalacionEspecial['UnidadInstalacionEspecial'])){
                        $infoReimpresion['Instalaciones_Especiales']['Comunes'][$control]['Unidad'] = $instalacionEspecial['UnidadInstalacionEspecial'];
                    }
                    if(isset($instalacionEspecial['CantidadInstalacionEspecial']) && !is_array($instalacionEspecial['CantidadInstalacionEspecial'])){
                        $infoReimpresion['Instalaciones_Especiales']['Comunes'][$control]['Cantidad'] = $instalacionEspecial['CantidadInstalacionEspecial'];
                    }
                    $control = $control + 1;
                }
            }

        }

        /************************************************************************************************************************************************************************/

        if(isset($elementosConstruccion['ElementosAccesorios'])){
            $infoReimpresion['Elementos_Accesorios']  = array();
            $elementosAccesorios = $elementosConstruccion['ElementosAccesorios'];

            if(isset($elementosAccesorios['Privativas']['@attributes'])){
                $infoReimpresion['Elementos_Accesorios']['Privativas'] = array();
                if(isset($elementosAccesorios['Privativas']['ClaveElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['ClaveElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Clave'] = $elementosAccesorios['Privativas']['ClaveElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Privativas']['DescripcionElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['DescripcionElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Descripcion'] = $elementosAccesorios['Privativas']['DescripcionElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Privativas']['UnidadElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['UnidadElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Unidad'] = $elementosAccesorios['Privativas']['UnidadElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Privativas']['CantidadElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['CantidadElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Cantidad'] = $elementosAccesorios['Privativas']['CantidadElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Privativas']['EdadElementoAccesorio']) && !is_array(isset($elementosAccesorios['Privativas']['EdadElementoAccesorio']))){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Edad'] = $elementosAccesorios['Privativas']['EdadElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Privativas']['VidaUtilTotalElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['VidaUtilTotalElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Vida_Util_Total'] = $elementosAccesorios['Privativas']['VidaUtilTotalElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Valor_Unitario'] = $elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio'];
                }
                
            }

            if(isset($elementosAccesorios['Privativas'][0])){
                $infoReimpresion['Elementos_Accesorios']['Privativas'] = array();
                $control = 0;
                foreach($elementosAccesorios['Privativas'] as $elementoAccesorio){
                    if(isset($elementoAccesorio['ClaveElementoAccesorio']) && !is_array($elementoAccesorio['ClaveElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Clave'] = $elementoAccesorio['ClaveElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['DescripcionElementoAccesorio']) && !is_array($elementoAccesorio['DescripcionElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Descripcion'] = $elementoAccesorio['DescripcionElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['UnidadElementoAccesorio']) && !is_array($elementoAccesorio['UnidadElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Unidad'] = $elementoAccesorio['UnidadElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['CantidadElementoAccesorio']) && !is_array($elementoAccesorio['CantidadElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $elementoAccesorio['CantidadElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['EdadElementoAccesorio']) && !is_array(isset($elementoAccesorio['EdadElementoAccesorio']))){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Edad'] = $elementoAccesorio['EdadElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['VidaUtilTotalElementoAccesorio']) && !is_array(isset($elementoAccesorio['VidaUtilTotalElementoAccesorio']))){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Vida_Util_Total'] = $elementoAccesorio['VidaUtilTotalElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['ValorUnitarioElementoAccesorio']) && !is_array($elementoAccesorio['ValorUnitarioElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $elementoAccesorio['ValorUnitarioElementoAccesorio'];
                    }
                    
                    $control = $control + 1;
                }
            }

            if(isset($elementosAccesorios['Comunes']['@attributes'])){
                $infoReimpresion['Elementos_Accesorios']['Comunes'] = array();
                if(isset($elementosAccesorios['Comunes']['ClaveElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['ClaveElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Clave'] = $elementosAccesorios['Comunes']['ClaveElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Comunes']['DescripcionElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['DescripcionElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Descripcion'] = $elementosAccesorios['Comunes']['DescripcionElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Comunes']['UnidadElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['UnidadElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Unidad'] = $elementosAccesorios['Comunes']['UnidadElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Comunes']['CantidadElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['CantidadElementoAccesorio'])){
                $infoReimpresion['Elementos_Accesorios']['Comunes']['Cantidad'] = $elementosAccesorios['Comunes']['CantidadElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Comunes']['EdadElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Edad'] = $elementosAccesorios['Comunes']['EdadElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Comunes']['VidaUtilTotalElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Vida_Util_Total'] = $elementosAccesorios['Comunes']['VidaUtilTotalElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Comunes']['ValorUnitarioElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Valor_Unitario'] = $elementosAccesorios['Comunes']['ValorUnitarioElementoAccesorio'];
                }
                
            }

            if(isset($elementosAccesorios['Comunes'][0])){
                $infoReimpresion['Elementos_Accesorios']['Comunes'] = array();
                $control = 0;
                foreach($elementosAccesorios['Comunes'] as $elementoAccesorio){
                    if(isset($elementoAccesorio['ClaveElementoAccesorio'])  && isset($elementoAccesorio['ClaveElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Clave'] = $elementoAccesorio['ClaveElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['DescripcionElementoAccesorio'])  && isset($elementoAccesorio['DescripcionElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Descripcion'] = $elementoAccesorio['DescripcionElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['UnidadElementoAccesorio'])  && isset($elementoAccesorio['UnidadElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Unidad'] = $elementoAccesorio['UnidadElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['CantidadElementoAccesorio'])  && isset($elementoAccesorio['CantidadElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $elementoAccesorio['CantidadElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['EdadElementoAccesorio'])  && isset($elementoAccesorio['EdadElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Edad'] = $elementoAccesorio['EdadElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['VidaUtilTotalElementoAccesorio'])  && isset($elementoAccesorio['VidaUtilTotalElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Vida_Util_Total'] = $elementoAccesorio['VidaUtilTotalElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['ValorUnitarioElementoAccesorio']) && isset($elementoAccesorio['ValorUnitarioElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Valor_Unitario'] = $elementoAccesorio['ValorUnitarioElementoAccesorio'];
                    }
                    
                    $control = $control + 1;
                }
            }
        }
        

        /******************************************************************************************************************************************************/
        
        if(isset($elementosConstruccion['ObrasComplementarias'])){
            $infoReimpresion['Obras_Complementarias']  = array();
            $obrasComplementarias = $elementosConstruccion['ObrasComplementarias'];

            if(isset($obrasComplementarias['Privativas']['@attributes'])){
                $infoReimpresion['Obras_Complementarias']['Privativas'] = array();
                if(isset($obrasComplementarias['Privativas']['ClaveObraComplementaria']) && is_array($obrasComplementarias['Privativas']['ClaveObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Clave'] = $obrasComplementarias['Privativas']['ClaveObraComplementaria'];
                }
                if(isset($obrasComplementarias['Privativas']['DescripcionObraComplementaria']) && is_array($obrasComplementarias['Privativas']['DescripcionObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Descripcion'] = $obrasComplementarias['Privativas']['DescripcionObraComplementaria'];
                }
                if(isset($obrasComplementarias['Privativas']['UnidadObraComplementaria']) && is_array($obrasComplementarias['Privativas']['UnidadObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Unidad'] = $obrasComplementarias['Privativas']['UnidadObraComplementaria'];
                }
                if(isset($obrasComplementarias['Privativas']['CantidadObraComplementaria']) && is_array($obrasComplementarias['Privativas']['CantidadObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Cantidad'] = $obrasComplementarias['Privativas']['CantidadObraComplementaria'];
                }
                if(isset($obrasComplementarias['Privativas']['EdadObraComplementaria']) && is_array($obrasComplementarias['Privativas']['EdadObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Edad'] = $obrasComplementarias['Privativas']['EdadObraComplementaria'];
                }
                if(isset($obrasComplementarias['Privativas']['VidaUtilTotalObraComplementaria']) && is_array($obrasComplementarias['Privativas']['VidaUtilTotalObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Vida_Util_Total'] = $obrasComplementarias['Privativas']['VidaUtilTotalObraComplementaria'];
                }
                if(isset($obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria']) && is_array($obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Valor_Unitario'] = $obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria'];
                }
            }

            if(isset($obrasComplementarias['Privativas'][0])){
                $infoReimpresion['Obras_Complementarias']['Privativas'] = array();
                $control = 0;
                foreach($obrasComplementarias['Privativas'] as $obraComplementaria){
                    if(isset($obraComplementaria['ClaveObraComplementaria']) && !is_array($obraComplementaria['ClaveObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Clave'] = $obraComplementaria['ClaveObraComplementaria'];
                    }
                    if(isset($obraComplementaria['DescripcionObraComplementaria']) && !is_array($obraComplementaria['DescripcionObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Descripcion'] = $obraComplementaria['DescripcionObraComplementaria'];
                    }
                    if(isset($obraComplementaria['UnidadObraComplementaria']) && !is_array($obraComplementaria['UnidadObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Unidad'] = $obraComplementaria['UnidadObraComplementaria'];
                    }
                    if(isset($obraComplementaria['CantidadObraComplementaria']) && !is_array($obraComplementaria['CantidadObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Cantidad'] = $obraComplementaria['CantidadObraComplementaria'];
                    }
                    if(isset($obraComplementaria['EdadObraComplementaria']) && !is_array($obraComplementaria['EdadObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Edad'] = $obraComplementaria['EdadObraComplementaria'];
                    }
                    if(isset($obraComplementaria['VidaUtilTotalObraComplementaria'])  && !is_array($obraComplementaria['VidaUtilTotalObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Vida_Util_Total'] = $obraComplementaria['VidaUtilTotalObraComplementaria'];
                    }
                    if(isset($obraComplementaria['ValorUnitarioObraComplementaria']) && !is_array($obraComplementaria['ValorUnitarioObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Valor_Unitario'] = $obraComplementaria['ValorUnitarioObraComplementaria'];
                    }
                    $control = $control + 1;
                }
            }

            if(isset($obrasComplementarias['Comunes']['@attributes'])){
                $infoReimpresion['Obras_Complementarias']['Comunes'] = array();
                if(isset($obrasComplementarias['Comunes']['ClaveObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['ClaveObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Clave'] = $obrasComplementarias['Comunes']['ClaveObraComplementaria'];
                }

                if(isset($obrasComplementarias['Comunes']['DescripcionObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['DescripcionObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Descripcion'] = $obrasComplementarias['Comunes']['DescripcionObraComplementaria'];
                }
                if(isset($obrasComplementarias['Comunes']['UnidadObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['UnidadObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Unidad'] = $obrasComplementarias['Comunes']['UnidadObraComplementaria'];
                }
                if(isset($obrasComplementarias['Comunes']['CantidadObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['CantidadObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Cantidad'] = $obrasComplementarias['Comunes']['CantidadObraComplementaria'];
                }
                if(isset($obrasComplementarias['Comunes']['EdadObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['EdadObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Edad'] = $obrasComplementarias['Comunes']['EdadObraComplementaria'];
                }
                if(isset($obrasComplementarias['Comunes']['VidaUtilTotalObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['VidaUtilTotalObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Vida_Util_Total'] = $obrasComplementarias['Comunes']['VidaUtilTotalObraComplementaria'];
                }
                if(isset($obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Valor_Unitario'] = $obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria'];
                }
                
            }

            if(isset($obrasComplementarias['Comunes'][0])){
                $infoReimpresion['Obras_Complementarias']['Comunes'] = array();
                $control = 0;
                foreach($obrasComplementarias['Comunes'] as $obraComplementaria){
                    if(isset($obraComplementaria['ClaveObraComplementaria']) && !is_array($obraComplementaria['ClaveObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Clave'] = $obraComplementaria['ClaveObraComplementaria'];
                    }
                    if(isset($obraComplementaria['DescripcionObraComplementaria']) && !is_array($obraComplementaria['DescripcionObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Descripcion'] = $obraComplementaria['DescripcionObraComplementaria'];
                    }
                    if(isset($obraComplementaria['UnidadObraComplementaria']) && !is_array($obraComplementaria['UnidadObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Unidad'] = $obraComplementaria['UnidadObraComplementaria'];
                    }
                    if(isset($obraComplementaria['CantidadObraComplementaria']) && !is_array($obraComplementaria['CantidadObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Cantidad'] = $obraComplementaria['CantidadObraComplementaria'];
                    }
                    if(isset($obraComplementaria['EdadObraComplementaria']) && !is_array($obraComplementaria['EdadObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Edad'] = $obraComplementaria['EdadObraComplementaria'];
                    }
                    if(isset($obraComplementaria['VidaUtilTotalObraComplementaria']) && !is_array($obraComplementaria['VidaUtilTotalObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Vida_Util_Total'] = $obraComplementaria['VidaUtilTotalObraComplementaria'];
                    }
                    if(isset($obraComplementaria['ValorUnitarioObraComplementaria']) && !is_array($obraComplementaria['ValorUnitarioObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Valor_Unitario'] = $obraComplementaria['ValorUnitarioObraComplementaria'];
                    }    
                    $control = $control + 1;
                }
            }
        }
        

        /*********************************************************************************************************************/

        if(isset($elementoPrincipal['EnfoqueDeMercado'])){

            $infoReimpresion['Terrenos']  = array();
            $enfoqueMercado = $elementoPrincipal['EnfoqueDeMercado'];
            $terrenos = $enfoqueMercado['Terrenos'];

            if(isset($terrenos['TerrenosDirectos'])){
                $infoReimpresion['Terrenos']['Terrenos_Directos'] = array();
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'] = array();
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'] = array();

                
                $terrenosDirectos = $terrenos['TerrenosDirectos'];       
                
                
                $control = 0; 
                foreach($terrenosDirectos as $terrenoDirecto){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Ubicacion'] = $terrenoDirecto['Calle'].". ".$terrenoDirecto['Colonia'].". ".$terrenoDirecto['CodigoPostal'].".";
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Descripcion'] = $terrenoDirecto['DescripcionDelPredio'];
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['C_U_S'] = $terrenoDirecto['CUS'];
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Uso_Suelo'] = $terrenoDirecto['UsoDelSuelo'];

                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_Negociacion'] = $terrenoDirecto['FactorDeNegociacion'];
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Superficie'] = $terrenoDirecto['Superficie'];
                    
                    if(isset($terrenoDirecto['Fzo']) && !is_array($terrenoDirecto['Fzo'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fzo'] = $terrenoDirecto['Fzo'];
                    }
                    if(isset($terrenoDirecto['Fub']) && !is_array($terrenoDirecto['Fub'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fub'] = $terrenoDirecto['Fub'];
                    }
                    if(isset($terrenoDirecto['FFr']) && !is_array($terrenoDirecto['FFr'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['FFr'] = $terrenoDirecto['FFr'];
                    }
                    if(isset($terrenoDirecto['Ffo']) && !is_array($terrenoDirecto['Ffo'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Ffo'] = $terrenoDirecto['Ffo'];
                    }
                    if(isset($terrenoDirecto['Fsu']) && !is_array($terrenoDirecto['Fsu'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fsu'] = $terrenoDirecto['Fsu'];
                    }
                    if(isset($terrenoDirecto['Fot'])){
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_otro'] = $terrenoDirecto['Fot']['Valor'];                    
                    }else{
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_otro'] = '';
                    }                
                    if(isset($terrenoDirecto['Fre']) && !is_array($terrenoDirecto['Fre'])){
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fre'] = $terrenoDirecto['Fre'];
                    }                    
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Precio_Solicitado'] = $terrenoDirecto['PrecioSolicitado'];

                    $control = $control + 1;
                } //print_r($infoReimpresion); exit();

                $conclusionHomologacionTerrenos = $terrenos['ConclusionesHomologacionTerrenos'];

                $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos'] = array();

                if(isset($conclusionHomologacionTerrenos['ValorUnitarioDeTierraPromedio']) && !is_array($conclusionHomologacionTerrenos['ValorUnitarioDeTierraPromedio'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Tierra_Promedio'] = $conclusionHomologacionTerrenos['ValorUnitarioDeTierraPromedio'];
                }
                if(isset($conclusionHomologacionTerrenos['ValorUnitarioDeTierraHomologado']) && !is_array($conclusionHomologacionTerrenos['ValorUnitarioDeTierraHomologado'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Tierra_Homologado'] = $conclusionHomologacionTerrenos['ValorUnitarioDeTierraHomologado'];
                }
                if(isset($conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMinimo']) && !is_array($conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMinimo'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Sin_Homologar_Minimo'] = $conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMinimo'];
                }
                if(isset($conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMaximo']) && !is_array($conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMaximo'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Sin_Homologar_Maximo'] = $conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMaximo'];
                }
                if(isset($conclusionHomologacionTerrenos['ValorUnitarioHomologadoMinimo']) && !is_array($conclusionHomologacionTerrenos['ValorUnitarioHomologadoMinimo'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Homologado_Minimo'] = $conclusionHomologacionTerrenos['ValorUnitarioHomologadoMinimo'];
                }
                if(isset($conclusionHomologacionTerrenos['ValorUnitarioHomologadoMaximo']) && !is_array($conclusionHomologacionTerrenos['ValorUnitarioHomologadoMaximo'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Homologado_Maximo'] = $conclusionHomologacionTerrenos['ValorUnitarioHomologadoMaximo'];
                }
            }
            

            /************************************************************************************************************************************************************************/

            if(isset($terrenos['TerrenosResidual'])){
                $infoReimpresion['Terrenos']['Terrenos_Residuales'] = array();                
                $terrenosResidual = $terrenos['TerrenosResidual'];

                if(isset($terrenosResidual['TipoDeProductoInmobiliarioPropuesto']) && !is_array($terrenosResidual['TipoDeProductoInmobiliarioPropuesto'])){
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Tipo_Producto_Inmoviliario_Propuesto'] = $terrenosResidual['TipoDeProductoInmobiliarioPropuesto'];
                }
                if(isset($terrenosResidual['NumeroDeUnidadesVendibles']) && !is_array($terrenosResidual['NumeroDeUnidadesVendibles'])){
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Numero_Unidades_Vendibles'] = $terrenosResidual['NumeroDeUnidadesVendibles'];
                }
                if(isset($terrenosResidual['SuperficieVendiblePorUnidad']) && !is_array($terrenosResidual['SuperficieVendiblePorUnidad'])){
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Superficie_Vendible_Unidad'] = $terrenosResidual['SuperficieVendiblePorUnidad'];
                }

                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables'] = array();
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables_2'] = array();

                $control = 0;
                foreach($terrenosResidual['InvestigacionProductosComparables'] as $terrenoResidualInvestigacionProductos){
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables'][$control]['Ubicacion'] = $terrenoResidualInvestigacionProductos['Calle'].". ".$terrenoResidualInvestigacionProductos['Colonia'].". ".$terrenoResidualInvestigacionProductos['CodigoPostal'].".";
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables'][$control]['Descripcion'] = $terrenoResidualInvestigacionProductos['DescripcionDelComparable'];   

                    if(isset($terrenoResidualInvestigacionProductos['SuperficieVendiblePorUnidad']) && isset($terrenoResidualInvestigacionProductos['PrecioSolicitado']) && isset($terrenoResidualInvestigacionProductos['FactorDeNegociacion'])){
                        $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables_2'][$control]['F_Negociacion'] = $terrenoResidualInvestigacionProductos['FactorDeNegociacion'];
                        $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables_2'][$control]['Superficie'] = $terrenoResidualInvestigacionProductos['SuperficieVendiblePorUnidad'];
                        $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables_2'][$control]['Precio_Solicitado'] = $terrenoResidualInvestigacionProductos['PrecioSolicitado'];
                    }

                    $control = $control + 1;
                }

                $conclusionHomologacionResiduales = $terrenosResidual['ConclusionesHomologacionCompResiduales'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales'] = array();

                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Promedio'] = $conclusionHomologacionResiduales['ValorUnitarioPromedio'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Homologado'] = $conclusionHomologacionResiduales['ValorUnitarioHomologado'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Sin_Homologar_Minimo'] = $conclusionHomologacionResiduales['ValorUnitarioSinHomologarMinimo'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Sin_Homologar_Maximo'] = $conclusionHomologacionResiduales['ValorUnitarioSinHomologarMaximo'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Homologado_Minimo'] = $conclusionHomologacionResiduales['ValorUnitarioHomologadoMinimo'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Homologado_Maximo'] = $conclusionHomologacionResiduales['ValorUnitarioHomologadoMaximo'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Aplicable_Residual'] = $conclusionHomologacionResiduales['ValorUnitarioAplicableAlResidual'];

                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual'] = array();
                $analisisResidual = $terrenosResidual['AnalisisResidual'];

                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual']['Total_Ingresos'] = $analisisResidual['TotalDeIngresos'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual']['Total_Egresos'] = $analisisResidual['TotalDeEgresos'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual']['Unidad_Propuesta'] = $analisisResidual['UtilidadPropuesta'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual']['Valor_Unitario_Tierra_Residual'] = $analisisResidual['ValorUnitarioDeTierraResidual'];

            }
            
            $infoReimpresion['Terrenos']['Valor_Unitario_Tierra_Del_Avaluo'] = $terrenos['ValorUnitarioDeTierraAplicableAlAvaluo'];
        
        

        /***********************************************************************************************************************/

            $infoReimpresion['Construcciones_En_Venta'] = array();
            $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables'] = array();
            $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaUno'] = array();
            $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaDos'] = array();

            $construccionesEnVenta = $enfoqueMercado['ConstruccionesEnVenta'];
            $investigacionProductosComparables = $construccionesEnVenta['InvestigacionProductosComparables'];

            $control = 0;
            foreach($investigacionProductosComparables as $productoComparable){
                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Ubicacion'] = $productoComparable['Calle'].". ".$productoComparable['Colonia'].". ".$productoComparable['CodigoPostal'];
                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Descripcion'] = $productoComparable['DescripcionDelComparable'];

                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['F_Negociacion'] = $productoComparable['FactorDeNegociacion'];
                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Superficie_Vendible'] = $productoComparable['SuperficieVendiblePorUnidad'];
                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Precio_Solicitado'] = $productoComparable['PrecioSolicitado'];

                $control = $control + 1;
            }

            $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta'] = array();
            if(isset($construccionesEnVenta['ConclusionesHomologacionConstruccionesEnVenta'])){
                $conclusionesHomologacionContruccionesVenta = $construccionesEnVenta['ConclusionesHomologacionConstruccionesEnVenta'];
            }   

            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioPromedio']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioPromedio'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Promedio'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioPromedio'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologado']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologado'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Homologado'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologado'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMinimo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMinimo'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Sin_Homolgar_Minimo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMinimo'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMaximo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMaximo'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Sin_Homolgar_Maximo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMaximo'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMinimo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMinimo'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Homologado_Minimo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMinimo'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMaximo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMaximo'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Homologado_Maximo']  = $conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMaximo'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioAplicableAlAvaluo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioAplicableAlAvaluo'])){
                $infoReimpresion['Construcciones_En_Venta']['Valor_Unitario_Aplicable_Avaluo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioAplicableAlAvaluo'];
            }
            
            if(isset($enfoqueMercado['ValorDeMercadoDelInmueble']) && !is_array($enfoqueMercado['ValorDeMercadoDelInmueble'])){
                $infoReimpresion['Construcciones_En_Venta']['Valor_Mercado_Del_Inmueble'] = $enfoqueMercado['ValorDeMercadoDelInmueble'];
            }            

            /************************************************************************************************************************************************************************/

            if(isset($enfoqueMercado['ConstruccionesEnRenta'])){
                $infoReimpresion['Construcciones_En_Renta'] = array();
                $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables'] = array();
                $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'] = array();
                $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'] = array();

                $construccionesEnRenta = $enfoqueMercado['ConstruccionesEnRenta'];
                $investigacionProductosComparables = $construccionesEnRenta['InvestigacionProductosComparables'];
                $control = 0;
                if(isset($investigacionProductosComparables['@attributes'])){
                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Ubicacion'] = $investigacionProductosComparables['Calle'].". ".$investigacionProductosComparables['Colonia'].". ".$investigacionProductosComparables['CodigoPostal'];
                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Descripcion'] = $investigacionProductosComparables['DescripcionDelComparable'];

                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['F_Negociacion'] = $investigacionProductosComparables['FactorDeNegociacion'];
                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Superficie_Vendible'] = $investigacionProductosComparables['SuperficieVendiblePorUnidad'];
                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Precio_Solicitado'] = $investigacionProductosComparables['PrecioSolicitado'];
                }else{
                    if(isset($investigacionProductosComparables[0])){
                        
                        foreach($investigacionProductosComparables as $productoComparable){
                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Ubicacion'] = $productoComparable['Calle'].". ".$productoComparable['Colonia'].". ".$productoComparable['CodigoPostal'];
                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Descripcion'] = $productoComparable['DescripcionDelComparable'];

                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['F_Negociacion'] = $productoComparable['FactorDeNegociacion'];
                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Superficie_Vendible'] = $productoComparable['SuperficieVendiblePorUnidad'];
                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Precio_Solicitado'] = $productoComparable['PrecioSolicitado'];

                            $control = $control + 1;
                        }
                    }
                }
                

                $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta'] = array();
                //Se usa ConclusionesHomologacionConstruccionesEnVenta porque asi llega en el XML
                if(isset($construccionesEnRenta['ConclusionesHomologacionConstruccionesEnRenta'])){
                    $conclusionesHomologacionContruccionesRenta = $construccionesEnRenta['ConclusionesHomologacionConstruccionesEnRenta'];
                }
                
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioPromedio']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioPromedio'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Promedio'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioPromedio'];
                }
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologado']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologado'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Homologado'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologado'];
                }
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMinimo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMinimo'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Sin_Homolgar_Minimo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMinimo'];
                }
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMaximo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMaximo'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Sin_Homolgar_Maximo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMaximo'];
                }
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMinimo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMinimo'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Homologado_Minimo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMinimo'];
                }
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMaximo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMaximo'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Homologado_Maximo']  = $conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMaximo'];
                }

                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioAplicableAlAvaluo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioAplicableAlAvaluo'])){
                    $infoReimpresion['Construcciones_En_Renta']['Valor_Unitario_Aplicable_Avaluo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioAplicableAlAvaluo'];
                }

            }        

    }
        /************************************************************************************************************************************************************************/



        /************************************************************************************************************************************************************************/

        //$superficieDelTerreno = $terreno['SuperficieDelTerreno'];
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno'] = array();
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales'] = array();

        if(isset($superficieDelTerreno[0])){
            $control = 0;
            foreach($superficieDelTerreno as $supDelTerreno){

                $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Fracc'] = $supDelTerreno['IdentificadorFraccionN1'];
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Clave_Area_Valor'] = $supDelTerreno['ClaveDeAreaDeValor'];
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Superficie_m2'] = $supDelTerreno['SuperficieFraccionN1'];
                if(isset($supDelTerreno['Fzo'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Fzo'] = $supDelTerreno['Fzo'];
                }        
                if(isset($supDelTerreno['Fub'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Fub'] = $supDelTerreno['Fub'];
                }
                if(isset($supDelTerreno['FFr'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['FFr'] = $supDelTerreno['FFr'];
                }
                if(isset($supDelTerreno['Ffo'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Ffo'] = $supDelTerreno['Ffo'];
                }
                if(isset($supDelTerreno['Fsu'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Fsu'] = $supDelTerreno['Fsu'];            
                }
                if(isset($supDelTerreno['Fot'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Fot'] = $supDelTerreno['Fot']['Valor'] === '1' ? $supDelTerreno['Fot']['Valor'].".00"." ".$supDelTerreno['Fot']['Descripcion'] : $supDelTerreno['Fot']['Valor']." ".$supDelTerreno['Fot']['Descripcion'];
                }
                if(isset($supDelTerreno['Fre'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['F_Resultante'] = $supDelTerreno['Fre'];
                }
                
                if($tipoDeAvaluo == 'Catastral'){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Valor_Catastral'] = $supDelTerreno['ValorCatastralDeTierraAplicableALaFraccion'];
                }
                
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno'][$control]['Valor_Fraccion'] = $supDelTerreno['ValorDeLaFraccionN'];
                $control = $control + 1;

            }
        }else{

            $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Fracc'] = $superficieDelTerreno['IdentificadorFraccionN1'];
            $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Clave_Area_Valor'] = $superficieDelTerreno['ClaveDeAreaDeValor'];
            $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Superficie_m2'] = $superficieDelTerreno['SuperficieFraccionN1'];
            if(isset($superficieDelTerreno['Fzo'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Fzo'] = $superficieDelTerreno['Fzo'];
            }        
            if(isset($superficieDelTerreno['Fub'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Fub'] = $superficieDelTerreno['Fub'];
            }
            if(isset($superficieDelTerreno['FFr'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['FFr'] = $superficieDelTerreno['FFr'];
            }
            if(isset($superficieDelTerreno['Ffo'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Ffo'] = $superficieDelTerreno['Ffo'];
            }
            if(isset($superficieDelTerreno['Fsu'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Fsu'] = $superficieDelTerreno['Fsu'];            
            }
            if(isset($superficieDelTerreno['Fot'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Fot'] = $superficieDelTerreno['Fot']['Valor'] === '1' ? $superficieDelTerreno['Fot']['Valor'].".00"." ".$superficieDelTerreno['Fot']['Descripcion'] : $superficieDelTerreno['Fot']['Valor']." ".$superficieDelTerreno['Fot']['Descripcion'];
            }
            if(isset($superficieDelTerreno['Fre'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['F_Resultante'] = $superficieDelTerreno['Fre'];
            }
            
            if($tipoDeAvaluo == 'Catastral'){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Valor_Catastral'] = $superficieDelTerreno['ValorCatastralDeTierraAplicableALaFraccion'];
            }
            
            $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Valor_Fraccion'] = $superficieDelTerreno['ValorDeLaFraccionN'];

        }

        

        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales']['Total_Superficie'] = $terreno['SuperficieTotalDelTerreno'];
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales']['Valor_Del_Terreno_Total'] = $terreno['ValorTotalDelTerreno'];        

        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales']['Indiviso_Unidad_Que_Se_Valua'] = $terreno['Indiviso'] <= 1 ? $terreno['Indiviso'] * 100 : $terreno['Indiviso'];
        
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales']['Valor_Total_Del_Terreno_Proporcional'] = $terreno['ValorTotalDelTerrenoProporcional'];

        
        /*********************************************************************************************************************************************************************************/
        $infoReimpresion['Calculo_Valor_Construcciones'] = array();
        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'] = array();
        $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas'] = array();
        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'] = array();        
        $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes'] = array();

        if($tipoDeAvaluo == 'Comercial'){

            if(isset($tiposContruccion['ConstruccionesPrivativas']['@attributes'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Fracc'] = 1;
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Descripcion'] = $tiposContruccion['ConstruccionesPrivativas']['Descripcion'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Uso'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveUso'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']);
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Superficie_m2'] = $tiposContruccion['ConstruccionesPrivativas']['Superficie'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Valor_Unitario'] = $tiposContruccion['ConstruccionesPrivativas']['ValorunitariodereposicionNuevo'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Edad'] = $tiposContruccion['ConstruccionesPrivativas']['Edad'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Fco'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveConservacion'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['FRe'] = $tiposContruccion['ConstruccionesPrivativas']['FactorResultante'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Valor_Fraccion'] = $tiposContruccion['ConstruccionesPrivativas']['ValorDeLaFraccionN'];            
            }
    
            if(isset($tiposContruccion['ConstruccionesPrivativas'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesPrivativas'] as $construccionPrivativa){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Fracc'] = $control + 1;
                    if(isset($construccionPrivativa['Descripcion']) && !is_array($construccionPrivativa['Descripcion'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Descripcion'] = $construccionPrivativa['Descripcion'];
                    }
                    if(isset($construccionPrivativa['ClaveUso']) && !is_array($construccionPrivativa['ClaveUso'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Uso'] = $construccionPrivativa['ClaveUso'];
                    }
                    if(isset($construccionPrivativa['ClaveClase']) && !is_array($construccionPrivativa['ClaveClase'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Clase'] = $this->modelFis->getClase($construccionPrivativa['ClaveClase']);
                    }
                    if(isset($construccionPrivativa['Superficie']) && !is_array($construccionPrivativa['Superficie'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Superficie_m2'] = $construccionPrivativa['Superficie'];
                    }
                    if(isset($construccionPrivativa['ValorunitariodereposicionNuevo']) && !is_array($construccionPrivativa['ValorunitariodereposicionNuevo'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor_Unitario'] = $construccionPrivativa['ValorunitariodereposicionNuevo'];
                    }
                    if(isset($construccionPrivativa['Edad']) && !is_array($construccionPrivativa['Edad'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Edad'] = $construccionPrivativa['Edad'];
                    }
                    if(isset($construccionPrivativa['ClaveConservacion']) && !is_array($construccionPrivativa['ClaveConservacion'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Fco'] = $construccionPrivativa['ClaveConservacion'];
                    }
                    if(isset($construccionPrivativa['FactorResultante']) && !is_array($construccionPrivativa['FactorResultante'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['FRe'] = $construccionPrivativa['FactorResultante'];
                    }
                    if(isset($construccionPrivativa['ValorDeLaFraccionN']) && !is_array($construccionPrivativa['ValorDeLaFraccionN'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor_Fraccion'] = $construccionPrivativa['ValorDeLaFraccionN'];
                    }
                                    
                    $control = $control + 1;
                }
            }
    
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Superficie'] = $tiposContruccion['SuperficieTotalDeConstruccionesPrivativas'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Construcciones_Privativas'] = $tiposContruccion['ValorTotalDeConstruccionesPrivativas'];
            
            if(isset($tiposContruccion['ConstruccionesComunes']['@attributes'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Fracc'] = 1;
                if(isset($tiposContruccion['ConstruccionesComunes']['Descripcion']) && !is_array($tiposContruccion['ConstruccionesComunes']['Descripcion'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Descripcion'] = $tiposContruccion['ConstruccionesComunes']['Descripcion'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ClaveUso']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveUso'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Uso'] = $tiposContruccion['ConstruccionesComunes']['ClaveUso'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ClaveClase']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveClase'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesComunes']['ClaveClase']);
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['Superficie']) && !is_array($tiposContruccion['ConstruccionesComunes']['Superficie'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Superficie_m2'] = $tiposContruccion['ConstruccionesComunes']['Superficie'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ValorunitariodereposicionNuevo']) && !is_array($tiposContruccion['ConstruccionesComunes']['ValorunitariodereposicionNuevo'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor_Unitario'] = $tiposContruccion['ConstruccionesComunes']['ValorunitariodereposicionNuevo'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['Edad']) && !is_array($tiposContruccion['ConstruccionesComunes']['Edad'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Edad'] = $tiposContruccion['ConstruccionesComunes']['Edad'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ClaveConservacion']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveConservacion'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Fco'] = $tiposContruccion['ConstruccionesComunes']['ClaveConservacion'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['FactorResultante']) && !is_array($tiposContruccion['ConstruccionesComunes']['FactorResultante'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['FRe'] = $tiposContruccion['ConstruccionesComunes']['FactorResultante'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ValorDeLaFraccionN']) && !is_array($tiposContruccion['ConstruccionesComunes']['ValorDeLaFraccionN'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor_Fraccion'] = $tiposContruccion['ConstruccionesComunes']['ValorDeLaFraccionN'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['PorcentajeIndivisoComunes'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Indiviso'] = $tiposContruccion['ConstruccionesComunes']['PorcentajeIndivisoComunes'];            
                }    
            }
    
            if(isset($tiposContruccion['ConstruccionesComunes'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesComunes'] as $construccionComun){
                    if(isset($construccionComun['Descripcion']) && !is_array($construccionComun['Descripcion'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Fracc'] = $control + 1;
                    }
                    if(isset($construccionComun['Descripcion']) && !is_array($construccionComun['Descripcion'])){                        
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Descripcion'] = $construccionComun['Descripcion'];
                    }
                    if(isset($construccionComun['ClaveUso']) && !is_array($construccionComun['ClaveUso'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Uso'] = $construccionComun['ClaveUso'];
                    }
                    if(isset($construccionComun['ClaveClase']) && !is_array($construccionComun['ClaveClase'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Clase'] = $this->modelFis->getClase($construccionComun['ClaveClase']);
                    }
                    if(isset($construccionComun['Superficie']) && !is_array($construccionComun['Superficie'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Superficie_m2'] = $construccionComun['Superficie'];
                    }
                    if(isset($construccionComun['ValorunitariodereposicionNuevo']) && !is_array($construccionComun['ValorunitariodereposicionNuevo'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor_Unitario'] = $construccionComun['ValorunitariodereposicionNuevo'];
                    }
                    if(isset($construccionComun['Edad']) && !is_array($construccionComun['Edad'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Edad'] = $construccionComun['Edad'];
                    }
                    if(isset($construccionComun['ClaveConservacion']) && !is_array($construccionComun['ClaveConservacion'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Fco'] = $construccionComun['ClaveConservacion'];
                    }
                    if(isset($construccionComun['FactorResultante']) && !is_array($construccionComun['FactorResultante'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['FRe'] = $construccionComun['FactorResultante'];
                    }
                    if(isset($construccionComun['ValorDeLaFraccionN']) && !is_array($construccionComun['ValorDeLaFraccionN'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor_Fraccion'] = $construccionComun['ValorDeLaFraccionN'];
                    }
                    if(isset($construccionComun['PorcentajeIndivisoComunes'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Indiviso'] = $construccionComun['PorcentajeIndivisoComunes'];
                    }    
                    
                    $control = $control + 1;
                }
            }
    
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Superficie'] = $tiposContruccion['SuperficieTotalDeConstruccionesComunes'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes'] = $tiposContruccion['ValorTotalDeConstruccionesComunes'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Valor_Total_De_Las_Construcciones'] = $tiposContruccion['ValorTotalDeConstruccionesComunes'] + $tiposContruccion['ValorTotalDeLasConstruccionesComunesProIndiviso'];
            

        }else{

            if(isset($tiposContruccion['ConstruccionesPrivativas']['@attributes'])){
                if(isset($tiposContruccion['ConstruccionesPrivativas']['Descripcion']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['Descripcion'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Tipo'] = 1;
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['Descripcion']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['Descripcion'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Descripcion'] = $tiposContruccion['ConstruccionesPrivativas']['Descripcion'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveUso']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveUso'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Uso'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveUso'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['NumeroDeNivelesDelTipo']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['NumeroDeNivelesDelTipo'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Numero_Niveles_Tipo'] = $tiposContruccion['ConstruccionesPrivativas']['NumeroDeNivelesDelTipo'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveClase'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']);
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['ValorUnitarioCatastral']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ValorUnitarioCatastral'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Valor_Unitario'] = $tiposContruccion['ConstruccionesPrivativas']['ValorUnitarioCatastral'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['DepreciacionPorEdad']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['DepreciacionPorEdad'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Depreciacion_Por_Edad'] = $tiposContruccion['ConstruccionesPrivativas']['DepreciacionPorEdad'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['ValorDeLaFraccionN']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ValorDeLaFraccionN'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Valor'] = $tiposContruccion['ConstruccionesPrivativas']['ValorDeLaFraccionN'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['Superficie']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['Superficie'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Sup'] = $tiposContruccion['ConstruccionesPrivativas']['Superficie'];
                }
                                
            }
    
            if(isset($tiposContruccion['ConstruccionesPrivativas'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesPrivativas'] as $construccionPrivativa){
                    if(isset($construccionPrivativa['Descripcion']) && !is_array($construccionPrivativa['Descripcion'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Tipo'] = $control + 1;
                    }
                    if(isset($construccionPrivativa['Descripcion']) && !is_array($construccionPrivativa['Descripcion'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Descripcion'] = $construccionPrivativa['Descripcion'];
                    }
                    if(isset($construccionPrivativa['ClaveUso']) && !is_array($construccionPrivativa['ClaveUso'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Uso'] = $construccionPrivativa['ClaveUso'];
                    }
                    if(isset($construccionPrivativa['NumeroDeNivelesDelTipo']) && !is_array($construccionPrivativa['NumeroDeNivelesDelTipo'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Numero_Niveles_Tipo'] = $construccionPrivativa['NumeroDeNivelesDelTipo'];
                    }
                    if(isset($construccionPrivativa['ClaveRangoDeNiveles']) && !is_array($construccionPrivativa['ClaveRangoDeNiveles'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Clave_Rango_Niveles'] = $construccionPrivativa['ClaveRangoDeNiveles'];
                    }
                    if(isset($construccionPrivativa['ClaveClase']) && !is_array($construccionPrivativa['ClaveClase'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Clase'] = $this->modelFis->getClase($construccionPrivativa['ClaveClase']);
                    }
                    if(isset($construccionPrivativa['ValorUnitarioCatastral']) && !is_array($construccionPrivativa['ValorUnitarioCatastral'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor_Unitario'] = $construccionPrivativa['ValorUnitarioCatastral'];
                    }
                    if(isset($construccionPrivativa['DepreciacionPorEdad']) && !is_array($construccionPrivativa['DepreciacionPorEdad'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Depreciacion_Por_Edad'] = $construccionPrivativa['DepreciacionPorEdad'];
                    }
                    if(isset($construccionPrivativa['ValorDeLaFraccionN']) && !is_array($construccionPrivativa['ValorDeLaFraccionN'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor'] = $construccionPrivativa['ValorDeLaFraccionN'];
                    }
                    if(isset($construccionPrivativa['Superficie']) && !is_array($construccionPrivativa['Superficie'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Sup'] = $construccionPrivativa['Superficie'];
                    }
                                    
                    $control = $control + 1;
                }
            }
    
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Superficie'] = $tiposContruccion['SuperficieTotalDeConstruccionesPrivativas'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Construcciones_Privativas'] = $tiposContruccion['ValorTotalDeConstruccionesPrivativas'];
            
            if(isset($tiposContruccion['ConstruccionesComunes']['@attributes'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Tipo'] = 1;
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Descripcion'] = $tiposContruccion['ConstruccionesComunes']['Descripcion'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Uso'] = $tiposContruccion['ConstruccionesComunes']['ClaveUso'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Numero_Niveles_Tipo'] = $tiposContruccion['ConstruccionesComunes']['NumeroDeNivelesDelTipo'];
                if(isset($tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ClaveClase']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveClase'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesComunes']['ClaveClase']);
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ValorUnitarioCatastral']) && !is_array($tiposContruccion['ConstruccionesComunes']['ValorUnitarioCatastral'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor_Unitario'] = $tiposContruccion['ConstruccionesComunes']['ValorUnitarioCatastral'];
                }
                    if(isset($tiposContruccion['ConstruccionesComunes']['DepreciacionPorEdad']) && !is_array($tiposContruccion['ConstruccionesComunes']['DepreciacionPorEdad'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Depreciacion_Por_Edad'] = $tiposContruccion['ConstruccionesComunes']['DepreciacionPorEdad'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ValorDeLaFraccionN']) && !is_array($tiposContruccion['ConstruccionesComunes']['ValorDeLaFraccionN'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor'] = $tiposContruccion['ConstruccionesComunes']['ValorDeLaFraccionN'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['Superficie']) && !is_array($tiposContruccion['ConstruccionesComunes']['Superficie'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Sup'] = $tiposContruccion['ConstruccionesComunes']['Superficie'];
                }
            }
    
            if(isset($tiposContruccion['ConstruccionesComunes'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesComunes'] as $construccionComun){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Tipo'] = $control + 1;
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Descripcion'] = $construccionComun['Descripcion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Uso'] = $construccionComun['ClaveUso'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Numero_Niveles_Tipo'] = $construccionComun['NumeroDeNivelesDelTipo'];
                    if(isset($construccionComun['ClaveRangoDeNiveles']) && !is_array($construccionComun['ClaveRangoDeNiveles'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Clave_Rango_Niveles'] = $construccionComun['ClaveRangoDeNiveles'];
                    }
                    if(isset($construccionComun['ClaveClase']) && !is_array($construccionComun['ClaveClase'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Clase'] = $this->modelFis->getClase($construccionComun['ClaveClase']);
                    }
                    if(isset($construccionComun['ValorUnitarioCatastral']) && !is_array($construccionComun['ValorUnitarioCatastral'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor_Unitario'] = $construccionComun['ValorUnitarioCatastral'];
                    }
                    if(isset($construccionComun['DepreciacionPorEdad']) && !is_array($construccionComun['DepreciacionPorEdad'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Depreciacion_Por_Edad'] = $construccionComun['DepreciacionPorEdad'];
                    }
                    if(isset($construccionComun['ValorDeLaFraccionN']) && !is_array($construccionComun['ValorDeLaFraccionN'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor'] = $construccionComun['ValorDeLaFraccionN'];
                    }
                    if(isset($construccionComun['Superficie']) && !is_array($construccionComun['Superficie'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Sup'] = $construccionComun['Superficie'];                                        
                    }
                    $control = $control + 1;
                }
            }
    
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Superficie'] = $tiposContruccion['SuperficieTotalDeConstruccionesComunes'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes'] = $tiposContruccion['ValorTotalDeConstruccionesComunes'];
            
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes_Por_Indiviso'] = $tiposContruccion['ValorTotalDeLasConstruccionesComunesProIndiviso'];
            

        }        

        /***********************************************************************************************************************************************************************/ 

        $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios'] = array();
        $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Totales'] = array();
        $control = 0;
        if(isset($elementosAccesorios['Privativas']['@attributes'])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'] = array();
            $control = 0;
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'] = array();
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'E';
            if(isset($elementosAccesorios['Privativas']['ClaveElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['ClaveElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $elementosAccesorios['Privativas']['ClaveElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Privativas']['DescripcionElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['DescripcionElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $elementosAccesorios['Privativas']['DescripcionElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Privativas']['CantidadElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['CantidadElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $elementosAccesorios['Privativas']['CantidadElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Privativas']['EdadElementoAccesorio']) && !is_array($elementosAccesorios['Privativas']['EdadElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $elementosAccesorios['Privativas']['EdadElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Privativas']['ImporteElementoAccesorio'])  && !is_array($elementosAccesorios['Privativas']['ImporteElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $elementosAccesorios['Privativas']['ImporteElementoAccesorio'];
            }
            
            $control = $control + 1;
        }

        if(isset($elementosAccesorios['Privativas'][0])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'] = array();
            $control = 0;
            foreach($elementosAccesorios['Privativas'] as $elementoAccesorio){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'E';
                if(isset($elementoAccesorio['ClaveElementoAccesorio']) && !is_array($elementoAccesorio['ClaveElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $elementoAccesorio['ClaveElementoAccesorio'];
                }
                if(isset($elementoAccesorio['DescripcionElementoAccesorio']) && !is_array($elementoAccesorio['DescripcionElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $elementoAccesorio['DescripcionElementoAccesorio'];
                }
                if(isset($elementoAccesorio['CantidadElementoAccesorio']) && !is_array($elementoAccesorio['CantidadElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $elementoAccesorio['CantidadElementoAccesorio'];
                }
                if(isset($elementoAccesorio['ValorUnitarioElementoAccesorio']) && !is_array($elementoAccesorio['ValorUnitarioElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $elementoAccesorio['ValorUnitarioElementoAccesorio'];
                }
                if(isset($elementoAccesorio['EdadElementoAccesorio']) && !is_array($elementoAccesorio['EdadElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $elementoAccesorio['EdadElementoAccesorio'];
                }
                if(isset($elementoAccesorio['ImporteElementoAccesorio']) && !is_array($elementoAccesorio['ImporteElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $elementoAccesorio['ImporteElementoAccesorio'];
                }  
                                
                $control = $control + 1;
            }
        }

        if(isset($obrasComplementarias['Privativas']['@attributes'])){  
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'O';
            if(isset($obrasComplementarias['Privativas']['ClaveObraComplementaria']) && !is_array($obrasComplementarias['Privativas']['ClaveObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $obrasComplementarias['Privativas']['ClaveObraComplementaria'];
            }
            if(isset($obrasComplementarias['Privativas']['DescripcionObraComplementaria']) && !is_array($obrasComplementarias['Privativas']['DescripcionObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $obrasComplementarias['Privativas']['DescripcionObraComplementaria'];
            }
            if(isset($obrasComplementarias['Privativas']['CantidadObraComplementaria']) && !is_array($obrasComplementarias['Privativas']['CantidadObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $obrasComplementarias['Privativas']['CantidadObraComplementaria'];
            }

            if(isset($obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria']) && !is_array($obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria'];
            }
            if(isset($obrasComplementarias['Privativas']['EdadObraComplementaria']) && !is_array($obrasComplementarias['Privativas']['EdadObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $obrasComplementarias['Privativas']['EdadObraComplementaria'];
            }
            if(isset($obrasComplementarias['Privativas']['ImporteObraComplementaria']) && !is_array($obrasComplementarias['Privativas']['ImporteObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $obrasComplementarias['Privativas']['ImporteObraComplementaria'];
            }
            $control = $control + 1;            
        }

        if(isset($obrasComplementarias['Privativas'][0])){    
            foreach($obrasComplementarias['Privativas'] as $obraComplementaria){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'O';
                if(isset($obraComplementaria['ClaveObraComplementaria']) && !is_array($obraComplementaria['ClaveObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $obraComplementaria['ClaveObraComplementaria'];
                }
                if(isset($obraComplementaria['DescripcionObraComplementaria']) && !is_array($obraComplementaria['DescripcionObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $obraComplementaria['DescripcionObraComplementaria'];
                }
                if(isset($obraComplementaria['CantidadObraComplementaria']) && !is_array($obraComplementaria['CantidadObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $obraComplementaria['CantidadObraComplementaria'];
                }
                if(isset($obraComplementaria['ValorUnitarioObraComplementaria']) && !is_array($obraComplementaria['ValorUnitarioObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $obraComplementaria['ValorUnitarioObraComplementaria'];
                }
                if(isset($obraComplementaria['EdadObraComplementaria']) && !is_array($obraComplementaria['EdadObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $obraComplementaria['EdadObraComplementaria'];
                }
                if(isset($obraComplementaria['ImporteObraComplementaria']) && !isset($obraComplementaria['ImporteObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $obraComplementaria['ImporteObraComplementaria'];                
                }    
                $control = $control + 1;
            }
        }

        /***************************************************************************************************************************************************/
        if(isset($instalacionesEspeciales['Privativas']['@attributes'])){  
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'IE';
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $instalacionesEspeciales['Privativas']['ClaveInstalacionEspecial'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $instalacionesEspeciales['Privativas']['DescripcionInstalacionEspecial'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $instalacionesEspeciales['Privativas']['CantidadInstalacionEspecial'];
            if(isset($instalacionesEspeciales['Privativas']['CostoUnitarioInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Costo_Unitario'] = $instalacionesEspeciales['Privativas']['CostoUnitarioInstalacionEspecial'];
            }
            if(isset($instalacionesEspeciales['Privativas']['EdadInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $instalacionesEspeciales['Privativas']['EdadInstalacionEspecial'];
            }
            if(isset($instalacionesEspeciales['Privativas']['ImporteInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $instalacionesEspeciales['Privativas']['ImporteInstalacionEspecial'];
            }    
            $control = $control + 1;            
        }

        if(isset($instalacionesEspeciales['Privativas'][0])){   
            foreach($instalacionesEspeciales['Privativas'] as $instalacionEspeciales){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'IE';
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $instalacionEspeciales['ClaveInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $instalacionEspeciales['DescripcionInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $instalacionEspeciales['CantidadInstalacionEspecial'];
                if(isset($instalacionEspeciales['CostoUnitarioInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Costo_Unitario'] = $instalacionEspeciales['CostoUnitarioInstalacionEspecial'];
                }
                if(isset($instalacionEspeciales['EdadInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $instalacionEspeciales['EdadInstalacionEspecial'];
                }
                if(isset($instalacionEspeciales['ImporteInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $instalacionEspeciales['ImporteInstalacionEspecial'];                
                }    
                $control = $control + 1;
            }
        }

        /******************************************************************************************************************************************************************/

        if(isset($elementosAccesorios['Comunes']['@attributes'])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'] = array();
            $control = 0;            
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'E';
            if(isset($elementosAccesorios['Comunes']['ClaveElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['ClaveElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $elementosAccesorios['Comunes']['ClaveElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Comunes']['DescripcionElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['DescripcionElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $elementosAccesorios['Comunes']['DescripcionElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Comunes']['DescripcionElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['DescripcionElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $elementosAccesorios['Comunes']['CantidadElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Comunes']['ValorUnitarioElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['ValorUnitarioElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Valor_Unitario'] = $elementosAccesorios['Comunes']['ValorUnitarioElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Comunes']['EdadElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['EdadElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $elementosAccesorios['Comunes']['EdadElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Comunes']['ImporteElementoAccesorio']) && !is_array($elementosAccesorios['Comunes']['ImporteElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $elementosAccesorios['Comunes']['ImporteElementoAccesorio'];
            }
            $control = $control + 1;          
            
        }

        if(isset($elementosAccesorios['Comunes'][0])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'] = array();
            $control = 0;
            foreach($elementosAccesorios['Comunes'] as $elementoAccesorio){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'E';
                if(isset($elementoAccesorio['ClaveElementoAccesorio']) && !is_array($elementoAccesorio['ClaveElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $elementoAccesorio['ClaveElementoAccesorio'];
                }
                if(isset($elementoAccesorio['ClaveElementoAccesorio']) && !is_array($elementoAccesorio['DescripcionElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $elementoAccesorio['DescripcionElementoAccesorio'];
                }
                if(isset($elementoAccesorio['CantidadElementoAccesorio']) && !is_array($elementoAccesorio['CantidadElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $elementoAccesorio['CantidadElementoAccesorio'];
                }
                if(isset($elementoAccesorio['ValorUnitarioElementoAccesorio']) && !is_array($elementoAccesorio['ValorUnitarioElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Valor_Unitario'] = $elementoAccesorio['ValorUnitarioElementoAccesorio'];
                }
                if(isset($elementoAccesorio['EdadElementoAccesorio']) && !is_array($elementoAccesorio['EdadElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $elementoAccesorio['EdadElementoAccesorio'];
                }
                if(isset($elementoAccesorio['ImporteElementoAccesorio']) && !is_array($elementoAccesorio['ImporteElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $elementoAccesorio['ImporteElementoAccesorio'];
                }

                $control = $control + 1;
            }
        }

        if(isset($obrasComplementarias['Comunes']['@attributes'])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'] = array();
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'O';
            if(isset($obrasComplementarias['Comunes']['ClaveObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['ClaveObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $obrasComplementarias['Comunes']['ClaveObraComplementaria'];
            }
            if(isset($obrasComplementarias['Comunes']['DescripcionObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['DescripcionObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $obrasComplementarias['Comunes']['DescripcionObraComplementaria'];
            }
            if(isset($obrasComplementarias['Comunes']['CantidadObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['CantidadObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $obrasComplementarias['Comunes']['CantidadObraComplementaria'];
            }
            if(isset($obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Valor_Unitario'] = $obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria'];
            }
            if(isset($obrasComplementarias['Comunes']['EdadObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['EdadObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $obrasComplementarias['Comunes']['EdadObraComplementaria'];
            }
            if(isset($obrasComplementarias['Comunes']['ImporteObraComplementaria']) && !is_array($obrasComplementarias['Comunes']['ImporteObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $obrasComplementarias['Comunes']['ImporteObraComplementaria'];
            }
            
            $control = $control + 1;   
        }

        if(isset($obrasComplementarias['Comunes'][0])){            
            foreach($obrasComplementarias['Comunes'] as $obraComplementaria){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'O';
                if(isset($obraComplementaria['ClaveObraComplementaria']) && !is_array($obraComplementaria['ClaveObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $obraComplementaria['ClaveObraComplementaria'];
                }
                if(isset($obraComplementaria['DescripcionObraComplementaria']) && !is_array($obraComplementaria['DescripcionObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $obraComplementaria['DescripcionObraComplementaria'];
                }
                if(isset($obraComplementaria['CantidadObraComplementaria']) && !is_array($obraComplementaria['CantidadObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $obraComplementaria['CantidadObraComplementaria'];
                }
                if(isset($obraComplementaria['ValorUnitarioObraComplementaria']) && !is_array($obraComplementaria['ValorUnitarioObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Valor_Unitario'] = $obraComplementaria['ValorUnitarioObraComplementaria'];
                }
                if(isset($obraComplementaria['EdadObraComplementaria']) && !is_array($obraComplementaria['EdadObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $obraComplementaria['EdadObraComplementaria'];
                }
                if(isset($obraComplementaria['ImporteObraComplementaria']) && !is_array($obraComplementaria['ImporteObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $obraComplementaria['ImporteObraComplementaria'];                
                }

                $control = $control + 1;
            }
        }

        /***************************************************************************************************************************************************/
        if(isset($instalacionesEspeciales['Comunes']['@attributes'])){  
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'IE';
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $instalacionesEspeciales['Comunes']['ClaveInstalacionEspecial'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $instalacionesEspeciales['Comunes']['DescripcionInstalacionEspecial'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $instalacionesEspeciales['Comunes']['CantidadInstalacionEspecial'];
            if(isset($instalacionesEspeciales['Comunes']['CostoUnitarioInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Costo_Unitario'] = $instalacionesEspeciales['Comunes']['CostoUnitarioInstalacionEspecial'];
            }
            if(isset($instalacionesEspeciales['Comunes']['EdadInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $instalacionesEspeciales['Comunes']['EdadInstalacionEspecial'];
            }
            if(isset($instalacionesEspeciales['Comunes']['ImporteInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $instalacionesEspeciales['Comunes']['ImporteInstalacionEspecial'];
            }    
            $control = $control + 1;            
        }

        if(isset($instalacionesEspeciales['Comunes'][0])){   
            foreach($instalacionesEspeciales['Comunes'] as $instalacionEspeciales){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'IE';
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $instalacionEspeciales['ClaveInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $instalacionEspeciales['DescripcionInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $instalacionEspeciales['CantidadInstalacionEspecial'];
                if(isset($instalacionEspeciales['CostoUnitarioInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Costo_Unitario'] = $instalacionEspeciales['CostoUnitarioInstalacionEspecial'];
                }
                if(isset($instalacionEspeciales['EdadInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $instalacionEspeciales['EdadInstalacionEspecial'];
                }
                if(isset($instalacionEspeciales['ImporteInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $instalacionEspeciales['ImporteInstalacionEspecial'];                
                }    
                $control = $control + 1;
            }
        }

        /***************************************************************************************************************************************************/

        $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Totales']['Indiviso_Unidad_Que_Se_Valua'] = $terreno['Indiviso'] <= 1 ? $terreno['Indiviso'] * 100 : $terreno['Indiviso'];
        if(isset($elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasPrivativas']) && isset($elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasComunes']) && !is_array($elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasPrivativas']) && !is_array($elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasComunes'])){

            $sumatoria = 0;
            if(isset($elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasPrivativas']) && isset($elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasComunes'])){
                $sumatoria = $sumatoria + $elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasPrivativas'] + $elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasComunes'];
            }

            if(isset($elementosConstruccion['ImporteTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas']) && isset($elementosConstruccion['ImporteTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes'])){
                $sumatoria = $sumatoria + $elementosConstruccion['ImporteTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas'] + $elementosConstruccion['ImporteTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas'];
            }

            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Totales']['Total_De_Las_Instalaciones'] = $sumatoria;
        }    

        $enfoqueCostos = $elementoPrincipal['EnfoqueDeCostos'];
        if(isset($enfoqueCostos['ImporteTotalDelEnfoqueDeCostos'])){ 
            $infoReimpresion['Indice_Fisico_Directo'] = $enfoqueCostos['ImporteTotalDelEnfoqueDeCostos'];
        }
        //print_r($infoReimpresion); exit();
        if($tipoDeAvaluo == 'Catastral'){
            $infoReimpresion['Importe_Instalaciones_Especiales_Elementos_Accesorios_Obras_Comp'] = $enfoqueCostos['ImporteInstalacionesEspeciales'];
            $infoReimpresion['Importe_Total_Valor_Catastral'] = $enfoqueCostos['ImporteTotalValorCatastral'];
            $infoReimpresion['Avance_Obra'] = $enfoqueCostos['AvanceDeObra'] <= 1 ? $enfoqueCostos['AvanceDeObra'] * 100 : $enfoqueCostos['AvanceDeObra'];
            $infoReimpresion['Importe_Total_Valor_Catastral_Obra_Proceso'] = $enfoqueCostos['ImporteTotalValorCatastralObraEnProceso'];

            /*$consideraciones = $elementoPrincipal['ConsideracionesPreviasAlAvaluo'];

            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'] = $consideraciones['ConsideracionesPreviasAlAvaluo'];*/
        }        

        if(isset($elementoPrincipal['ConsideracionesPreviasAlAvaluo'])){
            $consideraciones = $elementoPrincipal['ConsideracionesPreviasAlAvaluo'];
            if(isset($consideraciones['ConsideracionesPreviasAlAvaluo'])){
                if(is_array($consideraciones['ConsideracionesPreviasAlAvaluo'])){

                }else{
                    $infoReimpresion['Consideraciones_Previas_Al_Avaluo'] = $consideraciones['ConsideracionesPreviasAlAvaluo'];
                }                
            }    
        }
        

        /*************************************************************************************************************************************************************/

        if(isset($enfoqueMercado['ConstruccionesEnRenta'])){
            $infoReimpresion['Renta_Estimada'] = array();
            $construccionesEnRenta = $enfoqueMercado['ConstruccionesEnRenta'];
            $investigacionProductosComparables = $construccionesEnRenta['InvestigacionProductosComparables']; 
            $control = 0;
            if(isset($investigacionProductosComparables['@attributes'])){
                $infoReimpresion['Renta_Estimada'][$control]['Ubicacion'] = $investigacionProductosComparables['Calle'].". ".$investigacionProductosComparables['Colonia'].". ".$investigacionProductosComparables['CodigoPostal'];
                $infoReimpresion['Renta_Estimada'][$control]['Superficie_m2'] = $investigacionProductosComparables['SuperficieVendiblePorUnidad'];
                $infoReimpresion['Renta_Estimada'][$control]['Renta_Mensual'] = $investigacionProductosComparables['PrecioSolicitado'];
                if(trim($investigacionProductosComparables['PrecioSolicitado']) == 0 && trim($investigacionProductosComparables['SuperficieVendiblePorUnidad']) == 0){
                    $infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = 0;    
                }else{
                    $infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = $investigacionProductosComparables['PrecioSolicitado'] / $investigacionProductosComparables['SuperficieVendiblePorUnidad'];    
                }
                //$infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = $investigacionProductosComparables['PrecioSolicitado'] / $investigacionProductosComparables['SuperficieVendiblePorUnidad'];
            }else{
                if(isset($investigacionProductosComparables[0])){                    
                    foreach($investigacionProductosComparables as $productoComparable){
                        $infoReimpresion['Renta_Estimada'][$control]['Ubicacion'] = $productoComparable['Calle'].". ".$productoComparable['Colonia'].". ".$productoComparable['CodigoPostal'];
                        $infoReimpresion['Renta_Estimada'][$control]['Superficie_m2'] = $productoComparable['SuperficieVendiblePorUnidad'];
                        $infoReimpresion['Renta_Estimada'][$control]['Renta_Mensual'] = $productoComparable['PrecioSolicitado'];
                        if(trim($productoComparable['PrecioSolicitado']) === 0 && trim($productoComparable['SuperficieVendiblePorUnidad']) === 0){
                            $infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = 0;    
                        }else{
                            $infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = $productoComparable['SuperficieVendiblePorUnidad'] == 0 ? $productoComparable['PrecioSolicitado'] : $productoComparable['PrecioSolicitado'] / $productoComparable['SuperficieVendiblePorUnidad'];    
                        }
                        

                        $control = $control + 1;
                    }
                }
            }
            
        }
        
        if(isset($elementoPrincipal['EnfoqueDeIngresos'])){
            $infoReimpresion['Analisis_Deducciones'] = array();
            $infoReimpresion['Analisis_Deducciones']['Totales'] = array();
            $enfoqueIngresos = $elementoPrincipal['EnfoqueDeIngresos'];
            $deducciones = $enfoqueIngresos['Deducciones'];
            
            $infoReimpresion['Analisis_Deducciones']['Vacios'] = $deducciones['Vacios'];
            $infoReimpresion['Analisis_Deducciones']['Impuesto_Predial'] = $deducciones['ImpuestoPredial'];
            $infoReimpresion['Analisis_Deducciones']['Servicio_Agua'] = $deducciones['ServicioDeAgua'];
            $infoReimpresion['Analisis_Deducciones']['Conserv_Mant'] = $deducciones['ConservacionYMantenimiento'];
            $infoReimpresion['Analisis_Deducciones']['Administracion'] = $deducciones['Administracion'];
            $infoReimpresion['Analisis_Deducciones']['Energia_Electrica'] = $deducciones['ServicioEnergiaElectrica'];
            $infoReimpresion['Analisis_Deducciones']['Seguros'] = $deducciones['Seguros'];
            $infoReimpresion['Analisis_Deducciones']['Otros'] = $deducciones['Otros'];
            $infoReimpresion['Analisis_Deducciones']['Depreciacion_Fiscal'] = $deducciones['DepreciacionFiscal'];
            $infoReimpresion['Analisis_Deducciones']['Deducc_Fiscales'] = $deducciones['DeduccionesFiscales'];
            $infoReimpresion['Analisis_Deducciones']['ISR'] = $deducciones['ImpuestoSobreLaRenta'];

            $infoReimpresion['Analisis_Deducciones']['Totales']['Suma'] = $deducciones['DeduccionesMensuales'];
            $infoReimpresion['Analisis_Deducciones']['Totales']['Deducciones_Mensuales'] = $deducciones['DeduccionesMensuales'];
            $infoReimpresion['Analisis_Deducciones']['Totales']['Producto_Liquido_Mensual'] = $enfoqueIngresos['ProductoLiquidoAnual'] / 12;
            $infoReimpresion['Analisis_Deducciones']['Totales']['Producto_Liquido_Anual'] = $enfoqueIngresos['ProductoLiquidoAnual'];
            $infoReimpresion['Analisis_Deducciones']['Totales']['Tasa_Capitalizacion_Aplicable'] = $enfoqueIngresos['TasaDeCapitalizacionAplicable'] <= 1 ? $enfoqueIngresos['TasaDeCapitalizacionAplicable'] * 100 : $enfoqueIngresos['TasaDeCapitalizacionAplicable'];

            $infoReimpresion['Resultado_Aplicacion_Enfoque_Ingresos'] = $enfoqueIngresos['ImporteEnfoqueDeIngresos'];
        }
        
        if(isset($elementoPrincipal['EnfoqueDeIngresos']['ImporteEnfoqueDeIngresos'])){
            $infoReimpresion['Valor_Capitalizacion_Rentas'] = $elementoPrincipal['EnfoqueDeIngresos']['ImporteEnfoqueDeIngresos']; 
        }

        if(isset($elementoPrincipal['EnfoqueDeMercado'])){
            $infoReimpresion['Valor_Mercado_Construcciones'] = $enfoqueMercado['ValorDeMercadoDelInmueble'];    
        }
        
        if(isset($elementoPrincipal['ConsideracionesPreviasALaConclusion'])){
            $consideraciones = $elementoPrincipal['ConsideracionesPreviasALaConclusion'];
            if(isset($consideraciones['ConsideracionesPreviasALaConclusion']) && !is_array($consideraciones['ConsideracionesPreviasALaConclusion'])){
                $infoReimpresion['Consideraciones'] = $consideraciones['ConsideracionesPreviasALaConclusion'];
            }  
            
        }

        $conclusionAvaluo = $elementoPrincipal['ConclusionDelAvaluo'];
        if(isset($conclusionAvaluo['ValorComercialDelInmueble'])){
            $infoReimpresion['Consideramos_Que_Valor_Comercial_Corresponde'] = $conclusionAvaluo['ValorComercialDelInmueble'];
        }
        
        if(isset($conclusionAvaluo['ValorCatastralDelInmueble'])){
            $infoReimpresion['Consideramos_Que_Valor_Catastral_Corresponde'] = $conclusionAvaluo['ValorCatastralDelInmueble'];
        } 

        if(isset($elementoPrincipal['ValorReferido'])){            
            $valorReferido = $elementoPrincipal['ValorReferido'];
            if(isset($valorReferido['ValorReferido'])){
                $infoReimpresion['Valor_Referido'] = array();
                $infoReimpresion['Valor_Referido']['Valor_Referido'] = $valorReferido['ValorReferido'];
                $infoReimpresion['Valor_Referido']['Fecha'] = $valorReferido['FechaDeValorReferido'];
                $infoReimpresion['Valor_Referido']['Factor'] = $valorReferido['FactorDeConversion'];
            }            
        }

        if(isset($identificacion['ClaveSociedad']) && !is_array($identificacion['ClaveSociedad'])){
            $infoReimpresion['Registro_TDF'] = $identificacion['ClaveSociedad']." ".$this->modelDocumentos->get_nombre_sociedad($identificacion['ClaveSociedad']);
        }

        if(isset($identificacion['ClaveValuador']) && !is_array($identificacion['ClaveValuador'])){
            $infoReimpresion['Perito_Valuador'] = $identificacion['ClaveValuador']." ".$this->modelDocumentos->get_nombre_perito($identificacion['ClaveValuador']);
        }  

        $anexoFotogrfico = $elementoPrincipal['AnexoFotografico'];
        $sujeto = $anexoFotogrfico['Sujeto'];
        $fotosInmuebleAvaluo = $sujeto['FotosInmuebleAvaluo'];
        $cuentaCatastral = $sujeto['CuentaCatastral'];

        if(isset($anexoFotogrfico['ComparableVentas'])){
            $fotosVenta = $anexoFotogrfico['ComparableVentas'];
        }
        if(isset($anexoFotogrfico['ComparableRentas'])){
            $fotosRenta = $anexoFotogrfico['ComparableRentas'];
        } 
        
        $infoReimpresion['Inmueble_Objeto_Avaluo'] = array();
        $cuentaAvaluo = $cuentaCatastral['Region']."-".$cuentaCatastral['Manzana']."-".$cuentaCatastral['Lote']."-".$cuentaCatastral['Localidad'];
            
        
        

        $control = 0;
        foreach($fotosInmuebleAvaluo as $fotoInmuebleAvaluo){
            $foto = $this->modelDocumentos->get_fichero_foto($fotoInmuebleAvaluo['Foto']);           
            //$infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
            $infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Foto'] = substr($foto,0,4) == '/9j/' ? $foto : base64_encode($foto);
            $infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Cuenta_Catastral'] = $cuentaAvaluo;
            $infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Interior_O_Exterior'] = $fotoInmuebleAvaluo['InteriorOExterior'];
            $control = $control + 1;
        }

        if(isset($fotosVenta)){

            $infoReimpresion['Inmueble_Venta'] = array();
            if(isset($fotosVenta[0])){
                $control = 0;
                foreach($fotosVenta as $fotoVenta){ //echo $fotoVenta['FotosInmuebleAvaluo']['Foto']."\n"; exit();
                    //print_r($fotoVenta); exit();
                    if(isset($fotoVenta['FotosInmuebleAvaluo'][0])){
                        foreach($fotoVenta['FotosInmuebleAvaluo'] as $fVenta){
                            $foto = $this->modelDocumentos->get_fichero_foto($fVenta['Foto']);
                            //$infoReimpresion['Inmueble_Venta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                            $infoReimpresion['Inmueble_Venta'][$control]['Foto'] = substr($foto,0,4) == '/9j/' ? $foto : base64_encode($foto);
                            if(isset($fVenta['CuentaCatastral'])){
                                $infoReimpresion['Inmueble_Venta'][$control]['Cuenta_Catastral'] = $fVenta['CuentaCatastral']['Region']."-".$fVenta['CuentaCatastral']['Manzana']."-".$fVenta['CuentaCatastral']['Lote']."-".$fVenta['CuentaCatastral']['Localidad'];
                            }else{
                                $infoReimpresion['Inmueble_Venta'][$control]['Cuenta_Catastral'] = '';
                            }    
                            $infoReimpresion['Inmueble_Venta'][$control]['Interior_O_Exterior'] = $fVenta['InteriorOExterior'];
                            $control = $control + 1;
                        }
                    }else{
                        $foto = $this->modelDocumentos->get_fichero_foto($fotoVenta['FotosInmuebleAvaluo']['Foto']);
                        //$infoReimpresion['Inmueble_Venta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                        $infoReimpresion['Inmueble_Venta'][$control]['Foto'] = substr($foto,0,4) == '/9j/' ? $foto : base64_encode($foto);
                        if(isset($fotoVenta['CuentaCatastral'])){
                            $infoReimpresion['Inmueble_Venta'][$control]['Cuenta_Catastral'] = $fotoVenta['CuentaCatastral']['Region']."-".$fotoVenta['CuentaCatastral']['Manzana']."-".$fotoVenta['CuentaCatastral']['Lote']."-".$fotoVenta['CuentaCatastral']['Localidad'];
                        }else{
                            $infoReimpresion['Inmueble_Venta'][$control]['Cuenta_Catastral'] = '';
                        }                        
                        $infoReimpresion['Inmueble_Venta'][$control]['Interior_O_Exterior'] = $fotoVenta['FotosInmuebleAvaluo']['InteriorOExterior'];
                        $control = $control + 1;
                    }
                    
                }
            }else{
                $foto = $this->modelDocumentos->get_fichero_foto($fotosVenta['FotosInmuebleAvaluo']['Foto']);
                //$infoReimpresion['Inmueble_Venta'][0]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                $infoReimpresion['Inmueble_Venta'][0]['Foto'] = substr($foto,0,4) == '/9j/' ? $foto : base64_encode($foto);
                if(isset($fotosVenta['CuentaCatastral'])){
                    $infoReimpresion['Inmueble_Venta'][0]['Cuenta_Catastral'] = $fotosVenta['CuentaCatastral']['Region']."-".$fotosVenta['CuentaCatastral']['Manzana']."-".$fotosVenta['CuentaCatastral']['Lote']."-".$fotosVenta['CuentaCatastral']['Localidad'];
                }else{
                    $infoReimpresion['Inmueble_Venta'][0]['Cuenta_Catastral'] = '';
                }                
                $infoReimpresion['Inmueble_Venta'][0]['Interior_O_Exterior'] = $fotosVenta['FotosInmuebleAvaluo']['InteriorOExterior'];                
            }
            
        }
        
            if(isset($fotosRenta)){

                $infoReimpresion['Inmueble_Renta'] = array();

                if(isset($fotosRenta[0])){
                    $control = 0;
                    foreach($fotosRenta as $fotoRenta){ //echo $fotoRenta['FotosInmuebleAvaluo']['Foto']."\n";
                        if(isset($fotoRenta['FotosInmuebleAvaluo'][0])){
                            foreach($fotoRenta['FotosInmuebleAvaluo'] as $fRenta){
                                $foto = $this->modelDocumentos->get_fichero_foto($fRenta['Foto']);
                                //$infoReimpresion['Inmueble_Renta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                                $infoReimpresion['Inmueble_Renta'][$control]['Foto'] = substr($foto,0,4) == '/9j/' ? $foto : base64_encode($foto);
                                if(isset($fRenta['CuentaCatastral'])){
                                    $infoReimpresion['Inmueble_Renta'][$control]['Cuenta_Catastral'] = $fRenta['CuentaCatastral']['Region']."-".$fRenta['CuentaCatastral']['Manzana']."-".$fRenta['CuentaCatastral']['Lote']."-".$fRenta['CuentaCatastral']['Localidad'];
                                }else{
                                    $infoReimpresion['Inmueble_Renta'][$control]['Cuenta_Catastral'] = '';
                                }
                                
                                $infoReimpresion['Inmueble_Renta'][$control]['Interior_O_Exterior'] = $fRenta['InteriorOExterior'];
                                $control = $control + 1;
                            }
                        }else{
                            $foto = $this->modelDocumentos->get_fichero_foto($fotoRenta['FotosInmuebleAvaluo']['Foto']);
                            //$infoReimpresion['Inmueble_Renta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                            $infoReimpresion['Inmueble_Renta'][$control]['Foto'] = substr($foto,0,4) == '/9j/' ? $foto : base64_encode($foto);
                            if(isset($fotoRenta['CuentaCatastral'])){
                                $infoReimpresion['Inmueble_Renta'][$control]['Cuenta_Catastral'] = $fotoRenta['CuentaCatastral']['Region']."-".$fotoRenta['CuentaCatastral']['Manzana']."-".$fotoRenta['CuentaCatastral']['Lote']."-".$fotoRenta['CuentaCatastral']['Localidad'];
                            }else{
                                $infoReimpresion['Inmueble_Renta'][$control]['Cuenta_Catastral'] = '';
                            }
                            
                            $infoReimpresion['Inmueble_Renta'][$control]['Interior_O_Exterior'] = $fotoRenta['FotosInmuebleAvaluo']['InteriorOExterior'];
                            $control = $control + 1;
                        }
                        
                    }
                }else{
                    $foto = $this->modelDocumentos->get_fichero_foto($fotosRenta['FotosInmuebleAvaluo']['Foto']);
                    //$infoReimpresion['Inmueble_Renta'][0]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                    $infoReimpresion['Inmueble_Renta'][0]['Foto'] = substr($foto,0,4) == '/9j/' ? $foto : base64_encode($foto);
                    if(isset($fotosRenta['CuentaCatastral'])){
                        $infoReimpresion['Inmueble_Renta'][0]['Cuenta_Catastral'] = $fotosRenta['CuentaCatastral']['Region']."-".$fotosRenta['CuentaCatastral']['Manzana']."-".$fotosRenta['CuentaCatastral']['Lote']."-".$fotosRenta['CuentaCatastral']['Localidad'];
                    }else{
                        $infoReimpresion['Inmueble_Renta'][0]['Cuenta_Catastral'] = '';
                    }                    
                    $infoReimpresion['Inmueble_Renta'][0]['Interior_O_Exterior'] = $fotosRenta['FotosInmuebleAvaluo']['InteriorOExterior'];   
                }
                
            }
        }  catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error al obtener Información del avalúo'], 500);
        }//exit();      

        return $infoReimpresion;    
        
    }

    public function obtenXML($idAvaluo){

        $this->modelFis = new Fis();
        $this->modelDocumentos = new Documentos();
        //echo "EL QUE LLEGA "."SELECT NOMBRE, BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idAvaluo"; exit();
        $infoArchivo = DB::select("SELECT NOMBRE, BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idAvaluo AND NOMBRE LIKE 'Avaluo_%'");
        //$arrInfoArchivo = convierte_a_arreglo($infoArchivo);
        $rutaArchivos = getcwd();
        $nombreArchivo = $infoArchivo[0]->nombre;
        $archivoComprimido = $infoArchivo[0]->binariodatos;
        $myfile = fopen($rutaArchivos."/".$nombreArchivo, "a+");
        fwrite($myfile,$archivoComprimido);
        fclose($myfile);
        $comandoNombre = "7z l ".$rutaArchivos."/".$nombreArchivo;
        $datosNombre = shell_exec($comandoNombre);
        $comandoDescomprimir = "7z e ".$rutaArchivos."/".$nombreArchivo;   
        shell_exec($comandoDescomprimir);
        $comandoRm = "rm ".$rutaArchivos."/".$nombreArchivo;
        shell_exec($comandoRm);
        if(file_exists($rutaArchivos."/default") === TRUE){    
            $myfile = fopen($rutaArchivos."/default", "r");
            $contenidoArchivo = fread($myfile, filesize($rutaArchivos."/default"));
            fclose($myfile);  

        }else{

            $comandols = "ls php*";
            $archphp = shell_exec($comandols);
            if(substr(trim($archphp),0,3) == 'php'){
                $comandoMv = "mv ".$rutaArchivos."/"."php* ".$rutaArchivos."/"."default";
                system($comandoMv);
            }

            $myfile = fopen($rutaArchivos."/default", "r");
            $contenidoArchivo = fread($myfile, filesize($rutaArchivos."/default"));
            fclose($myfile);
        }

        $xml = simplexml_load_string($contenidoArchivo,'SimpleXMLElement', LIBXML_NOCDATA);
        $comandoRmDefault = "rm ".$rutaArchivos."/default";
        shell_exec($comandoRmDefault);
        
        return $contenidoArchivo;
    }

    public function infoAvaluoNuevo($idAvaluo){

        try{
        $this->modelFis = new Fis();
        $this->modelDocumentos = new Documentos();

        //echo "EL QUE LLEGA "."SELECT NOMBRE, BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idAvaluo"; exit();
        $infoArchivo = DB::select("SELECT NOMBRE, BINARIODATOS FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idAvaluo AND NOMBRE LIKE 'Avaluo_%'");
        //$arrInfoArchivo = convierte_a_arreglo($infoArchivo);
        $rutaArchivos = getcwd();
        $nombreArchivo = $infoArchivo[0]->nombre;
        $archivoComprimido = $infoArchivo[0]->binariodatos;
        $myfile = fopen($rutaArchivos."/".$nombreArchivo, "a+");
        fwrite($myfile,$archivoComprimido);
        fclose($myfile);
        $comandoNombre = "7z l ".$rutaArchivos."/".$nombreArchivo;
        $datosNombre = shell_exec($comandoNombre);
        $comandoDescomprimir = "7z e ".$rutaArchivos."/".$nombreArchivo;   
        shell_exec($comandoDescomprimir);
        $comandoRm = "rm ".$rutaArchivos."/".$nombreArchivo;
        shell_exec($comandoRm);
        if(file_exists($rutaArchivos."/default") === TRUE){    
            $myfile = fopen($rutaArchivos."/default", "r");
            $contenidoArchivo = fread($myfile, filesize($rutaArchivos."/default"));
            fclose($myfile);  

        }else{

            $comandols = "ls php*";
            $archphp = shell_exec($comandols);
            if(substr(trim($archphp),0,3) == 'php'){
                $comandoMv = "mv ".$rutaArchivos."/"."php* ".$rutaArchivos."/"."default";
                system($comandoMv);
            }

            $myfile = fopen($rutaArchivos."/default", "r");
            $contenidoArchivo = fread($myfile, filesize($rutaArchivos."/default"));
            fclose($myfile);
        }
                
        $xml = simplexml_load_string($contenidoArchivo,'SimpleXMLElement', LIBXML_NOCDATA);
        $comandoRmDefault = "rm ".$rutaArchivos."/default";
        shell_exec($comandoRmDefault);
        $arrXML = convierte_a_arreglo($xml); //echo $contenidoArchivo; exit();

        $infoFexava = DB::select("SELECT * FROM FEXAVA_AVALUO WHERE IDAVALUO = $idAvaluo");
        $arrInfoFexava = array_map("convierte_a_arreglo",$infoFexava);
        $arrFexava = $arrInfoFexava[0];

        $infoReimpresion = array();

        if(isset($arrXML['Comercial'])){
            $elementoPrincipal = $arrXML['Comercial'];
            $tipoDeAvaluo =  "Comercial";
        }

        if(isset($arrXML['Catastral'])){
            $elementoPrincipal = $arrXML['Catastral'];
            $tipoDeAvaluo =  "Catastral";
        }

        $identificacion = $elementoPrincipal['Identificacion'];

        $infoReimpresion['Encabezado'] = array();

        $infoReimpresion['Encabezado']['Fecha'] = $identificacion['FechaAvaluo'];
        $infoReimpresion['Encabezado']['Avaluo_No'] = $identificacion['NumeroDeAvaluo'];
        $infoReimpresion['Encabezado']['No_Unico'] = $arrFexava['numerounico'];
        $infoReimpresion['Encabezado']['Registro_TDF'] = $identificacion['ClaveValuador'];

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Sociedad_Participa'] = array();

        if(isset($identificacion['ClaveSociedad']) && !is_array($identificacion['ClaveSociedad'])){
            $infoReimpresion['Sociedad_Participa']['Sociedad_Que_Practica_Avaluo'] = $identificacion['ClaveSociedad'];
        }

        $antecedentes = $elementoPrincipal['Antecedentes'];
        $solicitante = $antecedentes['Solicitante']; 
        $arrSolicitante = array_map("convierte_a_arreglo",$solicitante); //print_r($arrSolicitante); exit();    

        /*$infoSolicitante = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idAvaluo AND CODTIPOFUNCION = 'S'");
        $arrinfoSolicitante = array_map("convierte_a_arreglo",$infoSolicitante);
        $arrSolicitante = $arrinfoSolicitante[0];*/

        $infoReimpresion['Sociedad_Participa']['Valuador'] = $identificacion['ClaveValuador'];
        $infoReimpresion['Sociedad_Participa']['Fecha_del_Avaluo'] = $identificacion['FechaAvaluo'];
        $infoReimpresion['Sociedad_Participa']['Solicitante'] = array();
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Tipo_persona'] = isset($arrSolicitante['A.Paterno']) && !is_array($arrSolicitante['A.Paterno']) ? "Física" : "Moral";
        if(isset($arrSolicitante['A.Paterno']) && !is_array($arrSolicitante['A.Paterno'])){
            $infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre'] = $arrSolicitante['Nombre']." ".$arrSolicitante['A.Paterno'];
            if(isset($arrPropietario['A.Materno']) && !is_array($arrPropietario['A.Materno'])){
                $infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre'] = $infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre']." ".$arrSolicitante['A.Materno'];
            }
            //$infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre'] = $arrSolicitante['Nombre']." ".$arrSolicitante['A.Paterno']." ".isset($arrSolicitante['A.Materno']) && !is_array($arrSolicitante['A.Materno']) ? $arrSolicitante['A.Materno'] : '';
        }else{
            $infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre'] = $arrSolicitante['Nombre'];
        }        
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Calle'] = $arrSolicitante['Calle'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['No_Exterior'] = $arrSolicitante['NumeroExterior'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['No_Interior'] = $arrSolicitante['NumeroInterior'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Colonia'] = $arrSolicitante['Colonia'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['CP'] = $arrSolicitante['CodigoPostal'];
        if(isset($arrSolicitante['Alcaldia'])){
            if(isset($arrSolicitante['Alcaldia']['ClaveAlcaldia'])){
                if($arrSolicitante['Alcaldia']['ClaveAlcaldia'] == '018'){
                    $infoReimpresion['Sociedad_Participa']['Solicitante']['Delegacion'] = $arrSolicitante['Alcaldia']['Otros'];    
                }else{
                    $infoReimpresion['Sociedad_Participa']['Solicitante']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrSolicitante['Alcaldia']['ClaveAlcaldia']);
                }    
            }else{
                $infoReimpresion['Sociedad_Participa']['Solicitante']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrSolicitante['Alcaldia']);
            }
            
        }
        if(isset($arrSolicitante['Delegacion'])){
            $infoReimpresion['Sociedad_Participa']['Solicitante']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrSolicitante['Delegacion']);
        }

        $infoReimpresion['Sociedad_Participa']['inmuebleQueSeValua'] = $arrFexava['region']."-".$arrFexava['manzana']."-".$arrFexava['lote']."-".$arrFexava['unidadprivativa']." ".$arrFexava['digitoverificador'];
        $infoReimpresion['Sociedad_Participa']['regimenDePropiedad'] = $this->modelDocumentos->get_regimen_propiedad($arrFexava['codregimenpropiedad']);

        /* $infoPropietario = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idAvaluo AND CODTIPOFUNCION = 'P'");
        $arrinfoPropietario = array_map("convierte_a_arreglo",$infoPropietario);
        $arrPropietario = $arrinfoPropietario[0]; */

        $propietario = $antecedentes['Propietario']; 
        $arrPropietario = array_map("convierte_a_arreglo",$propietario); //print_r($arrPropietario); exit();

        $infoReimpresion['Sociedad_Participa']['Propietario'] = array();
        $infoReimpresion['Sociedad_Participa']['Propietario']['Tipo_persona'] = isset($arrPropietario['A.Paterno']) && !is_array($arrPropietario['A.Paterno']) ? "Física" : "Moral";
        
        if(isset($arrPropietario['A.Paterno']) && !is_array($arrPropietario['A.Paterno'])){
            $infoReimpresion['Sociedad_Participa']['Propietario']['Nombre'] = $arrPropietario['Nombre']." ".$arrPropietario['A.Paterno'];
            if(isset($arrPropietario['A.Materno']) && !is_array($arrPropietario['A.Materno'])){
                $infoReimpresion['Sociedad_Participa']['Propietario']['Nombre'] = $infoReimpresion['Sociedad_Participa']['Propietario']['Nombre']." ".$arrPropietario['A.Materno'];
            }
        }else{
            $infoReimpresion['Sociedad_Participa']['Propietario']['Nombre'] = $arrPropietario['Nombre'];
        }
              
        $infoReimpresion['Sociedad_Participa']['Propietario']['Calle'] = $arrPropietario['Calle'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['No_Exterior'] = $arrPropietario['NumeroExterior'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['No_Interior'] = $arrPropietario['NumeroInterior'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['Colonia'] = $arrPropietario['Colonia'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['CP'] = $arrPropietario['CodigoPostal'];
        if(isset($arrPropietario['Alcaldia'])){
            if(isset($arrPropietario['Alcaldia']['ClaveAlcaldia'])){
                if($arrPropietario['Alcaldia']['ClaveAlcaldia'] == '018'){
                    $infoReimpresion['Sociedad_Participa']['Propietario']['Delegacion'] = $arrPropietario['Alcaldia']['Otros'];    
                }else{
                    $infoReimpresion['Sociedad_Participa']['Propietario']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrPropietario['Alcaldia']['ClaveAlcaldia']);
                }    
            }else{
                $infoReimpresion['Sociedad_Participa']['Propietario']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrPropietario['Alcaldia']);    
            }
        }else{
            $infoReimpresion['Sociedad_Participa']['Propietario']['Delegacion'] = $this->modelDocumentos->ObtenerNombreDelegacionPorClave($arrPropietario['Delegacion']);        
        }    

        $infoReimpresion['Sociedad_Participa']['Objeto_Avaluo'] = $arrFexava['objeto'];
        $infoReimpresion['Sociedad_Participa']['Proposito_Avaluo'] = $arrFexava['proposito'];
        
        if(isset($elementoPrincipal['Antecedentes']['TipoDeInmueble']) && !is_array($elementoPrincipal['Antecedentes']['TipoDeInmueble'])) {
            //$tipoDeInmueble = $elementoPrincipal['Antecedentes']['TipoDeInmueble'];
            $infoReimpresion['Sociedad_Participa']['tipoDeInmueble'] = $this->cat_tipo_inmueble($elementoPrincipal['Antecedentes']['TipoDeInmueble']);
        }else{
            $infoReimpresion['Sociedad_Participa']['tipoDeInmueble'] = '';
        }
        //print_r($infoReimpresion); exit();
        /************************************************************************************************************************************************************************/

        $infoReimpresion['Ubicacion_Inmueble'] = array();
        $ubicacionInmueble = $elementoPrincipal['Antecedentes']['InmuebleQueSeValua'];        

        $infoReimpresion['Ubicacion_Inmueble']['Calle'] = $ubicacionInmueble['Calle'];
        $infoReimpresion['Ubicacion_Inmueble']['No_Exterior'] = $ubicacionInmueble['NumeroExterior'];
        $infoReimpresion['Ubicacion_Inmueble']['No_Interior'] = $ubicacionInmueble['NumeroInterior'];
        $infoReimpresion['Ubicacion_Inmueble']['Colonia'] = $ubicacionInmueble['Colonia'];
        $infoReimpresion['Ubicacion_Inmueble']['CP'] = $ubicacionInmueble['CodigoPostal'];
        $infoReimpresion['Ubicacion_Inmueble']['Delegacion'] = isset($ubicacionInmueble['Delegacion']) ? $this->modelDocumentos->ObtenerNombreDelegacionPorClave($ubicacionInmueble['Delegacion']) : $this->modelDocumentos->ObtenerNombreDelegacionPorClave($ubicacionInmueble['Alcaldia']);
        $infoReimpresion['Ubicacion_Inmueble']['Edificio'] = "-";
        $infoReimpresion['Ubicacion_Inmueble']['Lote'] = isset($ubicacionInmueble['Lote']) && !is_array($ubicacionInmueble['Lote']) ? $ubicacionInmueble['Lote'] : 0;
        $infoReimpresion['Ubicacion_Inmueble']['Cuenta_agua'] = isset($ubicacionInmueble['CuentaDeAgua']) && !is_array($ubicacionInmueble['CuentaDeAgua']) ? $ubicacionInmueble['CuentaDeAgua'] : '';

        $infoReimpresion['Clasificacion_de_la_zona'] = $this->modelDocumentos->get_clasificacion_zona($arrFexava['cucodclasificacionzona']);
        $infoReimpresion['Indice_Saturacion_Zona'] = $arrFexava['cuindicesaturacionzona'] <= 1 ? $arrFexava['cuindicesaturacionzona'] * 100 : $arrFexava['cuindicesaturacionzona'];
        
        $caracterisiticasUrbanas = $elementoPrincipal['CaracteristicasUrbanas'];
        $infoReimpresion['Tipo_Construccion_Dominante'] = $this->modelFis->getClase($caracterisiticasUrbanas['ClaseGeneralDeInmueblesDeLaZona']);
        $infoReimpresion['Densidad_Poblacion'] = $this->modelDocumentos->get_densidad_poblacion($arrFexava['cucoddensidadpoblacion']);
        $infoReimpresion['Nivel_Socioeconomico_Zona'] = $this->modelDocumentos->get_nivel_socioeconomico_zona($arrFexava['cucodnivelsocioeconomico']);
        if(isset($caracterisiticasUrbanas['ContaminacionAmbientalEnLaZona']) && !is_array($caracterisiticasUrbanas['ContaminacionAmbientalEnLaZona'])){
            $infoReimpresion['Contaminacion_Medio_Ambiente'] = $caracterisiticasUrbanas['ContaminacionAmbientalEnLaZona'];
        }
        
        if(isset($caracterisiticasUrbanas['ClaseGeneralDeInmueblesDeLaZona']) && !is_array($caracterisiticasUrbanas['ClaseGeneralDeInmueblesDeLaZona'])){
            $infoReimpresion['Clase_General_De_Inmuebles_Zona'] = $this->modelFis->getClase($caracterisiticasUrbanas['ClaseGeneralDeInmueblesDeLaZona']);
        }
        if(isset($caracterisiticasUrbanas['ClaseGeneralDeInmueblesDeLaZona']) && !is_array($caracterisiticasUrbanas['ClaseGeneralDeInmueblesDeLaZona'])){
            $infoReimpresion['Uso_Suelo'] = $arrFexava['cuuso'];
        }
        if(isset($caracterisiticasUrbanas['cuarealibreobligatorio']) && !is_array($caracterisiticasUrbanas['cuarealibreobligatorio'])){
            $infoReimpresion['Area_Libre_Obligatoria'] = $arrFexava['cuarealibreobligatorio'];
        }
        if(isset($caracterisiticasUrbanas['ViasDeAccesoEImportancia']) && !is_array($caracterisiticasUrbanas['ViasDeAccesoEImportancia'])){
            $infoReimpresion['Vias_Acceso_E_Importancia'] = $caracterisiticasUrbanas['ViasDeAccesoEImportancia'];
        }
        
        /************************************************************************************************************************************************************************/

        $infoReimpresion['Servicios_Publicos_Equipamiento'] = array();

        $infoReimpresion['Servicios_Publicos_Equipamiento']['Red_Agua_Potable'] = $this->modelDocumentos->get_red_agua_potable($arrFexava['cucodaguapotable']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Red_Aguas_Residuales'] = $this->modelDocumentos->get_red_agua_potable($arrFexava['cucodaguapotableresidual']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Red_Drenaje_Aguas_Pluviales_Calle'] = $this->modelDocumentos->get_drenaje_pluvial_calle_zona($arrFexava['cucoddrenajepluvialcalle']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Red_Drenaje_Aguas_Pluviales_Zona'] = $this->modelDocumentos->get_drenaje_pluvial_calle_zona($arrFexava['cucoddrenajepluvialzona']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Sistema_Mixto'] = $this->modelDocumentos->get_drenaje_mixto($arrFexava['cucoddrenajeinmueble']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Suministro_Electrico'] = $this->modelDocumentos->get_suministro_electrico($arrFexava['cucodsuministroelectrico']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Acometida_Inmueble'] = $this->modelDocumentos-> get_acometida_inmueble($arrFexava['cucodacometidainmueble']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Alumbrado_Publico'] = $this->modelDocumentos->get_alumbrado_publico($arrFexava['cucodalumbradopublico']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Vialidades'] = $this->modelDocumentos->get_vialidades($arrFexava['cucodvialidades']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Banquetas'] = $this->modelDocumentos->get_banquetas($arrFexava['cucodbanquetas']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Guarniciones'] = $this->modelDocumentos->get_guarniciones($arrFexava['cucodguarniciones']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Nivel_Infraestructura_Zona'] = $arrFexava['cuporcentajeinfraestructura'] <= 1 ? $arrFexava['cuporcentajeinfraestructura'] * 100 : $arrFexava['cuporcentajeinfraestructura'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Gas_Natutral'] = $this->modelDocumentos->get_gas_natural($arrFexava['cucodgasnatural']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Telefonos_Suministro'] = $this->modelDocumentos->get_suministro_tel($arrFexava['cucodsuministrotelefonica']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Senalizacion_Vias'] = $this->modelDocumentos->get_senal_vias($arrFexava['cucodsenalizacionvias']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Acometida_Inmueble_Tel'] = $this->modelDocumentos->get_acometida_inmueble_tel($arrFexava['cucodacometidainmuebletel']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Distancia_Transporte_Urbano'] =  $arrFexava['cudistanciatransporteurbano'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Frecuencia_Transporte_Urbano'] = $arrFexava['cufrecuenciatransporteurbano'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Distancia_Transporte_Suburbano'] = $arrFexava['cudistanciatransportesuburb'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Frecuencia_Transporte_Suburbano'] = $arrFexava['cufrecuenciatransportesuburb'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Vigilancia'] = $this->modelDocumentos->get_vigilancia_zona($arrFexava['cucodvigilanciazona']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Recoleccion_Basura'] = $this->modelDocumentos->get_recoleccion_basura($arrFexava['cucodrecoleccionbasura']);
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Templo'] = $arrFexava['cuexisteiglesia'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Mercados'] = $arrFexava['cuexistemercados'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Plazas_Publicas'] = $arrFexava['cuexisteplazaspublicos'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Parques_Jardines'] = $arrFexava['cuexisteparquesjardines'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Escuelas'] = $arrFexava['cuexisteescuelas'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Hospitales'] = $arrFexava['cuexistehospitales'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Bancos'] = $arrFexava['cuexistebancos'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Estacion_Transporte'] = $arrFexava['cuexisteestaciontransporte'] == 1 ? 'Si' : 'No';
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Nivel_Equipamiento_Urbano'] = $caracterisiticasUrbanas['ServiciosPublicosYEquipamientoUrbano']['NivelDeEquipamientoUrbano'];
        $infoReimpresion['Servicios_Publicos_Equipamiento']['Nomenclatura_Calles'] = $this->modelDocumentos->get_nomenclatura_calle($arrFexava['cucodnomenclaturacalle']);

        $terreno = $elementoPrincipal['Terreno'];

        $infoReimpresion['Calles_Transversales_Limitrofes'] = $terreno['CallesTransversalesLimitrofesYOrientacion'];        
        /************************************************************************************************************************************************************************/
        
        $infoReimpresion['Croquis_Localizacion'] = array(); //echo var_dump(base64_decode($this->modelDocumentos->get_fichero_documento($terreno['CroquisMicroLocalizacion']))); exit();
        $microlocalizacion = $this->modelDocumentos->get_fichero_documento($terreno['CroquisMicroLocalizacion']);
        $macrolocalizacion = $this->modelDocumentos->get_fichero_documento($terreno['CroquisMacroLocalizacion']);
        $infoReimpresion['Croquis_Localizacion']['Microlocalizacion'] = $microlocalizacion == base64_encode(base64_decode($microlocalizacion)) ? $microlocalizacion : base64_encode($microlocalizacion);
        $infoReimpresion['Croquis_Localizacion']['Macrolocalizacion'] = $macrolocalizacion == base64_encode(base64_decode($macrolocalizacion)) ? $macrolocalizacion : base64_encode($macrolocalizacion);
        
        /************************************************************************************************************************************************************************/

        $infoReimpresion['Medidas_Colindancias'] = array();
        $fuenteDeInformacion = $terreno['MedidasYColindancias']['FuenteDeInformacionLegal'];

        $infoReimpresion['Medidas_Colindancias']['Fuente'] = isset($fuenteDeInformacion['Escritura']) ? 'Escritura' : '';
        $infoReimpresion['Medidas_Colindancias']['Numero_Escritura'] = isset($fuenteDeInformacion['Escritura']['NumeroDeEscritura']) ? $fuenteDeInformacion['Escritura']['NumeroDeEscritura'] : '';
        $infoReimpresion['Medidas_Colindancias']['Numero_Notaria'] = isset($fuenteDeInformacion['Escritura']['NumeroNotaria']) ? $fuenteDeInformacion['Escritura']['NumeroNotaria'] : '';
        $infoReimpresion['Medidas_Colindancias']['Entidad_Federativa'] = isset($fuenteDeInformacion['Escritura']['DistritoJudicialNotario']) ? $fuenteDeInformacion['Escritura']['DistritoJudicialNotario'] : '';
        $infoReimpresion['Medidas_Colindancias']['Numero_Volumen'] = isset($fuenteDeInformacion['Escritura']['NumeroDeVolumen']) ? $fuenteDeInformacion['Escritura']['NumeroDeVolumen'] : '';
        $infoReimpresion['Medidas_Colindancias']['Nombre_Notario'] = isset($fuenteDeInformacion['Escritura']['NombreDelNotario']) ? $fuenteDeInformacion['Escritura']['NombreDelNotario'] : '';
        
        /************************************************************************************************************************************************************************/

        $colindancias = $terreno['MedidasYColindancias']['Colindancias'];
        $infoReimpresion['Colindancias'] = array();

        if(isset($colindancias['@attributes'])){
            unset($colindancias['@attributes']);
            $infoReimpresion['Colindancias'][] = $colindancias;
        }

        if(isset($colindancias[0])){
            foreach($colindancias as $colindancia){
                unset($colindancia['@attributes']);
                $infoReimpresion['Colindancias'][] = $colindancia;
            }
        }
        

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Superficie_Total_Segun'] = array();
        $infoReimpresion['Superficie_Total_Segun']['Totales'] = array();
        /*$infoSuperficie = DB::select("SELECT * FROM FEXAVA_SUPERFICIE WHERE IDAVALUO = $idAvaluo");
        $arrInfoSuperficie = array_map("convierte_a_arreglo",$infoSuperficie);
        $arrSuperficie = $arrInfoSuperficie[0];
        print_r($arrSuperficie); exit();*/
        $superficieDelTerreno = $terreno['SuperficieDelTerreno'];

        if(isset($superficieDelTerreno[0])){

            $control = 0;
            foreach($superficieDelTerreno as $supDelTerreno){

                $infoReimpresion['Superficie_Total_Segun'][$control]['Ident_Fraccion'] = $supDelTerreno['IdentificadorFraccionN1'];
                $infoReimpresion['Superficie_Total_Segun'][$control]['Sup_Fraccion'] = $supDelTerreno['SuperficieFraccionN1'];
                if(isset($supDelTerreno['Factor1'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor1'] = array();
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor1']['Titulo'] = $supDelTerreno['Factor1']['Siglas'];
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor1']['Valor'] = $supDelTerreno['Factor1']['Valor'];
                }
                
                if(isset($supDelTerreno['Factor2'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor2'] = array();
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor2']['Titulo'] = $supDelTerreno['Factor2']['Siglas'];
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor2']['Valor'] = $supDelTerreno['Factor2']['Valor'];
                }

                if(isset($supDelTerreno['Factor3'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor3'] = array();
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor3']['Titulo'] = $supDelTerreno['Factor3']['Siglas'];
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor3']['Valor'] = $supDelTerreno['Factor3']['Valor'];
                }

                if(isset($supDelTerreno['Factor4'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor4'] = array();
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor4']['Titulo'] = $supDelTerreno['Factor4']['Siglas'];
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor4']['Valor'] = $supDelTerreno['Factor4']['Valor'];
                }

                if(isset($supDelTerreno['Factor5'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor5'] = array();
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor5']['Titulo'] = $supDelTerreno['Factor5']['Siglas'];
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Factor5']['Valor'] = $supDelTerreno['Factor5']['Valor'];
                }

                if($tipoDeAvaluo == 'Catastral'){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Valor_Fraccion'] = $supDelTerreno['ValorDeLaFraccionN'];
                }
                
                $infoReimpresion['Superficie_Total_Segun'][$control]['Clave_Area_Valor'] = $supDelTerreno['ClaveDeAreaDeValor'];                

                if(isset($supDelTerreno['Fre'])){
                    $infoReimpresion['Superficie_Total_Segun'][$control]['Fre'] = $supDelTerreno['Fre'];
                }

                $control = $control + 1;
            }

        }else{

            $infoReimpresion['Superficie_Total_Segun']['Ident_Fraccion'] = $superficieDelTerreno['IdentificadorFraccionN1'];
            $infoReimpresion['Superficie_Total_Segun']['Sup_Fraccion'] = $superficieDelTerreno['SuperficieFraccionN1'];
            if(isset($superficieDelTerreno['Factor1'])){
                $infoReimpresion['Superficie_Total_Segun']['Factor1'] = array();
                $infoReimpresion['Superficie_Total_Segun']['Factor1']['Titulo'] = $superficieDelTerreno['Factor1']['Siglas'];
                $infoReimpresion['Superficie_Total_Segun']['Factor1']['Valor'] = $superficieDelTerreno['Factor1']['Valor'];
            }
            
            if(isset($superficieDelTerreno['Factor2'])){
                $infoReimpresion['Superficie_Total_Segun']['Factor2'] = array();
                $infoReimpresion['Superficie_Total_Segun']['Factor2']['Titulo'] = $superficieDelTerreno['Factor2']['Siglas'];
                $infoReimpresion['Superficie_Total_Segun']['Factor2']['Valor'] = $superficieDelTerreno['Factor2']['Valor'];
            }

            if(isset($superficieDelTerreno['Factor3'])){
                $infoReimpresion['Superficie_Total_Segun']['Factor3'] = array();
                $infoReimpresion['Superficie_Total_Segun']['Factor3']['Titulo'] = $superficieDelTerreno['Factor3']['Siglas'];
                $infoReimpresion['Superficie_Total_Segun']['Factor3']['Valor'] = $superficieDelTerreno['Factor3']['Valor'];
            }

            if(isset($superficieDelTerreno['Factor4'])){
                $infoReimpresion['Superficie_Total_Segun']['Factor4'] = array();
                $infoReimpresion['Superficie_Total_Segun']['Factor4']['Titulo'] = $superficieDelTerreno['Factor4']['Siglas'];
                $infoReimpresion['Superficie_Total_Segun']['Factor4']['Valor'] = $superficieDelTerreno['Factor4']['Valor'];
            }

            if(isset($superficieDelTerreno['Factor5'])){
                $infoReimpresion['Superficie_Total_Segun']['Factor5'] = array();
                $infoReimpresion['Superficie_Total_Segun']['Factor5']['Titulo'] = $superficieDelTerreno['Factor5']['Siglas'];
                $infoReimpresion['Superficie_Total_Segun']['Factor5']['Valor'] = $superficieDelTerreno['Factor5']['Valor'];
            }

            if($tipoDeAvaluo == 'Catastral'){
                $infoReimpresion['Superficie_Total_Segun']['Valor_Fraccion'] = $superficieDelTerreno['ValorDeLaFraccionN'];
            }
            
            $infoReimpresion['Superficie_Total_Segun']['Clave_Area_Valor'] = $superficieDelTerreno['ClaveDeAreaDeValor'];

            /*if(isset($superficieDelTerreno['Fot'])){
                $infoReimpresion['Superficie_Total_Segun']['Valor'] = $superficieDelTerreno['Fot']['Valor'];
                $infoReimpresion['Superficie_Total_Segun']['Descripcion'] = $superficieDelTerreno['Fot']['Descripcion'];
            }*/

            if(isset($superficieDelTerreno['Fre'])){
                $infoReimpresion['Superficie_Total_Segun']['Fre'] = $superficieDelTerreno['Fre'];
            }

        }           

        $infoReimpresion['Superficie_Total_Segun']['Totales']['Superficie_Total_Terreno'] =  $terreno['SuperficieTotalDelTerreno'];

        $infoReimpresion['Topografia_Configuracion'] = array();
        $infoReimpresion['Topografia_Configuracion']['Caracteristicas_Panoramicas'] = $arrFexava['tecaracteristicasparonamicas'];
        $infoReimpresion['Topografia_Configuracion']['Densidad_Habitacional'] = $this->modelDocumentos->get_densidad_habitacional($arrFexava['tecoddensidadhabitacional']);
        $infoReimpresion['Topografia_Configuracion']['Servidumbre_Restricciones'] = $arrFexava['teservidumbresorestricciones'];

        $infoReimpresion['Uso_Actual'] = $arrFexava['diusoactual'];

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Construcciones_Privativas'] = array();            
        
        $descripcionInmueble = $elementoPrincipal['DescripcionDelInmueble'];
        $tiposContruccion = $descripcionInmueble['TiposDeConstruccion'];

        if(isset($tiposContruccion['ConstruccionesPrivativas']['@attributes'])){
            $infoReimpresion['Construcciones_Privativas']['Tipo'] = 'P';
            $infoReimpresion['Construcciones_Privativas']['Descripcion'] = $tiposContruccion['ConstruccionesPrivativas']['Descripcion'];
            $infoReimpresion['Construcciones_Privativas']['Uso'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveUso'];
            $infoReimpresion['Construcciones_Privativas']['No_Niveles_Tipo'] = $tiposContruccion['ConstruccionesPrivativas']['NumeroDeNivelesDelTipo'];
            if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'])){
                $infoReimpresion['Construcciones_Privativas']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'];
            }
            if(isset($tiposContruccion['ConstruccionesPrivativas']['PuntajeDeClasificacion']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['PuntajeDeClasificacion'])){
                $infoReimpresion['Construcciones_Privativas']['Puntaje'] = $tiposContruccion['ConstruccionesPrivativas']['PuntajeDeClasificacion'];
            }
            if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveClase'])){
                $infoReimpresion['Construcciones_Privativas']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']);
            }
            if($tipoDeAvaluo ==  "Catastral"){
                if(isset($tiposContruccion['ConstruccionesPrivativas']['Edad']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['Edad'])){
                    $infoReimpresion['Construcciones_Privativas']['Edad'] = $tiposContruccion['ConstruccionesPrivativas']['Edad'];
                }                
            }
            if(isset($tiposContruccion['ConstruccionesPrivativas']['VidaUtilTotalDelTipo']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['VidaUtilTotalDelTipo'])){
                $infoReimpresion['Construcciones_Privativas']['Vida_Util_Total_Tipo'] = $tiposContruccion['ConstruccionesPrivativas']['VidaUtilTotalDelTipo'];
            }    
            if(isset($tiposContruccion['ConstruccionesPrivativas']['VidaMinimaRemanente']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['VidaMinimaRemanente'])){
                $infoReimpresion['Construcciones_Privativas']['Vida_Minima_Remanente'] = $tiposContruccion['ConstruccionesPrivativas']['VidaMinimaRemanente'];
            }
            
            //$infoReimpresion['Construcciones_Privativas']['Conservacion'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveConservacion'];
            if(isset($tiposContruccion['ConstruccionesPrivativas']['Superficie']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['Superficie'])){
                $infoReimpresion['Construcciones_Privativas']['Sup'] = $tiposContruccion['ConstruccionesPrivativas']['Superficie'];
            }
        }

        if(isset($tiposContruccion['ConstruccionesPrivativas'][0])){
            $control = 0;
            foreach($tiposContruccion['ConstruccionesPrivativas'] as $construccionPrivativa){
                $infoReimpresion['Construcciones_Privativas'][$control]['Tipo'] = 'P';
                $infoReimpresion['Construcciones_Privativas'][$control]['Descripcion'] = $construccionPrivativa['Descripcion'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Uso'] = $construccionPrivativa['ClaveUso'];
                $infoReimpresion['Construcciones_Privativas'][$control]['No_Niveles_Tipo'] = $construccionPrivativa['NumeroDeNivelesDelTipo'];
                if(isset($construccionPrivativa['ClaveRangoDeNiveles']) && !is_array($construccionPrivativa['ClaveRangoDeNiveles'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Clave_Rango_Niveles'] = $construccionPrivativa['ClaveRangoDeNiveles'];
                }
                if(isset($construccionPrivativa['PuntajeDeClasificacion']) && !is_array($construccionPrivativa['PuntajeDeClasificacion'])){
                $infoReimpresion['Construcciones_Privativas'][$control]['Puntaje'] = $construccionPrivativa['PuntajeDeClasificacion'];
                }
                if(isset($construccionPrivativa['ClaveClase']) && !is_array($construccionPrivativa['ClaveClase'])){
                $infoReimpresion['Construcciones_Privativas'][$control]['Clase'] = $this->modelFis->getClase($construccionPrivativa['ClaveClase']);
                }

                if($tipoDeAvaluo ==  "Catastral"){
                    if(isset($construccionPrivativa['Edad']) && !is_array($construccionPrivativa['Edad'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Edad'] = $construccionPrivativa['Edad'];
                    }
                }
                $infoReimpresion['Construcciones_Privativas'][$control]['Vida_Util_Total_Tipo'] = $construccionPrivativa['VidaUtilTotalDelTipo'];
                if(isset($construccionPrivativa['VidaMinimaRemanente']) && !is_array($construccionPrivativa['VidaMinimaRemanente'])){
                    $infoReimpresion['Construcciones_Privativas'][$control]['Vida_Minima_Remanente'] = $construccionPrivativa['VidaMinimaRemanente'];
                }                
                
                if(isset($construccionPrivativa['Superficie']) && !is_array($construccionPrivativa['Superficie'])){//$infoReimpresion['Construcciones_Privativas'][$control]['Conservacion'] = $construccionPrivativa['ClaveConservacion'];
                    $infoReimpresion['Construcciones_Privativas'][$control]['Sup'] = $construccionPrivativa['Superficie'];
                }
                $control = $control + 1;
            }
        }
        
        if(isset($tiposContruccion['ConstruccionesComunes']['@attributes'])){
            $infoReimpresion['Construcciones_Comunes']['Tipo'] = 'C';
            $infoReimpresion['Construcciones_Comunes']['Descripcion'] = $tiposContruccion['ConstruccionesComunes']['Descripcion'];
            $infoReimpresion['Construcciones_Comunes']['Uso'] = $tiposContruccion['ConstruccionesComunes']['ClaveUso'];
            $infoReimpresion['Construcciones_Comunes']['No_Niveles_Tipo'] = $tiposContruccion['ConstruccionesComunes']['NumeroDeNivelesDelTipo'];
            if(isset($tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'])){
                $infoReimpresion['Construcciones_Comunes']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'];
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['PuntajeDeClasificacion']) && !is_array($tiposContruccion['ConstruccionesComunes']['PuntajeDeClasificacion'])){
            $infoReimpresion['Construcciones_Comunes']['Puntaje'] = $tiposContruccion['ConstruccionesComunes']['PuntajeDeClasificacion'];
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['ClaveClase']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveClase'])){
            $infoReimpresion['Construcciones_Comunes']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesComunes']['ClaveClase']);
            }
            if($tipoDeAvaluo ==  "Catastral"){
                if(isset($tiposContruccion['ConstruccionesComunes']['Edad']) && !is_array($tiposContruccion['ConstruccionesComunes']['Edad'])){
                $infoReimpresion['Construcciones_Comunes']['Edad'] = $tiposContruccion['ConstruccionesComunes']['Edad'];
                }
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['VidaUtilTotalDelTipo']) && !is_array($tiposContruccion['ConstruccionesComunes']['VidaUtilTotalDelTipo'])){
                $infoReimpresion['Construcciones_Comunes']['Vida_Util_Total_Tipo'] = $tiposContruccion['ConstruccionesComunes']['VidaUtilTotalDelTipo'];
            }
            if(isset($tiposContruccion['ConstruccionesComunes']['VidaMinimaRemanente']) && !is_array($tiposContruccion['ConstruccionesComunes']['VidaMinimaRemanente'])){
                $infoReimpresion['Construcciones_Comunes']['Vida_Minima_Remanente'] = $tiposContruccion['ConstruccionesComunes']['VidaMinimaRemanente'];
            }
            //$infoReimpresion['Construcciones_Comunes']['Conservacion'] = $tiposContruccion['ConstruccionesComunes']['ClaveConservacion'];
            if(isset($tiposContruccion['ConstruccionesComunes']['Superficie']) && !is_array($tiposContruccion['ConstruccionesComunes']['Superficie'])){
                $infoReimpresion['Construcciones_Comunes']['Sup'] = $tiposContruccion['ConstruccionesComunes']['Superficie'];
            }
        }

        if(isset($tiposContruccion['ConstruccionesComunes'][0])){
            $control = 0;
            foreach($tiposContruccion['ConstruccionesComunes'] as $construccionComun){
                $infoReimpresion['Construcciones_Comunes'][$control]['Tipo'] = 'C';
                $infoReimpresion['Construcciones_Comunes'][$control]['Descripcion'] = $construccionComun['Descripcion'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Uso'] = $construccionComun['ClaveUso'];
                $infoReimpresion['Construcciones_Comunes'][$control]['No_Niveles_Tipo'] = $construccionComun['NumeroDeNivelesDelTipo'];
                if(isset($construccionComun['ClaveRangoDeNiveles']) && !is_array($construccionComun['ClaveRangoDeNiveles'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Clave_Rango_Niveles'] = $construccionComun['ClaveRangoDeNiveles'];
                }
                if(isset($construccionComun['PuntajeDeClasificacion']) && !is_array($construccionComun['PuntajeDeClasificacion'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Puntaje'] = $construccionComun['PuntajeDeClasificacion'];
                }
                if(isset($construccionComun['ClaveClase']) && !is_array($construccionComun['ClaveClase'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Clase'] = $this->modelFis->getClase($construccionComun['ClaveClase']);
                }
                if($tipoDeAvaluo ==  "Catastral"){
                    if(isset($construccionComun['Edad']) && !is_array($construccionComun['Edad'])){
                        $infoReimpresion['Construcciones_Comunes'][$control]['Edad'] = $construccionComun['Edad'];
                    }
                }

                if(isset($construccionComun['VidaUtilTotalDelTipo']) && !is_array($construccionComun['VidaUtilTotalDelTipo'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Vida_Util_Total_Tipo'] = $construccionComun['VidaUtilTotalDelTipo'];
                }
                if(isset($construccionComun['VidaMinimaRemanente']) && !is_array($construccionComun['VidaMinimaRemanente'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Vida_Minima_Remanente'] = $construccionComun['VidaMinimaRemanente'];
                }
               // $infoReimpresion['Construcciones_Comunes'][$control]['Conservacion'] = $construccionComun['ClaveConservacion'];
               if(isset($construccionComun['Superficie']) && !is_array($construccionComun['Superficie'])){
                    $infoReimpresion['Construcciones_Comunes'][$control]['Sup'] = $construccionComun['Superficie'];
               }
                $control = $control + 1;
            }
        }
        
        $infoReimpresion['Indiviso'] = $terreno['Indiviso'] <= 1 ? $terreno['Indiviso'] * 100 : $terreno['Indiviso'];
        if(isset($descripcionInmueble['VidaUtilTotalPonderadaDelInmueble']) && !is_array($descripcionInmueble['VidaUtilTotalPonderadaDelInmueble'])){
            $infoReimpresion['Vida_Util_Promedio_Inmueble'] = $descripcionInmueble['VidaUtilTotalPonderadaDelInmueble'];
        }    
        
        if($tipoDeAvaluo ==  "Catastral"){
            $infoReimpresion['Edad_Aproximada_Construccion'] = $descripcionInmueble['EdadPonderadaDelInmueble'];
        }

        if(isset($descripcionInmueble['VidaMinimaRemanentePonderadaDelInmueble']) && !is_array($descripcionInmueble['VidaMinimaRemanentePonderadaDelInmueble'])){
            $infoReimpresion['Vida_Util_Remanente'] = $descripcionInmueble['VidaMinimaRemanentePonderadaDelInmueble'];
        }

        if(isset($descripcionInmueble['VidaUtilRemanentePonderadaDelInmueble']) && !is_array($descripcionInmueble['VidaUtilRemanentePonderadaDelInmueble'])){
            $infoReimpresion['Vida_Util_Remanente'] = $descripcionInmueble['VidaUtilRemanentePonderadaDelInmueble'];
        }
        

        /************************************************************************************************************************************************************************/

        $elementosConstruccion = $elementoPrincipal['ElementosDeLaConstruccion'];
        $obraNegra = $elementosConstruccion['ObraNegra'];

        $infoReimpresion['Obra_Negra_Gruesa'] = array();

        if(isset($obraNegra['Cimentacion']) && !is_array($obraNegra['Cimentacion'])){
            $infoReimpresion['Obra_Negra_Gruesa']['Cimientos'] = $obraNegra['Cimentacion'];
        }
        if(isset($obraNegra['Estructura']) && !is_array($obraNegra['Estructura'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Estructura'] = $obraNegra['Estructura'];
        }
        if(isset($obraNegra['Muros']) && !is_array($obraNegra['Muros'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Muros'] = $obraNegra['Muros'];
        }
        if(isset($obraNegra['Entrepisos']) && !is_array($obraNegra['Entrepisos'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Entrepiso'] = $obraNegra['Entrepisos'];
        }
        if(isset($obraNegra['Techos']) && !is_array($obraNegra['Techos'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Techos'] = $obraNegra['Techos'];
        }
        if(isset($obraNegra['Azoteas']) && !is_array($obraNegra['Azoteas'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Azoteas'] = $obraNegra['Azoteas'];
        }
        if(isset($obraNegra['Bardas']) && !is_array($obraNegra['Bardas'])){
        $infoReimpresion['Obra_Negra_Gruesa']['Bardas'] = $obraNegra['Bardas'];
        }

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Revestimientos_Acabados_Interiores'] = array();
        $revestimientosAcabados = $elementosConstruccion['RevestimientosYAcabadosInteriores'];

        $infoReimpresion['Revestimientos_Acabados_Interiores']['Aplanados'] = $revestimientosAcabados['Aplanados'];
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Plafones'] = $revestimientosAcabados['Plafones'];
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Lambrines'] = $revestimientosAcabados['Lambrines'];
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Pisos'] = $revestimientosAcabados['Pisos'];
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Zoclos'] = $revestimientosAcabados['Zoclos'];
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Escaleras'] = $revestimientosAcabados['Escaleras'];
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Pintura'] = $revestimientosAcabados['Pintura'];
        $infoReimpresion['Revestimientos_Acabados_Interiores']['Recubrimientos_Especiales'] = $revestimientosAcabados['RecubrimientosEspeciales'];

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Carpinteria'] = array();
        $carpinteria = $elementosConstruccion['Carpinteria'];

        if(isset($carpinteria['PuertasInteriores']) && !is_array($carpinteria['PuertasInteriores'])){
            $infoReimpresion['Carpinteria']['Puertas_Interiores'] = $carpinteria['PuertasInteriores'];
        }
        if(isset($carpinteria['Guardaropas']) && !is_array($carpinteria['Guardaropas'])){
        $infoReimpresion['Carpinteria']['Guardarropas'] = $carpinteria['Guardaropas'];
        }
        if(isset($carpinteria['MueblesEmpotradosOFijos']) && !is_array($carpinteria['MueblesEmpotradosOFijos'])){
        $infoReimpresion['Carpinteria']['Muebles_Empotrados'] = $carpinteria['MueblesEmpotradosOFijos'];
        }

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias'] = array();
        $hidraulicasSanitarias = $elementosConstruccion['InstalacionesHidraulicasYSanitrias'];

        if(isset($hidraulicasSanitarias['MueblesDeBanno']) && !is_array($hidraulicasSanitarias['MueblesDeBanno'])){
            $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias']['Muebles_Banio'] = $hidraulicasSanitarias['MueblesDeBanno'];
        }
        if(isset($hidraulicasSanitarias['RamaleosHidraulicos']) && !is_array($hidraulicasSanitarias['RamaleosHidraulicos'])){
            $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Hidraulicos'] = $hidraulicasSanitarias['RamaleosHidraulicos'];
        }
        if(isset($hidraulicasSanitarias['RamaleosSanitarios']) && !is_array($hidraulicasSanitarias['RamaleosSanitarios'])){
            $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Sanitarios'] = $hidraulicasSanitarias['RamaleosSanitarios'];
        }
        if(isset($elementosConstruccion['InstalacionesElectricasYAlumbrado'])){
            $infoReimpresion['Instalaciones_Electricas_Alumbrados'] = $elementosConstruccion['InstalacionesElectricasYAlumbrado'];
        }    

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Puertas_Ventaneria_Metalica'] = array();
        $puertasVentaneria = $elementosConstruccion['PuertasYVentaneriaMetalica'];

        if(isset($puertasVentaneria['Herreria']) && !is_array($puertasVentaneria['Herreria'])){
            $infoReimpresion['Puertas_Ventaneria_Metalica']['Herreria'] = $puertasVentaneria['Herreria'];
        }
        if(isset($puertasVentaneria['Ventaneria']) && !is_array($puertasVentaneria['Ventaneria'])){
            $infoReimpresion['Puertas_Ventaneria_Metalica']['Ventaneria'] = $puertasVentaneria['Ventaneria'];
        }

        if(isset($elementosConstruccion['Vidreria']) && !is_array($elementosConstruccion['Vidreria'])){
            $infoReimpresion['Vidrieria'] = $elementosConstruccion['Vidreria'];
        }
        if(isset($elementosConstruccion['Cerrajeria']) && !is_array($elementosConstruccion['Cerrajeria'])){
        $infoReimpresion['Cerrajeria'] = $elementosConstruccion['Cerrajeria'];
        }
        if(isset($elementosConstruccion['Fachadas']) && !is_array($elementosConstruccion['Fachadas'])){
        $infoReimpresion['Fachadas'] = $elementosConstruccion['Fachadas'];
        }

        /************************************************************************************************************************************************************************/
        if(isset($elementosConstruccion['InstalacionesEspeciales'])){
            $infoReimpresion['Instalaciones_Especiales'] = array();
            $instalacionesEspeciales = $elementosConstruccion['InstalacionesEspeciales'];

            if(isset($instalacionesEspeciales['Privativas']['@attributes'])){
                $infoReimpresion['Instalaciones_Especiales']['Privativas'] = array();
                $infoReimpresion['Instalaciones_Especiales']['Privativas']['Clave'] = $instalacionesEspeciales['Privativas']['ClaveInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales']['Privativas']['Descripcion'] = $instalacionesEspeciales['Privativas']['DescripcionInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales']['Privativas']['Unidad'] = $instalacionesEspeciales['Privativas']['UnidadInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales']['Privativas']['Cantidad'] = $instalacionesEspeciales['Privativas']['CantidadInstalacionEspecial'];
            }

            if(isset($instalacionesEspeciales['Privativas'][0])){
                $infoReimpresion['Instalaciones_Especiales']['Privativas'] = array();
                $control = 0;
                foreach($instalacionesEspeciales['Privativas'] as $instalacionEspecial){
                    $infoReimpresion['Instalaciones_Especiales']['Privativas'][$control]['Clave'] = $instalacionEspecial['ClaveInstalacionEspecial'];
                    $infoReimpresion['Instalaciones_Especiales']['Privativas'][$control]['Descripcion'] = $instalacionEspecial['DescripcionInstalacionEspecial'];
                    $infoReimpresion['Instalaciones_Especiales']['Privativas'][$control]['Unidad'] = $instalacionEspecial['UnidadInstalacionEspecial'];
                    $infoReimpresion['Instalaciones_Especiales']['Privativas'][$control]['Cantidad'] = $instalacionEspecial['CantidadInstalacionEspecial'];
                    $control = $control + 1;
                }
            }

            if(isset($instalacionesEspeciales['Comunes']['@attributes'])){
                $infoReimpresion['Instalaciones_Especiales']['Comunes'] = array();
                $infoReimpresion['Instalaciones_Especiales']['Comunes']['Clave'] = $instalacionesEspeciales['Comunes']['ClaveInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales']['Comunes']['Descripcion'] = $instalacionesEspeciales['Comunes']['DescripcionInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales']['Comunes']['Unidad'] = $instalacionesEspeciales['Comunes']['UnidadInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales']['Comunes']['Cantidad'] = $instalacionesEspeciales['Comunes']['CantidadInstalacionEspecial'];
            }

            if(isset($instalacionesEspeciales['Comunes'][0])){
                $infoReimpresion['Instalaciones_Especiales']['Comunes'] = array();
                $control = 0;
                foreach($instalacionesEspeciales['Comunes'] as $instalacionEspecial){
                    $infoReimpresion['Instalaciones_Especiales']['Comunes'][$control]['Clave'] = $instalacionEspecial['ClaveInstalacionEspecial'];
                    $infoReimpresion['Instalaciones_Especiales']['Comunes'][$control]['Descripcion'] = $instalacionEspecial['DescripcionInstalacionEspecial'];
                    $infoReimpresion['Instalaciones_Especiales']['Comunes'][$control]['Unidad'] = $instalacionEspecial['UnidadInstalacionEspecial'];
                    $infoReimpresion['Instalaciones_Especiales']['Comunes'][$control]['Cantidad'] = $instalacionEspecial['CantidadInstalacionEspecial'];
                    $control = $control + 1;
                }
            }

        }

        /************************************************************************************************************************************************************************/

        if(isset($elementosConstruccion['ElementosAccesorios'])){
            $infoReimpresion['Elementos_Accesorios']  = array();
            $elementosAccesorios = $elementosConstruccion['ElementosAccesorios'];

            if(isset($elementosAccesorios['Privativas']['@attributes'])){
                $infoReimpresion['Elementos_Accesorios']['Privativas'] = array();
                $infoReimpresion['Elementos_Accesorios']['Privativas']['Clave'] = $elementosAccesorios['Privativas']['ClaveElementoAccesorio'];
                $infoReimpresion['Elementos_Accesorios']['Privativas']['Descripcion'] = $elementosAccesorios['Privativas']['DescripcionElementoAccesorio'];
                $infoReimpresion['Elementos_Accesorios']['Privativas']['Unidad'] = $elementosAccesorios['Privativas']['UnidadElementoAccesorio'];
                $infoReimpresion['Elementos_Accesorios']['Privativas']['Cantidad'] = $elementosAccesorios['Privativas']['CantidadElementoAccesorio'];
                if(isset($elementosAccesorios['Privativas']['EdadElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Edad'] = $elementosAccesorios['Privativas']['EdadElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Privativas']['VidaUtilTotalElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Vida_Util_Total'] = $elementosAccesorios['Privativas']['VidaUtilTotalElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Privativas']['CostoUnitarioElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Costo_Unitario'] = $elementosAccesorios['Privativas']['CostoUnitarioElementoAccesorio'];
                }
                
            }

            if(isset($elementosAccesorios['Privativas'][0])){
                $infoReimpresion['Elementos_Accesorios']['Privativas'] = array();
                $control = 0;
                foreach($elementosAccesorios['Privativas'] as $elementoAccesorio){
                    $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Clave'] = $elementoAccesorio['ClaveElementoAccesorio'];
                    $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Descripcion'] = $elementoAccesorio['DescripcionElementoAccesorio'];
                    $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Unidad'] = $elementoAccesorio['UnidadElementoAccesorio'];
                    $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $elementoAccesorio['CantidadElementoAccesorio'];
                    if(isset($elementoAccesorio['EdadElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Edad'] = $elementoAccesorio['EdadElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['VidaUtilTotalElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Vida_Util_Total'] = $elementoAccesorio['VidaUtilTotalElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['CostoUnitarioElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Costo_Unitario'] = $elementoAccesorio['CostoUnitarioElementoAccesorio'];
                    }
                    
                    $control = $control + 1;
                }
            }

            if(isset($elementosAccesorios['Comunes']['@attributes'])){
                $infoReimpresion['Elementos_Accesorios']['Comunes'] = array();
                $infoReimpresion['Elementos_Accesorios']['Comunes']['Clave'] = $elementosAccesorios['Comunes']['ClaveElementoAccesorio'];
                $infoReimpresion['Elementos_Accesorios']['Comunes']['Descripcion'] = $elementosAccesorios['Comunes']['DescripcionElementoAccesorio'];
                $infoReimpresion['Elementos_Accesorios']['Comunes']['Unidad'] = $elementosAccesorios['Comunes']['UnidadElementoAccesorio'];
                $infoReimpresion['Elementos_Accesorios']['Comunes']['Cantidad'] = $elementosAccesorios['Comunes']['CantidadElementoAccesorio'];
                if(isset($elementosAccesorios['Comunes']['EdadElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Edad'] = $elementosAccesorios['Comunes']['EdadElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Comunes']['VidaUtilTotalElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Vida_Util_Total'] = $elementosAccesorios['Comunes']['VidaUtilTotalElementoAccesorio'];
                }
                if(isset($elementosAccesorios['Comunes']['CostoUnitarioElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Costo_Unitario'] = $elementosAccesorios['Comunes']['CostoUnitarioElementoAccesorio'];
                }
                
            }

            if(isset($elementosAccesorios['Comunes'][0])){
                $infoReimpresion['Elementos_Accesorios']['Comunes'] = array();
                $control = 0;
                foreach($elementosAccesorios['Comunes'] as $elementoAccesorio){
                    $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Clave'] = $elementoAccesorio['ClaveElementoAccesorio'];
                    $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Descripcion'] = $elementoAccesorio['DescripcionElementoAccesorio'];
                    $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Unidad'] = $elementoAccesorio['UnidadElementoAccesorio'];
                    $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $elementoAccesorio['CantidadElementoAccesorio'];
                    if(isset($elementoAccesorio['EdadElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Edad'] = $elementoAccesorio['EdadElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['VidaUtilTotalElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Vida_Util_Total'] = $elementoAccesorio['VidaUtilTotalElementoAccesorio'];
                    }
                    if(isset($elementoAccesorio['CostoUnitarioElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Comunes'][$control]['Costo_Unitario'] = $elementoAccesorio['CostoUnitarioElementoAccesorio'];
                    }
                    
                    $control = $control + 1;
                }
            }
        }
        

        /******************************************************************************************************************************************************/
        
        if(isset($elementosConstruccion['ObrasComplementarias'])){
            $infoReimpresion['Obras_Complementarias']  = array();
            $obrasComplementarias = $elementosConstruccion['ObrasComplementarias'];

            if(isset($obrasComplementarias['Privativas']['@attributes'])){
                $infoReimpresion['Obras_Complementarias']['Privativas'] = array();
                $infoReimpresion['Obras_Complementarias']['Privativas']['Clave'] = $obrasComplementarias['Privativas']['ClaveObraComplementaria'];
                $infoReimpresion['Obras_Complementarias']['Privativas']['Descripcion'] = $obrasComplementarias['Privativas']['DescripcionObraComplementaria'];
                $infoReimpresion['Obras_Complementarias']['Privativas']['Unidad'] = $obrasComplementarias['Privativas']['UnidadObraComplementaria'];
                $infoReimpresion['Obras_Complementarias']['Privativas']['Cantidad'] = $obrasComplementarias['Privativas']['CantidadObraComplementaria'];
                if(isset($obrasComplementarias['Privativas']['EdadObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Edad'] = $obrasComplementarias['Privativas']['EdadObraComplementaria'];
                }
                if(isset($obrasComplementarias['Privativas']['VidaUtilTotalObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Vida_Util_Total'] = $obrasComplementarias['Privativas']['VidaUtilTotalObraComplementaria'];
                }
                if(isset($obrasComplementarias['Privativas']['CostoUnitarioObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Costo_Unitario'] = $obrasComplementarias['Privativas']['CostoUnitarioObraComplementaria'];
                }
            }

            if(isset($obrasComplementarias['Privativas'][0])){
                $infoReimpresion['Obras_Complementarias']['Privativas'] = array();
                $control = 0;
                foreach($obrasComplementarias['Privativas'] as $obraComplementaria){
                    $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Clave'] = $obraComplementaria['ClaveObraComplementaria'];
                    $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Descripcion'] = $obraComplementaria['DescripcionObraComplementaria'];
                    $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Unidad'] = $obraComplementaria['UnidadObraComplementaria'];
                    $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Cantidad'] = $obraComplementaria['CantidadObraComplementaria'];
                    if(isset($obraComplementaria['EdadObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Edad'] = $obraComplementaria['EdadObraComplementaria'];
                    }
                    if(isset($obraComplementaria['VidaUtilTotalObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Vida_Util_Total'] = $obraComplementaria['VidaUtilTotalObraComplementaria'];
                    }
                    if(isset($obraComplementaria['CostoUnitarioObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Costo_Unitario'] = $obraComplementaria['CostoUnitarioObraComplementaria'];
                    }
                    $control = $control + 1;
                }
            }

            if(isset($obrasComplementarias['Comunes']['@attributes'])){
                $infoReimpresion['Obras_Complementarias']['Comunes'] = array();
                $infoReimpresion['Obras_Complementarias']['Comunes']['Clave'] = $obrasComplementarias['Comunes']['ClaveObraComplementaria'];
                $infoReimpresion['Obras_Complementarias']['Comunes']['Descripcion'] = $obrasComplementarias['Comunes']['DescripcionObraComplementaria'];
                $infoReimpresion['Obras_Complementarias']['Comunes']['Unidad'] = $obrasComplementarias['Comunes']['UnidadObraComplementaria'];
                $infoReimpresion['Obras_Complementarias']['Comunes']['Cantidad'] = $obrasComplementarias['Comunes']['CantidadObraComplementaria'];
                if(isset($obrasComplementarias['Comunes']['EdadObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Edad'] = $obrasComplementarias['Comunes']['EdadObraComplementaria'];
                }
                if(isset($obrasComplementarias['Comunes']['VidaUtilTotalObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Vida_Util_Total'] = $obrasComplementarias['Comunes']['VidaUtilTotalObraComplementaria'];
                }
                if(isset($obrasComplementarias['Comunes']['CostoUnitarioObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Costo_Unitario'] = $obrasComplementarias['Comunes']['CostoUnitarioObraComplementaria'];
                }
                
            }

            if(isset($obrasComplementarias['Comunes'][0])){
                $infoReimpresion['Obras_Complementarias']['Comunes'] = array();
                $control = 0;
                foreach($obrasComplementarias['Comunes'] as $obraComplementaria){
                    $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Clave'] = $obraComplementaria['ClaveObraComplementaria'];
                    $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Descripcion'] = $obraComplementaria['DescripcionObraComplementaria'];
                    $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Unidad'] = $obraComplementaria['UnidadObraComplementaria'];
                    $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Cantidad'] = $obraComplementaria['CantidadObraComplementaria'];
                    if(isset($obraComplementaria['EdadObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Edad'] = $obraComplementaria['EdadObraComplementaria'];
                    }
                    if(isset($obraComplementaria['VidaUtilTotalObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Vida_Util_Total'] = $obraComplementaria['VidaUtilTotalObraComplementaria'];
                    }
                    if(isset($obraComplementaria['CostoUnitarioObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Costo_Unitario'] = $obraComplementaria['CostoUnitarioObraComplementaria'];
                    }    
                    $control = $control + 1;
                }
            }
        }
        

        /*********************************************************************************************************************/

        if(isset($elementoPrincipal['EnfoqueDeMercado'])){
            $infoReimpresion['Terrenos']  = array();        

            $enfoqueMercado = $elementoPrincipal['EnfoqueDeMercado'];
            $terrenos = $enfoqueMercado['Terrenos'];            

            if(isset($terrenos['TerrenosDirectos'])){
                $infoReimpresion['Terrenos']['Terrenos_Directos'] = array();
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'] = array();
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'] = array();

                $terrenosDirectos = $terrenos['TerrenosDirectos'];

                if(isset($terrenosDirectos[0])){

                    $control = 0; 
                    foreach($terrenosDirectos as $terrenoDirecto){
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Ubicacion'] = $terrenoDirecto['Calle'].". ".$terrenoDirecto['Colonia'].". ".$terrenoDirecto['CodigoPostal'].".";
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Descripcion'] = $terrenoDirecto['DescripcionDelPredio'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['C_U_S'] = $terrenoDirecto['CUS'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Uso_Suelo'] = $terrenoDirecto['UsoDelSuelo'];
    
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_Negociacion'] = $terrenoDirecto['FactorDeNegociacion'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Superficie'] = $terrenoDirecto['Superficie'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor1'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor1']['Titulo'] = $terrenoDirecto['Factor1']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor1']['Valor'] = $terrenoDirecto['Factor1']['Valor'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor2'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor2']['Titulo'] = $terrenoDirecto['Factor2']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor2']['Valor'] = $terrenoDirecto['Factor2']['Valor'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor3'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor3']['Titulo'] = $terrenoDirecto['Factor3']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor3']['Valor'] = $terrenoDirecto['Factor3']['Valor'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor4'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor4']['Titulo'] = $terrenoDirecto['Factor4']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor4']['Valor'] = $terrenoDirecto['Factor4']['Valor'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor5'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor5']['Titulo'] = $terrenoDirecto['Factor5']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor5']['Valor'] = $terrenoDirecto['Factor5']['Valor'];
                        /*if(isset($terrenoDirecto['Fot'])){
                            $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_otro'] = $terrenoDirecto['Fot']['Valor'];                    
                        }else{
                            $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_otro'] = '';
                        } */               
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fre'] = $terrenoDirecto['Fre'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Precio_Solicitado'] = $terrenoDirecto['PrecioSolicitado'];
    
                        $control = $control + 1;
                    } //print_r($infoReimpresion); exit();
    
                }else{
    
                    $control = 0;
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Ubicacion'] = $terrenosDirectos['Calle'].". ".$terrenosDirectos['Colonia'].". ".$terrenosDirectos['CodigoPostal'].".";
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Descripcion'] = $terrenosDirectos['DescripcionDelPredio'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['C_U_S'] = $terrenosDirectos['CUS'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Uso_Suelo'] = $terrenosDirectos['UsoDelSuelo'];
    
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_Negociacion'] = $terrenosDirectos['FactorDeNegociacion'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Superficie'] = $terrenosDirectos['Superficie'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor1'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor1']['Titulo'] = $terrenosDirectos['Factor1']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor1']['Valor'] = $terrenosDirectos['Factor1']['Valor'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor2'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor2']['Titulo'] = $terrenosDirectos['Factor2']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor2']['Valor'] = $terrenosDirectos['Factor2']['Valor'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor3'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor3']['Titulo'] = $terrenosDirectos['Factor3']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor3']['Valor'] = $terrenosDirectos['Factor3']['Valor'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor4'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor4']['Titulo'] = $terrenosDirectos['Factor4']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor4']['Valor'] = $terrenosDirectos['Factor4']['Valor'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor5'] = array();
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor5']['Titulo'] = $terrenosDirectos['Factor5']['Siglas'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Factor5']['Valor'] = $terrenosDirectos['Factor5']['Valor'];
                        /*if(isset($terrenoDirecto['Fot'])){
                            $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_otro'] = $terrenoDirecto['Fot']['Valor'];                    
                        }else{
                            $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_otro'] = '';
                        } */               
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fre'] = $terrenosDirectos['Fre'];
                        $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Precio_Solicitado'] = $terrenosDirectos['PrecioSolicitado'];
    
                }
                
    
                $conclusionHomologacionTerrenos = $terrenos['ConclusionesHomologacionTerrenos'];
    
                $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos'] = array();
                $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Tierra_Promedio'] = $conclusionHomologacionTerrenos['ValorUnitarioDeTierraPromedio'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Tierra_Homologado'] = $conclusionHomologacionTerrenos['ValorUnitarioDeTierraHomologadoPromedio'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Sin_Homologar_Minimo'] = $conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMinimo'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Sin_Homologar_Maximo'] = $conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMaximo'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Homologado_Minimo'] = $conclusionHomologacionTerrenos['ValorUnitarioHomologadoMinimo'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Homologado_Maximo'] = $conclusionHomologacionTerrenos['ValorUnitarioHomologadoMaximo'];

            }
            
            

            /************************************************************************************************************************************************************************/

            if(isset($terrenos['TerrenosResidual'])){
                $infoReimpresion['Terrenos']['Terrenos_Residuales'] = array();                
                $terrenosResidual = $terrenos['TerrenosResidual'];

                if(isset($terrenosResidual['TipoDeProductoInmobiliarioPropuesto']) && !is_array($terrenosResidual['TipoDeProductoInmobiliarioPropuesto'])){
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Tipo_Producto_Inmoviliario_Propuesto'] = $terrenosResidual['TipoDeProductoInmobiliarioPropuesto'];
                }
                if(isset($terrenosResidual['NumeroDeUnidadesVendibles']) && !is_array($terrenosResidual['NumeroDeUnidadesVendibles'])){
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Numero_Unidades_Vendibles'] = $terrenosResidual['NumeroDeUnidadesVendibles'];
                }
                if(isset($terrenosResidual['SuperficieVendiblePorUnidad']) && !is_array($terrenosResidual['SuperficieVendiblePorUnidad'])){
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Superficie_Vendible_Unidad'] = $terrenosResidual['SuperficieVendiblePorUnidad'];
                }

                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables'] = array();
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables_2'] = array();

                $control = 0;
                foreach($terrenosResidual['InvestigacionProductosComparables'] as $terrenoResidualInvestigacionProductos){
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables'][$control]['Ubicacion'] = $terrenoResidualInvestigacionProductos['Calle'].". ".$terrenoResidualInvestigacionProductos['Colonia'].". ".$terrenoResidualInvestigacionProductos['CodigoPostal'].".";
                    $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables'][$control]['Descripcion'] = $terrenoResidualInvestigacionProductos['DescripcionDelComparable'];   

                    if(isset($terrenoResidualInvestigacionProductos['SuperficieVendiblePorUnidad']) && isset($terrenoResidualInvestigacionProductos['PrecioSolicitado']) && isset($terrenoResidualInvestigacionProductos['FactorDeNegociacion'])){
                        $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables_2'][$control]['F_Negociacion'] = $terrenoResidualInvestigacionProductos['FactorDeNegociacion'];
                        $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables_2'][$control]['Superficie'] = $terrenoResidualInvestigacionProductos['SuperficieVendiblePorUnidad'];
                        $infoReimpresion['Terrenos']['Terrenos_Residuales']['Investigacion_Productos_Comparables_2'][$control]['Precio_Solicitado'] = $terrenoResidualInvestigacionProductos['PrecioSolicitado'];
                    }

                    $control = $control + 1;
                }

                $conclusionHomologacionResiduales = $terrenosResidual['ConclusionesHomologacionCompResiduales'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales'] = array();

                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Promedio'] = $conclusionHomologacionResiduales['ValorUnitarioPromedio'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Homologado'] = $conclusionHomologacionResiduales['ValorUnitarioHomologadoPromedio'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Sin_Homologar_Minimo'] = $conclusionHomologacionResiduales['ValorUnitarioSinHomologarMinimo'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Sin_Homologar_Maximo'] = $conclusionHomologacionResiduales['ValorUnitarioSinHomologarMaximo'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Homologado_Minimo'] = $conclusionHomologacionResiduales['ValorUnitarioHomologadoMinimo'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Homologado_Maximo'] = $conclusionHomologacionResiduales['ValorUnitarioHomologadoMaximo'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Conclusiones_Homologacion_Comp_Residuales']['Valor_Unitario_Aplicable_Residual'] = $conclusionHomologacionResiduales['ValorUnitarioAplicableAlResidual'];

                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual'] = array();
                $analisisResidual = $terrenosResidual['AnalisisResidual'];

                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual']['Total_Ingresos'] = $analisisResidual['TotalDeIngresos'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual']['Total_Egresos'] = $analisisResidual['TotalDeEgresos'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual']['Unidad_Propuesta'] = $analisisResidual['UtilidadPropuesta'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Analisis_Residual']['Valor_Unitario_Tierra_Residual'] = $analisisResidual['ValorUnitarioDeTierraResidual'];

            }
            
            $infoReimpresion['Terrenos']['Valor_Unitario_Tierra_Del_Avaluo'] = $terrenos['ValorUnitarioDeTierraAplicableAlAvaluo'];
        
        

        /***********************************************************************************************************************/

            $infoReimpresion['Construcciones_En_Venta'] = array();
            $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables'] = array();
            $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaUno'] = array();
            $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaDos'] = array();

            $construccionesEnVenta = $enfoqueMercado['ConstruccionesEnVenta'];
            $investigacionProductosComparables = $construccionesEnVenta['InvestigacionProductosComparables'];

            $control = 0;
            foreach($investigacionProductosComparables as $productoComparable){
                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Ubicacion'] = $productoComparable['Calle'].". ".$productoComparable['Colonia'].". ".$productoComparable['CodigoPostal'];
                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Descripcion'] = $productoComparable['DescripcionDelComparable'];

                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['F_Negociacion'] = $productoComparable['FactorDeNegociacion'];
                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Superficie_Vendible'] = $productoComparable['SuperficieVendiblePorUnidad'];
                $infoReimpresion['Construcciones_En_Venta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Precio_Solicitado'] = $productoComparable['PrecioSolicitado'];

                $control = $control + 1;
            }

            $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta'] = array();
            $conclusionesHomologacionContruccionesVenta = $construccionesEnVenta['ConclusionesHomologacionConstruccionesEnVenta'];

            $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Promedio'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioPromedio'];
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoPromedio']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoPromedio'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Homologado'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoPromedio'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMinimo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMinimo'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Sin_Homolgar_Minimo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMinimo'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMaximo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMaximo'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Sin_Homolgar_Maximo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMaximo'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMinimo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMinimo'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Homologado_Minimo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMinimo'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMaximo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMaximo'])){
                $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Homologado_Maximo']  = $conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMaximo'];
            }
            if(isset($conclusionesHomologacionContruccionesVenta['ValorUnitarioAplicableAlAvaluo']) && !is_array($conclusionesHomologacionContruccionesVenta['ValorUnitarioAplicableAlAvaluo'])){
                $infoReimpresion['Construcciones_En_Venta']['Valor_Unitario_Aplicable_Avaluo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioAplicableAlAvaluo'];
            }
            $infoReimpresion['Construcciones_En_Venta']['Valor_Mercado_Del_Inmueble'] = $enfoqueMercado['ValorDeMercadoDelInmueble'];

            /************************************************************************************************************************************************************************/

            if(isset($enfoqueMercado['ConstruccionesEnRenta'])){
                $infoReimpresion['Construcciones_En_Renta'] = array();
                $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables'] = array();
                $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'] = array();
                $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'] = array();

                $construccionesEnRenta = $enfoqueMercado['ConstruccionesEnRenta'];
                $investigacionProductosComparables = $construccionesEnRenta['InvestigacionProductosComparables'];
                $control = 0;
                if(isset($investigacionProductosComparables['@attributes'])){
                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Ubicacion'] = $investigacionProductosComparables['Calle'].". ".$investigacionProductosComparables['Colonia'].". ".$investigacionProductosComparables['CodigoPostal'];
                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Descripcion'] = $investigacionProductosComparables['DescripcionDelComparable'];

                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['F_Negociacion'] = $investigacionProductosComparables['FactorDeNegociacion'];
                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Superficie_Vendible'] = $investigacionProductosComparables['SuperficieVendiblePorUnidad'];
                    $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Precio_Solicitado'] = $investigacionProductosComparables['PrecioSolicitado'];
                }else{
                    if(isset($investigacionProductosComparables[0])){
                        
                        foreach($investigacionProductosComparables as $productoComparable){
                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Ubicacion'] = $productoComparable['Calle'].". ".$productoComparable['Colonia'].". ".$productoComparable['CodigoPostal'];
                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaUno'][$control]['Descripcion'] = $productoComparable['DescripcionDelComparable'];

                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['F_Negociacion'] = $productoComparable['FactorDeNegociacion'];
                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Superficie_Vendible'] = $productoComparable['SuperficieVendiblePorUnidad'];
                            $infoReimpresion['Construcciones_En_Renta']['Investigacion_Productos_Comparables']['TablaDos'][$control]['Precio_Solicitado'] = $productoComparable['PrecioSolicitado'];

                            $control = $control + 1;
                        }
                    }
                }
                

                $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta'] = array();
                //Se usa ConclusionesHomologacionConstruccionesEnVenta porque asi llega en el XML
                if(isset($construccionesEnRenta['ConclusionesHomologacionConstruccionesEnRenta'])){
                    $conclusionesHomologacionContruccionesRenta = $construccionesEnRenta['ConclusionesHomologacionConstruccionesEnRenta'];
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Promedio'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioPromedio'];
                }
                

                
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoPromedio']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoPromedio'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Homologado'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoPromedio'];
                }
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMinimo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMinimo'])){              
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Sin_Homolgar_Minimo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMinimo'];
                }
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMaximo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMaximo'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Sin_Homolgar_Maximo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMaximo'];
                }
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMinimo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMinimo'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Homologado_Minimo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMinimo'];
                }
                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMaximo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMaximo'])){
                    $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Homologado_Maximo']  = $conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMaximo'];
                }

                if(isset($conclusionesHomologacionContruccionesRenta['ValorUnitarioAplicableAlAvaluo']) && !is_array($conclusionesHomologacionContruccionesRenta['ValorUnitarioAplicableAlAvaluo'])){
                    $infoReimpresion['Construcciones_En_Renta']['Valor_Unitario_Aplicable_Avaluo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioAplicableAlAvaluo'];
                }

            }        

    }
        /************************************************************************************************************************************************************************/



        /************************************************************************************************************************************************************************/

        //$superficieDelTerreno = $terreno['SuperficieDelTerreno'];
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno'] = array();
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales'] = array();

        if(isset($superficieDelTerreno[0])){
            $control = 0;
            foreach($superficieDelTerreno as $supDelTerreno){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Fracc'] = $supDelTerreno['IdentificadorFraccionN1'];
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Clave_Area_Valor'] = $supDelTerreno['ClaveDeAreaDeValor'];
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Superficie_m2'] = $supDelTerreno['SuperficieFraccionN1'];
                if(isset($supDelTerreno['Factor1'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor1'] = array();
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor1']['Titulo'] = $supDelTerreno['Factor1']['Siglas'];
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor1']['Valor'] = $supDelTerreno['Factor1']['Valor'];
                }        
                if(isset($supDelTerreno['Factor2'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor2'] = array();
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor2']['Titulo'] = $supDelTerreno['Factor2']['Siglas'];
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor2']['Valor'] = $supDelTerreno['Factor2']['Valor'];
                }
                if(isset($supDelTerreno['Factor3'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor3'] = array();
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor3']['Titulo'] = $supDelTerreno['Factor3']['Siglas'];
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor3']['Valor'] = $supDelTerreno['Factor3']['Valor'];
                }
                if(isset($supDelTerreno['Factor4'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor4'] = array();
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor4']['Titulo'] = $supDelTerreno['Factor4']['Siglas'];
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor4']['Valor'] = $supDelTerreno['Factor4']['Valor'];
                }
                if(isset($supDelTerreno['Factor5'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor5'] = array();
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor5']['Titulo'] = $supDelTerreno['Factor5']['Siglas'];            
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor5']['Valor'] = $supDelTerreno['Factor5']['Valor'];
                }
                /*if(isset($superficieDelTerreno['Fot'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Fot'] = $superficieDelTerreno['Fot']['Valor'] === '1' ? $superficieDelTerreno['Fot']['Valor'].".00"." ".$superficieDelTerreno['Fot']['Descripcion'] : $superficieDelTerreno['Fot']['Valor']." ".$superficieDelTerreno['Fot']['Descripcion'];
                }*/
                if(isset($supDelTerreno['Fre'])){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['F_Resultante'] = $supDelTerreno['Fre'];
                }
                
                if($tipoDeAvaluo == 'Catastral'){
                    $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Valor_Catastral'] = $supDelTerreno['ValorCatastralDeTierraAplicableALaFraccion'];
                }
                
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Valor_Fraccion'] = $supDelTerreno['ValorDeLaFraccionN'];
                $control = $control + 1;
            }
        }else{

            $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Fracc'] = $superficieDelTerreno['IdentificadorFraccionN1'];
            $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Clave_Area_Valor'] = $superficieDelTerreno['ClaveDeAreaDeValor'];
            $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Superficie_m2'] = $superficieDelTerreno['SuperficieFraccionN1'];
            if(isset($superficieDelTerreno['Factor1'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor1'] = array();
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor1']['Titulo'] = $superficieDelTerreno['Factor1']['Siglas'];
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor1']['Valor'] = $superficieDelTerreno['Factor1']['Valor'];
            }        
            if(isset($superficieDelTerreno['Factor2'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor2'] = array();
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor2']['Titulo'] = $superficieDelTerreno['Factor2']['Siglas'];
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor2']['Valor'] = $superficieDelTerreno['Factor2']['Valor'];
            }
            if(isset($superficieDelTerreno['Factor3'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor3'] = array();
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor3']['Titulo'] = $superficieDelTerreno['Factor3']['Siglas'];
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor3']['Valor'] = $superficieDelTerreno['Factor3']['Valor'];
            }
            if(isset($superficieDelTerreno['Factor4'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor4'] = array();
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor4']['Titulo'] = $superficieDelTerreno['Factor4']['Siglas'];
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor4']['Valor'] = $superficieDelTerreno['Factor4']['Valor'];
            }
            if(isset($superficieDelTerreno['Factor5'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor5'] = array();
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor5']['Titulo'] = $superficieDelTerreno['Factor5']['Siglas'];
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Factor5']['Valor'] = $superficieDelTerreno['Factor5']['Valor'];            
            }
            /*if(isset($superficieDelTerreno['Fot'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Fot'] = $superficieDelTerreno['Fot']['Valor'] === '1' ? $superficieDelTerreno['Fot']['Valor'].".00"." ".$superficieDelTerreno['Fot']['Descripcion'] : $superficieDelTerreno['Fot']['Valor']." ".$superficieDelTerreno['Fot']['Descripcion'];
            }*/
            if(isset($superficieDelTerreno['Fre'])){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['F_Resultante'] = $superficieDelTerreno['Fre'];
            }
            
            if($tipoDeAvaluo == 'Catastral'){
                $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Valor_Catastral'] = $superficieDelTerreno['ValorCatastralDeTierraAplicableALaFraccion'];
            }
            
            $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Valor_Fraccion'] = $superficieDelTerreno['ValorDeLaFraccionN'];

        }
        

        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales']['Total_Superficie'] = $terreno['SuperficieTotalDelTerreno'];
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales']['Valor_Del_Terreno_Total'] = $terreno['ValorTotalDelTerreno'];        

        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales']['Indiviso_Unidad_Que_Se_Valua'] = $terreno['Indiviso'] <= 1 ? $terreno['Indiviso'] * 100 : $terreno['Indiviso'];
        
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales']['Valor_Total_Del_Terreno_Proporcional'] = $terreno['ValorTotalDelTerrenoProporcional'];

        
        /*********************************************************************************************************************************************************************************/
        $infoReimpresion['Calculo_Valor_Construcciones'] = array();
        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'] = array();
        $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas'] = array();
        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'] = array();        
        $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes'] = array();

        if($tipoDeAvaluo == 'Comercial'){

            if(isset($tiposContruccion['ConstruccionesPrivativas']['@attributes'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Fracc'] = 1;
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Descripcion'] = $tiposContruccion['ConstruccionesPrivativas']['Descripcion'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Uso'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveUso'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']);
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Superficie_m2'] = $tiposContruccion['ConstruccionesPrivativas']['Superficie'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Valor_Unitario'] = $tiposContruccion['ConstruccionesPrivativas']['CostoUnitarioDeReposicionNuevo'];
                if($tipoDeAvaluo ==  "Catastral"){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Edad'] = $tiposContruccion['ConstruccionesPrivativas']['Edad'];
                }
                //$infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Fco'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveConservacion'];
                //$infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['FRe'] = $tiposContruccion['ConstruccionesPrivativas']['FactorResultante'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Costo_Fraccion'] = $tiposContruccion['ConstruccionesPrivativas']['CostoDeLaFraccionN'];            
            }
    
            if(isset($tiposContruccion['ConstruccionesPrivativas'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesPrivativas'] as $construccionPrivativa){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Fracc'] = $control + 1;
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Descripcion'] = $construccionPrivativa['Descripcion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Uso'] = $construccionPrivativa['ClaveUso'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Clase'] = $this->modelFis->getClase($construccionPrivativa['ClaveClase']);
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Superficie_m2'] = $construccionPrivativa['Superficie'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor_Unitario'] = $construccionPrivativa['CostoUnitarioDeReposicionNuevo'];
                    if($tipoDeAvaluo ==  "Catastral"){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Edad'] = $construccionPrivativa['Edad'];
                    }
                    //$infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Fco'] = $construccionPrivativa['ClaveConservacion'];
                    //$infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['FRe'] = $construccionPrivativa['FactorResultante'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Costo_Fraccion'] = $construccionPrivativa['CostoDeLaFraccionN'];
                                    
                    $control = $control + 1;
                }
            }
    
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Superficie'] = $tiposContruccion['SuperficieTotalDeConstruccionesPrivativas'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Construcciones_Privativas'] = $tiposContruccion['ValorTotalDeConstruccionesPrivativas'];
            
            if(isset($tiposContruccion['ConstruccionesComunes']['@attributes'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Fracc'] = 1;
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Descripcion'] = $tiposContruccion['ConstruccionesComunes']['Descripcion'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Uso'] = $tiposContruccion['ConstruccionesComunes']['ClaveUso'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesComunes']['ClaveClase']);
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Superficie_m2'] = $tiposContruccion['ConstruccionesComunes']['Superficie'];

                if(isset($tiposContruccion['ConstruccionesComunes']['CostoUnitarioDeReposicionNuevo']) && isset($tiposContruccion['ConstruccionesComunes']['CostoUnitarioDeReposicionNuevo'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor_Unitario'] = $tiposContruccion['ConstruccionesComunes']['CostoUnitarioDeReposicionNuevo'];
                }

                if(isset($tiposContruccion['ConstruccionesComunes']['ValorunitariodereposicionNuevo']) && isset($tiposContruccion['ConstruccionesComunes']['ValorunitariodereposicionNuevo'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor_Unitario'] = $tiposContruccion['ConstruccionesComunes']['ValorunitariodereposicionNuevo'];
                }
                
                if($tipoDeAvaluo ==  "Catastral"){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Edad'] = $tiposContruccion['ConstruccionesComunes']['Edad'];
                }
                //$infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Fco'] = $tiposContruccion['ConstruccionesComunes']['ClaveConservacion'];
                //$infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['FRe'] = $tiposContruccion['ConstruccionesComunes']['FactorResultante'];
                if(isset($tiposContruccion['ConstruccionesComunes']['CostoDeLaFraccionN']) && !is_array($tiposContruccion['ConstruccionesComunes']['CostoDeLaFraccionN'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Costo_Fraccion'] = $tiposContruccion['ConstruccionesComunes']['CostoDeLaFraccionN'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['PorcentajeIndivisoComunes']) && !is_array($tiposContruccion['ConstruccionesComunes']['PorcentajeIndivisoComunes'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Indiviso'] = $tiposContruccion['ConstruccionesComunes']['PorcentajeIndivisoComunes'];
                }   
            }
    
            if(isset($tiposContruccion['ConstruccionesComunes'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesComunes'] as $construccionComun){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Fracc'] = $control + 1;
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Descripcion'] = $construccionComun['Descripcion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Uso'] = $construccionComun['ClaveUso'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Clase'] = $this->modelFis->getClase($construccionComun['ClaveClase']);
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Superficie_m2'] = $construccionComun['Superficie'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor_Unitario'] = $construccionComun['CostoUnitarioDeReposicionNuevo'];
                    if($tipoDeAvaluo ==  "Catastral"){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Edad'] = $construccionComun['Edad'];
                    }
                    //$infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Fco'] = $construccionComun['ClaveConservacion'];
                    //$infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['FRe'] = $construccionComun['FactorResultante'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Costo_Fraccion'] = $construccionComun['CostoDeLaFraccionN'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Indiviso'] = $construccionComun['PorcentajeIndivisoComunes'];
                    
                    $control = $control + 1;
                }
            }
    
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Superficie'] = $tiposContruccion['SuperficieTotalDeConstruccionesComunes'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes'] = $tiposContruccion['ValorTotalDeConstruccionesComunes'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Valor_Total_De_Las_Construcciones'] = $tiposContruccion['ValorTotalDeConstruccionesComunes'] + $tiposContruccion['ValorTotalDeLasConstruccionesComunesProIndiviso'];
            

        }else{

            if(isset($tiposContruccion['ConstruccionesPrivativas']['@attributes'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Tipo'] = 1;
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Descripcion'] = $tiposContruccion['ConstruccionesPrivativas']['Descripcion'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Uso'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveUso'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Numero_Niveles_Tipo'] = $tiposContruccion['ConstruccionesPrivativas']['NumeroDeNivelesDelTipo'];
                if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ClaveClase'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']);
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['ValorUnitarioCatastral']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['ValorUnitarioCatastral'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Valor_Unitario'] = $tiposContruccion['ConstruccionesPrivativas']['ValorUnitarioCatastral'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['DepreciacionPorEdad']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['DepreciacionPorEdad'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Depreciacion_Por_Edad'] = $tiposContruccion['ConstruccionesPrivativas']['DepreciacionPorEdad'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['CostoDeLaFraccionN']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['CostoDeLaFraccionN'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Costo'] = $tiposContruccion['ConstruccionesPrivativas']['CostoDeLaFraccionN'];
                }
                if(isset($tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles']) && !is_array($tiposContruccion['ConstruccionesPrivativas']['Superficie'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Sup'] = $tiposContruccion['ConstruccionesPrivativas']['Superficie'];
                }
                                
            }
    
            if(isset($tiposContruccion['ConstruccionesPrivativas'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesPrivativas'] as $construccionPrivativa){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Tipo'] = $control + 1;
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Descripcion'] = $construccionPrivativa['Descripcion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Uso'] = $construccionPrivativa['ClaveUso'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Numero_Niveles_Tipo'] = $construccionPrivativa['NumeroDeNivelesDelTipo'];
                    if(isset($construccionPrivativa['ClaveRangoDeNiveles']) && !is_array($construccionPrivativa['ClaveRangoDeNiveles'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Clave_Rango_Niveles'] = $construccionPrivativa['ClaveRangoDeNiveles'];
                    }
                    if(isset($construccionPrivativa['ClaveClase']) && !is_array($construccionPrivativa['ClaveClase'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Clase'] = $this->modelFis->getClase($construccionPrivativa['ClaveClase']);
                    }
                    if(isset($construccionPrivativa['ValorUnitarioCatastral']) && !is_array($construccionPrivativa['ValorUnitarioCatastral'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor_Unitario'] = $construccionPrivativa['ValorUnitarioCatastral'];
                    }
                    if(isset($construccionPrivativa['DepreciacionPorEdad']) && !is_array($construccionPrivativa['DepreciacionPorEdad'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Depreciacion_Por_Edad'] = $construccionPrivativa['DepreciacionPorEdad'];
                    }
                    if(isset($construccionPrivativa['CostoDeLaFraccionN']) && !is_array($construccionPrivativa['CostoDeLaFraccionN'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Costo'] = $construccionPrivativa['CostoDeLaFraccionN'];
                    }
                    if(isset($construccionPrivativa['Superficie']) && !is_array($construccionPrivativa['Superficie'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Sup'] = $construccionPrivativa['Superficie'];
                    }
                                    
                    $control = $control + 1;
                }
            }
    
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Superficie'] = $tiposContruccion['SuperficieTotalDeConstruccionesPrivativas'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Construcciones_Privativas'] = $tiposContruccion['ValorTotalDeConstruccionesPrivativas'];
            
            if(isset($tiposContruccion['ConstruccionesComunes']['@attributes'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Tipo'] = 1;
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Descripcion'] = $tiposContruccion['ConstruccionesComunes']['Descripcion'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Uso'] = $tiposContruccion['ConstruccionesComunes']['ClaveUso'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Numero_Niveles_Tipo'] = $tiposContruccion['ConstruccionesComunes']['NumeroDeNivelesDelTipo'];
                if(isset($tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ClaveClase']) && !is_array($tiposContruccion['ConstruccionesComunes']['ClaveClase'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesComunes']['ClaveClase']);
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['ValorUnitarioCatastral']) && !is_array($tiposContruccion['ConstruccionesComunes']['ValorUnitarioCatastral'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor_Unitario'] = $tiposContruccion['ConstruccionesComunes']['ValorUnitarioCatastral'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['DepreciacionPorEdad']) && !is_array($tiposContruccion['ConstruccionesComunes']['DepreciacionPorEdad'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Depreciacion_Por_Edad'] = $tiposContruccion['ConstruccionesComunes']['DepreciacionPorEdad'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['CostoDeLaFraccionN']) && !is_array($tiposContruccion['ConstruccionesComunes']['CostoDeLaFraccionN'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Costo'] = $tiposContruccion['ConstruccionesComunes']['CostoDeLaFraccionN'];
                }
                if(isset($tiposContruccion['ConstruccionesComunes']['Superficie']) && !is_array($tiposContruccion['ConstruccionesComunes']['Superficie'])){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Sup'] = $tiposContruccion['ConstruccionesComunes']['Superficie'];                          
                }
            }
    
            if(isset($tiposContruccion['ConstruccionesComunes'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesComunes'] as $construccionComun){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Tipo'] = $control + 1;
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Descripcion'] = $construccionComun['Descripcion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Uso'] = $construccionComun['ClaveUso'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Numero_Niveles_Tipo'] = $construccionComun['NumeroDeNivelesDelTipo'];
                    if(isset($construccionComun['ClaveRangoDeNiveles']) && !is_array($construccionComun['ClaveRangoDeNiveles'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Clave_Rango_Niveles'] = $construccionComun['ClaveRangoDeNiveles'];
                    }
                    if(isset($construccionComun['ClaveClase']) && !is_array($construccionComun['ClaveClase'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Clase'] = $this->modelFis->getClase($construccionComun['ClaveClase']);
                    }
                    if(isset($construccionComun['ValorUnitarioCatastral']) && !is_array($construccionComun['ValorUnitarioCatastral'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor_Unitario'] = $construccionComun['ValorUnitarioCatastral'];
                    }
                    if(isset($construccionComun['DepreciacionPorEdad']) && !is_array($construccionComun['DepreciacionPorEdad'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Depreciacion_Por_Edad'] = $construccionComun['DepreciacionPorEdad'];
                    }
                    if(isset($construccionComun['CostoDeLaFraccionN']) && !is_array($construccionComun['CostoDeLaFraccionN'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Costo'] = $construccionComun['CostoDeLaFraccionN'];
                    }
                    if(isset($construccionComun['Superficie']) && !is_array($construccionComun['Superficie'])){
                        $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Sup'] = $construccionComun['Superficie'];                                        
                    }
                    $control = $control + 1;
                }
            }
    
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Superficie'] = $tiposContruccion['SuperficieTotalDeConstruccionesComunes'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes'] = $tiposContruccion['ValorTotalDeConstruccionesComunes'];
            
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes_Por_Indiviso'] = $tiposContruccion['ValorTotalDeLasConstruccionesComunesProIndiviso'];
            

        }

        /***********************************************************************************************************************************************************************/ 

        $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios'] = array();
        $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Totales'] = array();
        $control = 0;
        if(isset($elementosAccesorios['Privativas']['@attributes'])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'] = array();
            $control = 0;
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'] = array();
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'E';
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $elementosAccesorios['Privativas']['ClaveElementoAccesorio'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $elementosAccesorios['Privativas']['DescripcionElementoAccesorio'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $elementosAccesorios['Privativas']['CantidadElementoAccesorio'];
            if(isset($elementosAccesorios['Privativas']['CostoUnitarioElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Costo_Unitario'] = $elementosAccesorios['Privativas']['CostoUnitarioElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Privativas']['EdadElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $elementosAccesorios['Privativas']['EdadElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Privativas']['ImporteElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $elementosAccesorios['Privativas']['ImporteElementoAccesorio'];
            }
            
            $control = $control + 1;
        }

        if(isset($elementosAccesorios['Privativas'][0])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'] = array();
            $control = 0;
            foreach($elementosAccesorios['Privativas'] as $elementoAccesorio){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'E';
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $elementoAccesorio['ClaveElementoAccesorio'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $elementoAccesorio['DescripcionElementoAccesorio'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $elementoAccesorio['CantidadElementoAccesorio'];
                if(isset($elementoAccesorio['CostoUnitarioElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Costo_Unitario'] = $elementoAccesorio['CostoUnitarioElementoAccesorio'];
                }
                if(isset($elementoAccesorio['EdadElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $elementoAccesorio['EdadElementoAccesorio'];
                }
                if(isset($elementoAccesorio['ImporteElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $elementoAccesorio['ImporteElementoAccesorio'];
                }    
                                
                $control = $control + 1;
            }
        }

        if(isset($obrasComplementarias['Privativas']['@attributes'])){  
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'O';
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $obrasComplementarias['Privativas']['ClaveObraComplementaria'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $obrasComplementarias['Privativas']['DescripcionObraComplementaria'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $obrasComplementarias['Privativas']['CantidadObraComplementaria'];
            if(isset($obrasComplementarias['Privativas']['CostoUnitarioObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Costo_Unitario'] = $obrasComplementarias['Privativas']['CostoUnitarioObraComplementaria'];
            }
            if(isset($obrasComplementarias['Privativas']['EdadObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $obrasComplementarias['Privativas']['EdadObraComplementaria'];
            }
            if(isset($obrasComplementarias['Privativas']['ImporteObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $obrasComplementarias['Privativas']['ImporteObraComplementaria'];
            }    
            $control = $control + 1;            
        }

        if(isset($obrasComplementarias['Privativas'][0])){    
            foreach($obrasComplementarias['Privativas'] as $obraComplementaria){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'O';
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $obraComplementaria['ClaveObraComplementaria'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $obraComplementaria['DescripcionObraComplementaria'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $obraComplementaria['CantidadObraComplementaria'];
                if(isset($obraComplementaria['CostoUnitarioObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Costo_Unitario'] = $obraComplementaria['CostoUnitarioObraComplementaria'];
                }
                if(isset($obraComplementaria['EdadObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $obraComplementaria['EdadObraComplementaria'];
                }
                if(isset($obraComplementaria['ImporteObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $obraComplementaria['ImporteObraComplementaria'];                
                }    
                $control = $control + 1;
            }
        }

        /***************************************************************************************************************************************************/
        if(isset($instalacionesEspeciales['Privativas']['@attributes'])){  
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'IE';
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $instalacionesEspeciales['Privativas']['ClaveInstalacionEspecial'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $instalacionesEspeciales['Privativas']['DescripcionInstalacionEspecial'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $instalacionesEspeciales['Privativas']['CantidadInstalacionEspecial'];
            if(isset($instalacionesEspeciales['Privativas']['CostoUnitarioInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Costo_Unitario'] = $instalacionesEspeciales['Privativas']['CostoUnitarioInstalacionEspecial'];
            }
            if(isset($instalacionesEspeciales['Privativas']['EdadInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $instalacionesEspeciales['Privativas']['EdadInstalacionEspecial'];
            }
            if(isset($instalacionesEspeciales['Privativas']['ImporteInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $instalacionesEspeciales['Privativas']['ImporteInstalacionEspecial'];
            }    
            $control = $control + 1;            
        }

        if(isset($instalacionesEspeciales['Privativas'][0])){   
            foreach($instalacionesEspeciales['Privativas'] as $instalacionEspeciales){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control][0] = 'IE';
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Clave'] = $instalacionEspeciales['ClaveInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Concepto'] = $instalacionEspeciales['DescripcionInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Cantidad'] = $instalacionEspeciales['CantidadInstalacionEspecial'];
                if(isset($instalacionEspeciales['CostoUnitarioInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Costo_Unitario'] = $instalacionEspeciales['CostoUnitarioInstalacionEspecial'];
                }
                if(isset($instalacionEspeciales['EdadInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Edad'] = $instalacionEspeciales['EdadInstalacionEspecial'];
                }
                if(isset($instalacionEspeciales['ImporteInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Importe'] = $instalacionEspeciales['ImporteInstalacionEspecial'];                
                }    
                $control = $control + 1;
            }
        }

        /******************************************************************************************************************************************************************/

        if(isset($elementosAccesorios['Comunes']['@attributes'])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'] = array();
            $control = 0;            
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'E';
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $elementosAccesorios['Comunes']['ClaveElementoAccesorio'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $elementosAccesorios['Comunes']['DescripcionElementoAccesorio'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $elementosAccesorios['Comunes']['CantidadElementoAccesorio'];
            if(isset($elementosAccesorios['Comunes']['CostoUnitarioElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Costo_Unitario'] = $elementosAccesorios['Comunes']['CostoUnitarioElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Comunes']['EdadElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $elementosAccesorios['Comunes']['EdadElementoAccesorio'];
            }
            if(isset($elementosAccesorios['Comunes']['ImporteElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $elementosAccesorios['Comunes']['ImporteElementoAccesorio'];
            }    
            $control = $control + 1;          
            
        }

        if(isset($elementosAccesorios['Comunes'][0])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'] = array();
            $control = 0;
            foreach($elementosAccesorios['Comunes'] as $elementoAccesorio){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'E';
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $elementoAccesorio['ClaveElementoAccesorio'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $elementoAccesorio['DescripcionElementoAccesorio'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $elementoAccesorio['CantidadElementoAccesorio'];
                if(isset($elementoAccesorio['CostoUnitarioElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Costo_Unitario'] = $elementoAccesorio['CostoUnitarioElementoAccesorio'];
                }
                if(isset($elementoAccesorio['EdadElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $elementoAccesorio['EdadElementoAccesorio'];
                }
                if(isset($elementoAccesorio['ImporteElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $elementoAccesorio['ImporteElementoAccesorio'];
                }

                $control = $control + 1;
            }
        }

        if(isset($obrasComplementarias['Comunes']['@attributes'])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'] = array();
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'O';
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $obrasComplementarias['Comunes']['ClaveObraComplementaria'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $obrasComplementarias['Comunes']['DescripcionObraComplementaria'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $obrasComplementarias['Comunes']['CantidadObraComplementaria'];
            if(isset($obrasComplementarias['Comunes']['CostoUnitarioObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Costo_Unitario'] = $obrasComplementarias['Comunes']['CostoUnitarioObraComplementaria'];
            }
            if(isset($obrasComplementarias['Comunes']['EdadObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $obrasComplementarias['Comunes']['EdadObraComplementaria'];
            }
            if(isset($obrasComplementarias['Comunes']['ImporteObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $obrasComplementarias['Comunes']['ImporteObraComplementaria'];
            }
            
            $control = $control + 1;   
        }

        if(isset($obrasComplementarias['Comunes'][0])){            
            foreach($obrasComplementarias['Comunes'] as $obraComplementaria){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'O';
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $obraComplementaria['ClaveObraComplementaria'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $obraComplementaria['DescripcionObraComplementaria'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $obraComplementaria['CantidadObraComplementaria'];
                if(isset($obraComplementaria['CostoUnitarioObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Costo_Unitario'] = $obraComplementaria['CostoUnitarioObraComplementaria'];
                }
                if(isset($obraComplementaria['EdadObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $obraComplementaria['EdadObraComplementaria'];
                }
                if(isset($obraComplementaria['ImporteObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $obraComplementaria['ImporteObraComplementaria'];                
                }

                $control = $control + 1;
            }
        }

        /***************************************************************************************************************************************************/
        if(isset($instalacionesEspeciales['Comunes']['@attributes'])){  
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'IE';
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $instalacionesEspeciales['Comunes']['ClaveInstalacionEspecial'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $instalacionesEspeciales['Comunes']['DescripcionInstalacionEspecial'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $instalacionesEspeciales['Comunes']['CantidadInstalacionEspecial'];
            if(isset($instalacionesEspeciales['Comunes']['CostoUnitarioInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Costo_Unitario'] = $instalacionesEspeciales['Comunes']['CostoUnitarioInstalacionEspecial'];
            }
            if(isset($instalacionesEspeciales['Comunes']['EdadInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $instalacionesEspeciales['Comunes']['EdadInstalacionEspecial'];
            }
            if(isset($instalacionesEspeciales['Comunes']['ImporteInstalacionEspecial'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $instalacionesEspeciales['Comunes']['ImporteInstalacionEspecial'];
            }    
            $control = $control + 1;            
        }

        if(isset($instalacionesEspeciales['Comunes'][0])){   
            foreach($instalacionesEspeciales['Comunes'] as $instalacionEspeciales){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'IE';
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $instalacionEspeciales['ClaveInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $instalacionEspeciales['DescripcionInstalacionEspecial'];
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $instalacionEspeciales['CantidadInstalacionEspecial'];
                if(isset($instalacionEspeciales['CostoUnitarioInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Costo_Unitario'] = $instalacionEspeciales['CostoUnitarioInstalacionEspecial'];
                }
                if(isset($instalacionEspeciales['EdadInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Edad'] = $instalacionEspeciales['EdadInstalacionEspecial'];
                }
                if(isset($instalacionEspeciales['ImporteInstalacionEspecial'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Importe'] = $instalacionEspeciales['ImporteInstalacionEspecial'];                
                }    
                $control = $control + 1;
            }
        }

        /***************************************************************************************************************************************************/

        $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Totales']['Indiviso_Unidad_Que_Se_Valua'] = $terreno['Indiviso'] <= 1 ? $terreno['Indiviso'] * 100 : $terreno['Indiviso'];
        if((isset($elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasPrivativas']) && isset($elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasComunes'])) || (isset($elementosConstruccion['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas']) && isset($elementosConstruccion['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes']))){
        //if(isset($elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasPrivativas']) && isset($elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasComunes'])){
            $sumatoria = 0;
            if(isset($elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasPrivativas']) && isset($elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasComunes'])){
                $sumatoria = $sumatoria + $elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasPrivativas'] + $elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasComunes'];
            }

            if(isset($elementosConstruccion['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas']) && isset($elementosConstruccion['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes'])){
                if(is_array($elementosConstruccion['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas'])){
                    $sumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas = 0;
                }else{
                    $sumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas = $elementosConstruccion['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas'];
                }
                //Log::info("CONTENIDOOOO ".json_encode($elementosConstruccion['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes']));
                if(is_array($elementosConstruccion['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes'])){
                    $sumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes = 0;
                }else{
                    $sumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes = $elementosConstruccion['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes'];
                }

                $sumatoria = $sumatoria + $sumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas + $sumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes;
            }

            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Totales']['Total_De_Las_Instalaciones'] = $sumatoria;
            
            //$infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Totales']['Total_De_Las_Instalaciones'] = $elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasPrivativas'] + $elementosConstruccion['SumatoriaTotalInstalacionesAccesoriosComplementariasComunes'];
        }

        $enfoqueCostos = $elementoPrincipal['EnfoqueDeCostos'];
        if(isset($enfoqueCostos['ImporteTotalDelEnfoqueDeCostos'])){ 
            $infoReimpresion['Indice_Fisico_Directo'] = $enfoqueCostos['ImporteTotalDelEnfoqueDeCostos'];
        }
        //print_r($infoReimpresion); exit();
        if($tipoDeAvaluo == 'Catastral'){
            $infoReimpresion['Importe_Instalaciones_Especiales_Elementos_Accesorios_Obras_Comp'] = $enfoqueCostos['ImporteInstalacionesEspeciales'];
            $infoReimpresion['Importe_Total_Valor_Catastral'] = $enfoqueCostos['ImporteTotalValorCatastral'];
            $infoReimpresion['Avance_Obra'] = $enfoqueCostos['AvanceDeObra'] <= 1 ? $enfoqueCostos['AvanceDeObra'] * 100 : $enfoqueCostos['AvanceDeObra'];
            $infoReimpresion['Importe_Total_Valor_Catastral_Obra_Proceso'] = $enfoqueCostos['ImporteTotalValorCatastralObraEnProceso'];

            /*$consideraciones = $elementoPrincipal['ConsideracionesPreviasAlAvaluo'];

            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'] = $consideraciones['ConsideracionesPreviasAlAvaluo'];*/
        }        

        if(isset($elementoPrincipal['ConsideracionesPreviasAlAvaluo'])){
            $consideraciones = $elementoPrincipal['ConsideracionesPreviasAlAvaluo'];
            if(isset($consideraciones['ConsideracionesPreviasAlAvaluo']['Consideraciones'])){
                if(is_array($consideraciones['ConsideracionesPreviasAlAvaluo']['Consideraciones'])){

                }else{

                    if($tipoDeAvaluo ==  "Catastral"){

                        if(isset($consideraciones['ConsideracionesPreviasAlAvaluo']['Consideraciones']) && !is_array($consideraciones['ConsideracionesPreviasAlAvaluo']['Consideraciones'])){
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'] = $consideraciones['ConsideracionesPreviasAlAvaluo']['Consideraciones'];
                        }else{
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'] = $consideraciones['ConsideracionesPreviasAlAvaluo'];
                        }
                        

                    }else{

                        $infoReimpresion['Consideraciones_Previas_Al_Avaluo'] = array();
                        $control = 0;

                        foreach($consideraciones['ConsideracionesPreviasAlAvaluo'] as $idCPA => $valCPA){
                            $idMayus = strtoupper($idCPA);
                            $consideraciones['ConsideracionesPreviasAlAvaluo'][$idMayus] = $valCPA;
                        }

                        if(isset($consideraciones['ConsideracionesPreviasAlAvaluo']['CONSIDERACIONES']) && !is_array($consideraciones['ConsideracionesPreviasAlAvaluo']['CONSIDERACIONES'])){
                            $control = $control + 1;
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control] = array();
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Titulo'] = "Consideraciones Previas Al Avalúo"; 
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Texto'] = $consideraciones['ConsideracionesPreviasAlAvaluo']['CONSIDERACIONES'];
                        }

                        if(isset($consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICAEXPOSICIONDEMOTIVOS']) && !is_array($consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICAEXPOSICIONDEMOTIVOS'])){
                            $control = $control + 1;
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control] = array();
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Titulo'] = "Memoria Técnica Exposición De Motivos"; 
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Texto'] = $consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICAEXPOSICIONDEMOTIVOS'];
                        }

                        if(isset($consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICADESGLOSEDEINFORMACION']) && !is_array($consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICADESGLOSEDEINFORMACION'])){
                            $control = $control + 1;
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control] = array();
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Titulo'] = "Memoria Técnica Desgloce De Información";
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Texto'] = $consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICADESGLOSEDEINFORMACION'];
                        }

                        if(isset($consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICADESCRIPCIONDECALCULOSREALIZADOS']) && !is_array($consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICADESCRIPCIONDECALCULOSREALIZADOS'])){
                            $control = $control + 1;
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control] = array();
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Titulo'] = "Memoria Técnica Descripción De Célculos Realizados"; 
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Texto'] = $consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICADESCRIPCIONDECALCULOSREALIZADOS'];
                        }

                        if(isset($consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICAOTROSPARASUSTENTO']) && !is_array($consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICAOTROSPARASUSTENTO'])){
                            $control = $control + 1;
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control] = array();
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Titulo'] = "Memoria Técnica Otros Para Sustento";
                            $infoReimpresion['Consideraciones_Previas_Al_Avaluo'][$control]['Texto'] = $consideraciones['ConsideracionesPreviasAlAvaluo']['MEMORIATECNICAOTROSPARASUSTENTO'];
                        }

                    }

                    


                    /*$infoReimpresion['Consideraciones_Previas_Al_Avaluo'] = $consideraciones['ConsideracionesPreviasAlAvaluo']['Consideraciones']."<br><br>".
                                                          $consideraciones['ConsideracionesPreviasAlAvaluo']['MemoriaTecnicaExposicionDeMotivos']."<br><br>".
                                                          $consideraciones['ConsideracionesPreviasAlAvaluo']['MemoriaTecnicaDesgloceDeInformacion']."<br><br>".
                                                          $consideraciones['ConsideracionesPreviasAlAvaluo']['MemoriaTecnicaDescripcionDeCalculosRealizados']."<br><br>".
                                                          $consideraciones['ConsideracionesPreviasAlAvaluo']['MemoriaTecnicaOtrosParaSustento'];*/
                }                
            }    
        }
        

        /*************************************************************************************************************************************************************/

        if(isset($enfoqueMercado['ConstruccionesEnRenta'])){
            $infoReimpresion['Renta_Estimada'] = array();
            $construccionesEnRenta = $enfoqueMercado['ConstruccionesEnRenta'];
            $investigacionProductosComparables = $construccionesEnRenta['InvestigacionProductosComparables']; 
            $control = 0;
            if(isset($investigacionProductosComparables['@attributes'])){
                $infoReimpresion['Renta_Estimada'][$control]['Ubicacion'] = $investigacionProductosComparables['Calle'].". ".$investigacionProductosComparables['Colonia'].". ".$investigacionProductosComparables['CodigoPostal'];
                $infoReimpresion['Renta_Estimada'][$control]['Superficie_m2'] = $investigacionProductosComparables['SuperficieVendiblePorUnidad'];
                $infoReimpresion['Renta_Estimada'][$control]['Renta_Mensual'] = $investigacionProductosComparables['PrecioSolicitado'];
                if(trim($investigacionProductosComparables['PrecioSolicitado']) == 0 && trim($investigacionProductosComparables['SuperficieVendiblePorUnidad']) == 0){
                    $infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = 0;    
                }else{
                    $infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = $investigacionProductosComparables['PrecioSolicitado'] / $investigacionProductosComparables['SuperficieVendiblePorUnidad'];    
                }
                //$infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = $investigacionProductosComparables['PrecioSolicitado'] / $investigacionProductosComparables['SuperficieVendiblePorUnidad'];
            }else{
                if(isset($investigacionProductosComparables[0])){                    
                    foreach($investigacionProductosComparables as $productoComparable){
                        $infoReimpresion['Renta_Estimada'][$control]['Ubicacion'] = $productoComparable['Calle'].". ".$productoComparable['Colonia'].". ".$productoComparable['CodigoPostal'];
                        $infoReimpresion['Renta_Estimada'][$control]['Superficie_m2'] = $productoComparable['SuperficieVendiblePorUnidad'];
                        $infoReimpresion['Renta_Estimada'][$control]['Renta_Mensual'] = $productoComparable['PrecioSolicitado'];
                        if(trim($productoComparable['PrecioSolicitado']) === 0 && trim($productoComparable['SuperficieVendiblePorUnidad']) === 0){
                            $infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = 0;    
                        }else{
                            $infoReimpresion['Renta_Estimada'][$control]['Renta_m2'] = $productoComparable['SuperficieVendiblePorUnidad'] == 0 ? $productoComparable['PrecioSolicitado'] : $productoComparable['PrecioSolicitado'] / $productoComparable['SuperficieVendiblePorUnidad'];    
                        }
                        

                        $control = $control + 1;
                    }
                }
            }
            
        }
        
        if(isset($elementoPrincipal['EnfoqueDeIngresos'])){
            $infoReimpresion['Analisis_Deducciones'] = array();
            $infoReimpresion['Analisis_Deducciones']['Totales'] = array();
            $enfoqueIngresos = $elementoPrincipal['EnfoqueDeIngresos'];
            $deducciones = $enfoqueIngresos['Deducciones'];
            
            $infoReimpresion['Analisis_Deducciones']['Vacios'] = $deducciones['Vacios'];
            $infoReimpresion['Analisis_Deducciones']['Impuesto_Predial'] = $deducciones['ImpuestoPredial'];
            $infoReimpresion['Analisis_Deducciones']['Servicio_Agua'] = $deducciones['ServicioDeAgua'];
            $infoReimpresion['Analisis_Deducciones']['Conserv_Mant'] = $deducciones['ConservacionYMantenimiento'];
            $infoReimpresion['Analisis_Deducciones']['Administracion'] = $deducciones['Administracion'];
            $infoReimpresion['Analisis_Deducciones']['Energia_Electrica'] = $deducciones['ServicioEnergiaElectrica'];
            $infoReimpresion['Analisis_Deducciones']['Seguros'] = $deducciones['Seguros'];
            $infoReimpresion['Analisis_Deducciones']['Otros'] = $deducciones['Otros'];
            $infoReimpresion['Analisis_Deducciones']['Depreciacion_Fiscal'] = $deducciones['DepreciacionFiscal'];
            $infoReimpresion['Analisis_Deducciones']['Deducc_Fiscales'] = $deducciones['DeduccionesFiscales'];
            $infoReimpresion['Analisis_Deducciones']['ISR'] = $deducciones['ImpuestoSobreLaRenta'];

            $infoReimpresion['Analisis_Deducciones']['Totales']['Suma'] = $deducciones['DeduccionesMensuales'];
            $infoReimpresion['Analisis_Deducciones']['Totales']['Deducciones_Mensuales'] = $deducciones['DeduccionesMensuales'];
            $infoReimpresion['Analisis_Deducciones']['Totales']['Producto_Liquido_Mensual'] = $enfoqueIngresos['ProductoLiquidoAnual'] / 12;
            $infoReimpresion['Analisis_Deducciones']['Totales']['Producto_Liquido_Anual'] = $enfoqueIngresos['ProductoLiquidoAnual'];
            $infoReimpresion['Analisis_Deducciones']['Totales']['Tasa_Capitalizacion_Aplicable'] = $enfoqueIngresos['TasaDeCapitalizacionAplicable'] <= 1 ? $enfoqueIngresos['TasaDeCapitalizacionAplicable'] * 100 : $enfoqueIngresos['TasaDeCapitalizacionAplicable'];

            $infoReimpresion['Resultado_Aplicacion_Enfoque_Ingresos'] = $enfoqueIngresos['ImporteEnfoqueDeIngresos'];
        }
        
        if(isset($elementoPrincipal['EnfoqueDeIngresos']['ImporteEnfoqueDeIngresos'])){
            $infoReimpresion['Valor_Capitalizacion_Rentas'] = $elementoPrincipal['EnfoqueDeIngresos']['ImporteEnfoqueDeIngresos']; 
        }

        if(isset($elementoPrincipal['EnfoqueDeMercado'])){
            $infoReimpresion['Valor_Mercado_Construcciones'] = $enfoqueMercado['ValorDeMercadoDelInmueble'];    
        }
        
        if(isset($elementoPrincipal['ConsideracionesPreviasALaConclusion'])){
            $consideraciones = $elementoPrincipal['ConsideracionesPreviasALaConclusion'];
            $infoReimpresion['Consideraciones'] = $consideraciones['ConsideracionesPreviasALaConclusion'];
        }

        $conclusionAvaluo = $elementoPrincipal['ConclusionDelAvaluo'];
        if(isset($conclusionAvaluo['ValorComercialDelInmueble'])){
            $infoReimpresion['Consideramos_Que_Valor_Comercial_Corresponde'] = $conclusionAvaluo['ValorComercialDelInmueble'];
        }
        
        if(isset($conclusionAvaluo['ValorCatastralDelInmueble'])){
            $infoReimpresion['Consideramos_Que_Valor_Catastral_Corresponde'] = $conclusionAvaluo['ValorCatastralDelInmueble'];
        } 

        if(isset($elementoPrincipal['ValorReferido'])){            
            $valorReferido = $elementoPrincipal['ValorReferido'];
            if(isset($valorReferido['ValorReferido'])){
                $infoReimpresion['Valor_Referido'] = array();
                $infoReimpresion['Valor_Referido']['Valor_Referido'] = $valorReferido['ValorReferido'];
                $infoReimpresion['Valor_Referido']['Fecha'] = $valorReferido['FechaDeValorReferido'];
                $infoReimpresion['Valor_Referido']['Factor'] = $valorReferido['FactorDeConversion'];
            }            
        }

        if(isset($identificacion['ClaveSociedad']) && !is_array($identificacion['ClaveSociedad'])){
            $infoReimpresion['Registro_TDF'] = $identificacion['ClaveSociedad']." ".$this->modelDocumentos->get_nombre_sociedad($identificacion['ClaveSociedad']);
        }

        if(isset($identificacion['ClaveValuador']) && !is_array($identificacion['ClaveValuador'])){
            $infoReimpresion['Perito_Valuador'] = $identificacion['ClaveValuador']." ".$this->modelDocumentos->get_nombre_perito($identificacion['ClaveValuador']);
        } 

        $anexoFotogrfico = $elementoPrincipal['AnexoFotografico'];
        $sujeto = $anexoFotogrfico['Sujeto'];
        $fotosInmuebleAvaluo = $sujeto['FotosInmuebleAvaluo'];
        $cuentaCatastral = $sujeto['CuentaCatastral'];

        if(isset($anexoFotogrfico['ComparableVentas'])){
            $fotosVenta = $anexoFotogrfico['ComparableVentas'];
        }
        if(isset($anexoFotogrfico['ComparableRentas'])){
            $fotosRenta = $anexoFotogrfico['ComparableRentas'];
        } 
        
        $infoReimpresion['Inmueble_Objeto_Avaluo'] = array();
        $cuentaAvaluo = $cuentaCatastral['Region']."-".$cuentaCatastral['Manzana']."-".$cuentaCatastral['Lote']."-".$cuentaCatastral['Localidad'];
            
        
        

        $control = 0;
        foreach($fotosInmuebleAvaluo as $fotoInmuebleAvaluo){
            if(isset($fotoInmuebleAvaluo['Foto']) && !is_array($fotoInmuebleAvaluo['Foto'])){
                $foto = $this->modelDocumentos->get_fichero_foto($fotoInmuebleAvaluo['Foto']);           
                $infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                $infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Cuenta_Catastral'] = $cuentaAvaluo;
                $infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Interior_O_Exterior'] = $fotoInmuebleAvaluo['InteriorOExterior'];
                $control = $control + 1;
            }
            
        }

        if(isset($fotosVenta)){

            $infoReimpresion['Inmueble_Venta'] = array();

            $control = 0;
            foreach($fotosVenta as $fotoVenta){ //echo $fotoVenta['FotosInmuebleAvaluo']['Foto']."\n";

                if(isset($fotoVenta['FotosInmuebleAvaluo'][0])){

                    foreach($fotoVenta['FotosInmuebleAvaluo'] as $fotoDet){

                        if(isset($fotoDet['Foto']) && !is_array($fotoDet['Foto'])){
                            $foto = $this->modelDocumentos->get_fichero_foto($fotoDet['Foto']);
                        }    
                        $infoReimpresion['Inmueble_Venta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                        $infoReimpresion['Inmueble_Venta'][$control]['Cuenta_Catastral'] = $fotoVenta['CuentaCatastral']['Region']."-".$fotoVenta['CuentaCatastral']['Manzana']."-".$fotoVenta['CuentaCatastral']['Lote']."-".$fotoVenta['CuentaCatastral']['Localidad'];
                        if(isset($fotoDet['InteriorOExterior']) && !is_array($fotoDet['InteriorOExterior'])){
                            $infoReimpresion['Inmueble_Venta'][$control]['Interior_O_Exterior'] = $fotoDet['InteriorOExterior'];
                        }else{
                            $infoReimpresion['Inmueble_Venta'][$control]['Interior_O_Exterior'] = '';
                        }

                        $control = $control + 1;

                    }

                }else{

                    if(isset($fotoVenta['FotosInmuebleAvaluo']['Foto']) && !is_array($fotoVenta['FotosInmuebleAvaluo']['Foto'])){
                        $foto = $this->modelDocumentos->get_fichero_foto($fotoVenta['FotosInmuebleAvaluo']['Foto']);
                    }    
                    $infoReimpresion['Inmueble_Venta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                    $infoReimpresion['Inmueble_Venta'][$control]['Cuenta_Catastral'] = $fotoVenta['CuentaCatastral']['Region']."-".$fotoVenta['CuentaCatastral']['Manzana']."-".$fotoVenta['CuentaCatastral']['Lote']."-".$fotoVenta['CuentaCatastral']['Localidad'];
                    if(isset($fotoVenta['FotosInmuebleAvaluo']['InteriorOExterior']) && !is_array($fotoVenta['FotosInmuebleAvaluo']['InteriorOExterior'])){
                        $infoReimpresion['Inmueble_Venta'][$control]['Interior_O_Exterior'] = $fotoVenta['FotosInmuebleAvaluo']['InteriorOExterior'];
                    }else{
                        $infoReimpresion['Inmueble_Venta'][$control]['Interior_O_Exterior'] = '';
                    }
                    $control = $control + 1;

                }
               
            }
        }
        
        if(isset($fotosRenta)){

            $infoReimpresion['Inmueble_Renta'] = array();

            $control = 0;
            foreach($fotosRenta as $fotoRenta){ //echo $fotoRenta['FotosInmuebleAvaluo']['Foto']."\n";

                if(isset($fotoRenta['FotosInmuebleAvaluo'][0])){

                    foreach($fotoRenta['FotosInmuebleAvaluo'] as $fotoDet){

                        if(isset($fotoDet['Foto']) && !is_array($fotoDet['Foto'])){
                            $foto = $this->modelDocumentos->get_fichero_foto($fotoDet['Foto']);
                        }    
                        $infoReimpresion['Inmueble_Renta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                        $infoReimpresion['Inmueble_Renta'][$control]['Cuenta_Catastral'] = $fotoRenta['CuentaCatastral']['Region']."-".$fotoRenta['CuentaCatastral']['Manzana']."-".$fotoRenta['CuentaCatastral']['Lote']."-".$fotoRenta['CuentaCatastral']['Localidad'];
                        if(isset($fotoDet['InteriorOExterior']) && !is_array($fotoDet['InteriorOExterior'])){
                            $infoReimpresion['Inmueble_Renta'][$control]['Interior_O_Exterior'] = $fotoDet['InteriorOExterior'];
                        }else{
                            $infoReimpresion['Inmueble_Renta'][$control]['Interior_O_Exterior'] = '';
                        }

                        $control = $control + 1;

                    }

                }else{

                    $foto = $this->modelDocumentos->get_fichero_foto($fotoRenta['FotosInmuebleAvaluo']['Foto']);
                    $infoReimpresion['Inmueble_Renta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                    $infoReimpresion['Inmueble_Renta'][$control]['Cuenta_Catastral'] = $fotoRenta['CuentaCatastral']['Region']."-".$fotoRenta['CuentaCatastral']['Manzana']."-".$fotoRenta['CuentaCatastral']['Lote']."-".$fotoRenta['CuentaCatastral']['Localidad'];
                    $infoReimpresion['Inmueble_Renta'][$control]['Interior_O_Exterior'] = $fotoRenta['FotosInmuebleAvaluo']['InteriorOExterior'];
                    $control = $control + 1;

                }
            }
            }        
            
        }  catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error al obtener Información del avalúo'], 500);
        }//exit();      

        return $infoReimpresion;    
        
    }
 
    public function cambiaTexto($texto){
        $cadenas = array("UBICACION"=>"UBICACIÓN",
        "AVALUO"=>"AVALÚO",
        "ubicacion"=>"ubicación",
        "avaluo"=>"avalúo");

        foreach($cadenas as $sin => $con){
            $texto = str_replace($sin,$con,$texto);
        }    
        return $texto;
    }

}