<html>

    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <style>
            @page { margin: 120px 50px 80px 50px; font-family: Arial, Helvetica, sans-serif!important; font-size: 9px;} 
            #header { position: fixed; left: 0px; top: -75px; right: 0px; height: 70px; text-align: center;} 
            #footer { position: fixed; left: 0px; bottom: -25px; right: 0px; height: 40px; text-align: center; font-size: 16px;} 
            #footer .page:after { content: counter(page, decimal); } 
            .grises{color: #8D8D8D;}
            .negritas{font-weight: bolder;}
            
            .tabla_cabeza_gris{width: 100%; border-collapse: collapse!important; margin-top: 2%;}
                .tabla_cabeza_gris>thead>tr>th, .tabla_cabeza_gris>tbody>tr>td {border: 1px solid #000; padding: 2px;}
                .tabla_cabeza_gris>thead{background-color: #D3D3D3; font-size: 10px!important;}

            .tabla_gris_valor{width: 100%; margin-top: 2%; margin-bottom: 2%; border-collapse: separate; border-spacing: 10px 5px;}
                .tabla_gris_valor>thead>tr>th{background-color: #D3D3D3; font-size: 10px!important; text-align: right; padding: 4px; font-size: 12px;}

            .tabla_gris_valor_no_bold{width: 100%; margin-top: 2%; margin-bottom: 2%; border-collapse: separate; border-spacing: 10px 5px;}
                .tabla_gris_valor_no_bold>thead>tr>th{background-color: #D3D3D3; font-size: 10px!important; text-align: left; padding: 4px; font-size: 12px; font-weight: lighter;}

            .tabla_doble{width: 80%; border-collapse: collapse!important;}
                 .tabla_doble>thead>tr>th{border-bottom: 2px solid #000;}
                 .tabla_doble>tfoot>tr>td{border-top: 2px solid #000;}
            
            .centrado{text-align: center;}
            .pleca_verde{background-color: #00A346; color: #fff; border: 0px; text-align: right; margin: 2% 0 2% 0; padding: 5px; font-size: 15px;}
            .pleca_gris{background-color: #5D6D7E; color: #fff; border: 0px; text-align: right; margin: 15px 0 5px 0; padding: 5px; font-size: 13px;}
            .letras_pequenas{font-weight: lighter; font-size: 10px;}
            .subtitulo_anexo_fotografico{font-size: 12px; margin-top: 15px; margin-bottom: 5px;}
            .fotos{width: 90%;}
            .container2{text-align: center; height: 30px;}
            .pie_de_foto{display:block; margin-left: auto; margin-right: auto; border: none; background-color: #FFF;}
        </style>
    </head>


    <body>

        <!-- HEADER -->
        <div id="header">
                <table style="width: 100%;">
                        <tr style="width: 100%;">
                            <td style="width: 60%;" rowspan="4"><img style="width: 320px;" src="data:image/png;base64,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"></td>
                            <td><b>Fecha:</b></td>
                            <td class="grises">
                                @isset($infoAvaluo['Encabezado']['Fecha'])
                                    {{$infoAvaluo['Encabezado']['Fecha']}}
                                @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>Avalúo:</b></td>
                            <td class="grises">
                                @isset($infoAvaluo['Encabezado']['Avaluo_No'])
                                    {{$infoAvaluo['Encabezado']['Avaluo_No']}}
                                @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>No. Único:</b></td>
                            <td class="grises">
                                @isset($infoAvaluo['Encabezado']['No_Unico'])
                                    {{$infoAvaluo['Encabezado']['No_Unico']}}
                                @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>Registro T.D.F</b></td>
                            <td class="grises">
                                @isset($infoAvaluo['Encabezado']['Registro_TDF'])
                                    {{$infoAvaluo['Encabezado']['Registro_TDF']}}
                                @endisset
                            </td>
                        </tr>
                </table>
                <hr style="background-color: #00A346; height: 5px; border: 0px;">
        </div>
        <!-- Fin de HEADER -->


        <!-- FOOTER -->
        <div id="footer" style="text-align: center; vertical-align: middle;">  
            <p class="page" style="margin: auto; display: block;"><br><?php $PAGE_NUM ?></p> 
        </div>
        <!-- Fin de FOOTER -->


        <!-- CONTENIDO -->
        <div id="content">
            <div class="page_break" style="font-family: Montserrat; margin-top: 0px; margin-bottom: 0px;">

                <div style="text-align: right; padding-top: 10px;">AVALÚO</div>

                <!-- 1.- ANTECEDENTES -->
                <div class="pleca_verde"><b>I. ANTECEDENTES</b></div>

                    <table style="border-collapse: collapse; width: 100%;">
                        <tr>
                            <td><b>SOCIEDAD QUE PRACTICA EL AVALÚO:</b></td>
                            <td>
                            @isset($infoAvaluo['Sociedad_Participa']['Sociedad_Que_Practica_Avaluo'])
                                <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Sociedad_Que_Practica_Avaluo']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>VALUADOR:</b></td>
                            <td>
                            @isset($infoAvaluo['Sociedad_Participa']['Valuador'])
                                <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Valuador']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>FECHA DEL AVALÚO:</b></td>
                            <td>
                            @isset($infoAvaluo['Sociedad_Participa']['Fecha_del_Avaluo'])
                                <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Fecha_del_Avaluo']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"><b>SOLICITANTE:</b></td>
                            <td>
                                Tipo persona: 
                                @isset($infoAvaluo['Sociedad_Participa']['Solicitante']['Tipo_persona'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Solicitante']['Tipo_persona']}}</span>
                                @endisset
                                <br>
                                @isset($infoAvaluo['Sociedad_Participa']['Solicitante']['Nombre'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Solicitante']['Nombre']}}</span> 
                                @endisset
                                <br>
                                UBICACIÓN DEL INMUEBLE: Calle : 
                                @isset($infoAvaluo['Sociedad_Participa']['Solicitante']['Calle'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Solicitante']['Calle']}}</span> 
                                @endisset
                                <br>
                                Nº Exterior: 
                                @isset($infoAvaluo['Sociedad_Participa']['Solicitante']['No_Exterior'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Solicitante']['No_Exterior']}}</span> 
                                @endisset
                                <br>
                                Nº Interior: 
                                @isset($infoAvaluo['Sociedad_Participa']['Solicitante']['No_Interior'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Solicitante']['No_Interior']}}</span> 
                                @endisset
                                <br>
                                Colonia: 
                                @isset($infoAvaluo['Sociedad_Participa']['Solicitante']['Colonia'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Solicitante']['Colonia']}}</span>
                                @endisset
                                CP : 
                                @isset($infoAvaluo['Sociedad_Participa']['Solicitante']['CP'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Solicitante']['CP']}}</span> 
                                @endisset
                                <br>
                                Alcaldía: 
                                @isset($infoAvaluo['Sociedad_Participa']['Solicitante']['Delegacion'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Solicitante']['Delegacion']}}</span>
                                @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>INMUEBLE QUE SE VALÚA:</b></td>
                            <td>
                            @isset($infoAvaluo['Sociedad_Participa']['inmuebleQueSeValua'])
                                <span class="grises">{{$infoAvaluo['Sociedad_Participa']['inmuebleQueSeValua']}}</span>
                            @endisset                            
                            </td>
                        </tr>
                        <tr>
                            <td><b>RÉGIMEN DE PROPIEDAD:</b></td>
                            <td>
                            @isset($infoAvaluo['Sociedad_Participa']['regimenDePropiedad'])
                                <span class="grises">{{$infoAvaluo['Sociedad_Participa']['regimenDePropiedad']}}</span>
                            @endisset                            
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"><b>PROPIETARIO DEL INMUEBLE:</b></td>
                            <td>
                                Tipo persona: 
                                @isset($infoAvaluo['Sociedad_Participa']['Propietario']['Tipo_persona'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Propietario']['Tipo_persona']}}</span>
                                @endisset
                                <br>
                                
                                @isset($infoAvaluo['Sociedad_Participa']['Propietario']['Nombre'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Propietario']['Nombre']}}</span>
                                @endisset
                                <br>
                                UBICACIÓN DEL INMUEBLE: Calle : 
                                @isset($infoAvaluo['Sociedad_Participa']['Propietario']['Calle'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Propietario']['Calle']}}</span>
                                @endisset
                                <br>
                                Nº Exterior: 
                                @isset($infoAvaluo['Sociedad_Participa']['Propietario']['No_Exterior'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Propietario']['No_Exterior']}}</span>
                                @endisset
                                <br>
                                Nº Interior: 
                                @isset($infoAvaluo['Sociedad_Participa']['Propietario']['No_Interior'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Propietario']['No_Interior']}}</span>
                                @endisset
                                <br>
                                Colonia: 
                                @isset($infoAvaluo['Sociedad_Participa']['Propietario']['Colonia'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Propietario']['Colonia']}}</span>
                                @endisset
                                CP : 
                                @isset($infoAvaluo['Sociedad_Participa']['Propietario']['CP'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Propietario']['CP']}}</span>
                                @endisset
                                <br>
                                Delegación: 
                                @isset($infoAvaluo['Sociedad_Participa']['Propietario']['Delegacion'])
                                    <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Propietario']['Delegacion']}}</span>
                                @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>OBJETO DEL AVALÚO:</b></td>
                            <td>
                            @isset($infoAvaluo['Sociedad_Participa']['Objeto_Avaluo'])
                                <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Objeto_Avaluo']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>PROPÓSITO DEL AVALÚO:</b></td>
                            <td>
                            @isset($infoAvaluo['Sociedad_Participa']['Proposito_Avaluo'])
                                <span class="grises">{{$infoAvaluo['Sociedad_Participa']['Proposito_Avaluo']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="heigth: 10px;"></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="border: 1px solid #B5B5B5; padding: 8px;">
                                <table style="width: 100%;">
                                    <tr>
                                        <td valign="top" style="width: 35%;"><b>UBICACIÓN DEL INMUEBLE:</b></td>
                                        <td style="width: 15%;" class="negritas">
                                            Calle:<br>
                                            <b>Nº Exterior:</b><br>
                                            <b>Nº Interior:</b><br>
                                            <b>Colonia:</b><br>
                                            <b>CP:</b><br>
                                            <b>Delegación:</b><br>
                                            <b>Edificio:</b><br>
                                            <b>Lote:</b><br>
                                            <b>Cuenta agua:</b>
                                        </td>
                                        <td style="width: 50%;">
                                            @isset($infoAvaluo['Ubicacion_Inmueble']['Calle'])
                                                <span class="grises">{{$infoAvaluo['Ubicacion_Inmueble']['Calle']}}</span>
                                            @endisset
                                            <br>
                                            @isset($infoAvaluo['Ubicacion_Inmueble']['No_Exterior'])
                                                <span class="grises">{{$infoAvaluo['Ubicacion_Inmueble']['No_Exterior']}}</span>
                                            @endisset
                                            <br>
                                            @isset($infoAvaluo['Ubicacion_Inmueble']['No_Interior'])
                                                <span class="grises">{{$infoAvaluo['Ubicacion_Inmueble']['No_Interior']}}</span>
                                            @endisset
                                            <br>
                                            @isset($infoAvaluo['Ubicacion_Inmueble']['Colonia'])
                                                <span class="grises">{{$infoAvaluo['Ubicacion_Inmueble']['Colonia']}}</span>
                                            @endisset
                                            <br>
                                            @isset($infoAvaluo['Ubicacion_Inmueble']['CP'])
                                                <span class="grises">{{$infoAvaluo['Ubicacion_Inmueble']['CP']}}</span>
                                            @endisset
                                            <br>
                                            @isset($infoAvaluo['Ubicacion_Inmueble']['Delegacion'])
                                                <span class="grises">{{$infoAvaluo['Ubicacion_Inmueble']['Delegacion']}}</span>
                                            @endisset
                                            <br>
                                            @isset($infoAvaluo['Ubicacion_Inmueble']['Edificio'])
                                                <span class="grises">{{$infoAvaluo['Ubicacion_Inmueble']['Edificio']}}</span>
                                            @endisset
                                            <br>
                                            @isset($infoAvaluo['Ubicacion_Inmueble']['Lote'])
                                                <span class="grises">{{$infoAvaluo['Ubicacion_Inmueble']['Lote']}}</span>
                                            @endisset
                                            <br>
                                            @isset($infoAvaluo['Ubicacion_Inmueble']['Cuenta_agua'])
                                                <span class="grises">{{$infoAvaluo['Ubicacion_Inmueble']['Cuenta_agua']}}</span>
                                            @endisset
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>


                <!-- 2.- Características Urbanas -->
                <div class="pleca_verde"><b>II. CARACTERÍSTICAS URBANAS</b></div>

                    <table style="width: 100%">
                        <tr>
                            <td style="width: 35%;"><b>CLASIFICACIÓN DE LA ZONA:</b></td>
                            <td style="width: 65%;">
                            @isset($infoAvaluo['Clasificacion_de_la_zona'])
                                <span class="grises">{{$infoAvaluo['Clasificacion_de_la_zona']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>ÍNDICE DE SATURACIÓN DE LA ZONA:</b></td>
                            <td>
                            @isset($infoAvaluo['Indice_Saturacion_Zona'])
                                <span class="grises">{{$infoAvaluo['Indice_Saturacion_Zona']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>TIPO DE CONSTRUCCIÓN DOMINANTE:</b></td>
                            <td>
                            @isset($infoAvaluo['Tipo_Construccion_Dominante'])
                                <span class="grises">{{$infoAvaluo['Tipo_Construccion_Dominante']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>DENSIDAD DE LA POBLACIÓN:</b></td>
                            <td>
                            @isset($infoAvaluo['Densidad_Poblacion'])
                                <span class="grises">{{$infoAvaluo['Densidad_Poblacion']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>NIVEL SOCIOECONÓMICO DE LA ZONA:</b></td>
                            <td>
                            @isset($infoAvaluo['Nivel_Socioeconomico_Zona'])
                                <span class="grises">{{$infoAvaluo['Nivel_Socioeconomico_Zona']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>CONTAMINACIÓN DEL MEDIO AMBIENTE:</b></td>
                            <td>
                            @isset($infoAvaluo['Contaminacion_Medio_Ambiente'])
                                @if(!is_array($infoAvaluo['Contaminacion_Medio_Ambiente']))
                                    <span class="grises">{{$infoAvaluo['Contaminacion_Medio_Ambiente']}}</span>
                                @endif
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>CLASE GENERAL DE INMUEBLES DE LA ZONA:</b></td>
                            <td>
                            @isset($infoAvaluo['Clase_General_De_Inmuebles_Zona'])
                                <span class="grises">{{$infoAvaluo['Clase_General_De_Inmuebles_Zona']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>USO DEL SUELO:</b></td>
                            <td>
                            @isset($infoAvaluo['Uso_Suelo'])
                                <span class="grises">{{$infoAvaluo['Uso_Suelo']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td><b>ÁREA LIBRE OBLIGATORIA:</b></td>
                            <td>
                            @isset($infoAvaluo['Area_Libre_Obligatoria'])
                                <span class="grises">{{$infoAvaluo['Area_Libre_Obligatoria']}}</span>
                            @endisset
                            </td>
                        </tr>
                        <tr>
                            <td valign="top"><b>VÍAS DE ACCESO E IMPORTANCIA DE LAS MISMAS:</b></td>
                            <td>
                            @isset($infoAvaluo['Vias_Acceso_E_Importancia'])
                                <span class="grises">{{$infoAvaluo['Vias_Acceso_E_Importancia']}}</span>
                            @endisset
                            </td>
                        </tr>
                    </table>


                    <h4 style="margin-top: 4%;">SERVICIOS PÚBLICOS Y EQUIPAMIENTO URBANO:</h4>

                        <table>
                            <tr>
                                <td><b>Red de distribución agua potable:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Red_Agua_Potable'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Red_Agua_Potable']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Red de recolección de aguas residuales:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Red_Aguas_Residuales'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Red_Aguas_Residuales']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Red de drenaje de aguas pluviales en la calle:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Red_Drenaje_Aguas_Pluviales_Calle'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Red_Drenaje_Aguas_Pluviales_Calle']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Red de drenaje de aguas pluviales en la zona:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Red_Drenaje_Aguas_Pluviales_Zona'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Red_Drenaje_Aguas_Pluviales_Zona']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Sistema mixto (aguas pluviales y residuales):</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Sistema_Mixto'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Sistema_Mixto']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Suministro eléctrico:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Suministro_Electrico'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Suministro_Electrico']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Acometida al inmueble:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Acometida_Inmueble'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Acometida_Inmueble']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Alumbrado público:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Alumbrado_Publico'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Alumbrado_Publico']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Vialidades:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Vialidades'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Vialidades']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Banquetas:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Banquetas'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Banquetas']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Guarniciones:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Guarniciones'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Guarniciones']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Nivel de infraestructura en la zona (%):</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Nivel_Infraestructura_Zona'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Nivel_Infraestructura_Zona']}} %</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Gas natural:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Gas_Natutral'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Gas_Natutral']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Teléfonos suministro:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Telefonos_Suministro'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Telefonos_Suministro']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Señalización de vías:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Senalizacion_Vias'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Senalizacion_Vias']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Acometida al inmueble tel.:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Acometida_Inmueble_Tel'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Acometida_Inmueble_Tel']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Distancia transporte urbano:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Distancia_Transporte_Urbano'])
                                    <span class="grises">{{number_format($infoAvaluo['Servicios_Publicos_Equipamiento']['Distancia_Transporte_Urbano'],2)}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Frecuencia transporte urbano:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Frecuencia_Transporte_Urbano'])
                                    <span class="grises">{{number_format($infoAvaluo['Servicios_Publicos_Equipamiento']['Frecuencia_Transporte_Urbano'],2)}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Distancia transporte suburbano:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Distancia_Transporte_Suburbano'])
                                    <span class="grises">{{number_format($infoAvaluo['Servicios_Publicos_Equipamiento']['Distancia_Transporte_Suburbano'],2)}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Frecuencia transporte suburbano:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Frecuencia_Transporte_Suburbano'])
                                    <span class="grises">{{number_format($infoAvaluo['Servicios_Publicos_Equipamiento']['Frecuencia_Transporte_Suburbano'],2)}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Vigilancia:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Vigilancia'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Vigilancia']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Recolección de basura:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Recoleccion_Basura'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Recoleccion_Basura']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Templo:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Templo'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Templo']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Mercados:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Mercados'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Mercados']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Plazas públicas:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Plazas_Publicas'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Plazas_Publicas']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Parques y jardines:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Parques_Jardines'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Parques_Jardines']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Escuelas:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Escuelas'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Escuelas']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Hospitales:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Hospitales'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Hospitales']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Bancos:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Bancos'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Bancos']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Estación de transporte:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Estacion_Transporte'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Estacion_Transporte']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Nivel de equipamiento urbano:</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Nivel_Equipamiento_Urbano'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Nivel_Equipamiento_Urbano']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Nomenclatura de calles</b></td>
                                <td>
                                @isset($infoAvaluo['Servicios_Publicos_Equipamiento']['Nomenclatura_Calles'])
                                    <span class="grises">{{$infoAvaluo['Servicios_Publicos_Equipamiento']['Nomenclatura_Calles']}}</span>
                                @endisset
                                </td>
                            </tr>
                        </table>


                <!-- 3.- Terreno -->
                <div class="pleca_verde"><b>III. TERRENO</b></div>

                    <div><b>CALLES TRANSVERSALES, LIMÍTROFES Y ORIENTACIÓN:</b></div>
                    <div>
                    @isset($infoAvaluo['Calles_Transversales_Limitrofes'])
                    <span class="grises">{{$infoAvaluo['Calles_Transversales_Limitrofes']}}</span>
                    @endisset
                    </div> 

                    <h4 style="margin-top: 4%;">CROQUIS DE LOCALIZACIÓN:</h4>
                        
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 50%; padding: 10px;" class="centrado"><img style="max-width: 320px;" src="data:image/png;base64,{{$infoAvaluo['Croquis_Localizacion']['Microlocalizacion']}}"></td>
                                <td style="width: 50%; padding: 10px;" class="centrado"><img style="max-width: 320px;" src="data:image/png;base64,{{$infoAvaluo['Croquis_Localizacion']['Macrolocalizacion']}}"></td>
                            </tr>
                        </table>


                    <h4 style="margin-top: 2%;">FUENTE DE INFORMACIÓN LEGAL:</h4>
                   
                        <table>
                            <tr>
                                <td><b>Fuente:</b></td>
                                <td>
                                @isset($infoAvaluo['Medidas_Colindancias']['Fuente'])
                                    <span class="grises">{{$infoAvaluo['Medidas_Colindancias']['Fuente']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Número escritura:</b></td>
                                <td>
                                @isset($infoAvaluo['Medidas_Colindancias']['Numero_Escritura'])
                                    <span class="grises">{{$infoAvaluo['Medidas_Colindancias']['Numero_Escritura']}}</span>
                                @endisset
                                </td>
                                <td><b>Número volumen:</b></td>
                                <td>
                                @isset($infoAvaluo['Medidas_Colindancias']['Numero_Volumen'])
                                    <span class="grises">{{$infoAvaluo['Medidas_Colindancias']['Numero_Volumen']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Número notaría:</b></td>
                                <td>
                                @isset($infoAvaluo['Medidas_Colindancias']['Numero_Notaria'])
                                    <span class="grises">{{$infoAvaluo['Medidas_Colindancias']['Numero_Notaria']}}</span>
                                @endisset
                                </td>
                                <td><b>Nombre de notario:</b></td>
                                <td>
                                @isset($infoAvaluo['Medidas_Colindancias']['Nombre_Notario'])
                                    <span class="grises">{{$infoAvaluo['Medidas_Colindancias']['Nombre_Notario']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>Entidad federativa:</b></td>
                                <td>
                                @isset($infoAvaluo['Medidas_Colindancias']['Entidad_Federativa'])
                                    <span class="grises">{{$infoAvaluo['Medidas_Colindancias']['Entidad_Federativa']}}</span>
                                @endisset
                                </td>
                            </tr>
                        </table>



                        <h4 style="margin-top: 4%;">MEDIDAS Y COLINDANCIAS:</h4>

                        <table class="tabla_cabeza_gris">
                            <thead>
                                <tr>
                                    <th>Orientación</th>
                                    <th>Medida En Metros</th>
                                    <th>Descripción Colindante</th>
                                </tr>
                            </thead>
                            <tbody>
                            @isset($infoAvaluo['Colindancias'])
                                @foreach($infoAvaluo['Colindancias'] as $value_colindancias)
                                <tr>
                                    <td><span class="grises">{{$value_colindancias['Orientacion']}}</span></td>
                                    <td><span class="grises">{{$value_colindancias['MedidaEnMetros']}}</span></td>
                                    <td><span class="grises">{{$value_colindancias['DescripcionColindante']}}</span></td>
                                </tr>
                                @endforeach
                            @endisset
                            </tbody>
                        </table>


                    <h4 style="margin-top: 4%;">SUPERFICIE TOTAL SEGÚN:</h4>

                        <table class="tabla_cabeza_gris">
                            <thead>
                                <tr>
                                    <th>Ident. Fracción</th>
                                    <th>Sup Fracción</th>
                                    <th>Valor Fracción</th>
                                    <th>Clave Área De Valor</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Superficie_Total_Segun']['Ident_Fraccion'])
                                        <span class="grises">{{$infoAvaluo['Superficie_Total_Segun']['Ident_Fraccion']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Superficie_Total_Segun']['Sup_Fraccion'])
                                        <span class="grises">{{number_format($infoAvaluo['Superficie_Total_Segun']['Sup_Fraccion'],2)}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Superficie_Total_Segun']['Valor_Fraccion'])
                                        <span class="grises">{{number_format($infoAvaluo['Superficie_Total_Segun']['Valor_Fraccion'],2)}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Superficie_Total_Segun']['Clave_Area_Valor'])
                                        <span class="grises">{{$infoAvaluo['Superficie_Total_Segun']['Clave_Area_Valor']}}</span>
                                    @endisset
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div style="text-align: right;">
                            <b>SUPERFICIE TOTAL TERRENO: 
                            @isset($infoAvaluo['Superficie_Total_Segun']['Totales']['Superficie_Total_Terreno'])
                                <span class="grises">{{$infoAvaluo['Superficie_Total_Segun']['Totales']['Superficie_Total_Terreno']}}</span>
                            @endisset                        
                            </b>
                        </div>


                    <h4 style="margin-top: 4%;">TOPOGRAFÍA Y CONFIGURACIÓN:</h4>
                   
                        <table>
                            <tr>
                                <td><b>CARACTERÍSTICAS PANORÁMICAS:</b></td>
                                <td>
                                @isset($infoAvaluo['Topografia_Configuracion']['Caracteristicas_Panoramicas'])
                                    <span class="grises">{{$infoAvaluo['Topografia_Configuracion']['Caracteristicas_Panoramicas']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>DENSIDAD HABITACIONAL:</b></td>
                                <td>
                                @isset($infoAvaluo['Topografia_Configuracion']['Densidad_Habitacional'])
                                    <span class="grises">{{$infoAvaluo['Topografia_Configuracion']['Densidad_Habitacional']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>SERVIDUMBRE O RESTRICCIONES:</b></td>
                                <td>
                                @isset($infoAvaluo['Topografia_Configuracion']['Servidumbre_Restricciones'])
                                    <span class="grises">{{$infoAvaluo['Topografia_Configuracion']['Servidumbre_Restricciones']}}</span>
                                @endisset
                                </td>
                            </tr>
                        </table>


                <!-- 4.- Descripción General del Inmueble -->
                <div class="pleca_verde"><b>IV.- DESCRIPCIÓN GENERAL DEL INMUEBLE</b></div>

                    <div><b>USO ACTUAL:</b></div>
                    <div>
                    @isset($infoAvaluo['Uso_Actual'])
                        <span class="grises">{{$infoAvaluo['Uso_Actual']}}</span>
                    @endisset
                    </div>


                    <h4 style="margin-top: 4%;">CONSTRUCCIONES PRIVATIVAS</h4>
                    @if(isset($infoAvaluo['Construcciones_Privativas']))
                        <table  class="tabla_cabeza_gris">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th>Uso</th>
                                    <th>Nº Niveles Del Tipo</th>
                                    <th>Clave Rango De Niveles</th>
                                    <th>Puntaje</th>
                                    <th>Clase</th>
                                    <th>Edad</th>
                                    <th>Vida Útil Total Del Tipo</th>
                                    <th>Vida Útil Remanente</th>
                                    <th>Conservación</th>
                                    <th>Sup.</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php
                                $i_construccionesP = 1;
                            @endphp
                            @if(isset($infoAvaluo['Construcciones_Privativas'][0]))
                                @foreach($infoAvaluo['Construcciones_Privativas'] as $value_construccionesP)
                                    <tr>
                                        <td class="centrado">
                                            <span class="grises">{{$i_construccionesP++}}</span>
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Descripcion'])
                                            <span class="grises">{{$value_construccionesP['Descripcion']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Uso'])
                                            <span class="grises">{{$value_construccionesP['Uso']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['No_Niveles_Tipo'])
                                            <span class="grises">{{$value_construccionesP['No_Niveles_Tipo']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Clave_Rango_Niveles'])
                                            <span class="grises">{{$value_construccionesP['Clave_Rango_Niveles']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Puntaje'])
                                            <span class="grises">{{$value_construccionesP['Puntaje']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Clase'])
                                            <span class="grises">{{$value_construccionesP['Clase']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Edad'])
                                            <span class="grises">{{$value_construccionesP['Edad']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Vida_Util_Total_Tipo'])
                                            <span class="grises">{{$value_construccionesP['Vida_Util_Total_Tipo']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Vida_Util_Remanente'])
                                            <span class="grises">{{$value_construccionesP['Vida_Util_Remanente']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Conservacion'])
                                            <span class="grises">{{$value_construccionesP['Conservacion']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesP['Sup'])
                                            <span class="grises">{{number_format($value_construccionesP['Sup'],2)}}</span>
                                        @endisset
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                    <tr>
                                        <td class="centrado">
                                        1
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Descripcion'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['Descripcion']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Uso'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['Uso']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['No_Niveles_Tipo'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['No_Niveles_Tipo']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Clave_Rango_Niveles'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['Clave_Rango_Niveles']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Puntaje'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['Puntaje']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Clase'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['Clase']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Edad'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['Edad']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Vida_Util_Total_Tipo'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['Vida_Util_Total_Tipo']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Vida_Util_Remanente'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['Vida_Util_Remanente']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Conservacion'])
                                            <span class="grises">{{$infoAvaluo['Construcciones_Privativas']['Conservacion']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Construcciones_Privativas']['Sup'])
                                            <span class="grises">{{number_format($infoAvaluo['Construcciones_Privativas']['Sup'],2)}}</span>
                                        @endisset
                                        </td>
                                    </tr>
                            @endif
                            </tbody>
                        </table>
                    @endif

                    <h4 style="margin-top: 4%;">CONSTRUCCIONES COMUNES</h4>
                    @if(isset($infoAvaluo['Construcciones_Comunes']))
                        <table  class="tabla_cabeza_gris">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Descripción</th>
                                    <th>Uso</th>
                                    <th>Nº Niveles Del Tipo</th>
                                    <th>Clave Rango De Niveles</th>
                                    <th>Puntaje</th>
                                    <th>Clase</th>
                                    <th>Edad</th>
                                    <th>Vida Útil Total Del Tipo</th>
                                    <th>Vida Útil Remanente</th>
                                    <th>Conservación</th>
                                    <th>Sup.</th>
                                </tr>
                            </thead>
                            <tbody>
                            @php
                                $i_construccionesC = 1;
                            @endphp
                            @if(isset($infoAvaluo['Construcciones_Comunes'][0]))
                                @foreach($infoAvaluo['Construcciones_Comunes'] as $value_construccionesC)
                                    <tr>
                                        <td class="centrado">
                                            <span class="grises">{{$i_construccionesC++}}</span>
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Descripcion'])
                                            <span class="grises">{{$value_construccionesC['Descripcion']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Uso'])
                                            <span class="grises">{{$value_construccionesC['Uso']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['No_Niveles_Tipo'])
                                            <span class="grises">{{$value_construccionesC['No_Niveles_Tipo']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Clave_Rango_Niveles'])
                                            <span class="grises">{{$value_construccionesC['Clave_Rango_Niveles']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Puntaje'])
                                            <span class="grises">{{$value_construccionesC['Puntaje']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Clase'])
                                            <span class="grises">{{$value_construccionesC['Clase']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Edad'])
                                            <span class="grises">{{$value_construccionesC['Edad']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Vida_Util_Total_Tipo'])
                                            <span class="grises">{{$value_construccionesC['Vida_Util_Total_Tipo']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Vida_Util_Remanente'])
                                            <span class="grises">{{$value_construccionesC['Vida_Util_Remanente']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Conservacion'])
                                            <span class="grises">{{$value_construccionesC['Conservacion']}}</span>
                                        @endisset
                                        </td>
                                        <td class="centrado">
                                        @isset($value_construccionesC['Sup'])
                                            <span class="grises">{{$value_construccionesC['Sup']}}</span>
                                        @endisset
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Tipo'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Tipo']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Descripcion'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Descripcion']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Uso'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Uso']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['No_Niveles_Tipo'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['No_Niveles_Tipo']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Clave_Rango_Niveles'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Clave_Rango_Niveles']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Puntaje'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Puntaje']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Clase'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Clase']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Edad'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Edad']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Vida_Util_Total_Tipo'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Vida_Util_Total_Tipo']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Vida_Util_Remanente'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Vida_Util_Remanente']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Conservacion'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Conservacion']}}</span>
                                    @endisset
                                    </td>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Construcciones_Comunes']['Sup'])
                                        <span class="grises">{{$infoAvaluo['Construcciones_Comunes']['Sup']}}</span>
                                    @endisset
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    @endif
                    <br>
                        <table>
                            <tr>
                                <td><b>INDIVISO</b></td>
                                <td>
                                @isset($infoAvaluo['Indiviso'])
                                    <span class="grises">{{$infoAvaluo['Indiviso']}} %</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>VIDA ÚTIL PROMEDIO DEL INMUEBLE:</b></td>
                                <td>
                                @isset($infoAvaluo['Vida_Util_Promedio_Inmueble'])
                                    <span class="grises">{{$infoAvaluo['Vida_Util_Promedio_Inmueble']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>EDAD APROXIMADA DE LA CONSTRUCCIÓN:</b></td>
                                <td>
                                @isset($infoAvaluo['Edad_Aproximada_Construccion'])
                                    <span class="grises">{{$infoAvaluo['Edad_Aproximada_Construccion']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>VIDA ÚTIL REMANENTE:</b></td>
                                <td>
                                @isset($infoAvaluo['Vida_Util_Remanente'])
                                    <span class="grises">{{$infoAvaluo['Vida_Util_Remanente']}}</span>
                                @endisset
                                </td>
                            </tr>
                        </table>

                <!-- 5.- Elementos de la Construcción -->
                <div class="pleca_verde"><b>V.- ELEMENTOS DE LA CONSTRUCCIÓN</b></div>

                    <h4 style="margin-top: 4%;"><b>a) OBRA NEGRA O GRUESA:</b></h4>

                        <table>
                            <tr>
                                <td><b>CIMIENTOS:</b></td>
                                <td>
                                @isset($infoAvaluo['Obra_Negra_Gruesa']['Cimientos'])
                                    <span class="grises">{{$infoAvaluo['Obra_Negra_Gruesa']['Cimientos']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>ESTRUCTURA:</b></td>
                                <td>
                                @isset($infoAvaluo['Obra_Negra_Gruesa']['Estructura'])
                                    <span class="grises">{{$infoAvaluo['Obra_Negra_Gruesa']['Estructura']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>MUROS:</b></td>
                                <td>
                                @isset($infoAvaluo['Obra_Negra_Gruesa']['Muros'])
                                    <span class="grises">{{$infoAvaluo['Obra_Negra_Gruesa']['Muros']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>ENTREPISOS:</b></td>
                                <td>
                                @isset($infoAvaluo['Obra_Negra_Gruesa']['Entrepiso'])
                                    <span class="grises">{{$infoAvaluo['Obra_Negra_Gruesa']['Entrepiso']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>TECHOS:</b></td>
                                <td>
                                @isset($infoAvaluo['Obra_Negra_Gruesa']['Techos'])
                                    <span class="grises">{{$infoAvaluo['Obra_Negra_Gruesa']['Techos']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>AZOTEAS:</b></td>
                                <td>
                                @isset($infoAvaluo['Obra_Negra_Gruesa']['Azoteas'])
                                    <span class="grises">{{$infoAvaluo['Obra_Negra_Gruesa']['Azoteas']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>BARDAS:</b></td>
                                <td>
                                @isset($infoAvaluo['Obra_Negra_Gruesa']['Bardas'])
                                    <span class="grises">{{$infoAvaluo['Obra_Negra_Gruesa']['Bardas']}}</span>
                                @endisset
                                </td>
                            </tr>
                        </table>


                    <h4 style="margin-top: 4%;"><b>b) REVESTIMIENTOS Y ACABADOS INTERIORES</b></h4>

                        <table>
                            <tr>
                                <td><b>APLANADOS:</b></td>
                                <td>
                                @isset($infoAvaluo['Revestimientos_Acabados_Interiores']['Aplanados'])
                                    <span class="grises">{{$infoAvaluo['Revestimientos_Acabados_Interiores']['Aplanados']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>PLAFONES:</b></td>
                                <td>
                                @isset($infoAvaluo['Revestimientos_Acabados_Interiores']['Plafones'])
                                    <span class="grises">{{$infoAvaluo['Revestimientos_Acabados_Interiores']['Plafones']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>LAMBRINES:</b></td>
                                <td>
                                @isset($infoAvaluo['Revestimientos_Acabados_Interiores']['Lambrines'])
                                    <span class="grises">{{$infoAvaluo['Revestimientos_Acabados_Interiores']['Lambrines']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>PISOS:</b></td>
                                <td>
                                @isset($infoAvaluo['Revestimientos_Acabados_Interiores']['Pisos'])
                                    <span class="grises">{{$infoAvaluo['Revestimientos_Acabados_Interiores']['Pisos']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>ZOCLOS:</b></td>
                                <td>
                                @isset($infoAvaluo['Revestimientos_Acabados_Interiores']['Zoclos'])
                                    <span class="grises">{{$infoAvaluo['Revestimientos_Acabados_Interiores']['Zoclos']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>ESCALERAS:</b></td>
                                <td>
                                @isset($infoAvaluo['Revestimientos_Acabados_Interiores']['Escaleras'])
                                    <span class="grises">{{$infoAvaluo['Revestimientos_Acabados_Interiores']['Escaleras']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>PINTURA:</b></td>
                                <td>
                                @isset($infoAvaluo['Revestimientos_Acabados_Interiores']['Pintura'])
                                    <span class="grises">{{$infoAvaluo['Revestimientos_Acabados_Interiores']['Pintura']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>RECUBRIMIENTOS ESPECIALES:</b></td>
                                <td>
                                @isset($infoAvaluo['Revestimientos_Acabados_Interiores']['Recubrimientos_Especiales'])
                                    <span class="grises">{{$infoAvaluo['Revestimientos_Acabados_Interiores']['Recubrimientos_Especiales']}}</span>
                                @endisset
                                </td>
                            </tr>
                        </table>


                    <h4 style="margin-top: 4%;"><b>c) CARPINTERÍA</b></h4>

                        <table>
                            <tr>
                                <td><b>PUERTAS INTERIORES:</b></td>
                                <td>
                                @isset($infoAvaluo['Carpinteria']['Puertas_Interiores'])
                                    <span class="grises">{{$infoAvaluo['Carpinteria']['Puertas_Interiores']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>GUARDARROPAS:</b></td>
                                <td>
                                @isset($infoAvaluo['Carpinteria']['Guardarropas'])
                                    <span class="grises">{{$infoAvaluo['Carpinteria']['Guardarropas']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>MUEBLES EMPOTRADOS O FIJOS:</b></td>
                                <td>
                                @isset($infoAvaluo['Carpinteria']['Muebles_Empotrados'])
                                    <span class="grises">{{$infoAvaluo['Carpinteria']['Muebles_Empotrados']}}</span>
                                @endisset
                                </td>
                            </tr>
                        </table>


                    <h4 style="margin-top: 4%;"><b>d) INSTALACIONES HIDRAULICAS Y SANITARIAS</b></h4>

                        <table>
                            <tr>
                                <td><b>MUEBLES DE BAÑO:</b></td>
                                <td>
                                @isset($infoAvaluo['Instalaciones_Hidraulicas_Sanitrias']['Muebles_Banio'])
                                    <span class="grises">{{$infoAvaluo['Instalaciones_Hidraulicas_Sanitrias']['Muebles_Banio']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>RAMALEOS HIDRÁULICOS:</b></td>
                                <td>
                                @isset($infoAvaluo['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Hidraulicos'])
                                    <span class="grises">{{$infoAvaluo['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Hidraulicos']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>RAMALEOS SANITARIOS:</b></td>
                                <td>
                                @isset($infoAvaluo['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Sanitarios'])
                                    <span class="grises">{{$infoAvaluo['Instalaciones_Hidraulicas_Sanitrias']['Ramaleos_Sanitarios']}}</span>
                                @endisset
                                </td>
                            </tr>
                        </table>


                    <table>
                        <tr>
                            <td><b>e) INSTALACIONES ELÉCTRICAS Y ALUMBRADO</b></td>
                            <td>
                            @isset($infoAvaluo['Instalaciones_Electricas_Alumbrados'])
                                @if(!is_array($infoAvaluo['Instalaciones_Electricas_Alumbrados']))
                                    <span class="grises">{{$infoAvaluo['Instalaciones_Electricas_Alumbrados']}}</span>
                                @endif
                            @endisset
                            </td>
                        </tr>
                    </table>


                    <h4 style="margin-top: 4%;"><b>f) PUERTAS Y VENTANERÍA METÁLICA</b></h4>

                        <table>
                            <tr>
                                <td><b>HERRERÍA:</b></td>
                                <td>
                                @isset($infoAvaluo['Puertas_Ventaneria_Metalica']['Herreria'])
                                    <span class="grises">{{$infoAvaluo['Puertas_Ventaneria_Metalica']['Herreria']}}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>VENTANERÍA:</b></td>
                                <td>
                                @isset($infoAvaluo['Puertas_Ventaneria_Metalica']['Ventaneria'])
                                    <span class="grises">{{$infoAvaluo['Puertas_Ventaneria_Metalica']['Ventaneria']}}</span>
                                @endisset
                                </td>
                            </tr>
                        </table>


                    <table>
                        <tr>
                            <td><b>g) VIDRIERÍA</b></td>
                            <td>
                            @isset($infoAvaluo['Vidrieria'])
                                @if(!is_array($infoAvaluo['Vidrieria']))
                                    <span class="grises">{{$infoAvaluo['Vidrieria']}}</span>
                                @endif
                            @endisset
                            </td>
                        </tr>
                    </table>


                    <table>
                        <tr>
                            <td><b>h) CERRAJERÍA</b></td>
                            <td>
                            @isset($infoAvaluo['Cerrajeria'])
                                @if(!is_array($infoAvaluo['Cerrajeria']))
                                    <span class="grises">{{$infoAvaluo['Cerrajeria']}}</span>
                                @endif
                            @endisset
                            </td>
                        </tr>
                    </table>


                    <table>
                        <tr>
                            <td><b>i) FACHADAS</b></td>
                            <td>
                            @isset($infoAvaluo['Fachadas'])
                                @if(!is_array($infoAvaluo['Fachadas']))
                                    <span class="grises">{{$infoAvaluo['Fachadas']}}</span>
                                @endif
                            @endisset
                            </td>
                        </tr>
                    </table>


                    <h4 style="margin-top: 4%;"><b>j) INSTALACIONES ESPECIALES</b></h4>
                        <!-- PRIVATIVAS -->
                        <span><b>Privativas</b></span>
                        @if(isset($infoAvaluo['Instalaciones_Especiales']['Privativas']))
                        <table class="tabla_cabeza_gris" style="">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Descripción</th>
                                    <th>Unidad</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($infoAvaluo['Instalaciones_Especiales']['Privativas'][0]))
                                    @foreach($infoAvaluo['Instalaciones_Especiales']['Privativas'] as $value_instalacionesEspeciales)
                                        <tr>
                                            <td class="centrado">
                                            @isset($value_instalacionesEspeciales['Clave'])
                                                <span class="grises">{{$value_instalacionesEspeciales['Clave']}}</span>
                                            @endisset
                                            </td>
                                            <td>
                                            @isset($value_instalacionesEspeciales['Descripcion'])
                                                <span class="grises">{{$value_instalacionesEspeciales['Descripcion']}}</span>
                                            @endisset
                                            </td>
                                            <td>
                                            @isset($value_instalacionesEspeciales['Unidad'])
                                                <span class="grises">{{$value_instalacionesEspeciales['Unidad']}}</span>
                                            @endisset
                                            </td>
                                            <td>
                                            @isset($value_instalacionesEspeciales['Cantidad'])
                                                <span class="grises">{{$value_instalacionesEspeciales['Cantidad']}}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Instalaciones_Especiales']['Privativas']['Clave'])
                                            <span class="grises">{{$infoAvaluo['Instalaciones_Especiales']['Privativas']['Clave']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($infoAvaluo['Instalaciones_Especiales']['Privativas']['Descripcion'])
                                            <span class="grises">{{$infoAvaluo['Instalaciones_Especiales']['Privativas']['Descripcion']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($infoAvaluo['Instalaciones_Especiales']['Privativas']['Unidad'])
                                            <span class="grises">{{$infoAvaluo['Instalaciones_Especiales']['Privativas']['Unidad']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($infoAvaluo['Instalaciones_Especiales']['Privativas']['Cantidad'])
                                            <span class="grises">{{$infoAvaluo['Instalaciones_Especiales']['Privativas']['Cantidad']}}</span>
                                        @endisset
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        @endif
                        <br>
                        <!-- COMUNES -->
                        <span><b>Comunes</b></span>
                        @if(isset($infoAvaluo['Instalaciones_Especiales']['Comunes']))
                        <table class="tabla_cabeza_gris" style="">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Descripción</th>
                                    <th>Unidad</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($infoAvaluo['Instalaciones_Especiales']['Comunes'][0]))
                                    @foreach($infoAvaluo['Instalaciones_Especiales']['Comunes'] as $value_instalacionesEspeciales)
                                        <tr>
                                            <td class="centrado">
                                            @isset($value_instalacionesEspeciales['Clave'])
                                                <span class="grises">{{$value_instalacionesEspeciales['Clave']}}</span>
                                            @endisset
                                            </td>
                                            <td>
                                            @isset($value_instalacionesEspeciales['Descripcion'])
                                                <span class="grises">{{$value_instalacionesEspeciales['Descripcion']}}</span>
                                            @endisset
                                            </td>
                                            <td>
                                            @isset($value_instalacionesEspeciales['Unidad'])
                                                <span class="grises">{{$value_instalacionesEspeciales['Unidad']}}</span>
                                            @endisset
                                            </td>
                                            <td>
                                            @isset($value_instalacionesEspeciales['Cantidad'])
                                                <span class="grises">{{$value_instalacionesEspeciales['Cantidad']}}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="centrado">
                                        @isset($infoAvaluo['Instalaciones_Especiales']['Comunes']['Clave'])
                                            <span class="grises">{{$infoAvaluo['Instalaciones_Especiales']['Comunes']['Clave']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($infoAvaluo['Instalaciones_Especiales']['Comunes']['Descripcion'])
                                            <span class="grises">{{$infoAvaluo['Instalaciones_Especiales']['Comunes']['Descripcion']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($infoAvaluo['Instalaciones_Especiales']['Comunes']['Unidad'])
                                            <span class="grises">{{$infoAvaluo['Instalaciones_Especiales']['Comunes']['Unidad']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($infoAvaluo['Instalaciones_Especiales']['Comunes']['Cantidad'])
                                            <span class="grises">{{$infoAvaluo['Instalaciones_Especiales']['Comunes']['Cantidad']}}</span>
                                        @endisset
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                        @endif

                    <h4 style="margin-top: 4%;"><b>k) ELEMENTOS ACCESORIOS</b></h4>
                        <span><b>Privativas</b></span>
                        @if(isset($infoAvaluo['Elementos_Accesorios']['Privativas']))
                        <table class="tabla_cabeza_gris" style="">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Descripción</th>
                                    <th>Unidad</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                            @if(isset($infoAvaluo['Elementos_Accesorios']['Privativas'][0]))
                                @foreach($infoAvaluo['Elementos_Accesorios']['Privativas'] as $value_elementoAccP)
                                    <tr>
                                        <td class="centrado">
                                        @isset($value_elementoAccP['Clave'])
                                            <span class="grises">{{$value_elementoAccP['Clave']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_elementoAccP['Descripcion'])
                                            <span class="grises">{{$value_elementoAccP['Descripcion']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_elementoAccP['Unidad'])
                                            <span class="grises">{{$value_elementoAccP['Unidad']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_elementoAccP['Cantidad'])
                                            <span class="grises">{{$value_elementoAccP['Cantidad']}}</span>
                                        @endisset
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Elementos_Accesorios']['Privativas']['Clave'])
                                        <span class="grises">{{$infoAvaluo['Elementos_Accesorios']['Privativas']['Clave']}}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Elementos_Accesorios']['Privativas']['Descripcion'])
                                        <span class="grises">{{$infoAvaluo['Elementos_Accesorios']['Privativas']['Descripcion']}}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Elementos_Accesorios']['Privativas']['Unidad'])
                                        <span class="grises">{{$infoAvaluo['Elementos_Accesorios']['Privativas']['Unidad']}}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Elementos_Accesorios']['Privativas']['Cantidad'])
                                        <span class="grises">{{$infoAvaluo['Elementos_Accesorios']['Privativas']['Cantidad']}}</span>
                                    @endisset
                                    </td>
                                </tr>
                            @endif    
                            </tbody>
                        </table>
                        @endif

                    <h4 style="margin-top: 4%;"><b>l) OBRAS COMPLEMENTARIAS</b></h4>
                        <span><b>Privativas</b></span>
                        @if(isset($infoAvaluo['Obras_Complementarias']['Privativas']))
                        <table class="tabla_cabeza_gris" style="">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Descripción</th>
                                    <th>Unidad</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                            @if(isset($infoAvaluo['Obras_Complementarias']['Privativas'][0]))
                                @foreach($infoAvaluo['Obras_Complementarias']['Privativas'] as $value_obras)
                                    <tr>
                                        <td class="centrado">
                                        @isset($value_obras['Clave'])
                                            <span class="grises">{{$value_obras['Clave']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_obras['Descripcion'])
                                            <span class="grises">{{$value_obras['Descripcion']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_obras['Unidad'])
                                            <span class="grises">{{$value_obras['Unidad']}}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_obras['Cantidad'])
                                            <span class="grises">{{$value_obras['Cantidad']}}</span>
                                        @endisset
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td class="centrado">
                                    @isset($infoAvaluo['Obras_Complementarias']['Privativas']['Clave'])
                                        <span class="grises">{{$infoAvaluo['Obras_Complementarias']['Privativas']['Clave']}}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Obras_Complementarias']['Privativas']['Descripcion'])
                                        <span class="grises">{{$infoAvaluo['Obras_Complementarias']['Privativas']['Descripcion']}}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Obras_Complementarias']['Privativas']['Unidad'])
                                        <span class="grises">{{$infoAvaluo['Obras_Complementarias']['Privativas']['Unidad']}}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Obras_Complementarias']['Privativas']['Cantidad'])
                                        <span class="grises">{{$infoAvaluo['Obras_Complementarias']['Privativas']['Cantidad']}}</span>
                                    @endisset
                                    </td>
                                </tr>                                
                            @endif
                            </tbody>
                        </table>
                        @endif

                <!-- 6.- Consideraciones Previas al Avalúo -->
                <div class="pleca_verde"><b>VI.- CONSIDERACIONES PREVIAS AL AVALÚO</b></div>
                @if(isset($infoAvaluo['Consideraciones_Previas_Al_Avaluo']))
                <p class="letras_pequenas">
                    @isset($infoAvaluo['Consideraciones_Previas_Al_Avaluo'])
                        <span class="grises">{!!$infoAvaluo['Consideraciones_Previas_Al_Avaluo']!!}</span>
                    @endisset
                </p>
                @endif


                <br>
                

                <!-- 7.- Índice Físico o Directo -->
                <div class="pleca_verde"><b>VII.- ÍNDICE FÍSICO O DIRECTO</b></div>
                <p><b>a) CÁLCULO DEL VALOR DEL TERRENO</b></p>
                @if(isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']))
                    <table class="tabla_cabeza_gris">
                        <thead>
                            <tr>
                                <th>Fracc.</th>
                                <th>Área de valor</th>
                                <th>Superficie (m2)</th>
                                <th>Valor Catastral</th>
                                <th>VALOR FRACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if(isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno'][0]))
                            @foreach($infoAvaluo['Calculo_Del_Valor_Del_Terreno'] as $value_valorTerreno)
                                <tr>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Fracc'])
                                        <span class="grises">{{ $infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Fracc'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Clave_Area_Valor'])
                                        <span class="grises">{{ $infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Clave_Area_Valor'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Superficie_m2'])
                                        <span class="grises">{{ number_format($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Superficie_m2'],2) }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Valor_Catastral'])
                                        <span class="grises">{{ number_format($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Valor_Catastral'],2) }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Valor_Fraccion'])
                                        <span class="grises">{{ number_format($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Valor_Fraccion'],2) }}</span>
                                    @endisset
                                    </td>
                                </tr>
                            @endforeach                            
                        @else
                            <tr>
                                <td>
                                @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Fracc'])
                                    <span class="grises">{{ $infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Fracc'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Clave_Area_Valor'])
                                    <span class="grises">{{ $infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Clave_Area_Valor'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Superficie_m2'])
                                    <span class="grises">{{ number_format($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Superficie_m2'],2) }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Valor_Catastral'])
                                    <span class="grises">{{ number_format($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Valor_Catastral'],2) }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Valor_Fraccion'])
                                    <span class="grises">{{ number_format($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Valor_Fraccion'],2) }}</span>
                                @endisset
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                    <br>
                    <p><b>Total superficie: <span class="grises" style="padding-right: 15px;">{{ number_format($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Totales']['Total_Superficie'],2) }}</span>  Valor del terreno total: <span class="grises">{{ number_format($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Totales']['Valor_Del_Terreno_Total'],2) }}</span> </b></p>
                    <br>
                    <p>Indiviso de la unidad que se valúa: <span class="grises">{{ $infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Totales']['Indiviso_Unidad_Que_Se_Valua'] }} %</span></p>

                    <table class="tabla_gris_valor">
                        <thead>
                            <tr>
                                <th style="width: 80%;">VALOR TOTAL DEL TERRENO PROPORCIONAL:</th>
                                <th><span class="grises">$ {{ number_format($infoAvaluo['Calculo_Del_Valor_Del_Terreno']['Totales']['Valor_Total_Del_Terreno_Proporcional'],2) }}</span></th>
                            </tr>
                        </thead> 
                    </table>
                @endif

                <p><b>b) CÁLCULO DEL VALOR DE LAS CONTRUCCIONES</b></p>
                <!-- PRIVATIVAS -->
                @if(isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']))
                    <p><b>PRIVATIVAS: </b></p>
                    <table class="tabla_cabeza_gris">
                        <thead>
                            <tr>
                                <th>Tipo.</th>
                                <th>Descripción</th>
                                <th>Uso</th>
                                <th>N° Niveles Del Tipo</th>
                                <th>Clave Rango De Niveles</th>
                                <th>Clase</th>
                                <th>Valor Unitario Cat.</th>
                                <th>Depreciación por Edad</th>
                                <th>Valor</th>
                                <th>Sup.</th>
                            </tr>
                        </thead>
                        <tbody>
                        @if(isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas'][0]))
                            @foreach($infoAvaluo['Calculo_Valor_Construcciones']['Privativas'] as $value_valorContruccionesP)
                            <tr>
                                    <td>
                                    @isset($value_valorContruccionesP['Tipo'])
                                        <span class="grises">{{ $value_valorContruccionesP['Tipo'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($value_valorContruccionesP['Descripcion'])
                                        <span class="grises">{{ $value_valorContruccionesP['Descripcion'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($value_valorContruccionesP['Uso'])
                                        <span class="grises">{{ $value_valorContruccionesP['Uso'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($value_valorContruccionesP['Numero_Niveles_Tipo'])
                                        <span class="grises">{{ $value_valorContruccionesP['Numero_Niveles_Tipo'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($value_valorContruccionesP['Clave_Rango_Niveles'])
                                        <span class="grises">{{ $value_valorContruccionesP['Clave_Rango_Niveles'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($value_valorContruccionesP['Clase'])
                                        <span class="grises">{{ $value_valorContruccionesP['Clase'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($value_valorContruccionesP['Valor_Unitario'])
                                        <span class="grises">{{ number_format($value_valorContruccionesP['Valor_Unitario'],2) }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($value_valorContruccionesP['Depreciacion_Por_Edad'])
                                        <span class="grises">{{ number_format($value_valorContruccionesP['Depreciacion_Por_Edad'],2) }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($value_valorContruccionesP['Valor'])
                                        <span class="grises">{{ number_format($value_valorContruccionesP['Valor'],2) }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($value_valorContruccionesP['Sup'])
                                        <span class="grises">{{ number_format($value_valorContruccionesP['Sup'],2) }}</span>
                                    @endisset
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Tipo'])
                                    <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Tipo'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Descripcion'])
                                    <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Descripcion'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Uso'])
                                    <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Uso'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Numero_Niveles_Tipo'])
                                    <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Numero_Niveles_Tipo'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Clave_Rango_Niveles'])
                                    <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Clave_Rango_Niveles'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Clase'])
                                    <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Clase'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Valor_Unitario'])
                                    <span class="grises">{{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Valor_Unitario'],2) }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Depreciacion_Por_Edad'])
                                    <span class="grises">{{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Depreciacion_Por_Edad'],2) }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Valor'])
                                    <span class="grises">{{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Valor'],2) }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Sup'])
                                    <span class="grises">{{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Privativas']['Sup'],2) }}</span>
                                @endisset
                                </td>
                            </tr>
                        @endif
                            <tr>
                                <td colspan="10" style="text-align: right; padding-right: 10px;">
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Superficie'])
                                    SUPERFICIE TOTAL DE LAS CONSTRUCCIONES PRIVATIVAS: <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Superficie'] }}</span>
                                @endisset
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table class="tabla_gris_valor">
                        <thead>
                            <tr>
                                <th style="width: 80%;">VALOR TOTAL DE LAS CONSTRUCCIONES PRIVATIVAS:</th>
                                <th>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Construcciones_Privativas'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Privativas']['Total_Construcciones_Privativas'],2) }}</span>
                                @endisset
                                </th>
                            </tr>
                        </thead> 
                    </table>
                <br>
                @endif

                
                
                <p><b>c) CÁLCULO DEL VALOR DE LAS CONSTRUCCIONES COMUNES</b></p>
                <br>
                @if(isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']))
                    <!-- COMUNES -->
                    @if(isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']) && !empty($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']))
                        <p><b>COMUNES: </b></p>
                        <table class="tabla_cabeza_gris">
                            <thead>
                                <tr>
                                    <th>Tipo.</th>
                                    <th>Descripción</th>
                                    <th>Uso</th>
                                    <th>N° Niveles Del Tipo</th>
                                    <th>Clave Rango De Niveles</th>
                                    <th>Clase</th>
                                    <th>Valor Unitario Cat.</th>
                                    <th>Depreciación por Edad</th>
                                    <th>Valor</th>
                                    <th>Sup.</th>
                                </tr>
                            </thead>
                            <tbody>
                            @if(isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes'][0]))
                                @foreach($infoAvaluo['Calculo_Valor_Construcciones']['Comunes'] as $value_valorConstruccionesC)
                                    <tr>
                                        <td>
                                        @isset($value_valorConstruccionesC['Tipo'])
                                            <span class="grises">{{ $value_valorConstruccionesC['Tipo'] }}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_valorConstruccionesC['Descripcion'])
                                            <span class="grises">{{ $value_valorConstruccionesC['Descripcion'] }}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_valorConstruccionesC['Uso'])
                                            <span class="grises">{{ $value_valorConstruccionesC['Uso'] }}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_valorConstruccionesC['Numero_Niveles_Tipo'])
                                            <span class="grises">{{ $value_valorConstruccionesC['Numero_Niveles_Tipo'] }}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_valorConstruccionesC['Clave_Rango_Niveles'])
                                            <span class="grises">{{ $value_valorConstruccionesC['Clave_Rango_Niveles'] }}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_valorConstruccionesC['Clase'])
                                            <span class="grises">{{ $value_valorConstruccionesC['Clase'] }}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_valorConstruccionesC['Valor_Unitario'])
                                            <span class="grises">{{ number_format($value_valorConstruccionesC['Valor_Unitario'],2) }}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_valorConstruccionesC['Depreciacion_Por_Edad'])
                                            <span class="grises">{{ number_format($value_valorConstruccionesC['Depreciacion_Por_Edad'],2) }}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_valorConstruccionesC['Valor'])
                                            <span class="grises">{{ number_format($value_valorConstruccionesC['Valor'],2) }}</span>
                                        @endisset
                                        </td>
                                        <td>
                                        @isset($value_valorConstruccionesC['Sup'])
                                            <span class="grises">{{ number_format($value_valorConstruccionesC['Sup'],2) }}</span>
                                        @endisset
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Tipo'])
                                        <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Tipo'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Descripcion'])
                                        <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Descripcion'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Uso'])
                                        <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Uso'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Numero_Niveles_Tipo'])
                                        <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Numero_Niveles_Tipo'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Clave_Rango_Niveles'])
                                        <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Clave_Rango_Niveles'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Clase'])
                                        <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Clase'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Valor_Unitario'])
                                        <span class="grises">{{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Valor_Unitario'],2) }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Depreciacion_Por_Edad'])
                                        <span class="grises">{{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Depreciacion_Por_Edad'],2) }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Valor'])
                                        <span class="grises">{{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Valor'],2) }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Sup'])
                                        <span class="grises">{{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Comunes']['Sup'],2) }}</span>
                                    @endisset
                                    </td>
                                </tr>
                            @endif
                                <tr>
                                    <td colspan="10" style="text-align: right; padding-right: 10px;">
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Superficie'])
                                        SUPERFICIE TOTAL DE LAS CONSTRUCCIONES COMUNES: <span class="grises">{{ $infoAvaluo['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Superficie'] }}</span>
                                    @endisset
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <!-- <table class="tabla_gris_valor">
                            <thead>
                                <tr>
                                    <th style="width: 80%;">VALOR TOTAL DE LAS CONTRUCCIONES:</th>
                                    <th>
                                    @isset($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes_Por_Indiviso'])
                                        <span class="grises">$ {{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes_Por_Indiviso'],2) }}</span>
                                    @endisset
                                    </th>
                                </tr>
                            </thead> 
                        </table> -->
                    @endif
                    <br>
                    <table class="tabla_gris_valor">
                        <thead>
                            <tr>
                                <th style="width: 80%;">VALOR TOTAL DE LAS CONSTRUCCIONES COMUNES:</th>
                                <th>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes'],2) }}</span>
                                @endisset
                                </th>
                            </tr>
                        </thead> 
                    </table>
                    <table class="tabla_gris_valor">
                        <thead>
                            <tr>
                                <th style="width: 80%;">VALOR TOTAL DE LAS CONSTRUCCIONES COMUNES POR INDIVISO:</th>
                                <th>
                                @isset($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes_Por_Indiviso'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Calculo_Valor_Construcciones']['Totales_Comunes']['Total_Construcciones_Comunes_Por_Indiviso'],2) }}</span>
                                @endisset
                                </th>
                            </tr>
                        </thead> 
                    </table>
                @endif

                    
                    <table class="tabla_gris_valor">
                        <thead>
                            <tr>
                                <th style="width: 80%;">IMPORTE INSTALACIONES ESPECIALES, ELEMENTOS ACCESORIOS Y OBRAS COMP.</th>
                                <th>
                                @isset($infoAvaluo['Importe_Instalaciones_Especiales_Elementos_Accesorios_Obras_Comp'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Importe_Instalaciones_Especiales_Elementos_Accesorios_Obras_Comp'],2) }}</span>
                                @endisset
                                </th>
                            </tr>
                        </thead> 
                    </table>

                    <table class="tabla_gris_valor">
                        <thead>
                            <tr>
                                <th style="width: 80%;">IMPORTE TOTAL DEL VALOR CATASTRAL:</th>
                                <th>
                                @isset($infoAvaluo['Importe_Total_Valor_Catastral'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Importe_Total_Valor_Catastral'],2) }}</span>
                                @endisset
                                </th>
                            </tr>
                        </thead> 
                    </table>

                    <table class="tabla_gris_valor">
                        <thead>
                            <tr>
                                <th style="width: 80%;">AVANCE OBRA:</th>
                                <th>
                                @isset($infoAvaluo['Avance_Obra'])
                                    <span class="grises"> {{ number_format($infoAvaluo['Avance_Obra'],2) }}%</span>
                                @endisset
                                </th>
                            </tr>
                        </thead> 
                    </table>

                    <table class="tabla_gris_valor">
                        <thead>
                            <tr>
                                <th style="width: 80%;">IMPORTE TOTAL DEL VALOR CATASTRAL OBRA EN PROCESO:</th>
                                <th>
                                @isset($infoAvaluo['Importe_Total_Valor_Catastral_Obra_Proceso'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Importe_Total_Valor_Catastral_Obra_Proceso'],2) }}</span>
                                @endisset
                                </th>
                            </tr>
                        </thead> 
                    </table>

                <!-- 9.- Índice de Capitalización de Rentas -->
                <!-- <div class="pleca_verde"><b>IX.- INDICE DE CAPITALIZACIÓN DE RENTAS</b></div> -->
                @if(isset($infoAvaluo['Renta_Estimada']))
                    <p><b>RENTA ESTIMADA</b></p>
                    <table class="tabla_cabeza_gris">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Ubicación</th>
                                <th>Superficie (m2)</th>
                                <th>Renta Mensual</th>
                                <th>Renta por m2</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $i_EA = 1;
                        @endphp
                        @if(isset($infoAvaluo['Renta_Estimada'][0]))
                            @foreach($infoAvaluo['Renta_Estimada'] as $valueEA_tablaPri)
                                <tr>
                                    <td><span class="grises">{{ $i_EA++ }}</td></span>
                                    <td>
                                    @isset($valueEA_tablaPri['Ubicacion'])                                
                                        <span class="grises">{{ $valueEA_tablaPri['Ubicacion'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @isset($valueEA_tablaPri['Superficie_m2'])                                
                                        <span class="grises">{{ $valueEA_tablaPri['Superficie_m2'] }}</span>
                                    @endisset
                                    </td>
                                    <td>
                                    @if(isset($valueEA_tablaPri['Renta_Mensual']))
                                        @if(!is_array($valueEA_tablaPri['Renta_Mensual']))
                                            <span class="grises">$ {{ number_format($valueEA_tablaPri['Renta_Mensual'],2) }}</span>
                                        @endif
                                    @endif
                                    </td>
                                    <td>
                                    @isset($valueEA_tablaPri['Renta_m2'])                                
                                        <span class="grises">$ {{ number_format($valueEA_tablaPri['Renta_m2'],2) }}</span>
                                    @endisset
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>
                                @isset($infoAvaluo['Renta_Estimada'])
                                    1
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Renta_Estimada']['Ubicacion'])
                                    <span class="grises">{{ $infoAvaluo['Renta_Estimada']['Ubicacion'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Renta_Estimada']['Superficie_m2'])
                                    <span class="grises">{{ $infoAvaluo['Renta_Estimada']['Superficie_m2'] }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Renta_Estimada']['Renta_Mensual'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Renta_Estimada']['Renta_Mensual'],2) }}</span>
                                @endisset
                                </td>
                                <td>
                                @isset($infoAvaluo['Renta_Estimada']['Renta_m2'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Renta_Estimada']['Renta_m2'],2) }}</span>
                                @endisset
                                </td>
                            </tr>
                        @endisset
                        </tbody>
                    </table>
                    <br>
                @endif

                @if(isset($infoAvaluo['Analisis_Deducciones']['Totales']['Suma']))
                    <p><b>ANÁLISIS DE DEDUCCIONES:</b></p>
                    <table style="border-collapse: collapse; width: 100%; margin-bottom: 2%;">
                        <tr>
                            <td>
                                <table class="tabla_doble">
                                    <thead>
                                        <tr>
                                            <th>Concepto</th>
                                            <th>Monto ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>a) Vacíos:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Vacios'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Vacios'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>b) Impuesto predial:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Impuesto_Predial'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Impuesto_Predial'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>c) Servicio de agua:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Servicio_Agua'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Servicio_Agua'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>d) Conserv. y mant.:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Conserv_Mant'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Conserv_Mant'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>e) Administración:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Administracion'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Administracion'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>f) Energía eléctrica:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Energia_Electrica'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Energia_Electrica'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                            <td>
                                <table class="tabla_doble">
                                    <thead>
                                        <tr>
                                            <th>Concepto</th>
                                            <th>Monto ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>g) Seguros:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Seguros'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Seguros'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>h) Otros:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Otros'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Otros'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>i) Depreciación Fiscal:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Depreciacion_Fiscal'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Depreciacion_Fiscal'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>j) Deducc. Fiscales:</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Deducc_Fiscales'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Deducc_Fiscales'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>k) I.S.R.</td>
                                            <td>
                                            @isset($infoAvaluo['Analisis_Deducciones']['ISR'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['ISR'],2) }}</span>
                                            @endisset
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td><b>SUMA:</b></td>
                                            <td><b>
                                            @isset($infoAvaluo['Analisis_Deducciones']['Totales']['Suma'])
                                                <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Totales']['Suma'],2) }}</span>
                                            @endisset
                                            </b>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </td>
                        </tr>
                    </table>
                    
                    <table style="border-collapse: collapse; width: 100%;">
                        <tbody>
                            <tr>
                                <td><b>DEDUCCIONES MENSUALES:</b></td>
                                <td>
                                @isset($infoAvaluo['Analisis_Deducciones']['Totales']['Deducciones_Mensuales'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Totales']['Deducciones_Mensuales'],2) }}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>PRODUCTO LIQUIDO MENSUAL:</b></td>
                                <td>
                                @isset($infoAvaluo['Analisis_Deducciones']['Totales']['Producto_Liquido_Mensual'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Totales']['Producto_Liquido_Mensual'],2) }}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>PRODUCTO LIQUIDO ANUAL:</b></td>
                                <td>
                                @isset($infoAvaluo['Analisis_Deducciones']['Totales']['Producto_Liquido_Anual'])
                                    <span class="grises">$ {{ number_format($infoAvaluo['Analisis_Deducciones']['Totales']['Producto_Liquido_Anual'],2) }}</span>
                                @endisset
                                </td>
                            </tr>
                            <tr>
                                <td><b>TASA DE CAPITALIZACIÓN APLICALE:</b></td>
                                <td>
                                @isset($infoAvaluo['Analisis_Deducciones']['Totales']['Tasa_Capitalizacion_Aplicable'])
                                    <span class="grises">{{ number_format($infoAvaluo['Analisis_Deducciones']['Totales']['Tasa_Capitalizacion_Aplicable'],2) }}%</span>
                                @endisset
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p><span>La tasa de capitalización aplicable aquí referida deberá ser justificada en el apartado de consideraciones propias.</span></p>

                    <table class="tabla_gris_valor">
                        <thead>
                            <tr>
                                <th style="width: 80%; font-size: 10px!important;">RESULTADO DE LA APLICACIÓN DEL ENFOQUE DE INGRESOS (VALOR POR CAPITALIZACIÓN DE RENTAS):</th>
                                <th>
                                @isset($infoAvaluo['Resultado_Aplicacion_Enfoque_Ingresos'])                                
                                    <span class="grises">$ number_format($infoAvaluo['Resultado_Aplicacion_Enfoque_Ingresos'],2) }}</span>
                                @endisset
                                </th>
                            </tr>
                        </thead> 
                    </table>
                @endif
                
                <!-- 10.- Resumen de Valores -->
                <div class="pleca_verde"><b>VIII.- RESUMEN DE VALORES</b></div>

                <table class="tabla_gris_valor">
                    <thead>
                        <tr>
                            <th style="width: 80%;">IMPORTE TOTAL DEL VALOR CATASTRAL:</th>
                            <th>
                            @isset($infoAvaluo['Importe_Total_Valor_Catastral'])
                                <span class="grises">$ {{ number_format($infoAvaluo['Importe_Total_Valor_Catastral'],2) }}</span>
                            @endisset
                            </th>
                        </tr>
                    </thead> 
                </table>

                <table class="tabla_gris_valor">
                    <thead>
                        <tr>
                            <th style="width: 80%;">IMPORTE TOTAL DEL VALOR CATASTRAL OBRA EN PROCESO:</th>
                            <th>
                            @isset($infoAvaluo['Importe_Total_Valor_Catastral_Obra_Proceso'])
                                <span class="grises">$ {{ number_format($infoAvaluo['Importe_Total_Valor_Catastral_Obra_Proceso'],2) }}</span>
                            @endisset
                            </th>
                        </tr>
                    </thead> 
                </table>

                <!-- 11.- Consideraciones Previas a la Conclusión -->
                <div class="pleca_verde"><b>IX.- CONSIDERACIONES PREVIAS A LA CONCLUSIÓN</b></div>

                    <p class="letras_pequenas">
                        @if(isset($infoAvaluo['Consideraciones']))
                            @if(!is_array($infoAvaluo['Consideraciones']))
                                <span class="grises">{{ $infoAvaluo['Consideraciones'] }}</span>
                            @endif
                        @endif
                    </p>

                
                <!-- 12.- Conclusiones sobre el Valor Comercial -->
                <div class="pleca_verde"><b>X.- CONCLUSIONES SOBRE EL VALOR COMERCIAL</b></div>
                <table class="tabla_gris_valor">
                    <thead>
                        <tr>
                        <th style="width: 80%;">CONSIDERAMOS QUE EL VALOR CATASTRAL CORRESPONDE A:</th>
                            <th>
                            @isset($infoAvaluo['Consideramos_Que_Valor_Catastral_Corresponde'])
                                <span class="grises">$ {{ number_format($infoAvaluo['Consideramos_Que_Valor_Catastral_Corresponde'],2) }}</span>
                            @endisset
                            </th>
                        </tr>
                    </thead> 
                </table>
                <br>
                <!-- <p><span>Esta cantidad estimamos que representa el valor comercial del inmueble al día:</span></p>
                <table class="tabla_gris_valor_no_bold">
                    <thead>
                        <tr>
                            <th>VALOR REFERIDO: 
                            @isset($infoAvaluo['Valor_Referido']['Valor_Referido'])
                                <span class="grises">$ {{ number_format($infoAvaluo['Valor_Referido']['Valor_Referido'],2)}}</span>
                            @endisset
                            </th>
                            <th>FECHA: 
                            @isset($infoAvaluo['Valor_Referido']['Fecha'])
                                <span class="grises">{{ $infoAvaluo['Valor_Referido']['Fecha'] }}</span>
                            @endisset
                            </th>
                            <th>FACTOR:
                            @isset($infoAvaluo['Valor_Referido']['Factor'])
                                <span class="grises">{{ $infoAvaluo['Valor_Referido']['Factor']}}</span>
                            @endisset
                            </th>
                        </tr>
                    </thead>
                </table> -->
                <br>
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="border-top: 2px solid #000;">Perito valuador:
                            @isset($infoAvaluo['Perito_Valuador'])
                                <span class="grises">{{ $infoAvaluo['Perito_Valuador'] }}</span>
                            @endisset
                            </th>
                            <th style="width: 5%;"></th>
                            <th style="border-top: 2px solid #000;">Registro T.D.F.:
                            @isset($infoAvaluo['Registro_TDF'])
                                <span class="grises">{{ $infoAvaluo['Registro_TDF'] }}</span>
                            @endisset
                            </th>
                        </tr>
                    </thead>
                </table>
                <br>
                <div class="pleca_gris"><b>ANEXO FOTOGRÁFICO SUJETO</b></div>
                <br>
                <h5 class="subtitulo_anexo_fotografico"><b>INMUEBLE OBJETO DE ESTE AVALÚO</b></h5>
                <table style="width: 100%" style="border-collapse: separate; border-spacing: 10px 5px;">
                    @isset($infoAvaluo['Inmueble_Objeto_Avaluo'])
                        @foreach($infoAvaluo['Inmueble_Objeto_Avaluo'] as $value_inmuebleOA)
                            @if($loop->iteration & 1)
                                <tr>
                                    <td style="width: 50%; text-align:center">
                                        <div>
                                            <img src="data:image/png;base64,{{$value_inmuebleOA['Foto']}}" class="fotos" />
                                        </div>
                                        <div class="container2">
                                            <button class="pie_de_foto">Cuenta: <span class="grises" style="padding-right: 15px;">{{ $value_inmuebleOA['Cuenta_Catastral'] }} </span> @if($value_inmuebleOA['Interior_O_Exterior'] == 'E') Exterior @else Interior @endif </button>
                                        </div>
                                    </td>
                                @if(count($infoAvaluo['Inmueble_Objeto_Avaluo']) < 2)
                                    <td style="width: 50%!important; text-align:center">
                                    </td>
                                @endif
                            @else
                                    <td style="width: 50%; text-align:center">
                                        <div>
                                            <img src="data:image/png;base64,{{$value_inmuebleOA['Foto']}}" class="fotos" />
                                        </div>
                                        <div class="container2">
                                            <button class="pie_de_foto">Cuenta: <span class="grises" style="padding-right: 15px;">{{ $value_inmuebleOA['Cuenta_Catastral'] }} </span> @if($value_inmuebleOA['Interior_O_Exterior'] == 'E') Exterior @else Interior @endif </button>
                                        </div>
                                    </td>
                                @if(count($infoAvaluo['Inmueble_Objeto_Avaluo']) < 2)
                                    <td style="width: 50%!important; text-align:center">
                                    </td>
                                @endif
                                </tr>
                            @endif
                        @endforeach
                    @endisset
                </table>
                <br>
                <!-- <div class="pleca_gris"><b>ANEXO FOTOGRÁFICO COMPARABLES</b></div>
                <h5 class="subtitulo_anexo_fotografico"><b>INMUEBLES EN VENTA</b></h5>
                <table style="width: 100%" style="border-collapse: separate; border-spacing: 10px 5px; margin-top: 5%;">
                @isset($infoAvaluo['Inmueble_Venta'])    
                    @foreach($infoAvaluo['Inmueble_Venta'] as $value_inmuebleEV)
                        @if($loop->iteration & 1)
                            <tr>
                                <td style="width: 50%; text-align:center">
                                    <div>
                                        <img src="data:image/png;base64,{{$value_inmuebleEV['Foto']}}" class="fotos" />
                                    </div>
                                    <div class="container2">
                                        <button class="pie_de_foto">Cuenta: <span class="grises" style="padding-right: 15px;">{{ $value_inmuebleEV['Cuenta_Catastral'] }} </span> @if($value_inmuebleEV['Interior_O_Exterior'] == 'E') Exterior @else Interior @endif </button> 
                                    </div>
                                </td>
                                @if(count($infoAvaluo['Inmueble_Venta']) < 2)
                                    <td style="width: 50%!important; text-align:center">
                                    </td>
                                @endif
                        @else
                                <td style="width: 50%; text-align:center">
                                    <div class="card">
                                        <img src="data:image/png;base64,{{$value_inmuebleEV['Foto']}}" class="fotos" />
                                    </div>
                                    <div class="container2">
                                        <button class="pie_de_foto">Cuenta: <span class="grises" style="padding-right: 15px;">{{ $value_inmuebleEV['Cuenta_Catastral'] }} </span> @if($value_inmuebleEV['Interior_O_Exterior'] == 'E') Exterior @else Interior @endif </button>
                                    </div>
                                </td>
                                @if(count($infoAvaluo['Inmueble_Venta']) < 2)
                                    <td style="width: 50%!important; text-align:center">
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @endforeach
                @endisset
                </table> -->
                <!-- <h5 class="subtitulo_anexo_fotografico"><b>INMUEBLES EN RENTA</b></h5>
                <table style="width: 100%" style="border-collapse: separate; border-spacing: 10px 5px; margin-top: 5%;">
                @isset($infoAvaluo['Inmueble_Renta'])    
                    @foreach($infoAvaluo['Inmueble_Renta'] as $value_inmuebleR)
                        @if($loop->iteration & 1)
                            <tr>
                                <td style="width: 50%; text-align:center">
                                    <div>
                                        <img src="data:image/png;base64,{{$value_inmuebleR['Foto']}}" class="fotos" />
                                    </div>
                                    <div class="container2">
                                        <button class="pie_de_foto">Cuenta: <span class="grises" style="padding-right: 15px;">{{ $value_inmuebleR['Cuenta_Catastral'] }} </span> @if($value_inmuebleR['Interior_O_Exterior'] == 'E') Exterior @else Interior @endif </button>
                                    </div>
                                </td>
                                @if(count($infoAvaluo['Inmueble_Renta']) < 2)
                                    <td style="width: 50%!important; text-align:center">
                                    </td>
                                @endif
                        @else
                                <td style="width: 50%; text-align:center">
                                    <div>
                                        <img src="data:image/png;base64,{{$value_inmuebleR['Foto']}}" class="fotos" />
                                    </div>
                                    <div class="container2">
                                        <button class="pie_de_foto">Cuenta: <span class="grises" style="padding-right: 15px;">{{ $value_inmuebleR['Cuenta_Catastral'] }} </span> @if($value_inmuebleR['Interior_O_Exterior'] == 'E') Exterior @else Interior @endif </button>
                                    </div>
                                </td>
                                @if(count($infoAvaluo['Inmueble_Renta']) < 2)
                                    <td style="width: 50%!important; text-align:center">
                                    </td>
                                @endif
                            </tr>
                        @endif
                    @endforeach
                @endisset
                </table> -->
            </div>
        </div> 
        <!-- Fin de CONTENIDO -->

    </body>

</html>