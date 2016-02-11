<?php
class reciclaje {

    const NOMBRE_TABLA = "reciclaje";
    const ID_RECICLAJE = "id_reciclaje";
    const ID_USUARIO = "id_usuario";
    const TIPO = "tipo_reciclaje";
    const PESO = "peso_reciclaje";
    const FECHA = "fecha";

    const PESO_ORGANICO = 1.66;
    const PESO_PLASTICO = 0.22;
    const PESO_VIDRIO = 1.27;
    const PESO_CARTON = 0.63;
    const PESO_ACEITE = 1.84;
    const PESO_PILAS = 1.23;
    const PESO_PAPELERA = 0.01;

    const PAPELERA = 0;
    const ORGANICO = 1;
    const CARTON = 2;
    const PLASTICO = 3;
    const VIDRIO = 4;
    const ACEITE = 5;
    const PILAS = 6;

    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_CREACION_EXITOSA = 3;
    const ESTADO_CREACION_FALLIDA = 4;
    const ESTADO_ERROR_BD = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;

    public static function post()
    {
        $idUsuario = usuarios::autorizar();

        $body = file_get_contents('php://input');
        $reciclaje = json_decode($body);

        $id = self::crear($idUsuario, $reciclaje);

        http_response_code(201);
        return [
            "estado" => self::ESTADO_CREACION_EXITOSA,
            "mensaje" => "Accion registrada correctamente",
            "idReciclaje" => $id
        ];
    }

    private function crear($idUsuario, $reciclaje)
    {
        if ($reciclaje) {
            try {

                $tipo = $reciclaje->tipo;
                $fecha = date("Y-m-d H:i:s");
                $peso = self::calculaPeso($tipo);

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::TIPO . "," .
                    self::PESO . "," .
                    self::FECHA . "," .
                    self::ID_USUARIO . ")" .
                    " VALUES(?,?,?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $tipo, PDO::PARAM_INT);
                $sentencia->bindParam(2, $peso);
                $sentencia->bindParam(3, $fecha);
                $sentencia->bindParam(4, $idUsuario);

                $sentencia->execute();
                $idReciclaje = $pdo->lastInsertId();

                return $idReciclaje;
            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        } else {
            throw new ExcepcionApi(
                self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Error en existencia o sintaxis de parámetros"));
        }
    }

    private function calculaPeso($tipo) {
        switch ($tipo) {
            case self::PAPELERA:
                return self::PESO_PAPELERA;
            case self::ORGANICO:
                return self::PESO_ORGANICO;
            case self::CARTON:
                return self::PESO_CARTON;
            case self::PLASTICO:
                return self::PESO_PLASTICO;
            case self::VIDRIO:
                return self::PESO_VIDRIO;
            case self::ACEITE:
                return self::PESO_ACEITE;
            case self::PILAS:
                return self::PESO_PILAS;
        }
    }
}