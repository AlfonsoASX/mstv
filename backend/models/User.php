<?php
/**
 * ==========================================================
 * MODELO: User
 * Gestiona Usuarios del sistema (Guardia, Supervisor, Admin,
 * RH, Nómina, Cliente). Incluye login, CRUD y autenticación.
 * Basado en el SQL real.
 * ==========================================================
 */

require_once __DIR__ . '/../config/database.php';

class User
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Autenticación: login con usuario + password
     */
    public function login($usuario, $password)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM usuarios
            WHERE usuario = :usuario AND activo = 1
            LIMIT 1
        ");
        $stmt->execute(['usuario' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            return $user; // Correcto
        }
        return false; // Incorrecto
    }

    /**
     * Generar y guardar API token
     */
    public function generarToken($idUsuario)
    {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare("
            UPDATE usuarios SET api_token = :token WHERE id = :id
        ");
        $stmt->execute(['token' => $token, 'id' => $idUsuario]);
        return $token;
    }

    /**
     * Validar acceso por token
     */
    public function validarToken($token)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM usuarios
            WHERE api_token = :token AND activo = 1
        ");
        $stmt->execute(['token' => $token]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Listar usuarios filtrados por rol (opcional)
     */
    public function listar($rol = null, $activo = null)
    {
        $sql = "SELECT id, nombre, apellido, telefono, email, usuario, rol, activo FROM usuarios WHERE 1=1";
        $params = [];

        if ($rol) {
            $sql .= " AND rol = :rol";
            $params['rol'] = $rol;
        }

        if ($activo !== null) {
            $sql .= " AND activo = :activo";
            $params['activo'] = $activo;
       }

        $sql .= " ORDER BY apellido ASC, nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtener detalle completo de usuario
     */
    public function obtener($id)
    {
        $stmt = $this->db->prepare("
            SELECT u.*, CONCAT(u.nombre,' ',u.apellido) AS nombre_completo
            FROM usuarios u
            WHERE u.id = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crear nuevo usuario
     */
    public function crear($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nombre, apellido, telefono, email, usuario, password, rol, foto_base)
            VALUES (:nombre, :apellido, :telefono, :email, :usuario, :password, :rol, :foto)
        ");

        return $stmt->execute([
            'nombre'  => $data['nombre'],
            'apellido'=> $data['apellido'],
            'telefono'=> $data['telefono'],
            'email'   => $data['email'],
            'usuario' => $data['usuario'],
            'password'=> password_hash($data['password'], PASSWORD_DEFAULT),
            'rol'     => $data['rol'],
            'foto'    => $data['foto_base'] ?? null
        ]);
    }

    /**
     * Actualizar usuario
     */
    public function actualizar($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios SET
                nombre = :nombre,
                apellido = :apellido,
                telefono = :telefono,
                email = :email,
                rol = :rol,
                foto_base = :foto
            WHERE id = :id
        ");

        return $stmt->execute([
            'id'       => $id,
            'nombre'   => $data['nombre'],
            'apellido' => $data['apellido'],
            'telefono' => $data['telefono'],
            'email'    => $data['email'],
            'rol'      => $data['rol'],
            'foto'     => $data['foto_base'] ?? null
        ]);
    }

    /**
     * Cambiar estado (activar/desactivar)
     */
    public function cambiarEstado($id, $activo)
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios SET activo = :activo WHERE id = :id
        ");
        return $stmt->execute([
            'activo' => $activo,
            'id'     => $id
        ]);
    }

    /**
     * Cambiar contraseña de usuario
     */
    public function cambiarPassword($id, $nuevoPassword)
    {
        $stmt = $this->db->prepare("
            UPDATE usuarios SET password = :pass WHERE id = :id
        ");
        return $stmt->execute([
            'id'   => $id,
            'pass' => password_hash($nuevoPassword, PASSWORD_DEFAULT)
        ]);
    }

    /**
     * Verificar permisos por rol
     */
    public function tieneRol($id, $rolesPermitidos = [])
    {
        $stmt = $this->db->prepare("
            SELECT rol FROM usuarios WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);
        $rol = $stmt->fetchColumn();

        return in_array($rol, $rolesPermitidos);
    }

    /**
     * Auditoría: registrar acción relevante (bitácora)
     */
    public function registrarBitacora($usuario_id, $accion, $entidad, $entidad_id, $detalles = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO bitacora (usuario_id, accion, entidad, entidad_id, detalles)
            VALUES (:u, :accion, :entidad, :entidad_id, :detalles)
        ");
        return $stmt->execute([
            'u'          => $usuario_id,
            'accion'     => $accion,
            'entidad'    => $entidad,
            'entidad_id' => $entidad_id,
            'detalles'   => $detalles
        ]);
    }
}
