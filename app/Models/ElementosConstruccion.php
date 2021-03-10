<?php

namespace App\Models;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ElementosConstruccion
{

    public function obtenerInstEspecialByClave($claveInstalEsp){

        $conn = oci_connect(env("DB_USERNAME_CAS"), env("DB_PASSWORD_CAS"), env("DB_TNS"));        
        $sqlcadena = oci_parse($conn, "SELECT * FROM CAS.CAS_CATINSTESPECIALES WHERE CLAVE = '".$claveInstalEsp."'");
        oci_execute($sqlcadena);

        $fila = oci_fetch_array($sqlcadena, OCI_ASSOC+OCI_RETURN_NULLS);
        oci_free_statement($sqlcadena);
        oci_close($conn);

        return $fila;
    }

}