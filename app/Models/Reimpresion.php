<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Documentos;
use App\Models\Fis;
use Log;

class Reimpresion
{
    protected $modelDocumentos;
    protected $modelFis;

    public function infoAcuse($idavaluo){

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
        
        $arrInfoAcuse = array();
        $numeroUnico = DB::select("SELECT NUMEROUNICO FROM FEXAVA_AVALUO WHERE IDAVALUO = $idavaluo");
        $arrInfoAcuse['numeroUnico'] = $numeroUnico[0]->numerounico;
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
        $arrInfoAcuse['Ubicacion_Inmueble']['Delegacion'] = isset($ubicacionInmueble['Delegacion']) ? $ubicacionInmueble['Delegacion'] : $ubicacionInmueble['Alcaldia'];
        $arrInfoAcuse['Ubicacion_Inmueble']['Edificio'] = "-";
        $arrInfoAcuse['Ubicacion_Inmueble']['Lote'] = 0;
        $arrInfoAcuse['Ubicacion_Inmueble']['Cuenta_agua'] = $ubicacionInmueble['CuentaDeAgua'];

        
        $infoEscritura = DB::select("SELECT * FROM FEXAVA_ESCRITURA WHERE IDAVALUO = $idavaluo");
        $arrInfoEscritura = array_map("convierte_a_arreglo",$infoEscritura);
        $arrInfoAcuse['escritura'] = $arrInfoEscritura[0];

        $infoFuenteInformacion = DB::select("SELECT * FROM FEXAVA_FUENTEINFORMACIONLEG WHERE IDAVALUO = $idavaluo");
        $arrinfoFuenteInformacion = array_map("convierte_a_arreglo",$infoFuenteInformacion);
        $arrInfoAcuse['fuenteInformacionLegal'] = $arrinfoFuenteInformacion[0];
        return $arrInfoAcuse;        
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
        $infoReimpresion['Encabezado']['Registro_TDF'] = $identificacion['ClaveValuador'];

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Sociedad_Participa'] = array();
        /*$antecedentes = $elementoPrincipal['Antecedentes'];
        $solicitante = $antecedentes['Solicitante'];*/

        $infoSolicitante = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idAvaluo AND CODTIPOFUNCION = 'S'");
        $arrinfoSolicitante = array_map("convierte_a_arreglo",$infoSolicitante);
        $arrSolicitante = $arrinfoSolicitante[0];

        $infoReimpresion['Sociedad_Participa']['Valuador'] = $identificacion['ClaveValuador'];
        $infoReimpresion['Sociedad_Participa']['Fecha_del_Avaluo'] = $identificacion['FechaAvaluo'];
        $infoReimpresion['Sociedad_Participa']['Solicitante'] = array();
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Tipo_persona'] = $arrSolicitante['tipopersona'] == 'F' ? "Física" : "Moral";
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Nombre'] = $arrSolicitante['nombre']." ".$arrSolicitante['apellidopaterno']." ".$arrSolicitante['apellidomaterno'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Calle'] = $arrSolicitante['calle'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['No_Exterior'] = $arrSolicitante['numeroexterior'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['No_Interior'] = $arrSolicitante['numerointerior'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Colonia'] = $arrSolicitante['nombrecolonia'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['CP'] = $arrSolicitante['codigopostal'];
        $infoReimpresion['Sociedad_Participa']['Solicitante']['Delegacion'] = $arrSolicitante['nombredelegacion'];

        $infoReimpresion['Sociedad_Participa']['inmuebleQueSeValua'] = $arrFexava['region']."-".$arrFexava['manzana']."-".$arrFexava['lote']."-".$arrFexava['unidadprivativa']." ".$arrFexava['digitoverificador'];
        $infoReimpresion['Sociedad_Participa']['regimenDePropiedad'] = $this->modelDocumentos->get_regimen_propiedad($arrFexava['codregimenpropiedad']);

        $infoPropietario = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idAvaluo AND CODTIPOFUNCION = 'P'");
        $arrinfoPropietario = array_map("convierte_a_arreglo",$infoPropietario);
        $arrPropietario = $arrinfoPropietario[0];

        $infoReimpresion['Sociedad_Participa']['Propietario'] = array();
        $infoReimpresion['Sociedad_Participa']['Propietario']['Tipo_persona'] = $arrPropietario['tipopersona'] == 'F' ? "Física" : "Moral";
        $infoReimpresion['Sociedad_Participa']['Propietario']['Nombre'] = $arrPropietario['nombre']." ".$arrPropietario['apellidopaterno']." ".$arrPropietario['apellidomaterno'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['Calle'] = $arrPropietario['calle'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['No_Exterior'] = $arrPropietario['numeroexterior'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['No_Interior'] = $arrPropietario['numerointerior'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['Colonia'] = $arrPropietario['nombrecolonia'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['CP'] = $arrPropietario['codigopostal'];
        $infoReimpresion['Sociedad_Participa']['Propietario']['Delegacion'] = $arrPropietario['nombredelegacion'];

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
        $infoReimpresion['Ubicacion_Inmueble']['Delegacion'] = isset($ubicacionInmueble['Delegacion']) ? $ubicacionInmueble['Delegacion'] : $ubicacionInmueble['Alcaldia'];
        $infoReimpresion['Ubicacion_Inmueble']['Edificio'] = "-";
        $infoReimpresion['Ubicacion_Inmueble']['Lote'] = 0;
        $infoReimpresion['Ubicacion_Inmueble']['Cuenta_agua'] = $ubicacionInmueble['CuentaDeAgua'];

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

        $infoReimpresion['Superficie_Total_Segun']['Totales']['Superficie_Total_Terreno'] =  $terreno['SuperficieTotalDelTerreno'];

        $infoReimpresion['Topografia_Configuracion'] = array();
        $infoReimpresion['Topografia_Configuracion']['Topografia_Configuracion'] = $this->modelDocumentos->getTopografia($arrFexava['tecodtopografia']);
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
            $infoReimpresion['Construcciones_Privativas']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'];
            $infoReimpresion['Construcciones_Privativas']['Puntaje'] = $tiposContruccion['ConstruccionesPrivativas']['PuntajeDeClasificacion'];
            $infoReimpresion['Construcciones_Privativas']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']);
            $infoReimpresion['Construcciones_Privativas']['Edad'] = $tiposContruccion['ConstruccionesPrivativas']['Edad'];
            $infoReimpresion['Construcciones_Privativas']['Vida_Util_Total_Tipo'] = $tiposContruccion['ConstruccionesPrivativas']['VidaUtilTotalDelTipo'];
            $infoReimpresion['Construcciones_Privativas']['Vida_Util_Remanente'] = $tiposContruccion['ConstruccionesPrivativas']['VidaUtilRemanente'];
            $infoReimpresion['Construcciones_Privativas']['Conservacion'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveConservacion'];
            $infoReimpresion['Construcciones_Privativas']['Sup'] = $tiposContruccion['ConstruccionesPrivativas']['Superficie'];
        }

        if(isset($tiposContruccion['ConstruccionesPrivativas'][0])){
            $control = 0;
            foreach($tiposContruccion['ConstruccionesPrivativas'] as $construccionPrivativa){
                $infoReimpresion['Construcciones_Privativas'][$control]['Tipo'] = 'P';
                $infoReimpresion['Construcciones_Privativas'][$control]['Descripcion'] = $construccionPrivativa['Descripcion'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Uso'] = $construccionPrivativa['ClaveUso'];
                $infoReimpresion['Construcciones_Privativas'][$control]['No_Niveles_Tipo'] = $construccionPrivativa['NumeroDeNivelesDelTipo'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Clave_Rango_Niveles'] = $construccionPrivativa['ClaveRangoDeNiveles'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Puntaje'] = $construccionPrivativa['PuntajeDeClasificacion'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Clase'] = $this->modelFis->getClase($construccionPrivativa['ClaveClase']);
                $infoReimpresion['Construcciones_Privativas'][$control]['Edad'] = $construccionPrivativa['Edad'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Vida_Util_Total_Tipo'] = $construccionPrivativa['VidaUtilTotalDelTipo'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Vida_Util_Remanente'] = $construccionPrivativa['VidaUtilRemanente'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Conservacion'] = $construccionPrivativa['ClaveConservacion'];
                $infoReimpresion['Construcciones_Privativas'][$control]['Sup'] = $construccionPrivativa['Superficie'];
                $control = $control + 1;
            }
        }
        
        if(isset($tiposContruccion['ConstruccionesComunes']['@attributes'])){
            $infoReimpresion['Construcciones_Comunes']['Tipo'] = 'C';
            $infoReimpresion['Construcciones_Comunes']['Descripcion'] = $tiposContruccion['ConstruccionesComunes']['Descripcion'];
            $infoReimpresion['Construcciones_Comunes']['Uso'] = $tiposContruccion['ConstruccionesComunes']['ClaveUso'];
            $infoReimpresion['Construcciones_Comunes']['No_Niveles_Tipo'] = $tiposContruccion['ConstruccionesComunes']['NumeroDeNivelesDelTipo'];
            $infoReimpresion['Construcciones_Comunes']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'];
            $infoReimpresion['Construcciones_Comunes']['Puntaje'] = $tiposContruccion['ConstruccionesComunes']['PuntajeDeClasificacion'];
            $infoReimpresion['Construcciones_Comunes']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesComunes']['ClaveClase']);
            $infoReimpresion['Construcciones_Comunes']['Edad'] = $tiposContruccion['ConstruccionesComunes']['Edad'];
            $infoReimpresion['Construcciones_Comunes']['Vida_Util_Total_Tipo'] = $tiposContruccion['ConstruccionesComunes']['VidaUtilTotalDelTipo'];
            $infoReimpresion['Construcciones_Comunes']['Vida_Util_Remanente'] = $tiposContruccion['ConstruccionesComunes']['VidaUtilRemanente'];
            $infoReimpresion['Construcciones_Comunes']['Conservacion'] = $tiposContruccion['ConstruccionesComunes']['ClaveConservacion'];
            $infoReimpresion['Construcciones_Comunes']['Sup'] = $tiposContruccion['ConstruccionesComunes']['Superficie'];
        }

        if(isset($tiposContruccion['ConstruccionesComunes'][0])){
            $control = 0;
            foreach($tiposContruccion['ConstruccionesComunes'] as $construccionComun){
                $infoReimpresion['Construcciones_Comunes'][$control]['Tipo'] = 'C';
                $infoReimpresion['Construcciones_Comunes'][$control]['Descripcion'] = $construccionComun['Descripcion'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Uso'] = $construccionComun['ClaveUso'];
                $infoReimpresion['Construcciones_Comunes'][$control]['No_Niveles_Tipo'] = $construccionComun['NumeroDeNivelesDelTipo'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Clave_Rango_Niveles'] = $construccionComun['ClaveRangoDeNiveles'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Puntaje'] = $construccionComun['PuntajeDeClasificacion'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Clase'] = $this->modelFis->getClase($construccionComun['ClaveClase']);
                $infoReimpresion['Construcciones_Comunes'][$control]['Edad'] = $construccionComun['Edad'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Vida_Util_Total_Tipo'] = $construccionComun['VidaUtilTotalDelTipo'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Vida_Util_Remanente'] = $construccionComun['VidaUtilRemanente'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Conservacion'] = $construccionComun['ClaveConservacion'];
                $infoReimpresion['Construcciones_Comunes'][$control]['Sup'] = $construccionComun['Superficie'];
                $control = $control + 1;
            }
        }
        
        $infoReimpresion['Indiviso'] = $terreno['Indiviso'] <= 1 ? $terreno['Indiviso'] * 100 : $terreno['Indiviso'];
        $infoReimpresion['Vida_Util_Promedio_Inmueble'] = $descripcionInmueble['VidaUtilTotalPonderadaDelInmueble'];
        $infoReimpresion['Edad_Aproximada_Construccion'] = $descripcionInmueble['EdadPonderadaDelInmueble'];
        $infoReimpresion['Vida_Util_Remanente'] = $descripcionInmueble['VidaUtilRemanentePonderadaDelInmueble'];

        /************************************************************************************************************************************************************************/

        $elementosConstruccion = $elementoPrincipal['ElementosDeLaConstruccion'];
        $obraNegra = $elementosConstruccion['ObraNegra'];

        $infoReimpresion['Obra_Negra_Gruesa'] = array();

        $infoReimpresion['Obra_Negra_Gruesa']['Cimientos'] = $obraNegra['Cimentacion'];
        $infoReimpresion['Obra_Negra_Gruesa']['Estructura'] = $obraNegra['Estructura'];
        $infoReimpresion['Obra_Negra_Gruesa']['Muros'] = $obraNegra['Muros'];
        $infoReimpresion['Obra_Negra_Gruesa']['Entrepiso'] = $obraNegra['Entrepisos'];
        $infoReimpresion['Obra_Negra_Gruesa']['Techos'] = $obraNegra['Techos'];
        $infoReimpresion['Obra_Negra_Gruesa']['Azoteas'] = $obraNegra['Azoteas'];
        $infoReimpresion['Obra_Negra_Gruesa']['Bardas'] = $obraNegra['Bardas'];

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

        $infoReimpresion['Carpinteria']['Puertas_Interiores'] = $carpinteria['PuertasInteriores'];
        $infoReimpresion['Carpinteria']['Guardarropas'] = $carpinteria['Guardaropas'];
        $infoReimpresion['Carpinteria']['Muebles_Empotrados'] = $carpinteria['MueblesEmpotradosOFijos'];

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias'] = array();
        $hidraulicasSanitarias = $elementosConstruccion['InstalacionesHidraulicasYSanitrias'];

        $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias']['Muebles_Banio'] = $hidraulicasSanitarias['MueblesDeBanno'];
        $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Hidraulicos'] = $hidraulicasSanitarias['RamaleosHidraulicos'];
        $infoReimpresion['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Sanitarios'] = $hidraulicasSanitarias['RamaleosSanitarios'];
        if(isset($elementosConstruccion['InstalacionesElectricasYAlumbrado'])){
            $infoReimpresion['Instalaciones_Electricas_Alumbrados'] = $elementosConstruccion['InstalacionesElectricasYAlumbrado'];
        }    

        /************************************************************************************************************************************************************************/

        $infoReimpresion['Puertas_Ventaneria_Metalica'] = array();
        $puertasVentaneria = $elementosConstruccion['PuertasYVentaneriaMetalica'];

        $infoReimpresion['Puertas_Ventaneria_Metalica']['Herreria'] = $puertasVentaneria['Herreria'];
        $infoReimpresion['Puertas_Ventaneria_Metalica']['Ventaneria'] = $puertasVentaneria['Ventaneria'];

        $infoReimpresion['Vidrieria'] = $elementosConstruccion['Vidreria'];
        $infoReimpresion['Cerrajeria'] = $elementosConstruccion['Cerrajeria'];
        $infoReimpresion['Fachadas'] = $elementosConstruccion['Fachadas'];

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
                if(isset($elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Privativas']['Valor_Unitario'] = $elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio'];
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
                    if(isset($elementoAccesorio['ValorUnitarioElementoAccesorio'])){
                        $infoReimpresion['Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $elementoAccesorio['ValorUnitarioElementoAccesorio'];
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
                if(isset($elementosAccesorios['Comunes']['ValorUnitarioElementoAccesorio'])){
                    $infoReimpresion['Elementos_Accesorios']['Comunes']['Valor_Unitario'] = $elementosAccesorios['Comunes']['ValorUnitarioElementoAccesorio'];
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
                    if(isset($elementoAccesorio['ValorUnitarioElementoAccesorio'])){
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
                if(isset($obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Privativas']['Valor_Unitario'] = $obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria'];
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
                    if(isset($obraComplementaria['ValorUnitarioObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Privativas'][$control]['Valor_Unitario'] = $obraComplementaria['ValorUnitarioObraComplementaria'];
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
                if(isset($obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria'])){
                    $infoReimpresion['Obras_Complementarias']['Comunes']['Valor_Unitario'] = $obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria'];
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
                    if(isset($obraComplementaria['ValorUnitarioObraComplementaria'])){
                        $infoReimpresion['Obras_Complementarias']['Comunes'][$control]['Valor_Unitario'] = $obraComplementaria['ValorUnitarioObraComplementaria'];
                    }    
                    $control = $control + 1;
                }
            }
        }
        

        /*********************************************************************************************************************/

        if(isset($elementoPrincipal['EnfoqueDeMercado'])){
            $infoReimpresion['Terrenos']  = array();
            $infoReimpresion['Terrenos']['Terrenos_Directos'] = array();
            $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'] = array();
            $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'] = array();

            $enfoqueMercado = $elementoPrincipal['EnfoqueDeMercado'];
            $terrenos = $enfoqueMercado['Terrenos'];
            $terrenosDirectos = $terrenos['TerrenosDirectos'];
            
            $control = 0; 
            foreach($terrenosDirectos as $terrenoDirecto){
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Ubicacion'] = $terrenoDirecto['Calle'].". ".$terrenoDirecto['Colonia'].". ".$terrenoDirecto['CodigoPostal'].".";
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Descripcion'] = $terrenoDirecto['DescripcionDelPredio'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['C_U_S'] = $terrenoDirecto['CUS'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaUno'][$control]['Uso_Suelo'] = $terrenoDirecto['UsoDelSuelo'];

                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_Negociacion'] = $terrenoDirecto['FactorDeNegociacion'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Superficie'] = $terrenoDirecto['Superficie'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fzo'] = $terrenoDirecto['Fzo'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fub'] = $terrenoDirecto['Fub'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['FFr'] = $terrenoDirecto['FFr'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Ffo'] = $terrenoDirecto['Ffo'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fsu'] = $terrenoDirecto['Fsu'];
                if(isset($terrenoDirecto['Fot'])){
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_otro'] = $terrenoDirecto['Fot']['Valor'];                    
                }else{
                    $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['F_otro'] = '';
                }                
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Fre'] = $terrenoDirecto['Fre'];
                $infoReimpresion['Terrenos']['Terrenos_Directos']['TablaDos'][$control]['Precio_Solicitado'] = $terrenoDirecto['PrecioSolicitado'];

                $control = $control + 1;
            } //print_r($infoReimpresion); exit();

            $conclusionHomologacionTerrenos = $terrenos['ConclusionesHomologacionTerrenos'];

            $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos'] = array();
            $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Tierra_Promedio'] = $conclusionHomologacionTerrenos['ValorUnitarioDeTierraPromedio'];
            $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Tierra_Homologado'] = $conclusionHomologacionTerrenos['ValorUnitarioDeTierraHomologado'];
            $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Sin_Homologar_Minimo'] = $conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMinimo'];
            $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Sin_Homologar_Maximo'] = $conclusionHomologacionTerrenos['ValorUnitarioSinHomologarMaximo'];
            $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Homologado_Minimo'] = $conclusionHomologacionTerrenos['ValorUnitarioHomologadoMinimo'];
            $infoReimpresion['Terrenos']['Terrenos_Directos']['Conclusiones_Homologacion_Terrenos']['Valor_Unitario_Homologado_Maximo'] = $conclusionHomologacionTerrenos['ValorUnitarioHomologadoMaximo'];

            /************************************************************************************************************************************************************************/

            if(isset($terrenos['TerrenosResidual'])){
                $infoReimpresion['Terrenos']['Terrenos_Residuales'] = array();                
                $terrenosResidual = $terrenos['TerrenosResidual'];

                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Tipo_Producto_Inmoviliario_Propuesto'] = $terrenosResidual['TipoDeProductoInmobiliarioPropuesto'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Numero_Unidades_Vendibles'] = $terrenosResidual['NumeroDeUnidadesVendibles'];
                $infoReimpresion['Terrenos']['Terrenos_Residuales']['Superficie_Vendible_Unidad'] = $terrenosResidual['SuperficieVendiblePorUnidad'];

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
            $conclusionesHomologacionContruccionesVenta = $construccionesEnVenta['ConclusionesHomologacionConstruccionesEnVenta'];

            $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Promedio'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioPromedio'];
            $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Homologado'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologado'];
            $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Sin_Homolgar_Minimo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMinimo'];
            $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Sin_Homolgar_Maximo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioSinHomologarMaximo'];
            $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Homologado_Minimo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMinimo'];
            $infoReimpresion['Construcciones_En_Venta']['Conclusion_Homologacion_Contrucciones_Venta']['Valor_Unitario_Homologado_Maximo']  = $conclusionesHomologacionContruccionesVenta['ValorUnitarioHomologadoMaximo'];

            $infoReimpresion['Construcciones_En_Venta']['Valor_Unitario_Aplicable_Avaluo'] = $conclusionesHomologacionContruccionesVenta['ValorUnitarioAplicableAlAvaluo'];
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
                $conclusionesHomologacionContruccionesRenta = $construccionesEnRenta['ConclusionesHomologacionConstruccionesEnVenta'];

                $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Promedio'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioPromedio'];
                $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Homologado'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologado'];
                $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Sin_Homolgar_Minimo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMinimo'];
                $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Sin_Homolgar_Maximo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioSinHomologarMaximo'];
                $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Homologado_Minimo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMinimo'];
                $infoReimpresion['Construcciones_En_Renta']['Conclusion_Homologacion_Contrucciones_Renta']['Valor_Unitario_Homologado_Maximo']  = $conclusionesHomologacionContruccionesRenta['ValorUnitarioHomologadoMaximo'];

                $infoReimpresion['Construcciones_En_Renta']['Valor_Unitario_Aplicable_Avaluo'] = $conclusionesHomologacionContruccionesRenta['ValorUnitarioAplicableAlAvaluo'];

            }        

    }
        /************************************************************************************************************************************************************************/



        /************************************************************************************************************************************************************************/

        //$superficieDelTerreno = $terreno['SuperficieDelTerreno'];
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno'] = array();
        $infoReimpresion['Calculo_Del_Valor_Del_Terreno']['Totales'] = array();

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
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Fracc'] = $superficieDelTerreno['IdentificadorFraccionN1'];
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
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Fracc'] = $superficieDelTerreno['IdentificadorFraccionN1'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Descripcion'] = $construccionPrivativa['Descripcion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Uso'] = $construccionPrivativa['ClaveUso'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Clase'] = $this->modelFis->getClase($construccionPrivativa['ClaveClase']);
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Superficie_m2'] = $construccionPrivativa['Superficie'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor_Unitario'] = $construccionPrivativa['ValorunitariodereposicionNuevo'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Edad'] = $construccionPrivativa['Edad'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Fco'] = $construccionPrivativa['ClaveConservacion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['FRe'] = $construccionPrivativa['FactorResultante'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor_Fraccion'] = $construccionPrivativa['ValorDeLaFraccionN'];
                                    
                    $control = $control + 1;
                }
            }
    
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Superficie'] = $tiposContruccion['SuperficieTotalDeConstruccionesPrivativas'];
            $infoReimpresion['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Construcciones_Privativas'] = $tiposContruccion['ValorTotalDeConstruccionesPrivativas'];
            
            if(isset($tiposContruccion['ConstruccionesComunes']['@attributes'])){
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Fracc'] = $superficieDelTerreno['IdentificadorFraccionN1'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Descripcion'] = $tiposContruccion['ConstruccionesComunes']['Descripcion'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Uso'] = $tiposContruccion['ConstruccionesComunes']['ClaveUso'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesComunes']['ClaveClase']);
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Superficie_m2'] = $tiposContruccion['ConstruccionesComunes']['Superficie'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor_Unitario'] = $tiposContruccion['ConstruccionesComunes']['ValorunitariodereposicionNuevo'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Edad'] = $tiposContruccion['ConstruccionesComunes']['Edad'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Fco'] = $tiposContruccion['ConstruccionesComunes']['ClaveConservacion'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['FRe'] = $tiposContruccion['ConstruccionesComunes']['FactorResultante'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor_Fraccion'] = $tiposContruccion['ConstruccionesComunes']['ValorDeLaFraccionN'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Indiviso'] = $tiposContruccion['ConstruccionesComunes']['PorcentajeIndivisoComunes'];            
            }
    
            if(isset($tiposContruccion['ConstruccionesComunes'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesComunes'] as $construccionComun){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Fracc'] = $superficieDelTerreno['IdentificadorFraccionN1'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Descripcion'] = $construccionComun['Descripcion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Uso'] = $construccionComun['ClaveUso'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Clase'] = $this->modelFis->getClase($construccionComun['ClaveClase']);
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Superficie_m2'] = $construccionComun['Superficie'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor_Unitario'] = $construccionComun['ValorunitariodereposicionNuevo'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Edad'] = $construccionComun['Edad'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Fco'] = $construccionComun['ClaveConservacion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['FRe'] = $construccionComun['FactorResultante'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor_Fraccion'] = $construccionComun['ValorDeLaFraccionN'];
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
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesPrivativas']['ClaveRangoDeNiveles'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesPrivativas']['ClaveClase']);
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Valor_Unitario'] = $tiposContruccion['ConstruccionesPrivativas']['ValorUnitarioCatastral'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Depreciacion_Por_Edad'] = $tiposContruccion['ConstruccionesPrivativas']['DepreciacionPorEdad'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Valor'] = $tiposContruccion['ConstruccionesPrivativas']['ValorDeLaFraccionN'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Privativas']['Sup'] = $tiposContruccion['ConstruccionesPrivativas']['Superficie'];
                                
            }
    
            if(isset($tiposContruccion['ConstruccionesPrivativas'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesPrivativas'] as $construccionPrivativa){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Tipo'] = $control + 1;
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Descripcion'] = $construccionPrivativa['Descripcion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Uso'] = $construccionPrivativa['ClaveUso'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Numero_Niveles_Tipo'] = $construccionPrivativa['NumeroDeNivelesDelTipo'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Clave_Rango_Niveles'] = $construccionPrivativa['ClaveRangoDeNiveles'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Clase'] = $this->modelFis->getClase($construccionPrivativa['ClaveClase']);
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor_Unitario'] = $construccionPrivativa['ValorUnitarioCatastral'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Depreciacion_Por_Edad'] = $construccionPrivativa['DepreciacionPorEdad'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Valor'] = $construccionPrivativa['ValorDeLaFraccionN'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Privativas'][$control]['Sup'] = $construccionPrivativa['Superficie'];
                                    
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
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Clave_Rango_Niveles'] = $tiposContruccion['ConstruccionesComunes']['ClaveRangoDeNiveles'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Clase'] = $this->modelFis->getClase($tiposContruccion['ConstruccionesComunes']['ClaveClase']);
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor_Unitario'] = $tiposContruccion['ConstruccionesComunes']['ValorUnitarioCatastral'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Depreciacion_Por_Edad'] = $tiposContruccion['ConstruccionesComunes']['DepreciacionPorEdad'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Valor'] = $tiposContruccion['ConstruccionesComunes']['ValorDeLaFraccionN'];
                $infoReimpresion['Calculo_Valor_Construcciones']['Comunes']['Sup'] = $tiposContruccion['ConstruccionesComunes']['Superficie'];                          
            }
    
            if(isset($tiposContruccion['ConstruccionesComunes'][0])){
                $control = 0;
                foreach($tiposContruccion['ConstruccionesComunes'] as $construccionComun){
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Tipo'] = $control + 1;
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Descripcion'] = $construccionComun['Descripcion'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Uso'] = $construccionComun['ClaveUso'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Numero_Niveles_Tipo'] = $construccionComun['NumeroDeNivelesDelTipo'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Clave_Rango_Niveles'] = $construccionComun['ClaveRangoDeNiveles'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Clase'] = $this->modelFis->getClase($construccionComun['ClaveClase']);
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor_Unitario'] = $construccionComun['ValorUnitarioCatastral'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Depreciacion_Por_Edad'] = $construccionComun['DepreciacionPorEdad'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Valor'] = $construccionComun['ValorDeLaFraccionN'];
                    $infoReimpresion['Calculo_Valor_Construcciones']['Comunes'][$control]['Sup'] = $construccionComun['Superficie'];                                        
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
            if(isset($elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $elementosAccesorios['Privativas']['ValorUnitarioElementoAccesorio'];
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
                if(isset($elementoAccesorio['ValorUnitarioElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $elementoAccesorio['ValorUnitarioElementoAccesorio'];
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
            if(isset($obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $obrasComplementarias['Privativas']['ValorUnitarioObraComplementaria'];
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
                if(isset($obraComplementaria['ValorUnitarioObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Privativas'][$control]['Valor_Unitario'] = $obraComplementaria['ValorUnitarioObraComplementaria'];
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

        /******************************************************************************************************************************************************************/

        if(isset($elementosAccesorios['Comunes']['@attributes'])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'] = array();
            $control = 0;            
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control][0] = 'E';
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Clave'] = $elementosAccesorios['Comunes']['ClaveElementoAccesorio'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Concepto'] = $elementosAccesorios['Comunes']['DescripcionElementoAccesorio'];
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Cantidad'] = $elementosAccesorios['Comunes']['CantidadElementoAccesorio'];
            if(isset($elementosAccesorios['Comunes']['ValorUnitarioElementoAccesorio'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Valor_Unitario'] = $elementosAccesorios['Comunes']['ValorUnitarioElementoAccesorio'];
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
                if(isset($elementoAccesorio['ValorUnitarioElementoAccesorio'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Valor_Unitario'] = $elementoAccesorio['ValorUnitarioElementoAccesorio'];
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
            if(isset($obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria'])){
                $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Valor_Unitario'] = $obrasComplementarias['Comunes']['ValorUnitarioObraComplementaria'];
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
                if(isset($obraComplementaria['ValorUnitarioObraComplementaria'])){
                    $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Comunes'][$control]['Valor_Unitario'] = $obraComplementaria['ValorUnitarioObraComplementaria'];
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

        $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Totales']['Indiviso_Unidad_Que_Se_Valua'] = $terreno['Indiviso'] <= 1 ? $terreno['Indiviso'] * 100 : $terreno['Indiviso'];
        if(isset($elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasPrivativas']) && isset($elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasComunes'])){
            $infoReimpresion['Instalaciones_Especiales_Obras_Complementarias_Elementos_Accesorios']['Totales']['Total_De_Las_Instalaciones'] = $elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasPrivativas'] + $elementosConstruccion['ImporteTotalInstalacionesAccesoriosComplementariasComunes'];
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

        $infoReimpresion['Perito_Valuador'] = $this->modelDocumentos->get_nombre_perito($identificacion['ClaveValuador']);

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
            $infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
            $infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Cuenta_Catastral'] = $cuentaAvaluo;
            $infoReimpresion['Inmueble_Objeto_Avaluo'][$control]['Interior_O_Exterior'] = $fotoInmuebleAvaluo['InteriorOExterior'];
            $control = $control + 1;
        }

        if(isset($fotosVenta)){

            $infoReimpresion['Inmueble_Venta'] = array();

            $control = 0;
            foreach($fotosVenta as $fotoVenta){ //echo $fotoVenta['FotosInmuebleAvaluo']['Foto']."\n";
                $foto = $this->modelDocumentos->get_fichero_foto($fotoVenta['FotosInmuebleAvaluo']['Foto']);
                $infoReimpresion['Inmueble_Venta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                $infoReimpresion['Inmueble_Venta'][$control]['Cuenta_Catastral'] = $fotoVenta['CuentaCatastral']['Region']."-".$fotoVenta['CuentaCatastral']['Manzana']."-".$fotoVenta['CuentaCatastral']['Lote']."-".$fotoVenta['CuentaCatastral']['Localidad'];
                $infoReimpresion['Inmueble_Venta'][$control]['Interior_O_Exterior'] = $fotoVenta['FotosInmuebleAvaluo']['InteriorOExterior'];
                $control = $control + 1;
            }
        }
        
        if(isset($fotosRenta)){

            $infoReimpresion['Inmueble_Renta'] = array();

            $control = 0;
            foreach($fotosRenta as $fotoRenta){ //echo $fotoRenta['FotosInmuebleAvaluo']['Foto']."\n";
                $foto = $this->modelDocumentos->get_fichero_foto($fotoRenta['FotosInmuebleAvaluo']['Foto']);
                $infoReimpresion['Inmueble_Renta'][$control]['Foto'] = $foto == base64_encode(base64_decode($foto)) ? $foto : base64_encode($foto);
                $infoReimpresion['Inmueble_Renta'][$control]['Cuenta_Catastral'] = $fotoRenta['CuentaCatastral']['Region']."-".$fotoRenta['CuentaCatastral']['Manzana']."-".$fotoRenta['CuentaCatastral']['Lote']."-".$fotoRenta['CuentaCatastral']['Localidad'];
                $infoReimpresion['Inmueble_Renta'][$control]['Interior_O_Exterior'] = $fotoRenta['FotosInmuebleAvaluo']['InteriorOExterior'];
                $control = $control + 1;
            }
            }
        }  catch (\Throwable $th) {
            //Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error al obtener Información del avalúo'], 500);
        }//exit();      

        return $infoReimpresion;
        
        /*$arrTablas = array('CROQUIS','FEXAVA_DATOSPERSONAS','FEXAVA_FUENTEINFORMACIONLEG','FEXAVA_ESCRITURA','FEXAVA_SUPERFICIE','FEXAVA_TIPOCONSTRUCCION','FEXAVA_ELEMENTOSCONST','FEXAVA_TERRENOMERCADO','FEXAVA_DATOSTERRENOS','FEXAVA_CONSTRUCCIONESMER','FEXAVA_ENFOQUECOSTESCAT','FEXAVA_FOTOAVALUO','FEXAVA_FOTOCOMPARABLE');
        $arrElementosConst = array('FEXAVA_OBRANEGRA','FEXAVA_REVESTIMIENTOACABADO','FEXAVA_CARPINTERIA','FEXAVA_INSTALACIONHIDSAN','FEXAVA_PUERTASYVENTANERIA','FEXAVA_ELEMENTOSEXTRA');
        
        $arrFexava = array();

        $infoFexava = DB::select("SELECT * FROM FEXAVA_AVALUO WHERE IDAVALUO = $idAvaluo");
        $arrInfoFexava = array_map("convierte_a_arreglo",$infoFexava);
        $arrFexava['FEXAVA_AVALUO'] = $arrInfoFexava[0];
        $this->modelDocumentos = new Documentos();
        $arrFexava['FEXAVA_AVALUO']['valuador'] = $this->modelDocumentos->get_valuador($arrFexava['FEXAVA_AVALUO']['idpersonaperito']);
        $arrFexava['FEXAVA_AVALUO']['inmuebleQueSeValua'] = $arrFexava['FEXAVA_AVALUO']['region']."-".$arrFexava['FEXAVA_AVALUO']['manzana']."-".$arrFexava['FEXAVA_AVALUO']['lote']."-".$arrFexava['FEXAVA_AVALUO']['unidadprivativa']." ".$arrFexava['FEXAVA_AVALUO']['digitoverificador'];
        $arrFexava['FEXAVA_AVALUO']['codregimenpropiedad'] = $this->modelDocumentos->get_regimen_propiedad($arrFexava['FEXAVA_AVALUO']['codregimenpropiedad']);
        foreach($arrTablas as $tabla){
            switch ($tabla){
                case 'CROQUIS':
                    $infoCroquis = DB::select("SELECT * FROM DOC.DOC_FICHERODOCUMENTO WHERE IDDOCUMENTODIGITAL = $idAvaluo");
                    $arrInfoFuenteInformacion = array_map("convierte_a_arreglo",$infoCroquis); //print_r($arrInfoFuenteInformacion); exit();
                    $arrFexava['CROQUIS'] = $arrInfoFuenteInformacion;
                break;
                case 'FEXAVA_DATOSPERSONAS':
                    $arrInfoAcuse = array();              
                    $infoPropietario = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idAvaluo AND CODTIPOFUNCION = 'P'");
                    $arrInfoPropietario = array_map("convierte_a_arreglo",$infoPropietario);
                    $arrInfoAcuse['propietario'] = $arrInfoPropietario[0];
                    $infoSolicitante = DB::select("SELECT * FROM FEXAVA_DATOSPERSONAS WHERE IDAVALUO = $idAvaluo AND CODTIPOFUNCION = 'S'");
                    $arrInfoSolicitante = array_map("convierte_a_arreglo",$infoSolicitante);
                    $arrInfoAcuse['solicitante'] = $arrInfoSolicitante[0];
                    $arrFexava['FEXAVA_DATOSPERSONAS'] = $arrInfoAcuse;              
                break;

                case 'FEXAVA_FUENTEINFORMACIONLEG':
                    $infoFuenteInformacion = DB::select("SELECT * FROM FEXAVA_FUENTEINFORMACIONLEG WHERE IDAVALUO = $idAvaluo");
                    $arrInfoFuenteInformacion = array_map("convierte_a_arreglo",$infoFuenteInformacion);
                    $arrFexava['FEXAVA_FUENTEINFORMACIONLEG'] = $arrInfoFuenteInformacion[0];
                break;

                case 'FEXAVA_ESCRITURA':
                    $infoEscritura = DB::select("SELECT * FROM FEXAVA_ESCRITURA WHERE IDAVALUO = $idAvaluo");
                    $arrInfoEscritura = array_map("convierte_a_arreglo",$infoEscritura);
                    $arrFexava['FEXAVA_ESCRITURA'] = $arrInfoEscritura[0];
                break;

                case 'FEXAVA_TIPOCONSTRUCCION':
                    $arrFexava['FEXAVA_TIPOCONSTRUCCION'] = array();
                    $infoTiposConstruccion = DB::select("SELECT * FROM FEXAVA_TIPOCONSTRUCCION WHERE IDAVALUO = $idAvaluo");
                    $control = 0;
                    foreach($infoTiposConstruccion as $info){
                        $arrInfoTiposConstruccion = array_map("convierte_a_arreglo",$infoTiposConstruccion);
                        $arrFexava['FEXAVA_TIPOCONSTRUCCION'][$control] = $arrInfoTiposConstruccion[0];
                        $control = $control + 1;
                    }
                    
                break;

                case 'FEXAVA_ELEMENTOSCONST':
                    $arrFexava['FEXAVA_ELEMENTOSCONST'] = array();
                    foreach($arrElementosConst as $tablaElementosConst){
                        if($tablaElementosConst != 'FEXAVA_ELEMENTOSEXTRA'){
                            $infoTabla = DB::select("SELECT * FROM $tablaElementosConst WHERE IDAVALUO = $idAvaluo");
                            $arrTabla = array_map("convierte_a_arreglo",$infoTabla);
                            $arrFexava['FEXAVA_ELEMENTOSCONST'][$tablaElementosConst] = $arrTabla[0];
                        }else{
                            $infoTabla = DB::select("SELECT * FROM $tablaElementosConst WHERE IDAVALUO = $idAvaluo");
                            $arrTabla = array_map("convierte_a_arreglo",$infoTabla);
                            $arrFexava['FEXAVA_ELEMENTOSCONST'][$tablaElementosConst] = $arrTabla;   
                        }
                        
                    }   
                break;

                case 'FEXAVA_TERRENOMERCADO':
                    $infoTerrenoMercado = DB::select("SELECT * FROM FEXAVA_TERRENOMERCADO WHERE IDAVALUO = $idAvaluo");
                    $arrInfoTerrenoMercado = array_map("convierte_a_arreglo",$infoTerrenoMercado);
                    $arrFexava['FEXAVA_TERRENOMERCADO'] = $arrInfoTerrenoMercado[0];
                break;

                case 'FEXAVA_DATOSTERRENOS':
                    $infoDatosTerreno = DB::select("SELECT * FROM FEXAVA_DATOSTERRENOS WHERE IDTERRENOMERCADO = ".$arrFexava['FEXAVA_TERRENOMERCADO']['idterrenomercado']);
                    $arrInfoDatosTerreno = array_map("convierte_a_arreglo",$infoDatosTerreno);
                    $arrFexava['FEXAVA_DATOSTERRENOS'] = $arrInfoDatosTerreno;
                break;

                case 'FEXAVA_CONSTRUCCIONESMER':
                    $infoConstruccionesMer = DB::select("SELECT * FROM FEXAVA_CONSTRUCCIONESMER WHERE IDAVALUO = $idAvaluo");
                    $arrInfoConstruccionesMer = array_map("convierte_a_arreglo",$infoConstruccionesMer);
                    $arrFexava['FEXAVA_CONSTRUCCIONESMER'] = $arrInfoConstruccionesMer;

                    for($i = 0; $i < count($arrFexava['FEXAVA_CONSTRUCCIONESMER']); $i++){
                        $infoInvestProductosComp = DB::select("SELECT * FROM FEXAVA_INVESTPRODUCTOSCOMP WHERE IDCONSTRUCCIONESMERCADO = ".$arrFexava['FEXAVA_CONSTRUCCIONESMER'][$i]['idconstruccionesmercado']);
                        $arrInfoInvestProductosComp = array_map("convierte_a_arreglo",$infoInvestProductosComp);
                        $arrFexava['FEXAVA_CONSTRUCCIONESMER'][$i]['FEXAVA_INVESTPRODUCTOSCOMP'] = $arrInfoInvestProductosComp;
                    }
                break;

                case 'FEXAVA_ENFOQUECOSTESCAT':
                    $infoEnfoqueCost = DB::select("SELECT * FROM FEXAVA_ENFOQUECOSTESCAT WHERE IDAVALUO = $idAvaluo");
                    $arrInfoEnfoqueCost = array_map("convierte_a_arreglo",$infoEnfoqueCost);
                    $arrFexava['FEXAVA_ENFOQUECOSTESCAT'] = $arrInfoEnfoqueCost;                    
                break;

                case 'FEXAVA_FOTOAVALUO':
                    $arrFexava['FEXAVA_FOTOAVALUO'] = array();
                    $infoFotoAvaluo = DB::select("SELECT * FROM FEXAVA_FOTOAVALUO WHERE IDAVALUO = $idAvaluo");
                    $arrInfoFotoAvaluo = array_map("convierte_a_arreglo",$infoFotoAvaluo);
                    foreach($arrInfoFotoAvaluo as $fotoAvaluo){
                        $idDocumentoDigital = $fotoAvaluo['iddocumentofoto'];
                        $infoFotoAvaluoCompleta =  DB::table('DOC.DOC_DOCUMENTOFOTOINMUEBLE')
                        ->join('DOC.DOC_FICHERODOCUMENTO', 'DOC.DOC_DOCUMENTOFOTOINMUEBLE.iddocumentodigital', '=', 'DOC.DOC_FICHERODOCUMENTO.iddocumentodigital')
                        ->where('DOC.DOC_DOCUMENTOFOTOINMUEBLE.iddocumentodigital',$idDocumentoDigital)->first();
                        $arrFexava['FEXAVA_FOTOAVALUO'][] = $infoFotoAvaluoCompleta;
                    }
                    //print_r($arrInfoFotoAvaluo); exit();
                                        
                break;
            }
        }

        return $arrFexava; */
    }
    

}