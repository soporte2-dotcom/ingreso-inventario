var tabla;
var usu_id = $('#user_id').val();
var rol_id = $('#rol_id').val();

let editingRow = null;
let originalData = {};

// Configuración centralizada
const CONFIG = {
    baseUrl: "../../controller/",
    endpoints: {
        permisos: "permisos.php?op=combo_salidas_permisos",
        tipodoctos: {
            consecutivos: "tipodoctos.php?op=consecutivos"
        },
        terceros: {
            combo_dir: "terceros.php?op=combo_dir",
            telefono_dir: "terceros.php?op=telefono_dir"
        },
        salidas: {
            insert_doc_salida: "../../controller/salidas.php?op=insert_doc_salida",
            guardar_salida: "salidas.php?op=guardar_salida",
            update_lote_salida: "../../controller/salidas.php?op=update_lote_salida"
        },
        documento: {
            insert_doc_entrada: "documento.php?op=insert_doc_entrada",
            asignar_selecc: "documento.php?op=asignar_selecc",
            update_prod_doc: "documento.php?op=update_prod_doc",
            guardar_entrada: "documento.php?op=guardar_entrada",
            guardar_doc: "documento.php?op=guardar_doc",
            mostrar_entrada: "documento.php?op=mostrar_entrada",
            listar_detalle_entrada: "documento.php?op=listar_detalle_entrada",
            total_entrada: "documento.php?op=total_entrada",
            totales: "documento.php?op=totales",
            total_cantidad: "documento.php?op=total_cantidad",
            mostrarXproducto: "documento.php?op=mostrarXproducto",
            duplicar_linea: "documento.php?op=duplicar_linea",
            eliminar: "documento.php?op=eliminar"
        }
    }
};

function init() {
    // Inicialización si es necesaria
}

$(document).ready(function() {
    inicializarCombos();
    inicializarEventos();
    
    // Lista de tipos que requieren OS obligatoriamente
    const tiposRestringidos = ['215', '213', '914', '938', '947'];
    window.tiposRestringidos = tiposRestringidos;
    
    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    if(tipo && consecutivo){
        listardetalle(tipo, consecutivo);
    }
});

// FUNCIONES DE UTILIDAD
function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
}

function validarCampoRequerido(valor, nombreCampo) {
    if (!valor || valor.trim() === '') {
        swal("Advertencia!", `El campo ${nombreCampo} es obligatorio`, "warning");
        return false;
    }
    return true;
}

function mostrarFeedbackExitoso(mensaje = "Cambio guardado correctamente") {
    const feedback = $(`<div class="alert alert-success alert-dismissible fade show" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <strong>✅ Éxito!</strong> ${mensaje}
    </div>`);
    
    $('body').append(feedback);
    
    setTimeout(() => {
        feedback.alert('close');
    }, 3000);
}

// FUNCIONES DE INICIALIZACIÓN
function inicializarCombos() {
    $.post(CONFIG.baseUrl + CONFIG.endpoints.permisos, function(data) {
        $('#idTipo').html(data);
    });
}

function inicializarEventos() {
    // Evento para cambio de tipo de documento
    $("#idTipo").change(function() {
        const idTipo = $(this).val();
        
        // Verificación de tipos restringidos (requieren OS)
        if (window.tiposRestringidos.includes(idTipo)) {
            $("#docref").val("0").change(); // Forzar a "No" (OS)
            $("#docref").prop('disabled', true);
            swal("Aviso!", "Este tipo de documento requiere obligatoriamente una Orden de Salida (OS).", "info");
        } else {
            $("#docref").prop('disabled', false);
        }

        $.post(CONFIG.baseUrl + CONFIG.endpoints.tipodoctos.consecutivos, { idTipo }, function(data) {
            data = JSON.parse(data);
            $("#consecutivo").val(data.consecutivo);
        });
    });

    // Eventos para Facturar A (nit1)
    $("#nit1").change(function() {
        const nit = $(this).val();
        $("#direccion1").html('<option value="" disabled selected>Seleccione...</option>');
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit }, function(data) {
            $("#direccion1").html(data);
        });
    });

    $("#direccion1").change(function() {
        const direccion = $(this).val();
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.telefono_dir, { direccion }, function(data) {
            data = JSON.parse(data);
            $("#telefono1").val(data.telefono_1);
        });
    });

    // Eventos para Enviar A (nit2)
    $("#nit2").change(function() {
        const nit = $(this).val();
        $("#direccion2").html('<option value="" disabled selected>Seleccione...</option>');
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit }, function(data) {
            $("#direccion2").html(data);
        });
    });

    // Eventos para terceros
    $("#nit3").change(function() {
        const nit = $(this).val();
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit }, function(data) {
            $("#direccion3").html(data);
        });
    });

    $("#direccion3").change(function() {
        const direccion = $(this).val();
        $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.telefono_dir, { direccion }, function(data) {
            data = JSON.parse(data);
            $("#telefono3").val(data.telefono_1);
        });
    });
}

// FUNCIONES DE GESTIÓN DE DOCUMENTOS
function crearDocumento() {
    const tipo = document.getElementById("idTipo").value;
    const consecutivo = document.getElementById("consecutivo").value;
    const numero = document.getElementById("numero").value;
    
    if (!validarCampoRequerido(tipo, "Tipo de Documento") ||
        !validarCampoRequerido(consecutivo, "Consecutivo") ||
        !validarCampoRequerido(numero, "Número")) {
        return false;
    }

    // Validación extra para tipos restringidos
    if (window.tiposRestringidos.includes(tipo)) {
        const docref = $("#docref").val();
        if (docref != "0") {
             swal("Error!", "Para este tipo de documento la base debe ser una Orden de Salida (OS)", "error");
             return false;
        }
    }
    
    $.blockUI({ message: '<h2>Cargando favor Espere...</h2>' });
    
    const formData = new FormData($("#doc_form")[0]);
    
    $.ajax({
        url: CONFIG.endpoints.salidas.insert_doc_salida,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function(response) {
            $.unblockUI();

            if (response.status === "success") {
                swal({
                    title: "Correcto!",
                    text: response.message,
                    type: "success"
                }, function() {
                    window.location.href = 'index.php?tipo='+ response.tipo +'&consecutivo='+ response.consecutivo;
                });
            } else {
                swal("Error!", response.message, "error");
                $("#btncrear").prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            $.unblockUI();
            swal("Error!", "Ha ocurrido un error al procesar la solicitud. Por favor intente nuevamente.", "error");
            console.error("Error en la petición:", error);
            $("#btncrear").prop('disabled', false);
        }
    });
    
    $("#btncrear").prop('disabled', true);
    return false;
}

function guardarLote() {
    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const lote = $('#lote1').val();

    if (!lote) {
        swal("Advertencia!", "Debe ingresar un número de lote", "warning");
        return;
    }

    $.post(CONFIG.endpoints.salidas.update_lote_salida,
        { tipo: tipo, numdoc: consecutivo, lote1: lote },
        function() {
            swal("Correcto!", "Lote asignado a todas las líneas", "success");
            $("#lot").modal('hide');
            $('#tb-doc').DataTable().ajax.reload();
            $('#lote1').val('');
        }
    );
}

function editarProducto() {
    const idproducto = document.getElementById("idproducto").value;
    const cantidad = document.getElementById("cantidad").value;
    const valorUnitario = document.getElementById("Valor_Unitario").value;
    const lote = document.getElementById("lote").value;
    const fechaVence = document.getElementById("fecha_vence").value;
    
    if (!validarCampoRequerido(idproducto, "Código de Producto") ||
        !validarCampoRequerido(cantidad, "Cantidad") ||
        !validarCampoRequerido(valorUnitario, "Valor Unitario") ||
        !validarCampoRequerido(lote, "Lote") ||
        !validarCampoRequerido(fechaVence, "Fecha de Vencimiento")) {
        return false;
    }
    
    const formData = new FormData($("#doc_form")[0]);
    
    $.ajax({
        url: CONFIG.baseUrl + CONFIG.endpoints.documento.update_prod_doc,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            $('#modalagregar').modal('hide');
            
            swal({
                title: "Correcto!", 
                text: "Registrado Correctamente", 
                type: "success",
                closeOnConfirm: true
            }, function() {
                const tipo = getUrlParameter('tipo');
                const consecutivo = getUrlParameter('consecutivo');
                
                $('#tb-doc').DataTable().ajax.reload();
                $('#cantidad, #idproducto, #Valor_Unitario, #lote, #fecha_vence').val('');
        
                actualizarTodosLosTotales(tipo, consecutivo);
            });
        },
        error: function(xhr, status, error) {
            swal("Error!", "Ha ocurrido un error al actualizar el producto. Por favor intente nuevamente.", "error");
            console.error("Error en la petición:", error);
        }
    });
}

function guardarDocumento() {
    if ($("#btnguardar").prop('disabled')) {
      swal("Advertencia!", "No se puede modificar un documento exportado", "warning");
      return false;
    }

    const sw = document.getElementById("sw").value;

    if (sw == 10) {

      const nit1 = document.getElementById("nit1").value;
      const direccion1 = document.getElementById("direccion1").value;
      const nit2 = document.getElementById("nit2").value;
      const direccion2 = document.getElementById("direccion2").value;

      if (!validarCampoRequerido(nit1, "NIT Facturar A") ||
          !validarCampoRequerido(direccion1, "Dirección Facturar A") ||
          !validarCampoRequerido(nit2, "NIT Enviar A") ||
          !validarCampoRequerido(direccion2, "Dirección Enviar A")) {
          return false;
      }

      procesarGuardado(CONFIG.endpoints.salidas.guardar_salida);

    } else if (sw == 9) {
        const nit = document.getElementById("nit3").value;
        const direccion = document.getElementById("direccion3").value;
        const traslfact1 = document.getElementById("traslfact1").value;

        if (!validarCampoRequerido(nit, "NIT/Cédula") ||
            !validarCampoRequerido(direccion, "Dirección") ||
            !validarCampoRequerido(traslfact1, "Despacho")) {
            return false;
        }

        procesarGuardado(CONFIG.endpoints.documento.guardar_entrada);
    } else {
        procesarGuardado(CONFIG.endpoints.documento.guardar_doc);
    }
}

function procesarGuardado(endpoint) {
    $.blockUI({ message: '<h2>Guardando por favor Espere...</h2>' });
    
    const formData = new FormData($("#doc_form")[0]);
    
    $.ajax({
        url: CONFIG.baseUrl + endpoint,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(datos) {                  
            swal({
                title: "Correcto!", 
                text: "Documento Registrado Correctamente", 
                type: "success"
            }, function() {
                window.location.href = 'index.php'; 
            });
            console.log(datos);
        },
        complete: function() {
            $.unblockUI();
        },
        error: function(xhr, status, error) {
            $.unblockUI();
            swal("Error!", "Ha ocurrido un error al guardar el documento. Por favor intente nuevamente.", "error");
            console.error("Error en la petición:", error);
        }
    });
}

// FUNCIONES DE INTERFAZ
function showInp(){
    const getSelectValue = document.getElementById("docref").value;
    const displayStyle = getSelectValue == "0" ? "none" : "inline-block";
    
    document.getElementById("txt_tipoDocRef").style.display = displayStyle;
    document.getElementById("tipoDocRef").style.display = displayStyle;
}

function configurarInterfazParaDocumentoExistente(data) {
    const elementosOcultar = [
        "idTipo", "consecutivo", "numero", "docref", "fecha",
        "txt_idTipo", "txt_consecutivo", "txt_numero", "txt_docref", "txt_fecha"
    ];
    
    elementosOcultar.forEach(id => {
        document.getElementById(id).style.display = "none";
        if (id === "idTipo" || id === "numero" || id === "docref") {
            document.getElementById(id).removeAttribute("required");
        }
    });

    document.getElementById("btncrear").style.display = "none";

    const elementosMostrar = [
        "txt_tipodoc", "txt_numdoc", "txt_fecha1", "txt_pedido1",
        "txt_traslfact1", "tipodoc", "numdoc",
        "fecha1", "pedido1", "traslfact1", "div_dotacion", "btnlot", "btnguardar"
    ];
    
    elementosMostrar.forEach(id => {
        document.getElementById(id).style.display = "inline-block";
    });

    // Configuración específica según tipo de documento
    if(data.Tipo_Docto_Base_2 == 9){
        mostrarCamposEntrada();
    } else {
        mostrarCamposTraslado();
    }

    // Configurar estado de exportación
    configurarEstadoExportado(data.exportado);
}

function mostrarCamposEntrada() {
    const camposEntrada = [
        "nit1", "nombre1", "direccion1", "telefono1",
        "hr1", "hr2", "hr3",
        "txt_nit3", "txt_nombre3", "txt_direccion3", "txt_telefono3",
        "nit3", "nombre3", "direccion3", "telefono3", "btnlot"
    ];
    
    camposEntrada.forEach(id => {
        document.getElementById(id).style.display = "inline-block";
    });
    
    document.getElementById("traslfact1").disabled = false;
}

function mostrarCamposTraslado() {
    const camposTraslado = [
        "nit1", "nombre1", "direccion1", "telefono1",
        "nit2", "nombre2", "direccion2",
        "txt_nit1", "txt_nombre1", "txt_direccion1", "txt_telefono1",
        "txt_nit2", "txt_nombre2", "txt_direccion2",
        "hr1", "hr2", "hr3", "btnlot"
    ];

    camposTraslado.forEach(id => {
        document.getElementById(id).style.display = "inline-block";
    });

    document.getElementById("traslfact1").disabled = false;
}

function configurarEstadoExportado(exportado) {
    if(exportado === 'S') {
        $("#btnguardar, #btnlot").prop('disabled', true).addClass('btn-disabled');
        $("#btnguardar").html('Documento Exportado')
                       .attr('title', 'No se puede modificar un documento exportado');

        const camposEditables = ['nit1', 'nombre1', 'direccion1', 'telefono1',
                                 'nit2', 'nombre2', 'direccion2',
                                 'nit3', 'nombre3', 'direccion3', 'telefono3',
                                 'traslfact1', 'dotacion_epp', 'notas'];
        camposEditables.forEach(id => {
            const el = document.getElementById(id);
            if(el) el.disabled = true;
        });

        window.documentoExportado = true;
    } else {
        window.documentoExportado = false;
        $("#btnguardar").prop('disabled', false)
                       .removeClass('btn-disabled')
                       .html('Guardar')
                       .removeAttr('title');
    }
}

// FUNCIONES DE GESTIÓN DE DATOS
function actualizarTodosLosTotales(tipo, consecutivo) {
    console.log('Actualizando todos los totales...');
    
    const operaciones = [
        {
            op: 'total_entrada', 
            elemento: '#total',
            propiedad: 'total',
            descripcion: 'Subtotal del documento'
        },
        {
            op: 'totales', 
            callback: function(data) {
                try {
                    const dataTotales = JSON.parse(data);
                    $('#valorTotal').text(dataTotales.valorTotal || '0');
                    $('#totalImpuesto').text(dataTotales.totalImpuesto || '0');
                    $('#totalDescuento').text(dataTotales.totalDescuento || '0');
                    console.log('Totales generales actualizados');
                } catch(e) {
                    console.error('Error parseando totales:', data);
                }
            },
            descripcion: 'Totales generales (IVA, descuentos, total)'
        },
        {
            op: 'total_cantidad', 
            elemento: '#totalCantidad',
            propiedad: 'totalCantidad',
            descripcion: 'Cantidad total de items'
        }
    ];
    
    let completadas = 0;
    const totalOperaciones = operaciones.length;
    
    operaciones.forEach(function(operacion) {
        $.post(`${CONFIG.baseUrl}documento.php?op=${operacion.op}`, 
               {tipo: tipo, consecutivo: consecutivo}, 
               function(data) {
                   completadas++;
                   
                   try {
                       if (operacion.callback) {
                           operacion.callback(data);
                       } else {
                           const resultado = JSON.parse(data);
                           const valor = resultado[operacion.propiedad] || '0';
                           $(operacion.elemento).text(valor);
                       }
                       console.log(`✅ ${operacion.descripcion} actualizado`);
                   } catch(e) {
                       console.error(`❌ Error en ${operacion.op}:`, data);
                   }
                   
                   if (completadas === totalOperaciones) {
                       console.log('🎉 Todos los totales han sido actualizados');
                   }
               }
        ).fail(function(xhr, status, error) {
            console.error(`❌ Error en petición ${operacion.op}:`, error);
            completadas++;
        });
    });
}

function listardetalle(tipo, consecutivo){
    // Cargar datos del documento
    $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.mostrar_entrada, 
           { tipo, consecutivo }, 
           function (data) {
        data = JSON.parse(data);
        console.log(data);
        
        // Llenar campos del formulario
        $('#tipo').val(data.tipo);
        $('#tipodoc').val(data.TipoDoctos);
        $('#numdoc').val(data.Numero_documento);
        $('#pedido1').val(data.Numero_Docto_Base_2);
        $('#traslfact1').val(data.Numero_Docto_Base);        
        $('#nit1').val(data.nit_Cedula);
        $('#nombre1').val(data.Nombre_Cliente);
        $('#telefono1').val(data.telefono_1);
        if(data.nit_Cedula) {
            $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: data.nit_Cedula }, function(html) {
                $('#direccion1').html(html);
                $('#direccion1 option').each(function() {
                    if($(this).val().split(',')[0].trim() == String(data.codigo_direccion).trim()) {
                        $(this).prop('selected', true);
                        return false;
                    }
                });
            });
        }
        $('#nit2').val(data.nit_Cedula_2);
        $('#nombre2').val(data.nombre2);
        if(data.nit_Cedula_2) {
            $.post(CONFIG.baseUrl + CONFIG.endpoints.terceros.combo_dir, { nit: data.nit_Cedula_2 }, function(html) {
                $('#direccion2').html(html);
                $('#direccion2 option').each(function() {
                    if($(this).val().split(',')[0].trim() == String(data.codigo_direccion_2).trim()) {
                        $(this).prop('selected', true);
                        return false;
                    }
                });
            });
        }
        $('#notas').html(data.notas);
        $('#sw').val(data.Tipo_Docto_Base_2);
        $('#dotacion_epp').prop('checked', data.IdVendedor == 12);

        if(data !== null){
            configurarInterfazParaDocumentoExistente(data);
        }      

    });

    actualizarTodosLosTotales(tipo, consecutivo);

    //Limpiar cualquier estado de edición pendiente al cargar la tabla
    if (editingRow) {
        cancelarEdicionNativa();
    }
        
    // Configurar DataTable
    tabla = $('#tb-doc').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "paging": false,
        "ordering": false,
        dom: 'Bfrtip',
        "searching": false,
        lengthChange: false,
        colReorder: false,
        buttons: [],
        "ajax": {
            url: CONFIG.baseUrl + CONFIG.endpoints.documento.listar_detalle_entrada,
            type: "post",
            dataType: "json",
            data: { tipo, consecutivo },
            error: function(consecutivo) {
                console.log(consecutivo.responseText);
            }
        },
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
        "iDisplayLength": 70,
        "autoWidth": false,
        "createdRow": function(row, data, dataIndex) {
            $('td', row).eq(4).addClass('editable-cell'); // Cantidad
            $('td', row).eq(7).addClass('editable-cell'); // Lote
            $('td', row).eq(9).addClass('editable-cell'); // Nota
        },
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    }).DataTable();

    // Agregar eventos de edición
    agregarEventosEdicionInline();

    setTimeout(() => {
        agregarEventosEdicionInline();
    }, 500);
}

// FUNCIONES DE EDICIÓN INLINE (MANTENIENDO LA VERSIÓN NATIVA ORIGINAL)
function agregarEventosEdicionInline() {
    console.log('Agregando eventos de edición inline (nativo)...');
    
    // SOLUCIÓN: Usar event delegation para todos los eventos
    const table = document.getElementById('tb-doc');
    
    // Limpiar eventos anteriores para evitar duplicados
    table.removeEventListener('dblclick', manejarDobleClic);
    table.addEventListener('dblclick', manejarDobleClic);
    
    // Evento para eliminar con delegation
    document.removeEventListener('click', manejarEliminarGlobal);
    document.addEventListener('click', manejarEliminarGlobal);
}

// Función separada para manejar doble clic
function manejarDobleClic(e) {
    if (window.documentoExportado) return;
    if (e.target.classList.contains('editable-cell')) {
        iniciarEdicionNativa(e.target);
    }
}

// Función separada para manejar eliminación global
function manejarEliminarGlobal(e) {
    if (e.target.closest('.btn-eliminar')) {
        e.preventDefault();
        const row = e.target.closest('tr');
        const tipo = getUrlParameter('tipo');
        const consecutivo = getUrlParameter('consecutivo');
        const seq = row.cells[0].textContent.trim();
        const producto = row.cells[1].textContent.trim();
        
        console.log(' Eliminando producto:', producto);
        eliminar(tipo, consecutivo, producto, seq);
    }
}

function iniciarEdicionNativa(cell) {
    console.log('🎬 Iniciando edición (nativo)...');
    
    // Cancelar edición anterior si existe
    if (editingRow) {
        console.log('🔄 Cancelando edición anterior...');
        cancelarEdicionNativa();
    }

    const row = cell.closest('tr');
    const cellIndex = cell.cellIndex;
    const currentValue = cell.textContent.trim();
    
    editingRow = row;
    originalData = {
        value: currentValue,
        cell: cell,
        index: cellIndex
    };

    row.classList.add('editing');
    
    let input;
    switch(cellIndex) {
        case 4: // Cantidad
        case 5: // % Desc
        case 6: // Valor
            input = document.createElement('input');
            input.type = 'number';
            input.value = currentValue;
            input.step = cellIndex === 6 ? '0.01' : '1';
            break;
        case 9: // Nota - permitir vacío
            input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
            input.placeholder = "Nota opcional...";
            break;
        default: // Lote y otros campos
            input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
    }
    
    input.className = 'edit-input';

    // 🔥 GUARDAR el valor original en un data attribute por si necesitamos restaurarlo
    cell.dataset.originalValue = currentValue;
    
    cell.innerHTML = '';
    cell.appendChild(input);
    input.focus();
    input.select();

    const actionsCell = row.cells[11];
    actionsCell.innerHTML = `
        <div class="edit-actions">
            <button type="button" class="btn btn-success btn-sm btn-action btn-guardar" title="Guardar cambios">
                <i class="fa fa-check"></i>
            </button>
            <button type="button" class="btn btn-danger btn-sm btn-action btn-cancelar" title="Cancelar edición">
                <i class="fa fa-times"></i>
            </button>
            <button type="button" class="btn btn-info btn-sm btn-action btn-duplicar" title="Duplicar línea">
                <i class="fa fa-copy"></i>
            </button>
            <button type="button" class="btn btn-warning btn-sm btn-action btn-eliminar" title="Eliminar registro">
                <i class="fa fa-trash"></i>
            </button>
        </div>
    `;

    // ASIGNAR EVENTOS DIRECTAMENTE a los botones de esta fila específica
    actionsCell.querySelector('.btn-guardar').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('💾 Click en guardar');
        guardarEdicionNativa(row);
    });

    actionsCell.querySelector('.btn-cancelar').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('❌ Click en cancelar');
        cancelarEdicionNativa();
    });

    actionsCell.querySelector('.btn-duplicar').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('📋 Click en duplicar');
        const tipo = getUrlParameter('tipo');
        const consecutivo = getUrlParameter('consecutivo');
        const seq = row.cells[0].textContent.trim();
        const producto = row.cells[1].textContent.trim();
        duplicarLinea(tipo, consecutivo, producto, seq);
    });

    actionsCell.querySelector('.btn-eliminar').addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('🗑️ Click en eliminar');
        const tipo = getUrlParameter('tipo');
        const consecutivo = getUrlParameter('consecutivo');
        const seq = row.cells[0].textContent.trim();
        const producto = row.cells[1].textContent.trim();
        eliminar(tipo, consecutivo, producto, seq);
    });

    // Enter para guardar, ESC para cancelar
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            console.log('↵ Enter presionado - Guardando');
            guardarEdicionNativa(row);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            console.log('⎋ ESC presionado - Cancelando');
            cancelarEdicionNativa();
        }
    });

    console.log('✅ Edición iniciada correctamente (nativo)');
}

function guardarEdicionNativa(row) {
    console.log('💾 Iniciando guardado (nativo)...');
    
    if (!editingRow || !originalData.cell) {
        console.error('❌ No hay edición activa');
        return;
    }

    const cell = originalData.cell;
    const input = cell.querySelector('.edit-input');
    const newValue = input.value.trim();
    const cellIndex = originalData.index;

    // Validación básica - EXCLUIR el campo de notas (índice 9)
    if (!newValue && cellIndex !== 9) { // El campo 9 es "Nota"
        swal("Advertencia!", "El campo no puede estar vacío", "warning");
        input.focus();
        return;
    }

    row.classList.add('saving');

    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');
    const producto = row.cells[1].textContent.trim();
    const seq = row.cells[0].textContent.trim();

    console.log('📤 Enviando datos:', { tipo, consecutivo, producto, seq, campo: cellIndex, valor: newValue });

    const formData = new FormData();
    formData.append('tipo', tipo);
    formData.append('consecutivo', consecutivo);
    formData.append('producto', producto);
    formData.append('seq', seq); 
    
    switch(cellIndex) {
        case 4: formData.append('cantidad', newValue); break;
        case 5: formData.append('descuento', newValue); break;
        case 6: formData.append('valor', newValue); break;
        case 7: formData.append('lote', newValue); break;
        case 8: formData.append('fecha_vence', newValue); break;
        case 9: formData.append('nota', newValue); break; // Nota puede ser vacía
        case 10: formData.append('unidades', newValue); break;
    }

    fetch(CONFIG.baseUrl + CONFIG.endpoints.documento.update_prod_doc, {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(response => {
        console.log('📥 Respuesta del servidor:', response);
        
        if (response && response.trim() === "success") {
            // 🔥 CORRECIÓN: Primero actualizar el contenido de la celda
            cell.textContent = newValue || '';
            
            // 🔥 CORRECIÓN: Limpiar inmediatamente el estado de edición
            row.classList.remove('editing', 'saving');
            limpiarEstadoEdicionNativa();
            
            mostrarFeedbackExitoso();
            actualizarTodosLosTotales(tipo, consecutivo);
            
            console.log('✅ Cambio guardado exitosamente');
            
        } else {
            row.classList.remove('saving');
            swal("Error!", "No se pudo guardar el cambio en el servidor. Respuesta: " + response, "error");
            console.error("Respuesta inesperada del servidor:", response);
            cancelarEdicionNativa();
        }
    })
    .catch(error => {
        row.classList.remove('saving');
        swal("Error!", "No se pudo guardar el cambio: " + error, "error");
        console.error("Error en la petición:", error);
        cancelarEdicionNativa();
    });
}

function limpiarEstadoEdicionNativa() {
    console.log('🧹 Limpiando estado de edición (nativo)...');
    
    if (editingRow) {
        const actionsCell = editingRow.cells[11];
        
        // 🔥 IMPORTANTE: Limpiar eventos antes de modificar el HTML
        const botonesAnteriores = actionsCell.querySelectorAll('button');
        botonesAnteriores.forEach(btn => {
            const clonBtn = btn.cloneNode(true);
            btn.parentNode.replaceChild(clonBtn, btn);
        });
        
        // Restaurar botones básicos
        actionsCell.innerHTML = `
            <div class="edit-actions">
                <button type="button" class="btn btn-info btn-sm btn-action btn-duplicar" title="Duplicar línea">
                    <i class="fa fa-copy"></i>
                </button>
                <button type="button" class="btn btn-warning btn-sm btn-action btn-eliminar" title="Eliminar registro">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        `;
        
        const celdasEditables = editingRow.querySelectorAll('.editable-cell');
        celdasEditables.forEach(celda => {
            if (celda.querySelector('input')) {
                const input = celda.querySelector('input');
                celda.textContent = celda.dataset.originalValue || input.value || '';
            }
        });
        
        editingRow.classList.remove('editing', 'saving', 'saved');
    }
    
    editingRow = null;
    originalData = {};
    
    console.log('✅ Estado de edición limpiado (nativo)');
}

// función para reconectar eventos después de guardar
function reconectarEventosDespuesDeGuardar() {
    setTimeout(() => {
        console.log('🔄 Reconectando eventos después de guardar...');
        agregarEventosEdicionInline();
    }, 100);
}


function cancelarEdicionNativa() {
    if (editingRow && originalData.cell) {
        originalData.cell.textContent = originalData.value;
        editingRow.classList.remove('editing', 'saving');
        limpiarEstadoEdicionNativa();
    }
}

// FUNCIONES DE GESTIÓN DE REGISTROS
function editar(tipo, consecutivo, producto){
    $('#mdltitulo').html('Editar Registro');   

    $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.mostrarXproducto, 
           {tipo, consecutivo, producto}, 
           function (data) {
        data = JSON.parse(data);
        
        $('#idproducto').val(data.IdProducto);
        $('#Producto').val(data.Producto);
        $('#cantidad').val(data.Cantidad_Facturada);
        $('#Valor_Unitario').val(data.Valor_Unitario);
        $('#lote').val(data.Numero_Lote);
        $('#fecha_vence').val(data.Fecha_Vence);
        
        console.log("producto", data);
    });   

    $('#modalagregar').modal('show');
}

function duplicarLinea(tipo, consecutivo, producto, seq) {
    console.log('🔄 Duplicando línea:', { tipo, consecutivo, producto, seq });
    
    swal({
        title: "¿Duplicar línea?",
        text: "Esta acción creará una copia idéntica de esta línea de producto.",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#17a2b8",
        confirmButtonText: "Sí, duplicar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false,
        showLoaderOnConfirm: true
    }, function(isConfirm) {
        if (isConfirm) {
            console.log('📤 Enviando petición de duplicación...');
            
            // 🔥 IMPORTANTE: Cancelar cualquier edición activa antes de duplicar
            if (editingRow) {
                cancelarEdicionNativa();
            }
            
            $.blockUI({ message: '<h2>Duplicando línea...</h2>' });
            
            $.ajax({
                url: CONFIG.baseUrl + CONFIG.endpoints.documento.duplicar_linea,
                type: "POST",
                data: {
                    tipo: tipo,
                    consecutivo: consecutivo,
                    producto: producto,
                    seq: seq
                },
                dataType: "json",
                success: function(response) {
                    console.log('📥 Respuesta del servidor:', response);
                    $.unblockUI();
                    
                    if (response.status === "success") {
                        swal({
                            title: "¡Correcto!",
                            text: response.message,
                            type: "success",
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // 🔥 CORRECIÓN: Recargar la tabla y reconectar eventos
                        $('#tb-doc').DataTable().ajax.reload(function() {
                            // Después de recargar, reconectar eventos
                            setTimeout(() => {
                                agregarEventosEdicionInline();
                            }, 300);
                        });
                        
                        actualizarTodosLosTotales(tipo, consecutivo);
                        
                    } else {
                        swal("Error!", response.message || "No se pudo duplicar la línea", "error");
                    }
                },
                error: function(xhr, status, error) {
                    console.error('❌ Error en petición:', error);
                    console.error('❌ Status:', status);
                    console.error('❌ Response:', xhr.responseText);
                    
                    $.unblockUI();
                    swal("Error!", "Error al duplicar la línea: " + error, "error");
                }
            });
        }
    });
}

function eliminar(tipo, consecutivo, producto, seq){
    const row = Array.from(document.querySelectorAll('#tb-doc tbody tr')).find(tr => {
        return tr.cells[0].textContent.trim() === seq && 
               tr.cells[1].textContent.trim() === producto;
    });
    
    let nombreProducto = '';
    let cantidad = '';
    let lote = '';
    
    if (row) {
        nombreProducto = row.cells[2].textContent.trim();
        cantidad = row.cells[4].textContent.trim();
        lote = row.cells[7].textContent.trim();
    }
    
    const mensaje = `
        <div style="text-align: left; padding: 10px;">
            <p><strong>Código:</strong> ${producto}</p>
            <p><strong>Producto:</strong> ${nombreProducto}</p>
            <p><strong>Cantidad:</strong> ${cantidad}</p>
            <p><strong>Lote:</strong> ${lote}</p>
            <p><strong>Seq:</strong> ${seq}</p>
            <br>
            <p style="color: #d9534f; font-weight: bold;">⚠️ Esta acción no se puede deshacer</p>
        </div>
    `;
    
    swal({
        title: "¿Eliminar este producto?",
        text: mensaje,
        html: true,
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "No, cancelar",
        closeOnConfirm: false
    },
    function(isConfirm) {
        if (isConfirm) {
            swal({
                title: "Eliminando...",
                text: "Por favor espere",
                type: "info",
                showConfirmButton: false
            });
            
            $.post(CONFIG.baseUrl + CONFIG.endpoints.documento.eliminar, {
                tipo: tipo, 
                consecutivo: consecutivo, 
                producto: producto, 
                seq: seq
            }, function (data) {
                console.log(data);
                
                if (data.includes("success") || data.includes("Eliminado correctamente")) {
                    swal({
                        title: "¡Eliminado!",
                        text: "El producto ha sido eliminado correctamente",
                        type: "success",
                        confirmButtonClass: "btn-success"
                    }, function(){
                        $('#tb-doc').DataTable().ajax.reload();
                        actualizarTodosLosTotales(tipo, consecutivo);
                    });
                } else {
                    swal({
                        title: "Error",
                        text: "No se pudo eliminar el producto. Intente nuevamente.",
                        type: "error",
                        confirmButtonClass: "btn-danger"
                    });
                }
            }).fail(function() {
                swal({
                    title: "Error",
                    text: "Error de conexión. No se pudo eliminar el producto.",
                    type: "error",
                    confirmButtonClass: "btn-danger"
                });
            });
        }
    });
}

// EVENT HANDLERS
$(document).on("click", "#btncrear", function(event) {
    event.preventDefault();
    crearDocumento();
    return false;
});

$(document).on("click", "#btnlote", function(){
    guardarLote();
});

$(document).on("click", "#btneditar", function(event) {
    event.preventDefault();
    editarProducto();
});

$(document).on("click", "#btnguardar", function() {
    guardarDocumento();
});

init();