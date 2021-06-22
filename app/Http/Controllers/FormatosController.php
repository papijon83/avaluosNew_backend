<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade as PDF;
use App\Models\Documentos;
use App\Models\ReimpresionNuevo;
use Carbon\Carbon;
use Log;

class FormatosController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */

    protected $modelDocumentos;
    protected $modelReimpresionNuevo;

    public function __construct()
    {
        //
    }

    public function generaAcusePDF(Request $request)
    {
        try {
            $data = decrypt($request->input('token'));
            $no_unico = $request->input('no_unico');

            $datosPDF = [];

            $datosPDF['numeroUnico'] = trim($data['numeroUnico']);
            $datosPDF['fechaCarga'] = trim('20/12/2021');
            $datosPDF['region'] = trim($data['cuentaCatastral']['region']);
            $datosPDF['manzana'] = trim($data['cuentaCatastral']['manzana']);
            $datosPDF['lote'] = trim($data['cuentaCatastral']['lote']);
            $datosPDF['unidadprivativa'] = trim($data['cuentaCatastral']['unidadprivativa']);
            $datosPDF['digitoverificador'] = trim($data['cuentaCatastral']['digitoverificador']);
            $datosPDF['cuentaAgua'] = trim($data['Ubicacion_Inmueble']['Cuenta_agua']);
            $datosPDF['propietario']['apellidopaterno'] = trim($data['propietario']['apellidopaterno']);
            $datosPDF['propietario']['apellidomaterno'] = trim($data['propietario']['apellidomaterno']);
            $datosPDF['propietario']['nombre'] = trim($data['propietario']['nombre']);
            $datosPDF['propietario']['calle'] = trim($data['propietario']['calle']);
            $datosPDF['propietario']['numeroexterior'] = trim($data['propietario']['numeroexterior']);
            $datosPDF['propietario']['numerointerior'] = trim($data['propietario']['numerointerior']);
            $datosPDF['propietario']['nombrecolonia'] = trim($data['propietario']['nombrecolonia']);
            $datosPDF['propietario']['nombredelegacion'] = trim($data['propietario']['nombredelegacion']);
            $datosPDF['propietario']['codigopostal'] = trim($data['propietario']['codigopostal']);
            $datosPDF['propietario']['entidad'] = trim("DISTRITO FEDERAL");
            $datosPDF['propietario']['telefono'] = trim("");
            //$datosPDF['inmueble']['tipoinmueble'] = trim($data['tipoDeInmueble']);
            $datosPDF['inmueble']['calle'] = trim($data['Ubicacion_Inmueble']['Calle']);
            $datosPDF['inmueble']['lote'] = trim($data['Ubicacion_Inmueble']['Lote']);
            $datosPDF['inmueble']['numeroexterior'] = trim($data['Ubicacion_Inmueble']['No_Exterior']);
            $datosPDF['inmueble']['numerointerior'] = trim($data['Ubicacion_Inmueble']['No_Interior']);
            $datosPDF['inmueble']['nombrecolonia'] = trim($data['Ubicacion_Inmueble']['Colonia']);
            $datosPDF['inmueble']['nombredelegacion'] = trim($data['Ubicacion_Inmueble']['Delegacion']);
            $datosPDF['inmueble']['codigopostal'] = trim($data['Ubicacion_Inmueble']['CP']);
            $datosPDF['numescritura'] = trim($data['escritura']['numescritura']);
            $datosPDF['fecha'] = Carbon::parse(trim($data['fuenteInformacionLegal']['fecha']))->format('d/m/Y');
            $datosPDF['numnotario'] = trim($data['escritura']['numnotario']);
            $datosPDF['nombrenotario'] = trim($data['escritura']['nombrenotario']);
            $datosPDF['distritojudicialnotario'] = trim($data['escritura']['distritojudicialnotario']);
           
            $formato = view('acuse_pdf', compact("datosPDF"))->render();
            $pdf = PDF::loadHTML($formato);
            $pdf->setOptions(['chroot' => 'public']);
            Storage::put('formato.pdf', $pdf->output());

            return response()->json(['pdfbase64' => base64_encode(Storage::get('formato.pdf')), 'nombre' => $no_unico . '.pdf'], 200);
        } catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            return response()->json(['mensaje' => 'Error en el servidor'], 500);
        }
    }

    public function generaAcusePDFSV(Request $request)
    {
        try {
            $data = $this->acuseAvaluoSV($request->input('no_unico'));  //print_r($data); exit();
            //$data = json_decode($dataJson); print_r($data); exit();
            //$data = decrypt($token);
            if($data == "INE"){
                return response()->json(['mensaje' => 'Información no encontrada'], 500);
            }
            $no_unico = $request->input('no_unico');

            $datosPDF = [];

            $datosPDF['numeroUnico'] = trim($data['numeroUnico']);
            $datosPDF['fechaCarga'] = trim('20/12/2021');
            $datosPDF['region'] = trim($data['cuentaCatastral']['region']);
            $datosPDF['manzana'] = trim($data['cuentaCatastral']['manzana']);
            $datosPDF['lote'] = trim($data['cuentaCatastral']['lote']);
            $datosPDF['unidadprivativa'] = trim($data['cuentaCatastral']['unidadprivativa']);
            $datosPDF['digitoverificador'] = trim($data['cuentaCatastral']['digitoverificador']);
            $datosPDF['cuentaAgua'] = trim($data['Ubicacion_Inmueble']['Cuenta_agua']);
            $datosPDF['propietario']['apellidopaterno'] = trim($data['propietario']['apellidopaterno']);
            $datosPDF['propietario']['apellidomaterno'] = trim($data['propietario']['apellidomaterno']);
            $datosPDF['propietario']['nombre'] = trim($data['propietario']['nombre']);
            $datosPDF['propietario']['calle'] = trim($data['propietario']['calle']);
            $datosPDF['propietario']['numeroexterior'] = trim($data['propietario']['numeroexterior']);
            $datosPDF['propietario']['numerointerior'] = trim($data['propietario']['numerointerior']);
            $datosPDF['propietario']['nombrecolonia'] = trim($data['propietario']['nombrecolonia']);
            $datosPDF['propietario']['nombredelegacion'] = trim($data['propietario']['nombredelegacion']);
            $datosPDF['propietario']['codigopostal'] = trim($data['propietario']['codigopostal']);
            $datosPDF['propietario']['entidad'] = trim("DISTRITO FEDERAL");
            $datosPDF['propietario']['telefono'] = trim("");
            //$datosPDF['inmueble']['tipoinmueble'] = trim($data['tipoDeInmueble']);
            $datosPDF['inmueble']['calle'] = trim($data['Ubicacion_Inmueble']['Calle']);
            $datosPDF['inmueble']['lote'] = trim($data['Ubicacion_Inmueble']['Lote']);
            $datosPDF['inmueble']['numeroexterior'] = trim($data['Ubicacion_Inmueble']['No_Exterior']);
            $datosPDF['inmueble']['numerointerior'] = trim($data['Ubicacion_Inmueble']['No_Interior']);
            $datosPDF['inmueble']['nombrecolonia'] = trim($data['Ubicacion_Inmueble']['Colonia']);
            $datosPDF['inmueble']['nombredelegacion'] = trim($data['Ubicacion_Inmueble']['Delegacion']);
            $datosPDF['inmueble']['codigopostal'] = trim($data['Ubicacion_Inmueble']['CP']);
            $datosPDF['numescritura'] = trim($data['escritura']['numescritura']);
            $datosPDF['fecha'] = Carbon::parse(trim($data['fuenteInformacionLegal']['fecha']))->format('d/m/Y');
            $datosPDF['numnotario'] = trim($data['escritura']['numnotario']);
            $datosPDF['nombrenotario'] = trim($data['escritura']['nombrenotario']);
            $datosPDF['distritojudicialnotario'] = trim($data['escritura']['distritojudicialnotario']);
           
            $formato = view('acuse_pdf', compact("datosPDF"))->render();
            $pdf = PDF::loadHTML($formato);
            $pdf->setOptions(['chroot' => 'public']);
            
            return $pdf->stream('formato.pdf');
            //return $pdf->download('formato.pdf');

            /*Storage::put('formato.pdf', $pdf->output());

            return response()->json(['pdfbase64' => base64_encode(Storage::get('formato.pdf')), 'nombre' => $no_unico . '.pdf'], 200);*/
        } catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            return response()->json(['mensaje' => 'Error en el servidor'], 500);
        }
    }

    public function acuseAvaluoSV($numero_unico){
        try{
            //$numero_unico = trim($request->query('no_unico'));
            $this->modelDocumentos = new Documentos();    //echo $numero_unico; exit();
                    
            $id_avaluo = $this->modelDocumentos->get_idavaluo_db($numero_unico);           
            $this->modelReimpresionNuevo = new ReimpresionNuevo();
            $infoAcuse = $this->modelReimpresionNuevo->infoAcuse($id_avaluo);
            //$token_infoAcuse = Crypt::encrypt($infoAcuse); 
            //return response()->json([$infoAcuse, $token_infoAcuse], 200);
            
            if(count($infoAcuse) == 0){
                return "INE";
            } 
            //Log::info(json_encode($infoAcuse));
            return $infoAcuse;
        }catch (\Throwable $th) {
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error en el servidor'], 500);
        }    
    }

    public function generaAcusePDFSVPost(Request $request){
        try {
            $data = $this->acuseAvaluoSV($request->input('no_unico'));  //print_r($data); exit();
            //$data = json_decode($dataJson); print_r($data); exit();
            //$data = decrypt($token);
            if($data == "INE"){
                return response()->json(['mensaje' => 'Información no encontrada'], 500);
            }
            $no_unico = $request->input('no_unico');

            $datosPDF = [];

            $datosPDF['numeroUnico'] = trim($data['numeroUnico']);
            $datosPDF['fechaCarga'] = trim('20/12/2021');
            $datosPDF['region'] = trim($data['cuentaCatastral']['region']);
            $datosPDF['manzana'] = trim($data['cuentaCatastral']['manzana']);
            $datosPDF['lote'] = trim($data['cuentaCatastral']['lote']);
            $datosPDF['unidadprivativa'] = trim($data['cuentaCatastral']['unidadprivativa']);
            $datosPDF['digitoverificador'] = trim($data['cuentaCatastral']['digitoverificador']);
            $datosPDF['cuentaAgua'] = trim($data['Ubicacion_Inmueble']['Cuenta_agua']);
            $datosPDF['propietario']['apellidopaterno'] = trim($data['propietario']['apellidopaterno']);
            $datosPDF['propietario']['apellidomaterno'] = trim($data['propietario']['apellidomaterno']);
            $datosPDF['propietario']['nombre'] = trim($data['propietario']['nombre']);
            $datosPDF['propietario']['calle'] = trim($data['propietario']['calle']);
            $datosPDF['propietario']['numeroexterior'] = trim($data['propietario']['numeroexterior']);
            $datosPDF['propietario']['numerointerior'] = trim($data['propietario']['numerointerior']);
            $datosPDF['propietario']['nombrecolonia'] = trim($data['propietario']['nombrecolonia']);
            $datosPDF['propietario']['nombredelegacion'] = trim($data['propietario']['nombredelegacion']);
            $datosPDF['propietario']['codigopostal'] = trim($data['propietario']['codigopostal']);
            $datosPDF['propietario']['entidad'] = trim("DISTRITO FEDERAL");
            $datosPDF['propietario']['telefono'] = trim("");
            //$datosPDF['inmueble']['tipoinmueble'] = trim($data['tipoDeInmueble']);
            $datosPDF['inmueble']['calle'] = trim($data['Ubicacion_Inmueble']['Calle']);
            $datosPDF['inmueble']['lote'] = trim($data['Ubicacion_Inmueble']['Lote']);
            $datosPDF['inmueble']['numeroexterior'] = trim($data['Ubicacion_Inmueble']['No_Exterior']);
            $datosPDF['inmueble']['numerointerior'] = trim($data['Ubicacion_Inmueble']['No_Interior']);
            $datosPDF['inmueble']['nombrecolonia'] = trim($data['Ubicacion_Inmueble']['Colonia']);
            $datosPDF['inmueble']['nombredelegacion'] = trim($data['Ubicacion_Inmueble']['Delegacion']);
            $datosPDF['inmueble']['codigopostal'] = trim($data['Ubicacion_Inmueble']['CP']);
            $datosPDF['numescritura'] = trim($data['escritura']['numescritura']);
            $datosPDF['fecha'] = Carbon::parse(trim($data['fuenteInformacionLegal']['fecha']))->format('d/m/Y');
            $datosPDF['numnotario'] = trim($data['escritura']['numnotario']);
            $datosPDF['nombrenotario'] = trim($data['escritura']['nombrenotario']);
            $datosPDF['distritojudicialnotario'] = trim($data['escritura']['distritojudicialnotario']);
           
            $formato = view('acuse_pdf', compact("datosPDF"))->render();
            $pdf = PDF::loadHTML($formato);
            $pdf->setOptions(['chroot' => 'public']);
            
            return $pdf->download('formato.pdf');

            /*Storage::put('formato.pdf', $pdf->output());

            return response()->json(['pdfbase64' => base64_encode(Storage::get('formato.pdf')), 'nombre' => $no_unico . '.pdf'], 200);*/
        } catch (\Throwable $th) {
            error_log($th);
            Log::info($th);
            return response()->json(['mensaje' => 'Error en el servidor'], 500);
        }

    }
}
