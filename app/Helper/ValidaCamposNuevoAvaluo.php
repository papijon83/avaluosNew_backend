<?php
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\TryCatch;
use Carbon\Carbon;
use App\Models\Fis;
use App\Models\DatosExtrasAvaluo;

function convierte_a_arreglo($data){    
    return json_decode( json_encode($data), true );
}

function limpiar_arreglo($arrIni){
    foreach($arrIni[0] as $etiqueta => $elemento){
        if(is_array($elemento) && count($elemento) == 1){
            if(isset($elemento['@attributes'])){
                $arrIni[0][$etiqueta] = null;
            }
        }

        if(is_array($elemento) && count($elemento) > 1){
            foreach($elemento as $etiquetaElemento => $valorElemento){
                if(is_array($valorElemento) && count($valorElemento) == 1){
                    if(isset($valorElemento['@attributes'])){
                        $arrIni[0][$etiqueta][$etiquetaElemento] = null;
                    }
                }
            }
        }
    }
     return $arrIni;        
}

function quitar_attributes($arrIni){
    if(count($arrIni) == 1 && isset($arrIni[0]['@attributes'])){
        unset($arrIni[0]['@attributes']);       
    }   
    return $arrIni;        
}

function numero_datos($arrIni){ //print_r($arrIni); exit();
    $numero = 0;
    foreach($arrIni as $elemento){

        if (isset($elemento) && !is_array($elemento) && trim($elemento) != '') {
            $numero = $numero + 1;
        }

    }
    return $numero;
}

function define_validacion($tipo_validacion, $valor){
    $existeSeparador = strpos($tipo_validacion,'_');
    if($existeSeparador != FALSE){
        $arrValidaciones = explode('_',$tipo_validacion);
        if(count($arrValidaciones) == 2){
            switch ($arrValidaciones[0]) {
                case 'nonEmptyString':
                    return val_vacio_longitud($valor, $arrValidaciones[1]);
                break;
                case 'string':
                    return val_longitud($valor, $arrValidaciones[1]);
                break;
                case 'decimalPositivo':
                    return val_decimal_positivo_tipo($valor, $arrValidaciones[1]);
                break;
                case 'nullableDecimalPositivo':
                    return val_nullable_decimal_positivo_tipo($valor, $arrValidaciones[1]);                
                break;
                case 'porcentaje':
                    return val_porcentaje_tipo($valor,$arrValidaciones[1]);
                break;
                
            }
        }
    }else{

        switch ($tipo_validacion) {
            case '':
                return 'correcto';
            break;        
            case 'date':
                return val_date($valor);
            break;
            case 'nullableDate':
                return val_nullable_date($valor);
            break;
            case 'catColonia':               
                return val_cat_colonia($valor);
            break;

            case 'catDelegacion':               
                return val_cat_delegacion($valor);
            break;

            case 'subTipoPersona':               
                return val_cat_tipo_persona($valor);
            break;

            case 'regionManzanaUp':               
                return val_region_manzana_localidad($valor);
            break;
            
            case 'lote':               
                return val_lote($valor);
            break;

            case 'digitoVerificador':               
                return val_stringLength1($valor);
            break;

            case 'catRegimen':               
                return val_cat_regimen($valor);
            break;

            case 'subTipoPersonaProp':
                return val_cat_tipo_persona($valor);
            break;

            case 'catClasificacionZona':
                return val_cat_clasificacion_zona($valor);
            break;

            case 'decimalPositivo':
                return val_decimal_positivo($valor);
            break;            
            case 'catClasesConstruccion':
                return val_cat_clases_construccion($valor);
            break;

            case 'catDensidadPoblacion':
                return val_cat_densidad_poblacion($valor);
            break;
            case 'catNivelSocioeconomico':
                return val_cat_nivel_socioeconomico($valor);
            break;

            case 'nonEmptyString':
                return val_vacio($valor);
            break;

            case 'catAguaPotable':
                return val_cat_agua_potable($valor);
            break;

            case 'catDrenaje':
                return val_cat_drenaje($valor);
            break;

            case 'catDrenajePluvial':
                return val_cat_drenaje_pluvial($valor);
            break;

            case 'catSuministroElectrico':
                return val_cat_suministro_electrico($valor);
            break;

            case 'catAcometidaInmueble':
                return val_cat_acometida_inmueble($valor);
            break;

            case 'catAlumbradoPublico':
                return val_cat_alumbrado_publico($valor);
            break;

            case 'catVialidades':
                return val_cat_vialidades($valor);
            break;

            case 'catBanquetas':
                return val_cat_banquetas($valor);
            break;

            case 'catGuarniciones':
                return val_cat_guarniciones($valor);
            break;
            
            case 'catGasNatural':
                return val_cat_gas_natural($valor);
            break;

            case 'catSuministroTelefonico':
                return val_cat_suministro_telefonico($valor);
            break;

            case 'catSenalizacionVias':
                return val_cat_senalizacion_vias($valor);
            break;

            case 'catNomenclaturaCalles':
                return val_cat_nomenclatura_calles($valor);
            break;

            case 'catVigilanciaZona':
                return val_cat_vigilancia_zona($valor);
            break;

            case 'catRecoleccionBasura':
                return val_cat_recoleccion_basura($valor);
            break;
            case 'boolean':
                return val_boolean($valor);
            break;
            case 'decimal':
                return val_decimal($valor);
            break;
            case 'base64Binary':
                return val_base64_binary($valor);
            break;
            case 'SUB-Indiviso':
                return val_decimal_positivo_tipo($valor, '98');                
            break;

            case 'catTopografia':
                return val_cat_topografia($valor);
            break;

            case 'catDensidadHabitacional':
                return val_cat_densidad_habitacional($valor);
            break;

            case 'SUB-IdentificadorFraccionN1Priv':
                return val_decimal_positivo($valor);
            break;

            case 'SUB-SuperficieFraccionN1Priv':
                return val_decimal_positivo_tipo($valor, '222');
            break;

            case 'SUB-FzoPriv':
                return val_decimal_positivo_tipo($valor, '32');
            break;
            case 'SUB-FubPriv':
                return val_decimal_positivo_tipo($valor, '32');
            break;

            case 'SUB-FFrPriv':
                return val_decimal_positivo_tipo($valor, '32');
            break;

            case 'SUB-FfoPriv':
                return val_decimal_positivo_tipo($valor, '32');
            break;

            case 'SUB-FsuPriv':
                return val_decimal_positivo_tipo($valor, '32');
            break;

            case 'SUB-ClaveDeAreaDeValorPriv':
                return val_vacio($valor);
            break;

            case 'SUB-FrePriv':
                return val_decimal_positivo($valor);
            break;

            case 'SUB-ValorDeLaFraccionNPriv':
                return val_decimal_positivo($valor);
            break;

            case 'SUB-ValorCatastralDeTierraAplicableALaFraccionPriv':
                return val_decimal_positivo($valor);
            break;

            case 'nullableDecimalPositivo':            
                return val_nullable_decimal_positivo($valor);
            break;

            case 'nullableDecimal':            
                return val_nullable_decimal($valor);
            break;

            case 'SUB-SuperficieTotalDeConstruccionesPrivativas':
            case 'SUB-ValorTotalDeConstruccionesPrivativas':                        
            case 'SUB-SuperficieTotalDeConstruccionesComunes':
            case 'SUB-ValorTotalDeConstruccionesComunes':
            case 'SUB-ValorTotalDeLasConstruccionesProIndivisoComunes':
                return val_nullable_decimal_positivo($valor);
            break;

            case 'SUB-ValorTotalDeLasConstruccionesProIndiviso':
                return val_nullable_decimal_positivop($valor);
            break;

            case 'SUB-Descripcion':
            case 'SUB-DescripcionComunes':
                return val_longitud($valor, 50);
            break;

            case 'SUB-ClaveUso':
            case 'SUB-ClaveUsoComunes':
                return val_usos_construcciones($valor);
            break;

            case 'SUB-NumeroDeNivelesDelTipo':
            case 'SUB-NumeroDeNivelesDelTipoComunes':
                return val_nullable_decimal_positivo_tipo($valor, '30');
            break;

            case 'SUB-ClaveRangoDeNiveles':
            case 'SUB-ClaveRangoDeNivelesComunes':
                return val_nullable_cat_rango_nivel_tgdf($valor);
            break;

            case 'SUB-PuntajeDeClasificacion':
            case 'SUB-PuntajeDeClasificacionComunes':
                return val_nullable_decimal_positivo($valor);
            break;

            case 'SUB-ClaveClase':
            case 'SUB-ClaveClaseComunes':
                return val_cat_clases_construccion($valor);
            break;

            case 'SUB-Edad':
            case 'SUB-EdadComunes':
            case 'SUB-VidaUtilTipo':
            case 'SUB-VidaUtilTipoComunes':
            case 'SUB-VidaUtilRemanente':
            case 'SUB-VidaUtilRemanenteComunes':
                return val_nullable_decimal_positivo($valor);
            break;

            case 'SUB-ClaveConservacion':
            case 'SUB-ClaveConservacionComunes':
                return val_nullable_cat_estado_conservacion($valor);
            break;

            case 'SUB-Superficie':
            case 'SUB-SuperficieComunes':
                return val_nullable_decimal_positivo_tipo($valor, '222');
            break;

            case 'SUB-ValorunitariodereposicionNuevo':
            case 'SUB-ValorunitariodereposicionNuevoComunes':
            case 'SUB-FactorDeEdad':
            case 'SUB-FactorDeEdadComunes':
            case 'SUB-FactorResultante':
            case 'SUB-FactorResultanteComunes':
            case 'SUB-ValorDeLaFraccionNDescInmueble':
            case 'SUB-ValorDeLaFraccionNDescInmuebleComunes':
            case 'SUB-PorcentajeIndivisoComunes':
                return val_nullable_decimal_positivo($valor);
            break;

            case 'SUB-ValorDeLaFraccionNDescInmueble':
            case 'SUB-ValorDeLaFraccionNDescInmuebleComunes':
            case 'SUB-ValorUnitarioCatastral':
            case 'SUB-ValorUnitarioCatastralComunes':
            case 'SUB-DepreciacionPorEdad':
            case 'SUB-DepreciacionPorEdadComunes':
            case 'SUB-PorcentajeIndivisoComunes':
                return val_nullable_decimal_positivo($valor);
            break;

            case 'SUB-Cimentacion':                
                return val_longitud($valor, '250');
            break;

            case 'SUB-ClaveInstalacionEspecial-PrivativaCom':
            case 'SUB-ClaveInstalacionEspecial-ComunesCom':
                return val_cat_instalaciones_especiales($valor);
            break;
            case 'SUB-DescripcionInstalacionEspecial-Privativa':
            case 'SUB-DescripcionInstalacionEspecial-Comunes':
                return val_longitud($valor, '100');
            break;
            case 'SUB-UnidadInstalacionEspecial-Privativa':
            case 'SUB-UnidadInstalacionEspecial-Comunes':
                return val_longitud($valor, '20');
            break;
            case 'SUB-CantidadInstalacionEspecial-Privativa':
            case 'SUB-CantidadInstalacionEspecial-Comunes':
                return val_nullable_decimal_positivo_tipo($valor, '222');
            break;
            case 'SUB-ValorUnitarioInstalacionEspecial-Privativa':
            case 'SUB-ImporteInstalacionEspecialComunes':
                return val_nullable_decimal_positivo($valor);
            break;
            case 'SUB-FactorDeEdadInstalacionEspecial':
            case 'SUB-FactorDeEdadInstalacionEspecialComunes':            
                return val_decimal_positivo_tipo($valor, '06');
            break;
            case 'SUB-ImporteInstalacionEspecial':
            case 'SUB-PorcentajeIndivisoEspecialComunes':
                return val_nullable_decimal_positivo($valor);
            break;
            case 'SUB-ValorUnitarioInstalacionEspecial-Comunes':
                return val_decimal_positivo($valor);
            break;
            case 'SUB-ImporteTotalInstalacionesEspecialesPrivativas':
            case 'SUB-ImporteTotalInstalacionesEspecialesComunes':
                return val_nullable_decimal_positivo($valor);
            break;

            case 'SUB-ClaveElementoAccesorio-PrivativasCom':
            case 'SUB-ClaveElementoAccesorio-ComunesCom':
            case 'SUB-ClaveObraComplementaria-PrivativasCom':
            case 'SUB-ClaveObraComplementaria-ComunesCom':
                return val_cat_instalaciones_especiales($valor);
            break;

            case 'SUB-DescripcionElementoAccesorio-Privativas':
            case 'SUB-DescripcionElementoAccesorio-ComunesCom':
            case 'SUB-DescripcionObraComplementaria-Privativas':
            case 'SUB-DescripcionObraComplementaria-ComunesCom':
                return val_vacio_longitud($valor, '100');
            break;

            case 'SUB-UnidadElementoAccesorio-Privativas':
            case 'SUB-UnidadElementoAccesorio-ComunesCom':
            case 'SUB-UnidadObraComplementaria-Privativas':
            case 'SUB-UnidadObraComplementaria-ComunesCom':
                return val_vacio_longitud($valor, '20');
            break;

            case 'SUB-CantidadElementoAccesorio-Privativas':
            case 'SUB-CantidadElementoAccesorio-ComunesCom':
            case 'SUB-CantidadObraComplementaria-Privativas':
            case 'SUB-CantidadObraComplementaria-ComunesCom':
                return val_decimal_positivo_tipo($valor, '222');
            break;

            case 'SUB-EdadElementoAccesorio':
            case 'SUB-VidaUtilTotalElementoAccesorio':
            case 'SUB-ValorUnitarioElementoAccesorio-Privativas':
            case 'SUB-FactorDeEdadElementoAccesorio':
            case 'SUB-ImporteElementoAccesorio':
            case 'SUB-EdadElementoAccesorio-Comunes':
            case 'SUB-VidaUtilTotalElementoAccesorioComunes':
            case 'SUB-ValorUnitarioElementoAccesorio-Comunes':
            case 'SUB-FactorDeEdadElementoAccesorioComunes':
            case 'SUB-ImporteElementoAccesorio-Comunes':
            case 'SUB-EdadObraComplementaria-Privativas':
            case 'SUB-ValorUnitarioObraComplementaria-Privativas':
            case 'SUB-ImporteObraComplementaria-Privativas':
            case 'SUB-EdadObraComplementaria-Comunes':
            case 'SUB-VidaUtilTotalObraComplementaria-Comunes':
            case 'SUB-ValorUnitarioObraComplementaria-Comunes':
                return val_decimal_positivo($valor);
            break;

            case 'SUB-PorcentajeIndivisoAccesorio-Comunes':
            case 'SUB-ImporteTotalElementosAccesoriosPrivativas':
            case 'SUB-ImporteTotalElementosAccesoriosComunes-Comunes':
            case 'SUB-ImporteObraComplementaria-complementaria':
            case 'SUB-PorcentajeIndivisoObraComplementaria-complementaria':
            case 'SUB-ImporteTotalObrasComplementariasPrivativas':
            case 'SUB-ImporteTotalObrasComplementariasComunes':
            case 'SUB-ImporteTotalInstalacionesAccesoriosComplementariasPrivativas':
            case 'SUB-ImporteTotalInstalacionesAccesoriosComplementariasComunes':
            case 'SUB-ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes':
            case 'SUB-ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas':
                return val_nullable_decimal_positivo($valor);
            break;

            case 'SUB-FactorDeEdadObraComplementaria-Privativas':
            case 'SUB-FactorDeEdadObraComplementaria-Comunes':
            case 'SUB-ImporteTotalObrasComplementariasComunes':
                return val_decimal_positivo_tipo($valor, '06');
            break;

            case 'SUB-ClaveInstalacionEspecial-PrivativaCat':
            case 'SUB-ClaveInstalacionEspecial-ComunesCat':
            case 'SUB-ClaveElementoAccesorio-PrivativasCat':
            case 'SUB-ClaveElementoAccesorio-ComunesCat':
            case 'SUB-ClaveObraComplementaria-PrivativasCat':
            case 'SUB-ClaveObraComplementaria-ComunesCat':          
                return val_cat_instalaciones_especiales($valor);
            break;
            
            case 'SUB-DescripcionElementoAccesorio-ComunesCat':
            case 'SUB-DescripcionObraComplementaria-ComunesCat':            
                return val_vacio_longitud($valor, '100');
            break;

            case 'SUB-UnidadElementoAccesorio-ComunesCat':
            case 'SUB-UnidadObraComplementaria-ComunesCat':
                return val_vacio_longitud($valor, '20');
            break;

            case 'SUB-CantidadElementoAccesorio-ComunesCat':
            case 'SUB-CantidadObraComplementaria-ComunesCat':
                return val_decimal_positivo_tipo($valor, '222');
            break;

            case 'nullableRegionManzanaUp':                
                return val_nullable_region_manzana_localidad($valor);
            break;

            case 'SUB-ValorUnitarioDeTierraAplicableAlAvaluo':
            case 'SUB-ValorDeMercadoDelInmueble':
                return val_decimal_positivo($valor);
            break;            

            case 'nullableLote':
                return val_nullable_lote($valor);
            break;

            case 'SUB-ImporteTotalDelEnfoqueDeCostos':
            case 'SUB-ImporteInstalacionesEspeciales':
            case 'SUB-ImporteTotalValorCatastralObraEnProceso':        
                return val_nullable_decimal_positivo($valor);
            break;
            
            case 'SUB-ImporteTotalValorCatastral':
                return val_decimal_positivo($valor);
            break;

            case 'SUB-AvanceDeObra':
                return val_decimal_positivo_tipo($valor, '32');
            break;
            case 'SUB-ImporteEnfoqueDeIngresos':
                return val_nullable_decimal_positivo($valor);
            break;
            case 'catTipoFotoInmueble':
                return val_cat_tipo_foto_inmueble($valor);
            break;
        }

    }
}

function val_porcentaje_tipo($valor, $tipo){
    if($valor < 0 || $valor > 1){
        return "el porcentaje ".$valor." no corresponde a un valor entre 0 y 1";
    }
    $estado = 'correcto';
    $pos = strpos($valor, '.');
    if($tipo == '02'){               
        if($pos === TRUE || $pos === 1){
            $arrVals = explode('.',$valor);
            if(strlen($arrVals[1]) > 2){
                return "no corresponde a un formato valido para este campo";
            }
        }
    }

    if($tipo == '04'){               
        if($pos === TRUE || $pos === 1){
            $arrVals = explode('.',$valor);
            if(strlen($arrVals[1]) > 4){
                return "no corresponde a un formato valido para este campo";
            }
        }
    }

    if($tipo == '10'){              
        if($pos === TRUE || $pos === 1){
            $arrVals = explode('.',$valor);
            if(strlen($arrVals[1]) > 10){
                return "no corresponde a un formato valido para este campo";
            }
        }
    }
    return $estado;
}

function val_vacio_longitud($valor, $longitud){
    $estado = "correcto";

    if(is_array($valor)){
        if(isset($valor['@attributes'])){
            unset($valor['@attributes']);
        }
        
        if(count($valor) == 1){
            $valor = $valor[0];
        }else{
            $valor = '';
        }
        
    }

    if(trim($valor) == ''){
        return "no puede estar vacio";
    }

    if(strlen($valor) > $longitud){
        return "excede la longitud permitida de ".$longitud;
    }
    return $estado;
}

function val_date($fecha){
    $valores = explode('-', $fecha);
	if(count($valores) == 3 && checkdate($valores[1], $valores[2], $valores[0])){
		return "correcto";
    }
	return "no es una fecha valida";
}

function val_nullable_date($fecha){

    if(val_null_string($fecha) == 'correcto'){
        return "correcto";
    }

    $valores = explode('-', $fecha);
	if(count($valores) == 3 && checkdate($valores[1], $valores[2], $valores[0])){
		return "correcto";
    }
	return "no es una fecha valida";
}

function val_longitud($valor, $longitud){
    $estado = "correcto";
   
    if(is_array($valor)){
        if(isset($valor['@attributes'])){
            unset($valor['@attributes']);
        }        
        if(count($valor) == 1){
            $valor = $valor[0];
        }else{
            $valor = '';
        }
        
    }

    if(strlen($valor) > $longitud && trim($valor) != ''){
        return "excede la longitud permitida de ".$longitud;
    }
    return $estado;
}

function val_cat_colonia($valor){    
    $estado = 'correcto';
    $res = val_vacio_longitud($valor, 50);    
    if($res != 'correcto'){
        return $res;
    }/*else{
        $valor = strtoupper($valor);
        $idColonia = convierte_a_arreglo(DB::select("SELECT IDCOLONIA FROM CAS.CAS_COLONIA WHERE NOMBRE = '$valor'"));
        
        if(count($idColonia) == 0){            
            return "con la colonia ".$valor." no existe en el catalogo de colonias";
        }
    }*/
    return $estado;
}

function val_cat_delegacion($valor){
    $estado = 'correcto';
    $res = val_vacio_longitud($valor, 50);    
    if($res != 'correcto'){
        return $res;
    }/*else{
        $idDelegacion = convierte_a_arreglo(DB::select("SELECT IDDELEGACION FROM CAS.CAS_DELEGACION WHERE CLAVE = '$valor'"));
        
        if(count($idDelegacion) == 0){            
            return "con la delegacion ".$valor." no existe en el catalogo de colonias";
        }
    }*/
    return $estado;
}

function val_cat_tipo_persona($valor){
    $estado = 'correcto';      
    $valor = strtoupper($valor);
    $idTipoPersona = convierte_a_arreglo(DB::select("SELECT CODTIPOSPERSONA FROM RCON.RCON_CATTIPOSPERSONA WHERE CODTIPOSPERSONA = '$valor'"));
        
    if(count($idTipoPersona) == 0){            
        return "con el tipo persona ".$valor." no existe en el catalogo de tipos persona";
    }
    
    return $estado;
}

function val_cat_tipo_foto_inmueble($valor){
    $estado = 'correcto';      
    $arrTipoFoto = array('I','E');
    if(in_array($valor,$arrTipoFoto)){
        return $estado;
    }else{
        return "con el tipo foto ".$valor." no existe en el catalogo de tipo foto";
    }
    
}

function val_nullable_region_manzana_localidad($valor){
    
    $estado = 'correcto';
    
    if(val_null_string($valor) == 'correcto'){
        return $estado;
    }
    
    $res = val_longitud($valor, 3);

    if($res != 'correcto'){
        return $res;
    }else{
        
        $patron = '[0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F|H|J|K|M|N|P|Q|R|T|U|V|W|X|Y]';
        if (preg_match($patron, $valor)) {
            return $estado;
        }else{
            return "contiene caracteres no validos";
        }
    }
}

function val_region_manzana_localidad($valor){
    $estado = 'correcto';
    $res = val_longitud($valor, 3);

    if($res != 'correcto'){
        return $res;
    }else{
        $patron = '[0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F|H|J|K|M|N|P|Q|R|T|U|V|W|X|Y]';
        if (preg_match($patron, $valor)) {
            return $estado;
        }else{
            return "contiene caracteres no validos";
        }
    }
}

function val_lote($valor){
    $estado = 'correcto';
    $res = val_longitud($valor, 2);

    if($res != 'correcto'){
        return $res;
    }else{
        $patron ='[0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F|H|J|K|M|N|P|Q|R|T|U|V|W|X|Y]';
        if (preg_match($patron, $valor)) {
            return $estado;
        }else{
            return "contiene caracteres no validos";
        }
    }
}

function val_nullable_lote($valor){
    $estado = 'correcto';

    if(val_null_string($valor) == 'correcto'){
        return $estado;
    }

    $res = val_longitud($valor, 2);

    if($res != 'correcto'){
        return $res;
    }else{
        $patron ='[0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F|H|J|K|M|N|P|Q|R|T|U|V|W|X|Y]';
        if (preg_match($patron, $valor)) {
            return $estado;
        }else{
            return "contiene caracteres no validos";
        }
    }
}

function val_stringLength1($valor){
    $estado = 'correcto';
    $res = val_longitud($valor, 1);
    if($res != 'correcto'){
        return $res;
    }
    return  $estado;
}

function val_nonNegativeInteger($valor){
    $estado = 'correcto';
    $valorInt = intval($valor);
    if($valorInt == $valor){
        if(is_int($valorInt) && $valorInt >= 0){
            return $estado;
        }else{
            return "contiene un valor no entero o negativo";
        }
    }else{
        return "contiene un valor no entero o negativo";
    }
    
}

function val_cat_regimen($valor){     
    $estado = 'correcto';       
    $res = val_nonNegativeInteger($valor);   
    if($res == 'correcto'){
        $idRegimen = convierte_a_arreglo(DB::select("SELECT CODREGIMENPROPIEDAD FROM FEXAVA_CATREGIMENPROPIEDAD WHERE CODREGIMENPROPIEDAD = '$valor'"));    
        if(count($idRegimen) == 0){            
            return "el regimen ".$valor." no existe en el catalogo de regimen propiedad";
        }
        return $estado;
    }else{
        return $res;
    }
    
}

function val_cat_clasificacion_zona($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idClasificacionZona = convierte_a_arreglo(DB::select("SELECT CODCLASIFICACIONZONA FROM FEXAVA_CATCLASIFICACIONZONA WHERE CODCLASIFICACIONZONA = '$valor'"));           
        if(count($idClasificacionZona) == 0){            
            return "el codigo de clasificacion de zona ".$valor." no existe en el catalogo de clasificacion zona";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_decimal_positivo($valor){
    $estado = 'correcto';
    $res = val_decimal($valor);
    if($res == 'correcto'){
        if($valor < 0){
            return "contiene un valor negativo";
        }else{
            return $estado;
        }
    }else{
        return $res;
    }    
    
}

function val_cat_clases_construccion($valor){
    $estado = 'correcto';
    $idClaseConstruccion = convierte_a_arreglo(DB::select("SELECT IDCLASES FROM FIS.FIS_CATCLASES WHERE CODCLASE = '$valor'"));        
    if(count($idClaseConstruccion) == 0){            
        return "el codigo de clase de construccion ".$valor." no existe en el catalogo de clases";
    }
    return $estado;
    
}

function val_cat_densidad_poblacion($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idDensidadPoblacion = convierte_a_arreglo(DB::select("SELECT CODDENSIDADPOBLACION FROM FEXAVA_CATDENSIDADPOB WHERE CODDENSIDADPOBLACION = '$valor'"));            
        if(count($idDensidadPoblacion) == 0){            
            return "el codigo de densidad de poblacion ".$valor." no existe en el catalogo de densidad de población";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_nivel_socioeconomico($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idNivelSocioeconomico = convierte_a_arreglo(DB::select("SELECT CODNIVELSOCIOECONOMICO FROM FEXAVA_CATNIVELSOCIOECON WHERE CODNIVELSOCIOECONOMICO = '$valor'"));          
        if(count($idNivelSocioeconomico) == 0){            
            return "el codigo de nivel socioeconomico ".$valor." no existe en el catalogo de nivel socioeconomico";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_decimal_positivo_tipo($valor, $tipo){
    $estado = 'correcto';
    $pos = strpos($valor, '.');

    if($tipo == '06'){
        $res = val_decimal_positivo($valor);
        if($res == 'correcto'){            
           if($pos === TRUE || $pos === 1){
                $arrVals = explode('.',$valor);
                if($arrVals[0] != 0 || strlen($arrVals[1]) > 6){
                    return "no corresponde a un formato valido para este campo";
                }
           }
            if($valor > 0.999999){
                return "contiene un valor mayor a 0.999999";
            }else{
                if($valor < 0.6){
                    return "contiene un valor menor a 0.6";
                }else{
                    return $estado;
                }                    
            }
            /*$patron = '/\d{1,3}/';
            if (!preg_match($patron, $valor)) {
                return "no corresponde a un formato valido para este campo";
            }else{
                if($valor > 0.999999){
                    return "contiene un valor mayor a 0.999999";
                }else{
                    if($valor < 0.6){
                        return "contiene un valor menor a 0.6";
                    }else{
                        return $estado;
                    }                    
                }
            }*/
        }
        
    }
    
    if($tipo == '30'){
        $res = val_decimal_positivo($valor);
        if($res == 'correcto'){
            $patron = '/\d{1,3}/';
            if (!preg_match($patron, $valor)) {
                return "no corresponde a un formato valido para este campo";
            }else{
                if($valor > 999){
                    return "contiene un valor mayor a 999";
                }else{
                    return $estado;
                }
            }
        }
        
    }

    if($tipo == '32'){
        if($valor > 10){
            return "contiene un valor mayor a 10";
        }
        $res = val_decimal_positivo($valor);
        if($res == 'correcto'){
            if($pos === TRUE || $pos === 1){
                $elementosCantidad = explode('.',$valor);
                if((strlen($elementosCantidad[0])+strlen($elementosCantidad[1])) > 3){
                    return " contiene mas de 3 digitos";
                }
                $patron = '/\d{1}+.\d{1,2}/';
                if (!preg_match($patron, $valor)) {
                    return "no corresponde a un formato valido para este campo";
                }                    
                return $estado;                   
            }else{    
                return $estado;
            }                
        }else{
            return $res;
        }            
    }

        if($tipo == '52'){
            if($valor > 100){
                return "contiene un valor mayor a 100";
            }
            $res = val_decimal_positivo($valor);
            if($res == 'correcto'){
                if($pos === TRUE || $pos === 1){
                    $elementosCantidad = explode('.',$valor);
                    if((strlen($elementosCantidad[0])+strlen($elementosCantidad[1])) > 5){
                        return " contiene mas de 5 digitos";
                    }
                    $patron = '/\d{1,3}+.\d{1,2}/';
                    if (!preg_match($patron, $valor)) {
                        return "no corresponde a un formato valido para este campo";
                    }                    
                    return $estado;                   
                }else{    
                    return $estado;
                }                
            }else{
                return $res;
            }            
        }

        if($tipo == '54'){
            if($valor > 10){
                return "contiene un valor mayor a 10";
            }    
            $res = val_decimal_positivo($valor);
            if($res == 'correcto'){
                if($pos === TRUE || $pos === 1){
                    $elementosCantidad = explode('.',$valor);
                    if((strlen($elementosCantidad[0])+strlen($elementosCantidad[1])) > 5){
                        return " contiene mas de 5 digitos";
                    }
                    $patron = '/\d{1}+.\d{1,4}/';
                    if (!preg_match($patron, $valor)) {
                        return "no corresponde a un formato valido para este campo";
                    }        
                    return $estado;  
                    
                }else{
                    return $estado;
                }            
            }else{
                return $res;
            }            
        }

        if($tipo == '98'){                        
            if($valor > 10){
                return "contiene un valor mayor a 10";
            }                          
            $res = val_decimal_positivo($valor);
            if($res == 'correcto'){                                             
                if($pos === TRUE || $pos === 1){                    
                    $elementosCantidad = explode('.',$valor);
                    if((strlen($elementosCantidad[0])+strlen($elementosCantidad[1])) > 9){
                        return " contiene mas de 9 digitos";
                    }                   
                    $patron = '/\d{1}+.\d{1,8}/';
                    if (!preg_match($patron, $valor)) {
                        return "no corresponde a un formato valido para este campo";
                    }                          
                    return $estado;  
                    
                }else{
                    return $estado;
                }            
            }else{
                return $res;
            }            
        }

        if($tipo == '223'){                        
            if($valor > 9999999999999999999){
                return "contiene un valor mayor a 9999999999999999999";
            }                          
            $res = val_decimal_positivo($valor);
            if($res == 'correcto'){                                             
                if($pos === TRUE || $pos === 1){                    
                    $elementosCantidad = explode('.',$valor);
                    if((strlen($elementosCantidad[0])+strlen($elementosCantidad[1])) > 22){
                        return " contiene mas de 22 digitos";
                    }                   
                    $patron = '/\d{1,19}+.\d{1,3}/';
                    if (!preg_match($patron, $valor)) {
                        return "no corresponde a un formato valido para este campo";
                    }                          
                    return $estado;  
                    
                }else{
                    return $estado;
                }            
            }else{
                return $res;
            }            
        }

        if($tipo == '222'){                        
            if($valor > 99999999999999999999){
                return "contiene un valor mayor a 99999999999999999999";
            }                          
            $res = val_decimal_positivo($valor);
            if($res == 'correcto'){                                             
                if($pos === TRUE || $pos === 1){                    
                    $elementosCantidad = explode('.',$valor);
                    if((strlen($elementosCantidad[0])+strlen($elementosCantidad[1])) > 22){
                        return " contiene mas de 22 digitos";
                    }                   
                    $patron = '/\d{1,20}+.\d{1,2}/';
                    if (!preg_match($patron, $valor)) {
                        return "no corresponde a un formato valido para este campo";
                    }                          
                    return $estado;  
                    
                }else{
                    return $estado;
                }            
            }else{
                return $res;
            }            
        }

    
}

function val_vacio($valor){
    $estado = 'correcto';
    if(trim($valor) == ''){
        return "no puede estar vacio";
    }
    return $estado;
}

function val_cat_agua_potable($valor){
    $estado = 'correcto';
    $idAguaPotable = convierte_a_arreglo(DB::select("SELECT CODAGUAPOTABLE FROM FEXAVA_CATAGUAPOTABLE WHERE CODAGUAPOTABLE = '$valor'"));    
    if(count($idAguaPotable) == 0){            
        return "el codigo de agua potable ".$valor." no existe en el catalogo de agua potable";
    }
    return $estado;    
}

function val_cat_drenaje($valor){
    $estado = 'correcto';
    $idDrenajeInmueble = convierte_a_arreglo(DB::select("SELECT CODDRENAJEINMUEBLE FROM FEXAVA_CATDRENAJEINMUEBLE WHERE CODDRENAJEINMUEBLE = '$valor'"));    
    if(count($idDrenajeInmueble) == 0){            
        return "el codigo de drenaje ".$valor." no existe en el catalogo de drenaje inmueble";
    }
    return $estado;
}

function val_cat_drenaje_pluvial($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idDrenajePluvial = convierte_a_arreglo(DB::select("SELECT CODDRENAJEPLUVIAL FROM FEXAVA_CATDRENAJEPLUVIAL WHERE CODDRENAJEPLUVIAL = '$valor'"));    
        if(count($idDrenajePluvial) == 0){            
            return "el codigo de drenaje pluvial ".$valor." no existe en el catalogo de drenaje pluvial";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_suministro_electrico($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idSuministroElectrico = convierte_a_arreglo(DB::select("SELECT CODSUMINISTROELECTRICO FROM FEXAVA_CATSUMINISTROELEC WHERE CODSUMINISTROELECTRICO = '$valor'"));    
        if(count($idSuministroElectrico) == 0){            
            return "el codigo de suministro electrico ".$valor." no existe en el catalogo de suministro electrico";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_acometida_inmueble($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idAcometidaInmueble = convierte_a_arreglo(DB::select("SELECT CODACOMETIDAINMUEBLE FROM FEXAVA_CATACOMETIDAINM WHERE CODACOMETIDAINMUEBLE = '$valor'"));    
        if(count($idAcometidaInmueble) == 0){            
            return "el codigo de acometida inmueble ".$valor." no existe en el catalogo de acometida inmueble";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_alumbrado_publico($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idAlumbradoPublico = convierte_a_arreglo(DB::select("SELECT CODALUMBRADOPUBLICO FROM FEXAVA_CATALUMBRADOPUBLICO WHERE CODALUMBRADOPUBLICO = '$valor'"));    
        if(count($idAlumbradoPublico) == 0){            
            return "el codigo de alumbrado publico ".$valor." no existe en el catalogo de alumbrado publico";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_vialidades($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idVialidad = convierte_a_arreglo(DB::select("SELECT CODVIALIDADES FROM FEXAVA_CATVIALIDADES WHERE CODVIALIDADES = '$valor'"));    
        if(count($idVialidad) == 0){            
            return "el codigo de vialidad ".$valor." no existe en el catalogo de vialidad";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_banquetas($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idBanquetas = convierte_a_arreglo(DB::select("SELECT CODBANQUETAS FROM FEXAVA_CATBANQUETAS WHERE CODBANQUETAS = '$valor'"));    
        if(count($idBanquetas) == 0){            
            return "el codigo de banqueta ".$valor." no existe en el catalogo de banqueta";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_guarniciones($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idGuarniciones = convierte_a_arreglo(DB::select("SELECT CODGUARNICIONES FROM FEXAVA_CATGUARNICIONES WHERE CODGUARNICIONES = '$valor'"));    
        if(count($idGuarniciones) == 0){            
            return "el codigo de guarnicion ".$valor." no existe en el catalogo de guarnicion";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_gas_natural($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idGasNatural = convierte_a_arreglo(DB::select("SELECT CODGASNATURAL FROM FEXAVA_CATGASNATURAL WHERE CODGASNATURAL = '$valor'"));    
        if(count($idGasNatural) == 0){            
            return "el codigo de gas natural ".$valor." no existe en el catalogo de gas natural";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_suministro_telefonico($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idSuministroTelefonico = convierte_a_arreglo(DB::select("SELECT CODSUMINISTROTELEFONICA FROM FEXAVA_CATSUMINISTROTEL WHERE CODSUMINISTROTELEFONICA = '$valor'"));    
        if(count($idSuministroTelefonico) == 0){            
            return "el codigo de suministro telefonico ".$valor." no existe en el catalogo de suministro telefonico";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_senalizacion_vias($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idSenalizacionVias = convierte_a_arreglo(DB::select("SELECT CODSENALIZACIONVIAS FROM FEXAVA_CATSENALIZACIONVIAS WHERE CODSENALIZACIONVIAS = '$valor'"));    
        if(count($idSenalizacionVias) == 0){            
            return "el codigo de señalizacion ".$valor." no existe en el catalogo de señalizacion vias";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_nomenclatura_calles($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idNomenclaturaCalle = convierte_a_arreglo(DB::select("SELECT CODNOMENCLATURACALLE FROM FEXAVA_CATNOMENCLATURACALLE WHERE CODNOMENCLATURACALLE = '$valor'"));    
        if(count($idNomenclaturaCalle) == 0){            
            return "el codigo de nomenclatura calle ".$valor." no existe en el catalogo de nomenclatura calle";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_vigilancia_zona($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idVigilanciaZona = convierte_a_arreglo(DB::select("SELECT CODVIGILANCIAZONA FROM FEXAVA_CATVIGILANCIAZONA WHERE CODVIGILANCIAZONA = '$valor'"));    
        if(count($idVigilanciaZona) == 0){            
            return "el codigo de vigilancia ".$valor." no existe en el catalogo de vigilancia zona";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_recoleccion_basura($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idRecoleccionBasura = convierte_a_arreglo(DB::select("SELECT CODRECOLECCIONBASURA FROM FEXAVA_CATRECOLECCIONBASURA WHERE CODRECOLECCIONBASURA = '$valor'"));    
        if(count($idRecoleccionBasura) == 0){            
            return "el codigo de recoleccion basura ".$valor." no existe en el catalogo de Recoleccion Basura";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_boolean($valor){    
    $estado = 'correcto';    
    if(boolval($valor) || $valor == '0' || $valor == '1'){    
        return $estado;
    }else{
        return "no es un valor booleano";
    }
}

function val_decimal($valor){
    $estado = 'correcto';
    if(is_numeric($valor)){
        return $estado;
    }else{
        return "contiene un valor que no es decimal";
    }        
}

function val_base64_binary($valor){
    $estado = 'correcto';
    try {

        $img = base64_decode($valor);                    
        $infoImg = getimagesizefromstring($img);

        if(isset($infoImg) && count($infoImg) > 0){
            return $estado;
        }else{
            return "no contiene un base64";
        }
       
    } catch (\Throwable $th) {
        return "no contiene un base64";
    }
    
}

function val_cat_topografia($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idTopografia = convierte_a_arreglo(DB::select("SELECT CODTOPOGRAFIA FROM FEXAVA_CATTOPOGRAFIA WHERE CODTOPOGRAFIA = '$valor'"));    
        if(count($idTopografia) == 0){            
            return "el codigo de topografia ".$valor." no existe en el catalogo de Topografia";
        }
        return $estado;
    }else{
        return $res;
    }
    
}

function val_cat_densidad_habitacional($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idDensidadHabit = convierte_a_arreglo(DB::select("SELECT CODDENSIDADHABITACIONAL FROM FEXAVA_CATDENSIDADHAB WHERE CODDENSIDADHABITACIONAL = '$valor'"));    
        if(count($idDensidadHabit) == 0){            
            return "el codigo de densidad habitacional ".$valor." no existe en el catalogo de densidad habitacional";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_nullable_decimal_positivo($valor){
    $estado = 'correcto';    
    if(val_null_string($valor) == 'correcto'){
        return $estado;
    }elseif(val_decimal_positivo($valor) == 'correcto'){
        
        return $estado;
    }else{
        return "no corresponde a un formato valido para este campo";
    }
}

function val_nullable_decimal_positivop($valor){
    $estado = 'correcto';       
    if(val_null_string($valor) == 'correcto'){
        return $estado;
    }elseif(val_decimal_positivo($valor) == 'correcto'){
        
        return $estado;
    }else{
        return "no corresponde a un formato valido para este campo";
    }
}

function val_nullable_decimal($valor){
    $estado = 'correcto';    
    if(val_null_string($valor) == 'correcto'){
        return $estado;
    }elseif(val_decimal($valor) == 'correcto'){
        
        return $estado;
    }else{
        return "no corresponde a un formato valido para este campo";
    }
}

function val_nullable_decimal_positivo_tipo($valor, $tipo){
    $estado = 'correcto';
    if(val_null_string($valor) == 'correcto'){
        return $estado;
    }elseif(val_decimal_positivo_tipo($valor, $tipo) == 'correcto'){
        return $estado;
    }else{
        return "no corresponde a un formato valido para este campo";
    }
}

function val_null_string($valor){
    if($valor === '' || $valor === null){
        return 'correcto';
    }
}

function val_usos_construcciones($valor){
    $estado = 'correcto';  
    $idUsos = convierte_a_arreglo(DB::select("SELECT IDUSOS FROM FIS.FIS_CATUSOS WHERE CODUSO = '$valor'"));    
    if(count($idUsos) == 0){            
        return "el codigo de uso construccion ".$valor." no existe en el catalogo de usos";
    }
    return $estado;    
}

function val_ejercicio($valor){
    $estado = 'correcto';  
    $idUsos = convierte_a_arreglo(DB::select("SELECT IDUSOS FROM FIS.FIS_EJERCICIO WHERE CODUSO = '$valor'"));    
    if(count($idUsos) == 0){            
        return "el codigo de uso construccion ".$valor." no existe en el catalogo de usos";
    }
    return $estado;    
}

function val_nullable_cat_rango_nivel_tgdf($valor){
    $estado = 'correcto';
    if(val_null_string($valor) == 'correcto'){
        return $estado;
    }elseif(val_cat_rango_nivel_tgdf($valor) == 'correcto'){
        return $estado;
    }else{
        return "no corresponde a un formato valido para este campo";
    }
}

function val_cat_rango_nivel_tgdf($valor){
    $estado = 'correcto';
    $arrRangos = array('01','02','05','10','15','20','99','RU');   
    if(in_array($valor,$arrRangos)){
        return $estado;
    }else{
        return "el codigo de nivel ".$valor." no existe en el catalogo de nivel";
    }        
}

function val_nullable_cat_estado_conservacion($valor){
    $estado = 'correcto';
    $res = val_nonNegativeInteger($valor);
    if($res == 'correcto'){
        $idEstadoConservacion = convierte_a_arreglo(DB::select("SELECT CODESTADOCONSERVACION FROM FEXAVA_CATESTADOCONSERV WHERE CODESTADOCONSERVACION = '$valor'"));    
        if(count($idEstadoConservacion) == 0){            
            return "el codigo de estado conservacion".$valor." no existe en el catalogo de estado conservacion";
        }
        return $estado;
    }else{
        return $res;
    }
}

function val_cat_instalaciones_especiales($valor){
    return 'correcto';
    /*$estado = 'correcto';
    $arrInstalacionesEspeciales = array('IE01','IE02','IE03','IE04','IE05','IE06','IE07','IE08','IE09','IE10','IE11','IE12','IE13','IE14','IE15','IE16','IE17','IE18','IE19','EA01','EA02','EA03','EA04','EA05','EA06','EA07','EA08','EA09','EA10','EA11','EA12','OC01','OC02','OC03','OC04','OC05','OC06','OC07','OC08','OC09','OC10','OC11','OC12','OC13','OC14','OC15','OC16','OC17');
    if(in_array($valor,$arrInstalacionesEspeciales)){
        return $estado;
    }else{
        return "el codigo de instalaciones especiales ".$valor." no existe en el catalogo de instalaciones especiales";
    }*/
}

/**************************************************************************************************************************************************************************/

function valida_AvaluoIdentificacion($data){    
    //$elementos = array('NumeroDeAvaluo','FechaAvaluo','ClaveValuador','ClaveSociedad');
    $arrIDS = array('NumeroDeAvaluo' => 'a.1','FechaAvaluo' => 'a.2','ClaveValuador' => 'a.3','ClaveSociedad' => 'a.4');
    $validaciones = array('NumeroDeAvaluo' => 'nonEmptyString_30','FechaAvaluo' => 'date','ClaveValuador' => 'nonEmptyString_20','ClaveSociedad' => 'string_20');  
    $errores = array();
    $data = array_map("convierte_a_arreglo",$data);
    $numeroIdentificaciones = count($data);
    if($numeroIdentificaciones == 0){
        $errores[] = "El XML no cuenta con la parte de Identificacion";
    }elseif ($numeroIdentificaciones > 1) {
        $errores[] = "El XML cuenta con mas de una Identificacion";
    }else{
        foreach($validaciones as $etiqueta => $validacion){
            if(!isset($data[0][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiqueta." en Identificacion";
            }else{                
                $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);    
                if($resValidacion != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiqueta." ".$resValidacion;
                }
            }
        }    
        
    }
    return $errores;
}

function valida_AvaluoAntecedentes($data, $elementoPrincipal){
    $arrIDS = array('PropositoDelAvaluo' => 'b.4', 'ObjetoDelAvaluo' => 'b.5', 'RegimenDePropiedad' => 'b.6','A.Paterno' => 'b.1.1','A.Materno' => 'b.1.2','Nombre' => 'b.1.3','Calle' => 'b.1.4','NumeroInterior' => 'b.1.5', 'NumeroExterior' => 'b.1.6','Colonia' =>'b.1.7', 'CodigoPostal' => 'b.1.8', 'Alcaldia' => 'b.1.9', 'ClaveAlcaldia' => 'b.1.9.1', 'TipoPersona' => 'b.1.10',
    'Calle' => 'b.3.1','NumeroInterior' => 'b.3.2','NumeroExterior' => 'b.3.3','Manzana' => 'b.3.4','Lote' => 'b.3.5', 'Edificio' => 'b.3.6','Colonia' =>'b.3.7', 'CodigoPostal' => 'b.3.8', 'Alcaldia' => 'b.3.9', 'CuentaCatastral' => 'b.3.10', 'CuentaDeAgua' => 'b.3.11',
    'Region' => 'b.3.10.1', 'Manzana' => 'b.3.10.2', 'Lote' => 'b.3.10.3', 'Localidad' => 'b.3.10.4', 'DigitoVerificador' => 'b.3.10.5', 'ClavePropositoAvaluo' => 'b.4.1', 'ClaveTipoInmueble' => 'b.7.1');

    $arrIDSb2 = array('A.Paterno' => 'b.2.1','A.Materno' => 'b.2.2','Nombre' => 'b.2.3','Calle' => 'b.2.4','NumeroInterior' => 'b.2.5', 'NumeroExterior' => 'b.2.6','Colonia' =>'b.2.7', 'CodigoPostal' => 'b.2.8', 'Alcaldia' => 'b.2.9', 'ClaveAlcaldia' => 'b.2.9.1', 'TipoPersona' => 'b.2.10');

    if($elementoPrincipal == '//Comercial'){
        $validacionesb = array('ObjetoDelAvaluo' => 'nonEmptyString_70', 'RegimenDePropiedad' => 'catRegimen');    
        $validacionesb1 = array('A.Paterno' => 'string_35','A.Materno' => 'string_35','Nombre' => 'nonEmptyString_50','Calle' => 'nonEmptyString_50','NumeroInterior' => 'nonEmptyString_30', 'NumeroExterior' => 'nonEmptyString_25','Colonia' =>'catColonia', 'CodigoPostal' => 'nonEmptyString_5','TipoPersona' => 'subTipoPersona');
        $validacionesb19 = array('ClaveAlcaldia' => 'catDelegacion');
        $validacionesb2 = array('A.Paterno' => 'string_35','A.Materno' => 'string_35','Nombre' => 'nonEmptyString_50','Calle' => 'nonEmptyString_50','NumeroInterior' => 'nonEmptyString_30', 'NumeroExterior' => 'nonEmptyString_25','Colonia' =>'catColonia', 'CodigoPostal' => 'nonEmptyString_5','TipoPersona' => 'subTipoPersona');
        $validacionesb29 = array('ClaveAlcaldia' => 'catDelegacion');
        $validacionesb3 = array('Calle' => 'nonEmptyString_50','NumeroInterior' => 'nonEmptyString_30','NumeroExterior' => 'nonEmptyString_25','Manzana' => 'string_50','Lote' => 'string_50', 'Edificio' => 'string_50','Colonia' =>'catColonia', 'CodigoPostal' => 'nonEmptyString_5', 'Alcaldia' => 'catDelegacion', 'CuentaCatastral' => 'validacionesb310', 'CuentaDeAgua' => '');
        $validacionesb4 = array('ClavePropositoAvaluo' => 'nonEmptyString');
        $validacionesb7 = array('ClaveTipoInmueble' => 'nonEmptyString');
        $validacionesb310 = array('Region' => 'regionManzanaUp', 'Manzana' => 'regionManzanaUp', 'Lote' => 'lote', 'Localidad' => 'regionManzanaUp', 'DigitoVerificador' => 'digitoVerificador');
    }else{
        $validacionesb = array('ObjetoDelAvaluo' => 'nonEmptyString_70', 'RegimenDePropiedad' => 'catRegimen');
        $validacionesb1 = array('A.Paterno' => 'string_35','A.Materno' => 'string_35','Nombre' => 'nonEmptyString_50','Calle' => 'nonEmptyString_50','NumeroInterior' => 'nonEmptyString_30', 'NumeroExterior' => 'nonEmptyString_25','Colonia' =>'catColonia', 'CodigoPostal' => 'nonEmptyString_5', 'Alcaldia' => 'catDelegacion','TipoPersona' => 'subTipoPersona');
        $validacionesb19 = array('ClaveAlcaldia' => 'catDelegacion');
        $validacionesb2 = array('A.Paterno' => 'string_35','A.Materno' => 'string_35','Nombre' => 'nonEmptyString_50','Calle' => 'nonEmptyString_50','NumeroInterior' => 'nonEmptyString_30', 'NumeroExterior' => 'nonEmptyString_25','Colonia' =>'catColonia', 'CodigoPostal' => 'nonEmptyString_5', 'Alcaldia' => 'catDelegacion','TipoPersona' => 'subTipoPersonaProp');
        $validacionesb29 = array('ClaveAlcaldia' => 'catDelegacion');
        $validacionesb3 = array('Calle' => 'nonEmptyString_50','NumeroInterior' => 'nonEmptyString_30','NumeroExterior' => 'nonEmptyString_25','Manzana' => 'string_50','Lote' => 'string_50', 'Edificio' => 'string_50','Colonia' =>'catColonia', 'CodigoPostal' => 'nonEmptyString_5', 'Alcaldia' => 'catDelegacion', 'CuentaCatastral' => 'validacionesb310', 'CuentaDeAgua' => '');
        $validacionesb4 = array('ClavePropositoAvaluo' => 'nonEmptyString');
        $validacionesb7 = array('ClaveTipoInmueble' => 'nonEmptyString');
        $validacionesb310 = array('Region' => 'regionManzanaUp', 'Manzana' => 'regionManzanaUp', 'Lote' => 'lote', 'Localidad' => 'regionManzanaUp', 'DigitoVerificador' => 'digitoVerificador');
    }
    
    $errores = array();
    $data = array_map("convierte_a_arreglo",$data);
    $numeroAntecedentes = count($data);    
    if($numeroAntecedentes == 0){
        $errores[] = "El XML no cuenta con la parte de Antecedentes";
    }elseif ($numeroAntecedentes > 1) {
        $errores[] = "El XML cuenta con mas de un Antecedente";
    }else{
        foreach($validacionesb1 as $etiqueta => $validacion){
            if(!isset($data[0]['Solicitante'][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiqueta." en Antecedentes (Solicitante)";
            }else{                   
                $resValidacionSolicitante = define_validacion($validacion, $data[0]['Solicitante'][$etiqueta]);                
                if($resValidacionSolicitante != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiqueta." ".$resValidacionSolicitante;
                }                
            }
        }

        foreach($validacionesb19 as $etiqueta => $validacion){
            if(!isset($data[0]['Solicitante']['Alcaldia'][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiqueta." en Antecedentes (Solicitante)";
            }else{                   
                $resValidacionSolicitante = define_validacion($validacion, $data[0]['Solicitante']['Alcaldia'][$etiqueta]);                
                if($resValidacionSolicitante != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiqueta." ".$resValidacionSolicitante;
                }else{
                    if($etiqueta == 'ClaveAlcaldia' && $data[0]['Solicitante']['Alcaldia'][$etiqueta] == '018'){
                        if(!isset($data[0]['Solicitante']['Alcaldia']['Otros']) || is_array($data[0]['Solicitante']['Alcaldia']['Otros']) || trim($data[0]['Solicitante']['Alcaldia']['Otros']) == ''){
                            $errores[] = $arrIDS[$etiqueta]." - El campo Otros es requerido en Antecedentes (Solicitante)";
                        }
                    }
                }              
            }
        }
        
        foreach($validacionesb2 as $etiqueta => $validacion){
            if(!isset($data[0]['Propietario'][$etiqueta])){
                $errores[] = $arrIDSb2[$etiqueta]." -Falta ".$etiqueta." en Antecedentes (Propietario)";
            }else{
                $resValidacionPropietario = define_validacion($validacion, $data[0]['Propietario'][$etiqueta]);                
                if($resValidacionPropietario != 'correcto'){
                    $errores[] = $arrIDSb2[$etiqueta]." -El campo ".$etiqueta." ".$resValidacionPropietario;
                }
            }
        }

        foreach($validacionesb29 as $etiqueta => $validacion){
            if(!isset($data[0]['Propietario']['Alcaldia'][$etiqueta])){
                $errores[] = $arrIDSb2[$etiqueta]." - Falta ".$etiqueta." en Antecedentes (Propietario)";
            }else{                   
                $resValidacionPropietario = define_validacion($validacion, $data[0]['Propietario']['Alcaldia'][$etiqueta]);                
                if($resValidacionPropietario != 'correcto'){
                    $errores[] = $arrIDSb2[$etiqueta]." - El campo ".$etiqueta." ".$resValidacionPropietario;
                }else{
                    if($etiqueta == 'ClaveAlcaldia' && $data[0]['Propietario']['Alcaldia'][$etiqueta] == '018'){
                        if(!isset($data[0]['Propietario']['Alcaldia']['Otros']) || is_array($data[0]['Propietario']['Alcaldia']['Otros']) || trim($data[0]['Propietario']['Alcaldia']['Otros']) == ''){
                            $errores[] = $arrIDSb2[$etiqueta]." - El campo Otros es requerido en Antecedentes (Propietario)";
                        }
                    }
                }              
            }
        }

        foreach($validacionesb3 as $etiqueta => $validacion){
            if(!isset($data[0]['InmuebleQueSeValua'][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiqueta." en Antecedentes (InmuebleQueSeValua)";
            }else{
                if($etiqueta == 'CuentaCatastral'){ 
                    $arrCuentaCatastral = $data[0]['InmuebleQueSeValua'][$etiqueta];                                    
                    foreach($validacionesb310 as $etiquetaCatastral => $validacionCatastral){
                        if(!isset($arrCuentaCatastral[$etiquetaCatastral])){
                            $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiquetaCatastral." en Antecedentes (CuentaCatastral)";
                        }else{
                            
                            $resValidacionCuentaCatastral = define_validacion($validacionCatastral, $arrCuentaCatastral[$etiquetaCatastral]);                
                            if($resValidacionCuentaCatastral != 'correcto'){
                                $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiquetaCatastral." ".$resValidacionCuentaCatastral;
                            }
                        }
                    }
                }else{
                    
                    $resValidacionInmuebleQueSeValua = define_validacion($validacion, $data[0]['InmuebleQueSeValua'][$etiqueta]);                
                    if($resValidacionInmuebleQueSeValua != 'correcto'){
                        $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiqueta.": ".$resValidacionInmuebleQueSeValua;
                    }
                }
                
            }
        }

        foreach($validacionesb4 as $etiqueta => $validacion){
            if(!isset($data[0]['PropositoDelAvaluo'][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." -Falta ".$etiqueta." en PropositoDelAvaluo";
            }else{
                $resValidacion = define_validacion($validacion, $data[0]['PropositoDelAvaluo'][$etiqueta]);                
                if($resValidacion != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." -El campo ".$etiqueta." ".$resValidacion;
                }else{
                    if($etiqueta == "PropositoDelAvaluo" && $data[0]['PropositoDelAvaluo'][$etiqueta] == 4){
                        if(!isset($data[0]['PropositoDelAvaluo']['Otros']) || is_array($data[0]['PropositoDelAvaluo']['Otros']) || trim($data[0]['PropositoDelAvaluo']['Otros']) == ''){
                            $errores[] = "b.4.2 - El campo Otros es requerido en PropositoDelAvaluo";
                        }
                    }
                }
            }
        }

        foreach($validacionesb7 as $etiqueta => $validacion){
            if(!isset($data[0]['TipoDeInmueble'][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." -Falta ".$etiqueta." en TipoDeInmueble";
            }else{
                $resValidacion = define_validacion($validacion, $data[0]['TipoDeInmueble'][$etiqueta]);                
                if($resValidacion != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." -El campo ".$etiqueta." ".$resValidacion;
                }else{
                    if($etiqueta == 'ClaveTipoInmueble' && $data[0]['TipoDeInmueble'][$etiqueta] == 19){
                        if(!isset($data[0]['TipoDeInmueble']['Otros']) || is_array($data[0]['TipoDeInmueble']['Otros']) || trim($data[0]['TipoDeInmueble']['Otros']) == ''){
                            $errores[] = "b.7.2 - El campo Otros es requerido en TipoDeInmueble";
                        }
                    }
                }
            }
        }

        foreach($validacionesb as $etiquetaPrincipal => $validacionPrincipal){
            if(!isset($data[0][$etiquetaPrincipal])){
                $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiquetaPrincipal." en Antecedentes ";
            }else{
                $resValidacionPrincipal = define_validacion($validacionPrincipal, $data[0][$etiquetaPrincipal]);                
                if($resValidacionPrincipal != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiquetaPrincipal." ".$resValidacionPrincipal;
                }
            }
        }        
        
    }
    return $errores;
}

function valida_Calculos_e($data, $dataextra = false, $dataextrados = false, $b_6){
    
        $mensajese = array();
        $datab6 = array_map("convierte_a_arreglo",$b_6);
        $b_6 = $datab6[0][0];
        $sumatoria_e_2_1_n_11 = 0;
        $sumatoria_e_2_1_n_15 = 0;
        foreach($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas'] as $idElemento => $elemento){
            if(isset($elemento['@attributes']['id']) && $elemento['@attributes']['id'] == 'e.2.1'){
                //$e_2_1_n_9 = $elemento['VidaMinimaRemanente'];
                $e_2_1_n_8 = $elemento['VidaUtilTotalDelTipo'];
                /*$e_2_1_n_7 = $elemento['Edad'];
                $calc_e_2_1_n_9 = $e_2_1_n_8 - $e_2_1_n_7;
                if(truncate($e_2_1_n_9,2) != truncate($calc_e_2_1_n_9,2)){ //echo "COIMPARACION ".truncate($e_2_1_n_9,2)." != ".truncate($calc_e_2_1_n_9,2)."\n";
                    $mensajese[] = "e.2.1.n.9 - El cálculo de VidaUtilRemanente es erróneo ";
                }*/

                if(isset($elemento['IndiceDelCostoRemanente'])){
                    $e_2_1_n_13 = $elemento['IndiceDelCostoRemanente'];
                    $e_2_1_n_2 = $elemento['ClaveUso'];
                    if($e_2_1_n_2 != 'H'){
                        if($e_2_1_n_2 != 'W'){
                            if($e_2_1_n_13 < 0.6){
                                $mensajese[] =  "e.2.1.n.13 - Factor de edad. El valor no debe ser inferior a 0.6.";
                            }else{

                                $usos_1 = array('PE','PC','J','P');
                                /*if(in_array($e_2_1_n_2,$usos_1)){
                                    if($e_2_1_n_13 != 1){
                                        $mensajese[] =  "e.2.1.n.13 - El cálculo de FactorDeEdad es erróneo ";
                                    }
                                }else{
                                    $calc_e_2_1_n_13 = ((0.1 * $e_2_1_n_8) + (0.9 * ($e_2_1_n_8 - $e_2_1_n_7))) / $e_2_1_n_8;
                                } 
                                if(truncate($e_2_1_n_13,2) != truncate($calc_e_2_1_n_13,2)){
                                    $mensajese[] =  "e.2.1.n.13 - El cálculo de FactorDeEdad es erróneo ";
                                }*/
                            }
                        }
                    }    
                    
                }

                /* if(isset($elemento['FactorResultante'])){
                    $e_2_1_n_14 = $elemento['FactorResultante'];
                    $e_2_1_n_10 = $elemento['ClaveConservacion'];
                    $calc_e_2_1_n_14 = $e_2_1_n_13 * $e_2_1_n_10; //echo "OPERACION  ".truncate($e_2_1_n_14,2)." != ".truncate($calc_e_2_1_n_14,2)."\n";
                    if((truncate($e_2_1_n_14,2) != truncate($calc_e_2_1_n_14,2)) || $e_2_1_n_14 < 0.6){
                        $mensajese[] =  "e.2.1.n.14 - El cálculo de FactorResultante es erróneo ";
                    }
                } */

                if(isset($dataextra) && $dataextra != false){
                    if($dataextra == '//Comercial'){
                        $e_2_1_n_15 = $elemento['CostoDeLaFraccionN'];
                        $e_2_1_n_12 = $elemento['CostoUnitarioDeReposicionNuevo'];
                        $e_2_1_n_11 = $elemento['Superficie'];
                        $calc_e_2_1_n_15 = $e_2_1_n_12 * $e_2_1_n_13 * $e_2_1_n_11;

                    }else{
                        $e_2_1_n_15 = $elemento['CostoDeLaFraccionN'];
                        $e_2_1_n_16 = $elemento['ValorUnitarioCatastral'];
                        $e_2_1_n_17 = $elemento['DepreciacionPorEdad'];
                        $e_2_1_n_11 = $elemento['Superficie'];
                        $calc_e_2_1_n_15 = ($e_2_1_n_16 * $e_2_1_n_17) * $e_2_1_n_11;
                    }
                    //echo "COMPARACION CostoDeLaFraccionN ".truncate($e_2_1_n_15,2)." != ".truncate($calc_e_2_1_n_15,2)."\n";
                    $sumatoria_e_2_1_n_11 = $sumatoria_e_2_1_n_11 + $e_2_1_n_11;
                    $sumatoria_e_2_1_n_15 = $sumatoria_e_2_1_n_15 + $e_2_1_n_15;
                    if(truncate($e_2_1_n_15,2) != truncate($calc_e_2_1_n_15,2)){
                        $mensajese[] =  "e.2.1.n.15 - El cálculo de CostoDeLaFraccionN es erróneo ";
                    }
                }

                if($dataextra == '//Catastral'){
                    if(isset($elemento['DepreciacionPorEdad'])){
                        $e_2_1_n_17 = $elemento['DepreciacionPorEdad'];
                        $e_2_1_n_7 = $elemento['Edad'];
                        //antes se tenia (100-min(40,$e_2_1_n_7 * 1)) / 100
                        $calc_e_2_1_n_17 = $e_2_1_n_7 >= 50 ? (100-(50 * 0.8)) / 100 : (100-(0.8 * $e_2_1_n_7)) / 100;
                        //echo "COMPARACION DepreciacionPorEdad ".truncate($e_2_1_n_17,2)." != ".truncate($calc_e_2_1_n_17,2)."\n"; exit();
                        if(truncate($e_2_1_n_17,2) != truncate($calc_e_2_1_n_17,2)){ //echo "COMPARACION DepreciacionPorEdad ".truncate($e_2_1_n_17,2)." != ".truncate($calc_e_2_1_n_17,2)."\n"; exit();
                            $mensajese[] =  "e.2.1.n.17 - El cálculo de DepreciacionPorEdad es erróneo ";
                        }
                    }
                }
                
                
            }else{
                if(isset($elemento['id']) && $elemento['id'] == 'e.2.1'){
                    //$e_2_1_n_9 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['VidaMinimaRemanente'];
                    $e_2_1_n_8 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['VidaUtilTotalDelTipo'];
                    $e_2_1_n_7 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Edad'];
                    $calc_e_2_1_n_9 = $e_2_1_n_8 - $e_2_1_n_7; 
                    /*if(truncate($e_2_1_n_9,2) != truncate($calc_e_2_1_n_9,2)){ //echo "OPERACION ".truncate($e_2_1_n_9,2)." != ".truncate($calc_e_2_1_n_9,2)."\n";
                        $mensajese[] =  "e.2.1.n.9 - El cálculo de VidaUtilRemanente es erróneo ";
                    }*/

                    if(isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['IndiceDelCostoRemanente'])){
                        $e_2_1_n_13 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['IndiceDelCostoRemanente'];
                        $e_2_1_n_2 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['ClaveUso'];
                        if($e_2_1_n_2 != 'H'){
                            if($e_2_1_n_2 != 'W'){
                                if($e_2_1_n_13 < 0.6){
                                    $mensajese[] =  "e.2.1.n.13 - IndiceDelCostoRemanente. El valor no debe ser inferior a 0.6.";
                                }else{
    
                                    $usos_1 = array('PE','PC','J','P');
                                    /*if(in_array($e_2_1_n_2,$usos_1)){
                                        if($e_2_1_n_13 != 1){
                                            $mensajese[] =  "e.2.1.n.13 - El cálculo de FactorDeEdad es erróneo ";
                                        }
                                    }else{
                                        $calc_e_2_1_n_13 = ((0.1 * $e_2_1_n_8) + (0.9 * ($e_2_1_n_8 - $e_2_1_n_7))) / $e_2_1_n_8;
                                    } 
                                    if(truncate($e_2_1_n_13,2) != truncate($calc_e_2_1_n_13,2)){
                                        $mensajese[] =  "e.2.1.n.13 - El cálculo de FactorDeEdad es erróneo ";
                                    }*/
                                }
                            }
                        }
                        /*$usos_1 = array('PE','PC','J','P');
                        if(in_array($e_2_1_n_2,$usos_1)){ 
                            if($e_2_1_n_13 != 1){ //echo "OPERACION 1 ".truncate($e_2_1_n_13,2)." != 1 \n";
                                $mensajese[] =  "El calculo de e.2.1.n.13 es erroneo ";
                            }
                        }else{ 
                            $calc_e_2_1_n_13 = ((0.1 * $e_2_1_n_8) + (0.9 * ($e_2_1_n_8 - $e_2_1_n_7))) / $e_2_1_n_8;
                        } 
                        if(truncate($e_2_1_n_13,2) != truncate($calc_e_2_1_n_13,2)){ //echo "OPERACION ".truncate($e_2_1_n_13,2)." != ".truncate($calc_e_2_1_n_13,2)."\n";
                            $mensajese[] =  "El calculo de e.2.1.n.13 es erroneo ";
                        }*/
                    }

                    /*if(isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['FactorResultante'])){
                        $e_2_1_n_14 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['FactorResultante'];
                        $e_2_1_n_10 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['ClaveConservacion'];
                        $calc_e_2_1_n_14 = $e_2_1_n_13 * $e_2_1_n_10; 
                        if((truncate($e_2_1_n_14,2) != truncate($calc_e_2_1_n_14,2)) || $e_2_1_n_14 < 0.6){ //echo "OPERACION  ".truncate($e_2_1_n_14,2)." != ".truncate($calc_e_2_1_n_14,2)."\n";
                            $mensajese[] =  "e.2.1.n.14 - El cálculo de FactorResultante es erróneo ";
                        }
                    } */

                    if(isset($dataextra) && $dataextra != false){
                        if($dataextra == '//Comercial'){
                            $e_2_1_n_15 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['CostoDeLaFraccionN'];
                            $e_2_1_n_12 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['CostoUnitarioDeReposicionNuevo'];
                            $e_2_1_n_11 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Superficie'];
                            $calc_e_2_1_n_15 = $e_2_1_n_12 * $e_2_1_n_13 * $e_2_1_n_11;
    
                        }else{
                            $e_2_1_n_15 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['CostoDeLaFraccionN'];
                            $e_2_1_n_16 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['ValorUnitarioCatastral'];
                            $e_2_1_n_17 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['DepreciacionPorEdad'];
                            $e_2_1_n_11 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Superficie'];
                            $calc_e_2_1_n_15 = ($e_2_1_n_16 * $e_2_1_n_17) * $e_2_1_n_11;
                        }
                        $sumatoria_e_2_1_n_11 = $sumatoria_e_2_1_n_11 + $e_2_1_n_11;
                        $sumatoria_e_2_1_n_15 = $sumatoria_e_2_1_n_15 + $e_2_1_n_15;
                        if(truncate($e_2_1_n_15,2) != truncate($calc_e_2_1_n_15,2)){ //echo "COMPARACION DepreciacionPorEdad ".truncate($e_2_1_n_15,2)." != ".truncate($calc_e_2_1_n_15,2)."\n";
                            $mensajese[] =  "e.2.1.n.15 - El cálculo de CostoDeLaFraccionN es erróneo ";
                        }
                    }

                    if(isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['DepreciacionPorEdad'])){
                        $e_2_1_n_17 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['DepreciacionPorEdad'];
                        $e_2_1_n_7 = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Edad'];
                        //$calc_e_2_1_n_17 = (100-min(40,$e_2_1_n_7 * 1)) / 100;
                        $calc_e_2_1_n_17 = $e_2_1_n_7 >= 50 ? (100-(50 * 0.8)) / 100 : (100-(0.8 * $e_2_1_n_7)) / 100;
                        if(truncate($e_2_1_n_17,2) != truncate($calc_e_2_1_n_17,2)){ //echo "COMPARACION DepreciacionPorEdad ".truncate($e_2_1_n_17,2)." != ".truncate($calc_e_2_1_n_17,2)."\n";
                            $mensajese[] =  "e.2.1.n.17 - El cálculo de CostoDeLaFraccionN es erróneo ";
                        }
                    }
                }
                
            }       
        }

        $e_2_2 = $data[0]['TiposDeConstruccion']['SuperficieTotalDeConstruccionesPrivativas'];
        if(truncate($sumatoria_e_2_1_n_11,2) != truncate($e_2_2,2)){ //echo "OPERACION ".truncate($sumatoria_e_2_1_n_11,2)." != ".truncate($e_2_2,2)."\n";
            $mensajese[] =  "e.2.2 - El cálculo de SuperficieTotalDeConstruccionesPrivativas es erróneo ";
        }

        $e_2_3 = $data[0]['TiposDeConstruccion']['ValorTotalDeConstruccionesPrivativas'];
        if(truncate($sumatoria_e_2_1_n_15,2) != truncate($e_2_3,2)){ //echo "OPERACION ".truncate($sumatoria_e_2_1_n_15,2)." != ".truncate($e_2_3,2)."\n";
            $mensajese[] =  "e.2.3 - El cálculo de ValorTotalDeConstruccionesPrivativas es erróneo ";
        }
        /************************************************************************************************************************************************/

        
        if(isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes'])){
            //echo "CONSTRUCCIONES COMUNES ADENTRO "; var_dump(isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes'])); exit();
            $sumatoria_e_2_5_n_11 = 0;
            $sumatoria_e_2_5_n_15 = 0;
            $para_e_2_8 = 0;
            foreach($data[0]['TiposDeConstruccion']['ConstruccionesComunes'] as $idElemento => $elemento){
                if(isset($elemento['@attributes']['id']) && $elemento['@attributes']['id'] == 'e.2.5'){
                    $e_2_5_n_9 = $elemento['VidaMinimaRemanente'];
                    //$e_2_5_n_8 = $elemento['VidaUtilTotalDelTipo'];
                    $e_2_5_n_7 = $elemento['Edad'];
                    /*$calc_e_2_5_n_9 = $e_2_5_n_8 - $e_2_5_n_7;
                    if(truncate($e_2_5_n_9,2) != truncate($calc_e_2_5_n_9,2)){ //echo "OPERACION ".truncate($e_2_5_n_9,2)." != ".truncate($calc_e_2_5_n_9,2)."\n";
                        $mensajese[] =  "e.2.5.n.9 - El cálculo de VidaMinimaRemanente es erróneo ";
                    }*/

                    if(isset($elemento['IndiceDelCostoRemanente'])){
                        $e_2_5_n_13 = $elemento['IndiceDelCostoRemanente'];
                        $e_2_5_n_2 = $elemento['ClaveUso'];
                        $usos_1 = array('PE','PC','J','P');
                        /*if(in_array($e_2_5_n_2,$usos_1)){
                            if($e_2_5_n_13 != 1){
                                $mensajese[] =  "e.2.5.n.13 - El cálculo de IndiceDelCostoRemanente es erróneo ";
                            }
                        }else{
                            $calc_e_2_5_n_13 = ((0.1 * $e_2_5_n_8) + (0.9 * ($e_2_5_n_8 - $e_2_5_n_7))) / $e_2_5_n_8;
                        } 
                        if(truncate($e_2_5_n_13,2) != truncate($calc_e_2_5_n_13,2)){
                            $mensajese[] =  "e.2.5.n.13 - El cálculo de IndiceDelCostoRemanente es erróneo ";
                        }*/
                    }

                    /*if(isset($elemento['FactorResultante'])){
                        $e_2_5_n_14 = $elemento['FactorResultante'];
                        $e_2_5_n_10 = $elemento['ClaveConservacion'];
                        $calc_e_2_5_n_14 = $e_2_5_n_13 * $e_2_5_n_10; //echo "OPERACION  ".truncate($e_2_5_n_14,2)." != ".truncate($calc_e_2_5_n_14,2)."\n";
                        if(truncate($e_2_5_n_14,2) != truncate($calc_e_2_5_n_14,2)){
                            $mensajese[] =  "e.2.5.n.14 - El cálculo de FactorResultante es erróneo ";
                        }
                    }*/

                    if(isset($dataextra) && $dataextra != false){
                        if($dataextra == '//Comercial'){
                            $e_2_5_n_18 = $elemento['PorcentajeIndivisoComunes'];
                            $e_2_5_n_15 = $elemento['CostoDeLaFraccionN'];
                            $e_2_5_n_12 = $elemento['CostoUnitarioDeReposicionNuevo'];
                            $e_2_5_n_11 = $elemento['Superficie'];
                            //$calc_e_2_5_n_15 = $e_2_5_n_12 * $e_2_5_n_14 * $e_2_5_n_11;
                            $calc_e_2_5_n_15 = $e_2_5_n_12 * $e_2_5_n_13 * $e_2_5_n_11;
                            $para_e_2_8 = ($para_e_2_8 + ($e_2_5_n_15 * $e_2_5_n_18));

                        }else{                        
                            $e_2_5_n_15 = $elemento['CostoDeLaFraccionN'];
                            $e_2_5_n_16 = $elemento['ValorUnitarioCatastral'];
                            $e_2_5_n_17 = $elemento['DepreciacionPorEdad'];
                            $e_2_5_n_11 = $elemento['Superficie'];
                            $calc_e_2_5_n_15 = ($e_2_5_n_16 * $e_2_5_n_17) * $e_2_5_n_11;
                        }
                        //echo "COMPARACION CostoDeLaFraccionN ".truncate($e_2_5_n_15,2)." != ".truncate($calc_e_2_5_n_15,2)."\n";
                        $sumatoria_e_2_5_n_11 = $sumatoria_e_2_5_n_11 + $e_2_5_n_11;
                        $sumatoria_e_2_5_n_15 = $sumatoria_e_2_5_n_15 + $e_2_5_n_15;                    
                        if(truncate($e_2_5_n_15,2) != truncate($calc_e_2_5_n_15,2)){
                            $mensajese[] =  "e.2.5.n.15 - El cálculo de CostoDeLaFraccionN es erróneo ";
                        }
                    }

                    if($dataextra == '//Catastral'){
                        if(isset($elemento['DepreciacionPorEdad'])){
                            $e_2_5_n_17 = $elemento['DepreciacionPorEdad'];
                            $e_2_5_n_7 = $elemento['Edad'];
                            //$calc_e_2_5_n_17 = (100-min(50,$e_2_5_n_7 * 1)) / 100; //echo "COMPARACION DepreciacionPorEdad ".truncate($e_2_5_n_17,2)." != ".truncate($calc_e_2_5_n_17,2)."\n";
                            $calc_e_2_5_n_17 = $e_2_5_n_7 >= 50 ? (100-(50 * 0.8)) / 100 : (100-(0.8 * $e_2_5_n_7)) / 100;
                            if(truncate($e_2_5_n_17,2) != truncate($calc_e_2_5_n_17,2)){
                                $mensajese[] =  "e.2.5.n.17 - El cálculo de DepreciacionPorEdad es erróneo ";
                            }
                        }
                    }
                    
                    
                }else{
                    if(isset($elemento['id']) && $elemento['id'] == 'e.2.5'){
                        $e_2_5_n_9 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['VidaMinimaRemanente'];
                        //$e_2_5_n_8 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['VidaUtilTotalDelTipo'];
                        if($dataextra == '//Catastral'){
                            $e_2_5_n_7 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['Edad'];
                        }
                        /*$calc_e_2_5_n_9 = $e_2_5_n_8 - $e_2_5_n_7; //echo "OPERACION ".truncate($e_2_5_n_9,2)." != ".truncate($calc_e_2_5_n_9,2)."\n";
                        if(truncate($e_2_5_n_9,2) != truncate($calc_e_2_5_n_9,2)){
                            $mensajese[] =  "e.2.5.n.9 - El cálculo de VidaUtilRemanente es erróneo ";
                        }*/

                        if(isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['IndiceDelCostoRemanente'])){
                            $e_2_5_n_13 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['IndiceDelCostoRemanente'];
                            $e_2_5_n_2 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['ClaveUso'];
                            $usos_1 = array('PE','PC','J','P');
                            /*if(in_array($e_2_5_n_2,$usos_1)){
                                if($e_2_5_n_13 != 1){
                                    $mensajese[] =  "e.2.5.n.13 - El cálculo de e.2.5.n.13 es erróneo ";
                                }
                            }else{
                                $calc_e_2_5_n_13 = ((0.1 * $e_2_5_n_8) + (0.9 * ($e_2_5_n_8 - $e_2_5_n_7))) / $e_2_5_n_8;
                            } 
                            if(truncate($e_2_5_n_13,2) != truncate($calc_e_2_5_n_13,2)){
                                $mensajese[] =  "El cálculo de IndiceDelCostoRemanente es erróneo ";
                            }*/
                        }

                        /*if(isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['FactorResultante'])){
                            $e_2_5_n_14 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['FactorResultante'];
                            $e_2_5_n_10 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['ClaveConservacion'];
                            $calc_e_2_5_n_14 = $e_2_5_n_13 * $e_2_5_n_10; //echo "OPERACION  ".truncate($e_2_5_n_14,2)." != ".truncate($calc_e_2_5_n_14,2)."\n";
                            if(truncate($e_2_5_n_14,2) != truncate($calc_e_2_5_n_14,2)){
                                $mensajese[] =  "e.2.5.n.14 - El cálculo de FactorResultante es erróneo ";
                            }
                        }*/

                        if(isset($dataextra) && $dataextra != false){
                            if($dataextra == '//Comercial'){
                                $e_2_5_n_18 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['PorcentajeIndivisoComunes'];
                                $e_2_5_n_15 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['CostoDeLaFraccionN'];
                                $e_2_5_n_12 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['CostoUnitarioDeReposicionNuevo'];
                                $e_2_5_n_11 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['Superficie'];
                                //$calc_e_2_5_n_15 = $e_2_5_n_12 * $e_2_5_n_14 * $e_2_5_n_11;
                                $calc_e_2_5_n_15 = $e_2_5_n_12 * $e_2_5_n_13 * $e_2_5_n_11;
                                $para_e_2_8 = ($para_e_2_8 + ($e_2_5_n_15 * $e_2_5_n_18));
                            }else{
                                
                                $e_2_5_n_15 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['CostoDeLaFraccionN'];
                                $e_2_5_n_16 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['ValorUnitarioCatastral'];
                                $e_2_5_n_17 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['DepreciacionPorEdad'];
                                $e_2_5_n_11 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['Superficie'];
                                $calc_e_2_5_n_15 = ($e_2_5_n_16 * $e_2_5_n_17) * $e_2_5_n_11;
                            }
                            $sumatoria_e_2_5_n_11 = $sumatoria_e_2_5_n_11 + $e_2_5_n_11;
                            $sumatoria_e_2_5_n_15 = $sumatoria_e_2_5_n_15 + $e_2_5_n_15;
                            
                            if(truncate($e_2_5_n_15,2) != truncate($calc_e_2_5_n_15,2)){
                                $mensajese[] =  "e.2.5.n.15 - El cálculo de CostoDeLaFraccionN es erróneo ";
                            }
                        }

                        if($dataextra == '//Catastral'){
                            if(isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['DepreciacionPorEdad'])){
                                $e_2_5_n_17 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['DepreciacionPorEdad'];
                                $e_2_5_n_7 = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['Edad'];
                                //$calc_e_2_5_n_17 = (100-min(40,$e_2_5_n_7 * 1)) / 100; //echo "COMPARACION DepreciacionPorEdad ".truncate($e_2_1_n_17,2)." != ".truncate($calc_e_2_1_n_17,2)."\n";
                                $calc_e_2_5_n_17 = $e_2_5_n_7 >= 50 ? (100-(50 * 0.8)) / 100 : (100-(0.8 * $e_2_5_n_7)) / 100;
                                if(truncate($e_2_5_n_17,2) != truncate($calc_e_2_5_n_17,2)){
                                    $mensajese[] =  "e.2.5.n.17 - El cálculo de DepreciacionPorEdad es erróneo ";
                                }
                            }
                        }
                        
                    }
                    
                }            
            }

            $e_2_6 = $data[0]['TiposDeConstruccion']['SuperficieTotalDeConstruccionesComunes'];
            if(truncate($sumatoria_e_2_5_n_11,2) != truncate($e_2_6,2)){ //echo "OPERACION ".truncate($sumatoria_e_2_5_n_11,2)." != ".truncate($e_2_6,2)."\n";
                $mensajese[] =  "e.2.6 - El cálculo de SuperficieTotalDeConstruccionesComunes es erróneo ";
            }

            $e_2_7 = $data[0]['TiposDeConstruccion']['ValorTotalDeConstruccionesComunes'];
            if(truncate($sumatoria_e_2_5_n_15,2) != truncate($e_2_7,2)){ //echo "OPERACION ".truncate($sumatoria_e_2_5_n_15,2)." != ".truncate($e_2_7,2)."\n";
                $mensajese[] =  "e.2.7 - El cálculo de ValorTotalDeConstruccionesComunes es erróneo ";
            }

            $e_2_8 = $data[0]['TiposDeConstruccion']['ValorTotalDeLasConstruccionesComunesProIndiviso'];
            $d_6 = $dataextrados[0]['Indiviso'];
            $calc_e_2_8 = $b_6 == 2 ? $para_e_2_8 : $e_2_7 * $d_6;
            //$calc_e_2_8 = $e_2_7 * $d_6;
            if(truncate($calc_e_2_8,2) != truncate($e_2_8,2)){//echo "OPERACION ".truncate($calc_e_2_8,2)." != ".truncate($e_2_8,2)."\n";
                $mensajese[] =  "e.2.8 - El cálculo de ValorTotalDeLasConstruccionesComunesProIndiviso es erróneo ";
            }
        }        

        
        if(count($mensajese) > 0){
            return $mensajese;
        }else{
            return "Correcto";
        }
    
}

function valida_Calculos($data, $letra, $dataextra = false, $dataextrados = false){    
    if($letra == 'c'){
        if(isset($data[0]['UsoDelSuelo'])){
            $c_6_4 = $data[0]['UsoDelSuelo']['CoeficienteDeUsoDelSuelo'];
            $c_6_2 = $data[0]['UsoDelSuelo']['AreaLibreObligatoria'];
            $c_6_3 = $data[0]['UsoDelSuelo']['NumeroMaximoDeNivelesAConstruir']; //echo "LA OPERACION "."(1-(".$c_6_2."/100))*".$c_6_3."\n"; 
            $calc_c_6_4 = (1-($c_6_2/100))*$c_6_3; //echo "el resultado ".$calc_c_6_4."\n"; echo "La comparacion |".truncate($c_6_4,2)."| != |".truncate($calc_c_6_4,2)."| \n"; 
            if(truncate($c_6_4,2) != truncate($calc_c_6_4,2)){ //echo "ENTREEEEEE "; exit();
                return  "c.6.4 - El cálculo de CoeficienteDeUsoDelSuelo es erróneo ";
            } //exit();
        }            
    }

    if($letra == 'd'){
        $mensajesd = array();
        if(isset($data[0]['SuperficieDelTerreno'])){
            if(isset($data[0]['SuperficieDelTerreno']['Fre']) && trim($data[0]['SuperficieDelTerreno']['Fre']) != ''){
                $d_5_n_10 = $data[0]['SuperficieDelTerreno']['Fre'];
                /*if(isset($data[0]['SuperficieDelTerreno']['Fot']['Valor']) && trim($data[0]['SuperficieDelTerreno']['Fot']['Valor']) != ''){
                    $calc_d_5_n_10 = $data[0]['SuperficieDelTerreno']['Fzo'] * $data[0]['SuperficieDelTerreno']['Fub'] * $data[0]['SuperficieDelTerreno']['FFr'] * $data[0]['SuperficieDelTerreno']['Ffo'] * $data[0]['SuperficieDelTerreno']['Fsu'] * $data[0]['SuperficieDelTerreno']['Fot']['Valor'];
                }else{
                    $calc_d_5_n_10 = $data[0]['SuperficieDelTerreno']['Fzo'] * $data[0]['SuperficieDelTerreno']['Fub'] * $data[0]['SuperficieDelTerreno']['FFr'] * $data[0]['SuperficieDelTerreno']['Ffo'] * $data[0]['SuperficieDelTerreno']['Fsu'];
                }*/
                $calc_d_5_n_10 = $data[0]['SuperficieDelTerreno']['Factor1']['Valor'] * $data[0]['SuperficieDelTerreno']['Factor2']['Valor'] * $data[0]['SuperficieDelTerreno']['Factor3']['Valor'] * $data[0]['SuperficieDelTerreno']['Factor4']['Valor'] * $data[0]['SuperficieDelTerreno']['Factor5']['Valor'];
                if(truncate($d_5_n_10,2) > truncate($calc_d_5_n_10,2)){
                    $mensajesd[] =  "d.5.n.10 - El cálculo de Fre es erróneo ";
                }
            }
            
            if(isset($dataextra) && $dataextra != false){
                $d_5_n_10 = $data[0]['SuperficieDelTerreno']['Fre'];
                $dataextra = array_map("convierte_a_arreglo",$dataextra);
                $h_1_4 = $dataextra[0]['Terrenos']['ValorUnitarioDeTierraAplicableAlAvaluo'];
                $calc_d_5_n_11 = $h_1_4 * $data[0]['SuperficieDelTerreno']['SuperficieFraccionN1'] * $d_5_n_10;
                $d_5_n_11 = $data[0]['SuperficieDelTerreno']['ValorDeLaFraccionN']; //echo truncate($calc_d_5_n_11,2)." != ".truncate($d_5_n_11,2); exit();
                if(truncate($calc_d_5_n_11,2) != truncate($d_5_n_11,2)){
                    $mensajesd[] =  "d.5.n.11 - El cálculo de ValorDeLaFraccionN es erróneo ";
                }
            }
        }

        if(isset($data[0]['SuperficieTotalDelTerreno'])){
            if(truncate($data[0]['SuperficieTotalDelTerreno'],2) != truncate($data[0]['SuperficieDelTerreno']['SuperficieFraccionN1'],2)){
                $mensajesd[] =  "d.11 - El cálculo de SuperficieTotalDelTerreno es erróneo ";
            }
        }

        if(isset($data[0]['ValorTotalDelTerreno'])){
            if(truncate($data[0]['ValorTotalDelTerreno'],2) != truncate($data[0]['SuperficieDelTerreno']['ValorDeLaFraccionN'],2)){
                $mensajesd[] =  "d.12 - El cálculo de ValorTotalDelTerreno es erróneo ";
            }
        }

        if(isset($data[0]['ValorTotalDelTerrenoProporcional'])){
            $d_13 = trim($data[0]['ValorTotalDelTerrenoProporcional']);
            $d_12 = $data[0]['ValorTotalDelTerreno'];
            $d_6 = $data[0]['Indiviso'];
            $calc_d_13 = $d_6 * $d_12;
            if((String)(truncate($d_13,2)) != (String)(truncate($calc_d_13,2))){ //echo "COMPARACION |".truncate($d_13,2)."| != |".truncate($calc_d_13,2)."|\n";
                $mensajesd[] =  "d.13 - El cálculo de ValorTotalDelTerrenoProporcional es erróneo ";
            }
        }

        if(count($mensajesd) > 0){
            return $mensajesd;
        }else{
            return "Correcto";
        }
        
    }
    

    if($letra == 'f'){ 

        $datab6 = array_map("convierte_a_arreglo",$dataextrados); //print_r($datab6); exit();
        $b_6 = $datab6[0][0];
        $para_f_14 = 0;
        $mensajesf = array();
        if(isset($data[0]['InstalacionesEspeciales']) && count($data[0]['InstalacionesEspeciales']) > 1){

            $sumatoriaf_9_1_n_9 = 0;
            $sumatoriaf_9_2_n_9 = 0;

            if(isset($data[0]['InstalacionesEspeciales']['Privativas'])){

                foreach($data[0]['InstalacionesEspeciales']['Privativas'] as $idElemento => $elemento){
                    if(isset($elemento['@attributes']['id']) && $elemento['@attributes']['id'] == 'f.9.1'){
                        if(isset($elemento['EdadInstalacionEspecial']) && $elemento['VidaUtilTotalInstalacionEspecial']){
                            $f_9_1_n_8 = $elemento['FactorDeEdadInstalacionEspecial'];
                            $calc_f_9_1_n_8 = 1-($elemento['EdadInstalacionEspecial'] / $elemento['VidaUtilTotalInstalacionEspecial']);

                            /*if(truncate($calc_f_9_1_n_8,2) != truncate($f_9_1_n_8,2)){
                                $mensajesf[] =  "f.9.1.n.8 - El cálculo de FactorDeEdadInstalacionEspecial es erróneo ";
                            }*/
                        }
                        
                        if(isset($f_9_1_n_8)){
                            $f_9_1_n_9 = $elemento['ImporteInstalacionEspecial'];
                            $calc_f_9_1_n_9 = $elemento['CantidadInstalacionEspecial'] * $elemento['CostoUnitarioInstalacionEspecial'] * $f_9_1_n_8;
                            $sumatoriaf_9_1_n_9 = $sumatoriaf_9_1_n_9 + $f_9_1_n_9;                            

                            if(truncate($calc_f_9_1_n_9,2) != truncate($f_9_1_n_9,2)){
                                $mensajesf[] =  "f.9.1.n.9 - El cálculo de ImporteInstalacionEspecial es erróneo ";
                            }

                        }
                        
                        /*if(isset($f_9_1_n_9) && isset($elemento['PorcentajeIndivisoInstalacionEspecial'])){
                            $f_9_1_n_10 = isset($elemento['PorcentajeIndivisoInstalacionEspecial']);
                            $para_f_14 = $para_f_14 + ($f_9_1_n_9 * $f_9_1_n_10);
                        }*/

                    }else{
                        if(isset($elemento['id']) && $elemento['id'] == 'f.9.1'){
                            if(isset($data[0]['InstalacionesEspeciales']['Privativas']['EdadInstalacionEspecial']) && isset($data[0]['InstalacionesEspeciales']['Privativas']['VidaUtilTotalInstalacionEspecial'])){
                                $f_9_1_n_8 = $data[0]['InstalacionesEspeciales']['Privativas']['FactorDeEdadInstalacionEspecial'];
                                $calc_f_9_1_n_8 = 1-($data[0]['InstalacionesEspeciales']['Privativas']['EdadInstalacionEspecial'] / $data[0]['InstalacionesEspeciales']['Privativas']['VidaUtilTotalInstalacionEspecial']);
                                /*if(truncate($calc_f_9_1_n_8,2) != truncate($f_9_1_n_8,2)){
                                    $mensajesf[] =  "f.9.1.n.8 - El cálculo de FactorDeEdadInstalacionEspecial es erróneo ";
                                }*/
                            }
                            
                            if(isset($f_9_1_n_8)){
                                $f_9_1_n_9 = $data[0]['InstalacionesEspeciales']['Privativas']['ImporteInstalacionEspecial'];
                                $calc_f_9_1_n_9 = $data[0]['InstalacionesEspeciales']['Privativas']['CantidadInstalacionEspecial'] * $data[0]['InstalacionesEspeciales']['Privativas']['CostoUnitarioInstalacionEspecial'] * $f_9_1_n_8;
                                $sumatoriaf_9_1_n_9 = $sumatoriaf_9_1_n_9 + $f_9_1_n_9;

                                if(truncate($calc_f_9_1_n_9,2) != truncate($f_9_1_n_9,2)){
                                    $mensajesf[] =  "f.9.1.n.9 - El cálculo de ImporteInstalacionEspecial es erróneo ";
                                }
                            }

                            /*if(isset($f_9_1_n_9) && isset($data[0]['InstalacionesEspeciales']['Privativas']['PorcentajeIndivisoInstalacionEspecial'])){
                                $f_9_1_n_10 = isset($data[0]['InstalacionesEspeciales']['Privativas']['PorcentajeIndivisoInstalacionEspecial']);
                                $para_f_14 = $para_f_14 + ($f_9_1_n_9 * $f_9_1_n_10);
                            }*/
                            
                        }
                    }
                }

            }

            /********************************************************************/

            if(isset($data[0]['InstalacionesEspeciales']['Comunes'])){

                foreach($data[0]['InstalacionesEspeciales']['Comunes'] as $idElemento => $elemento){
                    if(isset($elemento['@attributes']['id']) && $elemento['@attributes']['id'] == 'f.9.2'){
                        $f_9_2_n_8 = $elemento['FactorDeEdadInstalacionEspecial'];
                        $calc_f_9_2_n_8 = 1-($elemento['EdadInstalacionEspecial'] / $elemento['VidaUtilTotalInstalacionEspecial']);

                        if(truncate($calc_f_9_2_n_8,2) != truncate($f_9_2_n_8,2)){ //echo truncate($calc_f_9_2_n_8,2)." != ".truncate($f_9_2_n_8,2)."\n";
                            $mensajesf[] =  "f.9.2.n.8 - El cálculo de FactorDeEdadInstalacionEspecial es erróneo ";
                        }

                        $f_9_2_n_9 = $elemento['ImporteInstalacionEspecial'];
                        $calc_f_9_2_n_9 = $elemento['CantidadInstalacionEspecial'] * $elemento['CostoUnitarioInstalacionEspecial'] * $f_9_2_n_8;
                        $sumatoriaf_9_2_n_9 = $sumatoriaf_9_2_n_9 + $f_9_2_n_9;

                        if(truncate($calc_f_9_2_n_9,2) != truncate($f_9_2_n_9,2)){ //echo truncate($calc_f_9_2_n_9,2)." != ".truncate($f_9_2_n_9,2)."\n";
                            $mensajesf[] =  "f.9.2.n.9 - El cálculo de ImporteInstalacionEspecial es erróneo ";
                        }

                        if(isset($f_9_2_n_9) && isset($elemento['PorcentajeIndivisoEspecial'])){
                            $f_9_2_n_10 = $elemento['PorcentajeIndivisoEspecial'];
                            $para_f_14 = $para_f_14 + ($f_9_2_n_9 * $f_9_2_n_10); //error_log("1.- ".$para_f_14);
                        }

                    }else{
                        if(isset($elemento['id']) && $elemento['id'] == 'f.9.2'){
                            $f_9_2_n_8 = $data[0]['InstalacionesEspeciales']['Comunes']['FactorDeEdadInstalacionEspecial'];
                            $calc_f_9_2_n_8 = 1-($data[0]['InstalacionesEspeciales']['Comunes']['EdadInstalacionEspecial'] / $data[0]['InstalacionesEspeciales']['Comunes']['VidaUtilTotalInstalacionEspecial']);
                            if(truncate($calc_f_9_2_n_8,2) != truncate($f_9_2_n_8,2)){ echo truncate($calc_f_9_2_n_8,2)." != ".truncate($f_9_2_n_8,2)."\n";
                                $mensajesf[] =  "f.9.2.n.8 - El cálculo de FactorDeEdadInstalacionEspecial es erróneo ";
                            }
                            
                            $f_9_2_n_9 = $data[0]['InstalacionesEspeciales']['Comunes']['ImporteInstalacionEspecial'];
                            $calc_f_9_2_n_9 = $data[0]['InstalacionesEspeciales']['Comunes']['CantidadInstalacionEspecial'] * $data[0]['InstalacionesEspeciales']['Comunes']['CostoUnitarioInstalacionEspecial'] * $f_9_2_n_8;
                            $sumatoriaf_9_2_n_9 = $sumatoriaf_9_2_n_9 + $f_9_2_n_9;

                            if(truncate($calc_f_9_2_n_9,2) != truncate($f_9_2_n_9,2)){
                                $mensajesf[] =  "f.9.2.n.9- El cálculo de ImporteInstalacionEspecial es erróneo ";
                            }

                            if(isset($f_9_2_n_9) && isset($data[0]['InstalacionesEspeciales']['Comunes']['PorcentajeIndivisoEspecial'])){
                                $f_9_2_n_10 = $data[0]['InstalacionesEspeciales']['Comunes']['PorcentajeIndivisoEspecial'];
                                $para_f_14 = $para_f_14 + ($f_9_2_n_9 * $f_9_2_n_10); //error_log("2.- ".$para_f_14);
                            }
                        }
                    }
                }

            }

            if(isset($data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesPrivativas'])){
                $f_9_3 = $data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesPrivativas'];

                if(truncate($f_9_3,2) != truncate($sumatoriaf_9_1_n_9,2)){
                    $mensajesf[] =  "f.9.3 - El cálculo de ImporteTotalInstalacionesEspecialesPrivativas es erróneo ";
                }
            }
            
            if(isset($data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesComunes'])){
                $f_9_4 = $data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesComunes'];
            
                if(truncate($f_9_4,2) != truncate($sumatoriaf_9_2_n_9,2)){ //echo truncate($sumatoriaf_9_2_n_9,2)." != ".truncate($f_9_4,2)."\n";
                    $mensajesf[] =  "f.9.4 - El cálculo de ImporteTotalInstalacionesEspecialesComunes es erróneo ";
                }
            }
            
        }

        /****************************************************************************************************************************************/
        /****************************************************************************************************************************************/

        if(isset($data[0]['ElementosAccesorios']) && count($data[0]['ElementosAccesorios']) > 1){

            $sumatoriaf_10_1_n_9 = 0;
            $sumatoriaf_10_2_n_9 = 0;

            if(isset($data[0]['ElementosAccesorios']['Privativas'])){

                foreach($data[0]['ElementosAccesorios']['Privativas'] as $idElemento => $elemento){
                    if(isset($elemento['@attributes']['id']) && $elemento['@attributes']['id'] == 'f.10.1'){
                        $f_10_1_n_8 = $elemento['FactorDeEdadElementoAccesorio'];
                        $calc_f_10_1_n_8 = 1-($elemento['EdadElementoAccesorio'] / $elemento['VidaUtilTotalElementoAccesorio']);

                        /*if((truncate($calc_f_10_1_n_8,2) != truncate($f_10_1_n_8,2)) || $f_10_1_n_8 < 0.6){ //echo truncate($calc_f_10_1_n_8,2)." != ".truncate($f_10_1_n_8,2)."\n";
                            $mensajesf[] =  "f.10.1.n.8 - El cálculo de FactorDeEdadElementoAccesorio es erróneo ";
                        }*/

                        $f_10_1_n_9 = $elemento['ImporteElementoAccesorio'];
                        $calc_f_10_1_n_9 = $elemento['CantidadElementoAccesorio'] * $elemento['CostoUnitarioElementoAccesorio'] * $f_10_1_n_8;
                        $sumatoriaf_10_1_n_9 = $sumatoriaf_10_1_n_9 + $f_10_1_n_9;

                        if(truncate($calc_f_10_1_n_9,2) != truncate($f_10_1_n_9,2)){
                            $mensajesf[] =  "f.10.1.n.9 - El cálculo de ImporteElementoAccesorio es erróneo ";
                        }

                        /*if(isset($f_10_1_n_9) && isset($elemento['PorcentajeIndivisoElementoAccesorio'])){
                            $f_10_1_n_10 = isset($elemento['PorcentajeIndivisoElementoAccesorio']);
                            $para_f_14 = $para_f_14 + ($f_10_1_n_9 * $f_10_1_n_10);
                        }*/

                    }else{
                        if(isset($elemento['id']) && $elemento['id'] == 'f.10.1'){
                            if(isset($data[0]['ElementosAccesorios']['Privativas']['FactorDeEdadElementoAccesorio'])){
                                $f_10_1_n_8 = $data[0]['ElementosAccesorios']['Privativas']['FactorDeEdadElementoAccesorio'];
                                if($data[0]['ElementosAccesorios']['Privativas']['EdadElementoAccesorio'] == 0 && $data[0]['ElementosAccesorios']['Privativas']['VidaUtilTotalElementoAccesorio'] == 0){
                                    $calc_f_10_1_n_8 = 0;
                                }else{
                                    $calc_f_10_1_n_8 = 1-($data[0]['ElementosAccesorios']['Privativas']['EdadElementoAccesorio'] / $data[0]['ElementosAccesorios']['Privativas']['VidaUtilTotalElementoAccesorio']);
                                }                                
                                /*if((truncate($calc_f_10_1_n_8,2) != truncate($f_10_1_n_8,2)) || $f_10_1_n_8 < 0.6){
                                    $mensajesf[] =  "f.10.1.n.8 - El cálculo de FactorDeEdadElementoAccesorio es erróneo ";
                                }*/
                            }                            
                            
                            if(isset($data[0]['ElementosAccesorios']['Privativas']['ImporteElementoAccesorio'])){
                                $f_10_1_n_9 = $data[0]['ElementosAccesorios']['Privativas']['ImporteElementoAccesorio'];
                                $calc_f_10_1_n_9 = $data[0]['ElementosAccesorios']['Privativas']['CantidadElementoAccesorio'] * $data[0]['ElementosAccesorios']['Privativas']['CostoUnitarioElementoAccesorio'] * $f_10_1_n_8;
                                $sumatoriaf_10_1_n_9 = $sumatoriaf_10_1_n_9 + $f_10_1_n_9;

                                if(truncate($calc_f_10_1_n_9,2) != truncate($f_10_1_n_9,2)){
                                    $mensajesf[] =  "f.10.1.n.9 - El cálculo de ImporteElementoAccesorio es erróneo ";
                                }
                            }

                            /*if(isset($f_10_1_n_9) && isset($data[0]['ElementosAccesorios']['Privativas']['PorcentajeIndivisoElementoAccesorio'])){
                                $f_10_1_n_10 = isset($data[0]['ElementosAccesorios']['Privativas']['PorcentajeIndivisoElementoAccesorio']);
                                $para_f_14 = $para_f_14 + ($f_10_1_n_9 * $f_10_1_n_10);
                            }*/
                            
                        }
                    }
                }

            }

            /********************************************************************/

            if(isset($data[0]['ElementosAccesorios']['Comunes'])){

                foreach($data[0]['ElementosAccesorios']['Comunes'] as $idElemento => $elemento){
                    if(isset($elemento['@attributes']['id']) && $elemento['@attributes']['id'] == 'f.10.2'){
                        $f_10_2_n_8 = $elemento['FactorDeEdadElementoAccesorio'];
                        $calc_f_10_2_n_8 = 1-($elemento['EdadElementoAccesorio'] / $elemento['VidaUtilTotalElementoAccesorio']);

                        if(truncate($calc_f_10_2_n_8,2) != truncate($f_10_2_n_8,2)){ //echo truncate($calc_f_10_2_n_8,2)." != ".truncate($f_10_2_n_8,2)."\n";
                            $mensajesf[] =  "f.10.2.n.8 - El cálculo de FactorDeEdadElementoAccesorio es erróneo ";
                        }

                        $f_10_2_n_9 = $elemento['ImporteElementoAccesorio'];
                        $calc_f_10_2_n_9 = $elemento['CantidadElementoAccesorio'] * $elemento['CostoUnitarioElementoAccesorio'] * $f_10_2_n_8;
                        $sumatoriaf_10_2_n_9 = $sumatoriaf_10_2_n_9 + $f_10_2_n_9;

                        if(truncate($calc_f_10_2_n_9,2) != truncate($f_10_2_n_9,2)){
                            $mensajesf[] =  "f.10.2.n.9 - El cálculo de ImporteElementoAccesorio es erróneo ";
                        }

                        if(isset($f_10_2_n_9) && isset($elemento['PorcentajeIndivisoAccesorio'])){
                            $f_10_2_n_10 = $elemento['PorcentajeIndivisoAccesorio'];
                            $para_f_14 = $para_f_14 + ($f_10_2_n_9 * $f_10_2_n_10); //error_log("3.- ".$para_f_14);
                        }

                    }else{
                        if(isset($elemento['id']) && $elemento['id'] == 'f.10.2'){
                            $f_10_2_n_8 = $data[0]['ElementosAccesorios']['Comunes']['FactorDeEdadElementoAccesorio'];
                            $calc_f_10_2_n_8 = 1-($data[0]['ElementosAccesorios']['Comunes']['EdadElementoAccesorio'] / $data[0]['ElementosAccesorios']['Comunes']['VidaUtilTotalElementoAccesorio']);
                            if(truncate($calc_f_10_2_n_8,2) != truncate($f_10_2_n_8,2)){
                                $mensajesf[] =  "f.10.2.n.8 - El cálculo de FactorDeEdadElementoAccesorio es erróneo ";
                            }
                            
                            $f_10_2_n_9 = $data[0]['ElementosAccesorios']['Comunes']['ImporteElementoAccesorio'];
                            $calc_f_10_2_n_9 = $data[0]['ElementosAccesorios']['Comunes']['CantidadElementoAccesorio'] * $data[0]['ElementosAccesorios']['Comunes']['CostoUnitarioElementoAccesorio'] * $f_10_2_n_8;
                            $sumatoriaf_10_2_n_9 = $sumatoriaf_10_2_n_9 + $f_10_2_n_9;

                            if(truncate($calc_f_10_2_n_9,2) != truncate($f_10_2_n_9,2)){
                                $mensajesf[] =  "f.10.2.n.9 - El cálculo de ImporteElementoAccesorio es erróneo ";
                            }
                           
                            if(isset($f_10_2_n_9) && isset($data[0]['ElementosAccesorios']['Comunes']['PorcentajeIndivisoAccesorio'])){
                                $f_10_2_n_10 = $data[0]['ElementosAccesorios']['Comunes']['PorcentajeIndivisoAccesorio'];
                                $para_f_14 = $para_f_14 + ($f_10_2_n_9 * $f_10_2_n_10); //error_log("4.- ".$para_f_14);
                            }
                        }
                    }
                }

            }

            if(isset($data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosPrivativas'])){
                $f_10_3 = $data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosPrivativas'];

                if(truncate($f_10_3,2) != truncate($sumatoriaf_10_1_n_9,2)){
                    $mensajesf[] =  "f.10.3 - El cálculo de ImporteTotalElementosAccesoriosPrivativas es erróneo ";
                }
            }
            
            if(isset($data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosComunes'])){
                $f_10_4 = $data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosComunes'];
            
                if(truncate($f_10_4,2) != truncate($sumatoriaf_10_2_n_9,2)){
                    $mensajesf[] =  "f.10.4 - El cálculo de ImporteTotalElementosAccesoriosComunes es erróneo ";
                }
            }
            
        }

        /****************************************************************************************************************************************************************/
        /****************************************************************************************************************************************************************/

        if(isset($data[0]['ObrasComplementarias']) && count($data[0]['ObrasComplementarias']) > 1){
            
            $sumatoria_f_11_1_n_9 = 0;
            $sumatoria_f_11_2_n_9 = 0;

            if(isset($data[0]['ObrasComplementarias']['Privativas'])){
                $arrUno = array('OC03','OC06');
                foreach($data[0]['ObrasComplementarias']['Privativas'] as $idElemento => $elemento){
                    if(isset($elemento['@attributes']['id']) && $elemento['@attributes']['id'] == 'f.11.1'){
                        if(isset($elemento['FactorDeEdadObraComplementaria'])){
                            $f_11_1_n_8 = $elemento['FactorDeEdadObraComplementaria'];
                            $f_11_1_n_6 = $elemento['VidaUtilTotalObraComplementaria'];
                            $f_11_1_n_5 = $elemento['EdadObraComplementaria'];
                            $calc_f_11_1_n_8 = (0.1 * $f_11_1_n_6 + 0.9 * ($f_11_1_n_6 - $f_11_1_n_5)) / $f_11_1_n_6;

                            if(in_array($elemento['ClaveObraComplementaria'],$arrUno)){
                                /*if(truncate($f_11_1_n_8,2) != truncate(1,2)){
                                    $mensajesf[] =  "f.11.1.N.8 - El cálculo de FactorDeEdadObraComplementaria es erróneo ";
                                } */
                            }else{
                                /*if(truncate($f_11_1_n_8,2) != truncate($calc_f_11_1_n_8,2)){
                                    $mensajesf[] =  "f.11.1.N.8 - El cálculo de FactorDeEdadObraComplementaria es erróneo ";
                                }*/
                            }
                        }
                        
                        if(isset($elemento['ImporteObraComplementaria']) && isset($f_11_1_n_8)){
                            $f_11_1_n_9 = $elemento['ImporteObraComplementaria'];
                            $calc_f_11_1_n_9 = $elemento['CantidadObraComplementaria'] * $elemento['CostoUnitarioObraComplementaria'] * $f_11_1_n_8;
                            $sumatoria_f_11_1_n_9 = $sumatoria_f_11_1_n_9 + $f_11_1_n_9;

                            if(truncate($f_11_1_n_9,2) != truncate($calc_f_11_1_n_9,2)){
                                $mensajesf[] =  "f.11.1.N.9 - El cálculo de ImporteObraComplementaria es erróneo ";
                            }
                        }
                        
                        /*if(isset($f_11_1_n_9) && isset($elemento['PorcentajeIndivisoObraComplementaria'])){
                            $f_11_1_n_10 = isset($elemento['PorcentajeIndivisoObraComplementaria']);
                            $para_f_14 = $para_f_14 + ($f_11_1_n_9 * $f_11_1_n_10);
                        }*/

                    }else{
                        if(isset($elemento['id']) && $elemento['id'] == 'f.11.1'){
                            if(isset($data[0]['ObrasComplementarias']['Privativas']['FactorDeEdadObraComplementaria'])){
                                $f_11_1_n_8 = $data[0]['ObrasComplementarias']['Privativas']['FactorDeEdadObraComplementaria'];
                                $f_11_1_n_6 = $data[0]['ObrasComplementarias']['Privativas']['VidaUtilTotalObraComplementaria'];
                                $f_11_1_n_5 = $data[0]['ObrasComplementarias']['Privativas']['EdadObraComplementaria'];
                                $calc_f_11_1_n_8 = (0.1 * $f_11_1_n_6 + 0.9 * ($f_11_1_n_6 - $f_11_1_n_5)) / $f_11_1_n_6;

                                if(in_array($data[0]['ObrasComplementarias']['Privativas']['ClaveObraComplementaria'],$arrUno)){
                                    /* if(truncate($f_11_1_n_8,2) != truncate(1,2)){
                                        $mensajesf[] =  "f.11.1.N.8 - El cálculo de FactorDeEdadObraComplementaria es erróneo ";
                                    } */
                                }else{
                                    /* if(truncate($f_11_1_n_8,2) != truncate($calc_f_11_1_n_8,2)){
                                        $mensajesf[] =  "f.11.1.N.8 - El cálculo de FactorDeEdadObraComplementaria es erróneo ";
                                    } */
                                }
                            }
                            
                            if(isset($data[0]['ObrasComplementarias']['Privativas']['ImporteObraComplementaria']) && isset($f_11_1_n_8)){
                                $f_11_1_n_9 = $data[0]['ObrasComplementarias']['Privativas']['ImporteObraComplementaria'];
                                $calc_f_11_1_n_9 = $data[0]['ObrasComplementarias']['Privativas']['CantidadObraComplementaria'] * $data[0]['ObrasComplementarias']['Privativas']['CostoUnitarioObraComplementaria'] * $f_11_1_n_8;
                                $sumatoria_f_11_1_n_9 = $sumatoria_f_11_1_n_9 + $f_11_1_n_9;

                                if(truncate($f_11_1_n_9,2) != truncate($calc_f_11_1_n_9,2)){
                                    $mensajesf[] =  "f.11.1.N.9 - El cálculo de ImporteObraComplementaria es erróneo ";
                                }
                            }
                            
                            /*if(isset($f_11_1_n_9) && isset($data[0]['ObrasComplementarias']['Privativas']['PorcentajeIndivisoObraComplementaria'])){
                                $f_11_1_n_10 = isset($data[0]['ObrasComplementarias']['Privativas']['PorcentajeIndivisoObraComplementaria']);
                                $para_f_14 = $para_f_14 + ($f_11_1_n_9 * $f_11_1_n_10);
                            } */

                        }
                    }
                }
            }

            /************************************************************************************************************/    

            if(isset($data[0]['ObrasComplementarias']['Comunes'])){
                $arrUno = array('OC03','OC06');                
                foreach($data[0]['ObrasComplementarias']['Comunes'] as $idElemento => $elemento){
                    if(isset($elemento['@attributes']['id']) && $elemento['@attributes']['id'] == 'f.11.2'){

                        $f_11_2_n_8 = $elemento['FactorDeEdadObraComplementaria'];
                        $f_11_2_n_6 = $elemento['VidaUtilTotalObraComplementaria'];
                        $f_11_2_n_5 = $elemento['EdadObraComplementaria'];
                        $calc_f_11_2_n_8 = (0.1 * $f_11_2_n_6 + 0.9 * ($f_11_2_n_6 - $f_11_2_n_5)) / $f_11_2_n_6;

                        if(in_array($elemento['ClaveObraComplementaria'],$arrUno)){
                            if(truncate($f_11_2_n_8,2) != truncate(1,2)){ //echo truncate($f_11_2_n_8,2)." != ".truncate(1,2)."\n";
                                $mensajesf[] =  "f.11.2.N.8 - El cálculo de FactorDeEdadObraComplementaria es erróneo ";
                            }
                        }else{
                            if(truncate($f_11_2_n_8,2) != truncate($calc_f_11_2_n_8,2)){ //echo truncate($f_11_2_n_8,2)." != ".truncate($calc_f_11_2_n_8,2)."\n";
                                $mensajesf[] =  "f.11.2.N.8 - El cálculo de FactorDeEdadObraComplementaria es erróneo ";
                            }
                        }
                        
                        $f_11_2_n_9 = $elemento['ImporteObraComplementaria'];
                        $calc_f_11_2_n_9 = $elemento['CantidadObraComplementaria'] * $elemento['CostoUnitarioObraComplementaria'] * $f_11_2_n_8;
                        $sumatoria_f_11_2_n_9 = $sumatoria_f_11_2_n_9 + $f_11_2_n_9;

                        if(truncate($f_11_2_n_9,2) != truncate($calc_f_11_2_n_9,2)){ //echo truncate($f_11_2_n_9,2)." != ".truncate($calc_f_11_2_n_9,2)."\n";
                            $mensajesf[] =  "f.11.2.N.9 - El cálculo de ImporteObraComplementaria es erróneo ";
                        }

                        if(isset($f_11_2_n_9) && isset($elemento['PorcentajeIndivisoObraComplementaria'])){
                            $f_11_2_n_10 = $elemento['PorcentajeIndivisoObraComplementaria'];
                            $para_f_14 = $para_f_14 + ($f_11_2_n_9 * $f_11_2_n_10); //error_log("5.- ".$para_f_14);
                        }

                    }else{
                        if(isset($elemento['id']) && $elemento['id'] == 'f.11.2'){
                            
                            $f_11_2_n_8 = $data[0]['ObrasComplementarias']['Comunes']['FactorDeEdadObraComplementaria'];
                            $f_11_2_n_6 = $data[0]['ObrasComplementarias']['Comunes']['VidaUtilTotalObraComplementaria'];
                            $f_11_2_n_5 = $data[0]['ObrasComplementarias']['Comunes']['EdadObraComplementaria'];
                            $calc_f_11_2_n_8 = (0.1 * $f_11_2_n_6 + 0.9 * ($f_11_2_n_6 - $f_11_2_n_5)) / $f_11_2_n_6;

                            if(in_array($data[0]['ObrasComplementarias']['Comunes']['ClaveObraComplementaria'],$arrUno)){
                                if(truncate($f_11_2_n_8,2) != truncate(1,2)){
                                    $mensajesf[] =  "f.11.2.N.8 - El cálculo de FactorDeEdadObraComplementaria es erróneo ";
                                }
                            }else{
                                if(truncate($f_11_2_n_8,2) != truncate($calc_f_11_2_n_8,2)){
                                    $mensajesf[] =  "f.11.2.N.8 - El cálculo de FactorDeEdadObraComplementaria es erróneo ";
                                }
                            }

                            $f_11_2_n_9 = $data[0]['ObrasComplementarias']['Comunes']['ImporteObraComplementaria'];
                            $calc_f_11_2_n_9 = $data[0]['ObrasComplementarias']['Comunes']['CantidadObraComplementaria'] * $data[0]['ObrasComplementarias']['Comunes']['CostoUnitarioObraComplementaria'] * $f_11_2_n_8;
                            $sumatoria_f_11_2_n_9 = $sumatoria_f_11_2_n_9 + $f_11_2_n_9;

                            if(truncate($f_11_2_n_9,2) != truncate($calc_f_11_2_n_9,2)){
                                $mensajesf[] =  "f.11.2.N.9 - El cálculo de ImporteObraComplementaria es erróneo ";
                            }

                            if(isset($f_11_2_n_9) && isset($data[0]['ObrasComplementarias']['Comunes']['PorcentajeIndivisoObraComplementaria'])){
                                $f_11_2_n_10 = $data[0]['ObrasComplementarias']['Comunes']['PorcentajeIndivisoObraComplementaria'];
                                $para_f_14 = $para_f_14 + ($f_11_2_n_9 * $f_11_2_n_10); //error_log("6.- ".$para_f_14);
                            }

                        }
                    }
                }
            }

            if(isset($data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasPrivativas'])){
                $f_11_3 = $data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasPrivativas'];
                if(truncate($f_11_3,2) != truncate($sumatoria_f_11_1_n_9,2)){
                    $mensajesf[] =  "f.11.3 - El cálculo de ImporteTotalObrasComplementariasPrivativas es erróneo ";
                }

                $f_11_4 = $data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasComunes'];
                if(truncate($f_11_4,2) != truncate($sumatoria_f_11_2_n_9,2)){ //echo truncate($f_11_4,2)." != ".truncate($sumatoria_f_11_2_n_9,2)."\n";
                    $mensajesf[] =  "f.11.4 - El cálculo de ImporteTotalObrasComplementariasComunes es erróneo ";
                }
            }    

        }

        /***********************************************************************************************/
        /***********************************************************************************************/

        if(isset($data[0]['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas'])){

            $f_12 = $data[0]['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas'];
                //echo "SOY "; print_r($data[0]['InstalacionesEspeciales']); echo" ".$data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesPrivativas']; exit();
                if(!isset($data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesPrivativas']) == true || trim($data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesPrivativas']) == ''){
                    $importeTotalInstalacionesEspecialesPrivativas = 0;
                }else{
                    $importeTotalInstalacionesEspecialesPrivativas = $data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesPrivativas'];
                }
                
                if(!isset($data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosPrivativas']) || trim($data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosPrivativas']) == ''){
                    $importeTotalElementosAccesoriosPrivativas = 0;
                }else{
                    $importeTotalElementosAccesoriosPrivativas = $data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosPrivativas'];
                }
                
                if(!isset($data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasPrivativas']) || trim($data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasPrivativas']) == ''){
                    $importeTotalObrasComplementariasPrivativas = 0;
                }else{
                    $importeTotalObrasComplementariasPrivativas = $data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasPrivativas'];
                }
                //$calc_f_12 = $data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesPrivativas'] + $data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosPrivativas'] + $data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasPrivativas'];
                $calc_f_12 = $importeTotalInstalacionesEspecialesPrivativas + $importeTotalElementosAccesoriosPrivativas + $importeTotalObrasComplementariasPrivativas;
                if(truncate($f_12,2) != truncate($calc_f_12,2)){
                    $mensajesf[] =  "f.12 - El cálculo de SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas es erróneo ";                                                                                                                                                                                
                }
            
            
        }

        if(isset($data[0]['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes'])){

            $f_13 = $data[0]['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes'];

            if(!isset($data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesComunes']) || trim($data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesComunes']) == ''){
                $importeTotalInstalacionesEspecialesComunes = 0;
            }else{
                $importeTotalInstalacionesEspecialesComunes = $data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesComunes'];
            }
            
            if(!isset($data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosComunes']) || trim($data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosComunes']) == ''){
                $importeTotalElementosAccesoriosComunes = 0;
            }else{
                $importeTotalElementosAccesoriosComunes = $data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosComunes'];
            }
            
            if(!isset($data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasComunes']) || trim($data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasComunes']) == ''){
                $importeTotalObrasComplementariasComunes = 0;
            }else{
                $importeTotalObrasComplementariasComunes = $data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasComunes'];
            }

            //$calc_f_13 = $data[0]['InstalacionesEspeciales']['ImporteTotalInstalacionesEspecialesComunes'] + $data[0]['ElementosAccesorios']['ImporteTotalElementosAccesoriosComunes'] + $data[0]['ObrasComplementarias']['ImporteTotalObrasComplementariasComunes'];
            $calc_f_13 = $importeTotalInstalacionesEspecialesComunes + $importeTotalElementosAccesoriosComunes + $importeTotalObrasComplementariasComunes;
            if(truncate($f_13,2) != truncate($calc_f_13,2)){ //echo truncate($f_13,2)." != ".truncate($calc_f_13,2)."\n";
                $mensajesf[] =  "f.13 - El cálculo de SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes es erróneo ";
            }
        }

        if(isset($data[0]['ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes'])){
            $f_14 = $data[0]['ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes'];
            //error_log("valor_de_b6 ".$b_6);
            if(trim($b_6) === '2'){ //error_log("Cuando es 2 ".truncate($f_14,2)." != ".truncate($para_f_14,2));
                if(truncate($f_14,2) != truncate($para_f_14,2)){
                    $mensajesf[] =  "f.14 - El cálculo de ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes es erróneo ";
                }
            }else{
                $calc_f_14 = $data[0]['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes'] * $dataextra[0]['Indiviso'];
                if(truncate($f_14,2) != truncate($calc_f_14,2)){ //error_log(truncate($f_14,2)." != ".truncate($calc_f_14,2));
                    $mensajesf[] =  "f.14 - El cálculo de ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes es erróneo ";
                }
            }
            
        }

        if(isset($data[0]['ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas'])){
            $f_15 = $data[0]['ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas'];                                   
            $calc_f_15 = $data[0]['SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas'] * $dataextra[0]['Indiviso'];
            if(truncate($f_15,2) != truncate($calc_f_15,2)){ //error_log(truncate($f_15,2)." != ".truncate($calc_f_15,2));
                $mensajesf[] =  "f.15 - El cálculo de ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas es erróneo ";
            }
        }

        if(count($mensajesf) > 0){
            return $mensajesf;
        }else{
            return "Correcto";
        }
        
    }

    if($letra == 'h'){
        if(isset($data[0]['Terrenos'])){

            foreach($data[0]['Terrenos']['TerrenosDirectos'] as $idElemento => $elemento){
                if(isset($elemento['@attributes']['id']) && $elemento['@attributes']['id'] == 'h.1.1'){
                    $h_1_1_n_17 = $elemento['Fre'];

                    /*if(isset($elemento['Fot']) && trim($elemento['Fot']['Valor'] != '') && $elemento['Fot']['Valor'] > 0){
                        $calc_h_1_1_n_17 = 1/($elemento['Fzo'] * $elemento['Fub'] * $elemento['FFr'] * $elemento['Ffo'] * $elemento['Fsu'] *  $elemento['Fot']['Valor']);
                    }else{
                        $calc_h_1_1_n_17 = 1/($elemento['Fzo'] * $elemento['Fub'] * $elemento['FFr'] * $elemento['Ffo'] * $elemento['Fsu']);
                    }*/
                    $calc_h_1_1_n_17 = 1/($elemento['Factor1']['Valor'] * $elemento['Factor2']['Valor'] * $elemento['Factor3']['Valor'] * $elemento['Factor4']['Valor'] * $elemento['Factor5']['Valor']);
                    if(truncate($h_1_1_n_17,2) != truncate($calc_h_1_1_n_17,2)){ error_log(truncate($h_1_1_n_17,2)." != ".truncate($calc_h_1_1_n_17,2));
                        return  "h.1.1.n.17 - El cálculo de Fre es erróneo ";
                    }
                }
            }

        }
                    
    }

    if($letra == 'k'){
        $mensajesk = array();
        //echo "SOY DATA DE K ".print_r($data); exit();
        $k_2_1 = $data[0]['Deducciones']['Vacios'];
        $k_2_2 = $data[0]['Deducciones']['ImpuestoPredial'];
        $k_2_3 = $data[0]['Deducciones']['ServicioDeAgua'];
        $k_2_4 = $data[0]['Deducciones']['ConservacionYMantenimiento'];
        $k_2_5 = $data[0]['Deducciones']['ServicioEnergiaElectrica'];
        $k_2_6 = $data[0]['Deducciones']['Administracion'];
        $k_2_7 = $data[0]['Deducciones']['Seguros'];
        $k_2_9 = $data[0]['Deducciones']['Otros'];
        $k_2_11 = $data[0]['Deducciones']['ImpuestoSobreLaRenta'];
        $k_2_12 = $data[0]['Deducciones']['DeduccionesMensuales'];
        $calc_k_2_12 = $k_2_1 + $k_2_2 + $k_2_3 + $k_2_4 + $k_2_5 + $k_2_6 + $k_2_7 + $k_2_9 + $k_2_11;

        if(truncate($k_2_12,2) != truncate($calc_k_2_12,2)){
            $mensajesk[] =  "k.2.12 - El cálculo de DeduccionesMensuales es erróneo ";
        }

        $k_2_13 = $data[0]['Deducciones']['PorcentajeDeduccionesMensuales'];
        $k_1 = $data[0]['RentaBrutaMensual'];
        $calc_k_2_13 = ($k_2_12 / $k_1) / 100;

        if(truncate($k_2_13,4) != truncate($calc_k_2_13,4)){ //echo truncate($k_2_13,4)." != ".truncate($calc_k_2_13,4)."\n";
            $mensajesk[] = "k.2.13 - El cálculo de PorcentajeDeduccionesMensuales es erróneo ";
        }

        $k_3 = $data[0]['ProductoLiquidoAnual'];
        $calc_k_3 = ($k_1 - $k_2_12) * 12;

        if(truncate($k_3,2) != truncate($calc_k_3,2)){
            $mensajesk[] =  "k.3 - El cálculo de ProductoLiquidoAnual es erróneo ";
        }

        $k_5 = $data[0]['ImporteEnfoqueDeIngresos'];
        $k_4 = $data[0]['TasaDeCapitalizacionAplicable'];
        $calc_k_5 = $k_3 / ($k_4 / 100);

        if(truncate($k_5,2) != truncate($calc_k_5,2)){ //echo truncate($k_5,2)." != ".truncate($calc_k_5,2)."\n";
            $mensajesk[] = "k.5 - El cálculo de ImporteEnfoqueDeIngresos es erróneo ";
        }
        if(count($mensajesk) > 0){
            return $mensajesk;
        }else{
            return "Correcto";
        }
    }

    if($letra == 'p'){
        $mensajesp = array();
        $p_2 = $data[0]['IndiceAntiguo'];
        $p_3 = $data[0]['IndiceActual'];
        $p_4 = $data[0]['FactorDeConversion'];
        //$calc_p_4 = $p_2 / $p_3;
        $calc_p_4 = $p_3 / $p_2;

        if(truncate($p_4,2) != truncate($calc_p_4,2)){
            $mensajesp[] = "p.4 - El cálculo de FactorDeConversion es erróneo ";
        }

        $p_1 = $data[0]['FechaDeValorReferido'];
        $fechaComparar = new Carbon($p_1);
        $fechaMinima = '1993-12-31';
        $p_5 = $data[0]['ValorReferido'];
        $datao1 = array_map("convierte_a_arreglo",$dataextra);
        $o_1 = $datao1[0][0];

        if($fechaComparar->gt($fechaMinima)){
            $calc_p_5 = $o_1/$p_4; 
        }else{
            $calc_p_5 = $o_1/$p_4*1000;
        }
        //$calc_p_5 = $p_4 * $o_1;

        if(truncate($p_5,2) != truncate($calc_p_5,2)){
            $mensajesp[] = "p.5 - El cálculo de ValorReferido es erróneo ";
        }
        if(count($mensajesp) > 0){
            return $mensajesp;
        }else{
            return "Correcto";
        }
    }

    return "Correcto";
}

function truncate($number, $decimals)
{
    $point_index = strrpos($number, '.');
     if($point_index === false){
        return intval($number);
     }else{
         $arrCantidades = explode('.',$number);
         if($arrCantidades[1] > 0){
            return substr($number, 0, $point_index + $decimals+ 1);
         }else{
            return intval($number);  
         }
        
     }
}

function valida_Calculos_i($data,$letra, $datad13, $datae2, $dataf12, $dataf14){
    $datad13 = array_map("convierte_a_arreglo",$datad13);
    $datae2 = array_map("convierte_a_arreglo",$datae2);
    $dataf12 = array_map("convierte_a_arreglo",$dataf12);
    $dataf14 = array_map("convierte_a_arreglo",$dataf14);
    $d_13 = $datad13[0][0];
    $e_2_3 = $datae2[0]['ValorTotalDeConstruccionesPrivativas'];
    $e_2_8 = $datae2[0]['ValorTotalDeLasConstruccionesComunesProIndiviso'];
    $f_12 = $dataf12[0][0];
    $f_14 = $dataf14[0][0];
    $i_6 = $data[0]['ImporteTotalDelEnfoqueDeCostos'];
    $calc_i_6 = $d_13 + $e_2_3 + $e_2_8 + $f_12 + $f_14;

    if((String)(truncate($i_6,2)) != (String)(truncate($calc_i_6,2))){ error_log(truncate($i_6,2)." != ".truncate($calc_i_6,2));
        return  "i.6 - El cálculo de ImporteTotalDelEnfoqueDeCostos es erróneo ";
    }

    return "Correcto";

}

function valida_Calculos_j($data, $letra, $datae23, $datae27, $datab6, $datad6, $datad13, $existef9, $existef10, $existef11){
    $mensajesj = array();
    $datae23 = array_map("convierte_a_arreglo",$datae23);
    $datae27 = array_map("convierte_a_arreglo",$datae27);
    $datab6 = array_map("convierte_a_arreglo",$datab6);
    $datad6 = array_map("convierte_a_arreglo",$datad6);
    $datad13 = array_map("convierte_a_arreglo",$datad13);
    $e_2_3 = $datae23[0][0];
    $e_2_7 = $datae27[0][0];
    $b_6 = $datab6[0][0];
    $d_6 = $datad6[0][0];
    $d_13 = $datad13[0][0];
    $j_4 = $data[0]['ImporteInstalacionesEspeciales'];

    if($existef9 === FALSE || $existef10 === FALSE || $existef11 === FALSE){
        $calc_j_4 = 0;
    }else{
        if($b_6 == 2){
            $calc_j_4 = ($e_2_3 + ($e_2_7 * $d_6)) * 0.08;
        }else{
            $calc_j_4 = ($e_2_3 + $e_2_7) * 0.08;
        }
    }
    //echo "OPERACION ".truncate($j_4,6)." != ".truncate($calc_j_4,6)."\n"; exit();
    if(truncate($j_4,6) != truncate($calc_j_4,6)){ //echo "OPERACION ".round($j_4,6)." != ".round($calc_j_4,6)."\n";
        $mensajesj[] =  "j.4 - El cálculo de ImporteInstalacionesEspeciales es erróneo ";
    }
    
    $j_5 = $data[0]['ImporteTotalValorCatastral'];
    if($b_6 == 2){
        $calc_j_5 = $d_13 + $e_2_3 + ($e_2_7 * $d_6) + $j_4;
    }else{        
        $calc_j_5 = $d_13 + $e_2_3 + $e_2_7 + $j_4;
    }

    if(truncate($j_5,2) !== truncate($calc_j_5,2)){ //echo "OPERACION ".truncate($j_5,2)." != ".truncate($calc_j_5,2)."\n";
        $mensajesj[] =  "j.5 - El cálculo de ImporteTotalValorCatastral es erróneo ";
    }
    
    $j_7 = $data[0]['ImporteTotalValorCatastralObraEnProceso'];
    $j_6 = $data[0]['AvanceDeObra'];
    $calc_j_7 = $j_5 * $j_6;

    if(truncate($j_7,2) != truncate($calc_j_7,2)){ //echo "OPERACION ".round($j_7,6)." != ".round($calc_j_7,6)."\n";
        $mensajesj[] =  "j.7 - El cálculo de ImporteTotalValorCatastralObraEnProceso es erróneo ";
    }

    if(count($mensajesj) > 0){
        return $mensajesj;
    }else{
        return "Correcto";
    }
    
}

function valida_AvaluoCaracteristicasUrbanas($data){
    $arrIDS = array('ContaminacionAmbientalEnLaZona' => 'c.0','ClasificacionDeLaZona' => 'c.1','IndiceDeSaturacionDeLaZona' => 'c.2','ClaseGeneralDeInmueblesDeLaZona' => 'c.3', 'DensidadDePoblacion' => 'c.4', 'NivelSocioeconomicoDeLaZona' => 'c.5',
                    'UsoDelSuelo' => 'c.6.1', 'AreaLibreObligatoria' => 'c.6.2', 'NumeroMaximoDeNivelesAConstruir' => 'c.6.3', 'CoeficienteDeUsoDelSuelo' => 'c.6.4', 'ViasDeAccesoEImportancia' => 'c.7',
                    'RedDeDistribucionAguaPotable' => 'c.8.1', 'RedDeRecoleccionDeAguasResiduales' => 'c.8.2', 'RedDeDrenajeDeAguasPluvialesEnLaCalle' => 'c.8.3', 'RedDeDrenajeDeAguasPluvialesEnLaZona' => 'c.8.4', 'SistemaMixto' => 'c.8.5', 'SuministroElectrico' => 'c.8.7', 'AcometidaAlInmueble' => 'c.8.8', 'AlumbradoPublico' => 'c.8.9', 'Vialidades' => 'c.8.10', 'Banquetas' => 'c.8.11', 'Guarniciones' => 'c.8.12', 'NivelDeInfraestructuraEnLaZona' => 'c.8.13', 'GasNatural' => 'c.8.14', 'TelefonosSuministro' => 'c.8.15', 'AcometidaAlInmuebleTel' => 'c.8.16', 'SennalizacionDeVias' => 'c.8.17', 'NomenclaturaDeCalles' => 'c.8.18', 'DistanciaTranporteUrbano' => 'c.8.19', 'FrecuenciaTransporteUrbano' => 'c.8.20', 'DistanciaTransporteSuburbano' => 'c.8.21', 'FrecuenciaTransporteSuburbano' => 'c.8.22', 'Vigilancia' => 'c.8.23', 'RecoleccionDeBasura' => 'c.8.24', 'Templo' => 'c.8.25', 'Mercados' => 'c.8.26', 'PlazasPublicas' => 'c.8.27', 'ParquesYJardines' => 'c.8.28', 'Escuelas' => 'c.8.29', 'Hospitales' => 'c.8.30', 'Bancos' => 'c.8.31', 'EstacionDeTransporte' => 'c.8.32', 'NivelDeEquipamientoUrbano' => 'c.8.33');        

    $validacionesc = array('ContaminacionAmbientalEnLaZona' => 'string_250','ClasificacionDeLaZona' => 'catClasificacionZona','IndiceDeSaturacionDeLaZona' => 'decimalPositivo','ClaseGeneralDeInmueblesDeLaZona' => 'catClasesConstruccion', 'DensidadDePoblacion' => 'catDensidadPoblacion', 'NivelSocioeconomicoDeLaZona' => 'catNivelSocioeconomico');
    $validacionesc6 = array('UsoDelSuelo' => 'nonEmptyString_50', 'AreaLibreObligatoria' => 'decimalPositivo_52', 'NumeroMaximoDeNivelesAConstruir' => 'decimalPositivo_30', 'CoeficienteDeUsoDelSuelo' => 'decimalPositivo');
    $validacionesc7 = array('ViasDeAccesoEImportancia' => 'nonEmptyString');
    $validacionesc8 = array('RedDeDistribucionAguaPotable' => 'catAguaPotable', 'RedDeRecoleccionDeAguasResiduales' => 'catDrenaje', 'RedDeDrenajeDeAguasPluvialesEnLaCalle' => 'catDrenajePluvial', 'RedDeDrenajeDeAguasPluvialesEnLaZona' => 'catDrenajePluvial', 'SistemaMixto' => 'catDrenaje', 'SuministroElectrico' => 'catSuministroElectrico', 'AcometidaAlInmueble' => 'catAcometidaInmueble', 'AlumbradoPublico' => 'catAlumbradoPublico', 'Vialidades' => 'catVialidades', 'Banquetas' => 'catBanquetas', 'Guarniciones' => 'catGuarniciones', 'NivelDeInfraestructuraEnLaZona' => 'porcentaje_04', 'GasNatural' => 'catGasNatural', 'TelefonosSuministro' => 'catSuministroTelefonico', 'AcometidaAlInmuebleTel' => 'catAcometidaInmueble', 'SennalizacionDeVias' => 'catSenalizacionVias', 'NomenclaturaDeCalles' => 'catNomenclaturaCalles', 'DistanciaTranporteUrbano' => 'decimalPositivo', 'FrecuenciaTransporteUrbano' => 'decimalPositivo', 'DistanciaTransporteSuburbano' => 'decimalPositivo', 'FrecuenciaTransporteSuburbano' => 'decimalPositivo', 'Vigilancia' => 'catVigilanciaZona', 'RecoleccionDeBasura' => 'catRecoleccionBasura', 'Templo' => 'boolean', 'Mercados' => 'boolean', 'PlazasPublicas' => 'boolean', 'ParquesYJardines' => 'boolean', 'Escuelas' => 'boolean', 'Hospitales' => 'boolean', 'Bancos' => 'boolean', 'EstacionDeTransporte' => 'boolean', 'NivelDeEquipamientoUrbano' => 'decimal');
    $errores = array();
    $data = array_map("convierte_a_arreglo",$data);
    $resValidaCalculo = valida_Calculos($data,'c');
    if($resValidaCalculo != "Correcto"){
        $errores[] = $resValidaCalculo;
    }    
    $numeroCaracteristicasUrbanas = count($data);    
    if ($numeroCaracteristicasUrbanas > 1) {
        $errores[] = "El XML cuenta con mas de una seccion de Caracteristicas Urbanas";
    }else{
        foreach($validacionesc as $etiqueta => $validacion){
            if(!isset($data[0][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiqueta." en Antecedentes (Solicitante)";
            }else{
                $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                             
                if($resValidacion != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiqueta." ".$resValidacion;
                }
            }
        }
        
        foreach($validacionesc6 as $etiqueta => $validacion){
            if(!isset($data[0]['UsoDelSuelo'][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiqueta." en CaracteristicasUrbanas (UsoDelSuelo)";
            }else{
                $resValidacion = define_validacion($validacion, $data[0]['UsoDelSuelo'][$etiqueta]);                
                if($resValidacion != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiqueta." ".$resValidacion;
                }
            
            }
        }

        foreach($validacionesc7 as $etiqueta => $validacion){
            if(!isset($data[0][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiqueta." en CaracteristicasUrbanas";
            }else{
                $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                
                if($resValidacion != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiqueta." ".$resValidacion;
                }
            }
        }

        foreach($validacionesc8 as $etiqueta => $validacion){
            if(!isset($data[0]['ServiciosPublicosYEquipamientoUrbano'][$etiqueta])){
                $errores[] = $arrIDS[$etiqueta]." - Falta ".$etiqueta." en CaracteristicasUrbanas";
            }else{
                $resValidacion = define_validacion($validacion, $data[0]['ServiciosPublicosYEquipamientoUrbano'][$etiqueta]);                
                if($resValidacion != 'correcto'){
                    $errores[] = $arrIDS[$etiqueta]." - El campo ".$etiqueta." ".$resValidacion;
                }                
            }
        }
    }
    return $errores;
}

function valida_AvaluoTerreno($data, $elementoPrincipal, $datah = false){   
    if($elementoPrincipal == '//Comercial'){
        $validacionesd = array('CallesTransversalesLimitrofesYOrientacion' => 'nonEmptyString', 'CroquisMicroLocalizacion' => 'base64Binary', 'CroquisMacroLocalizacion' => 'base64Binary', 'Indiviso' => 'porcentaje_10', 'TopografiaYConfiguracion' => 'catTopografia', 'CaracteristicasPanoramicas' => 'nonEmptyString_250', 'DensidadHabitacional' => 'catDensidadHabitacional', 'ServidumbresORestricciones' => 'nonEmptyString_250', 'SuperficieTotalDelTerreno' => 'decimalPositivo', 'ValorTotalDelTerreno' => 'decimalPositivo', 'ValorTotalDelTerrenoProporcional' => 'decimalPositivo');
        $validacionesd411 = array('NumeroDeEscritura' => 'decimalPositivo', 'NumeroDeVolumen' => 'nonEmptyString_7', 'FechaEscritura' => 'date', 'NumeroNotaria' => 'decimalPositivo', 'NombreDelNotario' => 'nonEmptyString_50', 'DistritoJudicialNotario' => 'nonEmptyString_50');
        $validacionesd412 = array('Juzgado' => 'nonEmptyString_50', 'Fecha' => 'date', 'NumeroExpediente' => 'nonEmptyString_16');
        $validacionesd413 = array('Fecha' => 'date', 'NombreAdquirente' => 'nonEmptyString_50', 'Apellido1Adquirente' => 'nonEmptyString_100', 'Apellido2Adquirente' => 'nonEmptyString_50', 'NombreEnajenante' => 'nonEmptyString_50', 'Apellido1Enajenante' => 'nonEmptyString_100', 'Apellido2Enajenante' => 'nonEmptyString_50');
        $validacionesd414 = array('Fecha' => 'date', 'NumeroFolio' => 'nonEmptyString_20');
        $validacionesd42 = array('Orientacion' => 'nonEmptyString', 'MedidaEnMetros' => 'decimalPositivo_223', 'DescripcionColindante' => 'nonEmptyString');
        $validacionesd5P = array('IdentificadorFraccionN1' => 'SUB-IdentificadorFraccionN1Priv', 'SuperficieFraccionN1' => 'SUB-SuperficieFraccionN1Priv', 'ClaveDeAreaDeValor' => 'SUB-ClaveDeAreaDeValorPriv', 'Fre' => 'SUB-FrePriv', 'ValorDeLaFraccionN' => 'SUB-ValorDeLaFraccionNPriv');
        $validacionesd5C = array('IdentificadorFraccionN1' => 'SUB-IdentificadorFraccionN1Com', 'SuperficieFraccionN1' => 'SUB-SuperficieFraccionN1Com', 'ClaveDeAreaDeValor' => 'SUB-ClaveDeAreaDeValorCom', 'Fre' => 'SUB-FreCom', 'ValorDeLaFraccionN' => 'SUB-ValorDeLaFraccionNCom');       
    }else{
        $validacionesd = array('CallesTransversalesLimitrofesYOrientacion' => 'nonEmptyString', 'CroquisMicroLocalizacion' => 'base64Binary', 'CroquisMacroLocalizacion' => 'base64Binary','Indiviso' => 'porcentaje_10', 'TopografiaYConfiguracion' => 'catTopografia', 'CaracteristicasPanoramicas' => 'nonEmptyString_250', 'DensidadHabitacional' => 'catDensidadHabitacional', 'ServidumbresORestricciones' => 'nonEmptyString_250', 'SuperficieTotalDelTerreno' => 'decimalPositivo', 'ValorTotalDelTerreno' => 'decimalPositivo', 'ValorTotalDelTerrenoProporcional' => 'decimalPositivo');
        $validacionesd411 = array('NumeroDeEscritura' => 'decimalPositivo', 'NumeroDeVolumen' => 'nonEmptyString_7', 'FechaEscritura' => 'date', 'NumeroNotaria' => 'decimalPositivo', 'NombreDelNotario' => 'nonEmptyString_50', 'DistritoJudicialNotario' => 'nonEmptyString_50');
        $validacionesd412 = array('Juzgado' => 'nonEmptyString_50', 'Fecha' => 'date', 'NumeroExpediente' => 'nonEmptyString_16');
        $validacionesd413 = array('Fecha' => 'date', 'NombreAdquirente' => 'nonEmptyString_50', 'Apellido1Adquirente' => 'nonEmptyString_100', 'Apellido2Adquirente' => 'nonEmptyString_50', 'NombreEnajenante' => 'nonEmptyString_50', 'Apellido1Enajenante' => 'nonEmptyString_100', 'Apellido2Enajenante' => 'nonEmptyString_50');
        $validacionesd414 = array('Fecha' => 'date', 'NumeroFolio' => 'nonEmptyString_20');
        $validacionesd42 = array('Orientacion' => 'nonEmptyString', 'MedidaEnMetros' => 'decimalPositivo_223', 'DescripcionColindante' => 'nonEmptyString');
        $validacionesd5P = array('IdentificadorFraccionN1' => 'SUB-IdentificadorFraccionN1Priv', 'SuperficieFraccionN1' => 'SUB-SuperficieFraccionN1Priv', 'ClaveDeAreaDeValor' => 'SUB-ClaveDeAreaDeValorPriv', 'ValorCatastralDeTierraAplicableALaFraccion' => 'SUB-ValorCatastralDeTierraAplicableALaFraccionPriv');
        $validacionesd5C = array('IdentificadorFraccionN1' => 'SUB-IdentificadorFraccionN1Com', 'SuperficieFraccionN1' => 'SUB-SuperficieFraccionN1Com', 'ClaveDeAreaDeValor' => 'SUB-ClaveDeAreaDeValorCom', 'ValorCatastralDeTierraAplicableALaFraccion' => 'SUB-ValorCatastralDeTierraAplicableALaFraccionCom');
    }
    
    $errores = array();
    $data = array_map("convierte_a_arreglo",$data);   
    $resValidaCalculo = valida_Calculos($data,'d',$datah);
    if($resValidaCalculo != "Correcto"){
        $errores[] = $resValidaCalculo;
    }    
    
    $numeroTerrenos = count($data);    
    if ($numeroTerrenos > 1) {
        $errores[] = "El XML cuenta con mas de una seccion de Terreno";
    }else{
        foreach($validacionesd as $etiqueta => $validacion){ 
            if(!isset($data[0][$etiqueta])){
                $errores[] = "Falta ".$etiqueta." en MedidasYColindancias";
            }else{                
                $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                             
                if($resValidacion != 'correcto'){
                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                }
                
                if($etiqueta == "CroquisMicroLocalizacion" || $etiqueta == "CroquisMacroLocalizacion"){
                try{    
                    $img = base64_decode($data[0][$etiqueta]);                    
                    $infoImg = getimagesizefromstring($img);
                    if($infoImg[0] > '640' || $infoImg[1] > '480'){
                        $errores[] = "La resolucion en pixeles de ".$etiqueta." es mas grande de lo aceptado";
                    }
                } catch (\Throwable $th) {
                    //return "no contiene un base64";
                    $errores[] = $etiqueta." no contiene una imagen";
                }
                    
                }
            }
        }
        
        
        foreach($validacionesd411 as $etiqueta => $validacion){
            if(!isset($data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['Escritura'][$etiqueta])){
                $errores[] = "Falta ".$etiqueta." en MedidasYColindancias (Escritura)";
            }else{
                $resValidacion = define_validacion($validacion, $data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['Escritura'][$etiqueta]);                             
                if($resValidacion != 'correcto'){
                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                }
            }
        }

        if(isset($data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['Sentencia'])){
            foreach($validacionesd412 as $etiqueta => $validacion){
                if(!isset($data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['Sentencia'][$etiqueta])){
                    $errores[] = "Falta ".$etiqueta." en FuenteDeInformacionLegal (Sentencia)";
                }else{
                    $resValidacion = define_validacion($validacion, $data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['Sentencia'][$etiqueta]);                             
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                    }
                }
            }
        }
        
        if(isset($data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['ContratoPrivado'])){
            foreach($validacionesd413 as $etiqueta => $validacion){
                if(!isset($data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['ContratoPrivado'][$etiqueta])){
                    $errores[] = "Falta ".$etiqueta." en FuenteDeInformacionLegal (ContratoPrivado)";
                }else{
                    $resValidacion = define_validacion($validacion, $data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['ContratoPrivado'][$etiqueta]);                             
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                    }
                }
            }
        }
        
        if(isset($data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['AlineamientoYNumeroOficial'])){
            foreach($validacionesd414 as $etiqueta => $validacion){
                if(!isset($data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['AlineamientoYNumeroOficial'][$etiqueta])){
                    $errores[] = "Falta ".$etiqueta." en FuenteDeInformacionLegal (AlineamientoYNumeroOficial)";
                }else{
                    $resValidacion = define_validacion($validacion, $data[0]['MedidasYColindancias']['FuenteDeInformacionLegal']['AlineamientoYNumeroOficial'][$etiqueta]);                             
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                    }
                }
            }
        }

        $numeroColindancias = 0;
        $numeroColindancias = count($data[0]['MedidasYColindancias']['Colindancias']);      

        if($numeroColindancias == 0){
            $errores[] = "El XML no cuenta con la seccion de Colindancias";
        }else{
            if(isset($data[0]['MedidasYColindancias']['Colindancias']['@attributes'])){
                foreach($validacionesd42 as $etiqueta => $validacion){
                    if(!isset($data[0]['MedidasYColindancias']['Colindancias'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en Colindancias";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['MedidasYColindancias']['Colindancias'][$etiqueta]);                             
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }
                    }
                }

            }else{
                foreach($data[0]['MedidasYColindancias']['Colindancias'] as $id => $colindancia){
                    
                        foreach($validacionesd42 as $etiqueta => $validacion){
                            if(!isset($colindancia[$etiqueta])){
                                $errores[] = "Falta ".$etiqueta." en Colindancias";
                            }else{
                                $resValidacion = define_validacion($validacion, $colindancia[$etiqueta]);                             
                                if($resValidacion != 'correcto'){
                                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                }
                            }
                        }
                    
                }

            }            
        }
        
        foreach($validacionesd5P as $etiqueta => $validacion){
            if(!isset($data[0]['SuperficieDelTerreno'][$etiqueta])){
                $errores[] = "Falta ".$etiqueta." en SuperficieDelTerreno";
            }else{
                $resValidacion = define_validacion($validacion, $data[0]['SuperficieDelTerreno'][$etiqueta]);                             
                if($resValidacion != 'correcto'){
                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                }
            }
        }
        
    }

    return $errores;    
}


function valida_AvaluoDescripcionImueble($data, $elementoPrincipal, $datad = false, $b_6 = false){

    if($elementoPrincipal == '//Comercial'){
        //$validacionese = array('UsoActual' => 'nonEmptyString_2000', 'VidaUtilTotalPonderadaDelInmueble' => 'nullableDecimalPositivo', 'EdadPonderadaDelInmueble' => 'nullableDecimalPositivo', 'VidaUtilRemanentePonderadaDelInmueble' => 'nullableDecimalPositivo');
        $validacionese = array('UsoActual' => 'nonEmptyString_2000', 'VidaMinimaRemanente' => 'nullableDecimalPositivo', 'VidaMinimaRemanentePonderadaDelInmueble' => 'nullableDecimalPositivo', 'PorcentSuperfUltimNivelRespectoAnterior' => 'porcentaje_10');

        $validacionese2 = array('SuperficieTotalDeConstruccionesPrivativas' => 'SUB-SuperficieTotalDeConstruccionesPrivativas', 'ValorTotalDeConstruccionesPrivativas' => 'SUB-ValorTotalDeConstruccionesPrivativas', 'SuperficieTotalDeConstruccionesComunes' => 'SUB-SuperficieTotalDeConstruccionesComunes', 'ValorTotalDeConstruccionesComunes' => 'SUB-ValorTotalDeConstruccionesComunes', 'ValorTotalDeLasConstruccionesComunesProIndiviso' => 'SUB-ValorTotalDeLasConstruccionesProIndivisoComunes');

        //$validacionese21 = array('Descripcion' => 'SUB-Descripcion', 'ClaveUso' => 'SUB-ClaveUso', 'NumeroDeNivelesDelTipo' => 'SUB-NumeroDeNivelesDelTipo', 'ClaveRangoDeNiveles' => 'SUB-ClaveRangoDeNiveles', 'PuntajeDeClasificacion' => 'SUB-PuntajeDeClasificacion', 'ClaveClase' => 'SUB-ClaveClase', 'VidaUtilTotalDelTipo' => 'SUB-VidaUtilTipo', 'VidaMinimaRemanente' => 'SUB-VidaUtilRemanente', 'Superficie' => 'SUB-Superficie', 'CostoUnitarioDeReposicionNuevo' => 'SUB-ValorunitariodereposicionNuevo', 'IndiceDelCostoRemanente' => 'decimalPositivo', 'CostoDeLaFraccionN' => 'SUB-ValorDeLaFraccionNDescInmueble');
        $validacionese21 = array('Descripcion' => 'SUB-Descripcion', 'ClaveUso' => 'SUB-ClaveUso', 'NumeroDeNivelesDelTipo' => 'SUB-NumeroDeNivelesDelTipo', 'ClaveRangoDeNiveles' => 'SUB-ClaveRangoDeNiveles', 'PuntajeDeClasificacion' => 'SUB-PuntajeDeClasificacion', 'ClaveClase' => 'SUB-ClaveClase', 'VidaUtilTotalDelTipo' => 'SUB-VidaUtilTipo', 'Superficie' => 'SUB-Superficie', 'CostoUnitarioDeReposicionNuevo' => 'SUB-ValorunitariodereposicionNuevo', 'IndiceDelCostoRemanente' => 'decimalPositivo', 'CostoDeLaFraccionN' => 'SUB-ValorDeLaFraccionNDescInmueble');

        //$validacionese25 = array('Descripcion' => 'SUB-DescripcionComunes', 'ClaveUso' => 'SUB-ClaveUsoComunes', 'NumeroDeNivelesDelTipo' => 'SUB-NumeroDeNivelesDelTipoComunes', 'ClaveRangoDeNiveles' => 'SUB-ClaveRangoDeNivelesComunes', 'PuntajeDeClasificacion' => 'SUB-PuntajeDeClasificacionComunes', 'ClaveClase' => 'SUB-ClaveClaseComunes', 'VidaUtilTotalDelTipo' => 'SUB-VidaUtilTipoComunes', 'VidaMinimaRemanente' => 'SUB-VidaUtilRemanenteComunes', 'Superficie' => 'SUB-SuperficieComunes', 'CostoUnitarioDeReposicionNuevo' => 'SUB-ValorunitariodereposicionNuevoComunes', 'IndiceDelCostoRemanente' => 'decimalPositivo', 'CostoDeLaFraccionN' => 'SUB-ValorDeLaFraccionNDescInmuebleComunes','PorcentajeIndivisoComunes' => 'SUB-PorcentajeIndivisoComunes');
        $validacionese25 = array('Descripcion' => 'SUB-DescripcionComunes', 'ClaveUso' => 'SUB-ClaveUsoComunes', 'NumeroDeNivelesDelTipo' => 'SUB-NumeroDeNivelesDelTipoComunes', 'ClaveRangoDeNiveles' => 'SUB-ClaveRangoDeNivelesComunes', 'PuntajeDeClasificacion' => 'SUB-PuntajeDeClasificacionComunes', 'ClaveClase' => 'SUB-ClaveClaseComunes', 'VidaMinimaRemanente' => 'SUB-VidaUtilRemanenteComunes', 'Superficie' => 'SUB-SuperficieComunes', 'CostoUnitarioDeReposicionNuevo' => 'SUB-ValorunitariodereposicionNuevoComunes', 'IndiceDelCostoRemanente' => 'decimalPositivo', 'CostoDeLaFraccionN' => 'SUB-ValorDeLaFraccionNDescInmuebleComunes','PorcentajeIndivisoComunes' => 'SUB-PorcentajeIndivisoComunes');
    }else{
        //$validacionese = array('UsoActual' => 'nonEmptyString_2000', 'VidaUtilTotalPonderadaDelInmueble' => 'nullableDecimalPositivo', 'EdadPonderadaDelInmueble' => 'nullableDecimalPositivo', 'VidaUtilRemanentePonderadaDelInmueble' => 'nullableDecimalPositivo');
        $validacionese = array('UsoActual' => 'nonEmptyString_2000', 'VidaUtilTotalPonderadaDelInmueble' => 'nullableDecimalPositivo', 'EdadPonderadaDelInmueble' => 'nullableDecimalPositivo', 'VidaMinimaRemanentePonderadaDelInmueble' => 'nullableDecimalPositivo', 'PorcentSuperfUltimNivelRespectoAnterior' => 'porcentaje_10');

        $validacionese2 = array('SuperficieTotalDeConstruccionesPrivativas' => 'SUB-SuperficieTotalDeConstruccionesPrivativas', 'ValorTotalDeConstruccionesPrivativas' => 'SUB-ValorTotalDeConstruccionesPrivativas', 'SuperficieTotalDeConstruccionesComunes' => 'SUB-SuperficieTotalDeConstruccionesComunes', 'ValorTotalDeConstruccionesComunes' => 'SUB-ValorTotalDeConstruccionesComunes', 'ValorTotalDeLasConstruccionesComunesProIndiviso' => 'SUB-ValorTotalDeLasConstruccionesProIndivisoComunes');
        
        $validacionese21 = array('Descripcion' => 'SUB-Descripcion', 'ClaveUso' => 'SUB-ClaveUso', 'NumeroDeNivelesDelTipo' => 'SUB-NumeroDeNivelesDelTipo', 'ClaveRangoDeNiveles' => 'SUB-ClaveRangoDeNiveles', 'PuntajeDeClasificacion' => 'SUB-PuntajeDeClasificacion', 'ClaveClase' => 'SUB-ClaveClase', 'Edad' => 'SUB-Edad', 'VidaUtilTotalDelTipo' => 'SUB-VidaUtilTipo', 'VidaMinimaRemanente' => 'SUB-VidaUtilRemanente', 'Superficie' => 'SUB-Superficie', 'CostoDeLaFraccionN' => 'SUB-ValorDeLaFraccionNDescInmueble', 'ValorUnitarioCatastral' => 'SUB-ValorUnitarioCatastral', 'DepreciacionPorEdad' => 'SUB-DepreciacionPorEdad');
       
        $validacionese25 = array('Descripcion' => 'SUB-Descripcion', 'ClaveUso' => 'SUB-ClaveUso', 'NumeroDeNivelesDelTipo' => 'SUB-NumeroDeNivelesDelTipo', 'ClaveRangoDeNiveles' => 'SUB-ClaveRangoDeNiveles', 'PuntajeDeClasificacion' => 'SUB-PuntajeDeClasificacion', 'ClaveClase' => 'SUB-ClaveClase', 'Edad' => 'SUB-Edad', 'VidaUtilTotalDelTipo' => 'SUB-VidaUtilTipo', 'VidaMinimaRemanente' => 'SUB-VidaUtilRemanente', 'Superficie' => 'SUB-Superficie', 'CostoDeLaFraccionN' => 'SUB-ValorDeLaFraccionNDescInmueble', 'ValorUnitarioCatastral' => 'SUB-ValorUnitarioCatastral', 'DepreciacionPorEdad' => 'SUB-DepreciacionPorEdad', 'PorcentajeIndivisoComunes' => 'SUB-PorcentajeIndivisoComunes');
    }

    $errores = array();
    $data = array_map("convierte_a_arreglo",$data);
    $data = limpiar_arreglo($data);

    $datad = array_map("convierte_a_arreglo",$datad);
    $resValidaCalculo = valida_Calculos_e($data,$elementoPrincipal, $datad, $b_6);
    if($resValidaCalculo != "Correcto"){
        $errores[] = $resValidaCalculo;
    }
    //print_r($data); exit();
    //print_r($data[0]['TiposDeConstruccion']['SuperficieTotalDeConstruccionesPrivativas']); exit();
    foreach($validacionese as $etiqueta => $validacion){
        if((!isset($data[0][$etiqueta]) && $etiqueta != 'PorcentSuperfUltimNivelRespectoAnterior')){
            $errores[] = "Falta ".$etiqueta." en DescripcionDelInmueble";
        }else{
            if((!isset($data[0][$etiqueta]) && $etiqueta == 'PorcentSuperfUltimNivelRespectoAnterior') || (is_array($data[0][$etiqueta]) && $etiqueta == 'PorcentSuperfUltimNivelRespectoAnterior')){
                $resValidacion = 'correcto';
            }else{
                $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);
            }                            
            if($resValidacion != 'correcto'){
                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
            }                
        }
    }
    //print_r($data[0]['TiposDeConstruccion']['ValorTotalDeLasConstruccionesComunesProIndiviso']); exit();
    foreach($validacionese2 as $etiqueta => $validacion){        

        if((!isset($data[0]['TiposDeConstruccion'][$etiqueta]) && $etiqueta != 'ValorTotalDeLasConstruccionesProIndiviso')){
            $errores[] = "Falta ".$etiqueta." en TiposDeConstruccion";
        }else{
                        
            $resValidacion = define_validacion($validacion, $data[0]['TiposDeConstruccion'][$etiqueta]);                
            if($resValidacion != 'correcto'){
                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
            }                
        }
    }
        
    if(isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['@attributes']) && $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['@attributes']['id'] == 'e.2.1'){

        if($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['ClaveUso'] == 'W'){
            if(trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Superficie']) != ''){
                $superficie = trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Superficie']);
                if($superficie == 0){
                    $usoNoBaldioConSuper = false;
                }else{
                    $usoNoBaldioConSuper = true;
                }
            }else{
                $usoNoBaldioConSuper = false;
            }
        }else{
            $usoNoBaldioConSuper = true;
        }

        if($usoNoBaldioConSuper == true){
            $claveUso = $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['ClaveUso'];
            $numeroEtiqueta = 1;
            $numeroArreglo = 0;
            foreach($validacionese21 as $etiqueta => $validacion){                
                if(trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas'][$etiqueta]) == '' && $numeroArreglo < 6){
                    $errores[] = $claveUso == 'W' ? "e.2.1.n.$numeroEtiqueta Campo obligatorio para el uso baldio" : "e.2.1.n.$numeroEtiqueta Campo obligatorio";
                }
                $numeroEtiqueta = $numeroEtiqueta + 1;
                $numeroArreglo = $numeroArreglo + 1;    
            }

            if($elementoPrincipal != '//Comercial' && trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Edad']) == ''){
                $errores[] = $claveUso == 'W' ? "e.2.1.n.7 Campo obligatorio para el uso baldio" : "e.2.1.n.7 Campo obligatorio";
            }else{
                if($elementoPrincipal == '//Comercial' && trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Edad']) != '' && !is_numeric($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Edad'])){
                    $errores[] = "e.2.1.n.7 El dato no es correcto, se requiere asignar un valor.";
                } 
            }

            if($elementoPrincipal != '//Comercial' && trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['VidaUtilTotalDelTipo']) == ''){
                if($claveUso != 'W'){
                    $errores[] = "e.2.1.n.8 Campo obligatorio";
                }                
            }

            /* if($claveUso != 'H' && trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['VidaMinimaRemanente']) == ''){                
                $errores[] = $claveUso == 'W' ? "e.2.1.n.9 Campo obligatorio para el uso baldio" : "e.2.1.n.9 Campo obligatorio";                              
            } */

            /*if($claveUso != 'H' && trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['ClaveConservacion']) == ''){                
                $errores[] = $claveUso == 'W' ? "e.2.1.n.10 Campo obligatorio para el uso baldio" : "e.2.1.n.10 Campo obligatorio";                              
            }*/

            if(trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['Superficie']) == ''){                
                $errores[] = $claveUso == 'W' ? "e.2.1.n.11 Campo obligatorio para el uso baldio" : "e.2.1.n.11 Campo obligatorio";                              
            }

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['CostoUnitarioDeReposicionNuevo'])  || trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['CostoUnitarioDeReposicionNuevo']) == '') && $elementoPrincipal == '//Comercial'){                
                $errores[] = $claveUso == 'W' ? "e.2.1.n.12 Campo obligatorio para el uso baldio" : "e.2.1.n.12 Campo obligatorio";                              
            }

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['FactorDeEdad'])  || trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['FactorDeEdad']) == '') && $elementoPrincipal == '//Comercial' && $claveUso != 'H'){                
                $errores[] = $claveUso == 'W' ? "e.2.1.n.13 Campo obligatorio para el uso baldio" : "e.2.1.n.13 Campo obligatorio";                              
            }

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['FactorResultante'])  || trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['FactorResultante']) == '') && $elementoPrincipal == '//Comercial' && $claveUso != 'H'){                
                $errores[] = $claveUso == 'W' ? "e.2.1.n.14 Campo obligatorio para el uso baldio" : "e.2.1.n.14 Campo obligatorio";                              
            }

            if(trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['CostoDeLaFraccionN']) == ''){              
                $errores[] = $claveUso == 'W' ? "e.2.1.n.15 Campo obligatorio para el uso baldio" : "e.2.1.n.15 Campo obligatorio";                              
            }

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['ValorUnitarioCatastral']) || trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['ValorUnitarioCatastral']) == '') && $elementoPrincipal != '//Comercial'){                
                if($claveUso != 'W'){
                    $errores[] = "e.2.1.n.16 Campo obligatorio";
                }                              
            }

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['DepreciacionPorEdad']) || trim($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas']['DepreciacionPorEdad']) == '') && $elementoPrincipal != '//Comercial'){                
                if($claveUso != 'W'){
                    $errores[] = "e.2.1.n.17 Campo obligatorio";
                }                              
            }

        }

        foreach($validacionese21 as $etiqueta => $validacion){
            if(!isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas'][$etiqueta])){
                $errores[] = "Falta ".$etiqueta." en ConstruccionesPrivativas";
            }else{
                $resValidacion = define_validacion($validacion, $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas'][$etiqueta]);                
                if($resValidacion != 'correcto'){
                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                }                
            }
        }    
        
    } 

    if(isset($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas'][0]['@attributes']) && $data[0]['TiposDeConstruccion']['ConstruccionesPrivativas'][0]['@attributes']['id'] == 'e.2.1'){
        foreach($data[0]['TiposDeConstruccion']['ConstruccionesPrivativas'] as $llavePrincipal => $elementoPrin){
            
            if($elementoPrin['ClaveUso'] == 'W'){
                if(trim($elementoPrin['Superficie']) != ''){
                    $superficie = trim($elementoPrin['Superficie']);
                    if($superficie == 0){
                        $usoNoBaldioConSuper = false;
                    }else{
                        $usoNoBaldioConSuper = true;
                    }
                }else{
                    $usoNoBaldioConSuper = false;
                }
            }else{
                $usoNoBaldioConSuper = true;
            }
    
            if($usoNoBaldioConSuper == true){
                $claveUso = $elementoPrin['ClaveUso'];
                $numeroEtiqueta = 1;
                $numeroArreglo = 0;
                foreach($validacionese21 as $etiqueta => $validacion){ //error_log("La etiqueta ".$etiqueta);               
                    if(trim($elementoPrin[$etiqueta]) == '' && $numeroArreglo < 6){
                        $errores[] = $claveUso == 'W' ? "e.2.1.n.$numeroEtiqueta Campo obligatorio para el uso baldio" : "e.2.1.n.$numeroEtiqueta Campo obligatorio";
                    }
                    $numeroEtiqueta = $numeroEtiqueta + 1;
                    $numeroArreglo = $numeroArreglo + 1;    
                }
    
                if($elementoPrincipal != '//Comercial' && trim($elementoPrin['Edad']) == ''){
                    $errores[] = $claveUso == 'W' ? "e.2.1.n.7 Campo obligatorio para el uso baldio" : "e.2.1.n.7 Campo obligatorio";
                }else{
                    if($elementoPrincipal == '//Comercial' && trim($elementoPrin['Edad']) != '' && !is_numeric($elementoPrin['Edad'])){
                        $errores[] = "e.2.1.n.7 El dato no es correcto, se requiere asignar un valor.";
                    } 
                }
    
                if($elementoPrincipal != '//Comercial' && trim($elementoPrin['VidaUtilTotalDelTipo']) == ''){
                    if($claveUso != 'W'){
                        $errores[] = "e.2.1.n.8 Campo obligatorio";
                    }                
                }
    
                /* if($claveUso != 'H' && trim($elementoPrin['VidaMinimaRemanente']) == ''){                
                    $errores[] = $claveUso == 'W' ? "e.2.1.n.9 Campo obligatorio para el uso baldio" : "e.2.1.n.9 Campo obligatorio";                              
                } */
    
                /*if($claveUso != 'H' && trim($elementoPrin['ClaveConservacion']) == ''){                
                    $errores[] = $claveUso == 'W' ? "e.2.1.n.10 Campo obligatorio para el uso baldio" : "e.2.1.n.10 Campo obligatorio";                              
                }*/
    
                if(trim($elementoPrin['Superficie']) == ''){                
                    $errores[] = $claveUso == 'W' ? "e.2.1.n.11 Campo obligatorio para el uso baldio" : "e.2.1.n.11 Campo obligatorio";                              
                }
    
                if((!isset($elementoPrin['CostoUnitarioDeReposicionNuevo']) || trim($elementoPrin['CostoUnitarioDeReposicionNuevo']) == '') && $elementoPrincipal == '//Comercial'){                
                    $errores[] = $claveUso == 'W' ? "e.2.1.n.12 Campo obligatorio para el uso baldio" : "e.2.1.n.12 Campo obligatorio";                              
                }
    
                if((!isset($elementoPrin['CostoUnitarioDeReposicionNuevo']) || trim($elementoPrin['IndiceDelCostoRemanente']) == '') && $elementoPrincipal == '//Comercial' && $claveUso != 'H'){                
                    $errores[] = $claveUso == 'W' ? "e.2.1.n.13 Campo obligatorio para el uso baldio" : "e.2.1.n.13 Campo obligatorio";                              
                }
    
                if(!isset($elementoPrin['CostoUnitarioDeReposicionNuevo']) && $elementoPrincipal == '//Comercial' && $claveUso != 'H'){                
                    $errores[] = $claveUso == 'W' ? "e.2.1.n.14 Campo obligatorio para el uso baldio" : "e.2.1.n.14 Campo obligatorio";                              
                }
    
                if(trim($elementoPrin['CostoDeLaFraccionN']) == ''){              
                    $errores[] = $claveUso == 'W' ? "e.2.1.n.15 Campo obligatorio para el uso baldio" : "e.2.1.n.15 Campo obligatorio";                              
                }
    
                if((!isset($elementoPrin['ValorUnitarioCatastral']) || trim($elementoPrin['ValorUnitarioCatastral']) == '') && $elementoPrincipal != '//Comercial'){                
                    if($claveUso != 'W'){
                        $errores[] = "e.2.1.n.16 Campo obligatorio";
                    }                              
                }
    
                if((!isset($elementoPrin['DepreciacionPorEdad']) || trim($elementoPrin['DepreciacionPorEdad']) == '') && $elementoPrincipal != '//Comercial'){                
                    if($claveUso != 'W'){
                        $errores[] = "e.2.1.n.17 Campo obligatorio";
                    }                              
                }
    
            }

            //if(is_array($elementoPrincipal) && $elementoPrincipal['id'] != 'e.2.1'){
                    foreach($validacionese21 as $etiqueta => $validacion){
                        if(!isset($elementoPrin[$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en ConstruccionesPrivativas";
                        }else{
                            $resValidacion = define_validacion($validacion, $elementoPrin[$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
            //}
           
        }
    }


    if(isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['@attributes']) && $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['@attributes']['id'] == 'e.2.5'){

        if($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['ClaveUso'] == 'W'){
            if(trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['Superficie']) != ''){
                $superficie = trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['Superficie']);
                if($superficie == 0){
                    $usoNoBaldioConSuper = false;
                }else{
                    $usoNoBaldioConSuper = true;
                }
            }else{
                $usoNoBaldioConSuper = false;
            }
        }else{
            $usoNoBaldioConSuper = true;
        }
        if($usoNoBaldioConSuper == true){

            $claveUso = $data[0]['TiposDeConstruccion']['ConstruccionesComunes']['ClaveUso'];
            $numeroEtiqueta = 1;
            $numeroArreglo = 0;
            foreach($validacionese25 as $etiqueta => $validacion){                
                if(trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes'][$etiqueta]) == '' && $numeroArreglo < 6){
                    $errores[] = $claveUso == 'W' ? "e.2.5.n.$numeroEtiqueta Campo obligatorio para el uso baldio" : "e.2.5.n.$numeroEtiqueta Campo obligatorio";
                }
                $numeroEtiqueta = $numeroEtiqueta + 1;
                $numeroArreglo = $numeroArreglo + 1;    
            }

            if($elementoPrincipal != '//Comercial' && trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['Edad']) == ''){
                $errores[] = $claveUso == 'W' ? "e.2.5.n.7 Campo obligatorio para el uso baldio" : "e.2.5.n.7 Campo obligatorio";
            }

            if($elementoPrincipal != '//Comercial' && trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['VidaUtilTotalDelTipo']) == ''){
                if($claveUso != 'W'){
                    $errores[] = "e.2.5.n.8 Campo obligatorio";
                }                
            }

            if($claveUso != 'H' && trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['VidaMinimaRemanente']) == ''){                
                $errores[] = $claveUso == 'W' ? "e.2.5.n.9 Campo obligatorio para el uso baldio" : "e.2.5.n.9 Campo obligatorio";                              
            }

            /*if($claveUso != 'W' && trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['ClaveConservacion']) == ''){                
                $errores[] = $claveUso == 'W' ? "e.2.5.n.10 Campo obligatorio para el uso baldio" : "e.2.5.n.10 Campo obligatorio";                              
            }*/

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['CostoUnitarioDeReposicionNuevo']) || trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['CostoUnitarioDeReposicionNuevo']) == '') && $elementoPrincipal == '//Comercial'){                
                $errores[] = $claveUso == 'W' ? "e.2.5.n.12 Campo obligatorio para el uso baldio" : "e.2.5.n.12 Campo obligatorio";                              
            }

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['FactorDeEdad']) || trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['FactorDeEdad']) == '') && $elementoPrincipal == '//Comercial' && $claveUso != 'H'){                
                $errores[] = $claveUso == 'W' ? "e.2.5.n.13 Campo obligatorio para el uso baldio" : "e.2.5.n.13 Campo obligatorio";                              
            }

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['FactorResultante']) || trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['FactorResultante']) == '') && $elementoPrincipal == '//Comercial' && $claveUso != 'H'){                
                $errores[] = $claveUso == 'W' ? "e.2.5.n.14 Campo obligatorio para el uso baldio" : "e.2.5.n.14 Campo obligatorio";                              
            }

            if(trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['CostoDeLaFraccionN']) == ''){              
                $errores[] = $claveUso == 'W' ? "e.2.5.n.15 Campo obligatorio para el uso baldio" : "e.2.5.n.15 Campo obligatorio";                              
            }

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['ValorUnitarioCatastral']) || trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['ValorUnitarioCatastral']) == '') && $elementoPrincipal != '//Comercial'){                
                if($claveUso != 'W'){
                    $errores[] = "e.2.5.n.16 Campo obligatorio";
                }                              
            }

            if((!isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['DepreciacionPorEdad']) || trim($data[0]['TiposDeConstruccion']['ConstruccionesComunes']['DepreciacionPorEdad']) == '') && $elementoPrincipal != '//Comercial'){                
                $errores[] = $claveUso == 'W' ? "e.2.5.n.17 Campo obligatorio para el uso baldio" : "e.2.5.n.17 Campo obligatorio";                             
            }

        }


        foreach($validacionese25 as $etiqueta => $validacion){
            if(!isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes'][$etiqueta])){
                $errores[] = "Falta ".$etiqueta." en ConstruccionesComunes";
            }else{
                $resValidacion = define_validacion($validacion, $data[0]['TiposDeConstruccion']['ConstruccionesComunes'][$etiqueta]);                
                if($resValidacion != 'correcto'){
                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                }                
            }
        }
    } 

    if(isset($data[0]['TiposDeConstruccion']['ConstruccionesComunes'][0]['@attributes']) && $data[0]['TiposDeConstruccion']['ConstruccionesComunes'][0]['@attributes']['id'] == 'e.2.5'){
        foreach($data[0]['TiposDeConstruccion']['ConstruccionesComunes'] as $llavePrincipal => $elementoPrin){            
            
            if($elementoPrin['ClaveUso'] == 'W'){
                if(trim($elementoPrin['Superficie']) != ''){
                    $superficie = trim($elementoPrin['Superficie']);
                    if($superficie == 0){
                        $usoNoBaldioConSuper = false;
                    }else{
                        $usoNoBaldioConSuper = true;
                    }
                }else{
                    $usoNoBaldioConSuper = false;
                }
            }else{
                $usoNoBaldioConSuper = true;
            }
            if($usoNoBaldioConSuper == true){
    
                $claveUso = $elementoPrin['ClaveUso'];
                $numeroEtiqueta = 1;
                $numeroArreglo = 0;
                foreach($validacionese25 as $etiqueta => $validacion){                
                    if(trim($elementoPrin[$etiqueta]) == '' && $numeroArreglo < 6){
                        $errores[] = $claveUso == 'W' ? "e.2.5.n.$numeroEtiqueta Campo obligatorio para el uso baldio" : "e.2.5.n.$numeroEtiqueta Campo obligatorio";
                    }
                    $numeroEtiqueta = $numeroEtiqueta + 1;
                    $numeroArreglo = $numeroArreglo + 1;    
                }
    
                if($elementoPrincipal != '//Comercial' && trim($elementoPrin['Edad']) == ''){
                    $errores[] = $claveUso == 'W' ? "e.2.5.n.7 Campo obligatorio para el uso baldio" : "e.2.5.n.7 Campo obligatorio";
                }
    
                if($elementoPrincipal != '//Comercial' && trim($elementoPrin['VidaUtilTotalDelTipo']) == ''){
                    if($claveUso != 'W'){
                        $errores[] = "e.2.5.n.8 Campo obligatorio";
                    }                
                }
    
                if($claveUso != 'H' && trim($elementoPrin['VidaMinimaRemanente']) == ''){                
                    $errores[] = $claveUso == 'W' ? "e.2.5.n.9 Campo obligatorio para el uso baldio" : "e.2.5.n.9 Campo obligatorio";                              
                }
    
                if($claveUso != 'W' && trim($elementoPrin['ClaveConservacion']) == ''){                
                    $errores[] = $claveUso == 'W' ? "e.2.5.n.10 Campo obligatorio para el uso baldio" : "e.2.5.n.10 Campo obligatorio";                              
                }
    
                if((!isset($elementoPrin['CostoUnitarioDeReposicionNuevo']) || trim($elementoPrin['CostoUnitarioDeReposicionNuevo']) == '') && $elementoPrincipal == '//Comercial'){                
                    $errores[] = $claveUso == 'W' ? "e.2.5.n.12 Campo obligatorio para el uso baldio" : "e.2.5.n.12 Campo obligatorio";                              
                }
    
                if((!isset($elementoPrin['FactorDeEdad']) || trim($elementoPrin['FactorDeEdad']) == '') && $elementoPrincipal == '//Comercial' && $claveUso != 'H'){                
                    $errores[] = $claveUso == 'W' ? "e.2.5.n.13 Campo obligatorio para el uso baldio" : "e.2.5.n.13 Campo obligatorio";                              
                }
    
                if((!isset($elementoPrin['FactorResultante']) || trim($elementoPrin['FactorResultante']) == '') && $elementoPrincipal == '//Comercial' && $claveUso != 'H'){                
                    $errores[] = $claveUso == 'W' ? "e.2.5.n.14 Campo obligatorio para el uso baldio" : "e.2.5.n.14 Campo obligatorio";                              
                }
    
                if(trim($elementoPrin['CostoDeLaFraccionN']) == ''){              
                    $errores[] = $claveUso == 'W' ? "e.2.5.n.15 Campo obligatorio para el uso baldio" : "e.2.5.n.15 Campo obligatorio";                              
                }
    
                if((!isset($elementoPrin['ValorUnitarioCatastral']) || trim($elementoPrin['ValorUnitarioCatastral']) == '') && $elementoPrincipal != '//Comercial'){                
                    if($claveUso != 'W'){
                        $errores[] = "e.2.5.n.16 Campo obligatorio";
                    }                              
                }
    
                if((!isset($elementoPrin['DepreciacionPorEdad']) || trim($elementoPrin['DepreciacionPorEdad']) == '') && $elementoPrincipal != '//Comercial'){                
                    $errores[] = $claveUso == 'W' ? "e.2.5.n.17 Campo obligatorio para el uso baldio" : "e.2.5.n.17 Campo obligatorio";                             
                }
    
            }

            //if(is_array($elementoPrincipal) && $elementoPrincipal['id'] != 'e.2.1'){
                    foreach($validacionese25 as $etiqueta => $validacion){
                        if(!isset($elementoPrin[$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en ConstruccionesComunes";
                        }else{
                            $resValidacion = define_validacion($validacion, $elementoPrin[$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
            //}            
        }
    }       

    return $errores;
}


function valida_AvaluoElementosDeLaConstruccion($data, $elementoPrincipal, $datad = false, $b_6 = false){
    
    if($elementoPrincipal == '//Comercial'){
        $validacionesf = array('InstalacionesElectricasYAlumbrado' => 'string_250', 'Vidreria' => 'string_250', 'Cerrajeria' => 'string_250', 'Fachadas' => 'string_250', 'SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas' => 'SUB-ImporteTotalInstalacionesAccesoriosComplementariasPrivativas', 'SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes' => 'SUB-ImporteTotalInstalacionesAccesoriosComplementariasComunes', 'ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes' => 'SUB-ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes', 'ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas' => 'SUB-ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas');

        $validacionesf1 = array('Cimentacion' => 'SUB-Cimentacion', 'Estructura' => 'string_250', 'Muros' => 'string_250', 'Entrepisos' => 'string_250', 'Techos' => 'string_250', 'Azoteas' => 'string_250', 'Bardas' => 'string_250');
        $validacionesf2 = array('Aplanados' => 'string_500', 'Plafones' => 'string_500', 'Lambrines' => 'string_500', 'Pisos' => 'string_500', 'Zoclos' => 'string_500', 'Escaleras' => 'string_500', 'Pintura' => 'string_500', 'RecubrimientosEspeciales' => 'string_500');
        $validacionesf3 = array('PuertasInteriores' => 'string_250', 'Guardaropas' => 'string_250', 'MueblesEmpotradosOFijos' => 'string_250');
        $validacionesf4 = array('MueblesDeBanno' => 'string_500', 'RamaleosHidraulicos' => 'string_500', 'RamaleosSanitarios' => 'string_500');
        $validacionesf5 = array('Herreria' => 'string_500', 'Ventaneria' => 'string_250');

        $validacionesf9 = array('ImporteTotalInstalacionesEspecialesPrivativas' => 'SUB-ImporteTotalInstalacionesEspecialesPrivativas', 'ImporteTotalInstalacionesEspecialesComunes' => 'SUB-ImporteTotalInstalacionesEspecialesComunes');

        $validacionesf91 = array('ClaveInstalacionEspecial' => 'SUB-ClaveInstalacionEspecial-PrivativaCom', 'DescripcionInstalacionEspecial' => 'SUB-DescripcionInstalacionEspecial-Privativa', 'UnidadInstalacionEspecial' => 'SUB-UnidadInstalacionEspecial-Privativa', 'CantidadInstalacionEspecial' => 'SUB-CantidadInstalacionEspecial-Privativa', 'EdadInstalacionEspecial' => 'nullableDecimalPositivo', 'VidaUtilTotalInstalacionEspecial' => 'nullableDecimalPositivo', 'CostoUnitarioInstalacionEspecial' => 'SUB-ValorUnitarioInstalacionEspecial-Privativa', 'FactorDeEdadInstalacionEspecial' => 'SUB-FactorDeEdadInstalacionEspecial', 'ImporteInstalacionEspecial' => 'SUB-ImporteInstalacionEspecial');

        $validacionesf92 = array('ClaveInstalacionEspecial' => 'SUB-ClaveInstalacionEspecial-ComunesCom', 'DescripcionInstalacionEspecial' => 'SUB-DescripcionInstalacionEspecial-Comunes', 'UnidadInstalacionEspecial' => 'SUB-UnidadInstalacionEspecial-Comunes', 'CantidadInstalacionEspecial' => 'SUB-CantidadInstalacionEspecial-Comunes', 'EdadInstalacionEspecial' => 'nullableDecimalPositivo', 'VidaUtilTotalInstalacionEspecial' => 'nullableDecimalPositivo', 'CostoUnitarioInstalacionEspecial' => 'SUB-ValorUnitarioInstalacionEspecial-Comunes', 'FactorDeEdadInstalacionEspecial' => 'SUB-FactorDeEdadInstalacionEspecialComunes', 'ImporteInstalacionEspecial' => 'SUB-ImporteInstalacionEspecialComunes', 'PorcentajeIndivisoEspecial' => 'SUB-PorcentajeIndivisoEspecialComunes');

        $validacionesf10 = array('ImporteTotalElementosAccesoriosPrivativas' => 'SUB-ImporteTotalElementosAccesoriosPrivativas', 'ImporteTotalElementosAccesoriosComunes' => 'SUB-ImporteTotalElementosAccesoriosComunes-Comunes');

        $validacionesf101 = array('ClaveElementoAccesorio' => 'SUB-ClaveElementoAccesorio-PrivativasCom', 'DescripcionElementoAccesorio' => 'SUB-DescripcionElementoAccesorio-Privativas', 'UnidadElementoAccesorio' => 'SUB-UnidadElementoAccesorio-Privativas', 'CantidadElementoAccesorio' => 'SUB-CantidadElementoAccesorio-Privativas', 'EdadElementoAccesorio' => 'SUB-EdadElementoAccesorio', 'VidaUtilTotalElementoAccesorio' => 'SUB-VidaUtilTotalElementoAccesorio', 'CostoUnitarioElementoAccesorio' => 'SUB-ValorUnitarioElementoAccesorio-Privativas', 'FactorDeEdadElementoAccesorio' => 'SUB-FactorDeEdadElementoAccesorio', 'ImporteElementoAccesorio' => 'SUB-ImporteElementoAccesorio');

        $validacionesf102 = array('ClaveElementoAccesorio' => 'SUB-ClaveElementoAccesorio-ComunesCom', 'DescripcionElementoAccesorio' => 'SUB-DescripcionElementoAccesorio-ComunesCom', 'UnidadElementoAccesorio' => 'SUB-UnidadElementoAccesorio-ComunesCom', 'CantidadElementoAccesorio' => 'SUB-CantidadElementoAccesorio-ComunesCom', 'EdadElementoAccesorio' => 'SUB-EdadElementoAccesorio-Comunes', 'VidaUtilTotalElementoAccesorio' => 'SUB-VidaUtilTotalElementoAccesorioComunes', 'CostoUnitarioElementoAccesorio' => 'SUB-ValorUnitarioElementoAccesorio-Comunes', 'FactorDeEdadElementoAccesorio' => 'SUB-FactorDeEdadElementoAccesorioComunes', 'ImporteElementoAccesorio' => 'SUB-ImporteElementoAccesorio-Comunes', 'PorcentajeIndivisoAccesorio' => 'SUB-PorcentajeIndivisoAccesorio-Comunes');

        $validacionesf11 = array('ImporteTotalObrasComplementariasPrivativas' => 'SUB-ImporteTotalObrasComplementariasPrivativas', 'ImporteTotalObrasComplementariasComunes' => 'SUB-ImporteTotalObrasComplementariasComunes');

        $validacionesf111 = array('ClaveObraComplementaria' => 'SUB-ClaveObraComplementaria-PrivativasCom', 'DescripcionObraComplementaria' => 'SUB-DescripcionObraComplementaria-Privativas', 'UnidadObraComplementaria' => 'SUB-UnidadObraComplementaria-Privativas', 'CantidadObraComplementaria' => 'SUB-CantidadObraComplementaria-Privativas', 'EdadObraComplementaria' => 'SUB-EdadObraComplementaria-Privativas', 'VidaUtilTotalObraComplementaria' => 'nullableDecimalPositivo', 'CostoUnitarioObraComplementaria' => 'SUB-ValorUnitarioObraComplementaria-Privativas', 'FactorDeEdadObraComplementaria' => 'SUB-FactorDeEdadObraComplementaria-Privativas', 'ImporteObraComplementaria' => 'SUB-ImporteObraComplementaria-Privativas');

        $validacionesf112 = array('ClaveObraComplementaria' => 'SUB-ClaveObraComplementaria-ComunesCom', 'DescripcionObraComplementaria' => 'SUB-DescripcionObraComplementaria-ComunesCom', 'UnidadObraComplementaria' => 'SUB-UnidadObraComplementaria-ComunesCom', 'CantidadObraComplementaria' => 'SUB-CantidadObraComplementaria-ComunesCom', 'EdadObraComplementaria' => 'SUB-EdadObraComplementaria-Comunes', 'VidaUtilTotalObraComplementaria' => 'SUB-VidaUtilTotalObraComplementaria-Comunes', 'CostoUnitarioObraComplementaria' => 'SUB-ValorUnitarioObraComplementaria-Comunes', 'FactorDeEdadObraComplementaria' => 'SUB-FactorDeEdadObraComplementaria-Comunes', 'ImporteObraComplementaria' => 'SUB-ImporteObraComplementaria-complementaria', 'PorcentajeIndivisoObraComplementaria' => 'SUB-PorcentajeIndivisoObraComplementaria-complementaria');
    }else{        
        $validacionesf = array('InstalacionesElectricasYAlumbrado' => 'string_250', 'Vidreria' => 'string_250', 'Cerrajeria' => 'string_250', 'Fachadas' => 'string_250', 'SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas' => 'SUB-ImporteTotalInstalacionesAccesoriosComplementariasPrivativas', 'SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes' => 'SUB-ImporteTotalInstalacionesAccesoriosComplementariasComunes', 'ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes' => 'SUB-ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes', 'ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas' => 'SUB-ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas');
        $validacionesf1 = array('Cimentacion' => 'SUB-Cimentacion', 'Estructura' => 'string_250', 'Muros' => 'string_250', 'Entrepisos' => 'string_250', 'Techos' => 'string_250', 'Azoteas' => 'string_250', 'Bardas' => 'string_250');
        $validacionesf2 = array('Aplanados' => 'string_500', 'Plafones' => 'string_500', 'Lambrines' => 'string_500', 'Pisos' => 'string_500', 'Zoclos' => 'string_500', 'Escaleras' => 'string_500', 'Pintura' => 'string_250', 'RecubrimientosEspeciales' => 'string_250');
        $validacionesf3 = array('PuertasInteriores' => 'string_250', 'Guardaropas' => 'string_250', 'MueblesEmpotradosOFijos' => 'string_250');
        $validacionesf4 = array('MueblesDeBanno' => 'string_500', 'RamaleosHidraulicos' => 'string_500', 'RamaleosSanitarios' => 'string_500');
        
        $validacionesf5 = array('Herreria' => 'string_250', 'Ventaneria' => 'string_250', 'Vidreria' => 'string_250', 'Cerrajeria' => 'string_250', 'Fachadas' => 'string_250');

        $validacionesf91 = array('ClaveInstalacionEspecial' => 'SUB-ClaveInstalacionEspecial-PrivativaCat', 'DescripcionInstalacionEspecial' => 'SUB-DescripcionInstalacionEspecial-Privativa', 'UnidadInstalacionEspecial' => 'SUB-UnidadInstalacionEspecial-Privativa', 'CantidadInstalacionEspecial' => 'SUB-CantidadInstalacionEspecial-Privativa');

        $validacionesf92 = array('ClaveInstalacionEspecial' => 'SUB-ClaveInstalacionEspecial-ComunesCat', 'DescripcionInstalacionEspecial' => 'SUB-DescripcionInstalacionEspecial-Comunes', 'UnidadInstalacionEspecial' => 'SUB-UnidadInstalacionEspecial-Comunes', 'CantidadInstalacionEspecial' => 'SUB-CantidadInstalacionEspecial-Comunes');

        $validacionesf101 = array('ClaveElementoAccesorio' => 'SUB-ClaveElementoAccesorio-PrivativasCat', 'DescripcionElementoAccesorio' => 'SUB-DescripcionElementoAccesorio-Privativas', 'UnidadElementoAccesorio' => 'SUB-UnidadElementoAccesorio-Privativas', 'CantidadElementoAccesorio' => 'SUB-CantidadElementoAccesorio-Privativas');

        $validacionesf102 = array('ClaveElementoAccesorio' => 'SUB-ClaveElementoAccesorio-ComunesCat', 'DescripcionElementoAccesorio' => 'SUB-DescripcionElementoAccesorio-ComunesCat', 'UnidadElementoAccesorio' => 'SUB-UnidadElementoAccesorio-ComunesCat', 'CantidadElementoAccesorio' => 'SUB-CantidadElementoAccesorio-ComunesCat');

        $validacionesf111 = array('ClaveObraComplementaria' => 'SUB-ClaveObraComplementaria-PrivativasCat', 'DescripcionObraComplementaria' => 'SUB-DescripcionObraComplementaria-Privativas', 'UnidadObraComplementaria' => 'SUB-UnidadObraComplementaria-Privativas', 'CantidadObraComplementaria' => 'SUB-CantidadObraComplementaria-Privativas');

        $validacionesf112 = array('ClaveObraComplementaria' => 'SUB-ClaveObraComplementaria-ComunesCat', 'DescripcionObraComplementaria' => 'SUB-DescripcionObraComplementaria-ComunesCat', 'UnidadObraComplementaria' => 'SUB-UnidadObraComplementaria-ComunesCat', 'CantidadObraComplementaria' => 'SUB-CantidadObraComplementaria-ComunesCat');
    }

    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data); //print_r($data); exit();

    $datad = array_map("convierte_a_arreglo",$datad);

    $resValidaCalculo = valida_Calculos($data,'f',$datad, $b_6);
    if($resValidaCalculo != "Correcto"){
        $errores[] = $resValidaCalculo;
    }

    if(count($data) > 1){
        $obligatoriosf = array();
            foreach($validacionesf as $etiqueta => $validacion){    
                
                if(!isset($data[0][$etiqueta]) && in_array($etiqueta,$obligatoriosf)){
                    $errores[] = "Falta ".$etiqueta." en ElementosDeLaConstruccion";
                }else{
                    if(isset($data[0][$etiqueta])){
                        $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }
                    }
                                    
                }
            }
        
        $obligatoriosf1 = array();
            foreach($validacionesf1 as $etiqueta => $validacion){
                if(!isset($data[0]['ObraNegra'][$etiqueta]) && in_array($etiqueta,$obligatoriosf1)){
                    $errores[] = "Falta ".$etiqueta." en ObraNegra";
                }else{
                    if(isset($data[0]['ObraNegra'][$etiqueta])){
                        $resValidacion = define_validacion($validacion, $data[0]['ObraNegra'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }
                    }
                                    
                }
            }
        
        $obligatoriosf2 = array();
            foreach($validacionesf2 as $etiqueta => $validacion){
                if(!isset($data[0]['RevestimientosYAcabadosInteriores'][$etiqueta]) && in_array($etiqueta,$obligatoriosf2)){
                    $errores[] = "Falta ".$etiqueta." en RevestimientosYAcabadosInteriores";
                }else{
                    if(isset($data[0]['RevestimientosYAcabadosInteriores'][$etiqueta])){
                        $resValidacion = define_validacion($validacion, $data[0]['RevestimientosYAcabadosInteriores'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }
                    }                                    
                }
            }
        $obligatoriosf3 = array();
            foreach($validacionesf3 as $etiqueta => $validacion){
                if(!isset($data[0]['Carpinteria'][$etiqueta])  && in_array($etiqueta,$obligatoriosf3)){
                    $errores[] = "Falta ".$etiqueta." en Carpinteria";
                }else{
                    if(isset($data[0]['Carpinteria'][$etiqueta])){
                        $resValidacion = define_validacion($validacion, $data[0]['Carpinteria'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }
                    }
                                    
                }
            }
        $obligatoriosf4 = array();
            foreach($validacionesf4 as $etiqueta => $validacion){
                if(!isset($data[0]['InstalacionesHidraulicasYSanitrias'][$etiqueta]) && in_array($etiqueta,$obligatoriosf4)){
                    $errores[] = "Falta ".$etiqueta." en InstalacionesHidraulicasYSanitrias";
                }else{
                    if(isset($data[0]['InstalacionesHidraulicasYSanitrias'][$etiqueta])){
                        $resValidacion = define_validacion($validacion, $data[0]['InstalacionesHidraulicasYSanitrias'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }
                    }
                }
            }
            
        $obligatoriosf5 = array();
            foreach($validacionesf5 as $etiqueta => $validacion){
                if(!isset($data[0]['PuertasYVentaneriaMetalica'][$etiqueta]) && in_array($etiqueta,$obligatoriosf5)){
                    $errores[] = "Falta ".$etiqueta." en PuertasYVentaneriaMetalica";
                }else{
                    if(isset($data[0]['PuertasYVentaneriaMetalica'][$etiqueta])){
                        $resValidacion = define_validacion($validacion, $data[0]['PuertasYVentaneriaMetalica'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }
                    }                    
                }
            }
        
            if(isset($data[0]['InstalacionesEspeciales']) && count($data[0]['InstalacionesEspeciales']) > 1){                

                if(isset($data[0]['InstalacionesEspeciales']['Privativas']['@attributes']) && $data[0]['InstalacionesEspeciales']['Privativas']['@attributes']['id'] == 'f.9.1'){

                    if($elementoPrincipal == '//Comercial'){
                        $obligatorios = array('ClaveInstalacionEspecial','DescripcionInstalacionEspecial','UnidadInstalacionEspecial','CantidadInstalacionEspecial','EdadInstalacionEspecial','VidaUtilTotalInstalacionEspecial','','FactorDeEdadInstalacionEspecial','ImporteInstalacionEspecial');
                    }else{
                        $obligatorios = array('ClaveInstalacionEspecial','DescripcionInstalacionEspecial','UnidadInstalacionEspecial','CantidadInstalacionEspecial');
                    }

                    foreach($validacionesf91 as $etiqueta => $validacion){
                        if(!isset($data[0]['InstalacionesEspeciales']['Privativas'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en en InstalacionesEspeciales (Privativas)";
                        }else{
                            $resValidacion = define_validacion($validacion, $data[0]['InstalacionesEspeciales']['Privativas'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
                }
        
                if(isset($data[0]['InstalacionesEspeciales']['Privativas'][0]['@attributes']) && $data[0]['InstalacionesEspeciales']['Privativas'][0]['@attributes']['id'] == 'f.9.1'){

                    if($elementoPrincipal == '//Comercial'){
                        $obligatorios = array('ClaveInstalacionEspecial','DescripcionInstalacionEspecial','UnidadInstalacionEspecial','CantidadInstalacionEspecial','EdadInstalacionEspecial','VidaUtilTotalInstalacionEspecial','CostoUnitarioInstalacionEspecial','FactorDeEdadInstalacionEspecial','ImporteInstalacionEspecial');
                    }else{
                        $obligatorios = array('ClaveInstalacionEspecial','DescripcionInstalacionEspecial','UnidadInstalacionEspecial','CantidadInstalacionEspecial');
                    }

                    foreach($data[0]['InstalacionesEspeciales']['Privativas'] as $llavePrincipal => $elementoPrincipal){            
                        //if(is_array($elementoPrincipal) && $elementoPrincipal['id'] != 'e.2.1'){
                                foreach($validacionesf91 as $etiqueta => $validacion){
                                    if(!isset($elementoPrincipal[$etiqueta])){
                                        $errores[] = "Falta ".$etiqueta." en InstalacionesEspeciales (Privativas)";
                                    }else{
                                        $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                                        if($resValidacion != 'correcto'){
                                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                        }                
                                    }
                                }
                        //}            
                    }
                }
        
                if(isset($data[0]['InstalacionesEspeciales']['Comunes']['@attributes']) && $data[0]['InstalacionesEspeciales']['Comunes']['@attributes']['id'] == 'f.9.2'){
                    if($elementoPrincipal == '//Comercial'){
                        $obligatorios = array();
                    }else{
                        $obligatorios = array('ClaveInstalacionEspecial','DescripcionInstalacionEspecial','UnidadInstalacionEspecial','CantidadInstalacionEspecial');
                    }
                    foreach($validacionesf92 as $etiqueta => $validacion){
                        if(!isset($data[0]['InstalacionesEspeciales']['Comunes'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en en InstalacionesEspeciales (Comunes)";
                        }else{
                            $resValidacion = define_validacion($validacion, $data[0]['InstalacionesEspeciales']['Comunes'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
                }
        
                if(isset($data[0]['InstalacionesEspeciales']['Comunes'][0]['@attributes']) && $data[0]['InstalacionesEspeciales']['Comunes'][0]['@attributes']['id'] == 'f.9.2'){
                    if($elementoPrincipal == '//Comercial'){
                        $obligatorios = array();
                    }else{
                        $obligatorios = array('ClaveInstalacionEspecial','DescripcionInstalacionEspecial','UnidadInstalacionEspecial','CantidadInstalacionEspecial');
                    }
                    foreach($data[0]['InstalacionesEspeciales']['Comunes'] as $llavePrincipal => $elementoPrincipal){            
                        //if(is_array($elementoPrincipal) && $elementoPrincipal['id'] != 'e.2.1'){
                                foreach($validacionesf92 as $etiqueta => $validacion){
                                    if(!isset($elementoPrincipal[$etiqueta])){
                                        $errores[] = "Falta ".$etiqueta." en InstalacionesEspeciales (Comunes)";
                                    }else{
                                        $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                                        if($resValidacion != 'correcto'){
                                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                        }                
                                    }
                                }
                        //}            
                    }
                }

                if($elementoPrincipal == '//Comercial'){
                    $obligatoriosf9 = array('ImporteTotalInstalacionesEspecialesPrivativas','ImporteTotalInstalacionesEspecialesComunes');
                }else{
                    $obligatoriosf9 = array();
                }                

                foreach($validacionesf9 as $etiqueta => $validacion){
                    if(!isset($data[0]['InstalacionesEspeciales'][$etiqueta]) && in_array($etiqueta, $obligatoriosf9)){
                        $errores[] = "Falta ".$etiqueta." en InstalacionesEspeciales";
                    }else{
                        if(isset($data[0]['InstalacionesEspeciales'][$etiqueta])){
                            $resValidacion = define_validacion($validacion, $data[0]['InstalacionesEspeciales'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }
                        }                                        
                    }
                }
            }
        
            if(isset($data[0]['ElementosAccesorios']) && count($data[0]['ElementosAccesorios']) > 1){        
                
                
                if(isset($data[0]['ElementosAccesorios']['Privativas']['@attributes']) && $data[0]['ElementosAccesorios']['Privativas']['@attributes']['id'] == 'f.10.1'){
        
                    if($elementoPrincipal == '//Comercial'){
                        $obligatorios = array('ClaveElementoAccesorio','DescripcionElementoAccesorio','UnidadElementoAccesorio','CantidadElementoAccesorio','EdadElementoAccesorio','VidaUtilTotalElementoAccesorio','CostoUnitarioElementoAccesorio','FactorDeEdadElementoAccesorio','ImporteElementoAccesorio');
                    }else{
                        $obligatorios = array('ClaveElementoAccesorio','DescripcionElementoAccesorio','UnidadElementoAccesorio','CantidadElementoAccesorio');
                    }
        
                    foreach($validacionesf101 as $etiqueta => $validacion){
                        if(!isset($data[0]['ElementosAccesorios']['Privativas'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en en ElementosAccesorios (Privativas)";
                        }else{
                            $resValidacion = define_validacion($validacion, $data[0]['ElementosAccesorios']['Privativas'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
                }
        
                if(isset($data[0]['ElementosAccesorios']['Privativas'][0]['@attributes']) && $data[0]['ElementosAccesorios']['Privativas'][0]['@attributes']['id'] == 'f.10.1'){
        
                    if($elementoPrincipal == '//Comercial'){
                        $obligatorios = array('ClaveElementoAccesorio','DescripcionElementoAccesorio','UnidadElementoAccesorio','CantidadElementoAccesorio','EdadElementoAccesorio','VidaUtilTotalElementoAccesorio','CostoUnitarioElementoAccesorio','FactorDeEdadElementoAccesorio','ImporteElementoAccesorio');
                    }else{
                        $obligatorios = array('ClaveElementoAccesorio','DescripcionElementoAccesorio','UnidadElementoAccesorio','CantidadElementoAccesorio');
                    }
        
                    foreach($data[0]['ElementosAccesorios']['Privativas'] as $llavePrincipal => $elementoPrincipal){            
                        //if(is_array($elementoPrincipal) && $elementoPrincipal['id'] != 'e.2.1'){
                                foreach($validacionesf101 as $etiqueta => $validacion){
                                    if(!isset($elementoPrincipal[$etiqueta])){
                                        $errores[] = "Falta ".$etiqueta." en ElementosAccesorios (Privativas)";
                                    }else{
                                        $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                                        if($resValidacion != 'correcto'){
                                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                        }                
                                    }
                                }
                        //}            
                    }
                }
        
                if(isset($data[0]['ElementosAccesorios']['Comunes']['@attributes']) && $data[0]['ElementosAccesorios']['Comunes']['@attributes']['id'] == 'f.10.2'){
        
                    if($elementoPrincipal == '//Comercial'){
                        $obligatorios = array('ClaveElementoAccesorio','DescripcionElementoAccesorio','UnidadElementoAccesorio','CantidadElementoAccesorio','EdadElementoAccesorio','VidaUtilTotalElementoAccesorio','CostoUnitarioElementoAccesorio','FactorDeEdadElementoAccesorio','ImporteElementoAccesorio');
                    }else{
                        $obligatorios = array('ClaveElementoAccesorio','DescripcionElementoAccesorio','UnidadElementoAccesorio','CantidadElementoAccesorio');
                    }
        
                    foreach($validacionesf102 as $etiqueta => $validacion){
                        if(!isset($data[0]['ElementosAccesorios']['Comunes'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en en ElementosAccesorios (Comunes)";
                        }else{
                            $resValidacion = define_validacion($validacion, $data[0]['ElementosAccesorios']['Comunes'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
                }
        
                if(isset($data[0]['ElementosAccesorios']['Comunes'][0]['@attributes']) && $data[0]['ElementosAccesorios']['Comunes'][0]['@attributes']['id'] == 'f.10.2'){
        
                    if($elementoPrincipal == '//Comercial'){
                        $obligatorios = array('ClaveElementoAccesorio','DescripcionElementoAccesorio','UnidadElementoAccesorio','CantidadElementoAccesorio','EdadElementoAccesorio','VidaUtilTotalElementoAccesorio','CostoUnitarioElementoAccesorio','FactorDeEdadElementoAccesorio','ImporteElementoAccesorio');
                    }else{
                        $obligatorios = array('ClaveElementoAccesorio','DescripcionElementoAccesorio','UnidadElementoAccesorio','CantidadElementoAccesorio');
                    }
        
                    foreach($data[0]['ElementosAccesorios']['Comunes'] as $llavePrincipal => $elementoPrincipal){            
                        //if(is_array($elementoPrincipal) && $elementoPrincipal['id'] != 'e.2.1'){
                                foreach($validacionesf102 as $etiqueta => $validacion){
                                    if(!isset($elementoPrincipal[$etiqueta])){
                                        $errores[] = "Falta ".$etiqueta." en ElementosAccesorios (Comunes)";
                                    }else{
                                        $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                                        if($resValidacion != 'correcto'){
                                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                        }                
                                    }
                                }
                        //}            
                    }
                }

                
                $obligatoriosf10 = array();
                        
                foreach($validacionesf10 as $etiqueta => $validacion){
                    if(!isset($data[0]['ElementosAccesorios'][$etiqueta]) && in_array($etiqueta,$obligatoriosf10)){
                        $errores[] = "Falta ".$etiqueta." en ElementosAccesorios";
                    }else{
                        if(isset($data[0]['ElementosAccesorios'][$etiqueta])){
                            $resValidacion = define_validacion($validacion, $data[0]['ElementosAccesorios'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }
                        }
                                        
                    }
                }
            }
        
        if(isset($data[0]['ObrasComplementarias']) && count($data[0]['ObrasComplementarias']) > 1){
            if(isset($data[0]['ObrasComplementarias']['Privativas']['@attributes']) && $data[0]['ObrasComplementarias']['Privativas']['@attributes']['id'] == 'f.11.1'){
        
                if($elementoPrincipal == '//Comercial'){
                    $obligatorios = array('ClaveObraComplementaria','DescripcionObraComplementaria','UnidadObraComplementaria','CantidadObraComplementaria','EdadObraComplementaria','VidaUtilTotalObraComplementaria','CostoUnitarioObraComplementaria','FactorDeEdadObraComplementaria','ImporteObraComplementaria');
                }else{
                    $obligatorios = array('ClaveObraComplementaria','DescripcionObraComplementaria','UnidadObraComplementaria','CantidadObraComplementaria');
                }
        
                foreach($validacionesf111 as $etiqueta => $validacion){
                    if(!isset($data[0]['ObrasComplementarias']['Privativas'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en ObrasComplementarias (Privativas)";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['ObrasComplementarias']['Privativas'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
            }
        
            if(isset($data[0]['ObrasComplementarias']['Privativas'][0]['@attributes']) && $data[0]['ObrasComplementarias']['Privativas'][0]['@attributes']['id'] == 'f.11.1'){
                if($elementoPrincipal == '//Comercial'){
                    $obligatorios = array('ClaveObraComplementaria','DescripcionObraComplementaria','UnidadObraComplementaria','CantidadObraComplementaria','EdadObraComplementaria','VidaUtilTotalObraComplementaria','CostoUnitarioObraComplementaria','FactorDeEdadObraComplementaria','ImporteObraComplementaria');
                }else{
                    $obligatorios = array('ClaveObraComplementaria','DescripcionObraComplementaria','UnidadObraComplementaria','CantidadObraComplementaria');
                }
                foreach($data[0]['ObrasComplementarias']['Privativas'] as $llavePrincipal => $elementoPrincipal){            
                    //if(is_array($elementoPrincipal) && $elementoPrincipal['id'] != 'e.2.1'){
                            foreach($validacionesf111 as $etiqueta => $validacion){
                                if(!isset($elementoPrincipal[$etiqueta])){
                                    $errores[] = "Falta ".$etiqueta." en ObrasComplementarias (Privativas)";
                                }else{
                                    $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                                    if($resValidacion != 'correcto'){
                                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                    }                
                                }
                            }
                    //}            
                }
            }
        
            if(isset($data[0]['ObrasComplementarias']['Comunes']['@attributes']) && $data[0]['ObrasComplementarias']['Comunes']['@attributes']['id'] == 'f.11.2'){
        
                if($elementoPrincipal == '//Comercial'){
                    $obligatorios = array('ClaveObraComplementaria','DescripcionObraComplementaria','UnidadObraComplementaria','CantidadObraComplementaria','EdadObraComplementaria','VidaUtilTotalObraComplementaria','CostoUnitarioObraComplementaria','FactorDeEdadObraComplementaria','ImporteObraComplementaria');
                }else{
                    $obligatorios = array('ClaveObraComplementaria','DescripcionObraComplementaria','UnidadObraComplementaria','CantidadObraComplementaria');
                }
        
                foreach($validacionesf112 as $etiqueta => $validacion){
                    if(!isset($data[0]['ObrasComplementarias']['Comunes'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en ObrasComplementarias (Comunes)";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['ObrasComplementarias']['Comunes'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
            }
        
            if(isset($data[0]['ObrasComplementarias']['Comunes'][0]['@attributes']) && $data[0]['ObrasComplementarias']['Comunes'][0]['@attributes']['id'] == 'f.11.2'){
        
                if($elementoPrincipal == '//Comercial'){
                    $obligatorios = array('ClaveObraComplementaria','DescripcionObraComplementaria','UnidadObraComplementaria','CantidadObraComplementaria','EdadObraComplementaria','VidaUtilTotalObraComplementaria','CostoUnitarioObraComplementaria','FactorDeEdadObraComplementaria','ImporteObraComplementaria');
                }else{
                    $obligatorios = array('ClaveObraComplementaria','DescripcionObraComplementaria','UnidadObraComplementaria','CantidadObraComplementaria');
                }
        
                foreach($data[0]['ObrasComplementarias']['Comunes'] as $llavePrincipal => $elementoPrincipal){            
                    //if(is_array($elementoPrincipal) && $elementoPrincipal['id'] != 'e.2.1'){
                            foreach($validacionesf112 as $etiqueta => $validacion){
                                if(!isset($elementoPrincipal[$etiqueta])){
                                    $errores[] = "Falta ".$etiqueta." en ObrasComplementarias (Comunes)";
                                }else{
                                    $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                                    if($resValidacion != 'correcto'){
                                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                    }                
                                }
                            }
                    //}            
                }
            }
        $obligatoriosf11 = array();
            foreach($validacionesf11 as $etiqueta => $validacion){
                if(!isset($data[0]['ObrasComplementarias'][$etiqueta]) && in_array($etiqueta,$obligatoriosf11)){
                    $errores[] = "Falta ".$etiqueta." en ObrasComplementarias";
                }else{
                    if(isset($data[0]['ObrasComplementarias'][$etiqueta])){
                        $resValidacion = define_validacion($validacion, $data[0]['ObrasComplementarias'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }
                    }                                    
                }
            }
        }

    }

    
    //print_r($data[0]['InstalacionesEspeciales']); exit();
    
    return $errores;
}

function valida_ConsideracionesPreviasAlAvaluo($data){

    $validacionesg = array('ConsideracionesPreviasAlAvaluo' => 'string_2000');
    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data);

    foreach($validacionesg as $etiqueta => $validacion){
        if(!isset($data[0]['ConsideracionesPreviasAlAvaluo'][$etiqueta])){
            $errores[] = "Falta ".$etiqueta." en ConsideracionesPreviasAlAvaluo";
        }else{
            $resValidacion = define_validacion($validacion, $data[0]['ConsideracionesPreviasAlAvaluo'][$etiqueta]);                
            if($resValidacion != 'correcto'){
                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
            }                
        }
    }

    return $errores;
}

function valida_AvaluoEnfoqueMercado($data){

    $validacionesh = array('ValorDeMercadoDelInmueble' => 'SUB-ValorDeMercadoDelInmueble');

    $validacionesh1 = array('ValorUnitarioDeTierraAplicableAlAvaluo' => 'SUB-ValorUnitarioDeTierraAplicableAlAvaluo');    

    $validacionesh11 = array('Calle' => 'nonEmptyString_100', 'Colonia' => 'catColonia', 'Alcaldia' => 'catDelegacion', 'CodigoPostal' => 'nonEmptyString_5', 'DescripcionDelPredio' => 'nonEmptyString_250', 'UsoDelSuelo' => 'nonEmptyString_50', 'UsoDelSuelo' => 'nonEmptyString_50', 'CUS' => 'decimalPositivo', 'Superficie' => 'decimalPositivo_222', 'Fre' => 'decimalPositivo', 'PrecioSolicitado' => 'decimalPositivo', 'FactorDeNegociacion' => 'decimalPositivo');
    $validacionesh11n5 = array('Telefono' => 'nonEmptyString_20', 'Informante' => 'nonEmptyString_100');
    $validacionesh11n18 = array('Valor' => 'decimalPositivo', 'Descripcion' => 'nonEmptyString_50');

    $validacionesh12 = array('ValorUnitarioDeTierraPromedio' => 'decimalPositivo', 'ValorUnitarioDeTierraHomologadoPromedio' => 'decimalPositivo', 'ValorUnitarioSinHomologarMinimo' => 'decimalPositivo', 'ValorUnitarioSinHomologarMaximo' => 'decimalPositivo', 'ValorUnitarioHomologadoMinimo' => 'decimalPositivo', 'ValorUnitarioHomologadoMaximo' => 'decimalPositivo');

    $validacionesh13 = array('TipoDeProductoInmobiliarioPropuesto' => 'nonEmptyString', 'NumeroDeUnidadesVendibles' => 'decimalPositivo', 'SuperficieVendiblePorUnidad' => 'decimalPositivo');
    $validacionesh134 = array('Calle' => 'nonEmptyString_50', 'Colonia' => 'catColonia', 'Alcaldia' => 'catDelegacion', 'CodigoPostal' => 'nonEmptyString_5', 'DescripcionDelComparable' => 'nonEmptyString_250', 'SuperficieVendiblePorUnidad' => 'decimalPositivo_222', 'PrecioSolicitado' => 'decimalPositivo', 'FactorDeNegociacion' => 'decimalPositivo');
    $validacionesh134n5 = array('Telefono' => 'nonEmptyString_20', 'Informante' => 'nonEmptyString_100');

    $validacionesh135 = array('ValorUnitarioPromedio' => 'decimalPositivo', 'ValorUnitarioHomologadoPromedio' => 'decimalPositivo', 'ValorUnitarioSinHomologarMinimo' => 'decimalPositivo', 'ValorUnitarioSinHomologarMaximo' => 'decimalPositivo', 'ValorUnitarioHomologadoMinimo' => 'decimalPositivo', 'ValorUnitarioHomologadoMaximo' => 'decimalPositivo', 'ValorUnitarioAplicableAlResidual' => 'decimalPositivo');

    $validacionesh136 = array('TotalDeIngresos' => 'decimalPositivo', 'TotalDeEgresos' => 'decimalPositivo', 'UtilidadPropuesta' => 'nonEmptyString', 'ValorUnitarioDeTierraResidual' => 'decimalPositivo');

    $validacionesh21 = array('Calle' => 'nonEmptyString_50', 'Colonia' => 'catColonia', 'Alcaldia' => 'catDelegacion', 'CodigoPostal' => 'nonEmptyString_5', 'DescripcionDelComparable' => 'nonEmptyString_250', 'SuperficieVendiblePorUnidad' => 'decimalPositivo_222', 'PrecioSolicitado' => 'decimalPositivo', 'FactorDeNegociacion' => 'decimalPositivo');

    $validacionesh21n5 = array('Telefono' => 'nonEmptyString_20', 'Informante' => 'nonEmptyString_100');

    $validacionesh21n10 = array('Region' => 'nullableRegionManzanaUp', 'Manzana' => 'nullableRegionManzanaUp', 'Lote' => 'nullableLote', 'Localidad' => 'nullableRegionManzanaUp');

    $validacionesh22 = array('ValorUnitarioPromedio' => 'decimalPositivo', 'ValorUnitarioHomologadoPromedio' => 'decimalPositivo', 'ValorUnitarioSinHomologarMinimo' => 'decimalPositivo', 'ValorUnitarioSinHomologarMaximo' => 'decimalPositivo', 'ValorUnitarioHomologadoMinimo' => 'decimalPositivo', 'ValorUnitarioHomologadoMaximo' => 'decimalPositivo', 'ValorUnitarioAplicableAlAvaluo' => 'decimalPositivo');

    $validacionesh41 = array('Calle' => 'nonEmptyString_50', 'Colonia' => 'catColonia', 'Alcaldia' => 'catDelegacion', 'CodigoPostal' => 'nonEmptyString_5', 'DescripcionDelComparable' => 'nonEmptyString_250', 'SuperficieVendiblePorUnidad' => 'decimalPositivo_222', 'PrecioSolicitado' => 'decimalPositivo', 'FactorDeNegociacion' => 'decimalPositivo');

    $validacionesh41n5 = array('Telefono' => 'nonEmptyString_20', 'Informante' => 'nonEmptyString_100');

    $validacionesh41n10 = array('Region' => 'nullableRegionManzanaUp', 'Manzana' => 'nullableRegionManzanaUp', 'Lote' => 'nullableLote', 'Localidad' => 'nullableRegionManzanaUp');

    $validacionesh42 = array('ValorUnitarioPromedio' => 'decimalPositivo', 'ValorUnitarioHomologadoPromedio' => 'decimalPositivo', 'ValorUnitarioSinHomologarMinimo' => 'decimalPositivo', 'ValorUnitarioSinHomologarMaximo' => 'decimalPositivo', 'ValorUnitarioHomologadoMinimo' => 'decimalPositivo', 'ValorUnitarioHomologadoMaximo' => 'decimalPositivo', 'ValorUnitarioAplicableAlAvaluo' => 'decimalPositivo');

    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data);

    $resValidaCalculo = valida_Calculos($data,'h');
    if($resValidaCalculo != "Correcto"){
        $errores[] = $resValidaCalculo;
    }

    if(count($data) > 1){
        $obligatoriosh = array();
        foreach($validacionesh as $etiqueta => $validacion){
            if(!isset($data[0][$etiqueta]) && in_array($etiqueta,$obligatoriosh)){
                $errores[] = "Falta ".$etiqueta." en Enfoque de Mercado";
            }else{
                if(isset($data[0][$etiqueta])){
                    $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                    }
                }                    
            }
        }
    
        foreach($validacionesh1 as $etiqueta => $validacion){
            if(!isset($data[0]['Terrenos'][$etiqueta])){
                $errores[] = "Falta ".$etiqueta." en Terrenos";
            }else{
                $resValidacion = define_validacion($validacion, $data[0]['Terrenos'][$etiqueta]);                
                if($resValidacion != 'correcto'){
                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                }                
            }
        }
    
        //print_r($data); exit();
        if(isset($data[0]['Terrenos']['TerrenosDirectos']['@attributes']) && $data[0]['Terrenos']['TerrenosDirectos']['@attributes']['id'] == 'h.1.1'){
            foreach($validacionesh11 as $etiqueta => $validacion){
                if(!isset($data[0]['Terrenos']['TerrenosDirectos'][$etiqueta])){
                    $errores[] = "Falta ".$etiqueta." en en Terrenos (TerrenosDirectos)";
                }else{
                    $resValidacion = define_validacion($validacion, $data[0]['Terrenos']['TerrenosDirectos'][$etiqueta]);                
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                    }                
                }
            }
    
            if(isset($data[0]['Terrenos']['TerrenosDirectos']['FuenteDeInformacion'])){
                foreach($validacionesh11n5 as $etiqueta => $validacion){
                    if(!isset($data[0]['Terrenos']['TerrenosDirectos']['FuenteDeInformacion'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en Terrenos (FuenteDeInformacion)";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['Terrenos']['TerrenosDirectos']['FuenteDeInformacion'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
            }
    
            if(isset($data[0]['Terrenos']['TerrenosDirectos']['Fot'])){
                foreach($validacionesh11n18 as $etiqueta => $validacion){
                    if(!isset($data[0]['Terrenos']['TerrenosDirectos']['Fot'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en Terrenos (Fot)";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['Terrenos']['TerrenosDirectos']['Fot'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
            }
        }
    
        if(isset($data[0]['Terrenos']['TerrenosDirectos'][0]['@attributes']) && $data[0]['Terrenos']['TerrenosDirectos'][0]['@attributes']['id'] == 'h.1.1'){
            foreach($data[0]['Terrenos']['TerrenosDirectos'] as $llavePrincipal => $elementoPrincipal){            
                //if(is_array($elementoPrincipal) && $elementoPrincipal['id'] != 'e.2.1'){
                        foreach($validacionesh11 as $etiqueta => $validacion){
                            if(!isset($elementoPrincipal[$etiqueta])){
                                $errores[] = "Falta ".$etiqueta." en Terrenos (TerrenosDirectos)";
                            }else{
                                $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                                if($resValidacion != 'correcto'){
                                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                }                
                            }
                        }
                //}
                
                if(isset($elementoPrincipal['FuenteDeInformacion'])){
    
                    foreach($validacionesh11n5 as $etiqueta => $validacion){
                        if(!isset($elementoPrincipal['FuenteDeInformacion'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en Terrenos (FuenteDeInformacion)";
                        }else{
                            $resValidacion = define_validacion($validacion, $elementoPrincipal['FuenteDeInformacion'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
    
                }
    
                if(isset($elementoPrincipal['Fot'])){
    
                    foreach($validacionesh11n18 as $etiqueta => $validacion){
                        if(!isset($elementoPrincipal['Fot'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en Terrenos (Fot)";
                        }else{
                            $resValidacion = define_validacion($validacion, $elementoPrincipal['Fot'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
    
                }
            }
        }
    
        if(isset($data[0]['Terrenos']['ConclusionesHomologacionTerrenos'])){
    
            foreach($validacionesh12 as $etiqueta => $validacion){
                if(!isset($data[0]['Terrenos']['ConclusionesHomologacionTerrenos'][$etiqueta])){
                    $errores[] = "Falta ".$etiqueta." en en Terrenos (ConclusionesHomologacionTerrenos)";
                }else{
                    $resValidacion = define_validacion($validacion, $data[0]['Terrenos']['ConclusionesHomologacionTerrenos'][$etiqueta]);                
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                    }                
                }
            }
    
        }else{
            $errores[] = "Falta ConclusionesHomologacionTerrenos en en Terrenos";
        }
    
        if(isset($data[0]['Terrenos']['TerrenosResidual']['@attributes']) && $data[0]['Terrenos']['TerrenosResidual']['@attributes']['id'] == 'h.1.3'){
            foreach($validacionesh13 as $etiqueta => $validacion){
                if(!isset($data[0]['Terrenos']['TerrenosResidual'][$etiqueta])){
                    $errores[] = "Falta ".$etiqueta." en en Terrenos (TerrenosResidual)";
                }else{
                    $resValidacion = define_validacion($validacion, $data[0]['Terrenos']['TerrenosResidual'][$etiqueta]);                
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                    }                
                }
            }
    
            if(isset($data[0]['Terrenos']['TerrenosResidual']['InvestigacionProductosComparables'])){
                $numeroInvestigacionProductosComparablesR = count($data[0]['Terrenos']['TerrenosResidual']['InvestigacionProductosComparables']);
                if($numeroInvestigacionProductosComparablesR < 4){
                    $errores[] = "Existen menos de 4 InvestigacionProductosComparables (TerrenosResidual)";
                }else{
                    foreach($data[0]['Terrenos']['TerrenosResidual']['InvestigacionProductosComparables'] as $llavePrincipal => $elementoPrincipal){
                        foreach($validacionesh134 as $etiqueta => $validacion){
                            if(!isset($elementoPrincipal[$etiqueta])){
                                $errores[] = "Falta ".$etiqueta." en en TerrenosResidual (InvestigacionProductosComparables)";
                            }else{
                                $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                                if($resValidacion != 'correcto'){
                                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                }                
                            }
                        }
                
                        if(isset($elementoPrincipal['FuenteDeInformacion'])){
                            foreach($validacionesh134n5 as $etiqueta => $validacion){
                                if(!isset($elementoPrincipal['FuenteDeInformacion'][$etiqueta])){
                                    $errores[] = "Falta ".$etiqueta." en en InvestigacionProductosComparables (FuenteDeInformacion)";
                                }else{
                                    $resValidacion = define_validacion($validacion, $elementoPrincipal['FuenteDeInformacion'][$etiqueta]);                
                                    if($resValidacion != 'correcto'){
                                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                    }                
                                }
                            }
                        }
                    }
                    
                    /******************************************************* */
                    /*foreach($validacionesh134 as $etiqueta => $validacion){
                        if(!isset($data[0]['Terrenos']['TerrenosResidual']['InvestigacionProductosComparables'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en en TerrenosResidual (InvestigacionProductosComparables)";
                        }else{
                            $resValidacion = define_validacion($validacion, $data[0]['Terrenos']['TerrenosResidual']['InvestigacionProductosComparables'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
            
                    if(isset($data[0]['Terrenos']['TerrenosResidual']['InvestigacionProductosComparables']['FuenteDeInformacion'])){
                        foreach($validacionesh134n5 as $etiqueta => $validacion){
                            if(!isset($data[0]['Terrenos']['TerrenosResidual']['InvestigacionProductosComparables']['FuenteDeInformacion'][$etiqueta])){
                                $errores[] = "Falta ".$etiqueta." en en InvestigacionProductosComparables (FuenteDeInformacion)";
                            }else{
                                $resValidacion = define_validacion($validacion, $data[0]['Terrenos']['TerrenosResidual']['InvestigacionProductosComparables']['FuenteDeInformacion'][$etiqueta]);                
                                if($resValidacion != 'correcto'){
                                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                }                
                            }
                        }
                    }*/
                    /******************************************************* */
                    
                }
                
            }
    
            if(isset($data[0]['Terrenos']['TerrenosResidual']['ConclusionesHomologacionCompResiduales'])){
                foreach($validacionesh135 as $etiqueta => $validacion){
                    if(!isset($data[0]['Terrenos']['TerrenosResidual']['ConclusionesHomologacionCompResiduales'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en TerrenosResidual (ConclusionesHomologacionCompResiduales)";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['Terrenos']['TerrenosResidual']['ConclusionesHomologacionCompResiduales'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
            }
    
            if(isset($data[0]['Terrenos']['TerrenosResidual']['AnalisisResidual'])){
                foreach($validacionesh136 as $etiqueta => $validacion){
                    if(!isset($data[0]['Terrenos']['TerrenosResidual']['AnalisisResidual'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en TerrenosResidual (AnalisisResidual)";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['Terrenos']['TerrenosResidual']['AnalisisResidual'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
            }
        }
        
        //print_r($data[0]['ConstruccionesEnVenta']); exit();
        if(isset($data[0]['ConstruccionesEnVenta']['@attributes']) && $data[0]['ConstruccionesEnVenta']['@attributes']['id'] == 'h.2'){
            $numeroInvestigacionProductosComparables = count($data[0]['ConstruccionesEnVenta']['InvestigacionProductosComparables']);
            if($numeroInvestigacionProductosComparables < 4){
                $errores[] = "Existen menos de 4 InvestigacionProductosComparables";
            }else{
                //print_r($data[0]['ConstruccionesEnVenta']['InvestigacionProductosComparables']); exit();
                foreach($data[0]['ConstruccionesEnVenta']['InvestigacionProductosComparables'] as $llavePrincipal => $elementoPrincipal){            
                    
                    foreach($validacionesh21 as $etiqueta => $validacion){
                        if(!isset($elementoPrincipal[$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en ConstruccionesEnVenta (InvestigacionProductosComparables)";
                        }else{
                            $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
                    
                    foreach($validacionesh21n5 as $etiqueta => $validacion){
                        if(!isset($elementoPrincipal['FuenteDeInformacion'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en InvestigacionProductosComparables (FuenteDeInformacion)";
                        }else{
                            $resValidacion = define_validacion($validacion, $elementoPrincipal['FuenteDeInformacion'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
    
                    foreach($validacionesh21n10 as $etiqueta => $validacion){
                        if(!isset($elementoPrincipal['CuentaCatastral'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en InvestigacionProductosComparables (CuentaCatastral)";
                        }else{                                             
                            $resValidacion = define_validacion($validacion, $elementoPrincipal['CuentaCatastral'][$etiqueta]);
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }    
                        }
                    }                
                }
                //print_r($data[0]['ConstruccionesEnVenta']['ConclusionesHomologacionConstruccionesEnVenta']); exit();
                foreach($validacionesh22 as $etiqueta => $validacion){
                    if(!isset($data[0]['ConstruccionesEnVenta']['ConclusionesHomologacionConstruccionesEnVenta'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en ConstruccionesEnVenta (ConclusionesHomologacionConstruccionesEnVenta)";
                    }else{                                             
                        $resValidacion = define_validacion($validacion, $data[0]['ConstruccionesEnVenta']['ConclusionesHomologacionConstruccionesEnVenta'][$etiqueta]);
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }    
                    }
                }
    
            }
        }
    
        if(isset($data[0]['ConstruccionesEnRenta']['@attributes']) && $data[0]['ConstruccionesEnRenta']['@attributes']['id'] == 'h.4'){
           
            if(isset($data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables']['@attributes']) && $data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables']['@attributes']['id'] == 'h.4.1'){
                foreach($validacionesh41 as $etiqueta => $validacion){
                    if(!isset($data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en ConstruccionesEnRenta (InvestigacionProductosComparables)";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
    
                foreach($validacionesh41n5 as $etiqueta => $validacion){
                    if(!isset($data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables']['FuenteDeInformacion'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en InvestigacionProductosComparables (FuenteDeInformacion)";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables']['FuenteDeInformacion'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
    
                foreach($validacionesh41n10 as $etiqueta => $validacion){
                    if(!isset($data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables']['CuentaCatastral'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en InvestigacionProductosComparables (CuentaCatastral)";
                    }else{
                        $resValidacion = define_validacion($validacion, $data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables']['CuentaCatastral'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
            }
    
            if(isset($data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables'][0]['@attributes']) && $data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables'][0]['@attributes']['id'] == 'h.4.1'){
                
                foreach($data[0]['ConstruccionesEnRenta']['InvestigacionProductosComparables'] as $llavePrincipal => $elementoPrincipal){
                    
                    foreach($validacionesh41 as $etiqueta => $validacion){
                        if(!isset($elementoPrincipal[$etiqueta]) || is_array($elementoPrincipal[$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en en ConstruccionesEnRenta (InvestigacionProductosComparables)";
                        }else{
                            $resValidacion = define_validacion($validacion, $elementoPrincipal[$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
    
                    foreach($validacionesh41n5 as $etiqueta => $validacion){
                        if(!isset($elementoPrincipal['FuenteDeInformacion'][$etiqueta])){
                            $errores[] = "Falta ".$etiqueta." en en InvestigacionProductosComparables (FuenteDeInformacion)";
                        }else{
                            $resValidacion = define_validacion($validacion, $elementoPrincipal['FuenteDeInformacion'][$etiqueta]);                
                            if($resValidacion != 'correcto'){
                                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                            }                
                        }
                    }
                    //echo "ENTRE AQUI "; print_r($elementoPrincipal['CuentaCatastral']); exit();
                    if(isset($elementoPrincipal['CuentaCatastral'])){
                        foreach($validacionesh41n10 as $etiqueta => $validacion){
                            //echo $elementoPrincipal['CuentaCatastral'][$etiqueta]; echo "\n";
                            if(!isset($elementoPrincipal['CuentaCatastral'][$etiqueta])){
                                $errores[] = "Falta ".$etiqueta." en en InvestigacionProductosComparables (CuentaCatastral)";
                            }else{
                                $resValidacion = define_validacion($validacion, $elementoPrincipal['CuentaCatastral'][$etiqueta]);                
                                if($resValidacion != 'correcto'){
                                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                                }                
                            }
                        }
                    }                
    
                }
                
            }
    
            foreach($validacionesh42 as $etiqueta => $validacion){
                if(!isset($data[0]['ConstruccionesEnRenta']['ConclusionesHomologacionConstruccionesEnVenta'][$etiqueta])){
                    $errores[] = "Falta ".$etiqueta." en en ConstruccionesEnRenta (ConclusionesHomologacionConstruccionesEnVenta)";
                }else{
                    $resValidacion = define_validacion($validacion, $data[0]['ConstruccionesEnRenta']['ConclusionesHomologacionConstruccionesEnVenta'][$etiqueta]);                
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                    }                
                }
            }        
    
        }

    }
    
    return $errores;
}

    function valida_AvaluoEnfoqueCostosComercial($data, $elementoPrincipal, $datad13, $datae2, $dataf12, $dataf14){

        $validacionesi = array('ImporteTotalDelEnfoqueDeCostos' => 'SUB-ImporteTotalDelEnfoqueDeCostos');    
        $errores = array();       
        $data = array_map("convierte_a_arreglo",$data);

        $resValidaCalculo = valida_Calculos_i($data,'i', $datad13, $datae2, $dataf12, $dataf14);
        if($resValidaCalculo != "Correcto"){
            $errores[] = $resValidaCalculo;
        }
        foreach($validacionesi as $etiqueta => $validacion){
            
            if(!isset($data[0][$etiqueta])){
                $errores[] = "Falta ".$etiqueta." en en EnfoqueDeCostos";
            }else{
                $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                                
                if($resValidacion != 'correcto'){
                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                }                
            }
        }
        return $errores;
    }

function valida_AvaluoEnfoqueCostosCatastral($data, $elementoPrincipal, $datae23, $datae27, $datab6, $datad6, $datad13, $existef9, $existef10, $existef11){
    
    $validacionesj = array('ImporteInstalacionesEspeciales' => 'SUB-ImporteInstalacionesEspeciales', 'ImporteTotalValorCatastral' => 'SUB-ImporteTotalValorCatastral', 'AvanceDeObra' => 'porcentaje_10', 'ImporteTotalValorCatastralObraEnProceso' => 'SUB-ImporteTotalValorCatastralObraEnProceso');        

    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data);

    $resValidaCalculo = valida_Calculos_j($data,'j', $datae23, $datae27, $datab6, $datad6, $datad13, $existef9, $existef10, $existef11);
    if($resValidaCalculo != "Correcto"){
        $errores[] = $resValidaCalculo;
    }
    //print_r($data); exit();
    foreach($validacionesj as $etiqueta => $validacion){
        if(!isset($data[0][$etiqueta])){
            $errores[] = "Falta ".$etiqueta." en en EnfoqueDeCostos";
        }else{
            $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                
            if($resValidacion != 'correcto'){
                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
            }                
        }
    }
    return $errores;
}

function valida_AvaluoEnfoqueIngresos($data, $elementoPrincipal){
    if($elementoPrincipal == '//Comercial'){
        $validacionesk = array('RentaBrutaMensual' => 'nullableDecimalPositivo', 'ProductoLiquidoAnual' => 'nullableDecimalPositivo', 'TasaDeCapitalizacionAplicable' => 'decimalPositivo', 'ImporteEnfoqueDeIngresos' => 'SUB-ImporteEnfoqueDeIngresos');
        $validacionesk2 = array('Vacios' => 'decimalPositivo', 'ImpuestoPredial' => 'decimalPositivo', 'ServicioDeAgua' => 'decimalPositivo', 'ConservacionYMantenimiento' => 'decimalPositivo', 'ServicioEnergiaElectrica' => 'decimalPositivo', 'Administracion' => 'decimalPositivo', 'Seguros' => 'decimalPositivo', 'DepreciacionFiscal' => 'decimalPositivo', 'Otros' => 'decimalPositivo', 'DeduccionesFiscales' => 'decimalPositivo', 'ImpuestoSobreLaRenta' => 'decimalPositivo', 'DeduccionesMensuales' => 'decimalPositivo', 'PorcentajeDeduccionesMensuales' => 'porcentaje_04');
        
    }

    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data);

    $resValidaCalculo = valida_Calculos($data,'k');
    if($resValidaCalculo != "Correcto"){
        $errores[] = $resValidaCalculo;
    } 
    
    foreach($validacionesk as $etiqueta => $validacion){
        //print_r($data[0][$etiqueta]); echo "\n";
        if(!isset($data[0][$etiqueta])){
            $errores[] = "Falta ".$etiqueta." en en EnfoqueDeIngresos";
        }else{
            $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                
            if($resValidacion != 'correcto'){
                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
            }                
        }
    }

    foreach($validacionesk2 as $etiqueta => $validacion){
        if(!isset($data[0]['Deducciones'][$etiqueta])){
            $errores[] = "Falta ".$etiqueta." en EnfoqueDeIngresos (Deducciones)";
        }else{
            
            $resValidacion = define_validacion($validacion, $data[0]['Deducciones'][$etiqueta]);                
            if($resValidacion != 'correcto'){
                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
            }                
        }
    }
    return $errores;
    
}
    
function valida_AvaluoConsideracionesPreviasALaConclusion($data, $elementoPrincipal){
    $validacionesn = array('ConsideracionesPreviasALaConclusion' => 'string_2000');

    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data);

    foreach($validacionesn as $etiqueta => $validacion){
        
        if(!isset($data[0][$etiqueta])){
            $errores[] = "Falta ".$etiqueta." en ConsideracionesPreviasALaConclusion";
        }else{
            $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                
            if($resValidacion != 'correcto'){
                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
            }                
        }
    }
    return $errores;
}

function valida_AvaluoConclusionDelAvaluoComercial($data, $elementoPrincipal){
    $validacioneso = array('ValorComercialDelInmueble' => 'decimalPositivo');
    //print_r($data); exit();
    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data);
    
    foreach($validacioneso as $etiqueta => $validacion){
        
        if(!isset($data[0][$etiqueta])){
            $errores[] = "Falta ".$etiqueta." en ConclusionDelAvaluo";
        }else{
            $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                
            if($resValidacion != 'correcto'){
                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
            }                
        }
    }
    return $errores;
}

function valida_AvaluoConclusionDelAvaluoCatastral($data, $elementoPrincipal){
    $validacioneso = array('ValorCatastralDelInmueble' => 'decimalPositivo');

    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data);
    //echo $data[0]['ValorCatastralDelInmueble']; exit();
    foreach($validacioneso as $etiqueta => $validacion){
        
        if(!isset($data[0][$etiqueta])){
            $errores[] = "Falta ".$etiqueta." en ConclusionDelAvaluo";
        }else{
            $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                
            if($resValidacion != 'correcto'){
                $errores[] = "El campo ".$etiqueta." ".$resValidacion;
            }                
        }
    }
    return $errores;
}

function valida_AvaluoValorReferido($data, $elementoPrincipal, $datao1){
    $validacionesp = array('FechaDeValorReferido' => 'nullableDate', 'IndiceAntiguo' => 'nullableDecimal', 'IndiceActual' => 'nullableDecimalPositivo', 'FactorDeConversion' => 'nullableDecimalPositivo', 'ValorReferido' => 'nullableDecimalPositivo');

    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data);
    
    $resValidaCalculo = valida_Calculos($data,'p', $datao1);
    if($resValidaCalculo != "Correcto"){
        $errores[] = $resValidaCalculo;
    } 

    unset($data[0]['@attributes']);
    //echo count($data[0]); exit();
    if(count($data[0]) == 0){

    }else{
        $validado = false;
        $fechaDeValorReferido = $data[0]['FechaDeValorReferido'];
        $valorReferido = $data[0]['ValorReferido'];

        if($fechaDeValorReferido >= 0 && $valorReferido >= 0){
            $validado = true;
        }

        if(trim($fechaDeValorReferido) != '' && trim($valorReferido) != ''){
            $validado = true;
        }

        if($validado == false){
            $errores[] = "Valor referido (p.5 y p.1)";
        }

        foreach($validacionesp as $etiqueta => $validacion){
        
            if(!isset($data[0][$etiqueta])){
                $errores[] = "Falta ".$etiqueta." en ValorReferido";
            }else{
                $resValidacion = define_validacion($validacion, $data[0][$etiqueta]);                
                if($resValidacion != 'correcto'){
                    $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                }                
            }
        }
    }    
    
    return $errores;
}


function valida_AvaluoAnexoFotografico($data, $elementoPrincipal){ //print_r($data); exit();
    if($elementoPrincipal == '//Comercial'){
        $validacionesq11 = array('Region' => 'regionManzanaUp', 'Manzana' => 'regionManzanaUp', 'Lote' => 'lote', 'Localidad' => 'regionManzanaUp');
        $validacionesq12 = array('Foto' => 'base64Binary', 'InteriorOExterior' => 'catTipoFotoInmueble');
        $validacionesq2n1 = array('Region' => 'nullableRegionManzanaUp', 'Manzana' => 'nullableRegionManzanaUp', 'Lote' => 'nullableLote', 'Localidad' => 'nullableRegionManzanaUp');
        $validacionesq2n2 = array('Foto' => 'base64Binary', 'InteriorOExterior' => 'catTipoFotoInmueble');
        $validacionesq3n1 = array('Region' => 'nullableRegionManzanaUp', 'Manzana' => 'nullableRegionManzanaUp', 'Lote' => 'nullableLote', 'Localidad' => 'nullableRegionManzanaUp');
        $validacionesq3n2 = array('Foto' => 'base64Binary', 'InteriorOExterior' => 'catTipoFotoInmueble');
    }else{
        $validacionesq11 = array('Region' => 'regionManzanaUp', 'Manzana' => 'regionManzanaUp', 'Lote' => 'lote', 'Localidad' => 'regionManzanaUp');
        $validacionesq12 = array('Foto' => 'base64Binary', 'InteriorOExterior' => 'catTipoFotoInmueble');        
    }

    $errores = array(); 
    $data = array_map("convierte_a_arreglo",$data);
    //print_r($data); exit();
    foreach($validacionesq11 as $etiquetaCatastral => $validacionCatastral){
        if(!isset($data[0]['Sujeto']['CuentaCatastral'][$etiquetaCatastral])){
            $errores[] = "Falta ".$etiquetaCatastral." en Sujeto (CuentaCatastral)";
        }else{
            
            $resValidacionCuentaCatastral = define_validacion($validacionCatastral, $data[0]['Sujeto']['CuentaCatastral'][$etiquetaCatastral]);                
            if($resValidacionCuentaCatastral != 'correcto'){
                $errores[] = "El campo q.1.1 ".$etiquetaCatastral." ".$resValidacionCuentaCatastral;
            }
        }
    }

    if(count($data[0]['Sujeto']['FotosInmuebleAvaluo']) < 8){
        $errores[] = "Existen menos de 8 FotosInmuebleAvaluo";
    }else{ 
        foreach($data[0]['Sujeto']['FotosInmuebleAvaluo'] as $llavePrincipal => $elementoPrincipal){

            foreach($validacionesq12 as $etiqueta => $validacion){
                if(!isset($elementoPrincipal[$etiqueta]) || isset($elementoPrincipal[$etiqueta]['@attributes'])){
                    $errores[] = "q.1.2 - Falta ".$etiqueta." en Sujeto (FotosInmuebleAvaluo)";
                }else{
                    
                    $resValidacion = define_validacion($validacion, trim($elementoPrincipal[$etiqueta]));                
                    if($resValidacion != 'correcto'){ //echo "ENTRE EN Q12 ".$elementoPrincipal[$etiqueta]; exit();
                        
                        $errores[] = "El campo q.1.2 ".$etiqueta." ".$resValidacion;
                    }
                }
            }
        }
        
    }
    
    if(isset($data[0]['ComparableRentas'])){

        if(isset($data[0]['ComparableRentas']['@attributes']) && $data[0]['ComparableRentas']['@attributes']['id'] == 'q.2'){

            foreach($validacionesq2n1 as $etiqueta => $validacion){
                if(!isset($data[0]['ComparableRentas']['CuentaCatastral'][$etiqueta])){
                    $errores[] = "Falta ".$etiqueta." en en ComparableRentas (CuentaCatastral)";
                }else{
                    $resValidacion = define_validacion($validacion, $data[0]['ComparableRentas']['CuentaCatastral'][$etiqueta]);                
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo q.2.n.1".$etiqueta." ".$resValidacion;
                    }                
                }
            }

            foreach($validacionesq2n2 as $etiqueta => $validacion){
                if(!isset($data[0]['ComparableRentas']['FotosInmuebleAvaluo'][$etiqueta]) || isset($data[0]['ComparableRentas']['FotosInmuebleAvaluo'][$etiqueta]['@attributes'])){
                    $errores[] = "q.2.n.2 Falta ".$etiqueta." en ComparableRentas (FotosInmuebleAvaluo)";
                }else{
                    //print_r($data[0]['ComparableRentas']['FotosInmuebleAvaluo']); exit();
                    $resValidacion = define_validacion($validacion, trim($data[0]['ComparableRentas']['FotosInmuebleAvaluo'][$etiqueta]));                
                    if($resValidacion != 'correcto'){
                        if(isset($data[0]['ComparableRentas']['FotosInmuebleAvaluo']['@attributes']['id'])){
                            $idCampo = $data[0]['ComparableRentas']['FotosInmuebleAvaluo']['@attributes']['id'];
                        }else{
                            $idCampo = '';
                        }
                        $errores[] = "El q.2.n.2 ".$idCampo." ".$etiqueta." ".$resValidacion;
                    }
                }
            }

        }
    
        if(isset($data[0]['ComparableRentas'][0]['@attributes']) && $data[0]['ComparableRentas'][0]['@attributes']['id'] == 'q.2'){
            foreach($data[0]['ComparableRentas'] as $llavePrincipal => $elementoPrincipal){            
                
                foreach($validacionesq2n1 as $etiqueta => $validacion){
                    if(!isset($elementoPrincipal['CuentaCatastral'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en ComparableRentas (CuentaCatastral)";
                    }else{
                        $resValidacion = define_validacion($validacion, $elementoPrincipal['CuentaCatastral'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo q.2.n.1 ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
    
                foreach($validacionesq2n2 as $etiqueta => $validacion){
                    if(!isset($elementoPrincipal['FotosInmuebleAvaluo'][$etiqueta]) || isset($elementoPrincipal['FotosInmuebleAvaluo'][$etiqueta]['@attributes'])){
                        $errores[] = "Falta ".$etiqueta." en ComparableRentas (FotosInmuebleAvaluo)";
                    }else{
                        
                        $resValidacion = define_validacion($validacion, $elementoPrincipal['FotosInmuebleAvaluo'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo q.2.n.2 ".$etiqueta." ".$resValidacion;
                        }
                    }
                }            
            }
        }

    }


    if(isset($data[0]['ComparableVentas'])){

        if(isset($data[0]['ComparableVentas']['@attributes']) && $data[0]['ComparableVentas']['@attributes']['id'] == 'q.2'){

            foreach($validacionesq3n1 as $etiqueta => $validacion){
                if(!isset($data[0]['ComparableVentas']['CuentaCatastral'][$etiqueta])){
                    $errores[] = "Falta ".$etiqueta." en en ComparableVentas (CuentaCatastral)";
                }else{
                    $resValidacion = define_validacion($validacion, $data[0]['ComparableVentas']['CuentaCatastral'][$etiqueta]);                
                    if($resValidacion != 'correcto'){
                        $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                    }                
                }
            }

            foreach($validacionesq3n2 as $etiqueta => $validacion){
                if(!isset($data[0]['ComparableVentas']['FotosInmuebleAvaluo'][$etiqueta]) || isset($data[0]['ComparableVentas']['FotosInmuebleAvaluo'][$etiqueta]['@attributes'])){
                    $errores[] = "Falta ".$etiqueta." en ComparableVentas (FotosInmuebleAvaluo)";
                }else{
                    
                    $resValidacion = define_validacion($validacion, trim($data[0]['ComparableVentas']['FotosInmuebleAvaluo'][$etiqueta]));                
                    if($resValidacion != 'correcto'){
                        
                        $errores[] = "El campo q.3.n.2 ".$etiqueta." ".$resValidacion;
                        
                    }
                }
            }

        }
    
        if(isset($data[0]['ComparableVentas'][0]['@attributes']) && $data[0]['ComparableVentas'][0]['@attributes']['id'] == 'q.2'){
            foreach($data[0]['ComparableVentas'] as $llavePrincipal => $elementoPrincipal){            
                
                foreach($validacionesq3n1 as $etiqueta => $validacion){
                    if(!isset($elementoPrincipal['CuentaCatastral'][$etiqueta])){
                        $errores[] = "Falta ".$etiqueta." en en ComparableVentas (CuentaCatastral)";
                    }else{
                        $resValidacion = define_validacion($validacion, $elementoPrincipal['CuentaCatastral'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }                
                    }
                }
    
                foreach($validacionesq3n2 as $etiqueta => $validacion){
                    if(!isset($elementoPrincipal['FotosInmuebleAvaluo'][$etiqueta]) || isset($elementoPrincipal['FotosInmuebleAvaluo'][$etiqueta]['@attributes'])){
                        $errores[] = "Falta ".$etiqueta." en ComparableVentas (FotosInmuebleAvaluo)";
                    }else{
                        
                        $resValidacion = define_validacion($validacion, $elementoPrincipal['FotosInmuebleAvaluo'][$etiqueta]);                
                        if($resValidacion != 'correcto'){
                            $errores[] = "El campo ".$etiqueta." ".$resValidacion;
                        }
                    }
                }            
            }
        }

    }

    return $errores;

}

function existeCatUsoEjercicio($codUso,$fecha){ //echo $codUso." ".$fecha; exit();
    if(estaEnCatClaseUso($codUso) == true && estaEnCatEjercicio($fecha) == true){
        $modelDatosExtrasAvaluo = new DatosExtrasAvaluo();
        //$idUsoEjercicio = $modelDatosExtrasAvaluo->solicitarObtenerIdUsosByCodeAndAno($fecha, $codUso); //COMENTADO PORQUE NO FUNCIONA EL PKG CON ESTA INFO
        return true;
    }else{
        $idUsoEjercicio = null;
        return false;
    }
}

function existeCatRangoNivelesEjercicio($codRangoNiveles,$fecha){
    if(estaEnCatClaseRangoNiveles($codRangoNiveles) == true && estaEnCatEjercicio($fecha) == true){
        $modelDatosExtrasAvaluo = new DatosExtrasAvaluo();
        $res = $modelDatosExtrasAvaluo->SolicitarObtenerIdRangoNivelesByCodeAndAno($fecha,$codRangoNiveles);
        return  $res;
        //return true;
    }else{        
        return false;
    }
}

function existeCatClaseEjercicio($codClase, $fecha){
    if(estaEnCatClaseConstruccion($codClase) == true && estaEnCatEjercicio($fecha) == true){
        $modelDatosExtrasAvaluo = new DatosExtrasAvaluo();
        //return $modelFis->solicitarObtenerIdClasesByCodeAndAno($fecha,intval($codRangoNiveles)); //COMENTADO PORQUE ES LO MISMO QUE EL DE ABAJO
        return $modelDatosExtrasAvaluo->SolicitarObtenerIdClasesByCodeAndAno($fecha,intval($codClase));
    }else{
        return false;
    }
}

function estaEnCatClaseConstruccion($elem){
    $resul = false;
    if(val_cat_clases_construccion($elem) == 'correcto'){
        $resul = true;
    }
    return $resul;
}

function estaEnCatClaseUso($elem){
    $resul = false;
    if(val_usos_construcciones($elem) == 'correcto'){
        $resul = true;
    }
    return $resul;
}

function estaEnCatEjercicio($date){
    $resultado = false;
    $date = strtotime(formateaFecha($date));
    $infoEjercicios = infoCat('FIS_EJERCICIO');
    foreach($infoEjercicios as $elemento){
        foreach($elemento as $id => $campo){ 
            if($id == 'FECHAINICIO'){ 
                $fechainicio = strtotime(formateaFecha($campo));
            }
            if($id == 'FECHAFIN'){ 
                $fechafin = strtotime(formateaFecha($campo));
            }            
        }
        if(isset($fechainicio) && isset($fechafin) && $date >= $fechainicio && $date <= $fechafin){
            //echo $date." >= ".$fechainicio." && ".$date." <= ".$fechafin."\n"; exit();
            $resultado = true;
        }
    }
    return $resultado;   
}

function estaEnCatClaseRangoNiveles($codRangoNiveles){
    $resultado = false;    
    $catRangoNiveles = infoCat('FIS_CATRANGONIVELES',"CODRANGONIVELES = '".$codRangoNiveles."'");
    if(count($catRangoNiveles) > 0){
        $resultado = true;
    }
    return $resultado;
}

function validarCatUsoClase($codUso, $codClase, $vidaUtilTotalDelTipo, $fecha){
    $modelFis = new Fis();
    //$idClaseEjercicio = existeCatClaseEjercicio($codClase, $fecha);    
    //$idusoEjercicio = existeCatUsoEjercicio($codUso, $fecha);
    $estaEnEjercicio = estaEnCatEjercicio($fecha);

    //if($idClaseEjercicio != false && $idusoEjercicio != false && $estaEnEjercicio != false){
    if($estaEnEjercicio != false){
        if ($codClase == "U"){
            return true;
        }else{
            //echo "INFOA ENVIAR 1 ".$fecha." ".$codUso; exit();
            //$idUsoEjercicio = $modelFis->solicitarObtenerIdUsosByCodeAndAno($fecha,$codUso);
            if (comprobarEdadUtilTipo($codUso, $codClase, $vidaUtilTotalDelTipo) == false){ //Si hay errores devolver el mensaje de error
                return false;
            }else{
                return true;
            }
        }
    }else{
        return false;
    }
    
}

function validarCatUsoClase5($codUso, $codClase, $vidaUtilTotalDelTipo, $fecha){
    $modelFis = new Fis();    
    $estaEnEjercicio = estaEnCatEjercicio($fecha);

    if($estaEnEjercicio != false){
        if ($codClase == "U"){
            return true;
        }else{            
            if (comprobarEdadUtilTipo($codUso, $codClase, $vidaUtilTotalDelTipo) == false){ //Si hay errores devolver el mensaje de error
                return false;
            }else{
                return true;
            }
        }
    }else{
        return false;
    }
    
}

function comprobarEdadUtilTipo($idUsoEjercicio, $idClaseEjercicio, $vidaUtilTotalDelTipo){
    $modelDatosExtrasAvaluo = new DatosExtrasAvaluo();
    $dseCatClaseUso = $modelDatosExtrasAvaluo->select_catClaseUsoId_p($idUsoEjercicio, $idClaseEjercicio);
    if(count($dseCatClaseUso) > 0){
        //print_r($dseCatClaseUso); exit();
        if($dseCatClaseUso[0]['EDADUTIL'] == $vidaUtilTotalDelTipo){
            return true;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

function formateaFecha($date){
    //$fecha = new Carbon($date);
    //return $fecha->format('Y-m-d');    
    return str_replace('/','-',$date);
}


function infoCat($cat, $where = false){
    $arrRes = array();
    if($where != false){
        $query = "SELECT * FROM $cat WHERE $where";
    }else{
        $query = "SELECT * FROM $cat";
    }    
    list($usu,$nombre) = explode('_',$cat);
    $conn = oci_connect($usu, env("DB_PASSWORD"), env("DB_TNS"));
    oci_execute(oci_parse($conn,"ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'"));
    oci_execute(oci_parse($conn,"ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD'"));
    $sqlcadena = oci_parse($conn, $query);
    oci_execute($sqlcadena);

    while (($fila = oci_fetch_array($sqlcadena, OCI_ASSOC+OCI_RETURN_NULLS)) != false){
        $arrRes[] = $fila;            
    }
    oci_free_statement($sqlcadena);
    oci_close($conn);
    
    return $arrRes;
}


function esFechaValida($fecha){
    try{
        list($anio,$mes,$dia) = explode('-',$fecha);
        if(checkdate($mes,$dia,$anio) == true){
            return true;
        }else{
            return false;
        }
    }catch (\Throwable $th) {
        //Log::info($th);
        error_log($th);
        return false;
    }  
}

function darFormatoFechaXML($fecha){
    list($anio,$mes,$dia) = explode('-',$fecha);
    $fechaXML = $dia."/".$mes."/".$anio;
    return $fechaXML;
}

function existeClaseUsoEjercicio($idClaseejercicio, $idUsoejercicio){
    return true;
}



function tofloat($num) {
    $dotPos = strrpos($num, '.');
    $commaPos = strrpos($num, ',');
    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
  
    if (!$sep) {
        return floatval(preg_replace("/[^0-9]/", "", $num));
    }

    return floatval(
        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
        preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
    );
}

function getPropositoAvaluo($id){
    $arr = array('1'=>'Establecer la base gravable para el pago de Impuesto sobre adquisición de inmuebles.',
                '2'=>'Establecer la base gravable para el pago de Impuesto predial.',
                '3'=>'Establecer la base gravable para el pago de derechos y contribuciones inmobiliarias.');
    return $arr[$id];
}

function getTipoDeInmueble($id){
    $arr = array('1'=>'BODEGA',
                '2'=>'CAJON DE ESTACIONAMIENTO',
                '3'=>'CASA HABITACION',
                '4'=>'CASA HABITACION EN CONDOMINIO',
                '5'=>'CENTRO COMERCIAL',
                '6'=>'DEPARTAMENTO EN CONDOMINIO',
                '7'=>'EDIFICIO DE DEPARTAMENTOS',
                '8'=>'EDIFICIO DE ESTACIONAMIENTO',
                '9'=>'EDIFICIO DE PRODUCTOS',
                '10'=>'ESCUELA',
                '11'=>'HOSPITAL',
                '12'=>'HOTEL',
                '13'=>'LOCAL COMERCIAL',
                '14'=>'NAVE INDUSTRIAL',
                '15'=>'OFICINA AISLADA',
                '16'=>'OFICINA EN CONDOMINIO',
                '17'=>'TERRENO CON USO COMERCIAL',
                '18'=>'TERRENO HABITACIONAL',
                '19'=>'');
    return $arr[$id];
}

?>