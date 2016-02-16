<?php

class usuarios
{
    // Datos de la tabla "usuario"
    const NOMBRE_TABLA = "usuarios";
    const ID_USUARIO = "id_usuario";
    const NOMBRE = "nombre";
    const CONTRASENA = "contrasena";
    const CORREO = "email";
    const CLAVE_API = "clave_api";
    const GOOGLE = "IdGoogle";
    const FACEBOOK = "IdFacebook";
    const IMAGEN = "img";

    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_REGISTRO_EXISTENTE = 9;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;

    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            return self::loguear();
        } else if ($peticion[0] == 'google') {
            return self::loginGoogle();
        } else if ($peticion[0] == 'facebook') {
            return self::loginFacebook();
        } else if ($peticion[0] == 'password') {
            return self::changePassword();
        } else if ($peticion[0] = 'imagen') {
            return self::updateImg();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }

    private function updateImg()
    {
        $idUsuario = usuarios::autorizar();

        $cuerpo = file_get_contents('php://input');
        $json = json_decode($cuerpo);

        $imagen = $json->imagen;

        $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " . self::IMAGEN . " = ? WHERE " . self::ID_USUARIO . " = ?";

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        $sentencia = $pdo->prepare($comando);

        $sentencia->bindParam(1, $imagen);
        $sentencia->bindParam(2, $idUsuario);

        $ok = $sentencia->execute();

        if ($ok) {
            return [
                "estado" => self::ESTADO_CREACION_EXITOSA,
                "mensaje" => utf8_encode("Imagen actualizada correctamente"),
                "imagen" => "true"
            ];
        }
    }

    private function changePassword()
    {

        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        $idUsuario = usuarios::autorizar();

        $oldpassword = $usuario->oldpassword;
        $newpassword = $usuario->newpassword;

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        $comando = "SELECT " . SELF::CONTRASENA . " FROM " . self::NOMBRE_TABLA . " WHERE " . SELF::ID_USUARIO . " = " . $idUsuario;
        $sentencia = $pdo->prepare($comando);

        $ok = $sentencia->execute();

        $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);

        if ($ok) {
            if (self::validarContrasena($oldpassword, $resultado[0][self::CONTRASENA])) {
                $newpassword = self::encriptarContrasena($newpassword);

                $comando = "UPDATE " . SELF::NOMBRE_TABLA . " SET " . SELF::CONTRASENA . " = '" . $newpassword . "' WHERE " . SELF::ID_USUARIO . " = " . $idUsuario;
                $sentencia = $pdo->prepare($comando);
                $result = $sentencia->execute();

                if ($result) {
                    return [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("Contraseña cambiada correctamente"),
                        "contrasena" => "true"
                    ];
                } else {
                    throw new ExcepcionApi(self::ESTADO_ERROR_BD, "No se ha podido cambiar la contraseña");
                }
            }
        }
        throw new ExcepcionApi(self::ESTADO_ERROR_BD, "Error al cambiar la contraseña");
    }

    private function loginGoogle()
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        $correo = $usuario->correo;

        if (self::isUsuarioRegistrado($correo)) {
            return self::vincularGoogle($usuario);
        } else
            return self::registrarGoogle($usuario);
    }

    private function loginFacebook()
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        $correo = $usuario->correo;

        if (self::isUsuarioRegistrado($correo)) {
            return self::vincularFacebook($usuario);
        } else
            return self::registrarFacebook($usuario);
    }

    private function vincularGoogle($usuario)
    {
        $id = $usuario->google;
        $correo = $usuario->correo;

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " .
            self::CORREO . " = ? ";

        $sentencia = $pdo->prepare($comando);

        $sentencia->bindParam(1, $correo);

        $sentencia->execute();

        $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        $idGoogle = $resultado[0][self::GOOGLE];
        if ($idGoogle == null) {
            return self::vincularIdGoogle($usuario);
        } elseif ($idGoogle == $id) {
            return self::comprubarIdGoogle($usuario);
        } else {
            throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "La cuenta de Google no es valida");
        }
    }

    private function vincularFacebook($usuario)
    {
        $id = $usuario->facebook;
        $correo = $usuario->correo;

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        $comando = "SELECT * FROM " . self::NOMBRE_TABLA . " WHERE " .
            self::CORREO . " = ? ";

        $sentencia = $pdo->prepare($comando);

        $sentencia->bindParam(1, $correo);

        $sentencia->execute();

        $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        $idFacebook = $resultado[0][self::FACEBOOK];
        if ($idFacebook == null) {
            return self::vincularIdFacebook($usuario);
        } elseif ($idFacebook == $id) {
            return self::comprubarIdFacebook($usuario);
        } else {
            throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "La cuenta de Facebook no es valida");
        }
    }

    private function vincularIdGoogle($usuario)
    {
        $id = $usuario->google;
        $correo = $usuario->correo;

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
            self::GOOGLE . " = ? WHERE " .
            self::CORREO . " = ?";

        $sentencia = $pdo->prepare($comando);

        $sentencia->bindParam(1, $id);
        $sentencia->bindParam(2, $correo);

        $resultado = $sentencia->execute();

        if ($resultado) {
            $comando = "SELECT " . self::CLAVE_API . " FROM " . self::NOMBRE_TABLA . " WHERE " . self::CORREO . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $correo);

            $sentencia->execute();

            $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);
            return [
                "estado" => self::ESTADO_CREACION_EXITOSA,
                "mensaje" => utf8_encode("Vinculado con Google"),
                "claveApi" => $resultado[0][self::CLAVE_API]
            ];
        } else {
            throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
        }
    }

    private function vincularIdFacebook($usuario)
    {
        $id = $usuario->facebook;
        $correo = $usuario->correo;

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        $comando = "UPDATE " . self::NOMBRE_TABLA . " SET " .
            self::FACEBOOK . " = ? WHERE " .
            self::CORREO . " = ?";

        $sentencia = $pdo->prepare($comando);

        $sentencia->bindParam(1, $id);
        $sentencia->bindParam(2, $correo);

        $resultado = $sentencia->execute();

        if ($resultado) {
            $comando = "SELECT " . self::CLAVE_API . " FROM " . self::NOMBRE_TABLA . " WHERE " . self::CORREO . " = ?";

            $sentencia = $pdo->prepare($comando);
            $sentencia->bindParam(1, $correo);

            $sentencia->execute();

            $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);
            return [
                "estado" => self::ESTADO_CREACION_EXITOSA,
                "mensaje" => utf8_encode("Vinculado con Facebook"),
                "claveApi" => $resultado[0][self::CLAVE_API]
            ];
        } else {
            throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
        }
    }

    private function comprubarIdGoogle($usuario)
    {
        $correo = $usuario->correo;

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        $comando = "SELECT " . self::CLAVE_API . " FROM " . self::NOMBRE_TABLA . " WHERE " . self::CORREO . " = ?";

        $sentencia = $pdo->prepare($comando);
        $sentencia->bindParam(1, $correo);

        $sentencia->execute();

        $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        return [
            "estado" => self::ESTADO_CREACION_EXITOSA,
            "mensaje" => utf8_encode("Inicio de sesion con Google"),
            "claveApi" => $resultado[0][self::CLAVE_API]
        ];
    }

    private function comprubarIdFacebook($usuario)
    {
        $correo = $usuario->correo;

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        $comando = "SELECT " . self::CLAVE_API . " FROM " . self::NOMBRE_TABLA . " WHERE " . self::CORREO . " = ?";

        $sentencia = $pdo->prepare($comando);
        $sentencia->bindParam(1, $correo);

        $sentencia->execute();

        $resultado = $sentencia->fetchAll(PDO::FETCH_ASSOC);
        return [
            "estado" => self::ESTADO_CREACION_EXITOSA,
            "mensaje" => utf8_encode("Inicio de sesion con Facebook"),
            "claveApi" => $resultado[0][self::CLAVE_API]
        ];
    }

    private function registrarGoogle($usuario)
    {
        $nombre = $usuario->nombre;
        $correo = $usuario->correo;
        $id = $usuario->google;

        $claveApi = self::generarClaveApi();

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        // Sentencia INSERT
        $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
            self::NOMBRE . "," .
            self::CLAVE_API . "," .
            self::CORREO . "," .
            self::GOOGLE . ")" .
            " VALUES(?,?,?,?)";

        $sentencia = $pdo->prepare($comando);

        $sentencia->bindParam(1, $nombre);
        $sentencia->bindParam(2, $claveApi);
        $sentencia->bindParam(3, $correo);
        $sentencia->bindParam(4, $id);

        $resultado = $sentencia->execute();

        if ($resultado) {
            return [
                "estado" => self::ESTADO_CREACION_EXITOSA,
                "mensaje" => utf8_encode("Vinculado con Google"),
                "claveApi" => $claveApi
            ];
        } else {
            throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
        }
    }

    private function registrarFacebook($usuario)
    {
        $nombre = $usuario->nombre;
        $correo = $usuario->correo;
        $id = $usuario->facebook;

        $claveApi = self::generarClaveApi();

        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        // Sentencia INSERT
        $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
            self::NOMBRE . "," .
            self::CLAVE_API . "," .
            self::CORREO . "," .
            self::FACEBOOK . ")" .
            " VALUES(?,?,?,?)";

        $sentencia = $pdo->prepare($comando);

        $sentencia->bindParam(1, $nombre);
        $sentencia->bindParam(2, $claveApi);
        $sentencia->bindParam(3, $correo);
        $sentencia->bindParam(4, $id);

        $resultado = $sentencia->execute();

        if ($resultado) {
            return [
                "estado" => self::ESTADO_CREACION_EXITOSA,
                "mensaje" => utf8_encode("Vinculado con Facebook"),
                "claveApi" => $claveApi
            ];
        } else {
            throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
        }
    }

    /**
     * Crea un nuevo usuario en la base de datos
     */
    private function registrar()
    {
        $cuerpo = file_get_contents('php://input');
        $usuario = json_decode($cuerpo);

        $resultado = self::crear($usuario);

        switch ($resultado['estado']) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("Registro con exito!"),
                        "claveApi" => $resultado["clave"]
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    /**
     * Crea un nuevo usuario en la tabla "usuario"
     * @param mixed $datosUsuario columnas del registro
     * @return int codigo para determinar si la inserción fue exitosa
     */
    private function crear($datosUsuario)
    {
        $nombre = $datosUsuario->nombre;
        $correo = $datosUsuario->correo;

        if (!self::isUsuarioRegistrado($correo)) {

            $contrasena = $datosUsuario->contrasena;
            $contrasenaEncriptada = self::encriptarContrasena($contrasena);

            $claveApi = self::generarClaveApi();

            try {

                $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

                // Sentencia INSERT
                $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                    self::NOMBRE . "," .
                    self::CONTRASENA . "," .
                    self::CLAVE_API . "," .
                    self::CORREO . ")" .
                    " VALUES(?,?,?,?)";

                $sentencia = $pdo->prepare($comando);

                $sentencia->bindParam(1, $nombre);
                $sentencia->bindParam(2, $contrasenaEncriptada);
                $sentencia->bindParam(3, $claveApi);
                $sentencia->bindParam(4, $correo);

                $resultado = $sentencia->execute();

                if ($resultado) {
                    return [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "clave" => $claveApi
                    ];
                } else {
                    throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                }
            } catch (PDOException $e) {
                throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_REGISTRO_EXISTENTE, "El usuario ya existe");
        }

    }

    private function isUsuarioRegistrado($correo)
    {
        $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

        $comando = "SELECT * FROM " . SELF::NOMBRE_TABLA . " WHERE " . self::CORREO . " = ?";
        $sentencia = $pdo->prepare($comando);

        $sentencia->bindParam(1, $correo);

        $sentencia->execute();
        $resultado = $sentencia->rowCount();

        if ($resultado > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Protege la contraseña con un algoritmo de encriptado
     * @param $contrasenaPlana
     * @return bool|null|string
     */
    private function encriptarContrasena($contrasenaPlana)
    {
        if ($contrasenaPlana)
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
        else return null;
    }

    private function generarClaveApi()
    {
        return md5(microtime() . rand());
    }

    private function loguear()
    {
        $respuesta = array();

        $body = file_get_contents('php://input');
        $usuario = json_decode($body);

        $correo = $usuario->correo;
        $contrasena = $usuario->contrasena;


        if (self::autenticar($correo, $contrasena)) {
            $usuarioBD = self::obtenerUsuarioPorCorreo($correo);

            if ($usuarioBD != NULL) {
                http_response_code(200);
                $respuesta["nombre"] = $usuarioBD[self::NOMBRE];
                $respuesta["correo"] = $usuarioBD[self::CORREO];
                $respuesta["claveApi"] = $usuarioBD[self::CLAVE_API];
                $respuesta["imagen"] = $usuarioBD[self::IMAGEN];
                return ["estado" => 1, "usuario" => $respuesta, ];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Correo o contraseña inválidos"));
        }
    }

    private function autenticar($correo, $contrasena)
    {
        $comando = "SELECT contrasena FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=?";

        try {

            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $correo);

            $sentencia->execute();

            if ($sentencia) {
                $resultado = $sentencia->fetch();

                if (self::validarContrasena($contrasena, $resultado[self::CONTRASENA])) {
                    return true;
                } else return false;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function validarContrasena($contrasenaPlana, $contrasenaHash)
    {
        return password_verify($contrasenaPlana, $contrasenaHash);
    }


    private function obtenerUsuarioPorCorreo($correo)
    {
        $comando = "SELECT " .
            self::NOMBRE . "," .
            self::CONTRASENA . "," .
            self::CORREO . "," .
            self::CLAVE_API .  "," .
            self::IMAGEN .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CORREO . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $correo);

        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;
    }

    /**
     * Otorga los permisos a un usuario para que acceda a los recursos
     * @return null o el id del usuario autorizado
     * @throws Exception
     */
    public static function autorizar()
    {
        $cabeceras = apache_request_headers();

        if (isset($cabeceras["Authorization"])) {

            $claveApi = $cabeceras["Authorization"];

            if (usuarios::validarClaveApi($claveApi)) {
                return usuarios::obtenerIdUsuario($claveApi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave de API no autorizada", 401);
            }

        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Clave del API para autenticación"));
        }
    }

    /**
     * Comprueba la existencia de la clave para la api
     * @param $claveApi
     * @return bool true si existe o false en caso contrario
     */
    private function validarClaveApi($claveApi)
    {
        $comando = "SELECT COUNT(" . self::ID_USUARIO . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        $sentencia->execute();

        return $sentencia->fetchColumn(0) > 0;
    }

    /**
     * Obtiene el valor de la columna "idUsuario" basado en la clave de api
     * @param $claveApi
     * @return null si este no fue encontrado
     */
    private function obtenerIdUsuario($claveApi)
    {
        $comando = "SELECT " . self::ID_USUARIO .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado[self::ID_USUARIO];
        } else
            return null;
    }
}


