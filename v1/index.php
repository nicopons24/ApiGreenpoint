<?php

require_once 'datos/ConexionBD.php';
require 'controladores/usuarios.php';
require 'controladores/comentarios.php';
require 'controladores/favoritos.php';
require 'controladores/alertas.php';
require 'vistas/VistaJson.php';
require 'vistas/VistaXML.php';
require 'utilidades/ExcepcionApi.php';

// Constantes de estado
const ESTADO_URL_INCORRECTA = 2;
const ESTADO_EXISTENCIA_RECURSO = 3;
const ESTADO_METODO_NO_PERMITIDO = 4;

// constultar el formato
$formato = isset($_GET['formato']) ? $_GET['formato'] : 'json';

switch ($formato) {
    case 'xml':
        $vista = new VistaXML();
        break;
    case 'json':
    default:
        $vista = new VistaJson();
}

// manejo de excepciones
set_exception_handler(function ($exception) use ($vista) {
    $cuerpo = array(
        "estado" => $exception->estado,
        "mensaje" => $exception->getMessage()
    );
    if ($exception->getCode()) {
        $vista->estado = $exception->getCode();
    } else {
        $vista->estado = 500;
    }

    $vista->imprimir($cuerpo);
}
);

// obtenemos el parametro tipo contenedor
if (isset($_GET['tipo']))
    $tipo = $_GET['tipo'];
// obtenemos el parametro longitud
if (isset($_GET['contenedor']))
    $contenedor = $_GET['contenedor'];
// obtenemos la distancia del usuario si no por defecto 200m

// Extraer segmento de la url
if (isset($_GET['PATH_INFO']))
    $peticion = explode('/', $_GET['PATH_INFO']);
else
    throw new ExcepcionApi(ESTADO_URL_INCORRECTA, utf8_encode("No se reconoce la petición"));
// Obtener recurso
$recurso = array_shift($peticion);
$recursos_existentes = array('usuarios', 'favoritos', 'comentarios', 'alertas', "reciclaje");

// Comprobar si existe el recurso
if (!in_array($recurso, $recursos_existentes)) {
    throw new ExcepcionApi(ESTADO_EXISTENCIA_RECURSO, utf8_encode("El recurso al que intentas acceder no existe"));
}

$metodo = strtolower($_SERVER['REQUEST_METHOD']);

switch ($metodo) {
    case 'get':
        switch ($recurso) {
            case $recursos_existentes[0]:
                break;
            case $recursos_existentes[1]:
                if (isset($tipo))
                    array_push($peticion, $tipo);
                break;
            case $recursos_existentes[2]:
                if (isset($contenedor)) {
                    array_push($peticion, $contenedor);
                }
                else
                    throw new ExcepcionApi(ESTADO_URL_INCORRECTA, "Falta el parametro id contenedor");
                if (isset($tipo)) {
                    array_push($peticion, $tipo);
                }
                else
                    throw new ExcepcionApi(ESTADO_URL_INCORRECTA, "Falta el parametro tipo");
                break;
        }
    case 'post':
    case 'put':
    case 'delete':
        if (method_exists($recurso, $metodo)) {
            $respuesta = call_user_func(array($recurso, $metodo), $peticion);
            $vista->imprimir($respuesta);
            break;
        }
    default:
        // Método no aceptado
        $vista->estado = 405;
        $cuerpo = [
            "estado" => ESTADO_METODO_NO_PERMITIDO,
            "mensaje" => utf8_encode("Metodo no permitido")
        ];
        $vista->imprimir($cuerpo);
}