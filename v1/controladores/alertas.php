<?php
class alertas {

    const NOMBRE_TABLA ="alertas";
    const TIPO = "tipo";
    const FECHA = "fecha";
    const DESCRIPCION ="descripcion";
    const ID_CONTENEDOR = "id_contenedor";
    const TIPO_CONTENEDOR = "tipo_contenedor";
    const LATITUD_CONTENEDOR = "lat_contenedor";
    const LONGITUD_CONTENEDOR = "lon_contenedor";
    const DIRECCION_CONTENEDOR = "dir_contenedor";
    const EMAIL_USUARIO = "email_usuario";
    const REPARADO = "reparado";
    const IMAGEN = "imagen";

    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_CREACION_EXITOSA = 3;
    const ESTADO_CREACION_FALLIDA = 4;
    const ESTADO_ERROR_BD = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;

    public static function post() {
        $body = file_get_contents('php://input');
        $alerta = json_decode($body);

        $idAlerta = self::crear($alerta);

        http_response_code(201);
        return [
            "estado" => self::ESTADO_CREACION_EXITOSA,
            "mensaje" => "Incidencia enviada correctamente",
            "alerta" => $idAlerta
        ];
    }

    private function crear($alerta)
    {
        if ($alerta) {
            try {
                $tipo = $alerta->tipo;
                $descripcion = $alerta->descripcion;
                $idContenedor = $alerta->idContenedor;
                $tipoContenedor = $alerta->tipoContenedor;
                $dirContenedor = $alerta->dirContenedor;
                $lat = $alerta->lat;
                $lon = $alerta->lon;
                $email = $alerta->email;
                $imagen = $alerta->encodedImg;
                $fecha = date("Y-m-d H:i:s");

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::TIPO . "," .
                    self::DESCRIPCION . "," .
                    self::FECHA . "," .
                    self::ID_CONTENEDOR . "," .
                    self::TIPO_CONTENEDOR . "," .
                    self::DIRECCION_CONTENEDOR . "," .
                    self::LATITUD_CONTENEDOR . "," .
                    self::LONGITUD_CONTENEDOR . "," .
                    self::EMAIL_USUARIO . "," .
                    self::IMAGEN . ")" .
                    " VALUES(?,?,?,?,?,?,?,?,?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $tipo);
                $sentencia->bindParam(2, $descripcion);
                $sentencia->bindParam(3, $fecha);
                $sentencia->bindParam(4, $idContenedor, PDO::PARAM_INT);
                $sentencia->bindParam(5, $tipoContenedor, PDO::PARAM_INT);
                $sentencia->bindParam(6, $dirContenedor);
                $sentencia->bindParam(7, $lat);
                $sentencia->bindParam(8, $lon);
                $sentencia->bindParam(9, $email);
                $sentencia->bindParam(10, $imagen);

                $sentencia->execute();
                // Retornar en el último id insertado
                return $pdo->lastInsertId();

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        } else {
            throw new ExcepcionApi(
                self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Error en existencia o sintaxis de parámetros"));
        }
    }

}