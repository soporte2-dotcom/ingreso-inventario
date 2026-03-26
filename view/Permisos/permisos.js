$(document).ready(function() {   
    // Variable para controlar el tipo de permisos
    var tipoPermisosActual = 'modulos';

    // Configurar botones de toggle
    $("#btn_permisos_modulos").click(function() {
        tipoPermisosActual = 'modulos';
        
        // Remover active de todos y agregarlo solo al clickeado
        $("#btn_permisos_modulos").addClass('active');
        $("#btn_permisos_documentos").removeClass('active');
        
        // Si ya hay un usuario seleccionado, recargar permisos
        let usuario_id = $("#usuario_id").val();
        if (usuario_id) {
            cargarPermisosUsuario(usuario_id);
        }
    });

    $("#btn_permisos_documentos").click(function() {
        tipoPermisosActual = 'documentos';
        
        // Remover active de todos y agregarlo solo al clickeado
        $("#btn_permisos_documentos").addClass('active');
        $("#btn_permisos_modulos").removeClass('active');
        
        // Si ya hay un usuario seleccionado, recargar permisos
        let usuario_id = $("#usuario_id").val();
        if (usuario_id) {
            cargarPermisosUsuario(usuario_id);
        }
    });

    // Buscar usuario al escribir
    $("#buscar_usuario").on('input', function() {
        let busqueda = $(this).val();
        if (busqueda.length >= 2) {
            buscarUsuarios(busqueda);
        } else {
            $("#resultados_busqueda").hide().html('');
        }
    });

    // Buscar usuario con botón
    $("#btn_buscar").click(function() {
        let busqueda = $("#buscar_usuario").val();
        if (busqueda.length >= 2) {
            buscarUsuarios(busqueda);
        } else {
            swal("Advertencia!", "Escribe al menos 2 caracteres para buscar", "warning");
        }
    });

    // Ocultar resultados al hacer clic fuera
    $(document).click(function(e) {
        if (!$(e.target).closest('#buscar_usuario, #resultados_busqueda').length) {
            $("#resultados_busqueda").hide();
        }
    });

    // Función para buscar usuarios
    function buscarUsuarios(busqueda) {
        $("#resultados_busqueda").html('<div class="no-results">Buscando...</div>').show();
        
        $.post("../../controller/permisos.php?op=buscar_usuario", 
            { busqueda: busqueda }, 
            function(data) {
                if (data && data.trim() !== '') {
                    $("#resultados_busqueda").html(data).show();
                } else {
                    $("#resultados_busqueda").html('<div class="no-results">No se encontraron resultados</div>').show();
                }
            }
        ).fail(function(xhr, status, error) {
            $("#resultados_busqueda").html('<div class="no-results">Error en la búsqueda: ' + error + '</div>').show();
            console.error("Error en búsqueda:", error);
        });
    }

    // Seleccionar usuario de los resultados
    $(document).on("click", ".usuario-item", function() {
        let usuario_id = $(this).data('usuario-id');
        let usuario_text = $(this).text().trim();
        
        $("#buscar_usuario").val(usuario_text);
        $("#usuario_id").val(usuario_id);
        $("#resultados_busqueda").hide().html('');
        
        // Cargar permisos del usuario seleccionado
        cargarPermisosUsuario(usuario_id);
    });

    // Función para cargar permisos del usuario
    function cargarPermisosUsuario(usuario_id) {
        $("#usuario_seleccionado").html('<div class="text-center"><span class="glyphicon glyphicon-refresh glyphicon-spin"></span> Cargando permisos...</div>').show();
        
        var operacion = (tipoPermisosActual === 'documentos') ? 'cargar_permisos_documentos' : 'cargar_permisos';
        
        $.post("../../controller/permisos.php?op=" + operacion, 
            { usuario_id: usuario_id }, 
            function(data) {
                $("#usuario_seleccionado").html(data);
                
                // Agregar botón de guardar después de cargar los permisos
                if (!$("#btn_guardar_container").length) {
                    var textoBoton = (tipoPermisosActual === 'documentos') ? 
                        'Guardar Permisos de Documentos' : 'Guardar Permisos';
                    
                    $("#usuario_seleccionado").append(`
                        <div class="row" id="btn_guardar_container">
                            <div class="col-lg-12 text-right" style="margin-top: 20px;">
                                <button type="button" id="btn_guardar" class="btn btn-rounded btn-inline btn-success">
                                    <span class="glyphicon glyphicon-floppy-disk"></span> ${textoBoton}
                                </button>
                                <button type="button" id="btn_limpiar" class="btn btn-rounded btn-inline btn-default">
                                    <span class="glyphicon glyphicon-remove"></span> Limpiar
                                </button>
                            </div>
                        </div>
                    `);
                }
                
                // Configurar eventos del checkbox "Seleccionar Todos"
                configurarSeleccionarTodos();
            }
        ).fail(function(xhr, status, error) {
            $("#usuario_seleccionado").html('<div class="alert alert-danger">Error al cargar los permisos: ' + error + '</div>');
            console.error("Error cargando permisos:", error);
        });
    }

    // Función para configurar el checkbox "Seleccionar Todos"
    function configurarSeleccionarTodos() {
        // Para módulos
        $(document).off('change', '#select_all_modulos').on('change', '#select_all_modulos', function() {
            var isChecked = $(this).prop('checked');
            $('.modulos-list input[type="checkbox"]').prop('checked', isChecked);
            console.log('Módulos todos ' + (isChecked ? 'marcados' : 'desmarcados'));
        });
        
        // Para documentos
        $(document).off('change', '#select_all_documentos').on('change', '#select_all_documentos', function() {
            var isChecked = $(this).prop('checked');
            $('.documentos-list input[type="checkbox"]').prop('checked', isChecked);
            console.log('Documentos todos ' + (isChecked ? 'marcados' : 'desmarcados'));
        });
        
        // Actualizar estado del "Seleccionar Todos" cuando se marca/desmarca individual
        $('.modulos-list').off('change', 'input[type="checkbox"]').on('change', 'input[type="checkbox"]', function() {
            actualizarEstadoSelectAll('.modulos-list', '#select_all_modulos');
        });
        
        $('.documentos-list').off('change', 'input[type="checkbox"]').on('change', 'input[type="checkbox"]', function() {
            actualizarEstadoSelectAll('.documentos-list', '#select_all_documentos');
        });
        
        // Verificar estado inicial
        actualizarEstadoSelectAll('.modulos-list', '#select_all_modulos');
        actualizarEstadoSelectAll('.documentos-list', '#select_all_documentos');
    }
    
    // Función auxiliar para actualizar el estado del checkbox "Seleccionar Todos"
    function actualizarEstadoSelectAll(containerSelector, checkboxSelector) {
        var $container = $(containerSelector);
        var $checkboxes = $container.find('input[type="checkbox"]').not(checkboxSelector);
        var total = $checkboxes.length;
        var checked = $checkboxes.filter(':checked').length;
        
        if (total > 0) {
            $(checkboxSelector).prop('checked', checked === total);
            // Opcional: estado indeterminado si algunos están marcados
            $(checkboxSelector).prop('indeterminate', checked > 0 && checked < total);
        }
    }

    // Guardar permisos
    $(document).on("click", "#btn_guardar", function() {
        let usuario_id = $("#usuario_id").val();
        let formData = new FormData($("#permisos_form")[0]);

        if (!usuario_id) {
            swal("Advertencia!", "Selecciona un usuario primero", "warning");
            return;
        }

        var operacion = (tipoPermisosActual === 'documentos') ? 'guardar_permisos_documentos' : 'guardar_permisos';
        var texto = (tipoPermisosActual === 'documentos') ? 'permisos de documentos' : 'permisos';

        swal({
            title: "¿Guardar " + texto + "?",
            text: "Se actualizarán los " + texto + " para: " + $("#buscar_usuario").val(),
            type: "warning",
            showCancelButton: true,
            confirmButtonClass: "btn-success",
            confirmButtonText: "Si, guardar",
            cancelButtonText: "Cancelar",
            closeOnConfirm: false
        }, function(isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: "../../controller/permisos.php?op=" + operacion,
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        try {
                            response = JSON.parse(response);
                            if (response.status == "success") {
                                swal("¡Correcto!", response.message, "success");
                            } else {
                                swal("¡Error!", response.message, "error");
                            }
                        } catch (e) {
                            console.error("Error parsing response:", response);
                            swal("¡Error!", "Respuesta inválida del servidor", "error");
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error guardando permisos:", error);
                        swal("¡Error!", "Error de conexión: " + error, "error");
                    }
                });
            }
        });
    });

    // Limpiar búsqueda
    $(document).on("click", "#btn_limpiar", function() {
        $("#buscar_usuario").val('');
        $("#usuario_id").val('');
        $("#usuario_seleccionado").hide().html('');
        $("#resultados_busqueda").hide().html('');
    });

});