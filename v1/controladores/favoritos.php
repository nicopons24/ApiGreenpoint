<?php

class favoritos
{

    // Datos de la tabla "favoritos"
    const NOMBRE_TABLA = "favoritos";
    const ID_FAVORITO = "id_favorito";
    const ID_USUARIO = "id_usuario";
    const ID_CONTENEDOR = "id_contenedor";
    const TIPO = "tipo_contenedor";
    const LATITUD = "lat_contenedor";
    const LONGITUD = "lon_contenedor";
    const DIRECCION = "dir_contenedor";

    const ESTADO_EXITO = 1;
    const ESTADO_ERROR = 2;
    const ESTADO_NO_ENCONTRADO = 3;
    const ESTADO_CREACION_EXITOSA = 4;
    const ESTADO_CREACION_FALLIDA = 5;
    const ESTADO_ERROR_BD = 6;
    const ESTADO_URL_INCORRECTA = 7;
    const ESTADO_FALLA_DESCONOCIDA = 8;
    const ESTADO_PARAMETROS_INCORRECTOS = 9;

    public static function get($peticion)
    {
        $idUsuario = usuarios::autorizar();
        $tipo = $peticion[0];

        if (empty($idUsuario))
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA,
                "Clave de api no valida");
        else {
            if (empty($tipo))
                return self::obtenerFavoritos($idUsuario);
            else
                return self::obtenerFavoritosTipo($idUsuario, $tipo);
        }
    }

    public static function post()
    {
        $idUsuario = usuarios::autorizar();

        $body = file_get_contents('php://input');
        $favorito = json_decode($body);

        $idFavorito = self::crear($idUsuario, $favorito);

        http_response_code(201);
        return [
            "estado" => self::ESTADO_CREACION_EXITOSA,
            "mensaje" => "Favorito guardado correctamente",
            "idFavorito" => $idFavorito
        ];
    }

    public static function delete()
    {
        $idUsuario = usuarios::autorizar();

        $cabeceras = apache_request_headers();
        $favorito = [
            "idContenedor" => $cabeceras['idContenedor'],
            "tipo" => $cabeceras['tipoContenedor']
        ];


        $resultado = self::eliminar($idUsuario, $favorito);
        http_response_code(200);
        return [
            "estado" => self::ESTADO_EXITO,
            "mensaje" => "Favorito eliminado correctamente",
            "idFavorito" => $resultado['idFavorito'],
            "eliminados" => $resultado['eliminados']
        ];
    }

    private function eliminar($idUsuario, $favorito)
    {
        if ($favorito) {
            try {
                $idContenedor = $favorito['idContenedor'];
                $tipo = $favorito['tipo'];

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                $comando = "SELECT " . self::ID_FAVORITO . " as idFavorito " .
                    "FROM " . self::NOMBRE_TABLA .
                    " WHERE " . self::ID_USUARIO . "=? AND " .
                    self::ID_CONTENEDOR . "=? AND " .
                    self::TIPO . "=?";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $idUsuario);
                $sentencia->bindParam(2, $idContenedor, PDO::PARAM_INT);
                $sentencia->bindParam(3, $tipo, PDO::PARAM_INT);

                $sentencia->execute();

                $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($resultado)) {
                    // Sentencia DELETE
                    $comando = "DELETE FROM " . self::NOMBRE_TABLA .
                        " WHERE " . self::ID_USUARIO . "=? AND " .
                        self::ID_CONTENEDOR . "=? AND " . self::TIPO . "=?";

                    // Preparar la sentencia
                    $sentencia = $pdo->prepare($comando);

                    $sentencia->bindParam(1, $idUsuario);
                    $sentencia->bindParam(2, $idContenedor);
                    $sentencia->bindParam(3, $tipo);

                    $sentencia->execute();
                }
                else {
                    throw new ExcepcionApi(self::ESTADO_ERROR, "No existe ningun favorito con el id y tipo indicados");
                }
                return [
                    "idFavorito" => $resultado[0]['idFavorito'],
                    "eliminados" => $sentencia->rowCount()
                ];

            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        }
    }

    private
    function crear($idUsuario, $favorito)
    {
        if ($favorito) {
            try {

                $idContenedor = $favorito->idContenedor;
                $tipo = $favorito->tipo;
                $direccion = $favorito->direccion;
                $lat = $favorito->lat;
                $lon = $favorito->lon;

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::ID_USUARIO . "," .
                    self::ID_CONTENEDOR . "," .
                    self::TIPO . "," .
                    self::DIRECCION . "," .
                    self::LATITUD . "," .
                    self::LONGITUD .
                    ")" .
                    " VALUES(?,?,?,?,?,?)";

                // Preparar la sentencia
                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $idUsuario, PDO::PARAM_INT);
                $sentencia->bindParam(2, $idContenedor, PDO::PARAM_INT);
                $sentencia->bindParam(3, $tipo, PDO::PARAM_INT);
                $sentencia->bindParam(4, $direccion);
                $sentencia->bindParam(5, $lat);
                $sentencia->bindParam(6, $lon);

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

    private
    function obtenerFavoritosTipo($idUsuario, $tipo)
    {
        try {
            $comando = "SELECT " . self::ID_FAVORITO . " as idFavorito" .
                ", " . self::ID_CONTENEDOR . " as idContenedor" .
                ", " . self::TIPO . " as tipo" .
                ", " . self::DIRECCION . " as direccion" .
                ", " . self::LATITUD . " as lat" .
                ", " . self::LONGITUD . " as lon" .
                " FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_USUARIO . "=? AND " . self::TIPO . "=?";

            // Preparar sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            // Ligar idUsuario
            $sentencia->bindParam(1, $idUsuario, PDO::PARAM_INT);
            $sentencia->bindParam(2, $tipo, PDO::PARAM_INT);

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_EXITO,
                        "mensaje" => "Consulta de favoritos exitosa",
                        "favoritos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private
    function obtenerFavoritos($idUsuario)
    {
        try {
            $comando = "SELECT " . self::ID_FAVORITO . " as idFavorito" .
                ", " . self::ID_CONTENEDOR . " as idContenedor" .
                ", " . self::TIPO . " as tipo" .
                ", " . self::DIRECCION . " as direccion" .
                ", " . self::LATITUD . " as lat" .
                ", " . self::LONGITUD . " as lon" .
                " FROM " . self::NOMBRE_TABLA .
                " WHERE " . self::ID_USUARIO . "=?";

            // Preparar sentencia
            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);
            // Ligar idUsuario
            $sentencia->bindParam(1, $idUsuario, PDO::PARAM_INT);

            // Ejecutar sentencia preparada
            if ($sentencia->execute()) {
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_EXITO,
                        "mensaje" => "Consulta de favoritos exitosa",
                        "favoritos" => $sentencia->fetchAll(PDO::FETCH_ASSOC)
                    ];
            } else
                throw new ExcepcionApi(self::ESTADO_ERROR, "Se ha producido un error");

        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }

    }
}