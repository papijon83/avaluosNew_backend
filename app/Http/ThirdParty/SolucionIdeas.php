<?php
namespace App\Http\ThirdParty;
use Log;

class SolucionIdeas
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /*public function recibeAvaluo($file, $folio_Interno, $idUsuario)
    {  
        try{
            
            $resGeneraToken = $this->obtenTokenIdeas($folio_Interno);
            //$resGeneraToken = 'OK'; 
            if($resGeneraToken == 'OK'){
                //sleep(10);
                $path = storage_path();
                $rutaArchivos = $path."/Tokens/";
                $nombreArchivo = "Token".date('Ymd').".txt";
                $rutaArchivos = $rutaArchivos.$nombreArchivo; 
                $contenidoTokens = file($rutaArchivos);
                
                foreach($contenidoTokens as $contenidoToken){
                    //$arrToken = json_decode($contenidoTokens);
                    if(trim($contenidoToken) != ''){
                        $objToken = json_decode($contenidoToken);                        
                        if($objToken->folio_avaluo == $folio_Interno){
                            $token = $objToken->token;
                        }
                    }    
                                      
                }
                
            }
            //echo "SOY TOKEN OBTENIDO DESDE EL ARCHIVO ".$token; exit();
            $myfile = fopen($file, "r");
            $contents = fread($myfile, filesize($file));
            fclose($myfile);

            $curl = curl_init();

            curl_setopt_array($curl, array(
            CURLOPT_PORT => "443",
            CURLOPT_URL => "https://serviciosqa.solucionideas.com/WS_Recibe_Avaluo.svc?wsdl",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => "UTF-8",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 7,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">
            <soapenv:Header>
                <usuario xmlns=\"IDEAS.Avametrica\">".env('USUSOLUCIONQA')."</usuario>
                <contrasenia xmlns=\"IDEAS.Avametrica\">".env('PASSOLUCIONQA')."</contrasenia>
            </soapenv:Header>
            <soapenv:Body>
                <tem:BandejaAvaluoXML>            
                    <tem:datos>                        
                        <tes:AvaluoXML>".(String)($contents)."</tes:AvaluoXML>                        
                        <tes:Folio_Interno>".$folio_Interno."</tes:Folio_Interno>                        
                        <tes:Folio_Usuario>".$idUsuario."</tes:Folio_Usuario>                
                        <tes:token>".$token."</tes:token>
                    </tem:datos>
                </tem:BandejaAvaluoXML>
            </soapenv:Body>
        </soapenv:Envelope>",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: text/xml;charset=UTF-8",
                "soapAction: http://tempuri.org/IWS_Recibe_Avaluo/obtenertoken"    
            ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
            
            if ($err) {
                Log::info("cURL Error #:".json_encode($err));
            }
            return $response;    
        }catch (\Throwable $th){
            Log::info($th);
            error_log($th);
            return response()->json(['mensaje' => 'Error en el servidor'], 500);
        }
    }*/
    
    public function actualizarEnAvaluoXML($folio_Interno, $fecha_Pago, $monto_Pago, $folio_Usuario, $linea_Captura){

        $usuario = base64_encode(env("USUSOLUCIONQA"));
        $password = base64_encode(env("PASSOLUCIONQA"));

        $client = new \nusoap_client(env("WSDL_SOLUCION"), 'wsdl');    
        $authHeaders = $client->getHeader();
        if(isset($authHeaders['usuario']) && isset($authHeaders['contrasenia'])){
            $header = '<soapenv:Header><usuario xmlns="IDEAS.Avametrica">'.$usuario.'</usuario>';
            $header .= '<contrasenia xmlns="http://IDEAS.Avametrica">'.$password.'</contrasenia></soapenv:Header>'; 
            $client->setHeaders($header);
        }
        else{   
            $client->setCredentials($usuario,$password,'basic');
        }        

        $client->setCredentials($usuario,$password);
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = FALSE;
        //$res = $client->call('WS_Recibe_Avaluo', array('AvaluoXML'=>$contents,'Folio_Interno'=>$folio_Interno,'Folio_Usuario'=>$idUsuario));
        $res = $client->call('ActualizarEnAvaluoXML', array('Folio_Interno'=>$folio_Interno,'Fecha_Pago'=>$fecha_Pago,'Monto_Pago'=>$monto_Pago,'Folio_Usuario'=>$folio_Usuario,'Linea_Captura'=>$linea_Captura));
        //error_log(json_encode($res)); 
        return $res;
    }

    /* public function obtenTokenIdeas($folio_Interno){

        $curl = curl_init();

        curl_setopt_array($curl, array(
        CURLOPT_PORT => "443",
        CURLOPT_URL => "https://serviciosqa.solucionideas.com/WS_Recibe_Avaluo.svc?wsdl",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING => "UTF-8",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 7,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => "<soapenv:Envelope xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:tem=\"http://tempuri.org/\">
        <soapenv:Header>
            <usuario xmlns=\"IDEAS.Avametrica\">".env('USUSOLUCIONQA')."</usuario>
            <contrasenia xmlns=\"IDEAS.Avametrica\">".env('PASSOLUCIONQA')."</contrasenia>
        </soapenv:Header>
        <soapenv:Body>
           <tem:obtenertoken>        
              <tem:folio_avaluo>".$folio_Interno."</tem:folio_avaluo>
           </tem:obtenertoken>
        </soapenv:Body>
     </soapenv:Envelope>",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: text/xml;charset=UTF-8",
            "soapAction: http://tempuri.org/IWS_Recibe_Avaluo/obtenertoken"    
        ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        
        if ($err) {
            Log::info("cURL Error #:".json_encode($err));
        }
        
        return "OK";
        
    } */
    
}