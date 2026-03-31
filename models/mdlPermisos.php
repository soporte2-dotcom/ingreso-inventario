<?php
require_once(__DIR__ . "/../config/conexionmysql.php");

class Permisos {
    private $mysql;

    public function __construct() {
        $this->mysql = new ConectarMysql();
    }

    // Obtener todos los módulos del sistema desde MySQL
    public function get_modulos() {
        try {
            $conn = $this->mysql->obtenerConexion();
            $query = "SELECT * FROM modulos_sistema WHERE activo = 'S' ORDER BY orden_menu ASC";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error get_modulos: " . $e->getMessage());
            return [];
        }
    }

    // Buscar usuario en SQL Server - SIN FILTRO ACTIVO
    public function buscar_usuario($busqueda) {
        try {
            require_once(__DIR__ . "/../config/conexionserver.php");
            $cn_sqlserver = new Conectarserver();
            
            $query = "SELECT Id_Usuario, Nom_Usuario, Ape_Usuario 
                      FROM TblUsuarios 
                      WHERE (Id_Usuario LIKE ? OR Nom_Usuario LIKE ? OR Ape_Usuario LIKE ?) 
                      ORDER BY Id_Usuario ASC";
            
            // Preparar los parámetros
            $param1 = "%" . $busqueda . "%";
            $param2 = "%" . $busqueda . "%"; 
            $param3 = "%" . $busqueda . "%";
            
            $params = array($param1, $param2, $param3);
            
            $registros = sqlsrv_query($cn_sqlserver->getConecta(), $query, $params);
            
            if ($registros === false) {
                error_log("Error en consulta SQL Server: " . print_r(sqlsrv_errors(), true));
                return [];
            }
            
            $usuarios = [];
            while ($fila = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
                $usuarios[] = $fila;
            }
            
            sqlsrv_free_stmt($registros);
            return $usuarios;
            
        } catch (Exception $e) {
            error_log("Error buscar_usuario: " . $e->getMessage());
            return [];
        }
    }

    // Obtener información de un usuario específico desde SQL Server - SIN FILTRO ACTIVO
    public function get_usuario($usuario_id) {
        try {
            require_once(__DIR__ . "/../config/conexionserver.php");
            $cn_sqlserver = new Conectarserver();
            
            $query = "SELECT Id_Usuario, Nom_Usuario, Ape_Usuario 
                      FROM TblUsuarios 
                      WHERE Id_Usuario = ?";
            
            $params = array($usuario_id);
            $registros = sqlsrv_query($cn_sqlserver->getConecta(), $query, $params);
            
            if ($registros === false) {
                error_log("Error get_usuario: " . print_r(sqlsrv_errors(), true));
                return null;
            }
            
            $usuario = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($registros);
            
            return $usuario;
            
        } catch (Exception $e) {
            error_log("Error get_usuario: " . $e->getMessage());
            return null;
        }
    }

    // Obtener permisos de un usuario específico desde MySQL
    public function get_permisos_usuario($usuario_id) {
        try {
            $conn = $this->mysql->obtenerConexion();
            $query = "SELECT up.modulo, up.permiso, ms.texto_menu 
                      FROM usuario_permisos up 
                      INNER JOIN modulos_sistema ms ON up.modulo = ms.nombre_modulo 
                      WHERE up.usuario_id = ? AND ms.activo = 'S'";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$usuario_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error get_permisos_usuario: " . $e->getMessage());
            return [];
        }
    }

    // Actualizar permiso de usuario en MySQL
    public function update_permiso($usuario_id, $modulo, $permiso) {
        try {
            $conn = $this->mysql->obtenerConexion();
            
            $query = "INSERT INTO usuario_permisos (usuario_id, modulo, permiso) 
                      VALUES (?, ?, ?) 
                      ON DUPLICATE KEY UPDATE permiso = ?, fecha_actualizacion = NOW()";
            
            $stmt = $conn->prepare($query);
            return $stmt->execute([$usuario_id, $modulo, $permiso, $permiso]);
        } catch (Exception $e) {
            error_log("Error update_permiso: " . $e->getMessage());
            return false;
        }
    }

    // Eliminar todos los permisos de un usuario en MySQL
    public function delete_permisos_usuario($usuario_id) {
        try {
            $conn = $this->mysql->obtenerConexion();
            $query = "DELETE FROM usuario_permisos WHERE usuario_id = ?";
            
            $stmt = $conn->prepare($query);
            return $stmt->execute([$usuario_id]);
        } catch (Exception $e) {
            error_log("Error delete_permisos_usuario: " . $e->getMessage());
            return false;
        }
    }

    // Obtener menú para el usuario actual desde MySQL
    public function get_menu_usuario($usuario_id) {
        try {
            $conn = $this->mysql->obtenerConexion();
            $query = "SELECT ms.ruta, ms.icono, ms.texto_menu 
                      FROM modulos_sistema ms
                      INNER JOIN usuario_permisos up ON ms.nombre_modulo = up.modulo
                      WHERE up.usuario_id = ? AND up.permiso = 'S' AND ms.activo = 'S'
                      ORDER BY ms.orden_menu ASC";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$usuario_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error get_menu_usuario: " . $e->getMessage());
            return [];
        }
    }

    public function __destruct() {
        if ($this->mysql) {
            $this->mysql->close();
        }
    }

  // Obtener tipos de documento entrada desde SQL Server
  public function get_tipos_documento_entradas() {
      try {
          require_once(__DIR__ . "/../config/conexionserver.php");
          $cn_sqlserver = new Conectarserver();
          
          $query = "SELECT idTipoDoctos, TipoDoctos, tipo 
                    FROM TblTipoDoctos 
                    WHERE Activo = 'S' AND tipo IN ('12', '3')
                    ORDER BY TipoDoctos ASC";
          
          $registros = sqlsrv_query($cn_sqlserver->getConecta(), $query);

          if ($registros === false) {
              error_log("Error get_tipos_documento_entradas: " . print_r(sqlsrv_errors(), true));
              return [];
          }

          $tipos = [];
          while ($fila = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
              $tipos[] = $fila;
          }

          sqlsrv_free_stmt($registros);
          return $tipos;

      } catch (Exception $e) {
          error_log("Error get_tipos_documento_entradas: " . $e->getMessage());
          return [];
      }
  }

  // Obtener tipos de documento salidas y consumos desde SQL Server
  public function get_tipos_documento_salidas() {
      try {
          require_once(__DIR__ . "/../config/conexionserver.php");
          $cn_sqlserver = new Conectarserver();
          
          $query = "SELECT idTipoDoctos, TipoDoctos, tipo 
                    FROM TblTipoDoctos 
                    WHERE Activo = 'S' AND tipo IN ('11', '2')
                    ORDER BY TipoDoctos ASC";
          
          $registros = sqlsrv_query($cn_sqlserver->getConecta(), $query);
          
          if ($registros === false) {
              error_log("Error get_tipos_documento_salidas: " . print_r(sqlsrv_errors(), true));
              return [];
          }

          $tipos = [];
          while ($fila = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
              $tipos[] = $fila;
          }

          sqlsrv_free_stmt($registros);
          return $tipos;

      } catch (Exception $e) {
          error_log("Error get_tipos_documento_salidas: " . $e->getMessage());
          return [];
      }
  }

  // Obtener permisos de documentos de un usuario
  public function get_permisos_documentos_usuario($usuario_id) {
      try {
          $conn = $this->mysql->obtenerConexion();
          $query = "SELECT tipo_documento_id, permiso 
                    FROM usuario_permisos_documentos 
                    WHERE usuario_id = ?";
          
          $stmt = $conn->prepare($query);
          $stmt->execute([$usuario_id]);
          
          $permisos = [];
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $permisos[$row['tipo_documento_id']] = $row['permiso'];
          }
          
          return $permisos;
          
      } catch (Exception $e) {
          error_log("Error get_permisos_documentos_usuario: " . $e->getMessage());
          return [];
      }
  }

  // Actualizar permiso de documento
  public function update_permiso_documento($usuario_id, $tipo_documento_id, $permiso) {
      try {
          $conn = $this->mysql->obtenerConexion();
          
          $query = "INSERT INTO usuario_permisos_documentos (usuario_id, tipo_documento_id, permiso) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE permiso = ?, fecha_creacion = NOW()";
          
          $stmt = $conn->prepare($query);
          return $stmt->execute([$usuario_id, $tipo_documento_id, $permiso, $permiso]);
          
      } catch (Exception $e) {
          error_log("Error update_permiso_documento: " . $e->getMessage());
          return false;
      }
  }

  // Eliminar todos los permisos de documentos de un usuario
  public function delete_permisos_documentos_usuario($usuario_id) {
      try {
          $conn = $this->mysql->obtenerConexion();
          $query = "DELETE FROM usuario_permisos_documentos WHERE usuario_id = ?";

          $stmt = $conn->prepare($query);
          return $stmt->execute([$usuario_id]);

      } catch (Exception $e) {
          error_log("Error delete_permisos_documentos_usuario: " . $e->getMessage());
          return false;
      }
  }

  // Eliminar permisos de documentos de un usuario por tipo (entrada o salida)
  public function delete_permisos_documentos_por_tipo($usuario_id, $tipo_documentos) {
      try {
          $conn = $this->mysql->obtenerConexion();

          // Obtener los IDs de tipos de documento según el tipo
          require_once(__DIR__ . "/../config/conexionserver.php");
          $cn_sqlserver = new Conectarserver();

          if ($tipo_documentos === 'entradas') {
              $query_tipos = "SELECT idTipoDoctos FROM TblTipoDoctos WHERE tipo IN ('12', '3') AND Activo = 'S'";
          } else {
              $query_tipos = "SELECT idTipoDoctos FROM TblTipoDoctos WHERE tipo IN ('11', '2') AND Activo = 'S'";
          }

          $registros = sqlsrv_query($cn_sqlserver->getConecta(), $query_tipos);
          $ids = [];
          while ($fila = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
              $ids[] = $fila['idTipoDoctos'];
          }
          sqlsrv_free_stmt($registros);

          if (empty($ids)) {
              return true;
          }

          // Crear placeholders para la consulta IN
          $placeholders = implode(',', array_fill(0, count($ids), '?'));
          $query = "DELETE FROM usuario_permisos_documentos WHERE usuario_id = ? AND tipo_documento_id IN ($placeholders)";

          $stmt = $conn->prepare($query);
          $params = array_merge([$usuario_id], $ids);
          return $stmt->execute($params);

      } catch (Exception $e) {
          error_log("Error delete_permisos_documentos_por_tipo: " . $e->getMessage());
          return false;
      }
  }

    // Obtener tipos de documento de entrada permitidos para un usuario (para el combo)
    public function get_tipos_documento_permitidos($usuario_id) {
      try {
          // Si es admin, mostrar todos
          if (in_array($usuario_id, ['LAUREN'])) {
              require_once(__DIR__ . "/../config/conexionserver.php");
              $cn_sqlserver = new Conectarserver();
              
              $query = "SELECT idTipoDoctos, TipoDoctos, tipo 
                        FROM TblTipoDoctos 
                        WHERE tipo IN ('12', '3') AND Activo = 'S' 
                        ORDER BY TipoDoctos ASC";
              
              $registros = sqlsrv_query($cn_sqlserver->getConecta(), $query);
              
              $tipos = [];
              while ($fila = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
                  $tipos[] = $fila;
              }
              
              sqlsrv_free_stmt($registros);
              return $tipos;
          }
          
          // Para usuarios normales: primero obtener permisos de MySQL
          $conn = $this->mysql->obtenerConexion();
          $query_permisos = "SELECT tipo_documento_id 
                            FROM usuario_permisos_documentos 
                            WHERE usuario_id = ? AND permiso = 'S'";
          
          $stmt = $conn->prepare($query_permisos);
          $stmt->execute([$usuario_id]);
          $permisos = $stmt->fetchAll(PDO::FETCH_COLUMN);
          
          // Si no tiene permisos, retornar vacío
          if (empty($permisos)) {
              return [];
          }
          
          // Ahora obtener los tipos de documento de SQL Server
          require_once(__DIR__ . "/../config/conexionserver.php");
          $cn_sqlserver = new Conectarserver();
          
          // Crear lista de IDs para la consulta
          $ids_string = implode(',', $permisos);
          
          $query = "SELECT idTipoDoctos, TipoDoctos, tipo 
                    FROM TblTipoDoctos 
                    WHERE idTipoDoctos IN ($ids_string) 
                      AND tipo IN ('12', '3') 
                      AND Activo = 'S' 
                    ORDER BY TipoDoctos ASC";
          
          $registros = sqlsrv_query($cn_sqlserver->getConecta(), $query);
          
          if ($registros === false) {
              error_log("Error get_tipos_documento_permitidos: " . print_r(sqlsrv_errors(), true));
              return [];
          }
          
          $tipos = [];
          while ($fila = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
              $tipos[] = $fila;
          }
          
          sqlsrv_free_stmt($registros);
          return $tipos;
          
      } catch (Exception $e) {
          error_log("Error get_tipos_documento_permitidos: " . $e->getMessage());
          return [];
      }
  }

  // Obtener tipos de documento de salidas permitidos para un usuario (para el combo)
    public function get_tipos_documento_salidas_permitidos($usuario_id) {
      try {
          // Si es admin, mostrar todos
          if (in_array($usuario_id, ['LAUREN'])) {
              require_once(__DIR__ . "/../config/conexionserver.php");
              $cn_sqlserver = new Conectarserver();
              
              $query = "SELECT idTipoDoctos, TipoDoctos, tipo 
                        FROM TblTipoDoctos 
                        WHERE tipo IN ('11', '2') AND Activo = 'S' 
                        ORDER BY TipoDoctos ASC";
              
              $registros = sqlsrv_query($cn_sqlserver->getConecta(), $query);
              
              $tipos = [];
              while ($fila = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
                  $tipos[] = $fila;
              }
              
              sqlsrv_free_stmt($registros);
              return $tipos;
          }
          
          // Para usuarios normales: primero obtener permisos de MySQL
          $conn = $this->mysql->obtenerConexion();
          $query_permisos = "SELECT tipo_documento_id 
                            FROM usuario_permisos_documentos 
                            WHERE usuario_id = ? AND permiso = 'S'";
          
          $stmt = $conn->prepare($query_permisos);
          $stmt->execute([$usuario_id]);
          $permisos = $stmt->fetchAll(PDO::FETCH_COLUMN);
          
          // Si no tiene permisos, retornar vacío
          if (empty($permisos)) {
              return [];
          }
          
          // Ahora obtener los tipos de documento de SQL Server
          require_once(__DIR__ . "/../config/conexionserver.php");
          $cn_sqlserver = new Conectarserver();
          
          // Crear lista de IDs para la consulta
          $ids_string = implode(',', $permisos);
          
          $query = "SELECT idTipoDoctos, TipoDoctos, tipo 
                    FROM TblTipoDoctos 
                    WHERE idTipoDoctos IN ($ids_string) 
                      AND tipo IN ('11', '2') 
                      AND Activo = 'S' 
                    ORDER BY TipoDoctos ASC";
          
          $registros = sqlsrv_query($cn_sqlserver->getConecta(), $query);
          
          if ($registros === false) {
              error_log("Error get_tipos_documento_permitidos: " . print_r(sqlsrv_errors(), true));
              return [];
          }
          
          $tipos = [];
          while ($fila = sqlsrv_fetch_array($registros, SQLSRV_FETCH_ASSOC)) {
              $tipos[] = $fila;
          }
          
          sqlsrv_free_stmt($registros);
          return $tipos;
          
      } catch (Exception $e) {
          error_log("Error get_tipos_documento_permitidos: " . $e->getMessage());
          return [];
      }
  }

  /**
   * Registra un cambio en los permisos en el log de auditoría
   */
  public function registrar_log_permisos($datos) {
      try {
          $conn = $this->mysql->obtenerConexion();
          
          $query = "INSERT INTO log_permisos 
                    (usuario_modificado, usuario_modificador, tipo_permiso, accion, 
                    permisos_anteriores, permisos_nuevos, cantidad_permisos, ip_address, user_agent) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
          
          $stmt = $conn->prepare($query);
          return $stmt->execute([
              $datos['usuario_modificado'],
              $datos['usuario_modificador'],
              $datos['tipo_permiso'],
              $datos['accion'],
              $datos['permisos_anteriores'],
              $datos['permisos_nuevos'],
              $datos['cantidad_permisos'],
              $datos['ip_address'],
              $datos['user_agent']
          ]);
          
      } catch (Exception $e) {
          error_log("Error registrar_log_permisos: " . $e->getMessage());
          return false;
      }
  }

  /**
   * Obtiene los permisos actuales de un usuario antes de modificarlos
   */
  public function obtener_permisos_json($usuario_id, $tipo = 'modulos') {
      try {
          if ($tipo === 'modulos') {
              $permisos = $this->get_permisos_usuario($usuario_id);
              $resultado = [];
              foreach ($permisos as $permiso) {
                  if ($permiso['permiso'] === 'S') {
                      $resultado[] = $permiso['texto_menu'];
                  }
              }
          } else {
              // Obtener tanto documentos de entrada como de salida
              $tipos_entrada = $this->get_tipos_documento_entradas();
              $tipos_salida = $this->get_tipos_documento_salidas();
              $permisos_docs = $this->get_permisos_documentos_usuario($usuario_id);
              $resultado = [];

              // Documentos de entrada
              foreach ($tipos_entrada as $tipo) {
                  if (isset($permisos_docs[$tipo['idTipoDoctos']]) &&
                      $permisos_docs[$tipo['idTipoDoctos']] === 'S') {
                      $resultado[] = '[ENTRADA] ' . $tipo['TipoDoctos'];
                  }
              }

              // Documentos de salida
              foreach ($tipos_salida as $tipo) {
                  if (isset($permisos_docs[$tipo['idTipoDoctos']]) &&
                      $permisos_docs[$tipo['idTipoDoctos']] === 'S') {
                      $resultado[] = '[SALIDA] ' . $tipo['TipoDoctos'];
                  }
              }
          }

          return json_encode($resultado, JSON_UNESCAPED_UNICODE);

      } catch (Exception $e) {
          error_log("Error obtener_permisos_json: " . $e->getMessage());
          return json_encode([]);
      }
  }

  /**
   * Obtiene el historial de cambios de permisos de un usuario
   */
  public function get_log_usuario($usuario_id, $limit = 50) {
      try {
          $conn = $this->mysql->obtenerConexion();
          $query = "SELECT * FROM v_log_permisos_resumen 
                    WHERE usuario_modificado = ? 
                    ORDER BY fecha_modificacion DESC 
                    LIMIT ?";
          
          $stmt = $conn->prepare($query);
          $stmt->execute([$usuario_id, $limit]);
          
          return $stmt->fetchAll(PDO::FETCH_ASSOC);
          
      } catch (Exception $e) {
          error_log("Error get_log_usuario: " . $e->getMessage());
          return [];
      }
  }

  /**
   * Obtiene el historial completo de auditoría de permisos
   */
  public function get_log_completo($limit = 100) {
      try {
          $conn = $this->mysql->obtenerConexion();
          $query = "SELECT * FROM v_log_permisos_resumen 
                    ORDER BY fecha_modificacion DESC 
                    LIMIT ?";
          
          $stmt = $conn->prepare($query);
          $stmt->execute([$limit]);
          
          return $stmt->fetchAll(PDO::FETCH_ASSOC);
          
      } catch (Exception $e) {
          error_log("Error get_log_completo: " . $e->getMessage());
          return [];
      }
  }

  /**
   * Obtiene estadísticas de modificaciones de permisos
   */
  public function get_estadisticas_log($fecha_inicio = null, $fecha_fin = null) {
      try {
          $conn = $this->mysql->obtenerConexion();
          
          $where = "";
          $params = [];
          
          if ($fecha_inicio && $fecha_fin) {
              $where = "WHERE fecha_modificacion BETWEEN ? AND ?";
              $params = [$fecha_inicio, $fecha_fin];
          }
          
          $query = "SELECT 
                      COUNT(*) as total_cambios,
                      COUNT(DISTINCT usuario_modificado) as usuarios_modificados,
                      COUNT(DISTINCT usuario_modificador) as usuarios_modificadores,
                      SUM(CASE WHEN tipo_permiso = 'modulos' THEN 1 ELSE 0 END) as cambios_modulos,
                      SUM(CASE WHEN tipo_permiso = 'documentos' THEN 1 ELSE 0 END) as cambios_documentos
                    FROM log_permisos 
                    $where";
          
          $stmt = $conn->prepare($query);
          $stmt->execute($params);
          
          return $stmt->fetch(PDO::FETCH_ASSOC);
          
      } catch (Exception $e) {
          error_log("Error get_estadisticas_log: " . $e->getMessage());
          return [];
      }
  }

}
?>