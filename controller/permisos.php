<?php
// Solo necesitamos la conexión a SQL Server para la sesión
require_once("../config/conexionserver.php");

// Verificar que el usuario tenga sesión activa
if (!isset($_SESSION["Id_Usuario"])) {
    echo "Error: No hay sesión activa";
    exit();
}

require_once("../models/mdlPermisos.php");

try {
    $permisos = new Permisos();

    switch ($_GET["op"]) {
        case "buscar_usuario":
            if (!isset($_POST["busqueda"])) {
                echo "<div class='no-results'>No se recibió término de búsqueda</div>";
                break;
            }
            
            $busqueda = $_POST["busqueda"];
            $usuarios = $permisos->buscar_usuario($busqueda);
            
            $html = "";
            if (is_array($usuarios) && count($usuarios) > 0) {
                foreach ($usuarios as $usuario) {
                    $nombre_completo = $usuario['Nom_Usuario'] . ' ' . $usuario['Ape_Usuario'];
                    $html .= "<div class='usuario-item' data-usuario-id='{$usuario['Id_Usuario']}'>
                                <strong>{$usuario['Id_Usuario']}</strong> - {$nombre_completo}
                             </div>";
                }
            } else {
                $html = "<div class='no-results'>No se encontraron usuarios</div>";
            }
            echo $html;
            break;

        case "cargar_permisos":
            if (!isset($_POST["usuario_id"])) {
                echo "<div class='alert alert-danger'>No se recibió ID de usuario</div>";
                break;
            }
            
            $usuario_id = $_POST["usuario_id"];
            
            // Primero verificar que el usuario existe en SQL Server
            $usuario_info = $permisos->get_usuario($usuario_id);
            if (!$usuario_info) {
                echo "<div class='alert alert-danger'>Usuario no encontrado en el sistema</div>";
                break;
            }
            
            // Mostrar información del usuario
            $nombre_completo = $usuario_info['Nom_Usuario'] . ' ' . $usuario_info['Ape_Usuario'];
            echo "<div class='user-info alert alert-info'>
                    <h5><span class='glyphicon glyphicon-user'></span> Información del Usuario</h5>
                    <strong>ID:</strong> {$usuario_info['Id_Usuario']}<br>
                    <strong>Nombre:</strong> {$nombre_completo}
                  </div>";
            
            // Cargar módulos y permisos
            $modulos = $permisos->get_modulos();
            $permisos_usuario = $permisos->get_permisos_usuario($usuario_id);
            
            // Crear array de permisos del usuario para fácil acceso
            $permisos_array = [];
            foreach ($permisos_usuario as $permiso) {
                $permisos_array[$permiso['modulo']] = $permiso['permiso'];
            }
            
            $html = "<h5><span class='glyphicon glyphicon-lock'></span> Permisos del Sistema</h5>";
            
            if (is_array($modulos) && count($modulos) > 0) {
                // Checkbox para seleccionar todos
                $html .= "<div class='select-all-container'>
                            <input type='checkbox' id='select_all_modulos'>
                            <label for='select_all_modulos'>
                                <span class='glyphicon glyphicon-check'></span>
                                Seleccionar Todos los Módulos
                            </label>
                          </div>";
                
                $html .= "<div class='modulos-list'>";
                foreach ($modulos as $modulo) {
                    $checked = (isset($permisos_array[$modulo['nombre_modulo']]) && $permisos_array[$modulo['nombre_modulo']] == 'S') ? 'checked' : '';
                    $html .= "<div class='checkbox'>
                                <input type='checkbox' id='modulo_{$modulo['nombre_modulo']}' 
                                       name='modulos[]' value='{$modulo['nombre_modulo']}' $checked>
                                <label for='modulo_{$modulo['nombre_modulo']}'>
                                    <span class='glyphicon {$modulo['icono']}'></span>
                                    {$modulo['texto_menu']}
                                </label>
                             </div>";
                }
                $html .= "</div>";
            } else {
                $html .= "<div class='alert alert-warning'>No hay módulos configurados en el sistema</div>";
            }
            echo $html;
            break;

        case "guardar_permisos":
            if (!isset($_POST["usuario_id"])) {
                echo json_encode(["status" => "error", "message" => "No se recibió ID de usuario"]);
                break;
            }
            
            $usuario_id = $_POST["usuario_id"];
            $modulos_seleccionados = isset($_POST["modulos"]) ? $_POST["modulos"] : [];
            
            // Obtener permisos anteriores para el log
            $permisos_anteriores = $permisos->obtener_permisos_json($usuario_id, 'modulos');
            
            // Primero eliminar todos los permisos del usuario
            $permisos->delete_permisos_usuario($usuario_id);
            
            // Luego insertar los nuevos permisos
            $success = true;
            $modulos_guardados = 0;
            
            foreach ($modulos_seleccionados as $modulo) {
                if ($permisos->update_permiso($usuario_id, $modulo, 'S')) {
                    $modulos_guardados++;
                } else {
                    $success = false;
                }
            }
            
            if ($success) {
                // Obtener permisos nuevos para el log
                $permisos_nuevos = $permisos->obtener_permisos_json($usuario_id, 'modulos');
                
                // Registrar en el log de auditoría
                $permisos->registrar_log_permisos([
                    'usuario_modificado' => $usuario_id,
                    'usuario_modificador' => $_SESSION["Id_Usuario"],
                    'tipo_permiso' => 'modulos',
                    'accion' => "Se actualizaron permisos de módulos para {$modulos_guardados} módulos",
                    'permisos_anteriores' => $permisos_anteriores,
                    'permisos_nuevos' => $permisos_nuevos,
                    'cantidad_permisos' => $modulos_guardados,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
                
                echo json_encode([
                    "status" => "success", 
                    "message" => "Permisos actualizados correctamente para {$modulos_guardados} módulos"
                ]);
            } else {
                echo json_encode([
                    "status" => "error", 
                    "message" => "Error al actualizar algunos permisos"
                ]);
            }
            break;

        case "cargar_permisos_entradas":
            $usuario_id = $_POST["usuario_id"];

            // Verificar que el usuario existe
            $usuario_info = $permisos->get_usuario($usuario_id);
            if (!$usuario_info) {
                echo "<div class='alert alert-danger'>Usuario no encontrado</div>";
                break;
            }

            // Mostrar información del usuario
            $nombre_completo = $usuario_info['Nom_Usuario'] . ' ' . $usuario_info['Ape_Usuario'];
            echo "<div class='user-info alert alert-info'>
                    <h5><span class='glyphicon glyphicon-user'></span> Información del Usuario</h5>
                    <strong>ID:</strong> {$usuario_info['Id_Usuario']}<br>
                    <strong>Nombre:</strong> {$nombre_completo}
                  </div>";

            // Cargar tipos de documento de entrada
            $tipos_documento_entradas = $permisos->get_tipos_documento_entradas();
            $permisos_documentos = $permisos->get_permisos_documentos_usuario($usuario_id);

            $html = "<h5><span class='glyphicon glyphicon-log-in'></span> Permisos de Documentos de Entrada</h5>";

            if (is_array($tipos_documento_entradas) && count($tipos_documento_entradas) > 0) {
                $html .= "<div class='select-all-container'>
                            <input type='checkbox' id='select_all_documentos_entrada'>
                            <label for='select_all_documentos_entrada'>
                                <span class='glyphicon glyphicon-check'></span>
                                Seleccionar Todos los Documentos de Entrada
                            </label>
                          </div>";

                $html .= "<div class='documentos-list documentos-entrada'>";
                $html .= "<p class='text-muted'>Selecciona los tipos de documento de entrada que este usuario puede usar:</p>";

                foreach ($tipos_documento_entradas as $tipo) {
                    $checked = (isset($permisos_documentos[$tipo['idTipoDoctos']]) &&
                              $permisos_documentos[$tipo['idTipoDoctos']] == 'S') ? 'checked' : '';

                    $html .= "<div class='checkbox'>
                                <input type='checkbox' id='doc_entrada_{$tipo['idTipoDoctos']}'
                                      name='documentos[]' value='{$tipo['idTipoDoctos']}' $checked>
                                <label for='doc_entrada_{$tipo['idTipoDoctos']}'>
                                    <strong>{$tipo['TipoDoctos']}</strong>
                                    <small class='text-muted'>(Tipo: {$tipo['tipo']})</small>
                                </label>
                            </div>";
                }
                $html .= "</div>";
            } else {
                $html .= "<div class='alert alert-warning'>No hay tipos de documento de entrada configurados</div>";
            }
            echo $html;
            break;

        case "cargar_permisos_salidas":
            $usuario_id = $_POST["usuario_id"];

            // Verificar que el usuario existe
            $usuario_info = $permisos->get_usuario($usuario_id);
            if (!$usuario_info) {
                echo "<div class='alert alert-danger'>Usuario no encontrado</div>";
                break;
            }

            // Mostrar información del usuario
            $nombre_completo = $usuario_info['Nom_Usuario'] . ' ' . $usuario_info['Ape_Usuario'];
            echo "<div class='user-info alert alert-info'>
                    <h5><span class='glyphicon glyphicon-user'></span> Información del Usuario</h5>
                    <strong>ID:</strong> {$usuario_info['Id_Usuario']}<br>
                    <strong>Nombre:</strong> {$nombre_completo}
                  </div>";

            // Cargar tipos de documento de salida
            $tipos_documento_salidas = $permisos->get_tipos_documento_salidas();
            $permisos_documentos = $permisos->get_permisos_documentos_usuario($usuario_id);

            $html = "<h5><span class='glyphicon glyphicon-log-out'></span> Permisos de Documentos de Salida</h5>";

            if (is_array($tipos_documento_salidas) && count($tipos_documento_salidas) > 0) {
                $html .= "<div class='select-all-container'>
                            <input type='checkbox' id='select_all_documentos_salida'>
                            <label for='select_all_documentos_salida'>
                                <span class='glyphicon glyphicon-check'></span>
                                Seleccionar Todos los Documentos de Salida
                            </label>
                          </div>";

                $html .= "<div class='documentos-list documentos-salida'>";
                $html .= "<p class='text-muted'>Selecciona los tipos de documento de salida que este usuario puede usar:</p>";

                foreach ($tipos_documento_salidas as $tipo) {
                    $checked = (isset($permisos_documentos[$tipo['idTipoDoctos']]) &&
                              $permisos_documentos[$tipo['idTipoDoctos']] == 'S') ? 'checked' : '';

                    $html .= "<div class='checkbox'>
                                <input type='checkbox' id='doc_salida_{$tipo['idTipoDoctos']}'
                                      name='documentos[]' value='{$tipo['idTipoDoctos']}' $checked>
                                <label for='doc_salida_{$tipo['idTipoDoctos']}'>
                                    <strong>{$tipo['TipoDoctos']}</strong>
                                    <small class='text-muted'>(Tipo: {$tipo['tipo']})</small>
                                </label>
                            </div>";
                }
                $html .= "</div>";
            } else {
                $html .= "<div class='alert alert-warning'>No hay tipos de documento de salida configurados</div>";
            }
            echo $html;
            break;

        case "guardar_permisos_documentos":
          if (!isset($_POST["usuario_id"])) {
              echo json_encode(["status" => "error", "message" => "No se recibió ID de usuario"]);
              break;
          }

          $usuario_id = $_POST["usuario_id"];
          $documentos_seleccionados = isset($_POST["documentos"]) ? $_POST["documentos"] : [];
          $tipo_documentos = isset($_POST["tipo_documentos"]) ? $_POST["tipo_documentos"] : 'entradas';

          // Obtener permisos anteriores para el log
          $permisos_anteriores = $permisos->obtener_permisos_json($usuario_id, 'documentos');

          // Eliminar solo los permisos del tipo de documento específico (entrada o salida)
          $permisos->delete_permisos_documentos_por_tipo($usuario_id, $tipo_documentos);

          // Luego insertar los nuevos permisos
          $success = true;
          $documentos_guardados = 0;

          foreach ($documentos_seleccionados as $tipo_documento_id) {
              if ($permisos->update_permiso_documento($usuario_id, $tipo_documento_id, 'S')) {
                  $documentos_guardados++;
              } else {
                  $success = false;
              }
          }

          if ($success) {
              // Obtener permisos nuevos para el log
              $permisos_nuevos = $permisos->obtener_permisos_json($usuario_id, 'documentos');

              // Registrar en el log de auditoría
              $tipo_texto = ($tipo_documentos === 'entradas') ? 'entrada' : 'salida';
              $permisos->registrar_log_permisos([
                  'usuario_modificado' => $usuario_id,
                  'usuario_modificador' => $_SESSION["Id_Usuario"],
                  'tipo_permiso' => 'documentos_' . $tipo_texto,
                  'accion' => "Se actualizaron permisos de documentos de {$tipo_texto} para {$documentos_guardados} tipos",
                  'permisos_anteriores' => $permisos_anteriores,
                  'permisos_nuevos' => $permisos_nuevos,
                  'cantidad_permisos' => $documentos_guardados,
                  'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                  'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
              ]);

              echo json_encode([
                  "status" => "success",
                  "message" => "Permisos de documentos de {$tipo_texto} actualizados para {$documentos_guardados} tipos"
              ]);
          } else {
              echo json_encode([
                  "status" => "error",
                  "message" => "Error al actualizar algunos permisos de documentos"
              ]);
          }
          break;

        case "combo_entradas_permisos":
            // Este es el que reemplazará al combo_entradas original
            $usuario_id = $_SESSION["Id_Usuario"];
            $tipos_permitidos = $permisos->get_tipos_documento_permitidos($usuario_id);
            
            $html = "";
            if (is_array($tipos_permitidos) && count($tipos_permitidos) > 0) {
                $html .= "<option value='' disabled selected>Seleccione...</option>";
                foreach ($tipos_permitidos as $tipo) {
                    $html .= "<option value='" . $tipo['idTipoDoctos'] . "'>" . $tipo['TipoDoctos'] . "</option>";
                }
            } else {
                $html .= "<option value='' disabled selected>No tiene permisos para ningún documento</option>";
            }
            echo $html;
            break;
        
        case "combo_salidas_permisos":
            $usuario_id = $_SESSION["Id_Usuario"];
            $tipos_permitidos = $permisos->get_tipos_documento_salidas_permitidos($usuario_id);
            
            $html = "";
            if (is_array($tipos_permitidos) && count($tipos_permitidos) > 0) {
                $html .= "<option value='' disabled selected>Seleccione...</option>";
                foreach ($tipos_permitidos as $tipo) {
                    $html .= "<option value='" . $tipo['idTipoDoctos'] . "'>" . $tipo['TipoDoctos'] . "</option>";
                }
            } else {
                $html .= "<option value='' disabled selected>No tiene permisos para ningún documento</option>";
            }
            echo $html;
            break;

        default:
            echo "Operación no válida";
            break;
    }

    

       

} catch (Exception $e) {
    error_log("Error en controller/permisos: " . $e->getMessage());
    echo "Error interno del sistema";
}
?>