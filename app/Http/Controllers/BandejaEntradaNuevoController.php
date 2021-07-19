<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Crypt;
use App\Models\PeritoSociedad;
use App\Models\DatosExtrasAvaluo;
use App\Models\Documentos;
use App\Models\ElementosConstruccion;
use App\Models\GuardaenBD;
use App\Models\Ava;
use App\Models\Fis;
use App\Models\Reimpresion;
use App\Models\ReimpresionNuevo;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Hamcrest\Arrays\IsArray;
use Log;

class BandejaEntradaNuevoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    protected $modelPeritoSociedad;
    protected $modelDatosExtrasAvaluo;
    protected $modelDocumentos;
    protected $modelElementosConstruccion;
    protected $modelGuardaenBD;
    protected $modelAva;
    protected $modelFis;
    protected $modelReimpresion;
    protected $modelReimpresionNuevo;
    private $errors;
    private $doc;
    private $fileXML;

    public function __construct()
    {
        //
    }

    public function avaluos(Request $request)
    { //error_log("EN AVALUOS ".json_encode($request)); //echo "<pre>"; print_r($request); exit();
        try {
            $fechaIni = $request->query('fecha_ini');
            $fechaFin = $request->query('fecha_fin');
            $noAvaluo = $request->query('no_avaluo');
            $noUnico = $request->query('no_unico');
            $codEstado = $request->query('estado');
            $idPerito = $request->query('id_perito');
            $idSociedad = $request->query('id_sociedad');
            //$codEstado = $request->query('estado');
            $ctaCatastral = $request->query('cta_catastral');
            $vigencia = $request->query('vigencia');
            $table = DB::table('FEXAVA_AVALUO');
            $table->join('FEXAVA_CATESTADOSAVALUO', 'FEXAVA_AVALUO.codestadoavaluo', '=', 'FEXAVA_CATESTADOSAVALUO.codestadoavaluo');
            $table->join('DOC.DOC_DOCUMENTODIGITAL', 'FEXAVA_AVALUO.idavaluo', '=', 'DOC.DOC_DOCUMENTODIGITAL.iddocumentodigital');
            $table->leftJoin('RCON.RCON_PERITO', 'FEXAVA_AVALUO.idpersonaperito', '=', 'RCON.RCON_PERITO.idpersona');
            $table->leftJoin('RCON.RCON_NOTARIO', 'FEXAVA_AVALUO.idpersonanotario', '=', 'RCON.RCON_NOTARIO.idpersona');

            $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            } 
            $resToken = Crypt::decrypt($authToken);
            //Log::info($request);
            
            //$table->join('RCON.RCON_SOCIEDADVALUACION', 'FEXAVA_AVALUO.idpersonasociedad', '=', 'RCON.RCON_SOCIEDADVALUACION.idpersona');
            //$table->join('RCON.RCON_NOTARIO', 'FEXAVA_AVALUO.idpersonanotario', '=', 'RCON.RCON_NOTARIO.idpersona');
            $table->select(
                DB::raw('TRIM(FEXAVA_AVALUO.numerounico) as numerounico'),
                'FEXAVA_AVALUO.region',
                'FEXAVA_AVALUO.manzana',
                'FEXAVA_AVALUO.lote',
                'FEXAVA_AVALUO.unidadprivativa',
                'FEXAVA_AVALUO.fecha_presentacion',
                'FEXAVA_AVALUO.numeroavaluo',
                'FEXAVA_CATESTADOSAVALUO.descripcion as estadoavaluo',
                'RCON.RCON_PERITO.registro as perito',
                'RCON.RCON_NOTARIO.NUMNOTARIO as notario',
                'FEXAVA_AVALUO.proposito',
                'FEXAVA_AVALUO.objeto',
                'DOC.DOC_DOCUMENTODIGITAL.fecha as fecha_avaluo',

                //'RCON.RCON_SOCIEDADVALUACION.registro as sociedad',
                //'RCON.RCON_NOTARIO.NUMNOTARIO as notario',
                DB::raw("CASE
                            WHEN FEXAVA_AVALUO.codtipotramite = '1' 
                                THEN 'COM'
                                ELSE 'CAT'
                        END as tipotramite"),
                
            );
            
            if ($fechaIni && $fechaFin) {
                $fi = new Carbon($fechaIni);
                $ff = new Carbon($fechaFin);
                if(isset($vigencia) &&  $vigencia == 2){
                    $year = Carbon::today()->subYear();
                    if(isset($fi) && $year->lt($fi)){
                        $ff = $ff->addDays(1);
                        $table->whereBetween('FEXAVA_AVALUO.fecha_presentacion', [$fi->format('Y-m-d'), $ff->format('Y-m-d')]);
                    }else{
                        $year = $year->addDays(1);
                        $table->whereBetween('FEXAVA_AVALUO.fecha_presentacion', [$fi->format('Y-m-d'), $year->format('Y-m-d')]);
                    }
                }else{
                    $ff = $ff->addDays(1);
                    $table->whereBetween('FEXAVA_AVALUO.fecha_presentacion', [$fi->format('Y-m-d'), $ff->format('Y-m-d')]);  
                }                
                
            }

            if ($noAvaluo) {
                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.numeroavaluo)'), $noAvaluo);
            }
                        
            if ($vigencia == 1) {
                $year = Carbon::today()->subYear();
                $table->where('FEXAVA_AVALUO.fecha_presentacion','>=',$year->format('Y-m-d'));
                // 6 es el estatus enviado notario
                //$table->where('FEXAVA_AVALUO.codestadoavaluo',6);
                $table->whereIn('FEXAVA_AVALUO.codestadoavaluo',array(6,1));
            }  

            if ($vigencia == 2) {
                $year = Carbon::today()->subYear();
                if(isset($fi) && $year->lt($fi)){
                    $table->where('FEXAVA_AVALUO.codestadoavaluo',2);
                }
                elseif(isset($fi) && $year->gt($fi)){

                    //$idPerito = empty($resToken['id_anterior']) ? $resToken['id_usuario']: $resToken['id_anterior'];
                    $idPerito = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona'];

                    /*if ($idPerito) {
                        $table->where('FEXAVA_AVALUO.idpersonaperito', $idPerito);
                    }
        
                    if ($idSociedad) {
                        $table->where('FEXAVA_AVALUO.idpersonasociedad', $idSociedad);
                    } */
                    $table->orWhere('FEXAVA_AVALUO.fecha_presentacion','>',$year->format('Y-m-d'));
                    $table->where('FEXAVA_AVALUO.fecha_presentacion','<=',$ff->format('Y-m-d'));
                    $table->where('FEXAVA_AVALUO.codestadoavaluo',2);
                }else{                                      
                    $table->where('FEXAVA_AVALUO.fecha_presentacion','<',$year->format('Y-m-d'));
                    if($ctaCatastral){
                        $cta = explode('-', $ctaCatastral);
                        if (count($cta) === 4) {
                            if(trim($cta[0]) != ''){
                                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.region)'), $cta[0]);
                            }
                            if(trim($cta[1] != '')){
                                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.manzana)'), $cta[1]);
                            }
                            if(trim($cta[2]) != ''){
                                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.lote)'), $cta[2]);
                            }
                            if(trim($cta[3])){
                                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.unidadprivativa)'), $cta[3]);
                            }
                            /*$table->where(DB::raw('TRIM(FEXAVA_AVALUO.region)'), $cta[0]);
                            $table->where(DB::raw('TRIM(FEXAVA_AVALUO.manzana)'), $cta[1]);
                            $table->where(DB::raw('TRIM(FEXAVA_AVALUO.lote)'), $cta[2]);
                            $table->where(DB::raw('TRIM(FEXAVA_AVALUO.unidadprivativa)'), $cta[3]);*/
                        } else {
                            return response()->json(['mensaje' => 'Formato de cuenta predial incorrecta'], 400);
                        }
                        //$idPerito = empty($resToken['id_anterior']) ? $resToken['id_usuario']: $resToken['id_anterior'];
                        $idPerito = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona'];
                        
                        /*if ($idPerito) {
                            $table->where('FEXAVA_AVALUO.idpersonaperito', $idPerito);
                        }*/
                    }
                    // 2 es el estatus cancelado
                    $table->orWhere('FEXAVA_AVALUO.codestadoavaluo',2);        
                }
                
            }

            //$idPerito = empty($resToken['id_anterior']) ? $resToken['id_usuario']: $resToken['id_anterior'];
            $idPerito = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona'];

            /* if ($idPerito) {
                $table->where('FEXAVA_AVALUO.idpersonaperito', $idPerito);
            } */

            if ($idSociedad) {
                $table->where('FEXAVA_AVALUO.idpersonasociedad', $idSociedad);
            }
            

            if ($noUnico) {
                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.numerounico)'), $noUnico);
            }

            if ($codEstado) {
                $table->where('FEXAVA_AVALUO.codestadoavaluo', $codEstado);
            }

            if ($ctaCatastral) {                
                $cta = explode('-', $ctaCatastral);
                if (count($cta) === 4) {
                    if(trim($cta[0]) != ''){
                        $table->where(DB::raw('TRIM(FEXAVA_AVALUO.region)'), $cta[0]);
                    }
                    if(trim($cta[1] != '')){
                        $table->where(DB::raw('TRIM(FEXAVA_AVALUO.manzana)'), $cta[1]);
                    }
                    if(trim($cta[2]) != ''){
                        $table->where(DB::raw('TRIM(FEXAVA_AVALUO.lote)'), $cta[2]);
                    }
                    if(trim($cta[3])){
                        $table->where(DB::raw('TRIM(FEXAVA_AVALUO.unidadprivativa)'), $cta[3]);
                    }
                    
                } else {
                    return response()->json(['mensaje' => 'Formato de cuenta predial incorrecta'], 400);
                }
            }
            $table->orderBy('FEXAVA_AVALUO.fecha_presentacion' , 'desc');
            $avaluos = $table->paginate(15);
            return response()->json($avaluos, 200);
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '1. Error en el servidor'], 500);
        }
    }

    public function avaluosPerito(Request $request)
    { //error_log("EN PERITO AVALUOS  ".json_encode($request));
        try {
            
            $fechaIni = $request->query('fecha_ini');
            $fechaFin = $request->query('fecha_fin');
            $noAvaluo = $request->query('no_avaluo');
            $noUnico = $request->query('no_unico');
            //$codEstado = $request->query('estado');
            $idPerito = $request->query('id_perito');
            $idSociedad = $request->query('id_sociedad');
            $codEstado = $request->query('estado');
            $ctaCatastral = $request->query('cta_catastral');
            $vigencia = $request->query('vigencia');
            $table = DB::table('FEXAVA_AVALUO');            
            $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            } 
            $resToken = Crypt::decrypt($authToken);
            $table->join('FEXAVA_CATESTADOSAVALUO', 'FEXAVA_AVALUO.codestadoavaluo', '=', 'FEXAVA_CATESTADOSAVALUO.codestadoavaluo');
            $table->join('RCON.RCON_PERITO', 'FEXAVA_AVALUO.idpersonaperito', '=', 'RCON.RCON_PERITO.idpersona');
            $table->join('DOC.DOC_DOCUMENTODIGITAL', 'FEXAVA_AVALUO.idavaluo', '=', 'DOC.DOC_DOCUMENTODIGITAL.iddocumentodigital');
            $table->leftJoin('RCON.RCON_NOTARIO', 'FEXAVA_AVALUO.idpersonanotario', '=', 'RCON.RCON_NOTARIO.idpersona');    
            
            //$table->join('RCON.RCON_SOCIEDADVALUACION', 'FEXAVA_AVALUO.idpersonasociedad', '=', 'RCON.RCON_SOCIEDADVALUACION.idpersona');
            //$table->join('RCON.RCON_NOTARIO', 'FEXAVA_AVALUO.idpersonanotario', '=', 'RCON.RCON_NOTARIO.idpersona');
            $table->select(
                DB::raw('TRIM(FEXAVA_AVALUO.numerounico) as numerounico'),
                'FEXAVA_AVALUO.region',
                'FEXAVA_AVALUO.manzana',
                'FEXAVA_AVALUO.lote',
                'FEXAVA_AVALUO.unidadprivativa',
                'FEXAVA_AVALUO.fecha_presentacion',
                'FEXAVA_AVALUO.numeroavaluo',
                'FEXAVA_CATESTADOSAVALUO.descripcion as estadoavaluo',
                'RCON.RCON_PERITO.registro as perito',
                'RCON.RCON_NOTARIO.NUMNOTARIO as notario',
                'FEXAVA_AVALUO.proposito',
                'FEXAVA_AVALUO.objeto',
                'DOC.DOC_DOCUMENTODIGITAL.fecha as fecha_avaluo',
                //'RCON.RCON_SOCIEDADVALUACION.registro as sociedad',
                //'RCON.RCON_NOTARIO.NUMNOTARIO as notario',
                DB::raw("CASE
                            WHEN FEXAVA_AVALUO.codtipotramite = '1' 
                                THEN 'COM'
                                ELSE 'CAT'
                        END as tipotramite"),
                
            );

            if ($fechaIni && $fechaFin) {
                $fi = new Carbon($fechaIni);
                $ff = new Carbon($fechaFin);
                if(isset($vigencia) &&  $vigencia == 2){
                    $year = Carbon::today()->subYear();
                    if(isset($fi) && $year->lt($fi)){
                        $ff = $ff->addDays(1);
                        $table->whereBetween('FEXAVA_AVALUO.fecha_presentacion', [$fi->format('Y-m-d'), $ff->format('Y-m-d')]);
                    }else{
                        $year = $year->addDays(1);
                        $table->whereBetween('FEXAVA_AVALUO.fecha_presentacion', [$fi->format('Y-m-d'), $year->format('Y-m-d')]);
                    }
                }else{
                    $ff = $ff->addDays(1);
                    $table->whereBetween('FEXAVA_AVALUO.fecha_presentacion', [$fi->format('Y-m-d'), $ff->format('Y-m-d')]);  
                }                
                
            }

            if ($noAvaluo) {
                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.numeroavaluo)'), $noAvaluo);
            }
            
            if ($vigencia == 1) {
                $year = Carbon::today()->subYear();
                $table->where('FEXAVA_AVALUO.fecha_presentacion','>=',$year->format('Y-m-d'));
                // 6 es el estatus enviado notario
                //$table->where('FEXAVA_AVALUO.codestadoavaluo',6);
                $table->whereIn('FEXAVA_AVALUO.codestadoavaluo',array(6,1));
            }
            if ($vigencia == 2) {
                $year = Carbon::today()->subYear();
                if(isset($fi) && $year->lt($fi)){
                    $table->where('FEXAVA_AVALUO.codestadoavaluo',2);
                }
                elseif(isset($fi) && $year->gt($fi)){

                    //$idPerito = 264; 
                    $idPerito = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona'];
                    
                    if ($idPerito) {
                        $table->where('FEXAVA_AVALUO.idpersonaperito', $idPerito);
                    }
        
                    if ($idSociedad) {
                        $table->where('FEXAVA_AVALUO.idpersonasociedad', $idSociedad);
                    }
                    $table->orWhere('FEXAVAA_AVALUO.fecha_presentacion','>',$year->format('Y-m-d'));
                    $table->where('FEXAVA_AVALUO.fecha_presentacion','<=',$ff->format('Y-m-d'));
                    $table->where('FEXAVA_AVALUO.codestadoavaluo',2);
                }else{                                      
                    $table->where('FEXAVA_AVALUO.fecha_presentacion','<',$year->format('Y-m-d'));
                    
                    if($ctaCatastral){
                        $cta = explode('-', $ctaCatastral);
                        if (count($cta) === 4) {
                            if(trim($cta[0]) != ''){
                                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.region)'), $cta[0]);
                            }
                            if(trim($cta[1] != '')){
                                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.manzana)'), $cta[1]);
                            }
                            if(trim($cta[2]) != ''){
                                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.lote)'), $cta[2]);
                            }
                            if(trim($cta[3])){
                                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.unidadprivativa)'), $cta[3]);
                            }
                            /*$table->where(DB::raw('TRIM(FEXAVA_AVALUO.region)'), $cta[0]);
                            $table->where(DB::raw('TRIM(FEXAVA_AVALUO.manzana)'), $cta[1]);
                            $table->where(DB::raw('TRIM(FEXAVA_AVALUO.lote)'), $cta[2]);
                            $table->where(DB::raw('TRIM(FEXAVA_AVALUO.unidadprivativa)'), $cta[3]);*/
                        } else {
                            return response()->json(['mensaje' => 'Formato de cuenta predial incorrecta'], 400);
                        }
                        $idPerito = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona'];
                        
                        if ($idPerito) {
                            $table->where('FEXAVA_AVALUO.idpersonaperito', $idPerito);
                        }
                    }
                    // 2 es el estatus cancelado
                    $table->orWhere('FEXAVA_AVALUO.codestadoavaluo',2);        
                }
                
            }

            //$idPerito = 264;
            $idPerito = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona']; error_log("EL ID ANTERIOR ".$resToken['id_persona']." EL ID USUARIO ".$resToken['id_usuario']);
            
            //$table->where('FEXAVA_AVALUO.idpersonaperito', $resToken['id_anterior']);
            //COMENTADO PRQUE YA ESTA ABAJO $table->where('FEXAVA_AVALUO.idpersonaperito', $idPerito);
            
            if ($idPerito) {
                $table->where('FEXAVA_AVALUO.idpersonaperito', $idPerito);
            }

            if ($idSociedad) {
                $table->where('FEXAVA_AVALUO.idpersonasociedad', $idSociedad);
            }
            

            if ($noUnico) {
                $table->where(DB::raw('TRIM(FEXAVA_AVALUO.numerounico)'), $noUnico);
            }

            if ($codEstado) {
                $table->where('FEXAVA_AVALUO.codestadoavaluo', $codEstado);
            }
            
            if ($ctaCatastral) {                
                $cta = explode('-', $ctaCatastral);
                
                if (count($cta) === 4) {
                    if(trim($cta[0]) != ''){
                        $table->where(DB::raw('TRIM(FEXAVA_AVALUO.region)'), $cta[0]);
                    }
                    if(trim($cta[1]) != ''){
                        $table->where(DB::raw('TRIM(FEXAVA_AVALUO.manzana)'), $cta[1]);
                    }
                    if(trim($cta[2]) != ''){
                        $table->where(DB::raw('TRIM(FEXAVA_AVALUO.lote)'), $cta[2]);
                    }
                    if(trim($cta[3])){
                        $table->where(DB::raw('TRIM(FEXAVA_AVALUO.unidadprivativa)'), $cta[3]);
                    }
                    
                } else {
                    return response()->json(['mensaje' => 'Formato de cuenta predial incorrecta'], 400);
                }
            }
            $table->orderBy('FEXAVA_AVALUO.fecha_presentacion' , 'desc');
            $avaluos = $table->paginate(15);
            //print_r($avaluos); exit();
            return response()->json($avaluos, 200);
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '2. Error en el servidor'], 500);
        }
    }

    public function ModificarEstadoAvaluo(Request $request)
    {
        try {
            //Log::info("SOLITO: ".$request->query('no_unico'));
            $numero_unico = $request->query('no_unico');
            $this->modelDocumentos = new Documentos();
             
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico); //Log::info("IDAVALUO: ".$id_avaluo);
            //$id_avaluo = $request->query('id_avaluo');
            $code_estado_avaluo = $request->query('code_estado_avaluo');

            $procedure = 'BEGIN
            FEXAVA.FEXAVA_AVALUOS_PKG.FEXAVA_UPDATE_AVALUOS_ESTADO_P(
                :PAR_IDAVALUO,
                :PAR_CODESTADOAVALUO
            ); END;';
            $pdo = DB::getPdo();
            $stmt = $pdo->prepare($procedure);
            $stmt->bindParam(':PAR_IDAVALUO', $id_avaluo, \PDO::PARAM_INT);
            $stmt->bindParam(':PAR_CODESTADOAVALUO', $code_estado_avaluo, \PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            $pdo->commit();
            $pdo->close();
            DB::commit();
            DB::reconnect();
            return response()->json(['mensaje' => 'Estado Actualizado'], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '3. Error en el servidor'], 500);
        }
    }

    public function avaluosProximosRES(Request $request)
    {
        try{
            $authToken = $request->header('Authorization');
                if (!$authToken) {
                    return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
                } 
                $resToken = Crypt::decrypt($authToken);
                
                //$id_persona_perito = empty($resToken['id_anterior']) ? $resToken['id_usuario']: $resToken['id_anterior'];
                $id_persona_perito = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona'];
                
                $codestadoavaluo = $request->query('codestadoavaluo');

            $numero_unico = $request->query('no_unico');
            $this->modelDocumentos = new Documentos();
                
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);

            //print_r($request); exit();
            //$id_avaluo = $request->query('id_avaluo');
            //$id_persona_perito = $request->query('id_persona_perito');
            $page_size = $request->query('page_size');
            $page = $request->query('page');
            $sortexpression = 'IDAVALUO';

            $procedure = 'BEGIN        
                FEXAVA.FEXAVA_AVALUOS_PKG.FEXAVA_SEL_V_BUSQUEDA_PROXIMID(
                    :PAR_IDAVALUO,
                    :PAR_CODESTADOAVALUO,
                    :PAGE_SIZE,
                    :PAGE,
                    :SORTEXPRESSION,
                    :C_AVALUOS
                ); END;';
            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_IDAVALUO', $id_avaluo);
            oci_bind_by_name($stmt, ':PAR_CODESTADOAVALUO', $codestadoavaluo, 300);
            oci_bind_by_name($stmt, ':PAGE_SIZE', $page_size, 300);
            oci_bind_by_name($stmt, ':PAGE', $page, 300);
            oci_bind_by_name($stmt, ':SORTEXPRESSION', $sortexpression);
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUOS", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $avaluos, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor); //Log::info($avaluos);

            $offset = ($page * $page_size) - $page_size;
            $data = array_slice($avaluos, $offset, $page_size, true);
            $avaluos = new \Illuminate\Pagination\LengthAwarePaginator($data, count($data), $page_size, $page);

            if (count($avaluos) > 0) {
                return response()->json($avaluos, 200);
            } else {
                return response()->json(['mensaje' => 'No se encontraron registros'], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '4. Error en el servidor'], 500);
        }
    }

    public function avaluosProximos(Request $request)
    { 
        try{
            $authToken = $request->header('Authorization');
                if (!$authToken) {
                    return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
                } 
                $resToken = Crypt::decrypt($authToken);
                
                //$id_persona_perito = empty($resToken['id_anterior']) ? $resToken['id_usuario']: $resToken['id_anterior'];
                $id_persona_perito = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona']; 

            $numero_unico = $request->query('no_unico');
            $this->modelDocumentos = new Documentos();
                
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico); 
            $page_size = $request->query('page_size');
            $page = $request->query('page');
            $sortexpression = 'IDAVALUO';
            error_log($id_avaluo." ".$id_persona_perito." ".$page_size." ".$page." ".$sortexpression);
            //echo $id_avaluo." ".$id_persona_perito." ".$page_size." ".$page." ".$sortexpression; exit();
            $procedure = 'BEGIN
                FEXAVA.FEXAVA_AVALUOS_PKG.FEXAVA_SEL_V_PROXIMID_PERITO_P(
                    :PAR_IDAVALUO,
                    :PAR_IDPERSONAPERITO,
                    :PAGE_SIZE,
                    :PAGE,
                    :SORTEXPRESSION,
                    :C_AVALUOS
                ); END;';
            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_IDAVALUO', $id_avaluo);
            oci_bind_by_name($stmt, ':PAR_IDPERSONAPERITO', $id_persona_perito, 300);
            oci_bind_by_name($stmt, ':PAGE_SIZE', $page_size, 300);
            oci_bind_by_name($stmt, ':PAGE', $page, 300);
            oci_bind_by_name($stmt, ':SORTEXPRESSION', $sortexpression);
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUOS", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $avaluos, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor); //Log::info($avaluos);

            $offset = ($page * $page_size) - $page_size;
            $data = array_slice($avaluos, $offset, $page_size, true);
            $avaluos = new \Illuminate\Pagination\LengthAwarePaginator($data, count($data), $page_size, $page);

            if (count($avaluos) > 0) {
                return response()->json($avaluos, 200);
            } else {
                return response()->json(['mensaje' => 'No se encontraron registros'], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '5. Error en el servidor'], 500);
        }
    }

    public function avaluosProximosSoci(Request $request)
    {
        try{
            $authToken = $request->header('Authorization');
                if (!$authToken) {
                    return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
                } 
                $resToken = Crypt::decrypt($authToken);
                
                //$id_persona_sociedad = empty($resToken['id_anterior']) ? $resToken['id_usuario']: $resToken['id_anterior'];
                $id_persona_perito = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona']; 

            $numero_unico = $request->query('no_unico');
            $this->modelDocumentos = new Documentos();
                
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);
            $page_size = $request->query('page_size');
            $page = $request->query('page');
            $sortexpression = 'IDAVALUO';

            $procedure = 'BEGIN
                FEXAVA.FEXAVA_AVALUOS_PKG.FEXAVA_SEL_V_PROXIMID_SOCI_P(           
                    :PAR_IDAVALUO,
                    :PAR_IDPERSONASOCIEDAD,
                    :PAGE_SIZE,
                    :PAGE,
                    :SORTEXPRESSION,
                    :C_AVALUOS
                ); END;';
            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_ID_AVALUO', $id_avaluo);
            oci_bind_by_name($stmt, ':PAR_IDPERSONASOCIEDAD', $id_persona_sociedad, 300);
            oci_bind_by_name($stmt, ':PAGE_SIZE', $page_size, 300);
            oci_bind_by_name($stmt, ':PAGE', $page, 300);
            oci_bind_by_name($stmt, ':SORTEXPRESSION', $sortexpression);
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_AVALUOS", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $avaluos, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor); //Log::info($avaluos);

            $offset = ($page * $page_size) - $page_size;
            $data = array_slice($avaluos, $offset, $page_size, true);
            $avaluos = new \Illuminate\Pagination\LengthAwarePaginator($data, count($data), $page_size, $page);

            if (count($avaluos) > 0) {
                return response()->json($avaluos, 200);
            } else {
                return response()->json(['mensaje' => 'No se encontraron registros'], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '6. Error en el servidor'], 500);
        }
    }

    public function buscaNotario(Request $request)
    {
        try{
            /*echo "ENCODE ".mb_check_encoding($request->query('ape_paterno'),'UTF-8')." "; echo " ";
            print_r($request->query('ape_paterno')); exit();*/              
            $numero_notario = $request->query('numero_notario') == '' ? null : $request->query('numero_notario');
            $nombre_notario = $request->query('nombre_notario') == '' ? null : mb_convert_encoding($request->query('nombre_notario'),'ISO-8859-1','UTF-8');
            $ape_paterno = $request->query('ape_paterno') == '' ? null : mb_convert_encoding($request->query('ape_paterno'),'ISO-8859-1','UTF-8');
            $ape_materno = $request->query('ape_materno') == '' ? null : mb_convert_encoding($request->query('ape_materno'),'ISO-8859-1','UTF-8');
            $rfc = $request->query('rfc') == '' ? null : $request->query('rfc');
            $curp = $request->query('curp') == '' ? null : $request->query('curp');
            $claveife = $request->query('claveife') == '' ? null : $request->query('claveife');
            $page_size = $request->query('page_size') == '' ? 1 : $request->query('page_size');
            $page = $request->query('page') == '' ? 1 : $request->query('page');
            $sortexpression = $request->query('sortexpression') == '' ? 'NUMERO' : $request->query('sortexpression');
            
            //$convertida = mb_convert_encoding($ape_paterno,'ISO-8859-1','UTF-8');
            //echo $ape_paterno."\n"; exit();
            //$ape_paterno = "NU".mb_convert_encoding('Ñ','Windows-1252','UTF-8')."EZ";
            //echo $ape_paterno."\n"; exit();
            
            //echo "ENCODE ".mb_check_encoding($ape_paterno,'UTF-8')." "; echo "VALOR ";
            //$ape_paterno = mb_convert_encoding($ape_paterno,'UTF-32','UTF-8');
            //echo $ape_paterno; exit();

            $procedure = 'BEGIN
            FEXAVA.FEXAVA_NOTARIOS_PKG.fexava_select_notariosbuscar_p(
                :PAR_NUMERO,
                :PAR_NOMBRE,
                :PAR_APELLIDOPATERNO,
                :PAR_APELLIDOMATERNO,
                :PAR_RFC,
                :PAR_CURP,
                :PAR_CLAVEIFE,
                :PAGE_SIZE,
                :PAGE,
                :SORTEXPRESSION,
                :C_NOTARIOS
            ); END;';
            $conn = oci_connect(env("DB_USERNAME"), env("DB_PASSWORD"), env("DB_TNS"));
            //oci_execute(oci_parse($conn,"ALTER SESSION SET NLS_LANG=SPANISH_SPAIN.WE8MSWIN1252"));
            //oci_execute(oci_parse($conn,"ALTER SESSION SET NLS_LANGUAGE='SPANISH'"));
            $stmt = oci_parse($conn, $procedure);
            oci_bind_by_name($stmt, ':PAR_NUMERO', $numero_notario);
            oci_bind_by_name($stmt, ':PAR_NOMBRE', $nombre_notario, 300);
            oci_bind_by_name($stmt, ':PAR_APELLIDOPATERNO', $ape_paterno, 300);
            oci_bind_by_name($stmt, ':PAR_APELLIDOMATERNO', $ape_materno, 300);
            oci_bind_by_name($stmt, ':PAR_RFC', $rfc, 300);
            oci_bind_by_name($stmt, ':PAR_CURP', $curp, 300);
            oci_bind_by_name($stmt, ':PAR_CLAVEIFE', $claveife, 300);
            oci_bind_by_name($stmt, ':PAGE_SIZE', $page_size, 300);
            oci_bind_by_name($stmt, ':PAGE', $page, 300);
            oci_bind_by_name($stmt, ':SORTEXPRESSION', $sortexpression);
            $cursor = oci_new_cursor($conn);
            oci_bind_by_name($stmt, ":C_NOTARIOS", $cursor, -1, OCI_B_CURSOR);
            oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
            oci_execute($cursor, OCI_COMMIT_ON_SUCCESS);
            oci_free_statement($stmt);
            oci_close($conn);
            oci_fetch_all($cursor, $notarios, 0, -1, OCI_FETCHSTATEMENT_BY_ROW + OCI_ASSOC);
            oci_free_cursor($cursor);

            $offset = ($page * $page_size) - $page_size;
            $data = array_slice($notarios, $offset, $page_size, true);
            
            foreach($data as $idElemento => $elementoNotario){
                if($elementoNotario['NUMERO'] < 1){
                    unset($data[$idElemento]);
                }else{
                    foreach($elementoNotario as $id => $valor){
                        if($id == 'NOMBRE' || $id == 'APELLIDOPATERNO' || $id == 'APELLIDOMATERNO' || $id == 'NOMBREAPELLIDOS'){
                            //$valor = mb_convert_encoding($valor,'ISO-8859-1','UTF-8');                            
                            $valor = str_replace('?','Ñ',$valor);
                            //echo "ENCODE ".mb_check_encoding($valor,'UTF-8')." "; echo "VALOR " ";
                            $data[$idElemento][$id] = $valor;
                        
                        }    
                    }
                }                 
            }

            $newData = array();
            $control = 0;
            foreach($data as $idElemento => $elementoNotario){
                $newData[] = $elementoNotario;
            }
            $data = $newData;
            //print_r($data); exit();
            $notarios = new \Illuminate\Pagination\LengthAwarePaginator($data, count($data), $page_size, $page);

            if (count($notarios) > 0) {            
                return $notarios;
            } else {
                return ['mensaje' => 'No existen resultados para la búsqueda ingresada'];
            }
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '7. Error al obtener notario'], 500);
        }    

    
    }

    public function asignaNotarioAvaluo(Request $request)
    {
        try {            
            //print_r($request); exit();
            $id_persona_notario = $request->query('id_persona_notario');    

            $numero_unico = $request->query('no_unico');
            $this->modelDocumentos = new Documentos();
                
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);
            //$id_avaluo = $request->query('id_avaluo');

            $procedure = 'BEGIN
            FEXAVA.FEXAVA_AVALUOS_PKG.fexava_update_ava_notarios_p(
                :PAR_IPNOTARIO,
                :PAR_IDAVALUO
            ); END;';
            $pdo = DB::getPdo();
            $stmt = $pdo->prepare($procedure);
            $stmt->bindParam(':PAR_IPNOTARIO', $id_persona_notario, \PDO::PARAM_INT);
            $stmt->bindParam(':PAR_IDAVALUO', $id_avaluo, \PDO::PARAM_INT);
            $stmt->execute();
            $stmt->closeCursor();
            $pdo->commit();
            $pdo->close();
            DB::commit();
            DB::reconnect();

            $infoNotario = convierte_a_arreglo(DB::select("SELECT * FROM FEXAVA_NOTARIOS_V WHERE IDPERSONA = $id_persona_notario")); //print_r($infoNotario);
            return response()->json(['mensaje' => 'Notario Actualizado a N° '.$infoNotario[0]['numero'].' '.$infoNotario[0]['apellidopaterno'].' '.$infoNotario[0]['apellidomaterno'].' '.$infoNotario[0]['nombre']], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '8. Error en el servidor'], 500);
        }
    }
    
    function descomprimirCualquierFormato($archivo){
        try{
            //var_dump($request);
        //$archivo = $request->file('files');        
        if($this->validarTamanioFichero(filesize($archivo)) == FALSE){
            $res = response()->json(['mensaje' => 'El tamaño del fichero es muy grande.'], 500);
            return $res;
        }
        if ($archivo) {
            $nombreArchivo = $archivo->getClientOriginalName(); // OK WORK!
            $rutaArchivos = getcwd();
        }
        //echo "EL NOMBRE DEL ARCHIVO ".$nombreArchivo; exit();
        $ext = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'zip':

                $cadenaDes = shell_exec("zipinfo -1 $archivo");
                $numeroNombres = count(explode("\n", trim($cadenaDes)));
                if ($numeroNombres == 1) {
                    $arrDes = explode("\n", trim($cadenaDes));
                    $nombreDes = trim($arrDes[0]);
                    shell_exec("unzip $archivo -d $rutaArchivos");
                    $myfile = fopen($rutaArchivos."/".$nombreDes, "r");
                    $res = fread($myfile, filesize($rutaArchivos."/".$nombreDes));
                    //$res = simplexml_load_file($rutaArchivos."/".$nombreDes);
                    fclose($myfile);
                    shell_exec("rm -f ".$rutaArchivos."/".str_replace(" ","\ ",$nombreDes));
                }else{
                    $res = response()->json(['mensaje' => 'El fichero debe contener un único archivo.'], 500);
                }

                break;
            case 'gz':
                //echo "SOY EL NOMBE DEL ARCHIVO ".$nombreArchivo; exit();
                $cadenaDes = shell_exec("gunzip -l " . $archivo);
                $arrCadenas = explode("%", trim($cadenaDes));
                if (count($arrCadenas) < 3) {
                    $nombreDes = explode("\n", trim($arrCadenas[1]));
                    shell_exec("gunzip $archivo");
                    $myfile = fopen($rutaArchivos."/".$nombreDes, "r");
                    $res = fread($myfile, filesize($rutaArchivos."/".$nombreDes));
                    //$res = simplexml_load_file($rutaArchivos."/".$nombreDes);
                    fclose($myfile);                   
                    shell_exec("rm -f ".$rutaArchivos."/".str_replace(" ","\ ",$nombreDes));
                }else{
                    $res = response()->json(['mensaje' => 'El fichero debe contener un único archivo.'], 500);
                }

                break;
            case 'rar':

                $cadenaDes = shell_exec("unrar lt $archivo");
                $numeroNombres = substr_count($cadenaDes, "Name:");
                if ($numeroNombres == 1) {
                    $arrDes = explode("\n", $cadenaDes);
                    $arrNombreDes = explode(":", $arrDes[6]);
                    $nombreDes = trim($arrNombreDes[1]);
                    shell_exec("unrar x $archivo $rutaArchivos");
                    $myfile = fopen($rutaArchivos."/".$nombreDes, "r");
                    $res = fread($myfile, filesize($rutaArchivos."/".$nombreDes));
                    //$res = simplexml_load_file($rutaArchivos."/".$nombreDes);
                    fclose($myfile);
                    shell_exec("rm -f ".$rutaArchivos."/".str_replace(" ","\ ",$nombreDes));
                }else{
                    $res = response()->json(['mensaje' => 'El fichero debe contener un único archivo.'], 500);
                }
                break;
        }

        return $res;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '9. Error en el servidor'], 500);
        }
        
    }

    function comprimir($archivo){
        try{
            if ($archivo) {
                $nombreArchivo = $archivo->getClientOriginalName(); // OK WORK!
                $nombreArchivo = str_replace(' ','_',$nombreArchivo);
                $nombreArchivo = str_replace('(','',$nombreArchivo);
                $nombreArchivo = str_replace(')','',$nombreArchivo);
                $rutaArchivos = getcwd();
            }
            
            $ext = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
    
            $nombreComprimido = str_replace($ext,'7z',$nombreArchivo);
            
            $xmlComprimido = shell_exec("7z a $nombreComprimido $archivo");
    
           return $nombreComprimido;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '10. Error al comprimir archivo'], 500);
        }
        
    }

    function comprimirNet($archivo,$nombreArchivo){
        try{    
            
            $ext = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
            
            $nombreArchivo = str_replace('(','',$nombreArchivo);
            $nombreArchivo = str_replace(')','',$nombreArchivo);
            $nombreComprimido = $nombreArchivo.".7z";
            
            $xmlComprimido = shell_exec("7z a $nombreComprimido $archivo");
    
           return $nombreComprimido;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '11. Error al comprimir archivo'], 500);
        }
        
    }

    function validarTamanioFichero($bytesXmlAvaluo)
    {
        try{
            $tamanioMaximo = 4194304;
            if ($tamanioMaximo < $bytesXmlAvaluo) {
                return FALSE;
            } else {
                return TRUE;
            }
        }catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '12. Error al comprimir archivo'], 500);
        }
        
    }
    
    function esValidoEsquema($contents){
        try{
            $xml = new \SimpleXMLElement($contents);            
            /*$rutaArchivos = getcwd();
            $resEstructura = "xmllint --format ".$file." > ".$rutaArchivos."/resEstructura";
            if(strpos($resEstructura,"parser error") != false){ error_log("ENTREEEEE "); 
                return explode(":",system("xmllint --format ".$file));   
            }*/
            $relacionErrores = '';
            //$arrRelacionErrores = array();
            $arrContenoidoXML = explode("\r\n",$contents); //print_r($arrContenoidoXML); exit();
            if(count($arrContenoidoXML) < 2){
                $arrContenoidoXML = explode("\n",$contents);
            }

            $this->doc = new \DOMDocument('1.0', 'utf-8');
            libxml_use_internal_errors(true);       
            
            //$xsd = 'EsquemaAvaluomio.xsd';
            $arrXML = convierte_a_arreglo(simplexml_load_string($contents,'SimpleXMLElement', LIBXML_NOCDATA));
            if(isset($arrXML['Comercial'])){
                $elementoPrincipal = "Comercial";
            }else{
                $elementoPrincipal = "Catastral";
            }
            //error_log($elementoPrincipal);            
            $fechaAvaluo = $arrXML[$elementoPrincipal]['Identificacion']['FechaAvaluo']; //error_log("LA FECHA AVALUO ".$fechaAvaluo);
            $fechaAvaluoCompara = new Carbon($fechaAvaluo);
            $fechaMaxima = new Carbon('2020-12-31');  
            if($fechaAvaluoCompara->lte($fechaMaxima)){
                if(isset($arrXML[$elementoPrincipal]['EnfoqueDeMercado']['Terrenos']['TerrenosDirectos']) && isset($arrXML[$elementoPrincipal]['EnfoqueDeMercado']['Terrenos']['TerrenosResidual'])){
                    $xsd = 'EsquemaAvaluoMixtoFinal.xsd'; error_log("ENTRE EN MIXTO VIEJO");
                }else{
                    $xsd = 'EsquemaAvaluoFinal.xsd'; error_log("ENTRE EN NORMAL VIEJO");
                }
            }else{
                if(isset($arrXML[$elementoPrincipal]['EnfoqueDeMercado']['Terrenos']['TerrenosDirectos']) && isset($arrXML[$elementoPrincipal]['EnfoqueDeMercado']['Terrenos']['TerrenosResidual'])){
                    if(is_array($arrXML[$elementoPrincipal]['Antecedentes']['PropositoDelAvaluo']) && isset($arrXML[$elementoPrincipal]['Antecedentes']['PropositoDelAvaluo']['Otros'])){                        
                        $xsd = 'EsquemaAvaluoNuevoCatMixtoFinal.xsd';
                    }else{
                        $xsd = 'EsquemaAvaluoNuevoMixtoFinal.xsd';
                    }                    
                }else{
                    if(is_array($arrXML[$elementoPrincipal]['Antecedentes']['PropositoDelAvaluo']) && isset($arrXML[$elementoPrincipal]['Antecedentes']['PropositoDelAvaluo']['Otros'])){
                        $xsd = 'EsquemaAvaluoNuevoCatFinal.xsd'; error_log("ENTRE EN CAT NUEVO");
                    }else{
                        $xsd = 'EsquemaAvaluoNuevoFinal.xsd'; error_log("ENTRE EN CAT MIXTO NUEVO");
                    }    
                }
            }          
                    
            //error_log("EL XSD ".$xsd);
            if (!file_exists($xsd)) {
                //$relacionErrores[] = "Archivo <b>$xsd</b> no existe.";
                return false;
            } //echo $contents; exit();
            $this->doc->loadXML($contents, LIBXML_NOBLANKS);
            if (!$this->doc->schemaValidate($xsd)) {
                //Recupera un array de errores
                $this->errors = libxml_get_errors();  //print_r($this->errors);
                foreach(convierte_a_arreglo($this->errors) as $elementoError){ //print_r($elementoError); echo 'arrContenoidoXML ';  print_r($arrContenoidoXML[$elementoError['line'] - 1])." FIN \n"; exit();
                    $pos = strpos($arrContenoidoXML[$elementoError['line'] - 1], "'");
                    if($pos === false){
                        $arrRenglonXML = explode('"',$arrContenoidoXML[$elementoError['line'] - 1]); //print_r($arrRenglonXML); exit();
                    }else{
                        $arrRenglonXML = explode("'",$arrContenoidoXML[$elementoError['line'] - 1]); //print_r($arrRenglonXML); exit();
                    }
                    error_log("EL ERROR ".json_encode($arrRenglonXML));
                    if(isset($arrRenglonXML[1])){
                        $relacionErrores = $relacionErrores.$arrRenglonXML[1]." - Línea ".$elementoError['line']." ".$elementoError['message']."<<>>";
                    }else{
                        $relacionErrores = $relacionErrores." - Línea ".$elementoError['line']." ".$elementoError['message']."<<>>";
                    }                
                                                                
                }
                //$arrRelacionErrores[] = $arrRenglonXML[1]." - Línea ".$elementoError['line']." ".$elementoError['message'];
                $arrRelacionErrores = $this->traduce($relacionErrores);
                return $arrRelacionErrores;        
            }
            return true;
        }catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '13. Error al comprimir archivo'], 500);
            return $th;
        }
        
    }

    function traduce($relacionErrores){
        $cadenas = array("("=>"'",")"=>"'","Element content is not allowed, because the content type is a simple type definition." => "El contenido del elemento no está permitido, porque el tipo de contenido es una definición de tipo simple.",
        "Element"=>"Elemento",
        "is not a valid value of the atomic type" => "no es un valor válido de tipo",
        "[facet 'minLength'] The value has a length of" => "[faceta 'minLength'] El valor tiene una longitud de",
        "this underruns the allowed minimum length of" => "Longitud mínima requerida",
        "[facet 'enumeration'] The value" => "El valor",
        "is not an element of the set" => "no es un elemento válido, valores permitidos:", 
        "[facet 'pattern'] The value" => "[faceta 'patrón'] El valor",
        "is not accepted by the pattern" => "no es aceptado por el patrón",
        "is not a valid value of the union type" => "no es un valor válido del tipo de unión",
        ": This element is not expected. Expected is" => " no válido. Lista esperada de elementos posibles: ",
        "[facet 'maxLength'] The value has a length of" => "[faceta 'maxLength'] EL valor tiene una longitud de",
        "this exceeds the allowed maximum length of" => "esto excede la longitud máxima permitida de",
        "The value" => "EL valor",
        "is less than the minimum value allowed" => "es menor que el valor mínimo permitido",
        "{'F', 'M', 'f', 'm'}" => " F o M",
        "This element is not expected" => "Este elemento no se espera",
        "Missing child element's'. Expected is" => "Falta el elemento secundario",
        "one of" => "uno de",
        "Character content other than whitespace is not allowed because the content type is 'element-only'" => "No se permite contenido de caracteres que no sean espacios en blanco porque el tipo de contenido es 'element-only'",
        "attribute" => "atributo",
        "does not match the fixed value constraint" => "no coincide con la restricción de valor fijo",
        "'ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementoosAccesoriosPrivativas': Este elemento no se espera." => "'ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementoosAccesoriosPrivativas': no válido. Lista esperada de elementos posibles: ConsideracionesPreviasAlAvaluo");

        foreach($cadenas as $en => $es){
            $relacionErrores = str_replace($en,$es,$relacionErrores);
        }

        $arrRelacionErrores = explode("<<>>",$relacionErrores);
        return $arrRelacionErrores;
    }

    function crearNombreDocumentoAv($cuentaCatastral,$tipAv){
        if($tipAv == "//Catastral"){
            return "Avaluo-"."Cat-".$cuentaCatastral.".xml";
        }else{
            return "Avaluo-"."Com-".$cuentaCatastral.".xml";
        }

    }

    function crearDescripcionDocumentoAv($cuentaCatastral){
        return "Avaluo_".$cuentaCatastral;
    }

    function guardarAvaluo(Request $request){
        try{

            $authToken = $request->header('Authorization');
            if (!$authToken) {
                return response()->json(['mensaje' => 'Sin acceso a la aplicación'], 403);
            } 
            $resToken = Crypt::decrypt($authToken);
            
            $idPersona = empty($resToken['id_persona']) ? $resToken['id_usuario']: $resToken['id_persona']; //$idPersona = 264;
           
            $file = $request->file('files');

            $myfile = fopen($file, "r");
            $contents = fread($myfile, filesize($file));    
            fclose($myfile);    

            if($this->validarTamanioFichero(filesize($file)) == FALSE){
                $camposFexavaAvaluo['ERRORES'][] = array('El tamaño del fichero es muy grande.');    
            }
            
            $resValidaEsquema = $this->esValidoEsquema($contents);
            if(is_array($resValidaEsquema)){
                $camposFexavaAvaluo = array();
                $camposFexavaAvaluo['ERRORES'][] = $resValidaEsquema;
                return response()->json(['mensaje' => $this->limpiaRepetidos($camposFexavaAvaluo['ERRORES'])], 500);
                
            }           
                    
            //$xml = new \SimpleXMLElement($contents);
            $xml = simplexml_load_string($contents,'SimpleXMLElement', LIBXML_NOCDATA);
            $this->fileXML = $xml;
                          
            $esComercial = $xml->xpath('//Comercial');
            if(count($esComercial) > 0){
                $esComercial = true;
                $tipoTramite = 1;
                $elementoPrincipal = '//Comercial';            
            }else{
                $esComercial = false;
                $tipoTramite = 2;
                $elementoPrincipal = '//Catastral';            
            }            

            $elementoFecha = $xml->xpath($elementoPrincipal.'//Identificacion//FechaAvaluo[@id="a.2"]');
            $fechaAvaluo = $elementoFecha[0];
            //$camposFexavaAvaluo = $this->camposFexAva();
            $camposFexavaAvaluo = array();
            $camposFexavaAvaluo['ERRORES'] = array();
            $camposFexavaAvaluo['CODESTADOAVALUO'] =  1; //CODESTADOAVALUO (Recibido)
            $fecha_hoy = new Carbon(date('Y-m-d'));
            $fecha_presentacion = $fecha_hoy->format('Y-m-d');
            $camposFexavaAvaluo['FECHA_PRESENTACION'] = $fecha_presentacion;
            $camposFexavaAvaluo['CODTIPOTRAMITE'] = $tipoTramite;            
            $infoXmlIdentificacion = $xml->xpath($elementoPrincipal.'//Identificacion[@id="a"]');
            $mensajes = array();

            $fechaAvaluoCompara = new Carbon($fechaAvaluo);
            //$fechaMaxima = new Carbon('2020-12-31');
            /*****************************************/
            $arrXML = convierte_a_arreglo($xml);

            if(isset($arrXML['Comercial'])){
                $elementoPrincipalArr = $arrXML['Comercial'];
            }
    
            if(isset($arrXML['Catastral'])){
                $elementoPrincipalArr = $arrXML['Catastral'];
            }
            if(isset($elementoPrincipalArr['Antecedentes']['Solicitante']['Alcaldia'])){
                $tieneAlcaldia = true;
            }
            if(isset($elementoPrincipalArr['Antecedentes']['Solicitante']['Delegacion'])){
                $tieneDelegacion = true;
            }
            $anioCompara = Carbon::parse($fechaAvaluoCompara)->format('Y');
            error_log("EL AÑO A COMPARAR ".$anioCompara);
            /*****************************************/
            //if($fechaAvaluoCompara->lte($fechaMaxima)){
            if($anioCompara == 2021 && isset($tieneAlcaldia) && $tieneAlcaldia == true){ error_log("Entre en Nuevo");
                return $this->guardarAvaluoN($file,$infoXmlIdentificacion, $camposFexavaAvaluo, $idPersona,$elementoPrincipal,$xml,$fechaAvaluo,$contents);                
            }else{ error_log("Entre en Viejo");
                return $this->guardarAvaluoV($file,$infoXmlIdentificacion, $camposFexavaAvaluo, $idPersona,$elementoPrincipal,$xml,$fechaAvaluo,$contents);
            }
            

        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);    
            if(strpos($th, "simplexml_load_string") != false){
                $th = explode("\n",$th);
                $arrError = array();
                $arrError[] = $this->traduceErrorFormato($th[0]);
                return response()->json(['mensaje' => $arrError], 500);
            }else{
                return response()->json(['mensaje' => '14. Error al guardar el Avalúo'], 500);
            }
        }
    }

    function guardarAvaluoPNet(Request $request){
        try{

            $idUsuario = $request->query('idUsuario'); //echo $idUsuario; exit();

            $file = $request->query('files');
            $contents = base64_decode($file);

            /*$file = $request->file('files');
            $myfile = fopen($file, "r");
            $contents = fread($myfile, filesize($file));    
            fclose($myfile);*/

            //echo $contents; exit();
            $xml = simplexml_load_string($contents,'SimpleXMLElement', LIBXML_NOCDATA);          
            //$this->fileXML = $xml;

                $esComercial = $xml->xpath('//Comercial');
                if(count($esComercial) > 0){
                    $esComercial = true;
                    $tipoTramite = 1;
                    $elementoPrincipal = '//Comercial';            
                }else{
                    $esComercial = false;
                    $tipoTramite = 2;
                    $elementoPrincipal = '//Catastral';            
                }

                $elementoFecha = $xml->xpath($elementoPrincipal.'//Identificacion//FechaAvaluo[@id="a.2"]');
                $fechaAvaluo = $elementoFecha[0];

                $infoXmlCuentaCatastral = $xml->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//InmuebleQueSeValua[@id="b.3"]//CuentaCatastral[@id="b.3.10"]');
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['CuentaCatastral'] = array();
                foreach($infoXmlCuentaCatastral[0] as $llave => $elemento){
                    $arrCuentaCatastral[$llave] = (String)($elemento);
                }
                
                if(trim($arrCuentaCatastral['Region'] != '')){
                    $region = $arrCuentaCatastral['Region'];
                }
        
                if(trim($arrCuentaCatastral['Manzana'] != '')){
                    $manzana = $arrCuentaCatastral['Manzana'];
                }
        
                if(trim($arrCuentaCatastral['Lote'] != '')){
                    $lote = $arrCuentaCatastral['Lote'];
                }
        
                if(trim($arrCuentaCatastral['Localidad'] != '')){
                    $localidad = $arrCuentaCatastral['Localidad'];
                }
        
                if(trim($arrCuentaCatastral['DigitoVerificador'] != '')){
                    $digitoVerificador = $arrCuentaCatastral['DigitoVerificador'];
                }

                $this->modelDocumentos =  new Documentos();

            $camposFexavaAvaluo['IDAVALUO'] = 0;    
                $cuentaCat = $region.'-'.
                            $manzana.'-'.
                            $lote.'-'.
                            $localidad;
                $nombreXMLAvaluo = $this->crearNombreDocumentoAv($cuentaCat,$elementoPrincipal);                
                $descripcionXMLAvaluo = $this->crearDescripcionDocumentoAv($cuentaCat);
                if($elementoPrincipal == "//Catastral"){
                    $idDocumentoDigital = $this->modelDocumentos->tran_InsertAvaluo($descripcionXMLAvaluo,2,$fechaAvaluo,$idUsuario);
                }else{
                    //echo $descripcionXMLAvaluo." 13 ".$fechaAvaluo." ".$idUsuario; exit();
                    $idDocumentoDigital = $this->modelDocumentos->tran_InsertAvaluo($descripcionXMLAvaluo,13,$fechaAvaluo,$idUsuario);
                }

                $rutaArchivos = getcwd();
                $nombreArchivoNet = $rutaArchivos.'/default';
                $mynewfile = fopen($nombreArchivoNet, "w");
                $fwrite = fwrite($mynewfile, $contents);    
                fclose($mynewfile);               
                //exit();
                
                $archivoComprimido = $this->comprimirNet($rutaArchivos.'/default',$nombreXMLAvaluo);
                //var_dump($archivoComprimido); exit();
                shell_exec("rm -f ".$rutaArchivos.'/default');
                $ficheroAvaluo = $this->modelDocumentos->tran_InsertFicheroAvaluo($idDocumentoDigital, $nombreXMLAvaluo, null, $archivoComprimido);                
            //return $idDocumentoDigital;
            return response()->json(['IDDOCUMENTODIGITAL' => $idDocumentoDigital], 200);
        }    
        catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '15. Error en el servidor'], 500);
        } 
    }

    function guardarAvaluoN($file,$infoXmlIdentificacion, $camposFexavaAvaluo, $idPersona,$elementoPrincipal,$xml,$fechaAvaluo,$contents){
        try{       
            
            $this->modelPeritoSociedad = new PeritoSociedad();
            $this->modelDatosExtrasAvaluo = new DatosExtrasAvaluo();
            $this->modelDocumentos = new Documentos();
            $this->modelElementosConstruccion = new ElementosConstruccion();
            $this->modelGuardaenBD = new GuardaenBD();
            $this->modelAva = new Ava();
            $this->modelFis = new Fis();
            //Id Persona de usuarios migrados es el id anterior         
            
            
            $nombreArchivo = $file->getClientOriginalName();
            $this->guardaAvance($nombreArchivo,0);
            $camposFexavaAvaluo = $this->guardarAvaluoIdentificacion($infoXmlIdentificacion, $camposFexavaAvaluo, $idPersona,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,5);                               
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS'] = array();
            $camposFexavaAvaluo = $this->guardarAvaluoAntecedentes($xml, $camposFexavaAvaluo,$elementoPrincipal); 
            
            $camposFexavaAvaluo = $this->guardarAvaluoCaracteristicasUrbanas($xml, $camposFexavaAvaluo,$elementoPrincipal);            
            $this->guardaAvance($nombreArchivo,10);
            $camposFexavaAvaluo['IDAVALUO'] = 0;    
            $cuentaCat = $camposFexavaAvaluo['REGION'].'-'.
                           $camposFexavaAvaluo['MANZANA'].'-'.
                           $camposFexavaAvaluo['LOTE'].'-'.
                           $camposFexavaAvaluo['UNIDADPRIVATIVA'];
            $nombreXMLAvaluo = $this->crearNombreDocumentoAv($cuentaCat,$elementoPrincipal);
            $descripcionXMLAvaluo = $this->crearDescripcionDocumentoAv($cuentaCat);
            if($elementoPrincipal == "//Catastral"){
                $idDocumentoDigital = $this->modelDocumentos->tran_InsertAvaluo($descripcionXMLAvaluo,2,$fechaAvaluo,$idPersona);
            }else{
                $idDocumentoDigital = $this->modelDocumentos->tran_InsertAvaluo($descripcionXMLAvaluo,13,$fechaAvaluo,$idPersona);
            }            
            $camposFexavaAvaluo['IDAVALUO'] = $idDocumentoDigital;

            $camposFexavaAvaluo = $this->guardarAvaluoTerreno($xml, $camposFexavaAvaluo,$elementoPrincipal,$idDocumentoDigital);
                           
            $camposFexavaAvaluo = $this->guardarAvaluoDescripcionImueble($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,15);
            $camposFexavaAvaluo = $this->guardarAvaluoElementosConstruccion($xml, $camposFexavaAvaluo,$elementoPrincipal);
           
            $camposFexavaAvaluo = $this->guardarAvaluoEnfoqueMercado($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,20);
            $camposFexavaAvaluo = $this->guardarAvaluoEnfoqueCostosComercial($xml, $camposFexavaAvaluo,$elementoPrincipal);
            
            $camposFexavaAvaluo = $this->guardarAvaluoEnfoqueCostosCatastral($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,25);
            $camposFexavaAvaluo = $this->guardarAvaluoEnfoqueIngresos($xml, $camposFexavaAvaluo,$elementoPrincipal);
            
            $camposFexavaAvaluo = $this->guardarAvaluoResumenConclusionAvaluo($xml, $camposFexavaAvaluo,$elementoPrincipal);
           
            $camposFexavaAvaluo = $this->guardarAvaluoValorReferido($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,30);
            $camposFexavaAvaluo = $this->guardarAvaluoAnexoFotografico($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,50);
            //print_r($camposFexavaAvaluo); exit();
            if(count($camposFexavaAvaluo['ERRORES']) > 0){  
                foreach($camposFexavaAvaluo['ERRORES'] as $idElementoError => $elementoError){
                    if(is_array($elementoError) === true){
                        if(count($elementoError) === 0){
                            unset($camposFexavaAvaluo['ERRORES'][$idElementoError]);
                        }else{
                            $control = 0;
                            foreach($elementoError as $idSubElementoError => $subElementoError){
                                if(is_array($subElementoError) === true){ 
                                    if(count($subElementoError) === 0){
                                        unset($camposFexavaAvaluo['ERRORES'][$idElementoError][$idSubElementoError]);
                                    }else{ 
                                        foreach($camposFexavaAvaluo['ERRORES'][$idElementoError][$idSubElementoError] as $idElementoFinal => $elementoFInal){
                                            //echo trim($elementoFInal)."\n";
                                            if(trim($elementoFInal) === ''){
                                                unset($camposFexavaAvaluo['ERRORES'][$idElementoError][$idSubElementoError][$idElementoFinal]);
                                            }else{
                                                $control = $control + 1;
                                            }
                                        }                                        
                                    }
                                    
                                }else{
                                    if(trim($subElementoError) === ''){
                                        unset($camposFexavaAvaluo['ERRORES'][$idElementoError][$idSubElementoError]);
                                    }else{
                                        $control = $control + 1;
                                    }
                                }
                            }
                            if($control === 0){
                                unset($camposFexavaAvaluo['ERRORES'][$idElementoError]);
                            }
                            
                        }
                    }else{
                        if(trim($elementoError) === ''){
                            unset($camposFexavaAvaluo['ERRORES'][$idElementoError]);
                        }
                    }
                    
                    $arrn = array();
                    foreach($camposFexavaAvaluo['ERRORES'] as $elementoError){
                        $arrn[] = $elementoError;
                    }
                }
                //Log::info($this->reordenaErrores($arrn));
                return response()->json(['mensaje' => $this->reordenaErrores($arrn)], 500);
            }
            $this->guardaAvance($nombreArchivo,55);
            //echo $this->fileXML->asXML(); exit();
            $mynewfile = fopen($file, "w");
            $fwrite = fwrite($mynewfile, $this->fileXML->asXML());    
            fclose($mynewfile);
            
            $archivoComprimido = $this->comprimir($file);
            $ficheroAvaluo = $this->modelDocumentos->tran_InsertFicheroAvaluo($idDocumentoDigital, $nombreXMLAvaluo, null, $archivoComprimido);
            $resInsert = $this->modelGuardaenBD->insertAvaluo($camposFexavaAvaluo);            
            $this->guardaDatosExtra($xml,$elementoPrincipal,$camposFexavaAvaluo['IDAVALUO']);
            $this->guardaAvance($nombreArchivo,100);
                    
            
            if($resInsert == TRUE){
               
                $numeroUnico = $this->modelDocumentos->get_numero_unico_db($camposFexavaAvaluo['IDAVALUO']);
                $this->recibeAvaluo($idPersona,$numeroUnico,$contents);    
                return response()->json(['Estado' => $resInsert,'numeroUnico' => $numeroUnico], 200);
            }else{
                return response()->json(['mensaje' => $resInsert], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);    
            if(strpos($th, "simplexml_load_string") != false){
                $th = explode("\n",$th);
                $arrError = array();
                $arrError[] = $this->traduceErrorFormato($th[0]);
                return response()->json(['mensaje' => $arrError], 500);
            }else{
                return response()->json(['mensaje' => '16. Error al guardar el Avalúo'], 500);
            }
        }
        
    }

    public function recibeAvaluo($idUsuario,$folio_Interno,$contents){
        $auth = '<usuario xmlns="IDEAS.Avametrica">' . env('USUARIO_WSDL') . '</usuario>';
        $auth .= '<contrasenia xmlns="IDEAS.Avametrica">' . env('PASS_WSDL') . '</contrasenia>';
        
        $client = new \SoapClient(env('WSDL_RECIBE_AVALUO'), ["debug" => true, "trace" => true, "exception" => true]);
        $auth_block = new \SoapVar($auth, XSD_ANYXML, NULL, NULL, NULL, NULL);
        $header = new \SoapHeader('http://schemas.xmlsoap.org/soap/envelope/', 'Header', $auth_block);
        $client->__setSoapHeaders($header);
        try {
            $client->__soapCall('obtenertoken', ['obtenertoken' => ['folio_avaluo' => $folio_Interno]]);
        } catch (\Exception $e) {
        }
        $restClient = new \GuzzleHttp\Client();
        $response = $restClient->request('POST', env('API_WEBHOOK') . $folio_Interno);

        if ($response->getStatusCode() == 200) {
            try {
                $res = json_decode($response->getBody());

                $enviado = $client->__soapCall('BandejaAvaluoXML', ['BandejaAvaluoXML' => [
                    'datos' => [
                        'AvaluoXML' => (String)($contents), //$xml->asXML(),
                        'Folio_Interno' => $folio_Interno,
                        'Folio_Usuario' => $idUsuario,
                        'token' => $res->token,
                    ]
                ]]);

                $this->modelDocumentos = new Documentos();
                $idAvaluo = $this->modelDocumentos->get_idavaluo_db($folio_Interno);

                if($enviado->BandejaAvaluoXMLResult){                        
                    $response = $this->modelDocumentos->guardaResultado($idAvaluo, $idUsuario, $enviado->BandejaAvaluoXMLResult, 'El avalúo fue entregado a la bandeja');
                    return response()->json(['mensaje' => 'El avalúo fue entregado a la bandeja '.$response], 200);
                }else{                        
                    $response = $this->modelDocumentos->guardaResultado($idAvaluo, $idUsuario, $enviado->BandejaAvaluoXMLResult, 'El avalúo no pudo ser entregado');
                    return response()->json(['mensaje' => 'El avalúo no pudo ser entregado '.$response], 400);
                }
                
            } catch (\Throwable $th) {
                error_log($th);
                
                return response()->json(['mensaje' => '17. Error al consumir el servicio'], 500);
            }
        } else {
            return response()->json($response->getBody(), $response->getStatusCode());
        }    
    }    

    function guardarAvaluoV($file,$infoXmlIdentificacion, $camposFexavaAvaluo, $idPersona,$elementoPrincipal,$xml,$fechaAvaluo,$contents){
        try{
            //echo "ENTRE AL VIEJO"; exit();
            $this->modelPeritoSociedad = new PeritoSociedad();
            $this->modelDatosExtrasAvaluo = new DatosExtrasAvaluo();
            $this->modelDocumentos = new Documentos();
            $this->modelElementosConstruccion = new ElementosConstruccion();
            $this->modelGuardaenBD = new GuardaenBD();
            $this->modelAva = new Ava();
            $this->modelFis = new Fis();
            //Id Persona de usuarios migrados es el id anterior                     
            
            $nombreArchivo = $file->getClientOriginalName();
            $this->guardaAvance($nombreArchivo,0);
            $camposFexavaAvaluo = $this->guardarAvaluoIdentificacion($infoXmlIdentificacion, $camposFexavaAvaluo, $idPersona,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,5);                               
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS'] = array();
            $camposFexavaAvaluo = $this->guardarAvaluoAntecedentesV($xml, $camposFexavaAvaluo,$elementoPrincipal); 
            
            $camposFexavaAvaluo = $this->guardarAvaluoCaracteristicasUrbanas($xml, $camposFexavaAvaluo,$elementoPrincipal);            
            $this->guardaAvance($nombreArchivo,10);
            $camposFexavaAvaluo['IDAVALUO'] = 0;    
            $cuentaCat = $camposFexavaAvaluo['REGION'].'-'.
                           $camposFexavaAvaluo['MANZANA'].'-'.
                           $camposFexavaAvaluo['LOTE'].'-'.
                           $camposFexavaAvaluo['UNIDADPRIVATIVA'];
            $nombreXMLAvaluo = $this->crearNombreDocumentoAv($cuentaCat,$elementoPrincipal);
            $descripcionXMLAvaluo = $this->crearDescripcionDocumentoAv($cuentaCat);
            if($elementoPrincipal == "//Catastral"){
                $idDocumentoDigital = $this->modelDocumentos->tran_InsertAvaluo($descripcionXMLAvaluo,2,$fechaAvaluo,$idPersona);
            }else{
                $idDocumentoDigital = $this->modelDocumentos->tran_InsertAvaluo($descripcionXMLAvaluo,13,$fechaAvaluo,$idPersona);
            }            
            $camposFexavaAvaluo['IDAVALUO'] = $idDocumentoDigital;

            $camposFexavaAvaluo = $this->guardarAvaluoTerrenoV($xml, $camposFexavaAvaluo,$elementoPrincipal,$idDocumentoDigital);
            
            $camposFexavaAvaluo = $this->guardarAvaluoDescripcionImuebleV($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,15);
            $camposFexavaAvaluo = $this->guardarAvaluoElementosConstruccionV($xml, $camposFexavaAvaluo,$elementoPrincipal);
           
            $camposFexavaAvaluo = $this->guardarAvaluoEnfoqueMercadoV($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,20);
            $camposFexavaAvaluo = $this->guardarAvaluoEnfoqueCostosComercialV($xml, $camposFexavaAvaluo,$elementoPrincipal);
            
            $camposFexavaAvaluo = $this->guardarAvaluoEnfoqueCostosCatastral($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,25);
            $camposFexavaAvaluo = $this->guardarAvaluoEnfoqueIngresos($xml, $camposFexavaAvaluo,$elementoPrincipal);
            
            $camposFexavaAvaluo = $this->guardarAvaluoResumenConclusionAvaluo($xml, $camposFexavaAvaluo,$elementoPrincipal);
           
            $camposFexavaAvaluo = $this->guardarAvaluoValorReferido($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,30);
            $camposFexavaAvaluo = $this->guardarAvaluoAnexoFotografico($xml, $camposFexavaAvaluo,$elementoPrincipal);
            $this->guardaAvance($nombreArchivo,50);
            //print_r($camposFexavaAvaluo); exit();
            if(count($camposFexavaAvaluo['ERRORES']) > 0){  
                foreach($camposFexavaAvaluo['ERRORES'] as $idElementoError => $elementoError){
                    if(is_array($elementoError) === true){
                        if(count($elementoError) === 0){
                            unset($camposFexavaAvaluo['ERRORES'][$idElementoError]);
                        }else{
                            $control = 0;
                            foreach($elementoError as $idSubElementoError => $subElementoError){
                                if(is_array($subElementoError) === true){ 
                                    if(count($subElementoError) === 0){
                                        unset($camposFexavaAvaluo['ERRORES'][$idElementoError][$idSubElementoError]);
                                    }else{ 
                                        foreach($camposFexavaAvaluo['ERRORES'][$idElementoError][$idSubElementoError] as $idElementoFinal => $elementoFInal){
                                            //echo trim($elementoFInal)."\n";
                                            if(trim($elementoFInal) === ''){
                                                unset($camposFexavaAvaluo['ERRORES'][$idElementoError][$idSubElementoError][$idElementoFinal]);
                                            }else{
                                                $control = $control + 1;
                                            }
                                        }                                        
                                    }
                                    
                                }else{
                                    if(trim($subElementoError) === ''){
                                        unset($camposFexavaAvaluo['ERRORES'][$idElementoError][$idSubElementoError]);
                                    }else{
                                        $control = $control + 1;
                                    }
                                }
                            }
                            if($control === 0){
                                unset($camposFexavaAvaluo['ERRORES'][$idElementoError]);
                            }
                            
                        }
                    }else{
                        if(trim($elementoError) === ''){
                            unset($camposFexavaAvaluo['ERRORES'][$idElementoError]);
                        }
                    }
                    
                    $arrn = array();
                    foreach($camposFexavaAvaluo['ERRORES'] as $elementoError){
                        $arrn[] = $elementoError;
                    }
                }
                //Log::info($arrn);
                return response()->json(['mensaje' => $this->reordenaErrores($arrn)], 500);
            }
            $this->guardaAvance($nombreArchivo,55);
            //echo $this->fileXML->asXML(); exit();
            $mynewfile = fopen($file, "w");
            $fwrite = fwrite($mynewfile, $this->fileXML->asXML());    
            fclose($mynewfile);

            /*$myfile = fopen($file, "r");
            $contents = fread($myfile, filesize($file));    
            fclose($myfile);*/
            //echo "AHORA SOY CONTENIDO "; echo $contents; exit();
            $archivoComprimido = $this->comprimir($file);
            $ficheroAvaluo = $this->modelDocumentos->tran_InsertFicheroAvaluo($idDocumentoDigital, $nombreXMLAvaluo, null, $archivoComprimido);
            $resInsert = $this->modelGuardaenBD->insertAvaluo($camposFexavaAvaluo);
            $this->guardaAvance($nombreArchivo,100);
            /*return $resInsert; 
            exit();  */            
            
            if($resInsert == TRUE){
               
                $numeroUnico = $this->modelDocumentos->get_numero_unico_db($camposFexavaAvaluo['IDAVALUO']);               
                $this->recibeAvaluo($idPersona,$numeroUnico,$contents);
                return response()->json(['Estado' => $resInsert,'numeroUnico' => $numeroUnico], 200);
            }else{
                return response()->json(['mensaje' => $resInsert], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '18. Error en el servidor'], 500);
        }    
    }

    public function traduceErrorFormato($cadenaError){
        $arrCadena = '';
        if(strpos($cadenaError, "Opening and ending tag mismatch") != false){
            
            $cadenas = array("Opening and ending tag mismatch" => "No coinciden las etiquetas de apertura y final",
                            "parser error" => "error del analizador",
                            "line" => "línea",
                            "and" => "y",
                            " in" => "");
            
            foreach($cadenas as $en => $es){
                $cadenaError = str_replace($en,$es,$cadenaError);
            }
            $arrCadena = explode(":",$cadenaError);
            $subArray = explode("/",$arrCadena[6]);
            $textoError = $arrCadena[0].": ".$arrCadena[3].": ".$arrCadena[4].": ".$arrCadena[5].": ".$subArray[0];

        }else{

            return $cadenaError;

        }
        
        return $textoError;
    }

    public function reordenaErrores($arrn){
        $control = 0;
        $arregloFinal = array();
        foreach($arrn as $elemArr){
            if(is_array($elemArr)){
                foreach($elemArr as $elem){
                    if(is_array($elem)){
                        foreach($elem as $el){
                            $arregloFinal[] = array($el);
                        }
                    }else{
                        $arregloFinal[] = array($elem);
                    }
                }
            }else{
                $arregloFinal[] = array($elemArr);
            }
        }
        //print_r($arregloFinal); exit();
        return $arregloFinal;
    }

    public function limpiaRepetidos($arrn){
        //Log::info($arrn);
        $arrLimpio = array();
        $textoLineaAnt = '';
        $numeroLineaAnt = '';
        foreach($arrn[0] as $idn => $elementon){
            if(trim($elementon) != ''){
                $arrElemento = explode(' ',$elementon);
                if(count($arrElemento) > 3){
                    $textoLinea = $arrElemento[2];
                    $numeroLinea = $arrElemento[3];
                    //Log::info($textoLineaAnt." ".$textoLinea." | ".$numeroLineaAnt." ".$numeroLinea);
                    if($textoLinea === $textoLineaAnt && $numeroLinea === $numeroLineaAnt){
                        unset($arrn[0][$idn]);
                    }else{
                        $textoLineaAnt = $textoLinea;
                        $numeroLineaAnt = $numeroLinea;
                    }
                }

            }    
        }

        foreach($arrn[0] as $elementon){
            if(trim($elementon) !== ''){
                $arrLimpio[] = $elementon;
            }            
        }

        return $arrLimpio;
    }

    public function guardaAvance($nombreArchivo,$porcentaje){
        $arrNombreArchivo = explode('.',$nombreArchivo);
        $rutaArchivos = getcwd();
        $comando = "echo ".$porcentaje." > ".$rutaArchivos."/Avance_".$arrNombreArchivo[0].".txt";
        //shell_exec($comando);    
    }

    public function leerAvance($nombreArchivo){
        $arrNombreArchivo = explode('.',$nombreArchivo);
        $rutaArchivos = getcwd();
        $archivoAvance = $rutaArchivos."/Avance_".$arrNombreArchivo[0].".txt";
        $myfile = fopen($archivoAvance, "r");
        $res = fread($myfile, filesize($archivoAvance));    
        fclose($myfile);
        return response()->json(['Avance' => $res], 200);
    }

    public function camposFexAva()
    {
        try {
           $camposFexavaAvaluo = DB::select("SELECT column_name FROM all_tab_columns WHERE table_name = 'FEXAVA_AVALUO'");
           //print_r($camposFexavaAvaluo); exit();
           $arrCamposFexavaAvaluo = array();
           foreach($camposFexavaAvaluo as $elemento){
               $arrCamposFexavaAvaluo[$elemento->column_name] = '';
           }           
           return $arrCamposFexavaAvaluo;
           
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '19. Error en el servidor'], 500);
        }
    }

    public function guardarAvaluoIdentificacion($infoXmlIdentificacion, $camposFexavaAvaluo, $idPersona,$elementoPrincipal){
        try{
            $errores = valida_AvaluoIdentificacion($infoXmlIdentificacion);
        
            if(count($errores) > 0){
                $camposFexavaAvaluo['ERRORES'][] = $errores;
                //return array('ERROR' => $errores);
            }
            $arrIdentificacion = array();
            foreach($infoXmlIdentificacion[0] as $llave => $elemento){
                $arrIdentificacion[$llave] = (String)($elemento);
            }
            //$errores = array(0 => "LOS IDS ".$idPersona." ".$this->modelDatosExtrasAvaluo->IdPeritoSociedadByRegistro($arrIdentificacion['ClaveValuador'], '',true)); $camposFexavaAvaluo['ERRORES'][] = $errores; return $camposFexavaAvaluo;
            if($idPersona != $this->modelDatosExtrasAvaluo->IdPeritoSociedadByRegistro($arrIdentificacion['ClaveValuador'], '',true)){
                /*$errores = array(0 => "LOS IDS ".$idPersona." ".$this->modelDatosExtrasAvaluo->IdPeritoSociedadByRegistro($arrIdentificacion['ClaveValuador'], '',true)); 
                $camposFexavaAvaluo['ERRORES'][] = $errores;*/
                $errores = array(0 => 'Un perito no puede subir avalúos a nombre de otro perito');
                $camposFexavaAvaluo['ERRORES'][] = $errores;
                //return array('ERROR' => $errores);
            }//exit();

            $resExiste = $this->modelDocumentos->valida_existencia($arrIdentificacion['NumeroDeAvaluo'],$idPersona);

            if($resExiste == TRUE){
                $errores = array(0 => 'a.1 - Ya existe un avalúo registrado con n° avalúo: '.$arrIdentificacion['NumeroDeAvaluo']);
                $camposFexavaAvaluo['ERRORES'][] = $errores;
                //return array('ERROR' => $errores);
            }
            
            if($arrIdentificacion['NumeroDeAvaluo'] != ''){            
                $camposFexavaAvaluo['NUMEROAVALUO'] = $arrIdentificacion['NumeroDeAvaluo'];
            } 
            if($arrIdentificacion['FechaAvaluo'] != ''){            
                $camposFexavaAvaluo['FECHAAVALUO'] = $arrIdentificacion['FechaAvaluo'];
            }
            if($arrIdentificacion['ClaveValuador'] != ''){            
                $registroPerito = $arrIdentificacion['ClaveValuador'];
                $camposFexavaAvaluo['IDPERSONAPERITO'] = $idPersona;
            }
            if($arrIdentificacion['ClaveSociedad'] != ''){            
                $registroSoci = $arrIdentificacion['ClaveSociedad'];
                /*$camposFexavaAvaluo['IDPERSONAPERITO'] = $this->modelDatosExtrasAvaluo->IdPeritoSociedadByRegistro($idPersona, true);//aqui se usa IdPeritoSociedadByRegistro(registroPerito, string.Empty, true);
                $camposFexavaAvaluo['IDPERSONASOCIEDAD'] = $this->modelDatosExtrasAvaluo->IdPeritoSociedadByRegistro($idPersona, false);//aqui se usa IdPeritoSociedadByRegistro(registroPerito, registroSoci, false);*/
                $camposFexavaAvaluo['IDPERSONAPERITO'] = $this->modelDatosExtrasAvaluo->IdPeritoSociedadByRegistro($registroPerito, '',true);//aqui se usa IdPeritoSociedadByRegistro(registroPerito, string.Empty, true);
                $camposFexavaAvaluo['IDPERSONASOCIEDAD'] = $this->modelDatosExtrasAvaluo->IdPeritoSociedadByRegistro($registroPerito, $registroSoci, false);//aqui se usa IdPeritoSociedadByRegistro(registroPerito, registroSoci, false);*
            }
            
            if($camposFexavaAvaluo['CODTIPOTRAMITE'] == 2){
                $tipo = "CAT";
            }else if($camposFexavaAvaluo['CODTIPOTRAMITE'] == 1){
                $tipo = "COM";
            }    

            $camposFexavaAvaluo['NUMEROUNICO'] = $this->obtenerNumUnicoAv($tipo);      
            return $camposFexavaAvaluo;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '20. Error en el servidor'], 500);
        }
        
    }

    public function guardarAvaluoAntecedentes($infoXmlAntecedentes, $camposFexavaAvaluo,$elementoPrincipal){
        try{
            $infoXmlSolicitante = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//Solicitante[@id="b.1"]');
            $errores = valida_AvaluoAntecedentes($infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]'), $elementoPrincipal);
            if(count($errores) > 0){
                //return array('ERROR' => $errores);
                $camposFexavaAvaluo['ERRORES'][] = $errores;
            }
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante'] = array();
            foreach($infoXmlSolicitante[0] as $llave => $elemento){            
                if($llave == 'Alcaldia'){
                    $arrSolicitante[$llave] = convierte_a_arreglo($elemento);
                }else{
                    $arrSolicitante[$llave] = (String)($elemento);
                }
            }

            $this->Documentos = new Documentos();

            if(trim($arrSolicitante['A.Paterno']) != ''){
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['APELLIDOPATERNO'] = $arrSolicitante['A.Paterno'];
            }
            if(trim($arrSolicitante['A.Materno']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['APELLIDOMATERNO'] = $arrSolicitante['A.Materno'];
            }
            if(trim($arrSolicitante['Nombre']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBRE'] = $arrSolicitante['Nombre'];
            }
            if(trim($arrSolicitante['Calle']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['CALLE'] = $arrSolicitante['Calle'];
            }
            if(trim($arrSolicitante['NumeroInterior']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NUMEROINTERIOR'] = $arrSolicitante['NumeroInterior'];
            }
            if(trim($arrSolicitante['NumeroExterior']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NUMEROEXTERIOR'] = $arrSolicitante['NumeroExterior'];
            }
            if(trim($arrSolicitante['CodigoPostal']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['CODIGOPOSTAL'] = $arrSolicitante['CodigoPostal'];
            } //error_log("EN JSON ".json_encode($arrSolicitante));
            if(is_int($arrSolicitante['Alcaldia']['ClaveAlcaldia'])){
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['IDDELEGACION'] = $arrSolicitante['Alcaldia']['ClaveAlcaldia'];
                if($arrSolicitante['Alcaldia']['ClaveAlcaldia'] == '018'){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBREDELEGACION'] = $arrSolicitante['Alcaldia']['Otros'];
                }else{
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBREDELEGACION'] = $this->Documentos->ObtenerNombreDelegacionPorClave($arrSolicitante['Alcaldia']['ClaveAlcaldia']);
                }
                
            }else{
                //aqui se obtendria el iddelegacion por el nombre
                /*$idDelegacion = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorNombre($arrSolicitante['Alcaldia']['ClaveAlcaldia']);
                if($idDelegacion != -1){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['IDDELEGACION'] = $idDelegacion;
                }
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBREDELEGACION'] = $arrSolicitante['Alcaldia'];*/            

                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['IDDELEGACION'] = intval($arrSolicitante['Alcaldia']['ClaveAlcaldia']);
                if($arrSolicitante['Alcaldia']['ClaveAlcaldia'] == '018'){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBREDELEGACION'] = $arrSolicitante['Alcaldia']['Otros'];
                }else{
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBREDELEGACION'] = $this->Documentos->ObtenerNombreDelegacionPorClave($arrSolicitante['Alcaldia']['ClaveAlcaldia']);
                }
            }
            if(trim($arrSolicitante['Colonia']) != ''){
                if($arrSolicitante['Alcaldia']['ClaveAlcaldia'] == '018'){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['IDCOLONIA'] = '';
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBRECOLONIA'] = '';
                }else{
                    //aqui se obtendria el idColonia por el nombre
                    $idColonia = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion(trim($arrSolicitante['Colonia']), $arrSolicitante['Alcaldia']['ClaveAlcaldia']);
                    if($idColonia != -1){
                        $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['IDCOLONIA'] = $idColonia;
                    }
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBRECOLONIA'] = $arrSolicitante['Colonia'];
                }
                
            }

            if(trim($arrSolicitante['TipoPersona']) != ''){
                if(strtoupper($arrSolicitante['TipoPersona']) == 'F'){
                    if(!isset($arrSolicitante['A.Paterno']) || trim($arrSolicitante['A.Paterno']) == ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.1.10 - Error en el tipo de persona: La persona física debe contener el apellido paterno (b.1.1)";                    
                    }
                }

                if(strtoupper($arrSolicitante['TipoPersona']) == 'M'){
                    if(isset($arrSolicitante['A.Paterno']) && trim($arrSolicitante['A.Paterno']) != ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.1.10 - Error en el tipo de persona: La persona moral no puede tener apellido paterno (b.1.1)";                    
                    }

                    if(isset($arrSolicitante['A.Materno']) && trim($arrSolicitante['A.Materno']) != ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.1.10 - Error en el tipo de persona: La persona moral no puede tener apellido materno (b.1.2)";                    
                    }
                }

                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['TIPOPERSONA'] = $arrSolicitante['TipoPersona'];
            }

            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['CODTIPOFUNCION'] = "S";

            /************************************************************/

            $infoXmlPropietario = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//Propietario[@id="b.2"]');
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario'] = array();
            foreach($infoXmlPropietario[0] as $llave => $elemento){           
                if($llave == 'Alcaldia'){
                    $arrPropietario[$llave] = convierte_a_arreglo($elemento);
                }else{
                    $arrPropietario[$llave] = (String)($elemento);
                }
            }
            if(trim($arrPropietario['A.Paterno']) != ''){
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['APELLIDOPATERNO'] = $arrPropietario['A.Paterno'];
            }
            if(trim($arrPropietario['A.Materno']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['APELLIDOMATERNO'] = $arrPropietario['A.Materno'];
            }
            if(trim($arrPropietario['Nombre']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBRE'] = $arrPropietario['Nombre'];
            }
            if(trim($arrPropietario['Calle']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['CALLE'] = $arrPropietario['Calle'];
            }
            if(trim($arrPropietario['NumeroInterior']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NUMEROINTERIOR'] = $arrPropietario['NumeroInterior'];
            }
            if(trim($arrPropietario['NumeroExterior']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NUMEROEXTERIOR'] = $arrPropietario['NumeroExterior'];
            }
            if(trim($arrPropietario['CodigoPostal']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['CODIGOPOSTAL'] = $arrPropietario['CodigoPostal'];
            }
            if(is_int($arrPropietario['Alcaldia'])){
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['IDDELEGACION'] = $arrPropietario['Alcaldia']['ClaveAlcaldia'];
                if($arrPropietario['Alcaldia']['ClaveAlcaldia'] == '018'){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBREDELEGACION'] = $arrSolicitante['Alcaldia']['Otros'];
                }else{
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBREDELEGACION'] = $this->Documentos->ObtenerNombreDelegacionPorClave($arrPropietario['Alcaldia']['ClaveAlcaldia']);
                }
                
            }else{
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['IDDELEGACION'] = $arrPropietario['Alcaldia']['ClaveAlcaldia'];
                if($arrPropietario['Alcaldia']['ClaveAlcaldia'] == '018'){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBREDELEGACION'] = $arrSolicitante['Alcaldia']['Otros'];
                }else{
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBREDELEGACION'] = $this->Documentos->ObtenerNombreDelegacionPorClave($arrPropietario['Alcaldia']['ClaveAlcaldia']);
                }
                //aqui se obtendria el iddelegacion por el nombre
                /*$idDelegacion = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorNombre($arrSolicitante['Alcaldia']);
                if($idDelegacion != -1){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['IDDELEGACION'] = $idDelegacion;
                }
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBREDELEGACION'] = $arrPropietario['Alcaldia'];*/
            }
            if(trim($arrPropietario['Colonia']) != ''){
                if($arrPropietario['Alcaldia']['ClaveAlcaldia'] == '018'){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['IDCOLONIA'] = '';
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBRECOLONIA'] = '';
                }else{
                    //aqui se obtendria el idColonia por el nombre
                    $idColonia = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion(trim($arrSolicitante['Colonia']), $arrSolicitante['Alcaldia']['ClaveAlcaldia']);
                    if($idColonia != -1){
                        $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['IDCOLONIA'] = $idColonia;
                    }
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBRECOLONIA'] = $arrPropietario['Colonia'];
                }
                
            }

            if(trim($arrPropietario['TipoPersona']) != ''){

                if(strtoupper($arrPropietario['TipoPersona']) == 'F'){
                    if(!isset($arrPropietario['A.Paterno']) || trim($arrPropietario['A.Paterno']) == ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.2.10 - Error en el tipo de persona: La persona física debe contener el apellido paterno (b.2.1)";                    
                    }
                }

                if(strtoupper($arrPropietario['TipoPersona']) == 'M'){
                    if(isset($arrPropietario['A.Paterno']) && trim($arrPropietario['A.Paterno']) != ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.2.10 - Error en el tipo de persona: La persona moral no puede tener apellido paterno (b.2.1)";                    
                    }

                    if(isset($arrPropietario['A.Materno']) && trim($arrPropietario['A.Materno']) != ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.2.10 - Error en el tipo de persona: La persona moral no puede tener apellido materno (b.2.2)";                    
                    }
                }

                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['TIPOPERSONA'] = $arrPropietario['TipoPersona'];
            }
            
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['CODTIPOFUNCION'] = "P";

            /************************************************************/

            $infoXmlCuentaCatastral = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//InmuebleQueSeValua[@id="b.3"]//CuentaCatastral[@id="b.3.10"]');
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['CuentaCatastral'] = array();
            foreach($infoXmlCuentaCatastral[0] as $llave => $elemento){
                $arrCuentaCatastral[$llave] = (String)($elemento);
            }
            
            if(trim($arrCuentaCatastral['Region'] != '')){
                $camposFexavaAvaluo['REGION'] = $arrCuentaCatastral['Region'];
            }

            if(trim($arrCuentaCatastral['Manzana'] != '')){
                $camposFexavaAvaluo['MANZANA'] = $arrCuentaCatastral['Manzana'];
            }

            if(trim($arrCuentaCatastral['Lote'] != '')){
                $camposFexavaAvaluo['LOTE'] = $arrCuentaCatastral['Lote'];
            }

            if(trim($arrCuentaCatastral['Localidad'] != '')){
                $camposFexavaAvaluo['UNIDADPRIVATIVA'] = $arrCuentaCatastral['Localidad'];
            }

            if(trim($arrCuentaCatastral['DigitoVerificador'] != '')){
                $camposFexavaAvaluo['DIGITOVERIFICADOR'] = $arrCuentaCatastral['DigitoVerificador'];
            }

            /************************************************************/

            $infoXmlPropositoDelAvaluo = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//PropositoDelAvaluo[@id="b.4"]');
            $infoXmlPropositoDelAvaluo = convierte_a_arreglo($infoXmlPropositoDelAvaluo); //print_r($infoXmlPropositoDelAvaluo); exit();
            if(isset($infoXmlPropositoDelAvaluo[0]['ClavePropositoAvaluo']) && $infoXmlPropositoDelAvaluo[0]['ClavePropositoAvaluo'] == 4){
                $camposFexavaAvaluo['PROPOSITO'] = $infoXmlPropositoDelAvaluo[0]['Otros'];
            }else{    
                $camposFexavaAvaluo['PROPOSITO'] = getPropositoAvaluo($infoXmlPropositoDelAvaluo[0]['ClavePropositoAvaluo']);
                //$camposFexavaAvaluo['PROPOSITO'] = (String)($infoXmlPropositoDelAvaluo[0]);
            }
                    
            //print_r($camposFexavaAvaluo); exit();
            /************************************************************/

            $infoXmlObjetoDelAvaluo = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//ObjetoDelAvaluo[@id="b.5"]');        
            $camposFexavaAvaluo['OBJETO'] = (String)($infoXmlObjetoDelAvaluo[0]);


            $arrAntecedentes = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]');
            
            $datab = array_map("convierte_a_arreglo",$arrAntecedentes);
            if(isset($datab[0]['RegimenDePropiedad'])){
                $camposFexavaAvaluo['CODREGIMENPROPIEDAD'] = $datab[0]['RegimenDePropiedad'];
            }

            if(isset($datab[0]['TipoDeInmueble'])){
                if($datab[0]['TipoDeInmueble']['ClaveTipoInmueble'] == 19){
                    $camposFexavaAvaluo['TIPOCONDOMINIO'] = $datab[0]['TipoDeInmueble']['Otros'];
                }else{
                    $camposFexavaAvaluo['TIPOCONDOMINIO'] = getTipoDeInmueble(trim($datab[0]['TipoDeInmueble']['ClaveTipoInmueble']));
                }
                
            }

            return $camposFexavaAvaluo;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '22. Error en el servidor'], 500);
        }        
        
    }

    public function guardaDatosExtra($infoXmlAntecedentes,$elementoPrincipal,$idAvaluo){
        try{
            $infoEstra = convierte_a_arreglo($infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]'));
        
            $cuenta_agua = !is_array($infoEstra[0]['InmuebleQueSeValua']['CuentaDeAgua']) ? $infoEstra[0]['InmuebleQueSeValua']['CuentaDeAgua'] : '';
            $folio_real = 'Definir';
            $antecedente_registral = 'Definir';
            $calle = !is_array($infoEstra[0]['InmuebleQueSeValua']['Calle']) ? $infoEstra[0]['InmuebleQueSeValua']['Calle'] : '';
            $manzana = !is_array($infoEstra[0]['InmuebleQueSeValua']['Manzana']) ? $infoEstra[0]['InmuebleQueSeValua']['Manzana'] : '';
            $lote = !is_array($infoEstra[0]['InmuebleQueSeValua']['Lote']) ? $infoEstra[0]['InmuebleQueSeValua']['Lote'] : '';
            $numero_exterior = !is_array($infoEstra[0]['InmuebleQueSeValua']['NumeroExterior']) ? $infoEstra[0]['InmuebleQueSeValua']['NumeroExterior'] : '';
            $numero_interior = !is_array($infoEstra[0]['InmuebleQueSeValua']['NumeroInterior']) ? $infoEstra[0]['InmuebleQueSeValua']['NumeroInterior'] : '';
            $colonia = !is_array($infoEstra[0]['InmuebleQueSeValua']['Colonia']) ? $infoEstra[0]['InmuebleQueSeValua']['Colonia'] : '';
            $codigo_postal = !is_array($infoEstra[0]['InmuebleQueSeValua']['CodigoPostal']) ? $infoEstra[0]['InmuebleQueSeValua']['CodigoPostal'] : '';
            $alcaldia_municipio = !is_array($infoEstra[0]['InmuebleQueSeValua']['Alcaldia']) ? $infoEstra[0]['InmuebleQueSeValua']['Alcaldia'] : '';

            $p_cod = '';
            $p_desc = '';

            $procedure = 'BEGIN
            FEXAVA.FEXAVA_AVALUOS_PKG.FEXAVA_INSERT_INFODGPC_P(
                :PAR_IDAVALUO,
                :PAR_CUENTA_AGUA,
                :PAR_FOLIO_REAL,
                :PAR_ANTECEDENTE_REGISTRAL,        
                :PAR_CALLE,
                :PAR_MANZANA,
                :PAR_LOTE,
                :PAR_NUMERO_EXTERIOR,
                :PAR_NUMERO_INTERIOR,
                :PAR_COLONIA,
                :PAR_CODIGO_POSTAL,
                :PAR_ALCALDIA_MUNICIPIO,
                :P_COD,
                :P_DESC
            ); END;';
            $pdo = DB::getPdo();
            $stmt = $pdo->prepare($procedure);
            $stmt->bindParam(':PAR_IDAVALUO', $idAvaluo, \PDO::PARAM_INT,20);
            $stmt->bindParam(':PAR_CUENTA_AGUA',$cuenta_agua,\PDO::PARAM_STR,50);
            $stmt->bindParam(':PAR_FOLIO_REAL',$folio_real, \PDO::PARAM_STR,20);
            $stmt->bindParam(':PAR_ANTECEDENTE_REGISTRAL',$antecedente_registral,\PDO::PARAM_STR,50);        
            $stmt->bindParam(':PAR_CALLE',$calle,\PDO::PARAM_STR,50);
            $stmt->bindParam(':PAR_MANZANA',$manzana,\PDO::PARAM_STR,50);
            $stmt->bindParam(':PAR_LOTE',$lote,\PDO::PARAM_STR,50);
            $stmt->bindParam(':PAR_NUMERO_EXTERIOR',$numero_exterior,\PDO::PARAM_STR,25);
            $stmt->bindParam(':PAR_NUMERO_INTERIOR',$numero_interior,\PDO::PARAM_STR,30);
            $stmt->bindParam(':PAR_COLONIA',$colonia,\PDO::PARAM_STR,50);
            $stmt->bindParam(':PAR_CODIGO_POSTAL',$codigo_postal,\PDO::PARAM_STR,5);
            $stmt->bindParam(':PAR_ALCALDIA_MUNICIPIO',$alcaldia_municipio,\PDO::PARAM_STR,50);
            $stmt->bindParam(':P_COD',$p_cod,\PDO::PARAM_INT,20);
            $stmt->bindParam(':P_DESC',$p_desc,\PDO::PARAM_STR,200);
            $stmt->execute();
            $stmt->closeCursor();
            $pdo->commit();
            $pdo->close();
            DB::commit();
            DB::reconnect();
            if($p_cod != '' && $p_desc != ''){
                return true;
            }else{
                return false;
            }
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '22. Error en el servidor'], 500);
        }
        
    }

    public function guardarAvaluoAntecedentesV($infoXmlAntecedentes, $camposFexavaAvaluo,$elementoPrincipal){
        try{
            $infoXmlSolicitante = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//Solicitante[@id="b.1"]');
            $errores = valida_AvaluoAntecedentesV($infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]'), $elementoPrincipal);
            if(count($errores) > 0){
                //return array('ERROR' => $errores);
                $camposFexavaAvaluo['ERRORES'][] = $errores;
            }
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante'] = array();
            foreach($infoXmlSolicitante[0] as $llave => $elemento){
                $arrSolicitante[$llave] = (String)($elemento);
            }

            if(trim($arrSolicitante['A.Paterno']) != ''){
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['APELLIDOPATERNO'] = $arrSolicitante['A.Paterno'];
            }
            if(trim($arrSolicitante['A.Materno']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['APELLIDOMATERNO'] = $arrSolicitante['A.Materno'];
            }
            if(trim($arrSolicitante['Nombre']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBRE'] = $arrSolicitante['Nombre'];
            }
            if(trim($arrSolicitante['Calle']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['CALLE'] = $arrSolicitante['Calle'];
            }
            if(trim($arrSolicitante['NumeroInterior']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NUMEROINTERIOR'] = $arrSolicitante['NumeroInterior'];
            }
            if(trim($arrSolicitante['NumeroExterior']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NUMEROEXTERIOR'] = $arrSolicitante['NumeroExterior'];
            }
            if(trim($arrSolicitante['CodigoPostal']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['CODIGOPOSTAL'] = $arrSolicitante['CodigoPostal'];
            }
            if(is_int($arrSolicitante['Delegacion'])){
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['IDDELEGACION'] = $arrSolicitante['Delegacion'];
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBREDELEGACION'] = '';
            }else{
                //aqui se obtendria el iddelegacion por el nombre
                $idDelegacion = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorNombre($arrSolicitante['Delegacion']);
                if($idDelegacion != -1){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['IDDELEGACION'] = $idDelegacion;
                }
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBREDELEGACION'] = $arrSolicitante['Delegacion'];
            }
            if(trim($arrSolicitante['Colonia']) != ''){
                //aqui se obtendria el idColonia por el nombre
                $idColonia = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion(trim($arrSolicitante['Colonia']), $arrSolicitante['Delegacion']);
                if($idColonia != -1){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['IDCOLONIA'] = $idColonia;
                }
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['NOMBRECOLONIA'] = $arrSolicitante['Colonia'];
            }

            if(trim($arrSolicitante['TipoPersona']) != ''){
                if(strtoupper($arrSolicitante['TipoPersona']) == 'F'){
                    if(!isset($arrSolicitante['A.Paterno']) || trim($arrSolicitante['A.Paterno']) == ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.1.10 - Error en el tipo de persona: La persona física debe contener el apellido paterno (b.1.1)";                    
                    }
                }

                if(strtoupper($arrSolicitante['TipoPersona']) == 'M'){
                    if(isset($arrSolicitante['A.Paterno']) && trim($arrSolicitante['A.Paterno']) != ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.1.10 - Error en el tipo de persona: La persona moral no puede tener apellido paterno (b.1.1)";                    
                    }

                    if(isset($arrSolicitante['A.Materno']) && trim($arrSolicitante['A.Materno']) != ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.1.10 - Error en el tipo de persona: La persona moral no puede tener apellido materno (b.1.2)";                    
                    }
                }

                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['TIPOPERSONA'] = $arrSolicitante['TipoPersona'];
            }

            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Solicitante']['CODTIPOFUNCION'] = "S";

            /************************************************************/

            $infoXmlPropietario = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//Propietario[@id="b.2"]');
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario'] = array();
            foreach($infoXmlPropietario[0] as $llave => $elemento){
                $arrPropietario[$llave] = (String)($elemento);
            }
            if(trim($arrPropietario['A.Paterno']) != ''){
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['APELLIDOPATERNO'] = $arrPropietario['A.Paterno'];
            }
            if(trim($arrPropietario['A.Materno']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['APELLIDOMATERNO'] = $arrPropietario['A.Materno'];
            }
            if(trim($arrPropietario['Nombre']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBRE'] = $arrPropietario['Nombre'];
            }
            if(trim($arrPropietario['Calle']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['CALLE'] = $arrPropietario['Calle'];
            }
            if(trim($arrPropietario['NumeroInterior']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NUMEROINTERIOR'] = $arrPropietario['NumeroInterior'];
            }
            if(trim($arrPropietario['NumeroExterior']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NUMEROEXTERIOR'] = $arrPropietario['NumeroExterior'];
            }
            if(trim($arrPropietario['CodigoPostal']) != ''){
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['CODIGOPOSTAL'] = $arrPropietario['CodigoPostal'];
            }
            if(is_int($arrPropietario['Delegacion'])){
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['IDDELEGACION'] = $arrPropietario['Delegacion'];
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBREDELEGACION'] = '';
            }else{
                //aqui se obtendria el iddelegacion por el nombre
                $idDelegacion = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorNombre($arrSolicitante['Delegacion']);
                if($idDelegacion != -1){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['IDDELEGACION'] = $idDelegacion;
                }
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBREDELEGACION'] = $arrPropietario['Delegacion'];
            }
            if(trim($arrPropietario['Colonia']) != ''){
                //aqui se obtendria el idColonia por el nombre
                $idColonia = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion(trim($arrSolicitante['Colonia']), $arrSolicitante['Delegacion']);
                if($idColonia != -1){
                    $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['IDCOLONIA'] = $idColonia;
                }
                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['NOMBRECOLONIA'] = $arrPropietario['Colonia'];
            }

            if(trim($arrPropietario['TipoPersona']) != ''){

                if(strtoupper($arrPropietario['TipoPersona']) == 'F'){
                    if(!isset($arrPropietario['A.Paterno']) || trim($arrPropietario['A.Paterno']) == ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.2.10 - Error en el tipo de persona: La persona física debe contener el apellido paterno (b.2.1)";                    
                    }
                }

                if(strtoupper($arrPropietario['TipoPersona']) == 'M'){
                    if(isset($arrPropietario['A.Paterno']) && trim($arrPropietario['A.Paterno']) != ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.2.10 - Error en el tipo de persona: La persona moral no puede tener apellido paterno (b.2.1)";                    
                    }

                    if(isset($arrPropietario['A.Materno']) && trim($arrPropietario['A.Materno']) != ''){
                        $validadoSol = false;
                        $camposFexavaAvaluo['ERRORES'][] = "b.2.10 - Error en el tipo de persona: La persona moral no puede tener apellido materno (b.2.2)";                    
                    }
                }

                $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['TIPOPERSONA'] = $arrPropietario['TipoPersona'];
            }
            
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['Propietario']['CODTIPOFUNCION'] = "P";

            /************************************************************/

            $infoXmlCuentaCatastral = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//InmuebleQueSeValua[@id="b.3"]//CuentaCatastral[@id="b.3.10"]');
            $camposFexavaAvaluo['FEXAVA_DATOSPERSONAS']['CuentaCatastral'] = array();
            foreach($infoXmlCuentaCatastral[0] as $llave => $elemento){
                $arrCuentaCatastral[$llave] = (String)($elemento);
            }
            
            if(trim($arrCuentaCatastral['Region'] != '')){
                $camposFexavaAvaluo['REGION'] = $arrCuentaCatastral['Region'];
            }

            if(trim($arrCuentaCatastral['Manzana'] != '')){
                $camposFexavaAvaluo['MANZANA'] = $arrCuentaCatastral['Manzana'];
            }

            if(trim($arrCuentaCatastral['Lote'] != '')){
                $camposFexavaAvaluo['LOTE'] = $arrCuentaCatastral['Lote'];
            }

            if(trim($arrCuentaCatastral['Localidad'] != '')){
                $camposFexavaAvaluo['UNIDADPRIVATIVA'] = $arrCuentaCatastral['Localidad'];
            }

            if(trim($arrCuentaCatastral['DigitoVerificador'] != '')){
                $camposFexavaAvaluo['DIGITOVERIFICADOR'] = $arrCuentaCatastral['DigitoVerificador'];
            }

            /************************************************************/

            $infoXmlPropositoDelAvaluo = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//PropositoDelAvaluo[@id="b.4"]');
            $camposFexavaAvaluo['PROPOSITO'] = (String)($infoXmlPropositoDelAvaluo[0]);        

            /************************************************************/

            $infoXmlObjetoDelAvaluo = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//ObjetoDelAvaluo[@id="b.5"]');        
            $camposFexavaAvaluo['OBJETO'] = (String)($infoXmlObjetoDelAvaluo[0]);


            $arrAntecedentes = $infoXmlAntecedentes->xpath($elementoPrincipal.'//Antecedentes[@id="b"]');
            
            $datab = array_map("convierte_a_arreglo",$arrAntecedentes);
            if(isset($datab[0]['RegimenDePropiedad'])){
                $camposFexavaAvaluo['CODREGIMENPROPIEDAD'] = $datab[0]['RegimenDePropiedad'];
            }

            return $camposFexavaAvaluo;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '24. Error en el servidor'], 500);
        }   
        
    }

    public function guardarAvaluoCaracteristicasUrbanas($infoXmlCaracteristicas, $camposFexavaAvaluo,$elementoPrincipal){
        try{
            $infoXmlCaracteristicasUrbanas = $infoXmlCaracteristicas->xpath($elementoPrincipal.'//CaracteristicasUrbanas[@id="c"]');

            $errores = valida_AvaluoCaracteristicasUrbanas($infoXmlCaracteristicasUrbanas);   
            if(count($errores) > 0){
                //return array('ERROR' => $errores);
                $camposFexavaAvaluo['ERRORES'][] = $errores;
            }
            //$camposFexavaAvaluo['CaracteristicasUrbanas'] = array();
            foreach($infoXmlCaracteristicasUrbanas[0] as $llave => $elemento){
                $arrCaracteristicasUrbanas[$llave] = (String)($elemento);
            }

            if(trim($arrCaracteristicasUrbanas['ClaseGeneralDeInmueblesDeLaZona']) != ''){
                $camposFexavaAvaluo['CUIDCLASESEJERCICIO'] = $this->modelFis->getClaseEjercicio($arrCaracteristicasUrbanas['ClaseGeneralDeInmueblesDeLaZona']);
            }

            if(trim($arrCaracteristicasUrbanas['ClasificacionDeLaZona']) != ''){
                $camposFexavaAvaluo['CUCODCLASIFICACIONZONA'] = $arrCaracteristicasUrbanas['ClasificacionDeLaZona'];
            }

            if(trim($arrCaracteristicasUrbanas['IndiceDeSaturacionDeLaZona']) != ''){
                $camposFexavaAvaluo['CUINDICESATURACIONZONA'] = $arrCaracteristicasUrbanas['IndiceDeSaturacionDeLaZona'];
            }

            $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];
            if(trim($arrCaracteristicasUrbanas['ClaseGeneralDeInmueblesDeLaZona']) != ''){
                $codClase = $arrCaracteristicasUrbanas['ClaseGeneralDeInmueblesDeLaZona'];
                if(esFechaValida($fechaAvaluo) == true){
                    $idClaseEjercicio = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdClasesByCodeAndAno(darFormatoFechaXML($fechaAvaluo), $codClase); //No se si el query sea el correcto ya que obtiene por fecha pero no hay fecha en la tabla
                }else{
                    $idClaseEjercicio = 0;
                }

                
                $camposFexavaAvaluo['CUCODCLASESCONSTRUCCION'] = $idClaseEjercicio;
            }

            if(trim($arrCaracteristicasUrbanas['DensidadDePoblacion']) != ''){
                $camposFexavaAvaluo['CUCODDENSIDADPOBLACION'] = $arrCaracteristicasUrbanas['DensidadDePoblacion'];
            }

            if(trim($arrCaracteristicasUrbanas['NivelSocioeconomicoDeLaZona']) != ''){
                $camposFexavaAvaluo['CUCODNIVELSOCIOECONOMICO'] = $arrCaracteristicasUrbanas['NivelSocioeconomicoDeLaZona'];
            }

            /********************************Uso del Suelo**********************************/
            $infoXmlUsoDelSuelo = $infoXmlCaracteristicas->xpath($elementoPrincipal.'//CaracteristicasUrbanas[@id="c"]//UsoDelSuelo[@id="c.6"]');        
            foreach($infoXmlUsoDelSuelo[0] as $llave => $elemento){
                $arrUsoDelSuelos[$llave] = (String)($elemento);
            }

            if(trim($arrUsoDelSuelos['UsoDelSuelo']) != ''){
                $camposFexavaAvaluo['CUUSO'] = $arrUsoDelSuelos['UsoDelSuelo'];
            }

            if(trim($arrUsoDelSuelos['AreaLibreObligatoria']) != ''){
                $camposFexavaAvaluo['CUAREALIBREOBLIGATORIO'] = $arrUsoDelSuelos['AreaLibreObligatoria'];
            }

            if(trim($arrUsoDelSuelos['NumeroMaximoDeNivelesAConstruir']) != ''){
                $camposFexavaAvaluo['CUNUMMAXNIVELESACONSTRUIR'] = $arrUsoDelSuelos['NumeroMaximoDeNivelesAConstruir'];
            }

            if(trim($arrUsoDelSuelos['CoeficienteDeUsoDelSuelo']) != ''){
                $camposFexavaAvaluo['CUCOEFICIENTE'] = $arrUsoDelSuelos['CoeficienteDeUsoDelSuelo'];
            }

            /***************************************Servicios públicos y equipamiento urbano*************/

            $infoXmlServiciosPublicosYEquipamientoUrbano = $infoXmlCaracteristicas->xpath($elementoPrincipal.'//CaracteristicasUrbanas[@id="c"]//ServiciosPublicosYEquipamientoUrbano[@id="c.8"]');        
            foreach($infoXmlServiciosPublicosYEquipamientoUrbano[0] as $llave => $elemento){
                $arrServiciosPublicosYEquipamientoUrbanos[$llave] = (String)($elemento);
            }
            //print_r($arrServiciosPublicosYEquipamientoUrbanos); exit();
            if(trim($arrServiciosPublicosYEquipamientoUrbanos['RedDeDistribucionAguaPotable']) != ''){
                $camposFexavaAvaluo['CUCODAGUAPOTABLE'] = $arrServiciosPublicosYEquipamientoUrbanos['RedDeDistribucionAguaPotable'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['RedDeRecoleccionDeAguasResiduales']) != ''){
                $camposFexavaAvaluo['CUCODAGUAPOTABLERESIDUAL'] = $arrServiciosPublicosYEquipamientoUrbanos['RedDeRecoleccionDeAguasResiduales'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['RedDeDrenajeDeAguasPluvialesEnLaCalle']) != ''){
                $camposFexavaAvaluo['CUCODDRENAJEPLUVIALCALLE'] = $arrServiciosPublicosYEquipamientoUrbanos['RedDeDrenajeDeAguasPluvialesEnLaCalle'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['RedDeDrenajeDeAguasPluvialesEnLaZona']) != ''){
                $camposFexavaAvaluo['CUCODDRENAJEPLUVIALZONA'] = $arrServiciosPublicosYEquipamientoUrbanos['RedDeDrenajeDeAguasPluvialesEnLaZona'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['SistemaMixto']) != ''){
                $camposFexavaAvaluo['CUCODDRENAJEINMUEBLE'] = $arrServiciosPublicosYEquipamientoUrbanos['SistemaMixto'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['SuministroElectrico']) != ''){
                $camposFexavaAvaluo['CUCODSUMINISTROELECTRICO'] = $arrServiciosPublicosYEquipamientoUrbanos['SuministroElectrico'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['AcometidaAlInmueble']) != ''){
                $camposFexavaAvaluo['CUCODACOMETIDAINMUEBLE'] = $arrServiciosPublicosYEquipamientoUrbanos['AcometidaAlInmueble'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['AlumbradoPublico']) != ''){
                $camposFexavaAvaluo['CUCODALUMBRADOPUBLICO'] = $arrServiciosPublicosYEquipamientoUrbanos['AlumbradoPublico'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['Vialidades']) != ''){
                $camposFexavaAvaluo['CUCODVIALIDADES'] = $arrServiciosPublicosYEquipamientoUrbanos['Vialidades'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['Banquetas']) != ''){
                $camposFexavaAvaluo['CUCODBANQUETAS'] = $arrServiciosPublicosYEquipamientoUrbanos['Banquetas'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['Guarniciones']) != ''){
                $camposFexavaAvaluo['CUCODGUARNICIONES'] = $arrServiciosPublicosYEquipamientoUrbanos['Guarniciones'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['NivelDeInfraestructuraEnLaZona']) != ''){
                $camposFexavaAvaluo['CUPORCENTAJEINFRAESTRUCTURA'] = $arrServiciosPublicosYEquipamientoUrbanos['NivelDeInfraestructuraEnLaZona'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['GasNatural']) != ''){
                $camposFexavaAvaluo['CUCODGASNATURAL'] = $arrServiciosPublicosYEquipamientoUrbanos['GasNatural'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['TelefonosSuministro']) != ''){
                $camposFexavaAvaluo['CUCODSUMINISTROTELEFONICA'] = $arrServiciosPublicosYEquipamientoUrbanos['TelefonosSuministro'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['AcometidaAlInmuebleTel']) != ''){
                $camposFexavaAvaluo['CUCODACOMETIDAINMUEBLETEL'] = $arrServiciosPublicosYEquipamientoUrbanos['AcometidaAlInmuebleTel'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['SennalizacionDeVias']) != ''){
                $camposFexavaAvaluo['CUCODSENALIZACIONVIAS'] = $arrServiciosPublicosYEquipamientoUrbanos['SennalizacionDeVias'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['NomenclaturaDeCalles']) != ''){
                $camposFexavaAvaluo['CUCODNOMENCLATURACALLE'] = $arrServiciosPublicosYEquipamientoUrbanos['NomenclaturaDeCalles'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['DistanciaTranporteUrbano']) != ''){
                $camposFexavaAvaluo['CUDISTANCIATRANSPORTEURBANO'] = $arrServiciosPublicosYEquipamientoUrbanos['DistanciaTranporteUrbano'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['FrecuenciaTransporteUrbano']) != ''){
                $camposFexavaAvaluo['CUFRECUENCIATRANSPORTEURBANO'] = $arrServiciosPublicosYEquipamientoUrbanos['FrecuenciaTransporteUrbano'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['DistanciaTransporteSuburbano']) != ''){
                $camposFexavaAvaluo['CUDISTANCIATRANSPORTESUBURB'] = $arrServiciosPublicosYEquipamientoUrbanos['DistanciaTransporteSuburbano'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['FrecuenciaTransporteSuburbano']) != ''){
                $camposFexavaAvaluo['CUFRECUENCIATRANSPORTESUBURB'] = $arrServiciosPublicosYEquipamientoUrbanos['FrecuenciaTransporteSuburbano'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['Vigilancia']) != ''){
                $camposFexavaAvaluo['CUCODVIGILANCIAZONA'] = $arrServiciosPublicosYEquipamientoUrbanos['Vigilancia'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['RecoleccionDeBasura']) != ''){
                $camposFexavaAvaluo['CUCODRECOLECCIONBASURA'] = $arrServiciosPublicosYEquipamientoUrbanos['RecoleccionDeBasura'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['Templo']) != ''){
                $camposFexavaAvaluo['CUEXISTEIGLESIA'] = $arrServiciosPublicosYEquipamientoUrbanos['Templo'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['Mercados']) != ''){
                $camposFexavaAvaluo['CUEXISTEMERCADOS'] = $arrServiciosPublicosYEquipamientoUrbanos['Mercados'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['PlazasPublicas']) != ''){
                $camposFexavaAvaluo['CUEXISTEPLAZASPUBLICOS'] = $arrServiciosPublicosYEquipamientoUrbanos['PlazasPublicas'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['ParquesYJardines']) != ''){
                $camposFexavaAvaluo['CUEXISTEPARQUESJARDINES'] = $arrServiciosPublicosYEquipamientoUrbanos['ParquesYJardines'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['Escuelas']) != ''){
                $camposFexavaAvaluo['CUEXISTEESCUELAS'] = $arrServiciosPublicosYEquipamientoUrbanos['Escuelas'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['Hospitales']) != ''){
                $camposFexavaAvaluo['CUEXISTEHOSPITALES'] = $arrServiciosPublicosYEquipamientoUrbanos['Hospitales'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['Bancos']) != ''){
                $camposFexavaAvaluo['CUEXISTEBANCOS'] = $arrServiciosPublicosYEquipamientoUrbanos['Bancos'];
            }

            if(trim($arrServiciosPublicosYEquipamientoUrbanos['EstacionDeTransporte']) != ''){
                $camposFexavaAvaluo['CUEXISTEESTACIONTRANSPORTE'] = $arrServiciosPublicosYEquipamientoUrbanos['EstacionDeTransporte'];
            }

            return $camposFexavaAvaluo;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '25. Error en el servidor'], 500);
        }
        
    }   

    public function guardarAvaluoTerreno($infoXmlTerreno, $camposFexavaAvaluo,$elementoPrincipal,$idDocumentoDigital){
        try{      
        $datah = $infoXmlTerreno->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]'); 
        if($elementoPrincipal == '//Comercial'){
            if(isset($datah)){
                $errores = valida_AvaluoTerreno($infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]'), $elementoPrincipal, $datah);                
            }else{
                $errores = valida_AvaluoTerreno($infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]'), $elementoPrincipal);                
            }
            
        }else{
            $errores = valida_AvaluoTerreno($infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]'), $elementoPrincipal);            
        }
            
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;            
        } 
        $infoXmlCallesTransversalesLimitrofesYOrientacion = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//CallesTransversalesLimitrofesYOrientacion[@id="d.1"]');        
        $query = (String)($infoXmlCallesTransversalesLimitrofesYOrientacion[0]);

        $infoXmlCroquisMicroLocalizacion = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//CroquisMicroLocalizacion[@id="d.2"]');    
        $queryMicro = (String)($infoXmlCroquisMicroLocalizacion[0]);
        
        $infoXmlCroquisMacroLocalizacion = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//CroquisMacroLocalizacion[@id="d.3"]');        
        $queryMacro = (String)($infoXmlCroquisMacroLocalizacion[0]);

        //AQUI FALTA GUARDAR LAS FOTOS Y CAMBIAR LO QUE TRAIA DE INFORMACION EN EL XML POR LOS ID OBTENIDOS Tran_InsertFichero
        $listaIdFicheros = array();
        $idFichero = 0;

        /*$cuentaCatastral = $camposFexavaAvaluo['REGION'].'-'.
                           $camposFexavaAvaluo['MANZANA'].'-'.
                           $camposFexavaAvaluo['LOTE'].'-'.
                           $camposFexavaAvaluo['UNIDADPRIVATIVA'];
        $tipoDocumentoDigital = 13;*/
        $idUsuario = $camposFexavaAvaluo['IDPERSONAPERITO'];
        /*$idDocumentoDigital = $this->modelDocumentos->insertDocumentoDigital($cuentaCatastral, $tipoDocumentoDigital, $idUsuario);
        $camposFexavaAvaluo['IDAVALUO'] = $idDocumentoDigital;*/
        $fotoMicro = $queryMicro;
        $idFichero = $this->modelDocumentos->tran_InsertFichero($idDocumentoDigital, 'CroquisMicroLocalizacion', 'CroquisMicroLocalizacion', $fotoMicro);
        $listaIdFicheros[] = $idFichero;
        if($elementoPrincipal == "//Comercial"){
            $this->fileXML->Comercial->Terreno->CroquisMicroLocalizacion = $idFichero;
        }else{
            $this->fileXML->Catastral->Terreno->CroquisMicroLocalizacion = $idFichero;
        }
        
        $fotoMacro = $queryMacro;
        $idFichero = $this->modelDocumentos->tran_InsertFichero($idDocumentoDigital, 'CroquisMacroLocalizacion', 'CroquisMacroLocalizacion', $fotoMacro);
        $listaIdFicheros[] = $idFichero;
        if($elementoPrincipal == "//Comercial"){            
            $this->fileXML->Comercial->Terreno->CroquisMacroLocalizacion = $idFichero;
        }else{
            $this->fileXML->Catastral->Terreno->CroquisMacroLocalizacion = $idFichero;
        }
        
        //xpath($elementoPrincipal.'//Terreno[@id="d"]//CroquisMicroLocalizacion[@id="d.2"]');        
        $infoXmlEscritura = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]//Escritura[@id="d.4.1.1"]');
        
        $arrEscritura = array();
        $controlElemntos = 0;
        if($infoXmlEscritura){
            foreach($infoXmlEscritura[0] as $llave => $elemento){
                $arrEscritura[$llave] =(String)($elemento);
                if(trim($arrEscritura[$llave]) != ''){
                    $controlElemntos = $controlElemntos+1;
                }
            } //print_r($infoXmlEscritura); echo "SOY controlElemntos ".$controlElemntos; exit();
        }
        
        if($controlElemntos > 0){
            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG'] = array();
            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['CODTIPOFUENTEINFORMACION'] = '1';

            if(trim($arrEscritura['FechaEscritura']) != ''){
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['FECHA'] = $arrEscritura['FechaEscritura'];
            }

            $camposFexavaAvaluo['FEXAVA_ESCRITURA'] = array();
            if(trim($arrEscritura['NumeroDeEscritura']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['NUMESCRITURA'] =  $arrEscritura['NumeroDeEscritura'];
            }

            if(trim($arrEscritura['NumeroDeVolumen']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['NUMVOLUMEN'] =  $arrEscritura['NumeroDeVolumen'];
            }

            if(trim($arrEscritura['NumeroNotaria']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['NUMNOTARIO'] =  $arrEscritura['NumeroNotaria'];
            }

            if(trim($arrEscritura['NombreDelNotario']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['NOMBRENOTARIO'] =  $arrEscritura['NombreDelNotario'];
            }

            if(trim($arrEscritura['DistritoJudicialNotario']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['DISTRITOJUDICIALNOTARIO'] =  $arrEscritura['DistritoJudicialNotario'];
            }
        }
        /***********************************************************Sentencia**********************************************************************/
        
        $arrPrincipalFuente = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]');
        $arrIdsFuenteDeInformacionLegal = array();
        $arrFuenteDeInformacionLegal = array();
        foreach($arrPrincipalFuente[0] as $llave => $elemento){
            $arrIdsFuenteDeInformacionLegal[(String)($elemento['id'])] = $llave;
            $arrFuenteDeInformacionLegal[$llave] = $elemento;
        }
        
        if(isset($arrIdsFuenteDeInformacionLegal['d.4.1.2'])){
            $etiqueta = $arrIdsFuenteDeInformacionLegal['d.4.1.2'];
            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG'] = array();
            $infoXmlSentencia = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]//'.$etiqueta.'[@id="d.4.1.2"]');
            $arrIdsSentencia = array();
            $arrSentencia = array();            
            foreach($infoXmlSentencia[0] as $llave => $elemento){
                $arrIdsSentencia[(String)($elemento['id'])] = $llave;
                $arrSentencia[$llave] =(String)($elemento);                
            }

            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['CODTIPOFUENTEINFORMACION'] = '2';            

            if(isset($arrIdsSentencia['d.4.1.2.2']) and trim($arrSentencia[$arrIdsSentencia['d.4.1.2.2']]) != ''){
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['FECHA'] = $arrSentencia[$arrIdsSentencia['d.4.1.2.2']];
            }

            $camposFexavaAvaluo['FEXAVA_SENTENCIA'] = array();

            if(isset($arrIdsSentencia['d.4.1.2.1']) and trim($arrSentencia[$arrIdsSentencia['d.4.1.2.1']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SENTENCIA']['JUZGADO'] = $arrSentencia[$arrIdsSentencia['d.4.1.2.1']];
            }

            if(isset($arrIdsSentencia['d.4.1.2.3']) and trim($arrSentencia[$arrIdsSentencia['d.4.1.2.3']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SENTENCIA']['NUMEXPEDIENTE'] = $arrSentencia[$arrIdsSentencia['d.4.1.2.3']];
            }
        }

            /***********************************************************Contrato Privado**********************************************************************/

            if(isset($arrIdsFuenteDeInformacionLegal['d.4.1.3'])){
                $etiqueta = $arrIdsFuenteDeInformacionLegal['d.4.1.3'];
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG'] = array();
                $infoXmlContratoPrivado = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]//'.$etiqueta.'[@id="d.4.1.3"]');
                $arrIdsContratoPrivado = array();
                $arrContratoPrivado = array();            
                foreach($infoXmlContratoPrivado[0] as $llave => $elemento){
                    $arrIdsContratoPrivado[(String)($elemento['id'])] = $llave;
                    $arrContratoPrivado[$llave] =(String)($elemento);                
                }
                error_log("EL arrIdsContratoPrivado".json_encode($arrIdsContratoPrivado['d.4.1.3.2'])); error_log("la info de arrIdsContratoPrivado ".json_encode($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.2']]));
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['CODTIPOFUENTEINFORMACION'] = '3';                
    
                if(isset($arrIdsContratoPrivado['d.4.1.3.1']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.1']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['FECHA'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.1']];
                }
    
                $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO'] = array();
    
                if(isset($arrIdsContratoPrivado['d.4.1.3.2']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.2']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['NOMBREADQUIRIENTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.2']];
                }
    
                if(isset($arrIdsContratoPrivado['d.4.1.2.3']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.2.3']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['APELLIDOPATERNOADQUIRIENTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.2.3']];
                }

                if(isset($arrIdsContratoPrivado['d.4.1.3.4']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.4']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['APELLIDOMATERNOADQUIRIENTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.4']];
                }

                if(isset($arrIdsContratoPrivado['d.4.1.3.5']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.5']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['NOMBREENAJENANTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.5']];
                }

                if(isset($arrIdsContratoPrivado['d.4.1.3.6']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.6']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['APELLIDOPATERNOENAJENANTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.6']];
                }

                if(isset($arrIdsContratoPrivado['d.4.1.3.7']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.7']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['APELLIDOMATERNOENAJENANTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.7']];
                }

        }

        /***********************************************************Alineamiento y numero oficial**********************************************************************/

        if(isset($arrIdsFuenteDeInformacionLegal['d.4.1.4'])){
            $etiqueta = $arrIdsFuenteDeInformacionLegal['d.4.1.4'];
            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG'] = array();
            $infoXmlAlineamiento = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]//'.$etiqueta.'[@id="d.4.1.4"]');
            $arrIdsAlineamiento = array();
            $arrAlineamiento = array();            
            foreach($infoXmlAlineamiento[0] as $llave => $elemento){
                $arrIdsAlineamiento[(String)($elemento['id'])] = $llave;
                $arrAlineamiento[$llave] =(String)($elemento);                
            }

            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['CODTIPOFUENTEINFORMACION'] = '4';                

            if(isset($arrIdsAlineamiento['d.4.1.4.1']) and trim($arrAlineamiento[$arrIdsAlineamiento['d.4.1.4.1']]) != ''){
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['FECHA'] = $arrAlineamiento[$arrIdsAlineamiento['d.4.1.4.1']];
            }

            $camposFexavaAvaluo['FEXAVA_ALINEAMIENTONUMOFI'] = array();
            if(isset($arrIdsAlineamiento['d.4.1.4.2']) and trim($arrAlineamiento[$arrIdsAlineamiento['d.4.1.4.2']]) != ''){
                $camposFexavaAvaluo['FEXAVA_ALINEAMIENTONUMOFI']['NUMFOLIO'] = $arrAlineamiento[$arrIdsAlineamiento['d.4.1.4.2']];
            }
            

        }        

        /**********************************************Superficie del terreno (Privativas)*****************************************/

        $arrPrincipalSuperficie = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//SuperficieDelTerreno[@id="d.5"]');
        $arrIdsSuperficieDelTerreno = array();
        $arrSuperficieDelTerreno = array();
        foreach($arrPrincipalSuperficie[0] as $llave => $elemento){
            $arrIdsSuperficieDelTerreno[(String)($elemento['id'])] = $llave;
            $SuperficieDelTerreno[$llave] = $elemento;
        }

        $SuperficieDelTerreno = convierte_a_arreglo($SuperficieDelTerreno);

        if(isset($SuperficieDelTerreno[0])){
            $control = 0;
            $camposFexavaAvaluo['FEXAVA_SUPERFICIE'] = array();
            foreach($SuperficieDelTerreno as $arrSuperficieDelTerreno){ 
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.1'])){ 
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control] = array();
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['IDENTIFICADORFRACCION'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.1']][0];
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['CODTIPO'] = "P";
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.2'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['SUPERFICIEFRACCION'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.2']][0];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.3'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FZO'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.3']]['Valor'];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.4'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FUB'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.4']]['Valor'];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.5'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FFR'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.5']]['Valor'];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.6'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FFO'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.6']]['Valor'];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.7'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FSU'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.7']]['Valor'];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.11'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['VALCATASTRALTIERRA'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.11']][0];
                }
                $control = $control + 1;
            }
        }else{

            if(isset($arrIdsSuperficieDelTerreno['d.5.n.1']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.1']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE'] = array();
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['IDENTIFICADORFRACCION'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.1']][0];
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['CODTIPO'] = "P";
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.2'])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['SUPERFICIEFRACCION'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.2']][0];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.3']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.3']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FZO'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.3']]['Valor'];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.4']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.4']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FUB'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.4']]['Valor'];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.5']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.5']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FFR'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.5']]['Valor'];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.6']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.6']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FFO'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.6']]['Valor'];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.7']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.7']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FSU'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.7']]['Valor'];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.11']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.11']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['VALCATASTRALTIERRA'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.11']][0];
            }

        }    

        /**************************************************************************************************************************/

        $arrPrincipalIndiviso = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//Indiviso[@id="d.6"]');        
        if(trim((String)($arrPrincipalIndiviso[0])) != ''){
            $camposFexavaAvaluo['TEINDIVISO'] = (String)($arrPrincipalIndiviso[0]);
        }

        $arrPrincipalTopografiaYConfiguracion = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//TopografiaYConfiguracion[@id="d.7"]');
        if(trim((String)($arrPrincipalTopografiaYConfiguracion[0])) != ''){
            $camposFexavaAvaluo['CUCODTOPOGRAFIA'] = (String)($arrPrincipalTopografiaYConfiguracion[0]);
        }

        $arrPrincipalCaracteristicasPanoramicas = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//CaracteristicasPanoramicas[@id="d.8"]');
        if(trim((String)($arrPrincipalCaracteristicasPanoramicas[0])) != ''){
            $camposFexavaAvaluo['TECARACTERISTICASPARONAMICAS'] = (String)($arrPrincipalCaracteristicasPanoramicas[0]);
        }

        $arrPrincipalDensidadHabitacional = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//DensidadHabitacional[@id="d.9"]');
        if(trim((String)($arrPrincipalDensidadHabitacional[0])) != ''){
            $camposFexavaAvaluo['TECODDENSIDADHABITACIONAL'] = (String)($arrPrincipalDensidadHabitacional[0]);
        }

        $arrPrincipalServidumbresORestricciones  = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//ServidumbresORestricciones[@id="d.10"]');
        if(trim((String)($arrPrincipalServidumbresORestricciones[0])) != ''){
            $camposFexavaAvaluo['TESERVIDUMBRESORESTRICCIONES'] = (String)($arrPrincipalServidumbresORestricciones[0]);            
        }       

        return $camposFexavaAvaluo;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '26. Error en el servidor'], 500);
        }
    }

    public function guardarAvaluoTerrenoV($infoXmlTerreno, $camposFexavaAvaluo,$elementoPrincipal,$idDocumentoDigital){
        try{
        $datah = $infoXmlTerreno->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]'); 
        if($elementoPrincipal == '//Comercial'){
            if(isset($datah)){
                $errores = valida_AvaluoTerrenoV($infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]'), $elementoPrincipal, $datah);                
            }else{
                $errores = valida_AvaluoTerrenoV($infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]'), $elementoPrincipal);                
            }
            
        }else{
            $errores = valida_AvaluoTerrenoV($infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]'), $elementoPrincipal);            
        }
            
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;            
        } 
        $infoXmlCallesTransversalesLimitrofesYOrientacion = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//CallesTransversalesLimitrofesYOrientacion[@id="d.1"]');        
        $query = (String)($infoXmlCallesTransversalesLimitrofesYOrientacion[0]);

        $infoXmlCroquisMicroLocalizacion = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//CroquisMicroLocalizacion[@id="d.2"]');    
        $queryMicro = (String)($infoXmlCroquisMicroLocalizacion[0]);
        
        $infoXmlCroquisMacroLocalizacion = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//CroquisMacroLocalizacion[@id="d.3"]');        
        $queryMacro = (String)($infoXmlCroquisMacroLocalizacion[0]);

        //AQUI FALTA GUARDAR LAS FOTOS Y CAMBIAR LO QUE TRAIA DE INFORMACION EN EL XML POR LOS ID OBTENIDOS Tran_InsertFichero
        $listaIdFicheros = array();
        $idFichero = 0;

        /*$cuentaCatastral = $camposFexavaAvaluo['REGION'].'-'.
                           $camposFexavaAvaluo['MANZANA'].'-'.
                           $camposFexavaAvaluo['LOTE'].'-'.
                           $camposFexavaAvaluo['UNIDADPRIVATIVA'];
        $tipoDocumentoDigital = 13;*/
        $idUsuario = $camposFexavaAvaluo['IDPERSONAPERITO'];
        /*$idDocumentoDigital = $this->modelDocumentos->insertDocumentoDigital($cuentaCatastral, $tipoDocumentoDigital, $idUsuario);
        $camposFexavaAvaluo['IDAVALUO'] = $idDocumentoDigital;*/
        $fotoMicro = $queryMicro;
        $idFichero = $this->modelDocumentos->tran_InsertFichero($idDocumentoDigital, 'CroquisMicroLocalizacion', 'CroquisMicroLocalizacion', $fotoMicro);
        $listaIdFicheros[] = $idFichero;
        if($elementoPrincipal == "//Comercial"){
            $this->fileXML->Comercial->Terreno->CroquisMicroLocalizacion = $idFichero;
        }else{
            $this->fileXML->Catastral->Terreno->CroquisMicroLocalizacion = $idFichero;
        }
        
        $fotoMacro = $queryMacro;
        $idFichero = $this->modelDocumentos->tran_InsertFichero($idDocumentoDigital, 'CroquisMacroLocalizacion', 'CroquisMacroLocalizacion', $fotoMacro);
        $listaIdFicheros[] = $idFichero;
        if($elementoPrincipal == "//Comercial"){            
            $this->fileXML->Comercial->Terreno->CroquisMacroLocalizacion = $idFichero;
        }else{
            $this->fileXML->Catastral->Terreno->CroquisMacroLocalizacion = $idFichero;
        }
        
        //xpath($elementoPrincipal.'//Terreno[@id="d"]//CroquisMicroLocalizacion[@id="d.2"]');        
        $infoXmlEscritura = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]//Escritura[@id="d.4.1.1"]');
        $arrEscritura = array();
        $controlElemntos = 0;
        foreach($infoXmlEscritura[0] as $llave => $elemento){
            $arrEscritura[$llave] =(String)($elemento);
            if(trim($arrEscritura[$llave]) != ''){
                $controlElemntos = $controlElemntos+1;
            }
        } //print_r($infoXmlEscritura); echo "SOY controlElemntos ".$controlElemntos; exit();
        if($controlElemntos > 0){
            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG'] = array();
            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['CODTIPOFUENTEINFORMACION'] = '1';

            if(trim($arrEscritura['FechaEscritura']) != ''){
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['FECHA'] = $arrEscritura['FechaEscritura'];
            }

            $camposFexavaAvaluo['FEXAVA_ESCRITURA'] = array();
            if(trim($arrEscritura['NumeroDeEscritura']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['NUMESCRITURA'] =  $arrEscritura['NumeroDeEscritura'];
            }

            if(trim($arrEscritura['NumeroDeVolumen']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['NUMVOLUMEN'] =  $arrEscritura['NumeroDeVolumen'];
            }

            if(trim($arrEscritura['NumeroNotaria']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['NUMNOTARIO'] =  $arrEscritura['NumeroNotaria'];
            }

            if(trim($arrEscritura['NombreDelNotario']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['NOMBRENOTARIO'] =  $arrEscritura['NombreDelNotario'];
            }

            if(trim($arrEscritura['DistritoJudicialNotario']) != ''){
                $camposFexavaAvaluo['FEXAVA_ESCRITURA']['DISTRITOJUDICIALNOTARIO'] =  $arrEscritura['DistritoJudicialNotario'];
            }
        }
        /***********************************************************Sentencia**********************************************************************/
        
        $arrPrincipalFuente = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]');
        $arrIdsFuenteDeInformacionLegal = array();
        $arrFuenteDeInformacionLegal = array();
        foreach($arrPrincipalFuente[0] as $llave => $elemento){
            $arrIdsFuenteDeInformacionLegal[(String)($elemento['id'])] = $llave;
            $arrFuenteDeInformacionLegal[$llave] = $elemento;
        }
        
        if(isset($arrIdsFuenteDeInformacionLegal['d.4.1.2'])){
            $etiqueta = $arrIdsFuenteDeInformacionLegal['d.4.1.2'];
            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG'] = array();
            $infoXmlSentencia = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]//'.$etiqueta.'[@id="d.4.1.2"]');
            $arrIdsSentencia = array();
            $arrSentencia = array();            
            foreach($infoXmlSentencia[0] as $llave => $elemento){
                $arrIdsSentencia[(String)($elemento['id'])] = $llave;
                $arrSentencia[$llave] =(String)($elemento);                
            }

            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['CODTIPOFUENTEINFORMACION'] = '2';            

            if(isset($arrIdsSentencia['d.4.1.2.2']) and trim($arrSentencia[$arrIdsSentencia['d.4.1.2.2']]) != ''){
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['FECHA'] = $arrSentencia[$arrIdsSentencia['d.4.1.2.2']];
            }

            $camposFexavaAvaluo['FEXAVA_SENTENCIA'] = array();

            if(isset($arrIdsSentencia['d.4.1.2.1']) and trim($arrSentencia[$arrIdsSentencia['d.4.1.2.1']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SENTENCIA']['JUZGADO'] = $arrSentencia[$arrIdsSentencia['d.4.1.2.1']];
            }

            if(isset($arrIdsSentencia['d.4.1.2.3']) and trim($arrSentencia[$arrIdsSentencia['d.4.1.2.3']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SENTENCIA']['NUMEXPEDIENTE'] = $arrSentencia[$arrIdsSentencia['d.4.1.2.3']];
            }
        }

            /***********************************************************Contrato Privado**********************************************************************/

            if(isset($arrIdsFuenteDeInformacionLegal['d.4.1.3'])){
                $etiqueta = $arrIdsFuenteDeInformacionLegal['d.4.1.3'];
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG'] = array();
                $infoXmlContratoPrivado = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]//'.$etiqueta.'[@id="d.4.1.3"]');
                $arrIdsContratoPrivado = array();
                $arrContratoPrivado = array();            
                foreach($infoXmlContratoPrivado[0] as $llave => $elemento){
                    $arrIdsContratoPrivado[(String)($elemento['id'])] = $llave;
                    $arrContratoPrivado[$llave] =(String)($elemento);                
                }
    
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['CODTIPOFUENTEINFORMACION'] = '3';                
    
                if(isset($arrIdsContratoPrivado['d.4.1.3.1']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.1']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['FECHA'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.1']];
                }
    
                $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO'] = array();
    
                if(isset($arrIdsContratoPrivado['d.4.1.3.2']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.2']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['NOMBREADQUIRIENTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.2']];
                }
    
                if(isset($arrIdsContratoPrivado['d.4.1.2.3']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.2.3']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['APELLIDOPATERNOADQUIRIENTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.2.3']];
                }

                if(isset($arrIdsContratoPrivado['d.4.1.3.4']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.4']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['APELLIDOMATERNOADQUIRIENTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.4']];
                }

                if(isset($arrIdsContratoPrivado['d.4.1.3.5']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.5']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['NOMBREENAJENANTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.5']];
                }

                if(isset($arrIdsContratoPrivado['d.4.1.3.6']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.6']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['APELLIDOPATERNOENAJENANTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.6']];
                }

                if(isset($arrIdsContratoPrivado['d.4.1.3.7']) and trim($arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.7']]) != ''){
                    $camposFexavaAvaluo['FEXAVA_CONTRATOPRIVADO']['APELLIDOMATERNOENAJENANTE'] = $arrContratoPrivado[$arrIdsContratoPrivado['d.4.1.3.7']];
                }

        }

        /***********************************************************Alineamiento y numero oficial**********************************************************************/

        if(isset($arrIdsFuenteDeInformacionLegal['d.4.1.4'])){
            $etiqueta = $arrIdsFuenteDeInformacionLegal['d.4.1.4'];
            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG'] = array();
            $infoXmlAlineamiento = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//MedidasYColindancias[@id="d.4"]//FuenteDeInformacionLegal[@id="d.4.1"]//'.$etiqueta.'[@id="d.4.1.4"]');
            $arrIdsAlineamiento = array();
            $arrAlineamiento = array();            
            foreach($infoXmlAlineamiento[0] as $llave => $elemento){
                $arrIdsAlineamiento[(String)($elemento['id'])] = $llave;
                $arrAlineamiento[$llave] =(String)($elemento);                
            }

            $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['CODTIPOFUENTEINFORMACION'] = '4';                

            if(isset($arrIdsAlineamiento['d.4.1.4.1']) and trim($arrAlineamiento[$arrIdsAlineamiento['d.4.1.4.1']]) != ''){
                $camposFexavaAvaluo['FEXAVA_FUENTEINFORMACIONLEG']['FECHA'] = $arrAlineamiento[$arrIdsAlineamiento['d.4.1.4.1']];
            }

            $camposFexavaAvaluo['FEXAVA_ALINEAMIENTONUMOFI'] = array();
            if(isset($arrIdsAlineamiento['d.4.1.4.2']) and trim($arrAlineamiento[$arrIdsAlineamiento['d.4.1.4.2']]) != ''){
                $camposFexavaAvaluo['FEXAVA_ALINEAMIENTONUMOFI']['NUMFOLIO'] = $arrAlineamiento[$arrIdsAlineamiento['d.4.1.4.2']];
            }
            

        }        

        /**********************************************Superficie del terreno (Privativas)*****************************************/

        $arrPrincipalSuperficie = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//SuperficieDelTerreno[@id="d.5"]');
        $arrIdsSuperficieDelTerreno = array();
        $arrSuperficieDelTerreno = array();
        
        if(isset($arrPrincipalSuperficie) && count($arrPrincipalSuperficie) > 1){ 
            $control = 0;
            foreach($arrPrincipalSuperficie as $llaveP => $elementoP){
                foreach($elementoP as $llave => $elemento){
                    $arrIdsSuperficieDelTerreno[(String)($elemento['id'])] = $llave;
                    $SuperficieDelTerreno[$control][$llave] = $elemento;    
                }
                $control = $control + 1;
            }
        }else{
            foreach($arrPrincipalSuperficie[0] as $llave => $elemento){
                $arrIdsSuperficieDelTerreno[(String)($elemento['id'])] = $llave;
                $SuperficieDelTerreno[$llave] = $elemento;
            }
        }    

        $SuperficieDelTerreno = convierte_a_arreglo($SuperficieDelTerreno);
        
        if(isset($SuperficieDelTerreno[0])){
            $control = 0;
            $camposFexavaAvaluo['FEXAVA_SUPERFICIE'] = array();
            foreach($SuperficieDelTerreno as $arrSuperficieDelTerreno){   
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.1'])){ 
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control] = array();
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['IDENTIFICADORFRACCION'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.1']][0];
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['CODTIPO'] = "P";
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.2'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['SUPERFICIEFRACCION'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.2']][0];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.3'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FZO'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.3']][0];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.4'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FUB'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.4']][0];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.5'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FFR'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.5']][0];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.6'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FFO'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.6']][0];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.7'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FSU'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.7']][0];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.9']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.9']])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FOTVALOR'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.9']]['Valor'];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.9']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.9']])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['FOTDESCRIPCION'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.9']]['Descripcion'];
                }
                if(isset($arrIdsSuperficieDelTerreno['d.5.n.11'])){
                    $camposFexavaAvaluo['FEXAVA_SUPERFICIE'][$control]['VALCATASTRALTIERRA'] = $arrSuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.11']][0];
                }
                $control = $control + 1;
            } //print_r($camposFexavaAvaluo['FEXAVA_SUPERFICIE']); exit();
        }else{ //echo print_r($arrIdsSuperficieDelTerreno); exit();
            //print_r($SuperficieDelTerreno); exit();
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.1']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.1']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE'] = array();
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['IDENTIFICADORFRACCION'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.1']][0];
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['CODTIPO'] = "P";
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.2'])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['SUPERFICIEFRACCION'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.2']][0];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.3']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.3']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FZO'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.3']][0];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.4']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.4']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FUB'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.4']][0];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.5']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.5']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FFR'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.5']][0];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.6']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.6']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FFO'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.6']][0];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.7']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.7']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FSU'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.7']][0];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.9']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.9']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FOTVALOR'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.9']]['Valor'];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.9']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.9']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FOTDESCRIPCION'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.9']]['Descripcion'];
            }
            if(isset($arrIdsSuperficieDelTerreno['d.5.n.11']) && isset($SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.11']])){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['VALCATASTRALTIERRA'] = $SuperficieDelTerreno[$arrIdsSuperficieDelTerreno['d.5.n.11']][0];
            }    
            

        }
        //print_r($camposFexavaAvaluo['FEXAVA_SUPERFICIE']); exit();
        /**********************************************Superficie del terreno (Comunes)*****************************************/
            
        /*if(isset($arrIdsSuperficieDelTerreno['d.5.2'])){
            $etiqueta = $arrIdsFuenteDeInformacionLegal['d.5.2'];
            $camposFexavaAvaluo['FEXAVA_SUPERFICIE'] = array();
            $infoXmlSuperficieDelTerrenoComun = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//SuperficieDelTerreno[@id="d.5"]//'.$etiqueta.'[@id="d.5.2"]');
            $arrIdsSuperficieDelTerrenoComun = array();
            $arrSuperficieDelTerrenoComun = array();
            foreach($infoXmlSuperficieDelTerrenoComun[0] as $llave => $elemento){
                $arrIdsSuperficieDelTerrenoComun[(String)($elemento['id'])] = $llave;
                $arrSuperficieDelTerrenoComun[$llave] =(String)($elemento);
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.1']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.1']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['IDENTIFICADORFRACCION'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.1']];
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.2']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.2']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['SUPERFICIEFRACCION'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.2']];
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.3']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.3']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FZO'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.3']];
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.4']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.4']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FUB'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.4']];
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.5']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.5']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FFR'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.5']];
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.6']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.6']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FFO'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.6']];
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.7']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.7']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FSU'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.7']];
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.9.1']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.9.1']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FOTVALOR'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.9.1']];
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.9.2']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.9.2']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['FOTDESCRIPCION'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.9.2']];
            }

            if(isset($arrIdsSuperficieDelTerrenoComun['d.5.2.n.12']) and trim($arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.12']]) != ''){
                $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['VALCATASTRALTIERRA'] = $arrSuperficieDelTerrenoComun[$arrIdsSuperficieDelTerrenoComun['d.5.2.n.12']];
            }
            $camposFexavaAvaluo['FEXAVA_SUPERFICIE']['CODTIPO'] = "C";
        }*/

        /**************************************************************************************************************************/

        $arrPrincipalIndiviso = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//Indiviso[@id="d.6"]');        
        if(trim((String)($arrPrincipalIndiviso[0])) != ''){
            $camposFexavaAvaluo['TEINDIVISO'] = (String)($arrPrincipalIndiviso[0]);
        }

        $arrPrincipalTopografiaYConfiguracion = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//TopografiaYConfiguracion[@id="d.7"]');
        if(trim((String)($arrPrincipalTopografiaYConfiguracion[0])) != ''){
            $camposFexavaAvaluo['CUCODTOPOGRAFIA'] = (String)($arrPrincipalTopografiaYConfiguracion[0]);
        }

        $arrPrincipalCaracteristicasPanoramicas = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//CaracteristicasPanoramicas[@id="d.8"]');
        if(trim((String)($arrPrincipalCaracteristicasPanoramicas[0])) != ''){
            $camposFexavaAvaluo['TECARACTERISTICASPARONAMICAS'] = (String)($arrPrincipalCaracteristicasPanoramicas[0]);
        }

        $arrPrincipalDensidadHabitacional = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//DensidadHabitacional[@id="d.9"]');
        if(trim((String)($arrPrincipalDensidadHabitacional[0])) != ''){
            $camposFexavaAvaluo['TECODDENSIDADHABITACIONAL'] = (String)($arrPrincipalDensidadHabitacional[0]);
        }

        $arrPrincipalServidumbresORestricciones  = $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]//ServidumbresORestricciones[@id="d.10"]');
        if(trim((String)($arrPrincipalServidumbresORestricciones[0])) != ''){
            $camposFexavaAvaluo['TESERVIDUMBRESORESTRICCIONES'] = (String)($arrPrincipalServidumbresORestricciones[0]);            
        }       

        return $camposFexavaAvaluo;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '27. Error en el servidor'], 500);
        }
    }

    public function guardarAvaluoDescripcionImueble($infoXmlTerreno, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        $errores = valida_AvaluoDescripcionImueble($infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]'), $elementoPrincipal, $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]'), $infoXmlTerreno->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//RegimenDePropiedad[@id="b.6"]')); 
        
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;
        }
        $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];

        if($elementoPrincipal == '//Comercial'){
            $esComercial = true;
        }else{
            $esComercial = false;
        }
        $fecha = '';
        $fechastr = '';
        if(esFechaValida($fechaAvaluo) == true){
            $fecha = $fechaAvaluo;
            $fechastr = darFormatoFechaXML($fechaAvaluo);
        }

        $arrPrincipalUsoActual = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//UsoActual[@id="e.1"]');        
        if(trim((String)($arrPrincipalUsoActual[0])) != ''){
            $camposFexavaAvaluo['DIUSOACTUAL'] = (String)($arrPrincipalUsoActual[0]);
        }
                    
        
        /**********************************************Tipos de Construccion*********************************************/
        $arrPrincipalDescripcionDelInmueble = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]');        
        $arrIdsDescripcionDelInmueble = array();
        $arrDescripcionDelInmueble = array();
        foreach($arrPrincipalDescripcionDelInmueble[0] as $llave => $elemento){
            $arrIdsDescripcionDelInmueble[(String)($elemento['id'])] = $llave;
            $arrDescripcionDelInmueble[$llave] = $elemento;
        }
        

        /**********************************************Construcciones Privativas*****************************************/
        if(isset($arrIdsDescripcionDelInmueble['e.2'])){
            $usoActual = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]');
            $arrPrincipalUsoActual = $this->obtenElementosPrincipal($usoActual);                        
            $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'] = array();
            
            if(isset($arrPrincipalUsoActual['arrIds']['e.2.1'])){                
               $construccionesPrivativas = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]//'.$arrPrincipalUsoActual['arrIds']['e.2.1'].'[@id="e.2.1"]');
               $arrConstruccionesPrivativas = $this->obtenElementos($construccionesPrivativas);
              
                for($i=0;$i<count($construccionesPrivativas);$i++){
                    
                    if(!isset($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]) || trim($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]) == ''){
                        $usoNoBaldioConSuper = false;
                    }else{
                        if((String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]) == 'W'){
                            if(trim((String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.11']])) != ''){
                                $superficie = trim((String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.11']]));
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
                    }    

                    $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i] = array();

                    if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.1'])){
                        $descripcion = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.1']]);
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['DESCRIPCION'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.1']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2'])){
                        $codUso = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]);
                        if($usoNoBaldioConSuper == true){
                            $idusoEjercicio = existeCatUsoEjercicio($codUso,$fechastr);
                            if($idusoEjercicio == false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.1.n.2 - No existe un uso ejercicio para la fecha '.$fecha." y codUso ".$codUso);
                            }
                        }                        
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDUSOSEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdUsosByCodeAndAno($fechastr, $codUso);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['NUMNIVELES'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.3']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.4'])){                        
                        $codRangoNiveles = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.4']]);
                        if($usoNoBaldioConSuper == true){
                            if(existeCatRangoNivelesEjercicio($codRangoNiveles,$fechastr) == false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.1.n.4 - No existe un rango nivel ejercicio para la fecha '.$fechastr." y codRangoNiveles ".$codRangoNiveles);
                            }
                        }                        
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDRANGONIVELESEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdRangoNivelesByCodeAndAno($fechastr, $codRangoNiveles);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.5'])){                
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['PUNTAJECLASIFICACION'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.5']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.6'])){
                        $codClase = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.6']]);
                        if($usoNoBaldioConSuper == true){
                            $idclaseEjercicio = existeCatClaseEjercicio($codClase,$fechastr);
                            if($idclaseEjercicio === false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.1.n.6 - No existe una clase Ejercicio para la fecha '.$fechastr." y codClase ".$codClase);
                            }
                        }                        
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDCLASESEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdClasesByCodeAndAno($fechastr, $codClase);
                       }

                       if($usoNoBaldioConSuper == true){
                            if (existeClaseUsoEjercicio($idclaseEjercicio, $idusoEjercicio) == false) //ValidarusoClaseejercicio
                            {
                                $camposFexavaAvaluo['ERRORES'][] = array('No existe relación entre clase(e.2.1.n.6) '.$codClase." y  uso(e.2.1.n.2) ".$codUso." para la fecha ".$fechastr);
                            }
                       }                       
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.7'])){                               
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['EDAD'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.7']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.8'])){
                        $idUsoEjercicio = $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDUSOSEJERCICIO'];
                        $idClaseEjercicio = $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDCLASESEJERCICIO'];
                        $VidaUtilTotalDelTipo = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.8']]);
                        if($usoNoBaldioConSuper == true){
                            if(trim($VidaUtilTotalDelTipo) != '' && $codUso != "W" && $esComercial == false && $codClase != 'U'){
                                if (validarCatUsoClase($idUsoEjercicio, $idClaseEjercicio, $VidaUtilTotalDelTipo, $fechastr) == false){
                                    $camposFexavaAvaluo['ERRORES'][] = array("e.2.1.n.8 - La vida útil especificada no es correcta para la clase y el uso especificados: Clase ".$codClase.", Uso ".$codUso);
                                }
                            }
                        }                        
                        if($codClase != 'U'){
                            //•	En el caso de clase única (U), no se debe validar el campo e.2.1.n.8 - Vida útil total del tipo y  por tanto no existe la relación clase uso en la tabla fexava_usoClase
                            $catdt = $this->modelDatosExtrasAvaluo->ObtenerClaseUsoByIdUsoIdClase($idUsoEjercicio, $idClaseEjercicio);
                            if(count($catdt) > 0){
                                $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDUSOCLASEEJERCICIO'] = $VidaUtilTotalDelTipo;
                            }                    
                        }                               
                        
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.9'])){
                           if($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.9'] < 0){
                            $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VIDAUTILREMANENTE'] = '0';
                           }else{
                            $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VIDAUTILREMANENTE'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.9']]);
                           }                               
                        
                       }else{
                            $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VIDAUTILREMANENTE'] = '0';
                       }
        
                       /*if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.10'])){                               
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['CODESTADOCONSERVACION'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.10']]);
                       }*/
                       
                       $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['CODESTADOCONSERVACION'] = 3;
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.11'])){                               
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['SUPERFICIE'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.11']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.12'])){                               
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VALORUNITARIOREPNUEVO'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.12']]);
                       }

                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.16'])){
                        $valorUnitarioCatastral = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.16']]);
                        $codUso = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]);
                        if($usoNoBaldioConSuper == true){
                            if(trim($codUso) != '' && $codUso != 'w'){
                                $codClase = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.6']]);
                                $codRangoNiveles = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.4']]);
                                $numeroNiveles = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.3']]);
                                $periodo = 1;
    
                                $descripcion = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.1']]);                        
                                
                                $fechaCarbon = new Carbon($fechaAvaluo);
                                $anioValorUnitario = Carbon::parse($fechaCarbon)->format('Y');
                                              
                                $infoValorUnitarioConstruccion = $this->modelFis->getDataByObtenerValorUnitarioConstruccion($codUso,$codClase,$codRangoNiveles,$numeroNiveles,$anioValorUnitario,$periodo);                            
                                //echo $valorUnitarioCatastral." != ".$infoValorUnitarioConstruccion." && ".$valorUnitarioCatastral." != ".($infoValorUnitarioConstruccion + 0.01)." && ".$valorUnitarioCatastral." != ".($infoValorUnitarioConstruccion - 0.01); exit();
                                if($valorUnitarioCatastral != $infoValorUnitarioConstruccion && $valorUnitarioCatastral != ($infoValorUnitarioConstruccion + 0.01) && $valorUnitarioCatastral != ($infoValorUnitarioConstruccion - 0.01)){
                                    $camposFexavaAvaluo['ERRORES'][] = array("e.2.1.n.16 - El valor unitario de construcción no es correcto para: Uso: ".$codUso.", Rango niveles: ".$codRangoNiveles.", Clase:  ".$codClase.", descripción: ".$descripcion.". El valor ESPERADO es: ".$infoValorUnitarioConstruccion);
                                }
                                //Comentado porque no existe VALORUNITARIOCATASTRAL en la tabla FEXAVA_TIPOCONSTRUCCION $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VALORUNITARIOCATASTRAL'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.16']]);
                            }
                        }                        
                        
                       }
        
                       $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['CODTIPO'] = "P";
                }


            }

            /**********************************************Construcciones Comunes*****************************************/

            if(isset($arrPrincipalUsoActual['arrIds']['e.2.5'])){
                $construccionesComunes = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]//'.$arrPrincipalUsoActual['arrIds']['e.2.5'].'[@id="e.2.5"]');
                $arrConstruccionesComunes = $this->obtenElementos($construccionesComunes);
                if(count($camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION']) == 0){
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION']);
                }else{
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION']) - 1;
                }                       
                
                for($i=0;$i<count($construccionesComunes);$i++){

                    if(trim($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2']]) == ''){
                        $usoNoBaldioConSuper = false;
                    }else{
                        if((String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2']]) == 'W'){
                            if(trim((String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.11']])) != ''){
                                $superficie = trim((String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.11']]));
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
                    } 
                    
                    
                    $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento] = array();

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.1'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['DESCRIPCION'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.1']] );
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2'])){
                        $codUso = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2']]);
                        if($usoNoBaldioConSuper == true){
                            $idusoEjercicio = existeCatUsoEjercicio($codUso,$fechastr);
                            if($idusoEjercicio == false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.5.n.2 - No existe un uso ejercicio para la fecha '.$fecha." y codUso ".$codUso);
                            }
                        }
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDUSOSEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdUsosByCodeAndAno($fechastr, $codUso);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['NUMNIVELES'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.3']]);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.4'])){
                        $codRangoNiveles = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.4']]);
                        if($usoNoBaldioConSuper == true){ //echo existeCatRangoNivelesEjercicio($codRangoNiveles,$fechastr);
                            if(existeCatRangoNivelesEjercicio($codRangoNiveles,$fechastr) === false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.5.n.4 - No existe un rango nivel ejercicio para la fecha '.$fechastr." y codRangoNiveles ".$codRangoNiveles);
                            }
                        }
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDRANGONIVELESEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdRangoNivelesByCodeAndAno($fechastr, $codRangoNiveles);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['PUNTAJECLASIFICACION'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.5']]);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.6'])){
                        $codClase = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.6']]);
                        if($usoNoBaldioConSuper == true){
                            $idclaseEjercicio = existeCatClaseEjercicio($codClase,$fechastr);
                            if($idclaseEjercicio === false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.5.n.6 - No existe una clase Ejercicio para la fecha '.$fechastr." y codClase ".$codClase);
                            }
                        }
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDCLASESEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdClasesByCodeAndAno($fechastr, $codClase);
                    }

                    if($usoNoBaldioConSuper == true){
                        if (existeClaseUsoEjercicio($idclaseEjercicio, $idusoEjercicio) == false) //ValidarusoClaseejercicio
                        {
                            $camposFexavaAvaluo['ERRORES'][] = array('No existe relación entre clase(e.2.1.n.6) '.$codClase." y  uso(e.2.1.n.2) ".$codUso." para la fecha ".$fechastr);
                        }
                   }
                    
                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['EDAD'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.7']]);
                    }
                    
                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.8'])){                    
                        $idUsoEjercicio = $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDUSOSEJERCICIO'];
                        $idClaseEjercicio = $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDCLASESEJERCICIO'];
                        $VidaUtilTotalDelTipo = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.8']]);
                        if($usoNoBaldioConSuper == true){
                            if(trim($VidaUtilTotalDelTipo) != '' && $codUso != "W" && $esComercial == false && $codClase != 'U'){
                                if (validarCatUsoClase5($idUsoEjercicio, $idClaseEjercicio, $VidaUtilTotalDelTipo, $fechastr) === false){
                                    $camposFexavaAvaluo['ERRORES'][] = array("e.2.5.n.8 - La vida útil especificada no es correcta para la clase y el uso especificados: Clase ".$codClase.", Uso ".$codUso);
                                }
                            }
                        }
                        if($codClase != 'U'){
                            //•	En el caso de clase única (U), no se debe validar el campo e.2.1.n.8 - Vida útil total del tipo y  por tanto no existe la relación clase uso en la tabla fexava_usoClase
                            $catdt = $this->modelDatosExtrasAvaluo->ObtenerClaseUsoByIdUsoIdClase($idUsoEjercicio, $idClaseEjercicio);
                            if(count($catdt) > 0){
                                $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDUSOCLASEEJERCICIO'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.8']]);
                            }
                        }
                    }
                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.9'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['VIDAUTILREMANENTE'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.9']]);
                    }else{
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['VIDAUTILREMANENTE'] = '0';
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['CODESTADOCONSERVACION'] = 3;
                        //$camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['CODESTADOCONSERVACION'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.10']]);
                    }else{
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['CODESTADOCONSERVACION'] = 3;
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.11'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['SUPERFICIE'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.11']]);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.12'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['VALORUNITARIOREPNUEVO'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.12']]);
                    }

                    if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.5.n.16'])){
                        $valorUnitarioCatastral = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.15']]);
                        //$codUso = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2']]);
                        if($usoNoBaldioConSuper == true){
                            if(trim($codUso) != '' && $codUso != 'w'){
                                //$codClase = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.6']]);
                                //$codRangoNiveles = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.4']]);
                                $numeroNiveles = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.3']]);
                                $periodo = 1;
    
                                $descripcion = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.1']]);                        
    
                                $infoValorUnitarioConstruccion = $this->modelFis->getDataByObtenerValorUnitarioConstruccion($codUso,$codClase,$codRangoNiveles,$numeroNiveles,$periodo);                            
                                //echo $valorUnitarioCatastral." != ".$infoValorUnitarioConstruccion." && ".$valorUnitarioCatastral." != ".($infoValorUnitarioConstruccion + 0.01)." && ".$valorUnitarioCatastral." != ".($infoValorUnitarioConstruccion - 0.01); exit();
                                if($valorUnitarioCatastral != $infoValorUnitarioConstruccion && $valorUnitarioCatastral != ($infoValorUnitarioConstruccion + 0.01) && $valorUnitarioCatastral != ($infoValorUnitarioConstruccion - 0.01)){
                                    $camposFexavaAvaluo['ERRORES'][] = array("e.2.5.n.16 - El valor unitario de construcción no es correcto para: Uso: ".$codUso.", Rango niveles: ".$codRangoNiveles.", Clase:  ".$codClase.", descripción: ".$descripcion.". El valor ESPERADO es: ".$infoValorUnitarioConstruccion);
                                }
                                //Comentado porque no existe VALORUNITARIOCATASTRAL en la tabla FEXAVA_TIPOCONSTRUCCION $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VALORUNITARIOCATASTRAL'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.16']]);
                            }
                        }                        
                        
                       }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.18'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['TEINDIVISO'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.18']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['CODTIPO'] = "C";
                    $controlElemento = $controlElemento + 1;
                }                

            }

            /*********************************************************************************************************************/

            if(isset($arrPrincipalUsoActual['arrIds']['e.2.3'])){
                $valorTotalDeConstruccionesPrivativas = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//ValorTotalDeConstruccionesPrivativas[@id="e.2.3"]');
                if(trim($valorTotalDeConstruccionesPrivativas[0]) != ''){
                    $camposFexavaAvaluo['DIVALORTOTALCONSPRIVATIVAS'] = (String)($valorTotalDeConstruccionesPrivativas[0]);
                }
            }

            if(isset($arrPrincipalUsoActual['arrIds']['e.2.7'])){
                $valorTotalDeConstruccionesComunes = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//ValorTotalDeConstruccionesComunes[@id="e.2.7"]');
                if(trim($valorTotalDeConstruccionesComunes[0]) != ''){
                    $camposFexavaAvaluo['DIVALORTOTALCONSTCOMUNES'] = (String)($valorTotalDeConstruccionesComunes[0]);
                }
            }        
        }        

        if($esComercial == true){
            if(isset($arrIdsDescripcionDelInmueble['e.3'])){
                $vidaUtilTotalPonderadaDelInmueble = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//VidaMinimaRemanente[@id="e.3"]');
    
                if(trim($vidaUtilTotalPonderadaDelInmueble[0]) != ''){
                    $camposFexavaAvaluo['DIVIDAUTILPONDERADA'] = (String)($vidaUtilTotalPonderadaDelInmueble[0]);
                }
            }
        }else{
            if(isset($arrIdsDescripcionDelInmueble['e.3'])){
                $vidaUtilTotalPonderadaDelInmueble = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//VidaUtilTotalPonderadaDelInmueble[@id="e.3"]');
    
                if(trim($vidaUtilTotalPonderadaDelInmueble[0]) != ''){
                    $camposFexavaAvaluo['DIVIDAUTILPONDERADA'] = (String)($vidaUtilTotalPonderadaDelInmueble[0]);
                }
            }
        }
        
        if(isset($arrIdsDescripcionDelInmueble['e.4'])){
            $edadPonderadaDelInmueble = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//EdadPonderadaDelInmueble[@id="e.4"]');
            
            if(trim($edadPonderadaDelInmueble[0]) != ''){
                $camposFexavaAvaluo['DIEDADPONDERADA'] = (String)($edadPonderadaDelInmueble[0]);
            }
        }

        if(isset($arrIdsDescripcionDelInmueble['e.5'])){
            $vidaUtilRemanentePonderadaDelInmueble = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//VidaMinimaRemanentePonderadaDelInmueble[@id="e.5"]');
            
            if(trim($vidaUtilRemanentePonderadaDelInmueble[0]) != ''){
                $camposFexavaAvaluo['DIVIDAUTILREMANENTEPONDERADA'] = (String)($vidaUtilRemanentePonderadaDelInmueble[0]);
            }
        }

        if(isset($arrIdsDescripcionDelInmueble['e.6'])){
            $porcentSuperfUltimNivelRespectoAnterior = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//PorcentSuperfUltimNivelRespectoAnterior[@id="e.6"]');
            
            if(trim($porcentSuperfUltimNivelRespectoAnterior[0]) != ''){
                $camposFexavaAvaluo['DIPORCENTAJESUPULTNIVEL'] = (String)($porcentSuperfUltimNivelRespectoAnterior[0]);
            }
        }

        return $camposFexavaAvaluo;
        } catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '28. Error en el servidor'], 500);
        }

    }

    public function guardarAvaluoDescripcionImuebleV($infoXmlTerreno, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        $errores = valida_AvaluoDescripcionImuebleV($infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]'), $elementoPrincipal, $infoXmlTerreno->xpath($elementoPrincipal.'//Terreno[@id="d"]'), $infoXmlTerreno->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//RegimenDePropiedad[@id="b.6"]')); 
        
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;
        }
        $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];

        if($elementoPrincipal == '//Comercial'){
            $esComercial = true;
        }else{
            $esComercial = false;
        }
        $fecha = '';
        $fechastr = '';
        if(esFechaValida($fechaAvaluo) == true){
            $fecha = $fechaAvaluo;
            $fechastr = darFormatoFechaXML($fechaAvaluo);
        }

        $arrPrincipalUsoActual = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//UsoActual[@id="e.1"]');        
        if(trim((String)($arrPrincipalUsoActual[0])) != ''){
            $camposFexavaAvaluo['DIUSOACTUAL'] = (String)($arrPrincipalUsoActual[0]);
        }
                    
        
        /**********************************************Tipos de Construccion*********************************************/
        $arrPrincipalDescripcionDelInmueble = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]');        
        $arrIdsDescripcionDelInmueble = array();
        $arrDescripcionDelInmueble = array();
        foreach($arrPrincipalDescripcionDelInmueble[0] as $llave => $elemento){
            $arrIdsDescripcionDelInmueble[(String)($elemento['id'])] = $llave;
            $arrDescripcionDelInmueble[$llave] = $elemento;
        }
        

        /**********************************************Construcciones Privativas*****************************************/
        if(isset($arrIdsDescripcionDelInmueble['e.2'])){
            $usoActual = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]');
            $arrPrincipalUsoActual = $this->obtenElementosPrincipal($usoActual);                        
            $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'] = array();
            
            if(isset($arrPrincipalUsoActual['arrIds']['e.2.1'])){                
               $construccionesPrivativas = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]//'.$arrPrincipalUsoActual['arrIds']['e.2.1'].'[@id="e.2.1"]');
               $arrConstruccionesPrivativas = $this->obtenElementos($construccionesPrivativas);
              
                for($i=0;$i<count($construccionesPrivativas);$i++){

                    if(!isset($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]) || trim($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]) == ''){
                        $usoNoBaldioConSuper = false;
                    }else{
                        if((String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]) == 'W'){
                            if(trim((String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.11']])) != ''){
                                $superficie = trim((String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.11']]));
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
                    }                    

                    $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i] = array();

                    if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.1'])){
                        $descripcion = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.1']]);
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['DESCRIPCION'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.1']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2'])){
                        $codUso = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]);
                        if($usoNoBaldioConSuper == true){
                            $idusoEjercicio = existeCatUsoEjercicio($codUso,$fechastr);
                            if($idusoEjercicio == false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.1.n.2 - No existe un uso ejercicio para la fecha '.$fecha." y codUso ".$codUso);
                            }
                        }                        
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDUSOSEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdUsosByCodeAndAno($fechastr, $codUso);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['NUMNIVELES'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.3']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.4'])){                        
                        $codRangoNiveles = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.4']]);
                        if($usoNoBaldioConSuper == true){
                            if(existeCatRangoNivelesEjercicio($codRangoNiveles,$fechastr) == false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.1.n.4 - No existe un rango nivel ejercicio para la fecha '.$fechastr." y codRangoNiveles ".$codRangoNiveles);
                            }
                        }                        
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDRANGONIVELESEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdRangoNivelesByCodeAndAno($fechastr, $codRangoNiveles);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.5'])){                
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['PUNTAJECLASIFICACION'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.5']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.6'])){
                        $codClase = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.6']]);
                        if($usoNoBaldioConSuper == true){
                            $idclaseEjercicio = existeCatClaseEjercicio($codClase,$fechastr);
                            if($idclaseEjercicio === false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.1.n.6 - No existe una clase Ejercicio para la fecha '.$fechastr." y codClase ".$codClase);
                            }
                        }                        
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDCLASESEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdClasesByCodeAndAno($fechastr, $codClase);
                       }

                       if($usoNoBaldioConSuper == true){
                            if (existeClaseUsoEjercicio($idclaseEjercicio, $idusoEjercicio) == false) //ValidarusoClaseejercicio
                            {
                                $camposFexavaAvaluo['ERRORES'][] = array('No existe relación entre clase(e.2.1.n.6) '.$codClase." y  uso(e.2.1.n.2) ".$codUso." para la fecha ".$fechastr);
                            }
                       }                       
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.7'])){                               
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['EDAD'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.7']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.8'])){
                        $idUsoEjercicio = $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDUSOSEJERCICIO'];
                        $idClaseEjercicio = $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDCLASESEJERCICIO'];
                        $VidaUtilTotalDelTipo = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.8']]);
                        if($usoNoBaldioConSuper == true){
                            if(trim($VidaUtilTotalDelTipo) != '' && $codUso != "W" && $esComercial == false && $codClase != 'U'){
                                if (validarCatUsoClase($idUsoEjercicio, $idClaseEjercicio, $VidaUtilTotalDelTipo, $fechastr) == false){
                                    $camposFexavaAvaluo['ERRORES'][] = array("e.2.1.n.8 - La vida útil especificada no es correcta para la clase y el uso especificados: Clase ".$codClase.", Uso ".$codUso);
                                }
                            }
                        }                        
                        if($codClase != 'U'){
                            //•	En el caso de clase única (U), no se debe validar el campo e.2.1.n.8 - Vida útil total del tipo y  por tanto no existe la relación clase uso en la tabla fexava_usoClase
                            $catdt = $this->modelDatosExtrasAvaluo->ObtenerClaseUsoByIdUsoIdClase($idUsoEjercicio, $idClaseEjercicio);
                            if(count($catdt) > 0){
                                $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['IDUSOCLASEEJERCICIO'] = $VidaUtilTotalDelTipo;
                            }                    
                        }                               
                        
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.9'])){
                           if($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.9'] < 0){
                            $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VIDAUTILREMANENTE'] = '0';
                           }else{
                            $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VIDAUTILREMANENTE'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.9']]);
                           }                               
                        
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.10'])){                               
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['CODESTADOCONSERVACION'] = 3;
                        //$camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['CODESTADOCONSERVACION'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.10']]);
                       }else{
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['CODESTADOCONSERVACION'] = 3;
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.11'])){                               
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['SUPERFICIE'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.11']]);
                       }
        
                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.12'])){                               
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VALORUNITARIOREPNUEVO'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.12']]);
                       }

                       if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.16'])){
                        $valorUnitarioCatastral = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.16']]);
                        $codUso = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.2']]);
                        if($usoNoBaldioConSuper == true){
                            if(trim($codUso) != '' && $codUso != 'w'){
                                $codClase = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.6']]);
                                $codRangoNiveles = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.4']]);
                                $numeroNiveles = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.3']]);
                                $periodo = 1;
    
                                $descripcion = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.1']]);                        
                                
                                $fechaCarbon = new Carbon($fechaAvaluo);
                                $anioValorUnitario = Carbon::parse($fechaCarbon)->format('Y');
                                                                
                                $infoValorUnitarioConstruccion = $this->modelFis->getDataByObtenerValorUnitarioConstruccion($codUso,$codClase,$codRangoNiveles,$numeroNiveles,$anioValorUnitario,$periodo);                            
                                //echo $valorUnitarioCatastral." != ".$infoValorUnitarioConstruccion." && ".$valorUnitarioCatastral." != ".($infoValorUnitarioConstruccion + 0.01)." && ".$valorUnitarioCatastral." != ".($infoValorUnitarioConstruccion - 0.01); exit();
                                if($valorUnitarioCatastral != $infoValorUnitarioConstruccion && $valorUnitarioCatastral != ($infoValorUnitarioConstruccion + 0.01) && $valorUnitarioCatastral != ($infoValorUnitarioConstruccion - 0.01)){
                                    $camposFexavaAvaluo['ERRORES'][] = array("e.2.1.n.16 - El valor unitario de construcción no es correcto para: Uso: ".$codUso.", Rango niveles: ".$codRangoNiveles.", Clase:  ".$codClase.", descripción: ".$descripcion.". El valor ESPERADO es: ".$infoValorUnitarioConstruccion);
                                }
                                //Comentado porque no existe VALORUNITARIOCATASTRAL en la tabla FEXAVA_TIPOCONSTRUCCION $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VALORUNITARIOCATASTRAL'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.16']]);
                            }
                        }                        
                        
                       }
        
                       $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['CODTIPO'] = "P";
                }


            }

            /**********************************************Construcciones Comunes*****************************************/

            if(isset($arrPrincipalUsoActual['arrIds']['e.2.5'])){
                $construccionesComunes = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]//'.$arrPrincipalUsoActual['arrIds']['e.2.5'].'[@id="e.2.5"]');
                $arrConstruccionesComunes = $this->obtenElementos($construccionesComunes);
                if(count($camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION']) == 0){
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION']);
                }else{
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION']) - 1;
                }                       
                
                for($i=0;$i<count($construccionesComunes);$i++){
                    if(trim($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2']]) == ''){
                        $usoNoBaldioConSuper = false;
                    }else{
                        if((String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2']]) == 'W'){
                            if(trim((String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.11']])) != ''){
                                $superficie = trim((String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.11']]));
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
                    }                    
                    
                    $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento] = array();

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.1'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['DESCRIPCION'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.1']] );
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2'])){
                        $codUso = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2']]);
                        if($usoNoBaldioConSuper == true){
                            $idusoEjercicio = existeCatUsoEjercicio($codUso,$fechastr);
                            if($idusoEjercicio == false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.5.n.2 - No existe un uso ejercicio para la fecha '.$fecha." y codUso ".$codUso);
                            }
                        }
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDUSOSEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdUsosByCodeAndAno($fechastr, $codUso);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['NUMNIVELES'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.3']]);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.4'])){
                        $codRangoNiveles = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.4']]);
                        if($usoNoBaldioConSuper == true){ //echo existeCatRangoNivelesEjercicio($codRangoNiveles,$fechastr);
                            if(existeCatRangoNivelesEjercicio($codRangoNiveles,$fechastr) === false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.5.n.4 - No existe un rango nivel ejercicio para la fecha '.$fechastr." y codRangoNiveles ".$codRangoNiveles);
                            }
                        }
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDRANGONIVELESEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdRangoNivelesByCodeAndAno($fechastr, $codRangoNiveles);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['PUNTAJECLASIFICACION'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.5']]);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.6'])){
                        $codClase = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.6']]);
                        if($usoNoBaldioConSuper == true){
                            $idclaseEjercicio = existeCatClaseEjercicio($codClase,$fechastr);
                            if($idclaseEjercicio === false){
                                $camposFexavaAvaluo['ERRORES'][] = array('e.2.5.n.6 - No existe una clase Ejercicio para la fecha '.$fechastr." y codClase ".$codClase);
                            }
                        }
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDCLASESEJERCICIO'] = $this->modelDatosExtrasAvaluo->SolicitarObtenerIdClasesByCodeAndAno($fechastr, $codClase);
                    }

                    if($usoNoBaldioConSuper == true){
                        if (existeClaseUsoEjercicio($idclaseEjercicio, $idusoEjercicio) == false) //ValidarusoClaseejercicio
                        {
                            $camposFexavaAvaluo['ERRORES'][] = array('No existe relación entre clase(e.2.1.n.6) '.$codClase." y  uso(e.2.1.n.2) ".$codUso." para la fecha ".$fechastr);
                        }
                   }
                    
                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['EDAD'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.7']]);
                    }
                    
                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.8'])){                    
                        $idUsoEjercicio = $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDUSOSEJERCICIO'];
                        $idClaseEjercicio = $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDCLASESEJERCICIO'];
                        $VidaUtilTotalDelTipo = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.8']]);
                        if($usoNoBaldioConSuper == true){
                            if(trim($VidaUtilTotalDelTipo) != '' && $codUso != "W" && $esComercial == false && $codClase != 'U'){
                                if (validarCatUsoClase5($idUsoEjercicio, $idClaseEjercicio, $VidaUtilTotalDelTipo, $fechastr) === false){
                                    $camposFexavaAvaluo['ERRORES'][] = array("e.2.5.n.8 - La vida útil especificada no es correcta para la clase y el uso especificados: Clase ".$codClase.", Uso ".$codUso);
                                }
                            }
                        }
                        if($codClase != 'U'){
                            //•	En el caso de clase única (U), no se debe validar el campo e.2.1.n.8 - Vida útil total del tipo y  por tanto no existe la relación clase uso en la tabla fexava_usoClase
                            $catdt = $this->modelDatosExtrasAvaluo->ObtenerClaseUsoByIdUsoIdClase($idUsoEjercicio, $idClaseEjercicio);
                            if(count($catdt) > 0){
                                $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['IDUSOCLASEEJERCICIO'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.8']]);
                            }
                        }
                    }
                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.9'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['VIDAUTILREMANENTE'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.9']]);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['CODESTADOCONSERVACION'] = 3;
                        //$camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['CODESTADOCONSERVACION'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.10']]);
                    }else{
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['CODESTADOCONSERVACION'] = 3;
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.11'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['SUPERFICIE'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.11']]);
                    }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.12'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['VALORUNITARIOREPNUEVO'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.12']]);
                    }

                    if(isset($arrConstruccionesPrivativas['arrIds'][$i]['e.2.5.n.16'])){
                        $valorUnitarioCatastral = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.15']]);
                        //$codUso = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.2']]);
                        if($usoNoBaldioConSuper == true){
                            if(trim($codUso) != '' && $codUso != 'w'){
                                //$codClase = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.6']]);
                                //$codRangoNiveles = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.4']]);
                                $numeroNiveles = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.3']]);
                                $periodo = 1;
    
                                $descripcion = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.1']]);                        
    
                                $infoValorUnitarioConstruccion = $this->modelFis->getDataByObtenerValorUnitarioConstruccion($codUso,$codClase,$codRangoNiveles,$numeroNiveles,$periodo);                            
                                //echo $valorUnitarioCatastral." != ".$infoValorUnitarioConstruccion." && ".$valorUnitarioCatastral." != ".($infoValorUnitarioConstruccion + 0.01)." && ".$valorUnitarioCatastral." != ".($infoValorUnitarioConstruccion - 0.01); exit();
                                if($valorUnitarioCatastral != $infoValorUnitarioConstruccion && $valorUnitarioCatastral != ($infoValorUnitarioConstruccion + 0.01) && $valorUnitarioCatastral != ($infoValorUnitarioConstruccion - 0.01)){
                                    $camposFexavaAvaluo['ERRORES'][] = array("e.2.5.n.16 - El valor unitario de construcción no es correcto para: Uso: ".$codUso.", Rango niveles: ".$codRangoNiveles.", Clase:  ".$codClase.", descripción: ".$descripcion.". El valor ESPERADO es: ".$infoValorUnitarioConstruccion);
                                }
                                //Comentado porque no existe VALORUNITARIOCATASTRAL en la tabla FEXAVA_TIPOCONSTRUCCION $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$i]['VALORUNITARIOCATASTRAL'] = (String)($arrConstruccionesPrivativas['arrElementos'][$i][$arrConstruccionesPrivativas['arrIds'][$i]['e.2.1.n.16']]);
                            }
                        }                        
                        
                       }

                    if(isset($arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.18'])){
                        $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['TEINDIVISO'] = (String)($arrConstruccionesComunes['arrElementos'][$i][$arrConstruccionesComunes['arrIds'][$i]['e.2.5.n.18']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_TIPOCONSTRUCCION'][$controlElemento]['CODTIPO'] = "C";
                    $controlElemento = $controlElemento + 1;
                }                

            }

            /*********************************************************************************************************************/

            if(isset($arrPrincipalUsoActual['arrIds']['e.2.3'])){
                $valorTotalDeConstruccionesPrivativas = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//ValorTotalDeConstruccionesPrivativas[@id="e.2.3"]');
                if(trim($valorTotalDeConstruccionesPrivativas[0]) != ''){
                    $camposFexavaAvaluo['DIVALORTOTALCONSPRIVATIVAS'] = (String)($valorTotalDeConstruccionesPrivativas[0]);
                }
            }

            if(isset($arrPrincipalUsoActual['arrIds']['e.2.7'])){
                $valorTotalDeConstruccionesComunes = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//ValorTotalDeConstruccionesComunes[@id="e.2.7"]');
                if(trim($valorTotalDeConstruccionesComunes[0]) != ''){
                    $camposFexavaAvaluo['DIVALORTOTALCONSTCOMUNES'] = (String)($valorTotalDeConstruccionesComunes[0]);
                }
            }        
        }

        if(isset($arrIdsDescripcionDelInmueble['e.3'])){
            $vidaUtilTotalPonderadaDelInmueble = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//VidaUtilTotalPonderadaDelInmueble[@id="e.3"]');

            if(trim($vidaUtilTotalPonderadaDelInmueble[0]) != ''){
                $camposFexavaAvaluo['DIVIDAUTILPONDERADA'] = (String)($vidaUtilTotalPonderadaDelInmueble[0]);
            }
        }

        if(isset($arrIdsDescripcionDelInmueble['e.4'])){
            $edadPonderadaDelInmueble = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//EdadPonderadaDelInmueble[@id="e.4"]');
            
            if(trim($edadPonderadaDelInmueble[0]) != ''){
                $camposFexavaAvaluo['DIEDADPONDERADA'] = (String)($edadPonderadaDelInmueble[0]);
            }
        }

        if(isset($arrIdsDescripcionDelInmueble['e.5'])){
            $vidaUtilRemanentePonderadaDelInmueble = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//VidaUtilRemanentePonderadaDelInmueble[@id="e.5"]');
            
            if(trim($vidaUtilRemanentePonderadaDelInmueble[0]) != ''){
                $camposFexavaAvaluo['DIVIDAUTILREMANENTEPONDERADA'] = (String)($vidaUtilRemanentePonderadaDelInmueble[0]);
            }
        }

        if(isset($arrIdsDescripcionDelInmueble['e.6'])){
            $porcentSuperfUltimNivelRespectoAnterior = $infoXmlTerreno->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//PorcentSuperfUltimNivelRespectoAnterior[@id="e.6"]');
            
            if(trim($porcentSuperfUltimNivelRespectoAnterior[0]) != ''){
                $camposFexavaAvaluo['DIPORCENTAJESUPULTNIVEL'] = (String)($porcentSuperfUltimNivelRespectoAnterior[0]);
            }
        }

        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '29. Error en el servidor'], 500);
    }

    }

    public function guardarAvaluoElementosConstruccion($infoXmlElementosConst, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST'] = array();

        $elementosConst = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]');
        
        $errores = valida_AvaluoElementosDeLaConstruccion($elementosConst, $elementoPrincipal, $infoXmlElementosConst->xpath($elementoPrincipal.'//Terreno[@id="d"]'),$infoXmlElementosConst->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//RegimenDePropiedad[@id="b.6"]'));    
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;
        }
        
        $arrPrincipalElementosConst = $this->obtenElementosPrincipal($elementosConst);       

        if(isset($arrPrincipalElementosConst['arrIds']['f.1']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.1']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA'] = array();
            $obraNegra = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.1'].'[@id="f.1"]');
            $arrObraNegra = $this->obtenElementosPrincipal($obraNegra);
            //print_r($arrObraNegra); exit();
            if(isset($arrObraNegra['arrIds']['f.1.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['CIMENTACION'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.1']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['ESTRUCTURA'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.2']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.3'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['MUROS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.3']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.4'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['ENTREPISOS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.4']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.5'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['TECHOS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.5']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.6'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['AZOTEAS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.6']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.7'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['BARDAS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.7']]);
            }
        }

        /*************************************************************Revestimientos***************************************************************/

        if(isset($arrPrincipalElementosConst['arrIds']['f.2']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.2']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO'] = array();
            $obraRevestimientos = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.2'].'[@id="f.2"]');
            $arrRevestimientos = $this->obtenElementosPrincipal($obraRevestimientos);

            if(isset($arrRevestimientos['arrIds']['f.2.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['APLANADOS'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.1']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['PLAFONES'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.2']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.3'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['LAMBRINES'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.3']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.4'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['PISOS'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.4']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.5'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['ZOCLOS'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.5']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.6'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['ESCALERAS'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.6']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.7'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['PINTURA'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.7']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.8'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['RECUBRIMIENTOSESPECIALES'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.8']]);
            }
        }

        /*************************************************************Carpinteria***************************************************************/

        if(isset($arrPrincipalElementosConst['arrIds']['f.3']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.3']]) > 0){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_CARPINTERIA'] = array();
                $carpinteria = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.3'].'[@id="f.3"]');
                $arrCarpinteria = $this->obtenElementosPrincipal($carpinteria);

            if(isset($arrCarpinteria['arrIds']['f.3.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_CARPINTERIA']['PUERTASINTERIORES'] = (String)($arrCarpinteria['arrElementos'][$arrCarpinteria['arrIds']['f.3.1']]);
            }

            if(isset($arrCarpinteria['arrIds']['f.3.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_CARPINTERIA']['GUARDAROPAS'] = (String)($arrCarpinteria['arrElementos'][$arrCarpinteria['arrIds']['f.3.2']]);
            }

            if(isset($arrCarpinteria['arrIds']['f.3.3'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_CARPINTERIA']['MUEBLESEMPOTRADOSFIJOS'] = (String)($arrCarpinteria['arrElementos'][$arrCarpinteria['arrIds']['f.3.3']]);
            }

        }

        /************************************************************Instalaciones hidráulicas y sanitrias***************************************************************/
            
        if(isset($arrPrincipalElementosConst['arrIds']['f.4']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.4']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_INSTALACIONHIDSAN'] = array();
            $hidraulicos = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.4'].'[@id="f.4"]');
            $arrHidraulicos = $this->obtenElementosPrincipal($hidraulicos);

            if(isset($arrHidraulicos['arrIds']['f.4.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_INSTALACIONHIDSAN']['MUEBLESBANO'] = (String)($arrHidraulicos['arrElementos'][$arrHidraulicos['arrIds']['f.4.1']]);
            }

            if(isset($arrHidraulicos['arrIds']['f.4.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_INSTALACIONHIDSAN']['RAMALEOSHIDRAULICOS'] = (String)($arrHidraulicos['arrElementos'][$arrHidraulicos['arrIds']['f.4.2']]);
            }

            if(isset($arrHidraulicos['arrIds']['f.4.3'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_INSTALACIONHIDSAN']['RAMALEOSSANITARIOS'] = (String)($arrHidraulicos['arrElementos'][$arrHidraulicos['arrIds']['f.4.3']]);
            }

        }

        /*********************************************Instalaciones Especiales y alumbrado**********************************************************************/
    
        if(isset($arrPrincipalElementosConst['arrIds']['f.16'])){
            $electricas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//InstalacionesElectricasYAlumbrado[@id="f.16"]');    
            $camposFexavaAvaluo['IEYALUMBRADO'] = (String)($electricas[0]);
        }

        /*********************************************Puertas y ventanería metálica**********************************************************************/

        if(isset($arrPrincipalElementosConst['arrIds']['f.5']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.5']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_PUERTASYVENTANERIA'] = array();
            $puertasYVentanas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.5'].'[@id="f.5"]');
            $arrPuertasYVentanas = $this->obtenElementosPrincipal($puertasYVentanas);

            if(isset($arrPuertasYVentanas['arrIds']['f.5.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_PUERTASYVENTANERIA']['HERRERIA'] = (String)($arrPuertasYVentanas['arrElementos'][$arrPuertasYVentanas['arrIds']['f.5.1']]);
            }

            if(isset($arrPuertasYVentanas['arrIds']['f.5.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_PUERTASYVENTANERIA']['VENTANERIA'] = (String)($arrPuertasYVentanas['arrElementos'][$arrPuertasYVentanas['arrIds']['f.5.2']]);
            }    

        }

        /**********************************************************************************************************************************************************/

        if(isset($arrPrincipalElementosConst['arrIds']['f.6'])){
            $electricas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//Vidreria[@id="f.6"]');    
            $camposFexavaAvaluo['VIDRERIA'] = (String)($electricas[0]);
        }

        if(isset($arrPrincipalElementosConst['arrIds']['f.7'])){
            $cerrajeria = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//Cerrajeria[@id="f.7"]');    
            $camposFexavaAvaluo['CERRAJERIA'] = (String)($cerrajeria[0]);
        }

        if(isset($arrPrincipalElementosConst['arrIds']['f.8'])){
            $fachadas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//Fachadas[@id="f.8"]');    
            $camposFexavaAvaluo['FACHADAS'] = (String)($fachadas[0]);
        }

        
        if(isset($arrPrincipalElementosConst['arrIds']['f.9']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.9']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'] = array();
            $elementosExtra = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.9'].'[@id="f.9"]');
            $arrPrincipalElementosExtra = $this->obtenElementosPrincipal($elementosExtra);
            //print_r($camposFexavaAvaluo); exit();
            /********************************************************Instalaciones Especiales. Privativas******************************************************************/
            if(isset($arrPrincipalElementosExtra['arrIds']['f.9.1'])){

                $instalacionesEspeciales = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//InstalacionesEspeciales[@id="f.9"]//'.$arrPrincipalElementosExtra['arrIds']['f.9.1'].'[@id="f.9.1"]');
                $arrInstalacionesEspeciales = $this->obtenElementos($instalacionesEspeciales);
                //print_r($arrInstalacionesEspeciales); exit();                           
                
                for($i=0;$i<count($instalacionesEspeciales);$i++){
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i] = array();
                    //print_r($arrInstalacionesEspeciales); exit();
                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.1'])){
                        $claveInstEsp = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['UNIDAD'] = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.3']]);
                    }

                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['CANTIDAD'] = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.4']]);
                    }

                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['EDAD'] = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.5']]);
                    }

                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['VALORUNITARIO'] = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.7']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['CODTIPO'] = "C";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                }                
            }

            /********************************************************Instalaciones Especiales. Comunes******************************************************************/

            if(isset($arrPrincipalElementosExtra['arrIds']['f.9.2'])){
                $instalacionesEspecialesComunes = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//InstalacionesEspeciales[@id="f.9"]//'.$arrPrincipalElementosExtra['arrIds']['f.9.2'].'[@id="f.9.2"]');
                $arrInstalacionesEspecialesComunes = $this->obtenElementos($instalacionesEspecialesComunes);
                //print_r($arrInstalacionesEspecialesComunes); exit();

                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                         
                
                for($i=0;$i<count($instalacionesEspecialesComunes);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();
                    //print_r($arrInstalacionesEspecialesComunes); exit();
                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.1'])){
                        $claveInstEsp = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.3']]);
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.4']]);
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.5']]);
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.7']]);
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['TEINDIVISO'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.10']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "P";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                    
                }               
            }
        }     
                           

        if(isset($arrPrincipalElementosConst['arrIds']['f.10']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.10']]) > 0){
            /********************************************************Elementos Accesorios. Privativas******************************************************************/
            if(!isset($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'] = array();
            }

            $elementosExtra = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.10'].'[@id="f.10"]');
            $arrPrincipalElementosExtra = $this->obtenElementosPrincipal($elementosExtra);
            //print_r($arrPrincipalElementosExtra); exit();
            if(isset($arrPrincipalElementosExtra['arrIds']['f.10.1'])){

                $accesoriosPrivativas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.10'].'[@id="f.10"]//'.$arrPrincipalElementosExtra['arrIds']['f.10.1'].'[@id="f.10.1"]');
                $arrAccesoriosPrivativas = $this->obtenElementos($accesoriosPrivativas);                                   
                $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                                          
                
                for($i=0;$i<count($accesoriosPrivativas);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();
                    //print_r($arrAccesoriosPrivativas); exit();
                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.1'])){
                        $claveInstEsp = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.3']]);
                    }

                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.4']]);
                    }

                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.5']]);
                    }

                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.7']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "P";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                }                
            }

            /********************************************************Elementos Accesorios. Comunes******************************************************************/

            if(isset($arrPrincipalElementosExtra['arrIds']['f.10.2'])){                
                $accesoriosComunes = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.10'].'[@id="f.10"]//'.$arrPrincipalElementosExtra['arrIds']['f.10.2'].'[@id="f.10.2"]');
                $arrAccesoriosComunes = $this->obtenElementos($accesoriosComunes);                
                $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                                      
                
                for($i=0;$i<count($accesoriosComunes);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();
                    //print_r($arrAccesoriosComunes); exit();
                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.1'])){
                        $claveInstEsp = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.3']]);
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.4']]);
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.5']]);
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.7']]);
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['TEINDIVISO'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.10']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "C";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];                    
                }               
            }
        }

        if(isset($arrPrincipalElementosConst['arrIds']['f.11']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.11']]) > 0){
            /********************************************************Obras Complementarias. Privativas******************************************************************/
            if(!isset($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'] = array();
            }
            $elementosExtra = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.11'].'[@id="f.11"]');
            $arrPrincipalElementosExtra = $this->obtenElementosPrincipal($elementosExtra);
            //print_r($arrPrincipalElementosExtra); exit();
            if(isset($arrPrincipalElementosExtra['arrIds']['f.11.1'])){

                $obrasComplementariasPrivativas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.11'].'[@id="f.11"]//'.$arrPrincipalElementosExtra['arrIds']['f.11.1'].'[@id="f.11.1"]');
                $arrObrasComplementariasPrivativas = $this->obtenElementos($obrasComplementariasPrivativas);
                                   
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                                          
                
                for($i=0;$i<count($obrasComplementariasPrivativas);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();
                    //print_r($arrObrasComplementariasPrivativas); exit();
                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.1'])){
                        $claveInstEsp = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.3']]);
                    }

                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.4']]);
                    }

                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.5']]);
                    }

                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.7']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "P";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                }                
            }

            /********************************************************Obras Complementarias. Comunes******************************************************************/

            if(isset($arrPrincipalElementosExtra['arrIds']['f.11.2'])){

                $obrasComplementariasComunes = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.11'].'[@id="f.11"]//'.$arrPrincipalElementosExtra['arrIds']['f.11.2'].'[@id="f.11.2"]');
                $arrObrasComplementariasComunes = $this->obtenElementos($obrasComplementariasComunes);
                //print_r($arrObrasComplementariasComunes); exit();                   
                $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                                          
                
                for($i=0;$i<count($obrasComplementariasComunes);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();                    
                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.1'])){
                        $claveInstEsp = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.3']]);
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.4']]);
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.5']]);
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.7']]);
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['TEINDIVISO'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.10']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "C";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                }                
            }


        }        


        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '30. Error en el servidor'], 500);
    }

    }

    public function guardarAvaluoElementosConstruccionV($infoXmlElementosConst, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST'] = array();

        $elementosConst = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]');
        
        $errores = valida_AvaluoElementosDeLaConstruccionV($elementosConst, $elementoPrincipal, $infoXmlElementosConst->xpath($elementoPrincipal.'//Terreno[@id="d"]'),$infoXmlElementosConst->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//RegimenDePropiedad[@id="b.6"]'));    
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;
        }
        
        $arrPrincipalElementosConst = $this->obtenElementosPrincipal($elementosConst);       

        if(isset($arrPrincipalElementosConst['arrIds']['f.1']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.1']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA'] = array();
            $obraNegra = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.1'].'[@id="f.1"]');
            $arrObraNegra = $this->obtenElementosPrincipal($obraNegra);
            //print_r($arrObraNegra); exit();
            if(isset($arrObraNegra['arrIds']['f.1.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['CIMENTACION'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.1']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['ESTRUCTURA'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.2']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.3'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['MUROS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.3']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.4'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['ENTREPISOS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.4']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.5'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['TECHOS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.5']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.6'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['AZOTEAS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.6']]);
            }

            if(isset($arrObraNegra['arrIds']['f.1.7'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_OBRANEGRA']['BARDAS'] = (String)($arrObraNegra['arrElementos'][$arrObraNegra['arrIds']['f.1.7']]);
            }
        }

        /*************************************************************Revestimientos***************************************************************/

        if(isset($arrPrincipalElementosConst['arrIds']['f.2']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.2']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO'] = array();
            $obraRevestimientos = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.2'].'[@id="f.2"]');
            $arrRevestimientos = $this->obtenElementosPrincipal($obraRevestimientos);

            if(isset($arrRevestimientos['arrIds']['f.2.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['APLANADOS'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.1']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['PLAFONES'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.2']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.3'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['LAMBRINES'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.3']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.4'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['PISOS'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.4']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.5'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['ZOCLOS'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.5']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.6'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['ESCALERAS'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.6']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.7'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['PINTURA'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.7']]);
            }

            if(isset($arrRevestimientos['arrIds']['f.2.8'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_REVESTIMIENTOACABADO']['RECUBRIMIENTOSESPECIALES'] = (String)($arrRevestimientos['arrElementos'][$arrRevestimientos['arrIds']['f.2.8']]);
            }
        }

        /*************************************************************Carpinteria***************************************************************/

        if(isset($arrPrincipalElementosConst['arrIds']['f.3']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.3']]) > 0){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_CARPINTERIA'] = array();
                $carpinteria = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.3'].'[@id="f.3"]');
                $arrCarpinteria = $this->obtenElementosPrincipal($carpinteria);

            if(isset($arrCarpinteria['arrIds']['f.3.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_CARPINTERIA']['PUERTASINTERIORES'] = (String)($arrCarpinteria['arrElementos'][$arrCarpinteria['arrIds']['f.3.1']]);
            }

            if(isset($arrCarpinteria['arrIds']['f.3.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_CARPINTERIA']['GUARDAROPAS'] = (String)($arrCarpinteria['arrElementos'][$arrCarpinteria['arrIds']['f.3.2']]);
            }

            if(isset($arrCarpinteria['arrIds']['f.3.3'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_CARPINTERIA']['MUEBLESEMPOTRADOSFIJOS'] = (String)($arrCarpinteria['arrElementos'][$arrCarpinteria['arrIds']['f.3.3']]);
            }

        }

        /************************************************************Instalaciones hidráulicas y sanitrias***************************************************************/
            
        if(isset($arrPrincipalElementosConst['arrIds']['f.4']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.4']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_INSTALACIONHIDSAN'] = array();
            $hidraulicos = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.4'].'[@id="f.4"]');
            $arrHidraulicos = $this->obtenElementosPrincipal($hidraulicos);

            if(isset($arrHidraulicos['arrIds']['f.4.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_INSTALACIONHIDSAN']['MUEBLESBANO'] = (String)($arrHidraulicos['arrElementos'][$arrHidraulicos['arrIds']['f.4.1']]);
            }

            if(isset($arrHidraulicos['arrIds']['f.4.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_INSTALACIONHIDSAN']['RAMALEOSHIDRAULICOS'] = (String)($arrHidraulicos['arrElementos'][$arrHidraulicos['arrIds']['f.4.2']]);
            }

            if(isset($arrHidraulicos['arrIds']['f.4.3'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_INSTALACIONHIDSAN']['RAMALEOSSANITARIOS'] = (String)($arrHidraulicos['arrElementos'][$arrHidraulicos['arrIds']['f.4.3']]);
            }

        }

        /*********************************************Instalaciones Especiales y alumbrado**********************************************************************/
    
        if(isset($arrPrincipalElementosConst['arrIds']['f.16'])){
            $electricas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//InstalacionesElectricasYAlumbrado[@id="f.16"]');    
            $camposFexavaAvaluo['IEYALUMBRADO'] = (String)($electricas[0]);
        }

        /*********************************************Puertas y ventanería metálica**********************************************************************/

        if(isset($arrPrincipalElementosConst['arrIds']['f.5']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.5']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_PUERTASYVENTANERIA'] = array();
            $puertasYVentanas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.5'].'[@id="f.5"]');
            $arrPuertasYVentanas = $this->obtenElementosPrincipal($puertasYVentanas);

            if(isset($arrPuertasYVentanas['arrIds']['f.5.1'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_PUERTASYVENTANERIA']['HERRERIA'] = (String)($arrPuertasYVentanas['arrElementos'][$arrPuertasYVentanas['arrIds']['f.5.1']]);
            }

            if(isset($arrPuertasYVentanas['arrIds']['f.5.2'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_PUERTASYVENTANERIA']['VENTANERIA'] = (String)($arrPuertasYVentanas['arrElementos'][$arrPuertasYVentanas['arrIds']['f.5.2']]);
            }    

        }

        /**********************************************************************************************************************************************************/

        if(isset($arrPrincipalElementosConst['arrIds']['f.6'])){
            $electricas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//Vidreria[@id="f.6"]');    
            $camposFexavaAvaluo['VIDRERIA'] = (String)($electricas[0]);
        }

        if(isset($arrPrincipalElementosConst['arrIds']['f.7'])){
            $cerrajeria = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//Cerrajeria[@id="f.7"]');    
            $camposFexavaAvaluo['CERRAJERIA'] = (String)($cerrajeria[0]);
        }

        if(isset($arrPrincipalElementosConst['arrIds']['f.8'])){
            $fachadas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//Fachadas[@id="f.8"]');    
            $camposFexavaAvaluo['FACHADAS'] = (String)($fachadas[0]);
        }

        
        if(isset($arrPrincipalElementosConst['arrIds']['f.9']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.9']]) > 0){
            $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'] = array();
            $elementosExtra = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.9'].'[@id="f.9"]');
            $arrPrincipalElementosExtra = $this->obtenElementosPrincipal($elementosExtra);
            //print_r($camposFexavaAvaluo); exit();
            /********************************************************Instalaciones Especiales. Privativas******************************************************************/
            if(isset($arrPrincipalElementosExtra['arrIds']['f.9.1'])){

                $instalacionesEspeciales = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//InstalacionesEspeciales[@id="f.9"]//'.$arrPrincipalElementosExtra['arrIds']['f.9.1'].'[@id="f.9.1"]');
                $arrInstalacionesEspeciales = $this->obtenElementos($instalacionesEspeciales);
                //print_r($arrInstalacionesEspeciales); exit();                           
                
                for($i=0;$i<count($instalacionesEspeciales);$i++){
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i] = array();
                    //print_r($arrInstalacionesEspeciales); exit();
                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.1'])){
                        $claveInstEsp = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['UNIDAD'] = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.3']]);
                    }

                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['CANTIDAD'] = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.4']]);
                    }

                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['EDAD'] = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.5']]);
                    }

                    if(isset($arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['VALORUNITARIO'] = (String)($arrInstalacionesEspeciales['arrElementos'][$i][$arrInstalacionesEspeciales['arrIds'][$i]['f.9.1.n.7']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['CODTIPO'] = "C";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$i]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                }                
            }

            /********************************************************Instalaciones Especiales. Comunes******************************************************************/

            if(isset($arrPrincipalElementosExtra['arrIds']['f.9.2'])){
                $instalacionesEspecialesComunes = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//InstalacionesEspeciales[@id="f.9"]//'.$arrPrincipalElementosExtra['arrIds']['f.9.2'].'[@id="f.9.2"]');
                $arrInstalacionesEspecialesComunes = $this->obtenElementos($instalacionesEspecialesComunes);
                //print_r($arrInstalacionesEspecialesComunes); exit();

                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                         
                
                for($i=0;$i<count($instalacionesEspecialesComunes);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();
                    //print_r($arrInstalacionesEspecialesComunes); exit();
                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.1'])){
                        $claveInstEsp = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.3']]);
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.4']]);
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.5']]);
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.7']]);
                    }

                    if(isset($arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['TEINDIVISO'] = (String)($arrInstalacionesEspecialesComunes['arrElementos'][$i][$arrInstalacionesEspecialesComunes['arrIds'][$i]['f.9.2.n.10']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "P";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                    
                }               
            }
        }     
                           

        if(isset($arrPrincipalElementosConst['arrIds']['f.10']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.10']]) > 0){
            /********************************************************Elementos Accesorios. Privativas******************************************************************/
            if(!isset($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'] = array();
            }

            $elementosExtra = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.10'].'[@id="f.10"]');
            $arrPrincipalElementosExtra = $this->obtenElementosPrincipal($elementosExtra);
            //print_r($arrPrincipalElementosExtra); exit();
            if(isset($arrPrincipalElementosExtra['arrIds']['f.10.1'])){

                $accesoriosPrivativas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.10'].'[@id="f.10"]//'.$arrPrincipalElementosExtra['arrIds']['f.10.1'].'[@id="f.10.1"]');
                $arrAccesoriosPrivativas = $this->obtenElementos($accesoriosPrivativas);                                   
                $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                                          
                
                for($i=0;$i<count($accesoriosPrivativas);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();
                    //print_r($arrAccesoriosPrivativas); exit();
                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.1'])){
                        $claveInstEsp = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.3']]);
                    }

                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.4']]);
                    }

                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.5']]);
                    }

                    if(isset($arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrAccesoriosPrivativas['arrElementos'][$i][$arrAccesoriosPrivativas['arrIds'][$i]['f.10.1.n.7']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "P";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                }                
            }

            /********************************************************Elementos Accesorios. Comunes******************************************************************/

            if(isset($arrPrincipalElementosExtra['arrIds']['f.10.2'])){                
                $accesoriosComunes = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.10'].'[@id="f.10"]//'.$arrPrincipalElementosExtra['arrIds']['f.10.2'].'[@id="f.10.2"]');
                $arrAccesoriosComunes = $this->obtenElementos($accesoriosComunes);                
                $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                                      
                
                for($i=0;$i<count($accesoriosComunes);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();
                    //print_r($arrAccesoriosComunes); exit();
                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.1'])){
                        $claveInstEsp = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.3']]);
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.4']]);
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.5']]);
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.7']]);
                    }

                    if(isset($arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['TEINDIVISO'] = (String)($arrAccesoriosComunes['arrElementos'][$i][$arrAccesoriosComunes['arrIds'][$i]['f.10.2.n.10']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "C";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];                    
                }               
            }
        }

        if(isset($arrPrincipalElementosConst['arrIds']['f.11']) && count($arrPrincipalElementosConst['arrElementos'][$arrPrincipalElementosConst['arrIds']['f.11']]) > 0){
            /********************************************************Obras Complementarias. Privativas******************************************************************/
            if(!isset($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'])){
                $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'] = array();
            }
            $elementosExtra = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.11'].'[@id="f.11"]');
            $arrPrincipalElementosExtra = $this->obtenElementosPrincipal($elementosExtra);
            //print_r($arrPrincipalElementosExtra); exit();
            if(isset($arrPrincipalElementosExtra['arrIds']['f.11.1'])){

                $obrasComplementariasPrivativas = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.11'].'[@id="f.11"]//'.$arrPrincipalElementosExtra['arrIds']['f.11.1'].'[@id="f.11.1"]');
                $arrObrasComplementariasPrivativas = $this->obtenElementos($obrasComplementariasPrivativas);
                                   
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                                          
                
                for($i=0;$i<count($obrasComplementariasPrivativas);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();
                    //print_r($arrObrasComplementariasPrivativas); exit();
                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.1'])){
                        $claveInstEsp = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.3']]);
                    }

                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.4']]);
                    }

                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.5']]);
                    }

                    if(isset($arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrObrasComplementariasPrivativas['arrElementos'][$i][$arrObrasComplementariasPrivativas['arrIds'][$i]['f.11.1.n.7']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "P";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                }                
            }

            /********************************************************Obras Complementarias. Comunes******************************************************************/

            if(isset($arrPrincipalElementosExtra['arrIds']['f.11.2'])){

                $obrasComplementariasComunes = $infoXmlElementosConst->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//'.$arrPrincipalElementosConst['arrIds']['f.11'].'[@id="f.11"]//'.$arrPrincipalElementosExtra['arrIds']['f.11.2'].'[@id="f.11.2"]');
                $arrObrasComplementariasComunes = $this->obtenElementos($obrasComplementariasComunes);
                //print_r($arrObrasComplementariasComunes); exit();                   
                $controlElemento = count($camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA']) - 1;                                          
                
                for($i=0;$i<count($obrasComplementariasComunes);$i++){
                    $controlElemento = $controlElemento+1;
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento] = array();                    
                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.1'])){
                        $claveInstEsp = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.1']]);
                        $codInstEsp = $this->modelElementosConstruccion->obtenerInstEspecialByClave($claveInstEsp);
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODINSTALACIONESESPECIALES'] = $codInstEsp['CODINSTESPECIALES'];
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.3'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['UNIDAD'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.3']]);
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CANTIDAD'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.4']]);
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.5'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['EDAD'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.5']]);
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['VALORUNITARIO'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.7']]);
                    }

                    if(isset($arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['TEINDIVISO'] = (String)($arrObrasComplementariasComunes ['arrElementos'][$i][$arrObrasComplementariasComunes ['arrIds'][$i]['f.11.2.n.10']]);
                    }

                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['CODTIPO'] = "C";
                    $camposFexavaAvaluo['FEXAVA_ELEMENTOSCONST']['FEXAVA_ELEMENTOSEXTRA'][$controlElemento]['IDAVALUO'] = $camposFexavaAvaluo['IDAVALUO'];
                }                
            }


        }        


        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '31. Error en el servidor'], 500);
    }

    }

    public function guardarAvaluoEnfoqueMercado($infoXmlElementosConst, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO'] = array();

        $enfoqueDeMercado = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]');

        if(count($enfoqueDeMercado) == 0){
            return $camposFexavaAvaluo; 
        }
        
        $errores = valida_AvaluoEnfoqueMercado($enfoqueDeMercado, $elementoPrincipal);    
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;
        }
        $arrPrincipalEnfoqueDeMercado = $this->obtenElementosPrincipal($enfoqueDeMercado);
        //print_r($arrPrincipalEnfoqueDeMercado); exit();
        if(isset($arrPrincipalEnfoqueDeMercado['arrIds']['h.1']) && count($arrPrincipalEnfoqueDeMercado['arrElementos'][$arrPrincipalEnfoqueDeMercado['arrIds']['h.1']]) > 0){           
            $terrenos = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]');
            $arrTerrenos = $this->obtenElementosPrincipal($terrenos);
            /********************************************************Conclusiones homologación terrenos******************************************************************/
            if(isset($arrTerrenos['arrIds']['h.1.2'])){
                $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO'] = array();
                $conclusionesHomologacionTerreno = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.2'].'[@id="h.1.2"]');
                $arrConclusionesHomologacionTerreno = $this->obtenElementosPrincipal($conclusionesHomologacionTerreno);
                //print_r($arrConclusionesHomologacionTerreno); exit();

                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.2.2'])){
                    $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIOTIERRAPROMEDIO'] = (String)($arrConclusionesHomologacionTerreno ['arrElementos'][$arrConclusionesHomologacionTerreno ['arrIds']['h.1.2.2']]);
                }

                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.2.2'])){
                    $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIOTIERRAHOMOLOGADO'] = (String)($arrConclusionesHomologacionTerreno ['arrElementos'][$arrConclusionesHomologacionTerreno ['arrIds']['h.1.2.2']]);
                }

                $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['CODTIPOTERRENO'] = 'D';
            }

            /********************************************************Terrenos Directos******************************************************************/
            //print_r($arrTerrenos['arrIds']); exit();
            if(isset($arrTerrenos['arrIds']['h.1.1'])){ 
                $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'] = array();
                $terrenosDirectos = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.1'].'[@id="h.1.1"]');
                $arrTerrenosDirectos = $this->obtenElementos($terrenosDirectos);
                //print_r($arrTerrenosDirectos); exit();
                $controlElemento = count($camposFexavaAvaluo['FEXAVA_DATOSTERRENOS']) - 1; //print_r($terrenosDirectos); exit();

                for($i=0;$i < count($terrenosDirectos); $i++){
                    $controlElemento = $controlElemento + 1;
                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.1'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CALLE'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.1']]);
                    }
    
                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.3'])){
                        $codDelegacion = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.3']]);
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['IDDELEGACION'] = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorClave($codDelegacion);
                        if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.2'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['IDCOLONIA'] = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion((String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.2']]), $codDelegacion);
                        }
    
                    }
    
                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CODIGOPOSTAL'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.4']]);
                    }
    
                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.5']) && count($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.5']]) > 0){
                        //print_r($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.5']]); exit();
                        $subarreglo = $arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.5']];
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['TELEFONO'] = (String)($subarreglo->Telefono);
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['INFORMANTE'] = (String)($subarreglo->Informante); 
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.6'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['DESCRIPCION'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.6']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['USOSUELO'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.7']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.8'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CUS'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.8']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.9'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['SUPERFICIE'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.9']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FZO'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.10']]->Valor);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.11'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FUB'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.11']]->Valor);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.12'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FFR'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.12']]->Valor);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.13'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FFO'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.13']]->Valor);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.14'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FSU'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.14']]->Valor);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.18']) && count($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.18']]) > 0){
                        $fot = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.1'].'[@id="h.1.1"]//'.$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.18'].'[@id="h.1.1.n.18"]');
                        $arrFot = $this->obtenElementosPrincipal($fot);
                        if(isset($arrTerrenosDirectos['arrIds']['h.1.1.n.18.1'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FOTVALOR'] = (String)($arrFot['arrElementos'][$arrTerrenosDirectos['arrIds']['h.1.1.n.18.1']]);
                        }
                        if(isset($arrTerrenosDirectos['arrIds']['h.1.1.n.18.2'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FOTDESCRIPCION'] = (String)($arrFot['arrElementos'][$arrTerrenosDirectos['arrIds']['h.1.1.n.18.2']]);
                        }
                        
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.15'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['PRECIOSOLICITADO'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.15']]);
                    }
                    
                }
                
            }

            if(isset($arrTerrenos['arrIds']['h.1.3'])){
                $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO'] = array();
                $conclusionesHomologacionTerreno = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]');
                $arrConclusionesHomologacionTerreno = $this->obtenElementosPrincipal($conclusionesHomologacionTerreno);
                //print_r($arrConclusionesHomologacionTerreno); exit();

                /********************************************************Conclusiones homologación comp. Residuales******************************************************************/
                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.3.5'])){
                    $conclusionesHomologacionResiduales = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]//'.$arrConclusionesHomologacionTerreno['arrIds']['h.1.3.5'].'[@id="h.1.3.5"]');
                    $arrConclusionesHomologacionResiduales = $this->obtenElementosPrincipal($conclusionesHomologacionResiduales);

                    if(isset($arrConclusionesHomologacionResiduales['arrIds']['h.1.3.5.1'])){
                        $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIOTIERRAPROMEDIO'] = (String)($arrConclusionesHomologacionResiduales['arrElementos'][$arrConclusionesHomologacionResiduales['arrIds']['h.1.3.5.1']]);
                    }

                    if(isset($arrConclusionesHomologacionResiduales['arrIds']['h.1.3.5.2'])){
                        $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIOTIERRAHOMOLOGADO'] = (String)($arrConclusionesHomologacionResiduales['arrElementos'][$arrConclusionesHomologacionResiduales['arrIds']['h.1.3.5.2']]);
                    }
                    
                }

                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.3.6'])){
                    $conclusionesHomologacionResiduales = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]//'.$arrConclusionesHomologacionTerreno['arrIds']['h.1.3.6'].'[@id="h.1.3.6"]');
                    $arrConclusionesHomologacionResiduales = $this->obtenElementosPrincipal($conclusionesHomologacionResiduales);

                    if(isset($arrConclusionesHomologacionResiduales['arrIds']['h.1.3.6.4'])){
                        $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIORESIDUAL'] = (String)($arrConclusionesHomologacionResiduales['arrElementos'][$arrConclusionesHomologacionResiduales['arrIds']['h.1.3.6.4']]);
                    }
                }

                $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['CODTIPOTERRENO'] = 'R';

                /********************************************************Investigación productos comparables******************************************************************/

                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.3.4'])){
                    $productosComparables = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]//'.$arrConclusionesHomologacionTerreno['arrIds']['h.1.3.4'].'[@id="h.1.3.4"]');
                    $arrProductosComparables = $this->obtenElementos($productosComparables);
                    //echo "arrProductosComparables "; print_r($arrProductosComparables); exit();
                    if(!isset($camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'] = array();
                    }                    
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_DATOSTERRENOS']) - 1;
                    for($i=0;$i < count($productosComparables);$i++){
                        $controlElemento = $controlElemento + 1;

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.1'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CALLE'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.1']]);
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.3'])){
                            $codDelegacion = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.3']]);
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['IDDELEGACION'] = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorClave($codDelegacion);
                            if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.2'])){
                                $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['IDCOLONIA'] = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion((String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.2']]), $codDelegacion);
                            }
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.4'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CODIGOPOSTAL'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.4']]);
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.5']) && count($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.5']]) > 0){
                            $fot = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]//InvestigacionProductosComparables[@id="h.1.3.4"]//'.$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.5'].'[@id="h.1.3.4.n.5"]');
                            $arrFot = $this->obtenElementosPrincipal($fot);
                            //if(isset($arrProductosComparables['arrIds']['h.1.3.4.n.5.1'])){ echo (String)($arrFot['arrElementos'][$arrFot['arrIds']['h.1.3.4.n.5.1']]); exit();
                                $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['TELEFONO'] = (String)($arrFot['arrElementos'][$arrFot['arrIds']['h.1.3.4.n.5.1']]);
                           // }
                            //if(isset($arrProductosComparables['arrIds']['h.1.3.4.n.5.2'])){
                                $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['INFORMANTE'] = (String)($arrFot['arrElementos'][$arrFot['arrIds']['h.1.3.4.n.5.2']]);
                            //}
                            
                        }//print_r($camposFexavaAvaluo['FEXAVA_DATOSTERRENOS']); exit();

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.6'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['DESCRIPCION'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.6']]);
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.7'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['SUPERFICIE'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.7']]);
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.8'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['PRECIOSOLICITADO'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.8']]);
                        }

                        
                    }
                }
            }

            if(isset($arrTerrenos['arrIds']['h.1.4'])){
                //echo "EL ARR_CAMPOS_FEXAVA "; print_r($camposFexavaAvaluo); exit();
                $valorUnitarioDeTierraAplicableAlAvaluo = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.4'].'[@id="h.1.4"]');

                $arrValorUnitarioDeTierraAplicableAlAvaluo = $this->obtenElementosPrincipal($valorUnitarioDeTierraAplicableAlAvaluo);

                $minMuestras = env("MINMUESTRAS");

                if($minMuestras > 0){
                    $infoDesviacion = $this->modelAva->valorUnitarioDesviacion($camposFexavaAvaluo['REGION'],$camposFexavaAvaluo['MANZANA'],$camposFexavaAvaluo['LOTE'],$camposFexavaAvaluo['UNIDADPRIVATIVA']);
                    if($infoDesviacion != 'Error al obtener la desviación estandar.'){

                        if(isset($infoDesviacion) && count($infoDesviacion) > 0){
                            if($infoDesviacion['NUMMUESTRAS'] < $minMuestras || trim($infoDesviacion['MEDIAVUS'] == ''))    {
                                $camposFexavaAvaluo['ALERTA'] = "No ha sido posible hacer la validación de valor unitario de suelo por falta de datos. ¿Desea continuar?";
                            }else{
                                if(trim($infoDesviacion['DESVIACION']) == ''){
                                    $infoDesviacion['DESVIACION'] = 0;
                                }
                                $valMinVus = $infoDesviacion['MEDIAVUS'] - $infoDesviacion['DESVIACION'];
                                $valMaxVus = $infoDesviacion['MEDIAVUS'] + $infoDesviacion['DESVIACION'];
                                if($valorUnitarioDeTierraAplicableAlAvaluo[0] < $valMinVus || $valorUnitarioDeTierraAplicableAlAvaluo[0] > $valMaxVus){
                                    $camposFexavaAvaluo['ALERTA'] = "El valor unitario de suelo del avalúo no se encuentra entre en el rango del valor mínimo y máximo de VUS en base a la media para el área de valor. ¿Desea continuar?";
                                }
                            }
                        }

                    }else{
                        //return array('ERROR' => array($infoDesviacion)); 
                        $camposFexavaAvaluo['ERRORES'][] = $errores;
                    }
                    
                }    
                
                $camposFexavaAvaluo['VALORUNITARIOTIERRAAVALUO'] = (String)($valorUnitarioDeTierraAplicableAlAvaluo[0]);
            }

        }

        /*********************************************************************** Conclusiones homologación construcciones en venta*********************************************************/

        if(isset($arrPrincipalEnfoqueDeMercado['arrIds']['h.2']) && count($arrPrincipalEnfoqueDeMercado['arrElementos'][$arrPrincipalEnfoqueDeMercado['arrIds']['h.2']]) > 0){
            $construccionesEnVenta = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]');
            $arrConstruccionesEnVenta = $this->obtenElementosPrincipal($construccionesEnVenta);
            
            if(isset($arrConstruccionesEnVenta['arrIds']['h.2.2'])){
                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'] = array();
                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0] = array();               
                $conclusionesHomologacionConstruccionesEnVenta = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]//'.$arrConstruccionesEnVenta['arrIds']['h.2.2'].'[@id="h.2.2"]');
                $arrConclusionesHomologacionConstruccionesEnVenta = $this->obtenElementosPrincipal($conclusionesHomologacionConstruccionesEnVenta);

                //print_r($arrConclusionesHomologacionConstruccionesEnVenta); exit();
                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['IDMODOCONSTRUCCION'] = 'V';
                if(isset($arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.1'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['VALORUNITARIOPROMEDIO'] = (String)($arrConclusionesHomologacionConstruccionesEnVenta['arrElementos'][$arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.1']]);
                }

                if(isset($arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.2'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['VALORUNITARIOHOMOLOGADO'] = (String)($arrConclusionesHomologacionConstruccionesEnVenta['arrElementos'][$arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.2']]);
                }

                if(isset($arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.7'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['VALORUNITARIOAPLICABLE'] = (String)($arrConclusionesHomologacionConstruccionesEnVenta['arrElementos'][$arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.7']]);
                }
                
            }

            if(isset($arrConstruccionesEnVenta['arrIds']['h.2.1'])){
                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'] = array();
                $investigacionProductosComparables = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]//'.$arrConstruccionesEnVenta['arrIds']['h.2.1'].'[@id="h.2.1"]');
                $arrInvestigacionProductosComparables = $this->obtenElementos($investigacionProductosComparables);

                $controlElemento = count($camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP']) - 1;
                $controlCuentaCatastral = 0;
                $controlFuenteInformacion = 0;
                for($i=0;$i < count($investigacionProductosComparables); $i++){
                    $controlElemento = $controlElemento + 1;

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.1'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['CODTIPOCOMPARABLE'] = 'V';
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['CALLE'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.1']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.3'])){
                        $codDelegacion = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.3']]);
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['IDDELEGACION'] = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorClave($codDelegacion);
                        if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.2'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['IDCOLONIA'] = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion((String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.2']]), $codDelegacion);                            
                        }
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['CODIGOPOSTAL'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.4']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.5']) && count($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.5']]) > 0){
                        $fuenteDeInformacion = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]//'.$arrConstruccionesEnVenta['arrIds']['h.2.1'].'[@id="h.2.1"]//'.$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.5'].'[@id="h.2.1.n.5"]');
                        $arrFuenteDeInformacion = $this->obtenElementos($fuenteDeInformacion);
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.2.1.n.5.1'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['TELEFONO'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlFuenteInformacion][$arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.2.1.n.5.1']]);
                        }
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.2.1.n.5.2'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['INFORMANTE'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlFuenteInformacion][$arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.2.1.n.5.2']]);
                        }
                        $controlFuenteInformacion = $controlFuenteInformacion+1;
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.6'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['DESCRIPCION'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.6']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['SUPERFICIEVENDIBLEPORUNIDAD'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.7']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.8'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['PRECIOSOLICITADO'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.8']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.10']) && count($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.10']]) > 0){
                        $cuentaCatastral = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]//'.$arrConstruccionesEnVenta['arrIds']['h.2.1'].'[@id="h.2.1"]//'.$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.10'].'[@id="h.2.1.n.10"]');
                        $arrFuenteDeInformacion = $this->obtenElementos($cuentaCatastral);
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.1'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['REGION'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlCuentaCatastral][$arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.1']]);
                        }
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.2'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['MANZANA'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlCuentaCatastral][$arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.2']]);
                        }
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.3'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['LOTE'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlCuentaCatastral][$arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.3']]);
                        }
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.4'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['UNIDADPRIVATIVA'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlCuentaCatastral][$arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.4']]);
                        }
                        $controlCuentaCatastral = $controlCuentaCatastral+1; 
                    }

                }
            }
        }
        /**********************************************************************************************************************************************************************/
        if(isset($arrPrincipalEnfoqueDeMercado['arrIds']['h.3'])){
            $valorDeMercadoDelInmueble = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.3'].'[@id="h.3"]');
            //echo (String)($valorDeMercadoDelInmueble[0]); exit();            
            $camposFexavaAvaluo['DIVALORMERCADO'] = (String)($valorDeMercadoDelInmueble[0]);
        }

        /******************************************************************Conclusiones homologación construcciones en venta********************************************************/

        if(isset($arrPrincipalEnfoqueDeMercado['arrIds']['h.4'])){
            $construccionesEnRenta = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]');
            $arrConstruccionesEnRenta = $this->obtenElementosPrincipal($construccionesEnRenta);
            //print_r($arrConstruccionesEnRenta); exit();

            if(isset($arrConstruccionesEnRenta['arrIds']['h.4.2'])){
                $conclusioneshomologacionConstruccionesEnVenta = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]//'.$arrConstruccionesEnRenta['arrIds']['h.4.2'].'[@id="h.4.2"]');
                $arrConclusioneshomologacionConstruccionesEnVenta = $this->obtenElementosPrincipal($conclusioneshomologacionConstruccionesEnVenta);

                if(!isset($camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'] = array();
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0] = array();
                    $controlElemento = 0;
                }else{
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER']);
                }
                

                if(isset($arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.1'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['VALORUNITARIOPROMEDIO'] = (String)($arrConclusioneshomologacionConstruccionesEnVenta['arrElementos'][$arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.1']]);
                }

                if(isset($arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.2'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['VALORUNITARIOHOMOLOGADO'] = (String)($arrConclusioneshomologacionConstruccionesEnVenta['arrElementos'][$arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.2']]);
                }

                if(isset($arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.7'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['VALORUNITARIOAPLICABLE'] = (String)($arrConclusioneshomologacionConstruccionesEnVenta['arrElementos'][$arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.7']]);
                }

                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['IDMODOCONSTRUCCION'] = 'R';
            }

            if ($this->esTerrenoValdio($infoXmlElementosConst, $elementoPrincipal) == FALSE){
                if(isset($arrConstruccionesEnRenta['arrIds']['h.4.1'])){
                
                    /*if(!isset($camposFexavaAvaluo['FEXAVA_INVESTPRODUCTOSCOMP'])){
                        $camposFexavaAvaluo['FEXAVA_INVESTPRODUCTOSCOMP'] = array();
                    }*/
                    
                    $investigacionProductoscomparables = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]//'.$arrConstruccionesEnRenta['arrIds']['h.4.1'].'[@id="h.4.1"]');
                    $arrInvestigacionProductoscomparables = $this->obtenElementos($investigacionProductoscomparables);
                    //$controlElemento = count($camposFexavaAvaluo['FEXAVA_INVESTPRODUCTOSCOMP']) - 1;
                    //print_r($arrInvestigacionProductoscomparables); exit();
                    $controlCuentaCatastral = 0;
                    $controlFuenteInformacion = 0;
                    for($i=0; $i < count($investigacionProductoscomparables); $i++){
                        //$controlElemento = $controlElemento + 1;
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i] = array();
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.1'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['CODTIPOCOMPARABLE'] = 'R';
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['CALLE'] = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.1']]);
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.3'])){
                            $codDelegacion = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.3']]);
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['IDDELEGACION'] = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorClave($codDelegacion);
                            if(isset($arrInvestigacionProductoscomparables['arrIds'][$controlElemento]['h.4.1.n.2'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['IDCOLONIA'] = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion((String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.2']]), $codDelegacion);
                            }
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.4'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['CODIGOPOSTAL'] = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.4']]);
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.5']) && count($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.5']]) > 0){
                            $fuenteDeInformacion = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]//'.$arrConstruccionesEnRenta['arrIds']['h.4.1'].'[@id="h.4.1"]//'.$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.5'].'[@id="h.4.1.n.5"]');
                            //print_r($fuenteDeInformacion); exit();
                            $arrFuenteDeInformacion = $this->obtenElementos($fuenteDeInformacion);
                            //print_r($arrFuenteDeInformacion); exit();
                            if(isset($arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.4.1.n.5.1'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['TELEFONO'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlFuenteInformacion][$arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.4.1.n.5.1']]);
                            }
                            if(isset($arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.4.1.n.5.2'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['INFORMANTE'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlFuenteInformacion][$arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.4.1.n.5.2']]);
                            }
                            $controlFuenteInformacion = $controlFuenteInformacion+1;
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.7'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['SUPERFICIEVENDIBLEPORUNIDAD'] = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.7']]);
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.8'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['PRECIOSOLICITADO'] = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.8']]);
                        }
                        //print_r($arrInvestigacionProductoscomparables['arrIds'][$i]);
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.10']) && count($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.10']]) > 0){
                            $cuentaCatastral = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]//'.$arrConstruccionesEnRenta['arrIds']['h.4.1'].'[@id="h.4.1"]//'.$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.10'].'[@id="h.4.1.n.10"]');
                            //print_r($cuentaCatastral); exit();
    
                            $arrCuentaCatastral = $this->obtenElementos($cuentaCatastral);
                            //print_r($arrCuentaCatastral); exit();
                            if(isset($arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.1'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['REGION'] = (String)($arrCuentaCatastral['arrElementos'][$controlCuentaCatastral][$arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.1']]);
                            }
                            if(isset($arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.2'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['MANZANA'] = (String)($arrCuentaCatastral['arrElementos'][$controlCuentaCatastral][$arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.2']]);
                            }
                            if(isset($arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.3'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['LOTE'] = (String)($arrCuentaCatastral['arrElementos'][$controlCuentaCatastral][$arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.3']]);
                            }
                            if(isset($arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.4'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['UNIDADPRIVATIVA'] = (String)($arrCuentaCatastral['arrElementos'][$controlCuentaCatastral][$arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.4']]);
                            }
                            $controlCuentaCatastral = $controlCuentaCatastral+1;
                        }
                    }
                }
            }
            
            
           
        }
        
        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '32. Error en el servidor'], 500);
    }
        
    }

    public function guardarAvaluoEnfoqueMercadoV($infoXmlElementosConst, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO'] = array();

        $enfoqueDeMercado = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]');

        if(count($enfoqueDeMercado) == 0){
            return $camposFexavaAvaluo; 
        }
        
        $errores = valida_AvaluoEnfoqueMercadoV($enfoqueDeMercado, $elementoPrincipal);    
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;
        }
        $arrPrincipalEnfoqueDeMercado = $this->obtenElementosPrincipal($enfoqueDeMercado);
        //print_r($arrPrincipalEnfoqueDeMercado); exit();
        if(isset($arrPrincipalEnfoqueDeMercado['arrIds']['h.1']) && count($arrPrincipalEnfoqueDeMercado['arrElementos'][$arrPrincipalEnfoqueDeMercado['arrIds']['h.1']]) > 0){           
            $terrenos = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]');
            $arrTerrenos = $this->obtenElementosPrincipal($terrenos);
            /********************************************************Conclusiones homologación terrenos******************************************************************/
            if(isset($arrTerrenos['arrIds']['h.1.2'])){
                $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO'] = array();
                $conclusionesHomologacionTerreno = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.2'].'[@id="h.1.2"]');
                $arrConclusionesHomologacionTerreno = $this->obtenElementosPrincipal($conclusionesHomologacionTerreno);
                //print_r($arrConclusionesHomologacionTerreno); exit();

                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.2.2'])){
                    $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIOTIERRAPROMEDIO'] = (String)($arrConclusionesHomologacionTerreno ['arrElementos'][$arrConclusionesHomologacionTerreno ['arrIds']['h.1.2.2']]);
                }

                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.2.2'])){
                    $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIOTIERRAHOMOLOGADO'] = (String)($arrConclusionesHomologacionTerreno ['arrElementos'][$arrConclusionesHomologacionTerreno ['arrIds']['h.1.2.2']]);
                }

                $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['CODTIPOTERRENO'] = 'D';
            }

            /********************************************************Terrenos Directos******************************************************************/

            if(isset($arrTerrenos['arrIds']['h.1.1'])){
                $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'] = array();
                $terrenosDirectos = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.1'].'[@id="h.1.1"]');
                $arrTerrenosDirectos = $this->obtenElementos($terrenosDirectos);
                //print_r($arrTerrenosDirectos); exit();
                $controlElemento = count($camposFexavaAvaluo['FEXAVA_DATOSTERRENOS']) - 1;

                for($i=0;$i < count($terrenosDirectos); $i++){
                    $controlElemento = $controlElemento + 1;
                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.1'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CALLE'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.1']]);
                    }
    
                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.3'])){
                        $codDelegacion = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.3']]);
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['IDDELEGACION'] = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorClave($codDelegacion);
                        if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.2'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['IDCOLONIA'] = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion((String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.2']]), $codDelegacion);
                        }
    
                    }
    
                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CODIGOPOSTAL'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.4']]);
                    }
    
                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.5']) && count($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.5']]) > 0){
                        //print_r($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.5']]); exit();
                        $subarreglo = $arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.5']];
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['TELEFONO'] = (String)($subarreglo->Telefono);
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['INFORMANTE'] = (String)($subarreglo->Informante); 
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.6'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['DESCRIPCION'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.6']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['USOSUELO'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.7']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.8'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CUS'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.8']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.9'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['SUPERFICIE'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.9']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.10'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FZO'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.10']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.11'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FUB'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.11']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.12'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FFR'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.12']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.13'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FFO'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.13']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.14'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FSU'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.14']]);
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.18']) && count($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.18']]) > 0){
                        $fot = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.1'].'[@id="h.1.1"]//'.$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.18'].'[@id="h.1.1.n.18"]');
                        $arrFot = $this->obtenElementosPrincipal($fot);
                        if(isset($arrTerrenosDirectos['arrIds']['h.1.1.n.18.1'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FOTVALOR'] = (String)($arrFot['arrElementos'][$arrTerrenosDirectos['arrIds']['h.1.1.n.18.1']]);
                        }
                        if(isset($arrTerrenosDirectos['arrIds']['h.1.1.n.18.2'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['FOTDESCRIPCION'] = (String)($arrFot['arrElementos'][$arrTerrenosDirectos['arrIds']['h.1.1.n.18.2']]);
                        }
                        
                    }

                    if(isset($arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.15'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['PRECIOSOLICITADO'] = (String)($arrTerrenosDirectos['arrElementos'][$i][$arrTerrenosDirectos['arrIds'][$i]['h.1.1.n.15']]);
                    }
                    
                }
                
            }

            if(isset($arrTerrenos['arrIds']['h.1.3'])){
                $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO'] = array();
                $conclusionesHomologacionTerreno = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]');
                $arrConclusionesHomologacionTerreno = $this->obtenElementosPrincipal($conclusionesHomologacionTerreno);
                //print_r($arrConclusionesHomologacionTerreno); exit();

                /********************************************************Conclusiones homologación comp. Residuales******************************************************************/
                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.3.5'])){
                    $conclusionesHomologacionResiduales = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]//'.$arrConclusionesHomologacionTerreno['arrIds']['h.1.3.5'].'[@id="h.1.3.5"]');
                    $arrConclusionesHomologacionResiduales = $this->obtenElementosPrincipal($conclusionesHomologacionResiduales);

                    if(isset($arrConclusionesHomologacionResiduales['arrIds']['h.1.3.5.1'])){
                        $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIOTIERRAPROMEDIO'] = (String)($arrConclusionesHomologacionResiduales['arrElementos'][$arrConclusionesHomologacionResiduales['arrIds']['h.1.3.5.1']]);
                    }

                    if(isset($arrConclusionesHomologacionResiduales['arrIds']['h.1.3.5.2'])){
                        $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIOTIERRAHOMOLOGADO'] = (String)($arrConclusionesHomologacionResiduales['arrElementos'][$arrConclusionesHomologacionResiduales['arrIds']['h.1.3.5.2']]);
                    }
                    
                }

                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.3.6'])){
                    $conclusionesHomologacionResiduales = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]//'.$arrConclusionesHomologacionTerreno['arrIds']['h.1.3.6'].'[@id="h.1.3.6"]');
                    $arrConclusionesHomologacionResiduales = $this->obtenElementosPrincipal($conclusionesHomologacionResiduales);

                    if(isset($arrConclusionesHomologacionResiduales['arrIds']['h.1.3.6.4'])){
                        $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['VALORUNITARIORESIDUAL'] = (String)($arrConclusionesHomologacionResiduales['arrElementos'][$arrConclusionesHomologacionResiduales['arrIds']['h.1.3.6.4']]);
                    }
                }

                $camposFexavaAvaluo['FEXAVA_TERRENOMERCADO']['CODTIPOTERRENO'] = 'R';

                /********************************************************Investigación productos comparables******************************************************************/

                if(isset($arrConclusionesHomologacionTerreno['arrIds']['h.1.3.4'])){
                    $productosComparables = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]//'.$arrConclusionesHomologacionTerreno['arrIds']['h.1.3.4'].'[@id="h.1.3.4"]');
                    $arrProductosComparables = $this->obtenElementos($productosComparables);
                    //echo "arrProductosComparables "; print_r($arrProductosComparables); exit();
                    if(!isset($camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'])){
                        $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'] = array();
                    }                    
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_DATOSTERRENOS']) - 1;
                    for($i=0;$i < count($productosComparables);$i++){
                        $controlElemento = $controlElemento + 1;

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.1'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CALLE'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.1']]);
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.3'])){
                            $codDelegacion = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.3']]);
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['IDDELEGACION'] = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorClave($codDelegacion);
                            if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.2'])){
                                $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['IDCOLONIA'] = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion((String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.2']]), $codDelegacion);
                            }
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.4'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['CODIGOPOSTAL'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.4']]);
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.5']) && count($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.5']]) > 0){
                            $fot = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.3'].'[@id="h.1.3"]//InvestigacionProductosComparables[@id="h.1.3.4"]//'.$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.5'].'[@id="h.1.3.4.n.5"]');
                            $arrFot = $this->obtenElementosPrincipal($fot);
                            //if(isset($arrProductosComparables['arrIds']['h.1.3.4.n.5.1'])){ echo (String)($arrFot['arrElementos'][$arrFot['arrIds']['h.1.3.4.n.5.1']]); exit();
                                $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['TELEFONO'] = (String)($arrFot['arrElementos'][$arrFot['arrIds']['h.1.3.4.n.5.1']]);
                           // }
                            //if(isset($arrProductosComparables['arrIds']['h.1.3.4.n.5.2'])){
                                $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['INFORMANTE'] = (String)($arrFot['arrElementos'][$arrFot['arrIds']['h.1.3.4.n.5.2']]);
                            //}
                            
                        }//print_r($camposFexavaAvaluo['FEXAVA_DATOSTERRENOS']); exit();

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.6'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['DESCRIPCION'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.6']]);
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.7'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['SUPERFICIE'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.7']]);
                        }

                        if(isset($arrProductosComparables['arrIds'][$i]['h.1.3.4.n.8'])){
                            $camposFexavaAvaluo['FEXAVA_DATOSTERRENOS'][$controlElemento]['PRECIOSOLICITADO'] = (String)($arrProductosComparables['arrElementos'][$i][$arrProductosComparables['arrIds'][$i]['h.1.3.4.n.8']]);
                        }

                        
                    }
                }
            }

            if(isset($arrTerrenos['arrIds']['h.1.4'])){
                //echo "EL ARR_CAMPOS_FEXAVA "; print_r($camposFexavaAvaluo); exit();
                $valorUnitarioDeTierraAplicableAlAvaluo = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.1'].'[@id="h.1"]//'.$arrTerrenos['arrIds']['h.1.4'].'[@id="h.1.4"]');

                $arrValorUnitarioDeTierraAplicableAlAvaluo = $this->obtenElementosPrincipal($valorUnitarioDeTierraAplicableAlAvaluo);

                $minMuestras = env("MINMUESTRAS");

                if($minMuestras > 0){
                    $infoDesviacion = $this->modelAva->valorUnitarioDesviacion($camposFexavaAvaluo['REGION'],$camposFexavaAvaluo['MANZANA'],$camposFexavaAvaluo['LOTE'],$camposFexavaAvaluo['UNIDADPRIVATIVA']);
                    if($infoDesviacion != 'Error al obtener la desviación estandar.'){

                        if(isset($infoDesviacion) && count($infoDesviacion) > 0){
                            if($infoDesviacion['NUMMUESTRAS'] < $minMuestras || trim($infoDesviacion['MEDIAVUS'] == ''))    {
                                $camposFexavaAvaluo['ALERTA'] = "No ha sido posible hacer la validación de valor unitario de suelo por falta de datos. ¿Desea continuar?";
                            }else{
                                if(trim($infoDesviacion['DESVIACION']) == ''){
                                    $infoDesviacion['DESVIACION'] = 0;
                                }
                                $valMinVus = $infoDesviacion['MEDIAVUS'] - $infoDesviacion['DESVIACION'];
                                $valMaxVus = $infoDesviacion['MEDIAVUS'] + $infoDesviacion['DESVIACION'];
                                if($valorUnitarioDeTierraAplicableAlAvaluo[0] < $valMinVus || $valorUnitarioDeTierraAplicableAlAvaluo[0] > $valMaxVus){
                                    $camposFexavaAvaluo['ALERTA'] = "El valor unitario de suelo del avalúo no se encuentra entre en el rango del valor mínimo y máximo de VUS en base a la media para el área de valor. ¿Desea continuar?";
                                }
                            }
                        }

                    }else{
                        //return array('ERROR' => array($infoDesviacion)); 
                        $camposFexavaAvaluo['ERRORES'][] = $errores;
                    }
                    
                }    
                
                $camposFexavaAvaluo['VALORUNITARIOTIERRAAVALUO'] = (String)($valorUnitarioDeTierraAplicableAlAvaluo[0]);
            }

        }

        /*********************************************************************** Conclusiones homologación construcciones en venta*********************************************************/

        if(isset($arrPrincipalEnfoqueDeMercado['arrIds']['h.2']) && count($arrPrincipalEnfoqueDeMercado['arrElementos'][$arrPrincipalEnfoqueDeMercado['arrIds']['h.2']]) > 0){
            $construccionesEnVenta = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]');
            $arrConstruccionesEnVenta = $this->obtenElementosPrincipal($construccionesEnVenta);
            
            if(isset($arrConstruccionesEnVenta['arrIds']['h.2.2'])){
                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'] = array();
                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0] = array();               
                $conclusionesHomologacionConstruccionesEnVenta = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]//'.$arrConstruccionesEnVenta['arrIds']['h.2.2'].'[@id="h.2.2"]');
                $arrConclusionesHomologacionConstruccionesEnVenta = $this->obtenElementosPrincipal($conclusionesHomologacionConstruccionesEnVenta);

                //print_r($arrConclusionesHomologacionConstruccionesEnVenta); exit();
                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['IDMODOCONSTRUCCION'] = 'V';
                if(isset($arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.1'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['VALORUNITARIOPROMEDIO'] = (String)($arrConclusionesHomologacionConstruccionesEnVenta['arrElementos'][$arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.1']]);
                }

                if(isset($arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.2'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['VALORUNITARIOHOMOLOGADO'] = (String)($arrConclusionesHomologacionConstruccionesEnVenta['arrElementos'][$arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.2']]);
                }

                if(isset($arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.7'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['VALORUNITARIOAPLICABLE'] = (String)($arrConclusionesHomologacionConstruccionesEnVenta['arrElementos'][$arrConclusionesHomologacionConstruccionesEnVenta['arrIds']['h.2.2.7']]);
                }
                
            }

            if(isset($arrConstruccionesEnVenta['arrIds']['h.2.1'])){
                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'] = array();
                $investigacionProductosComparables = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]//'.$arrConstruccionesEnVenta['arrIds']['h.2.1'].'[@id="h.2.1"]');
                $arrInvestigacionProductosComparables = $this->obtenElementos($investigacionProductosComparables);

                $controlElemento = count($camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP']) - 1;
                $controlCuentaCatastral = 0;
                $controlFuenteInformacion = 0;
                for($i=0;$i < count($investigacionProductosComparables); $i++){
                    $controlElemento = $controlElemento + 1;

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.1'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['CALLE'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.1']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.3'])){
                        $codDelegacion = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.3']]);
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['IDDELEGACION'] = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorClave($codDelegacion);
                        if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.2'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['IDCOLONIA'] = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion((String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.2']]), $codDelegacion);                            
                        }
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.4'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['CODIGOPOSTAL'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.4']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.5']) && count($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.5']]) > 0){
                        $fuenteDeInformacion = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]//'.$arrConstruccionesEnVenta['arrIds']['h.2.1'].'[@id="h.2.1"]//'.$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.5'].'[@id="h.2.1.n.5"]');
                        $arrFuenteDeInformacion = $this->obtenElementos($fuenteDeInformacion);
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.2.1.n.5.1'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['TELEFONO'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlFuenteInformacion][$arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.2.1.n.5.1']]);
                        }
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.2.1.n.5.2'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['INFORMANTE'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlFuenteInformacion][$arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.2.1.n.5.2']]);
                        }
                        $controlFuenteInformacion = $controlFuenteInformacion+1;
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.6'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['DESCRIPCION'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.6']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.7'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['SUPERFICIEVENDIBLEPORUNIDAD'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.7']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.8'])){
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['PRECIOSOLICITADO'] = (String)($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.8']]);
                    }

                    if(isset($arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.10']) && count($arrInvestigacionProductosComparables['arrElementos'][$i][$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.10']]) > 0){
                        $cuentaCatastral = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.2'].'[@id="h.2"]//'.$arrConstruccionesEnVenta['arrIds']['h.2.1'].'[@id="h.2.1"]//'.$arrInvestigacionProductosComparables['arrIds'][$i]['h.2.1.n.10'].'[@id="h.2.1.n.10"]');
                        $arrFuenteDeInformacion = $this->obtenElementos($cuentaCatastral);
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.1'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['REGION'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlCuentaCatastral][$arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.1']]);
                        }
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.2'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['MANZANA'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlCuentaCatastral][$arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.2']]);
                        }
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.3'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['LOTE'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlCuentaCatastral][$arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.3']]);
                        }
                        if(isset($arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.4'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0]['FEXAVA_INVESTPRODUCTOSCOMP'][$controlElemento]['UNIDADPRIVATIVA'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlCuentaCatastral][$arrFuenteDeInformacion['arrIds'][$controlCuentaCatastral]['h.2.1.n.10.4']]);
                        }
                        $controlCuentaCatastral = $controlCuentaCatastral+1; 
                    }

                }
            }
        }
        /**********************************************************************************************************************************************************************/
        if(isset($arrPrincipalEnfoqueDeMercado['arrIds']['h.3'])){
            $valorDeMercadoDelInmueble = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.3'].'[@id="h.3"]');
            //echo (String)($valorDeMercadoDelInmueble[0]); exit();            
            $camposFexavaAvaluo['DIVALORMERCADO'] = (String)($valorDeMercadoDelInmueble[0]);
        }

        /******************************************************************Conclusiones homologación construcciones en venta********************************************************/

        if(isset($arrPrincipalEnfoqueDeMercado['arrIds']['h.4'])){
            $construccionesEnRenta = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]');
            $arrConstruccionesEnRenta = $this->obtenElementosPrincipal($construccionesEnRenta);
            //print_r($arrConstruccionesEnRenta); exit();

            if(isset($arrConstruccionesEnRenta['arrIds']['h.4.2'])){
                $conclusioneshomologacionConstruccionesEnVenta = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]//'.$arrConstruccionesEnRenta['arrIds']['h.4.2'].'[@id="h.4.2"]');
                $arrConclusioneshomologacionConstruccionesEnVenta = $this->obtenElementosPrincipal($conclusioneshomologacionConstruccionesEnVenta);

                if(!isset($camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'] = array();
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][0] = array();
                    $controlElemento = 0;
                }else{
                    $controlElemento = count($camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER']);
                }
                

                if(isset($arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.1'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['VALORUNITARIOPROMEDIO'] = (String)($arrConclusioneshomologacionConstruccionesEnVenta['arrElementos'][$arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.1']]);
                }

                if(isset($arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.2'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['VALORUNITARIOHOMOLOGADO'] = (String)($arrConclusioneshomologacionConstruccionesEnVenta['arrElementos'][$arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.2']]);
                }

                if(isset($arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.7'])){
                    $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['VALORUNITARIOAPLICABLE'] = (String)($arrConclusioneshomologacionConstruccionesEnVenta['arrElementos'][$arrConclusioneshomologacionConstruccionesEnVenta['arrIds']['h.4.2.7']]);
                }

                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['IDMODOCONSTRUCCION'] = 'R';
            }

            if ($this->esTerrenoValdio($infoXmlElementosConst, $elementoPrincipal) == FALSE){
                if(isset($arrConstruccionesEnRenta['arrIds']['h.4.1'])){
                
                    /*if(!isset($camposFexavaAvaluo['FEXAVA_INVESTPRODUCTOSCOMP'])){
                        $camposFexavaAvaluo['FEXAVA_INVESTPRODUCTOSCOMP'] = array();
                    }*/
                    
                    $investigacionProductoscomparables = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]//'.$arrConstruccionesEnRenta['arrIds']['h.4.1'].'[@id="h.4.1"]');
                    $arrInvestigacionProductoscomparables = $this->obtenElementos($investigacionProductoscomparables);
                    //$controlElemento = count($camposFexavaAvaluo['FEXAVA_INVESTPRODUCTOSCOMP']) - 1;
                    //print_r($arrInvestigacionProductoscomparables); exit();
                    $controlCuentaCatastral = 0;
                    $controlFuenteInformacion = 0;
                    for($i=0; $i < count($investigacionProductoscomparables); $i++){
                        //$controlElemento = $controlElemento + 1;
                        $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i] = array();
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.1'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['CODTIPOCOMPARABLE'] = 'R';
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['CALLE'] = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.1']]);
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.3'])){
                            $codDelegacion = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.3']]);
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['IDDELEGACION'] = $this->modelDatosExtrasAvaluo->ObtenerIdDelegacionPorClave($codDelegacion);
                            if(isset($arrInvestigacionProductoscomparables['arrIds'][$controlElemento]['h.4.1.n.2'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['IDCOLONIA'] = $this->modelDatosExtrasAvaluo->ObtenerIdColoniaPorNombreyDelegacion((String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.2']]), $codDelegacion);
                            }
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.4'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['CODIGOPOSTAL'] = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.4']]);
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.5']) && count($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.5']]) > 0){
                            $fuenteDeInformacion = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]//'.$arrConstruccionesEnRenta['arrIds']['h.4.1'].'[@id="h.4.1"]//'.$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.5'].'[@id="h.4.1.n.5"]');
                            //print_r($fuenteDeInformacion); exit();
                            $arrFuenteDeInformacion = $this->obtenElementos($fuenteDeInformacion);
                            //print_r($arrFuenteDeInformacion); exit();
                            if(isset($arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.4.1.n.5.1'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['TELEFONO'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlFuenteInformacion][$arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.4.1.n.5.1']]);
                            }
                            if(isset($arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.4.1.n.5.2'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['INFORMANTE'] = (String)($arrFuenteDeInformacion['arrElementos'][$controlFuenteInformacion][$arrFuenteDeInformacion['arrIds'][$controlFuenteInformacion]['h.4.1.n.5.2']]);
                            }
                            $controlFuenteInformacion = $controlFuenteInformacion+1;
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.7'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['SUPERFICIEVENDIBLEPORUNIDAD'] = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.7']]);
                        }
    
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.8'])){
                            $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['PRECIOSOLICITADO'] = (String)($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.8']]);
                        }
                        //print_r($arrInvestigacionProductoscomparables['arrIds'][$i]);
                        if(isset($arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.10']) && count($arrInvestigacionProductoscomparables['arrElementos'][$i][$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.10']]) > 0){
                            $cuentaCatastral = $infoXmlElementosConst->xpath($elementoPrincipal.'//EnfoqueDeMercado[@id="h"]//'.$arrPrincipalEnfoqueDeMercado['arrIds']['h.4'].'[@id="h.4"]//'.$arrConstruccionesEnRenta['arrIds']['h.4.1'].'[@id="h.4.1"]//'.$arrInvestigacionProductoscomparables['arrIds'][$i]['h.4.1.n.10'].'[@id="h.4.1.n.10"]');
                            //print_r($cuentaCatastral); exit();
    
                            $arrCuentaCatastral = $this->obtenElementos($cuentaCatastral);
                            //print_r($arrCuentaCatastral); exit();
                            if(isset($arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.1'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['REGION'] = (String)($arrCuentaCatastral['arrElementos'][$controlCuentaCatastral][$arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.1']]);
                            }
                            if(isset($arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.2'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['MANZANA'] = (String)($arrCuentaCatastral['arrElementos'][$controlCuentaCatastral][$arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.2']]);
                            }
                            if(isset($arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.3'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['LOTE'] = (String)($arrCuentaCatastral['arrElementos'][$controlCuentaCatastral][$arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.3']]);
                            }
                            if(isset($arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.4'])){
                                $camposFexavaAvaluo['FEXAVA_CONSTRUCCIONESMER'][$controlElemento]['FEXAVA_INVESTPRODUCTOSCOMP'][$i]['UNIDADPRIVATIVA'] = (String)($arrCuentaCatastral['arrElementos'][$controlCuentaCatastral][$arrCuentaCatastral['arrIds'][$controlCuentaCatastral]['h.4.1.n.10.4']]);
                            }
                            $controlCuentaCatastral = $controlCuentaCatastral+1;
                        }
                    }
                }
            }
            
            
           
        }
        
        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '33. Error en el servidor'], 500);
    }
        
    }

    /// <summary>
        /// Inserta los datos referentes al enfoque de costos del avaluo Comercial en el dseAvaluos desde
        /// el elemento xml.
        /// </summary>
        /// <param name="enfoqueCostosComercial">Elemento xml con los datos de enfoque de costos
        /// comerciales.</param>
        
    public function guardarAvaluoEnfoqueCostosComercial($xmlEnfoqueDeCostos, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        $arrCostos = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//EnfoqueDeCostos[@id="i"]');

        if(count($arrCostos) == 0){
            return $camposFexavaAvaluo;
        }
        $datad13 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//Terreno[@id="d"]//ValorTotalDelTerrenoProporcional[@id="d.13"]');
        $datae2 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]');
        $dataf12 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//SumatoriaTotalInstalacionesEspecialesObrasComplementariasYElementosAccesoriosPrivativas[@id="f.12"]');
        $dataf14 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes[@id="f.14"]');

        $errores = valida_AvaluoEnfoqueCostosComercial($xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//EnfoqueDeCostos[@id="i"]'), $elementoPrincipal, $datad13, $datae2, $dataf12, $dataf14);    
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;
        }
        $enfoqueDeCostos = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//EnfoqueDeCostos[@id="i"]//ImporteTotalDelEnfoqueDeCostos[@id="i.6"]');        

        $camposFexavaAvaluo['IMPORTETOTALENFCOSTOS'] = (String)($enfoqueDeCostos[0]);
        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '34. Error en el servidor'], 500);
    }
    }

    public function guardarAvaluoEnfoqueCostosComercialV($xmlEnfoqueDeCostos, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        $arrCostos = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//EnfoqueDeCostos[@id="i"]');

        if(count($arrCostos) == 0){
            return $camposFexavaAvaluo;
        }
        $datad13 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//Terreno[@id="d"]//ValorTotalDelTerrenoProporcional[@id="d.13"]');
        $datae2 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]');
        $dataf12 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//ImporteTotalInstalacionesAccesoriosComplementariasPrivativas[@id="f.12"]');
        $dataf14 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//ImporteIndivisoInstalacionesEspecialesObrasComplementariasYElementosAccesoriosComunes[@id="f.14"]');

        $errores = valida_AvaluoEnfoqueCostosComercialV($xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//EnfoqueDeCostos[@id="i"]'), $elementoPrincipal, $datad13, $datae2, $dataf12, $dataf14);    
        if(count($errores) > 0){
            //return array('ERROR' => $errores);
            $camposFexavaAvaluo['ERRORES'][] = $errores;
        }
        $enfoqueDeCostos = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//EnfoqueDeCostos[@id="i"]//ImporteTotalDelEnfoqueDeCostos[@id="i.6"]');        

        $camposFexavaAvaluo['IMPORTETOTALENFCOSTOS'] = (String)($enfoqueDeCostos[0]);
        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '35. Error en el servidor'], 500);
    }
    }

    /// <summary>
        /// Inserta los datos referentes al enfoque de costos del avaluo Catastral en el dseAvaluos desde
        /// el elemento xml.
        /// </summary>
        /// <param name="enfoqueCostosCatastral">Elemento xml con los datos de enfoque de costos
        /// catastrales.</param>
    public function guardarAvaluoEnfoqueCostosCatastral($xmlEnfoqueDeCostos, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        if(count($xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//EnfoqueDeCostos[@id="j"]')) == 0){
            return $camposFexavaAvaluo;
        }

        $camposFexavaAvaluo['FEXAVA_ENFOQUECOSTESCAT'] = array();
        $general = $xmlEnfoqueDeCostos->xpath($elementoPrincipal);        
        $arrGeneral = $this->obtenElementosPrincipal($general);        
        //print_r($arrGeneral['arrIds']); exit();
        if(isset(($arrGeneral['arrIds']['j']))){

            $datae23 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]//ValorTotalDeConstruccionesPrivativas[@id="e.2.3"]');
            //error_log("EL e.2.3 ".json_encode($datae23));
            $datae27 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//DescripcionDelInmueble[@id="e"]//TiposDeConstruccion[@id="e.2"]//ValorTotalDeConstruccionesComunes[@id="e.2.7"]');
            //error_log("EL e.2.7 ".json_encode($datae27));
            $datab6 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//Antecedentes[@id="b"]//RegimenDePropiedad[@id="b.6"]');
            $datad6 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//Terreno[@id="d"]//Indiviso[@id="d.6"]');
            $datad13 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//Terreno[@id="d"]//ValorTotalDelTerrenoProporcional[@id="d.13"]');
            $f9 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//InstalacionesEspeciales[@id="f.9"]'); //echo "count ".count(quitar_attributes(convierte_a_arreglo($f9)))."\n"; //echo " SOY F9 ".print_r(quitar_attributes(convierte_a_arreglo($f9))); echo "  
            if(count(quitar_attributes(convierte_a_arreglo($f9))) > 0){
                $existef9 = TRUE;
            }else{
                $existef9 = FALSE;
            }
            $f10 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//ElementosAccesorios[@id="f.10"]'); //echo "  count ".count(quitar_attributes(convierte_a_arreglo($f10)))."\n"; //echo " SOY F10 ".print_r(quitar_attributes(convierte_a_arreglo($f10))); echo "  count ".count(quitar_attributes(convierte_a_arreglo($f10)))."\n";
            if(count(quitar_attributes(convierte_a_arreglo($f10))) > 0){
                $existef10 = TRUE;
            }else{
                $existef10 = FALSE;
            }
            $f11 = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//ElementosDeLaConstruccion[@id="f"]//ObrasComplementarias[@id="f.11"]'); //echo "  count ".count(quitar_attributes(convierte_a_arreglo($f11)))."\n"; //echo " SOY F11 ".print_r(quitar_attributes(convierte_a_arreglo($f11))); 
            if(count(quitar_attributes(convierte_a_arreglo($f11))) > 0){
                $existef11 = TRUE;
            }else{
                $existef11 = FALSE;
            } //echo "existef9 ".var_dump($existef9)." existef10 ".var_dump($existef10)." existef11".var_dump($existef11); exit();

            $errores = valida_AvaluoEnfoqueCostosCatastral($xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//EnfoqueDeCostos[@id="j"]'), $elementoPrincipal, $datae23, $datae27, $datab6, $datad6, $datad13, $existef9, $existef10, $existef11);    
            if(count($errores) > 0){
                //return array('ERROR' => $errores);
                $camposFexavaAvaluo['ERRORES'][] = $errores;
            }

            $instalacionesEsp = $xmlEnfoqueDeCostos->xpath($elementoPrincipal.'//'.$arrGeneral['arrIds']['j'].'[@id="j"]');
            $arrInstalacionesEsp = $this->obtenElementosPrincipal($instalacionesEsp);
            if(isset($arrInstalacionesEsp['arrIds']['j.4'])){
                $camposFexavaAvaluo['FEXAVA_ENFOQUECOSTESCAT']['IMPINSTALACIONESESPECIALES'] = $arrInstalacionesEsp['arrElementos'][$arrInstalacionesEsp['arrIds']['j.4']];
            }

            if(isset($arrInstalacionesEsp['arrIds']['j.5'])){
                $camposFexavaAvaluo['FEXAVA_ENFOQUECOSTESCAT']['IMPTOTVALORCATASTRAL'] = $arrInstalacionesEsp['arrElementos'][$arrInstalacionesEsp['arrIds']['j.5']];
            }

            if(isset($arrInstalacionesEsp['arrIds']['j.6'])){
                $camposFexavaAvaluo['FEXAVA_ENFOQUECOSTESCAT']['AVANCEOBRA'] = $arrInstalacionesEsp['arrElementos'][$arrInstalacionesEsp['arrIds']['j.6']];
            }

            if(isset($arrInstalacionesEsp['arrIds']['j.7'])){
                $camposFexavaAvaluo['FEXAVA_ENFOQUECOSTESCAT']['IMPTOTVALCATASTRALOBRAPROCESO'] = $arrInstalacionesEsp['arrElementos'][$arrInstalacionesEsp['arrIds']['j.7']];
            }
            
        }
        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '36. Error en el servidor'], 500);
    }
    }

    /// <summary>
    /// Inserta los datos referentes al enfoque de ingresos en el dseAvaluos desde el elemento xml.
    /// </summary>
    /// <param name="enfoqueIngresos">Elemento xml con los datos de enfoque de ingresos.</param>
    public function guardarAvaluoEnfoqueIngresos($xmlEnfoqueDeIngresos, $camposFexavaAvaluo,$elementoPrincipal){
        try{
        $enfoqueDeIngresos = $xmlEnfoqueDeIngresos->xpath($elementoPrincipal.'//EnfoqueDeIngresos[@id="k"]');

        if(!isset($enfoqueDeIngresos[0]) || count($enfoqueDeIngresos) == 0){
            return $camposFexavaAvaluo;
        }else{
            $arrEnfoqueDeIngresos = convierte_a_arreglo($enfoqueDeIngresos); //print_r($arrEnfoqueDeIngresos); exit();
            if(count($arrEnfoqueDeIngresos[0]) == 1){
                return $camposFexavaAvaluo;
            }
        }

        if($this->esTerrenoValdio($xmlEnfoqueDeIngresos, $elementoPrincipal) == FALSE){

           
            if(isset($enfoqueDeIngresos)){
                $errores = valida_AvaluoEnfoqueIngresos($enfoqueDeIngresos, $elementoPrincipal);    
                if(count($errores) > 0){
                    //return array('ERROR' => $errores);
                    $camposFexavaAvaluo['ERRORES'][] = $errores;
                }

                $arrEnfoqueDeIngresos = $this->obtenElementosPrincipal($enfoqueDeIngresos);
            }    
            
            if(isset($arrEnfoqueDeIngresos['arrIds']['k.1'])){
                $camposFexavaAvaluo['EIRENTABRUTAMENSUAL'] = (String)($arrEnfoqueDeIngresos['arrElementos'][$arrEnfoqueDeIngresos['arrIds']['k.1']]);
            }

            if(isset($arrEnfoqueDeIngresos['arrIds']['k.3'])){
                $camposFexavaAvaluo['EIPRODUCTOLIQUIDOANUAL'] = (String)($arrEnfoqueDeIngresos['arrElementos'][$arrEnfoqueDeIngresos['arrIds']['k.3']]);
            }

            if(isset($arrEnfoqueDeIngresos['arrIds']['k.4'])){
                $camposFexavaAvaluo['EITASACAPITALIZACION'] = (String)($arrEnfoqueDeIngresos['arrElementos'][$arrEnfoqueDeIngresos['arrIds']['k.4']]);
            }

            if(isset($arrEnfoqueDeIngresos['arrIds']['k.5'])){
                $camposFexavaAvaluo['EIIMPORTE'] = (String)($arrEnfoqueDeIngresos['arrElementos'][$arrEnfoqueDeIngresos['arrIds']['k.5']]);
            }
        }
        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '37. Error en el servidor'], 500);
    }        

    }

    /// <summary>
        /// Inserta los datos referentes al resumen conclusion del avaluo en el dseAvaluos desde el
        /// elemento xml.
        /// </summary>
        /// <param name="conclusionAvaluo">Elemento xml con los datos de la conclusion del avaluo.</param>
    public function guardarAvaluoResumenConclusionAvaluo($xmlConclusionAvaluo, $camposFexavaAvaluo, $elementoPrincipal){
        try{
        $conclusionAvaluo = $xmlConclusionAvaluo->xpath($elementoPrincipal.'//ConclusionDelAvaluo[@id="o"]');
        
        $arrConclusionAvaluo = $this->obtenElementosPrincipal($conclusionAvaluo);

        if($elementoPrincipal == '//Comercial'){
            if(isset($arrConclusionAvaluo['arrIds']['o.1'])){
                $errores = valida_AvaluoConclusionDelAvaluoComercial($conclusionAvaluo, $elementoPrincipal);    
                if(count($errores) > 0){
                    //return array('ERROR' => $errores);
                    $camposFexavaAvaluo['ERRORES'][] = $errores;
                }
                $camposFexavaAvaluo['VALORCOMERCIAL'] = (String)($arrConclusionAvaluo['arrElementos'][$arrConclusionAvaluo['arrIds']['o.1']]);
            }
        }else{ 
            if(isset($arrConclusionAvaluo['arrIds']['o.2'])){
                $errores = valida_AvaluoConclusionDelAvaluoCatastral($conclusionAvaluo, $elementoPrincipal);    
                if(count($errores) > 0){
                    //return array('ERROR' => $errores);
                    $camposFexavaAvaluo['ERRORES'][] = $errores;
                }
                $camposFexavaAvaluo['VALORCATASTRAL'] = (String)($arrConclusionAvaluo['arrElementos'][$arrConclusionAvaluo['arrIds']['o.2']]);
            }
        }

        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '38. Error en el servidor'], 500);
    }
    }

    /// <summary>
        /// Inserta los datos referentes al valor referido del avaluo en el dseAvaluos desde el elemento
        /// xml.
        /// </summary>
        /// <param name="valorReferido">Elemento xml con los datos del valor referido del avaluo.</param>
    public function guardarAvaluoValorReferido($xmlValorReferido, $camposFexavaAvaluo, $elementoPrincipal){
        try{
        $valorReferido = $xmlValorReferido->xpath($elementoPrincipal.'//ValorReferido[@id="p"]');
        $datao1 = $xmlValorReferido->xpath($elementoPrincipal.'//ConclusionDelAvaluo[@id="o"]//ValorComercialDelInmueble[@id="o.1"]');        
        if(count($valorReferido) > 0){
            $arrLimpio = quitar_attributes(convierte_a_arreglo($valorReferido));
            if(count($arrLimpio[0]) > 0 && numero_datos($arrLimpio) > 0){
                $errores = valida_AvaluoValorReferido($valorReferido, $elementoPrincipal,$datao1);    
                if(count($errores) > 0){
                    //return array('ERROR' => $errores);
                    $camposFexavaAvaluo['ERRORES'][] = $errores;
                }

                $arrValorReferido = $this->obtenElementosPrincipal($valorReferido);

                if(isset($arrValorReferido['arrIds']['p.1'])){
                    $camposFexavaAvaluo['FECHAVALORREFERIDO'] = (String)($arrValorReferido['arrElementos'][$arrValorReferido['arrIds']['p.1']]);
                }
                if(isset($arrValorReferido['arrIds']['p.2'])){
                    $camposFexavaAvaluo['VALORREFERIDO'] = (String)($arrValorReferido['arrElementos'][$arrValorReferido['arrIds']['p.2']]);
                }
            }    
        }
        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '39. Error en el servidor'], 500);
    }
        
    }

    /// <summary>
        /// Inserta los datos referentes al anexo fotografico del avaluo en el dseAvaluos desde el
        /// elemento xml.
        /// </summary>
        /// <param name="transactionHelper">La transacción ayudante.</param>
        /// <param name="anexoFotografico">Elemento xml con los datos del anexo fotografico del avaluo.</param>
    public function guardarAvaluoAnexoFotografico($xmlAnexoFotografico, $camposFexavaAvaluo, $elementoPrincipal){
        try{
        $cuentaCatastralStr = '';
        $indiceCuentaCatastral = '1';
        $anexoFotografico = $xmlAnexoFotografico->xpath($elementoPrincipal.'//AnexoFotografico[@id="q"]');

        $errores = valida_AvaluoAnexoFotografico($anexoFotografico, $elementoPrincipal);    
            if(count($errores) > 0){
                //return array('ERROR' => $errores);
                $camposFexavaAvaluo['ERRORES'][] = $errores;
            }

        $arrAnexoFotografico = $this->obtenElementosPrincipal($anexoFotografico);
        if(isset($arrAnexoFotografico['arrIds']['q.1'])){
            $cuentaCatastral = $xmlAnexoFotografico->xpath($elementoPrincipal.'//AnexoFotografico[@id="q"]//'.$arrAnexoFotografico['arrIds']['q.1'].'[@id="q.1"]//CuentaCatastral[@id="q.1.1"]');            
            $arrCuentaCatastral = $this->obtenElementosPrincipal($cuentaCatastral);
            //print_r($arrCuentaCatastral); exit();
            if(isset($arrCuentaCatastral['arrIds']['q.1.1.1'])){
                
                $cuentaCatastralStr .= (String)($arrCuentaCatastral['arrElementos'][$arrCuentaCatastral['arrIds']['q.1.1.1']]);
            }

            if(isset($arrCuentaCatastral['arrIds']['q.1.1.2'])){
                $cuentaCatastralStr .= (String)($arrCuentaCatastral['arrElementos'][$arrCuentaCatastral['arrIds']['q.1.1.2']]);
            }

            if(isset($arrCuentaCatastral['arrIds']['q.1.1.3'])){
                $cuentaCatastralStr .= (String)($arrCuentaCatastral['arrElementos'][$arrCuentaCatastral['arrIds']['q.1.1.3']]);
            }

            if(isset($arrCuentaCatastral['arrIds']['q.1.1.4'])){
                $cuentaCatastralStr .= (String)($arrCuentaCatastral['arrElementos'][$arrCuentaCatastral['arrIds']['q.1.1.4']]);
            }

            $fotosInmuebleAvaluo = $xmlAnexoFotografico->xpath($elementoPrincipal.'//AnexoFotografico[@id="q"]//'.$arrAnexoFotografico['arrIds']['q.1'].'[@id="q.1"]//FotosInmuebleAvaluo[@id="q.1.2"]');            
            $arrFotosInmuebleAvaluo = $this->obtenElementos($fotosInmuebleAvaluo);            
            $camposFexavaAvaluo['FEXAVA_FOTOAVALUO'] = array();
            for($i=0;$i<count($fotosInmuebleAvaluo);$i++){
                if(isset($arrFotosInmuebleAvaluo['arrIds'][$i]['q.1.2.n.2'])){
                    $tipoFoto = $this->tipoInmueble((String)($arrFotosInmuebleAvaluo['arrElementos'][$i][$arrFotosInmuebleAvaluo['arrIds'][$i]['q.1.2.n.2']]));                    
                }

                if(isset($arrFotosInmuebleAvaluo['arrIds'][$i]['q.1.2.n.1'])){
                    $idFoto = 0;
                    $nombreFoto = $indiceCuentaCatastral."_".$cuentaCatastralStr.".jpg";
                    $descripcion = "Foto_".$nombreFoto;
                    $fichero = (String)($arrFotosInmuebleAvaluo['arrElementos'][$i][$arrFotosInmuebleAvaluo['arrIds'][$i]['q.1.2.n.1']]);
                    $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];
                    $idUsuario = $camposFexavaAvaluo['IDPERSONAPERITO'];
                    
                    $idFoto = $this->modelDocumentos->tran_InsertFotoInmueble($fichero, $nombreFoto, $descripcion, $fechaAvaluo, $tipoFoto, $idUsuario);
                    if($elementoPrincipal == "//Comercial"){            
                        $this->fileXML->Comercial->AnexoFotografico->Sujeto->FotosInmuebleAvaluo[$i]->Foto = $idFoto;
                    }

                    if($elementoPrincipal == "//Catastral"){            
                        $this->fileXML->Catastral->AnexoFotografico->Sujeto->FotosInmuebleAvaluo[$i]->Foto = $idFoto;
                    }
                }
                $indiceCuentaCatastral = $indiceCuentaCatastral +1;
                /****Pendiente******/
                //Reemplazamos en el XML del avaluo, la foto por el id obtenido de documental.
                $camposFexavaAvaluo['FEXAVA_FOTOAVALUO'][$i]['IDDOCUMENTOFOTO'] = $idFoto;
                
            } 
            
           
        }

      /****************************************************Comparable Rentas*****************************************/
        if(isset($arrAnexoFotografico['arrIds']['q.2'])){
            $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'] = array();
            $comparableRentas = $xmlAnexoFotografico->xpath($elementoPrincipal.'//AnexoFotografico[@id="q"]//'.$arrAnexoFotografico['arrIds']['q.2'].'[@id="q.2"]');            
            //print_r(convierte_a_arreglo($comparableRentas)); exit();
            $arrComparableRentas = convierte_a_arreglo($comparableRentas);
            //$arrComparableRentas = $this->obtenElementosFotos($comparableRentas);
            
            //$listCuentaCatastral = array();
            $i=0;
            foreach($arrComparableRentas as $idComparableRentas => $valComparableRentas){
                if(isset($valComparableRentas['CuentaCatastral'])){
                    $listCuentaCatastral[] = (String)($valComparableRentas['CuentaCatastral']['Region']);
                    $listCuentaCatastral[] = (String)($valComparableRentas['CuentaCatastral']['Manzana']);
                    $listCuentaCatastral[] = (String)($valComparableRentas['CuentaCatastral']['Lote']);
                    $listCuentaCatastral[] = (String)($valComparableRentas['CuentaCatastral']['Localidad']);
                    $cuentaCatastralStr = '';
                    foreach($listCuentaCatastral as $elementoCuenta){
                        $cuentaCatastralStr .= $elementoCuenta;
                    }
                }

                $indiceCuentaCatastral = $i+1;

                if(isset($valComparableRentas['FotosInmuebleAvaluo'])){
                    if(isset($valComparableRentas['FotosInmuebleAvaluo'][0])){
                        foreach($valComparableRentas['FotosInmuebleAvaluo'] as $idFotosInmueble => $valFotosInmueble){
                            if(isset($valFotosInmueble['Foto'])){
                                $idFoto = 0;
                                $nombreFoto = $indiceCuentaCatastral.$idFotosInmueble."_".".jpg";
                                $descripcion = "Foto_".$nombreFoto;
                                $fichero = (String)($valFotosInmueble['Foto']);
                                $tipoFoto = $this->tipoInmueble((String)($valFotosInmueble['InteriorOExterior'])); //error_log("TIPO_FOTO |".$tipoFoto."|");
                                $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];
                                $idUsuario = $camposFexavaAvaluo['IDPERSONAPERITO'];
                                //error_log($nombreFoto);
                                $idFoto = $this->modelDocumentos->tran_InsertFotoInmueble($fichero, $nombreFoto, $descripcion, $fechaAvaluo, $tipoFoto, $idUsuario);
        
                                if($elementoPrincipal == "//Comercial"){            
                                    $this->fileXML->Comercial->AnexoFotografico->ComparableRentas[$i]->FotosInmuebleAvaluo[$idFotosInmueble]->Foto = $idFoto;
                                }
                                if($elementoPrincipal == "//Catastral"){            
                                    $this->fileXML->Catastral->AnexoFotografico->ComparableRentas[$i]->FotosInmuebleAvaluo[$idFotosInmueble]->Foto = $idFoto;
                                }
                                
                                if(count($listCuentaCatastral) == 4){
                                    $cadenaSelect = "REGION = '".$listCuentaCatastral[0]."' AND MANZANA = '".$listCuentaCatastral[1]."' AND LOTE = '".$listCuentaCatastral[2]."' AND UNIDADPRIVATIVA = '".$listCuentaCatastral[3]."' AND ROWNUM = 1";
                                    $arrInvestProductosCompRow = DB::select("SELECT * FROM FEXAVA_INVESTPRODUCTOSCOMP WHERE ".$cadenaSelect);                                
                                    $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['FEXAVA_INVESTPRODUCTOSCOMP'] = $arrInvestProductosCompRow[0];
                                    $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['IDDOCUMENTOFOTO'] = $idFoto; 
                                    
                                }
                            }
                        }
                    }else{

                        if(isset($valComparableRentas['FotosInmuebleAvaluo']['Foto'])){
                            $idFoto = 0;
                            $nombreFoto = $indiceCuentaCatastral."_".".jpg";
                            $descripcion = "Foto_".$nombreFoto;
                            $fichero = (String)($valComparableRentas['FotosInmuebleAvaluo']['Foto']);
                            $tipoFoto = $this->tipoInmueble((String)($valComparableRentas['FotosInmuebleAvaluo']['InteriorOExterior'])); //error_log("TIPO_FOTO |".$tipoFoto."|");
                            $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];
                            $idUsuario = $camposFexavaAvaluo['IDPERSONAPERITO'];
                            //error_log($nombreFoto);
                            $idFoto = $this->modelDocumentos->tran_InsertFotoInmueble($fichero, $nombreFoto, $descripcion, $fechaAvaluo, $tipoFoto, $idUsuario);
    
                            if($elementoPrincipal == "//Comercial"){            
                                $this->fileXML->Comercial->AnexoFotografico->ComparableRentas[$i]->FotosInmuebleAvaluo->Foto = $idFoto;
                            }
                            if($elementoPrincipal == "//Catastral"){            
                                $this->fileXML->Catastral->AnexoFotografico->ComparableRentas[$i]->FotosInmuebleAvaluo->Foto = $idFoto;
                            }
                            
                            if(count($listCuentaCatastral) == 4){
                                $cadenaSelect = "REGION = '".$listCuentaCatastral[0]."' AND MANZANA = '".$listCuentaCatastral[1]."' AND LOTE = '".$listCuentaCatastral[2]."' AND UNIDADPRIVATIVA = '".$listCuentaCatastral[3]."' AND ROWNUM = 1";
                                $arrInvestProductosCompRow = DB::select("SELECT * FROM FEXAVA_INVESTPRODUCTOSCOMP WHERE ".$cadenaSelect);                                
                                $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['FEXAVA_INVESTPRODUCTOSCOMP'] = $arrInvestProductosCompRow[0];
                                $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['IDDOCUMENTOFOTO'] = $idFoto; 
                                
                            }
                        }

                    }
                    
                }
                $i = $i+1;
            }
            
            /*for($i=0;$i < count($comparableRentas); $i++){
                $listCuentaCatastral = array();
                if(isset($arrComparableRentas['arrIds'][$i]['q.2.n.1'])){
                    //print_r($arrComparableRentas['arrElementos'][$i][$arrComparableRentas['arrIds'][$i]['q.2.n.1']]); exit();
                    $infoCuenta = $arrComparableRentas['arrElementos'][$i][$arrComparableRentas['arrIds'][$i]['q.2.n.1']];
                    $listCuentaCatastral[] = (String)($infoCuenta->Region);
                    $listCuentaCatastral[] = (String)($infoCuenta->Manzana);
                    $listCuentaCatastral[] = (String)($infoCuenta->Lote);
                    $listCuentaCatastral[] = (String)($infoCuenta->Localidad);
                    $cuentaCatastralStr = '';
                    foreach($listCuentaCatastral as $elementoCuenta){
                        $cuentaCatastralStr .= $elementoCuenta;
                    }                
                    
                }                
                $indiceCuentaCatastral = $i+1;

                if(isset($arrComparableRentas['arrIds'][$i]['q.2.n.2'])){                    
                        if(isset($arrComparableRentas['arrElementos'][$i]['FotosInmuebleAvaluo']->Foto)){
                            $idFoto = 0;
                            $nombreFoto = $indiceCuentaCatastral."_".".jpg";
                            $descripcion = "Foto_".$nombreFoto;
                            $fichero = (String)($arrComparableRentas['arrElementos'][$i]['FotosInmuebleAvaluo']->Foto);
                            $tipoFoto = $this->tipoInmueble((String)($arrComparableRentas['arrElementos'][$i]['FotosInmuebleAvaluo']->InteriorOExterior)); //error_log("TIPO_FOTO |".$tipoFoto."|");
                            $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];
                            $idUsuario = $camposFexavaAvaluo['IDPERSONAPERITO'];
                            //error_log($nombreFoto);
                            $idFoto = $this->modelDocumentos->tran_InsertFotoInmueble($fichero, $nombreFoto, $descripcion, $fechaAvaluo, $tipoFoto, $idUsuario);
    
                            if($elementoPrincipal == "//Comercial"){            
                                $this->fileXML->Comercial->AnexoFotografico->ComparableRentas[$i]->FotosInmuebleAvaluo->Foto = $idFoto;
                            }
                            if($elementoPrincipal == "//Catastral"){            
                                $this->fileXML->Catastral->AnexoFotografico->ComparableRentas[$i]->FotosInmuebleAvaluo->Foto = $idFoto;
                            }
    
                            if(count($listCuentaCatastral) == 4){
                                $cadenaSelect = "REGION = '".$listCuentaCatastral[0]."' AND MANZANA = '".$listCuentaCatastral[1]."' AND LOTE = '".$listCuentaCatastral[2]."' AND UNIDADPRIVATIVA = '".$listCuentaCatastral[3]."' AND ROWNUM = 1";
                                $arrInvestProductosCompRow = DB::select("SELECT * FROM FEXAVA_INVESTPRODUCTOSCOMP WHERE ".$cadenaSelect);                                
                                $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['FEXAVA_INVESTPRODUCTOSCOMP'] = $arrInvestProductosCompRow[0];
                                $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['IDDOCUMENTOFOTO'] = $idFoto;
                                
                                
                            }
                        }                                                             
                    
                }  

            }*/
            
        
        }

        /****************************************************Comparable Ventas*****************************************/

        if(isset($arrAnexoFotografico['arrIds']['q.3'])){
            $comparableVentas = $xmlAnexoFotografico->xpath($elementoPrincipal.'//AnexoFotografico[@id="q"]//'.$arrAnexoFotografico['arrIds']['q.3'].'[@id="q.3"]');            
            //$arrComparableVentas = $this->obtenElementos($comparableVentas);
            $arrComparableVentas = convierte_a_arreglo($comparableVentas);
            //$listCuentaCatastral = array();

            $i=0;
            foreach($arrComparableVentas as $idComparableVentas => $valComparableVentas){
                if(isset($valComparableVentas['CuentaCatastral'])){
                    $listCuentaCatastral[] = (String)($valComparableVentas['CuentaCatastral']['Region']);
                    $listCuentaCatastral[] = (String)($valComparableVentas['CuentaCatastral']['Manzana']);
                    $listCuentaCatastral[] = (String)($valComparableVentas['CuentaCatastral']['Lote']);
                    $listCuentaCatastral[] = (String)($valComparableVentas['CuentaCatastral']['Localidad']);
                    $cuentaCatastralStr = '';
                    foreach($listCuentaCatastral as $elementoCuenta){
                        $cuentaCatastralStr .= $elementoCuenta;
                    }
                }

                $indiceCuentaCatastral = $i+1;

                if(isset($valComparableVentas['FotosInmuebleAvaluo'])){
                    if(isset($valComparableVentas['FotosInmuebleAvaluo'][0])){
                        foreach($valComparableVentas['FotosInmuebleAvaluo'] as $idFotosInmueble => $valFotosInmueble){
                            if(isset($valFotosInmueble['Foto'])){
                                $idFoto = 0;
                                $nombreFoto = $indiceCuentaCatastral.$idFotosInmueble."_".".jpg";
                                $descripcion = "Foto_".$nombreFoto;
                                $fichero = (String)($valFotosInmueble['Foto']);
                                $tipoFoto = $this->tipoInmueble((String)($valFotosInmueble['InteriorOExterior'])); //error_log("TIPO_FOTO |".$tipoFoto."|");
                                $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];
                                $idUsuario = $camposFexavaAvaluo['IDPERSONAPERITO'];
                                //error_log($nombreFoto);
                                $idFoto = $this->modelDocumentos->tran_InsertFotoInmueble($fichero, $nombreFoto, $descripcion, $fechaAvaluo, $tipoFoto, $idUsuario);
        
                                if($elementoPrincipal == "//Comercial"){            
                                    $this->fileXML->Comercial->AnexoFotografico->ComparableVentas[$i]->FotosInmuebleAvaluo[$idFotosInmueble]->Foto = $idFoto;
                                }
                                if($elementoPrincipal == "//Catastral"){            
                                    $this->fileXML->Catastral->AnexoFotografico->ComparableVentas[$i]->FotosInmuebleAvaluo[$idFotosInmueble]->Foto = $idFoto;
                                }
                                
                                if(count($listCuentaCatastral) == 4){
                                    $cadenaSelect = "REGION = '".$listCuentaCatastral[0]."' AND MANZANA = '".$listCuentaCatastral[1]."' AND LOTE = '".$listCuentaCatastral[2]."' AND UNIDADPRIVATIVA = '".$listCuentaCatastral[3]."' AND ROWNUM = 1";
                                    $arrInvestProductosCompRow = DB::select("SELECT * FROM FEXAVA_INVESTPRODUCTOSCOMP WHERE ".$cadenaSelect);                                
                                    $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['FEXAVA_INVESTPRODUCTOSCOMP'] = $arrInvestProductosCompRow[0];
                                    $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['IDDOCUMENTOFOTO'] = $idFoto; 
                                    
                                }
                            }
                        }
                    }else{

                        if(isset($valComparableVentas['FotosInmuebleAvaluo']['Foto'])){
                            $idFoto = 0;
                            $nombreFoto = $indiceCuentaCatastral."_".".jpg";
                            $descripcion = "Foto_".$nombreFoto;
                            $fichero = (String)($valComparableVentas['FotosInmuebleAvaluo']['Foto']);
                            $tipoFoto = $this->tipoInmueble((String)($valComparableVentas['FotosInmuebleAvaluo']['InteriorOExterior'])); //error_log("TIPO_FOTO |".$tipoFoto."|");
                            $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];
                            $idUsuario = $camposFexavaAvaluo['IDPERSONAPERITO'];
                            //error_log($nombreFoto);
                            $idFoto = $this->modelDocumentos->tran_InsertFotoInmueble($fichero, $nombreFoto, $descripcion, $fechaAvaluo, $tipoFoto, $idUsuario);
    
                            if($elementoPrincipal == "//Comercial"){            
                                $this->fileXML->Comercial->AnexoFotografico->ComparableVentas[$i]->FotosInmuebleAvaluo->Foto = $idFoto;
                            }
                            if($elementoPrincipal == "//Catastral"){            
                                $this->fileXML->Catastral->AnexoFotografico->ComparableVentas[$i]->FotosInmuebleAvaluo->Foto = $idFoto;
                            }
                            
                            if(count($listCuentaCatastral) == 4){
                                $cadenaSelect = "REGION = '".$listCuentaCatastral[0]."' AND MANZANA = '".$listCuentaCatastral[1]."' AND LOTE = '".$listCuentaCatastral[2]."' AND UNIDADPRIVATIVA = '".$listCuentaCatastral[3]."' AND ROWNUM = 1";
                                $arrInvestProductosCompRow = DB::select("SELECT * FROM FEXAVA_INVESTPRODUCTOSCOMP WHERE ".$cadenaSelect);                                
                                $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['FEXAVA_INVESTPRODUCTOSCOMP'] = $arrInvestProductosCompRow[0];
                                $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['IDDOCUMENTOFOTO'] = $idFoto; 
                                
                            }
                        }

                    }
                    
                }
                $i = $i+1;
            }

            /*for($i=0;$i < count($comparableVentas); $i++){
                $listCuentaCatastral = array();
                if(isset($arrComparableVentas['arrIds'][$i]['q.3.n.1'])){
                    
                    $infoCuenta = $arrComparableVentas['arrElementos'][$i][$arrComparableVentas['arrIds'][$i]['q.3.n.1']];
                    $listCuentaCatastral[] = (String)($infoCuenta->Region);
                    $listCuentaCatastral[] = (String)($infoCuenta->Manzana);
                    $listCuentaCatastral[] = (String)($infoCuenta->Lote);
                    $listCuentaCatastral[] = (String)($infoCuenta->Localidad);
                    $cuentaCatastralStr = '';
                    foreach($listCuentaCatastral as $elementoCuenta){
                        $cuentaCatastralStr .= $elementoCuenta;
                    }
                    
                    $indiceCuentaCatastral = $i+1;
                    
                    if(isset($arrComparableVentas['arrIds'][$i]['q.3.n.2'])){
                        if(isset($arrComparableVentas['arrElementos'][$i]['FotosInmuebleAvaluo']->Foto)){
                            $idFoto = 0;
                            $nombreFoto = $indiceCuentaCatastral."_".$cuentaCatastralStr.".jpg";
                            $descripcion = "Foto_".$nombreFoto;
                            $fichero = (String)($arrComparableVentas['arrElementos'][$i]['FotosInmuebleAvaluo']->Foto);
                            $tipoFoto = $this->tipoInmueble((String)($arrComparableVentas['arrElementos'][$i]['FotosInmuebleAvaluo']->InteriorOExterior));
                            $fechaAvaluo = $camposFexavaAvaluo['FECHAAVALUO'];
                            $idUsuario = $camposFexavaAvaluo['IDPERSONAPERITO'];
                            //error_log($nombreFoto);   
                            $idFoto = $this->modelDocumentos->tran_InsertFotoInmueble($fichero, $nombreFoto, $descripcion, $fechaAvaluo, $tipoFoto, $idUsuario);
    
                            if($elementoPrincipal == "//Comercial"){            
                                $this->fileXML->Comercial->AnexoFotografico->ComparableVentas[$i]->FotosInmuebleAvaluo->Foto = $idFoto;
                            }
                            if($elementoPrincipal == "//Catastral"){            
                                $this->fileXML->Catastral->AnexoFotografico->ComparableVentas[$i]->FotosInmuebleAvaluo->Foto = $idFoto;
                            }
    
                            if(count($listCuentaCatastral) == 4){
                                $cadenaSelect = "REGION = '".$listCuentaCatastral[0]."' AND MANZANA = '".$listCuentaCatastral[1]."' AND LOTE = '".$listCuentaCatastral[2]."' AND UNIDADPRIVATIVA = '".$listCuentaCatastral[3]."' AND ROWNUM = 1";
                                //error_log("SELECT * FROM FEXAVA_INVESTPRODUCTOSCOMP WHERE ".$cadenaSelect);
                                $arrInvestProductosCompRow = DB::select("SELECT * FROM FEXAVA_INVESTPRODUCTOSCOMP WHERE ".$cadenaSelect);                                
                                $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['FEXAVA_INVESTPRODUCTOSCOMP'] = $arrInvestProductosCompRow[0];
                                $camposFexavaAvaluo['FEXAVA_FOTOCOMPARABLE'][$i]['IDDOCUMENTOFOTO'] = $idFoto;
                                
                                
                            }
                        }    
                    }
                }
            }*/
        }
        //print_r($this->fileXML); exit();
        return $camposFexavaAvaluo;
    } catch (\Throwable $th) {
        Log::info($th);
        error_log($th);
        return response()->json(['mensaje' => '39. Error en el servidor'], 500);
    }
    }
    
    public function obtenElementosPrincipal($arrPrincipal){
        $arrIds = array();
        $arrElementos = array();
        $arrRes = array();

            foreach($arrPrincipal[0] as $llave => $elemento){
                $arrIds[(String)($elemento['id'])] = $llave;
                $arrElementos[$llave] = $elemento;
            }
            $arrRes['arrIds'] = $arrIds;
            $arrRes['arrElementos'] = $arrElementos;        
        
        return $arrRes;
    }

    public function obtenElementos($arrPrincipal){
        $arrRes = array();

            for($i=0;$i<count($arrPrincipal);$i++){
                $arrElementos = array();
                $arrIds = array();
                foreach($arrPrincipal[$i] as $llave => $elemento){
                    $arrIds[(String)($elemento['id'])] = $llave;
                    $arrElementos[$llave] = $elemento;                    
                }

                $arrRes['arrIds'][$i] = $arrIds;
                $arrRes['arrElementos'][$i] = $arrElementos;               
            }    
        
        return $arrRes;
    }

    public function obtenElementosFotos($arrPrincipal){
        $arrRes = array();

            for($i=0;$i<count($arrPrincipal);$i++){
                $arrElementos = array();
                $arrIds = array();
                foreach($arrPrincipal[$i] as $llave => $elemento){
                    $arrIds[(String)($elemento['id'])] = $llave;
                    $arrElementos[$llave] = $elemento;                    
                }

                $arrRes['arrIds'][$i] = $arrIds;
                $arrRes['arrElementos'][$i] = $arrElementos;               
            }    
        
        return $arrRes;
    }

    public function obtenerNumUnicoAv($tipo){
        $anio = date('Y');
        return "A-".$tipo."-".$anio."-";
    }

    public function esTerrenoValdio($data, $elementoPrincipal){
        try{
        $resultado = FALSE;
        $general = $data->xpath($elementoPrincipal);        
        $arrGeneral = $this->obtenElementosPrincipal($general);
        
        if(isset($arrGeneral['arrIds']['e'])){
            $descripcionDelInmueble = $data->xpath($elementoPrincipal.'//'.$arrGeneral['arrIds']['e'].'[@id="e"]//TiposDeConstruccion[@id="e.2"]');
            $arrDescripcionDelInmueble = $this->obtenElementos($descripcionDelInmueble);
            
            if(isset($arrDescripcionDelInmueble['arrIds'][0]['e.2.1']) && count($arrDescripcionDelInmueble['arrElementos'][0][$arrDescripcionDelInmueble['arrIds'][0]['e.2.1']]) > 0){
                $tienePrivativas = TRUE;
            }

            if(isset($arrDescripcionDelInmueble['arrIds'][0]['e.2.5']) && count($arrDescripcionDelInmueble['arrElementos'][0][$arrDescripcionDelInmueble['arrIds'][0]['e.2.5']]) > 0){
                $tieneComunes = TRUE;                     
            
            }

            if(!isset($tienePrivativas) && !isset($tieneComunes)){
                return TRUE;
            }else{
                return FALSE;
            }

        }else{
            return $resultado;
        }
    }catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '40. Error en el servidor'], 500);
        }
        
    }

    public function tipoInmueble($data){
        switch ($data) {
            case 'I':
                return 'I';
                break;
            
            default:
                return 'F';
                break;
        }
    }

    public function acuseAvaluo(Request $request){
        try{
            $numero_unico = trim($request->query('no_unico'));
            $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);            
            $this->modelReimpresion = new ReimpresionNuevo();
            $infoAcuse = $this->modelReimpresion->infoAcuse($id_avaluo);
            $token_infoAcuse = Crypt::encrypt($infoAcuse); 
            return response()->json([$infoAcuse, $token_infoAcuse], 200);
        }catch (\Throwable $th) {
            //Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '41. Error en el servidor'], 500);
        }    
    }

    public function infoAvaluo(Request $request){
        try{

            $numero_unico = trim($request->query('no_unico'));
            $format = trim($request->query('formato'));

            $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);
            
            $fechaAvaluo = DB::select("SELECT to_char(FECHA,'YYYY-MM-DD') as FECHA_AVALUO FROM DOC.DOC_DOCUMENTODIGITAL WHERE IDDOCUMENTODIGITAL = '".$id_avaluo."'");
            $arr_fechaAvaluo = convierte_a_arreglo($fechaAvaluo);
            $dataFechaAvaluo = $arr_fechaAvaluo[0]['fecha_avaluo'];
            $fechaAvaluoCompara = new Carbon($dataFechaAvaluo);

            /*$fechaPresentacion = DB::select("SELECT to_char(FECHA_PRESENTACION,'YYYY-MM-DD') as FECHA_PRESENTACION FROM FEXAVA_AVALUO WHERE NUMEROUNICO = '".$numero_unico."'");
            $arr_fechaPresentacion = convierte_a_arreglo($fechaPresentacion);
            $dataFechaPresentacion = $arr_fechaPresentacion[0]['fecha_presentacion'];
            $fechaAvaluoCompara = new Carbon($dataFechaPresentacion);*/

            //$fechaCompara = new Carbon('2021-02-28');
            $anioCompara = Carbon::parse($fechaAvaluoCompara)->format('Y'); //echo "SOY AÑO COMPARA ".$anioCompara; exit();

            $contenido = $this->obtenXMLSV($numero_unico);

            $xml = simplexml_load_string($contenido,'SimpleXMLElement', LIBXML_NOCDATA);
            $arrXML = convierte_a_arreglo($xml);

            if(isset($arrXML['Comercial'])){
                $elementoPrincipal = $arrXML['Comercial'];
                $tipoDeAvaluo =  "Comercial";
            }
    
            if(isset($arrXML['Catastral'])){
                $elementoPrincipal = $arrXML['Catastral'];
                $tipoDeAvaluo =  "Catastral";
            }

            if(isset($elementoPrincipal['Antecedentes']['Solicitante']['Alcaldia'])){
                $tieneAlcaldia = true;
                //echo "SOY ALCALDIA ".$elementoPrincipal['Antecedentes']['Solicitante']['Alcaldia'];
            }
            if(isset($elementoPrincipal['Antecedentes']['Solicitante']['Delegacion'])){
                $tieneDelegacion = true;
                //echo "SOY DELEGACION ".$elementoPrincipal['Antecedentes']['Solicitante']['Delegacion'];
            }            
            

            /*$nuevo = 1; //var_dump($fechaPresentacionCompara->lt($fechaCompara)); exit();
            if($fechaAvaluoCompara->lte($fechaCompara)){
                $nuevo = 0;
            }*/
            if($anioCompara == 2021 && isset($tieneAlcaldia) && $tieneAlcaldia == true){
                $nuevo = 1;
            }else{
                $nuevo = 0;
            }

            //echo "SOY NUEVO ".$nuevo; exit();
            if($nuevo == 0){                
                   
                $this->modelReimpresionNuevo = new ReimpresionNuevo();
                $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluo($id_avaluo);
                if(!is_array($infoAvaluo)){
                    return $infoAvaluo;
                }

                $tipo_avaluo = substr($infoAvaluo['Encabezado']['No_Unico'], 0, 5);

                if($tipo_avaluo == 'A-CAT'){
                    $formato = view('justificante', compact("infoAvaluo"))->render();
                }else{
                    $formato = view('justificante_com', compact("infoAvaluo"))->render();
                }
                
                $pdf = PDF::loadHTML($formato);
                $pdf->setOptions(['chroot' => 'public']);
                Storage::put('formato.pdf', $pdf->output());
                if($format == 'PDF'){
                    return response()->json(['pdfbase64' => base64_encode(Storage::get('formato.pdf')), 'nombre' =>  $numero_unico . '.pdf'], 200);
                } else {
                    shell_exec('rm '. storage_path('app/*.doc'));
                    $fileName = 'formato';
                    $pathpdf = storage_path('app/' . $fileName . '.pdf');
                    $pathdoc = storage_path('app/' . $fileName . '.doc');
                    file_put_contents($pathpdf, $pdf->output());
                    shell_exec('libreoffice --headless --infilter="writer_pdf_import" --convert-to doc  --outdir '. storage_path('app/') . ' ' . $pathpdf);
                    shell_exec('rm ' . $pathpdf);
                    //Log::info('python3 pdf2doc.py '.storage_path('app/formato.pdf').' '.storage_path('app/formato.docx'));
                    //shell_exec('rm '. storage_path('app/*.docx'));
                    //shell_exec('python3 pdf2doc.py '.storage_path('app/formato.pdf').' '.'formato.docx');
                    //shell_exec('pdf2docx convert '.storage_path('app/formato.pdf').' '.storage_path('app/formato.doc'));
                    return response()->json(['docxbase64' => base64_encode($pathdoc), 'nombre' =>  $numero_unico . '.docx'], 200);
                }     
            /*$this->modelReimpresion = new ReimpresionNuevo();
            $infoAvaluo = $this->modelReimpresion->infoAvaluo($id_avaluo);
            print_r($infoAvaluo); exit();*/
                
            }else{
                $numero_unico = trim($request->query('no_unico'));

                    
                $this->modelReimpresionNuevo = new ReimpresionNuevo();
                $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluoNuevo($id_avaluo);                
                if(!is_array($infoAvaluo)){
                    return $infoAvaluo;
                }                
                $tipo_avaluo = substr($infoAvaluo['Encabezado']['No_Unico'], 0, 5);
               
                if($tipo_avaluo == 'A-CAT'){
                    $formato = view('justificanteNew', compact("infoAvaluo"))->render();
                }else{
                    //Log::info(json_encode($infoAvaluo));                    
                    $formato = view('justificanteNew_com', compact("infoAvaluo"))->render();
                    Log::info(json_encode("ABAJO DE VIEW"));
                }

                $pdf = PDF::loadHTML($formato);
                $pdf->setOptions(['chroot' => 'public']);
                Storage::put('formato.pdf', $pdf->output());
                if($format == 'PDF'){
                    return response()->json(['pdfbase64' => base64_encode(Storage::get('formato.pdf')), 'nombre' =>  $numero_unico . '.pdf'], 200);
                } else {
                    shell_exec('rm '. storage_path('app/*.doc'));
                    $fileName = 'formato';
                    $pathpdf = storage_path('app/' . $fileName . '.pdf');
                    $pathdoc = storage_path('app/' . $fileName . '.doc');
                    file_put_contents($pathpdf, $pdf->output());
                    shell_exec('libreoffice --headless --infilter="writer_pdf_import" --convert-to doc  --outdir '. storage_path('app/') . ' ' . $pathpdf);
                    shell_exec('rm ' . $pathpdf);
                    //Log::info('python3 pdf2doc.py '.storage_path('app/formato.pdf').' '.storage_path('app/formato.docx'));
                    //shell_exec('rm '. storage_path('app/*.docx'));
                    //shell_exec('python3 pdf2doc.py '.storage_path('app/formato.pdf').' '.'formato.docx');
                    return response()->json(['docxbase64' => base64_encode($pathdoc), 'nombre' =>  $numero_unico . '.docx'], 200);
                } 
                /*$this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);    
            $this->modelReimpresionNuevo = new ReimpresionNuevo();
            $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluoNuevo($id_avaluo);
            print_r($infoAvaluo); exit();*/
            }
            
                        
        }catch (\Throwable $th) {
            //Log::info("n° unico: ".$numero_unico."\nerror: ".$th->getMessage()."\narchivo: ".$th->getFile()."\nlinea: ".$th->getLine());
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '42. Error al obtener la información del avalúo'], 500);
        }    
    }

    /*public function infoAvaluo(Request $request){
        try{
            $numero_unico = trim($request->query('no_unico'));

            $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);    
            $this->modelReimpresionNuevo = new ReimpresionNuevo();
            $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluo($id_avaluo);
            if(!is_array($infoAvaluo)){
                return $infoAvaluo;
            }
            // $datosPDF = [];
            // $datosPDF['no_unico'] =  $numero_unico;
            $tipo_avaluo = substr($infoAvaluo['Encabezado']['No_Unico'], 0, 5);
            if($tipo_avaluo == 'A-CAT'){
                $formato = view('justificante', compact("infoAvaluo"))->render();
            }else{
                $formato = view('justificante_com', compact("infoAvaluo"))->render();
            }
            $pdf = PDF::loadHTML($formato);
            $pdf->setOptions(['chroot' => 'public']);
            Storage::put('formato.pdf', $pdf->output());
            return response()->json(['pdfbase64' => base64_encode(Storage::get('formato.pdf')), 'nombre' =>  $numero_unico . '.pdf'], 200);
            
            //print_r($infoAvaluo);

            //$this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            //$id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);    
            //$this->modelReimpresion = new ReimpresionNuevo();
            //$infoAvaluo = $this->modelReimpresion->infoAvaluo($id_avaluo);
            //print_r($infoAvaluo); exit(); 
            //return response()->json($infoAvaluo, 200);
        }catch (\Throwable $th) {
            Log::info("n° unico: ".$numero_unico."\nerror: ".$th->getMessage()."\narchivo: ".$th->getFile()."\nlinea: ".$th->getLine());
            error_log($th);
            return response()->json(['mensaje' => 'Error al obtener la información del avalúo'], 500);
        }    
    }*/

    /*public function infoAvaluoNuevo(Request $request){
        try{
            $numero_unico = trim($request->query('no_unico'));

            $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);    
            $this->modelReimpresionNuevo = new ReimpresionNuevo();
            $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluoNuevo($id_avaluo);
            if(!is_array($infoAvaluo)){
                return $infoAvaluo;
            }
            // $datosPDF = [];
            // $datosPDF['no_unico'] =  $numero_unico;
            $tipo_avaluo = substr($infoAvaluo['Encabezado']['No_Unico'], 0, 5);
            if($tipo_avaluo == 'A-CAT'){
                $formato = view('justificanteNew', compact("infoAvaluo"))->render();
            }else{
                $formato = view('justificanteNew_com', compact("infoAvaluo"))->render();
            }
            $pdf = PDF::loadHTML($formato);
            $pdf->setOptions(['chroot' => 'public']);
            Storage::put('formato.pdf', $pdf->output());
            return response()->json(['pdfbase64' => base64_encode(Storage::get('formato.pdf')), 'nombre' =>  $numero_unico . '.pdf'], 200);
            
            //print_r($infoAvaluo);

            //$this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            //$id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);    
            //$this->modelReimpresion = new ReimpresionNuevo();
            //$infoAvaluo = $this->modelReimpresion->infoAvaluoNuevo($id_avaluo);
            //print_r($infoAvaluo); exit();
            //return response()->json($infoAvaluo, 200);
        }catch (\Throwable $th) {
            Log::info("n° unico: ".$numero_unico."\nerror: ".$th->getMessage()."\narchivo: ".$th->getFile()."\nlinea: ".$th->getLine());
            error_log($th);
            return response()->json(['mensaje' => 'Error al obtener la información del avalúo'], 500);
        }    
    }*/

    public function reimprimeSV(Request $request){
        try{
            
            $idAvaluo = $request->query('IdAvaluo');            
            if(isset($idAvaluo)){
                Log::info($idAvaluo);
                $id_avaluo = trim($idAvaluo);
                $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
                $numero_unico = $this->modelDocumentos->get_numero_unico_db($id_avaluo);
            }else{
                $numero_unico = trim($request->query('no_unico'));      

                $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
                $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);
                
                
            }
           
            
            $fechaAvaluo = DB::select("SELECT to_char(FECHA,'YYYY-MM-DD') as FECHA_AVALUO FROM DOC.DOC_DOCUMENTODIGITAL WHERE IDDOCUMENTODIGITAL = '".$id_avaluo."'");
            $arr_fechaAvaluo = convierte_a_arreglo($fechaAvaluo);
            $dataFechaAvaluo = $arr_fechaAvaluo[0]['fecha_avaluo'];
            $fechaAvaluoCompara = new Carbon($dataFechaAvaluo);

            /*$fechaPresentacion = DB::select("SELECT to_char(FECHA_PRESENTACION,'YYYY-MM-DD') as FECHA_PRESENTACION FROM FEXAVA_AVALUO WHERE NUMEROUNICO = '".$numero_unico."'");
            $arr_fechaPresentacion = convierte_a_arreglo($fechaPresentacion);
            $dataFechaPresentacion = $arr_fechaPresentacion[0]['fecha_presentacion'];
            $fechaAvaluoCompara = new Carbon($dataFechaPresentacion);*/

            //$fechaCompara = new Carbon('2021-02-28');
            $anioCompara = Carbon::parse($fechaAvaluoCompara)->format('Y'); //echo "SOY AÑO COMPARA ".$anioCompara; exit();

            $contenido = $this->obtenXMLSV($numero_unico);

            $xml = simplexml_load_string($contenido,'SimpleXMLElement', LIBXML_NOCDATA);
            $arrXML = convierte_a_arreglo($xml);

            if(isset($arrXML['Comercial'])){
                $elementoPrincipal = $arrXML['Comercial'];
                $tipoDeAvaluo =  "Comercial";
            }
    
            if(isset($arrXML['Catastral'])){
                $elementoPrincipal = $arrXML['Catastral'];
                $tipoDeAvaluo =  "Catastral";
            }

            if(isset($elementoPrincipal['Antecedentes']['Solicitante']['Alcaldia'])){
                $tieneAlcaldia = true;
                //echo "SOY ALCALDIA ".$elementoPrincipal['Antecedentes']['Solicitante']['Alcaldia'];
            }
            if(isset($elementoPrincipal['Antecedentes']['Solicitante']['Delegacion'])){
                $tieneDelegacion = true;
                //echo "SOY DELEGACION ".$elementoPrincipal['Antecedentes']['Solicitante']['Delegacion'];
            }            
            

            /*$nuevo = 1; //var_dump($fechaPresentacionCompara->lt($fechaCompara)); exit();
            if($fechaAvaluoCompara->lte($fechaCompara)){
                $nuevo = 0;
            }*/
            if($anioCompara == 2021 && isset($tieneAlcaldia) && $tieneAlcaldia == true){
                $nuevo = 1;
            }else{
                $nuevo = 0;
            }

            //echo "SOY NUEVO ".$nuevo; exit();
            if($nuevo == 0){                
                   
                $this->modelReimpresionNuevo = new ReimpresionNuevo();
                $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluo($id_avaluo);
                if(!is_array($infoAvaluo)){
                    return $infoAvaluo;
                }

                $tipo_avaluo = substr($infoAvaluo['Encabezado']['No_Unico'], 0, 5);
                if($tipo_avaluo == 'A-CAT'){
                    $formato = view('justificante', compact("infoAvaluo"))->render();
                }else{
                    $formato = view('justificante_com', compact("infoAvaluo"))->render();
                }
                $pdf = PDF::loadHTML($formato);
                $pdf->setOptions(['chroot' => 'public']);
                
                return $pdf->stream('formato.pdf');
                    
            /*$this->modelReimpresion = new ReimpresionNuevo();
            $infoAvaluo = $this->modelReimpresion->infoAvaluo($id_avaluo);
            print_r($infoAvaluo); exit();*/
                
            }else{
                $numero_unico = trim($request->query('no_unico'));

                    
                $this->modelReimpresionNuevo = new ReimpresionNuevo();
                $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluoNuevo($id_avaluo);                
                if(!is_array($infoAvaluo)){
                    return $infoAvaluo;
                }                
                $tipo_avaluo = substr($infoAvaluo['Encabezado']['No_Unico'], 0, 5);
                if($tipo_avaluo == 'A-CAT'){
                    $formato = view('justificanteNew', compact("infoAvaluo"))->render();
                }else{
                    //Log::info(json_encode($infoAvaluo));                    
                    $formato = view('justificanteNew_com', compact("infoAvaluo"))->render();
                    Log::info(json_encode("ABAJO DE VIEW"));
                }
                $pdf = PDF::loadHTML($formato);
                $pdf->setOptions(['chroot' => 'public']);

                return $pdf->stream('formato.pdf');
                
                /*$this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);    
            $this->modelReimpresionNuevo = new ReimpresionNuevo();
            $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluoNuevo($id_avaluo);
            print_r($infoAvaluo); exit();*/
            }
            
                        
        }catch (\Throwable $th) {
            //Log::info("n° unico: ".$numero_unico."\nerror: ".$th->getMessage()."\narchivo: ".$th->getFile()."\nlinea: ".$th->getLine());
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '43. Error al obtener la información del avalúo'], 500);
        }    
    }

    public function reimprimeSVPost(Request $request){
        try{

            $idAvaluo = $request->input('idAvaluo');
            if(isset($idAvaluo)){
                Log::info($idAvaluo);
                $id_avaluo = trim($idAvaluo);
                $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
                $numero_unico = $this->modelDocumentos->get_numero_unico_db($id_avaluo);
            }else{
                $numero_unico = trim($request->input('no_unico'));      

                $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
                $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);
                
                
            }
            //$numero_unico = trim($request->input('no_unico'));
          

            $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);
            
            $fechaAvaluo = DB::select("SELECT to_char(FECHA,'YYYY-MM-DD') as FECHA_AVALUO FROM DOC.DOC_DOCUMENTODIGITAL WHERE IDDOCUMENTODIGITAL = '".$id_avaluo."'");
            $arr_fechaAvaluo = convierte_a_arreglo($fechaAvaluo);
            $dataFechaAvaluo = $arr_fechaAvaluo[0]['fecha_avaluo'];
            $fechaAvaluoCompara = new Carbon($dataFechaAvaluo);

            /*$fechaPresentacion = DB::select("SELECT to_char(FECHA_PRESENTACION,'YYYY-MM-DD') as FECHA_PRESENTACION FROM FEXAVA_AVALUO WHERE NUMEROUNICO = '".$numero_unico."'");
            $arr_fechaPresentacion = convierte_a_arreglo($fechaPresentacion);
            $dataFechaPresentacion = $arr_fechaPresentacion[0]['fecha_presentacion'];
            $fechaAvaluoCompara = new Carbon($dataFechaPresentacion);*/

            /*$fechaCompara = new Carbon('2021-02-28');

            $contenido = $this->obtenXMLSV($numero_unico);

            $nuevo = 1; //var_dump($fechaPresentacionCompara->lt($fechaCompara)); exit();
            if($fechaAvaluoCompara->lte($fechaCompara)){
                $nuevo = 0;
            }*/

            $anioCompara = Carbon::parse($fechaAvaluoCompara)->format('Y'); //echo "SOY AÑO COMPARA ".$anioCompara; exit();

            $contenido = $this->obtenXMLSV($numero_unico);

            $xml = simplexml_load_string($contenido,'SimpleXMLElement', LIBXML_NOCDATA);
            $arrXML = convierte_a_arreglo($xml);

            if(isset($arrXML['Comercial'])){
                $elementoPrincipal = $arrXML['Comercial'];
                $tipoDeAvaluo =  "Comercial";
            }
    
            if(isset($arrXML['Catastral'])){
                $elementoPrincipal = $arrXML['Catastral'];
                $tipoDeAvaluo =  "Catastral";
            }

            if(isset($elementoPrincipal['Antecedentes']['Solicitante']['Alcaldia'])){
                $tieneAlcaldia = true;
                //echo "SOY ALCALDIA ".$elementoPrincipal['Antecedentes']['Solicitante']['Alcaldia'];
            }
            if(isset($elementoPrincipal['Antecedentes']['Solicitante']['Delegacion'])){
                $tieneDelegacion = true;
                //echo "SOY DELEGACION ".$elementoPrincipal['Antecedentes']['Solicitante']['Delegacion'];
            }            
            

            /*$nuevo = 1; //var_dump($fechaPresentacionCompara->lt($fechaCompara)); exit();
            if($fechaAvaluoCompara->lte($fechaCompara)){
                $nuevo = 0;
            }*/
            if($anioCompara == 2021 && $tieneAlcaldia == true){
                $nuevo = 1;
            }else{
                $nuevo = 0;
            }

            //echo "SOY NUEVO ".$nuevo; exit();
            if($nuevo == 0){             
                   
                $this->modelReimpresionNuevo = new ReimpresionNuevo();
                $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluo($id_avaluo);
                if(!is_array($infoAvaluo)){
                    return $infoAvaluo;
                }

                $tipo_avaluo = substr($infoAvaluo['Encabezado']['No_Unico'], 0, 5);
                if($tipo_avaluo == 'A-CAT'){
                    $formato = view('justificante', compact("infoAvaluo"))->render();
                }else{
                    $formato = view('justificante_com', compact("infoAvaluo"))->render();
                }
                $pdf = PDF::loadHTML($formato);
                $pdf->setOptions(['chroot' => 'public']);

                //return $pdf->stream('formato.pdf');
                return $pdf->download('formato.pdf');
                    
            /*$this->modelReimpresion = new ReimpresionNuevo();
            $infoAvaluo = $this->modelReimpresion->infoAvaluo($id_avaluo);
            print_r($infoAvaluo); exit();*/
                
            }else{
                $numero_unico = trim($request->query('no_unico'));

                    
                $this->modelReimpresionNuevo = new ReimpresionNuevo();
                $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluoNuevo($id_avaluo);
                if(!is_array($infoAvaluo)){
                    return $infoAvaluo;
                }                
                $tipo_avaluo = substr($infoAvaluo['Encabezado']['No_Unico'], 0, 5);
                if($tipo_avaluo == 'A-CAT'){
                    $formato = view('justificanteNew', compact("infoAvaluo"))->render();
                }else{
                    $formato = view('justificanteNew_com', compact("infoAvaluo"))->render();
                }
                $pdf = PDF::loadHTML($formato);
                $pdf->setOptions(['chroot' => 'public']);

                //return $pdf->stream('formato.pdf');
                return $pdf->download('formato.pdf');

                /*$this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);    
            $this->modelReimpresionNuevo = new ReimpresionNuevo();
            $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluoNuevo($id_avaluo);
            print_r($infoAvaluo); exit();*/
            }
            
                        
        }catch (\Throwable $th) {
            //Log::info("n° unico: ".$numero_unico."\nerror: ".$th->getMessage()."\narchivo: ".$th->getFile()."\nlinea: ".$th->getLine());
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '44. Error al obtener la información del avalúo'], 500);
        }    
    }

    public function obtenXMLSV($numero_unico){
        //$numero_unico = trim($request->input('no_unico'));
        
        $this->modelDocumentos = new Documentos();            
        $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);
        $this->modelReimpresionNuevo = new ReimpresionNuevo();
        $infoAvaluo = $this->modelReimpresionNuevo->obtenXML($id_avaluo);    
        return $infoAvaluo;
             
    }

    public function obtenXML(Request $request){
        $numero_unico = trim($request->input('no_unico'));
        
        $this->modelDocumentos = new Documentos();            
        $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);
        $this->modelReimpresionNuevo = new ReimpresionNuevo();
        $infoAvaluo = $this->modelReimpresionNuevo->obtenXML($id_avaluo);    
        return $infoAvaluo;

    } 
    
    public function insertSuperficieAuxPNet(Request $request){
        //print_r($request->query); exit();     
        $par_identificadorFraccion = $request->query('par_identificadorFraccion') == 'null' ? null : $request->query('par_identificadorFraccion');
        $par_superficiefraccion = $request->query('par_superficiefraccion') == 'null' ? null : $request->query('par_superficiefraccion');
        $par_fzo = $request->query('par_fzo') == 'null' ? null : $request->query('par_fzo');
        $par_fub = $request->query('par_fub') == 'null' ? null : $request->query('par_fub');
        $par_ffr = $request->query('par_ffr') == 'null' ? null : $request->query('par_ffr');
        $par_ffo = $request->query('par_ffo') == 'null' ? null : $request->query('par_ffo');
        $par_fsu = $request->query('par_fsu') == 'null' ? null : $request->query('par_fsu');
        $par_idavaluo = $request->query('par_idavaluo') == 'null' ? null : $request->query('par_idavaluo');
        $par_fotvalor = $request->query('par_fotvalor') == 'null' ? null : $request->query('par_fotvalor');
        $par_fotdescripcion = $request->query('par_fotdescripcion') == 'null' ? null : $request->query('par_fotdescripcion');
        $par_valcatastraltierra = $request->query('par_valcatastraltierra') == 'null' ? null : $request->query('par_valcatastraltierra');
        $par_codtipo = $request->query('par_codtipo') == 'null' ? null : $request->query('par_codtipo');

        $this->modelGuardaenBD = new GuardaenBD();
        $resInsertSuperficie = $this->modelGuardaenBD->insertFexavaInvestSuperficieAux($par_identificadorFraccion,$par_superficiefraccion,$par_fzo,$par_fub,$par_ffr,$par_ffo,$par_fsu,$par_idavaluo,$par_fotvalor,$par_fotdescripcion,$par_valcatastraltierra,$par_codtipo);

        return response()->json(['IDDOCUMENTODIGITAL' => $resInsertSuperficie], 200);        
    }

    public function imprimeArreglo(Request $request){
        try{
            
            $idAvaluo = $request->query('IdAvaluo');            
            if(isset($idAvaluo)){
                Log::info($idAvaluo);
                $id_avaluo = trim($idAvaluo);
                $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
                $numero_unico = $this->modelDocumentos->get_numero_unico_db($id_avaluo);
            }else{
                $numero_unico = trim($request->query('no_unico'));      

                $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();         
                $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);
                
                
            }
           
            
            $fechaAvaluo = DB::select("SELECT to_char(FECHA,'YYYY-MM-DD') as FECHA_AVALUO FROM DOC.DOC_DOCUMENTODIGITAL WHERE IDDOCUMENTODIGITAL = '".$id_avaluo."'");
            $arr_fechaAvaluo = convierte_a_arreglo($fechaAvaluo);
            $dataFechaAvaluo = $arr_fechaAvaluo[0]['fecha_avaluo'];
            $fechaAvaluoCompara = new Carbon($dataFechaAvaluo);

            /*$fechaPresentacion = DB::select("SELECT to_char(FECHA_PRESENTACION,'YYYY-MM-DD') as FECHA_PRESENTACION FROM FEXAVA_AVALUO WHERE NUMEROUNICO = '".$numero_unico."'");
            $arr_fechaPresentacion = convierte_a_arreglo($fechaPresentacion);
            $dataFechaPresentacion = $arr_fechaPresentacion[0]['fecha_presentacion'];
            $fechaAvaluoCompara = new Carbon($dataFechaPresentacion);*/

            //$fechaCompara = new Carbon('2021-02-28');
            $anioCompara = Carbon::parse($fechaAvaluoCompara)->format('Y'); //echo "SOY AÑO COMPARA ".$anioCompara; exit();

            $contenido = $this->obtenXMLSV($numero_unico);

            $xml = simplexml_load_string($contenido,'SimpleXMLElement', LIBXML_NOCDATA);
            $arrXML = convierte_a_arreglo($xml);

            if(isset($arrXML['Comercial'])){
                $elementoPrincipal = $arrXML['Comercial'];
                $tipoDeAvaluo =  "Comercial";
            }
    
            if(isset($arrXML['Catastral'])){
                $elementoPrincipal = $arrXML['Catastral'];
                $tipoDeAvaluo =  "Catastral";
            }

            if(isset($elementoPrincipal['Antecedentes']['Solicitante']['Alcaldia'])){
                $tieneAlcaldia = true;
                //echo "SOY ALCALDIA ".$elementoPrincipal['Antecedentes']['Solicitante']['Alcaldia'];
            }
            if(isset($elementoPrincipal['Antecedentes']['Solicitante']['Delegacion'])){
                $tieneDelegacion = true;
                //echo "SOY DELEGACION ".$elementoPrincipal['Antecedentes']['Solicitante']['Delegacion'];
            }            
            

            /*$nuevo = 1; //var_dump($fechaPresentacionCompara->lt($fechaCompara)); exit();
            if($fechaAvaluoCompara->lte($fechaCompara)){
                $nuevo = 0;
            }*/
            if($anioCompara == 2021 && isset($tieneAlcaldia) && $tieneAlcaldia == true){
                $nuevo = 1;
            }else{
                $nuevo = 0;
            }

            //echo "SOY NUEVO ".$nuevo; exit();
            if($nuevo == 0){                
                   
                $this->modelReimpresionNuevo = new ReimpresionNuevo();
                $infoAvaluo = $this->modelReimpresionNuevo->infoAvaluo($id_avaluo);
                if(!is_array($infoAvaluo)){
                    return $infoAvaluo;
                }               
                
            }else{
                $numero_unico = trim($request->query('no_unico'));

                    
                $this->modelReimpresionNuevo = new ReimpresionNuevo();
                $infoAvaluo = $this->modelReimpresionNuevo->imprimeArreglo($id_avaluo);                
                
            }
            
                        
        }catch (\Throwable $th) {
            //Log::info("n° unico: ".$numero_unico."\nerror: ".$th->getMessage()."\narchivo: ".$th->getFile()."\nlinea: ".$th->getLine());
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => '45. Error al obtener la información del avalúo'], 500);
        }    
    }

    public function getToken(Request $request){ 

        $idPersona = $request->query('id_persona');        
        
        $res = array();           
                   
        $token = Crypt::encrypt(['id_persona'=>$idPersona]);
        //error_log($token);          
        echo $token;
    }

}