<?php

class comentarios
{
    // Datos de la tabla "comentario"
    const NOMBRE_TABLA = "comentarios";
    const ID_COMENTARIO = "id_comentario";
    const ID_USUARIO = "id_usuario";
    const ID_CONTENEDOR = "id_contenedor";
    const TIPO = "tipo_contenedor";
    const FECHA = "fecha";
    const TEXTO = "texto";

    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_CREACION_EXITOSA = 3;
    const ESTADO_CREACION_FALLIDA = 4;
    const ESTADO_ERROR_BD = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;

    public static function get($peticion)
    {
        $idContenedor = $peticion[0];
        $tipo = $peticion[1];

        return self::obtenerComentarios($idContenedor, $tipo);

    }

    public static function post()
    {
        $idUsuario = usuarios::autorizar();

        $body = file_get_contents('php://input');
        $comentario = json_decode(utf8_encode($body));

        $comentario = self::crear($idUsuario, $comentario);

        http_response_code(201);
        return [
            "estado" => self::ESTADO_CREACION_EXITOSA,
            "mensaje" => "Comentario guardado correctamente",
            "comentarios" => $comentario
        ];
    }

    private function crear($idUsuario, $comentario)
    {
        if ($comentario) {
            try {

                $idContenedor = $comentario->idContenedor;
                $tipo = $comentario->tipo;
                $fecha = date("Y-m-d H:i:s");
                $texto = $comentario->texto;

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::ID_USUARIO . "," .
                    self::ID_CONTENEDOR . "," .
                    self::FECHA . "," .
                    self::TEXTO . "," .
                    self::TIPO . ")" .
                    " VALUES(?,?,?,?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $idUsuario, PDO::PARAM_INT);
                $sentencia->bindParam(2, $idContenedor, PDO::PARAM_INT);
                $sentencia->bindParam(3, $fecha);
                $sentencia->bindParam(4, $texto);
                $sentencia->bindParam(5, $tipo, PDO::PARAM_INT);

                $sentencia->execute();
                $idComentario = $pdo->lastInsertId();

                // Retornar en el último id insertado
                $comando = "SELECT c." . self::ID_COMENTARIO . " as idComentario" .
                    ", u." . usuarios::NOMBRE . " as nombreAutor" .
                    ", c." . self::FECHA . " as fecha" .
                    ", c." . self::TEXTO . " as cuerpo" .
                    " FROM " . self::NOMBRE_TABLA . " c" .
                    " INNER JOIN " . usuarios::NOMBRE_TABLA . " u" .
                    " ON c." . self::ID_USUARIO . " = u." . usuarios::ID_USUARIO .
                    " WHERE " . self::ID_COMENTARIO . "=?";

                // Preparar sentencia
                $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
                // Ligar idContenedor
                $sentencia->bindParam(1, $idComentario, PDO::PARAM_INT);

                $sentencia->execute();

                return $sentencia->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        } else {
            throw new ExcepcionApi(
                self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Error en existencia o sintaxis de parámetros"));
        }
    }

    private function obtenerComentarios($idContenedor, $tipo)
    {
        try {
            $comando = "SELECT c." . self::ID_COMENTARIO . " as idComentario" .
                ", u." . usuarios::NOMBRE . " as nombreAutor" .
                ", u." . usuarios::IMAGEN . " as encodedImg" .
                ", c." . self::FECHA . " as fecha" .
                ", c." . self::TEXTO . " as cuerpo" .
                " FROM " . self::NOMBRE_TABLA . " c" .
                " INNER JOIN " . usuarios::NOMBRE_TABLA . " u" .
                " ON c." . self::ID_USUARIO . " = u." . usuarios::ID_USUARIO .
                " WHERE " . self::ID_CONTENEDOR . "=? AND " . self::TIPO . "=?" .
                " ORDER BY " . self::FECHA . " DESC";

            // Preparar sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            // Ligar idContenedor
            $sentencia->bindParam(1, $idContenedor, PDO::PARAM_INT);
            $sentencia->bindParam(2, $tipo, PDO::PARAM_INT);

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_EXITO,
                        "mensaje" => "Consulta de comentarios exitosa",
                        "comentarios" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }

    }
}