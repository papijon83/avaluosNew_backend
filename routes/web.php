<?php
/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});


    $router->get('reimprime', 'BandejaEntradaNuevoController@reimprimeSV');
    $router->post('reimprime', 'BandejaEntradaNuevoController@reimprimeSVPost');
    $router->get('acuse', 'FormatosController@generaAcusePDFSV');
    $router->post('acuse', 'FormatosController@generaAcusePDFSVPost');
    $router->get('obtenXML', 'BandejaEntradaNuevoController@obtenXML');
    $router->get('imprimeArreglo', 'BandejaEntradaNuevoController@imprimeArreglo');
    

    $router->post('guardarxml', 'BandejaEntradaNuevoController@guardarAvaluoPNet');
    $router->post('insertSuperficie', 'BandejaEntradaNuevoController@insertSuperficieAuxPNet');

    $router->get('guardarxml', 'BandejaEntradaNuevoController@guardarAvaluoPNet');
    $router->get('insertSuperficie', 'BandejaEntradaNuevoController@insertSuperficieAuxPNet');

    $router->post('estadoEnvio', 'WsSolucionIdeas@estadoEnvio');
    $router->get('estadoEnvio', 'WsSolucionIdeas@estadoEnvio');

    $router->group(['prefix' => 'WsSolucionIdeas'], function () use ($router) {
        $router->get('wsRecibeAvaluo/{folio}', 'ClienteWSController@sendAvaluo');
        $router->post('wsRecibeAvaluo', 'WsSolucionIdeas@wsRecibeAvaluo');
        $router->post('getToken', 'WsConsultaAvaluo@getToken');
        $router->post('webhooktoken', 'WebhookController@recibeToken');
        $router->post('wsRecibeAvaluoMi', 'WsSolucionIdeas@wsRecibeAvaluoMi');
        $router->post('tokenG', 'WsSolucionIdeas@obtenerTokenGuardado');    
    });

    $router->group(['prefix' => 'WsConsultaAvaluo'], function () use ($router) {    
        $router->post('consultaVista', 'WsConsultaAvaluo@avaluosVista');
        $router->post('WS_ConsultaAvaluo', 'WsConsultaAvaluo@WS_ConsultaAvaluo');    
    });



$router->group(['prefix' => 'api'], function () use ($router) {
    $router->group(['prefix' => 'v1'], function () use ($router) {
        $router->group(['prefix' => 'bandeja-entrada'], function () use ($router) {
            $router->get('avaluos', 'BandejaEntradaNuevoController@avaluos');
            $router->get('avaluos-perito', 'BandejaEntradaNuevoController@avaluosPerito');
           
            $router->get('modificarestadoavaluo', 'BandejaEntradaNuevoController@ModificarEstadoAvaluo');
            $router->get('avaluosProximos', 'BandejaEntradaNuevoController@avaluosProximos');
            $router->get('buscaNotario', 'BandejaEntradaNuevoController@buscaNotario');
            $router->get('asignaNotarioAvaluo', 'BandejaEntradaNuevoController@asignaNotarioAvaluo');
            $router->post('esValidoAvaluo', 'BandejaEntradaNuevoController@esValidoAvaluo');
            $router->post('guardarAvaluo', 'BandejaEntradaNuevoController@guardarAvaluo');
            $router->get('acuseAvaluo', 'BandejaEntradaNuevoController@acuseAvaluo');
            $router->get('reimprimeAvaluo', 'BandejaEntradaNuevoController@infoAvaluo');
            //$router->get('reimprimeAvaluoNuevo', 'BandejaEntradaNuevoController@infoAvaluoNuevo');
            $router->post('generaAcusePDF', 'FormatosController@generaAcusePDF');

            $router->post('pruebaDoc', 'PruebaDoc@pruebaGuardadoDB');
            $router->get('pruebaEjecuta', 'PruebaDoc@pruebaEjecutaProcedure');
            $router->get('catalogo/{cat}', 'PruebaDoc@infoCat');
            $router->get('pk/{pk}', 'PruebaDoc@infopk');
            $router->get('pruebaIdUsos', 'PruebaDoc@pruebaIdUsos');
            $router->get('pruebaIdRango', 'PruebaDoc@pruebaIdRango');
            $router->get('query', 'PruebaDoc@ejecutaQuery');
        });

        
    });
});
