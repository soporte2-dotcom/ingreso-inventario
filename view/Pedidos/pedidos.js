var tabla;
var usu_id =  $('#user_idx').val();
var rol_id =  $('#rol_idx').val();

// Selección de líneas para borrado masivo, independiente de la página del paginador
// (DataTables solo mantiene en el DOM las filas de la página visible, así que el
// estado de los checkboxes se guarda aparte por seq y se reaplica en cada draw).
var seleccionLineas = {}; // { seq: producto }


function init(){

    $("#doc_form").on("click","#btnguardar",function(e){
        guardar(e);
    });

}

$(document).ready(function() {

    $.post("../../controller/permisos.php?op=combo_pedidos_permisos",function(data, status){
       $('#idTipo').html(data);
    });

    $("#idTipo").change(function () {

        $("#idTipo option:selected").each(function () {
            idTipo = $(this).val();
            $.post("../../controller/tipodoctos.php?op=consecutivos", { idTipo : idTipo }, function(data){
                data = JSON.parse(data);
               $("#consecutivo").val(data.consecutivo);

               console.log(data.consecutivo);
            });
        });
    });

    $("#nit").change(function () {

        $("#nit").each(function () {
            nit = $(this).val();
            $.post("../../controller/terceros.php?op=combo_dir", { nit : nit }, function(data){
               $("#direccion").html(data);
            });
        });
    });

    $("#direccion").change(function () {

        $("#direccion option:selected").each(function () {
            direccion = $(this).val();
            $.post("../../controller/terceros.php?op=telefono_dir", { direccion : direccion}, function(data){
                data = JSON.parse(data);
               $("#telefono").val(data.telefono_1);
            });
        });
    });

    var tipo =  getUrlParameter('tipo');
    var consecutivo =  getUrlParameter('consecutivo');
    listardetalle(tipo, consecutivo);



});

var getUrlParameter = function getUrlParameter(sParam) {
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
};

$(document).on("click","#btncrear", function(){

    let tipo = document.getElementById("idTipo").value;
    let nit = document.getElementById("nit").value;
    let direccion = document.getElementById("direccion").value;
    let formData = new FormData($("#doc_form")[0]);

    if ((tipo =='') || (nit =='') || (direccion =='')){
        swal("Advertencia!", "Campos Vacios", "warning");
        return;
    }

    let $btn = $(this);
    $btn.prop("disabled", true);

    $.ajax({
        url: "../../controller/documento.php?op=insert_doc",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function(datos){
            let response;
            try {
                response = JSON.parse(datos);
            } catch (e) {
                $btn.prop("disabled", false);
                swal("Error!", "Respuesta inválida del servidor", "error");
                return;
            }

            if (response.status !== "success") {
                $btn.prop("disabled", false);
                swal("Error!", response.message || "No se pudo crear el documento", "error");
                return;
            }

            swal({
                title: "Correcto!",
                text: "Documento Registrado Correctamente",
                type: "success"
            }, function(){
                window.location.href = 'index.php?tipo='+ response.tipo +'&consecutivo='+ response.consecutivo;
            });
        },
        error: function(){
            $btn.prop("disabled", false);
            swal("Error!", "Error de comunicación con el servidor", "error");
        }
    });
});

$("#numerodocumento").keypress(function(event) {
    if (event.keyCode === 13) {
        $("#btnagregar").click();
    }
});

$(document).on("click","#btnagregar", function(){

    var tipo =  getUrlParameter('tipo');
    var consecutivo =  getUrlParameter('consecutivo');
    let idproducto = document.getElementById("idproducto").value;
    let cantidad = document.getElementById("cantidad").value;

    var formData = new FormData($("#doc_form")[0]);

    if ((idproducto =='') || (cantidad =='')){
        swal("Advertencia!", "Campos Vacios", "warning");
    }else{

        $.post("../../controller/productos.php?op=consultar", { idproducto : idproducto}, function(data){
            data = JSON.parse(data);
            console.log(data);
            if(data.producto == 0){
                swal("Advertencia!", "Producto No Existe", "warning");
            }else{

                $.ajax({
                    url: "../../controller/documento.php?op=insert_detalle",
                    type: "POST",
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(datos){

                        $('#cantidad').val('');
                        $('#idproducto').val('');
                        $('#producto').val('');
                        swal("Correcto!", "Registrado Correctamente", "success");
                        listardetalle(tipo, consecutivo);
                        console.log(datos);


                    }
                });
            }



        });
    }


});


$(document).on("click","#btnguardar", function(){

    var totalProductos = $('#tb-doc').DataTable().rows().count();
    if (totalProductos === 0) {
        swal("Advertencia!", "Debe agregar al menos un producto antes de guardar el documento.", "warning");
        return;
    }

    var formData = new FormData($("#doc_form")[0]);
        $.ajax({
            url: "../../controller/documento.php?op=guardar_doc",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(datos){
                if (String(datos).indexOf('Actualizado Correctamente') === -1) {
                    swal("No se pudo guardar", String(datos).trim(), "error");
                    return;
                }
                swal({
                    title: "Correcto!",
                    text: "Documento Registrado Correctamente",
                    type: "success"
                }, function(){
                    window.location.href = 'index.php';
                });
                console.log(datos);

            }

        });

});


function listardetalle(tipo, consecutivo){

    $.post("../../controller/documento.php?op=consultar_seq", { tipo : tipo, consecutivo : consecutivo }, function (data) {
        data = JSON.parse(data);
        console.log(data);
        $('#seq').val(data.seq);

    });

    $.post("../../controller/documento.php?op=mostrar", { tipo : tipo, consecutivo : consecutivo }, function (data) {
        data = JSON.parse(data);
        console.log(data);

        // Un documento anulado se bloquea exactamente igual que uno ya exportado:
        // se reutiliza toda la lógica existente basada en data.exportado en vez de duplicarla.
        if (data.anulado === 'S') {
            data.exportado = 'S';
            $('#avisoAnulado').show();
        } else {
            $('#avisoAnulado').hide();
        }

        $('#tipo').val(data.tipo);
        $('#tipodoc').val(data.TipoDoctos);
        $('#numdoc').val(data.Numero_documento);
        $('#nit1').val(data.nit_Cedula);
        $('#nombre1').val(data.Nombre_Cliente);
        $('#direcc').val(data.codigo_direccion);
        $('#direccion1').val(data.direccion);
        $('#telefono1').val(data.telefono_1);

    if(data !== null){

      document.getElementById("idTipo").style.display = "none";
      document.getElementById("consecutivo").style.display = "none";
      document.getElementById("fecha").style.display = "none";
      document.getElementById("nit").style.display = "none";
      document.getElementById("nombre").style.display = "none";
      document.getElementById("direccion").style.display = "none";
      document.getElementById("telefono").style.display = "none";

      document.getElementById("txt_idTipo").style.display = "none";
      document.getElementById("txt_consecutivo").style.display = "none";
      document.getElementById("txt_fecha").style.display = "none";
      document.getElementById("txt_nit").style.display = "none";
      document.getElementById("txt_nombre").style.display = "none";
      document.getElementById("txt_direccion").style.display = "none";
      document.getElementById("txt_telefono").style.display = "none";

      document.getElementById("btncrear").style.display = "none";

      document.getElementById("tipodoc").style.display = "inline-block";
      document.getElementById("numdoc").style.display = "inline-block";
      document.getElementById("fecha1").style.display = "inline-block";
      document.getElementById("nit1").style.display = "inline-block";
      document.getElementById("nombre1").style.display = "inline-block";
      document.getElementById("direccion1").style.display = "inline-block";
      document.getElementById("telefono1").style.display = "inline-block";

      document.getElementById("txt_tipodoc").style.display = "inline-block";
      document.getElementById("txt_numdoc").style.display = "inline-block";
      document.getElementById("txt_fecha1").style.display = "inline-block";
      document.getElementById("txt_nit1").style.display = "inline-block";
      document.getElementById("txt_nombre1").style.display = "inline-block";
      document.getElementById("txt_direccion1").style.display = "inline-block";
      document.getElementById("txt_telefono1").style.display = "inline-block";

      document.getElementById("agregar").style.display = "inline-block";
      document.getElementById("btnexcel").style.display = "inline-block";
      document.getElementById("btneliminarsel").style.display = "inline-block";

      $('#notas').val(data.notas || '');

      if (data.exportado === 'S') {
        // Documento ya guardado: modo consulta, no se permite modificar nada más
        document.getElementById("avisoConsulta").style.display = "block";
        document.getElementById("agregar").style.display = "none";
        document.getElementById("btnexcel").style.display = "none";
        document.getElementById("btnguardar").style.display = "none";
        document.getElementById("btneliminarsel").style.display = "none";
        $('#notas').prop('readonly', true);
      } else {
        document.getElementById("avisoConsulta").style.display = "none";
        $('#notas').prop('readonly', false);
      }
    }



    });

    tabla=$('#tb-doc').dataTable({
        "aProcessing": true,
        "aServerSide": false,
        "paging": true,
        "ordering": false,
        dom: 'Bfrtip',
        "searching": true,
        lengthChange: true,
        colReorder: false,
        buttons: [

                ],
        "ajax":{
            url: '../../controller/documento.php?op=listar_detalle',
            type : "post",
            dataType : "json",
            data:{ tipo : tipo, consecutivo : consecutivo },
            dataSrc: "aaData",
            error: function(consecutivo){
                console.log(consecutivo.responseText);
            }

        },
        "bDestroy": true,
        "responsive": true,
        "bInfo":true,
        "iDisplayLength": 10,
        "autoWidth": false,
        "language": {
            "sProcessing":     "Procesando...",
            "sLengthMenu":     "Mostrar _MENU_ registros",
            "sZeroRecords":    "No se encontraron resultados",
            "sEmptyTable":     "Ningún dato disponible en esta tabla",
            "sInfo":           "Mostrando un total de _TOTAL_ registros",
            "sInfoEmpty":      "Mostrando un total de 0 registros",
            "sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix":    "",
            "sSearch":         "Buscar:",
            "sUrl":            "",
            "sInfoThousands":  ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst":    "Primero",
                "sLast":     "Último",
                "sNext":     "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    }).DataTable()
}





$(document).on('click', '#tb-doc .btn-eliminar', function() {
    if ($(this).is(':disabled')) return;

    const seq = $(this).data('seq');
    const producto = $(this).data('producto');
    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');

    swal({
        title: "Documento",
        text: "Esta seguro de Eliminar el registro?",
        type: "error",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "Si",
        cancelButtonText: "No",
        closeOnConfirm: false
    },
    function(isConfirm) {
        if (!isConfirm) return;

        $.post("../../controller/documento.php?op=eliminar", { tipo: tipo, consecutivo: consecutivo, producto: producto, seq: seq }, function(data) {
            if (String(data).indexOf('success') === -1) {
                swal({
                    title: "No se pudo eliminar",
                    text: "El documento ya está guardado o anulado y no se puede modificar.",
                    type: "error",
                    confirmButtonClass: "btn-danger"
                }, function() {
                    $('#tb-doc').DataTable().ajax.reload();
                });
                return;
            }
            delete seleccionLineas[String(seq)];
            swal({
                title: "Documento!",
                text: "Registro Eliminado.",
                type: "success",
                confirmButtonClass: "btn-success"
            }, function() {
                $('#tb-doc').DataTable().ajax.reload();
            });
        });
    });
});

// Reaplica el estado guardado en seleccionLineas a los checkboxes de la página
// actualmente visible (se llama en cada draw del DataTable: carga inicial, cambio
// de página, búsqueda, o recarga por ajax).
function sincronizarCheckboxesPagina() {
    $('#tb-doc tbody input.chk-seleccionar').each(function() {
        var seq = String($(this).data('seq'));
        $(this).prop('checked', Object.prototype.hasOwnProperty.call(seleccionLineas, seq));
    });
}

$('#tb-doc').on('draw.dt', function() {
    sincronizarCheckboxesPagina();
});

$(document).on('change', '#tb-doc tbody input.chk-seleccionar', function() {
    var seq = String($(this).data('seq'));
    var producto = $(this).data('producto');
    if ($(this).is(':checked')) {
        seleccionLineas[seq] = producto;
    } else {
        delete seleccionLineas[seq];
    }
});

$(document).on('click', '#marcarTodo', function(e) {
    e.preventDefault();
    // Recorre TODAS las filas del documento (todas las páginas), no solo la visible.
    // Las filas bloqueadas (documento ya guardado/anulado) traen '-' en vez del checkbox
    // en esta columna, así que se filtran antes de intentar leer data-seq.
    $('#tb-doc').DataTable().rows().data().each(function(row) {
        if (typeof row[5] !== 'string' || row[5].indexOf('<input') !== 0) return;
        var $chk = $(row[5]);
        var seq = $chk.data('seq');
        if (seq !== undefined) {
            seleccionLineas[String(seq)] = $chk.data('producto');
        }
    });
    sincronizarCheckboxesPagina();
});

$(document).on('click', '#desmarcarTodo', function(e) {
    e.preventDefault();
    seleccionLineas = {};
    sincronizarCheckboxesPagina();
});

function eliminarSeleccionados() {
    const tipo = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');

    const seleccionados = Object.keys(seleccionLineas).map(function(seq) {
        return { seq: seq, producto: seleccionLineas[seq] };
    });

    if (seleccionados.length === 0) {
        swal("Advertencia!", "Debe seleccionar al menos un producto para eliminar", "warning");
        return;
    }

    swal({
        title: "¿Eliminar productos seleccionados?",
        text: "Se eliminarán " + seleccionados.length + " producto(s). Esta acción no se puede deshacer.",
        type: "warning",
        showCancelButton: true,
        confirmButtonClass: "btn-danger",
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        closeOnConfirm: false
    }, function(isConfirm) {
        if (!isConfirm) return;

        const seqs = seleccionados.map(function(p) { return p.seq; }).join(',');
        const productos = seleccionados.map(function(p) { return p.producto; }).join(',');

        $.post("../../controller/documento.php?op=eliminar_masivo", {
            tipo: tipo,
            consecutivo: consecutivo,
            seqs: seqs,
            productos: productos
        }, function(data) {
            if (String(data).trim() !== 'success') {
                swal("No se pudo eliminar", "El documento ya está guardado o anulado, o hubo un error al eliminar. Respuesta: " + data, "error");
                return;
            }
            seleccionLineas = {};
            swal({
                title: "¡Eliminados!",
                text: seleccionados.length + " producto(s) eliminado(s) correctamente",
                type: "success",
                confirmButtonClass: "btn-success"
            }, function() {
                $('#tb-doc').DataTable().ajax.reload();
            });
        }).fail(function() {
            swal("Error", "Error de conexión. No se pudieron eliminar los productos.", "error");
        });
    });
}


// ─── CARGA MASIVA EXCEL (validar → confirmar) ─────────────────────────────────

let excelEstadoModal      = 'inicial'; // 'inicial' | 'validado'
let excelValidosPendientes = [];
let excelTokenCarga        = ''; // id único por carga; se reutiliza en reintentos para no duplicar

const EXCEL_MAX_BYTES = 8 * 1024 * 1024; // 8 MB (debe coincidir con Documento::EXCEL_MAX_BYTES)

// Genera un id único por carga. Se crea al validar y se mantiene en los reintentos del mismo lote,
// para que el backend detecte un reenvío (token repetido) y no duplique la carga ya guardada.
function generarTokenCarga() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'cm_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
}

// Traduce un fallo HTTP a un mensaje accionable, distinguiendo "servidor ocupado" (reintentar)
// de "archivo grande" o "conexión cortada" (revisar antes de reintentar). El 401 lo maneja el
// interceptor global de sesión en MainJs/js.php, así que aquí no lo tocamos.
function mensajeErrorHttpExcel(xhr) {
    if (xhr.status === 413)                      return "El archivo es demasiado grande para el servidor. Divide la carga en archivos más pequeños.";
    if (xhr.status === 429 || xhr.status === 503) return "El servidor está ocupado en este momento (muchas cargas a la vez). Espera unos segundos y vuelve a intentar.";
    if (xhr.status === 0)                         return "Se interrumpió la conexión o la carga tardó demasiado. Revisa el detalle del documento antes de reintentar: el sistema no duplica productos ya cargados.";
    return "Error de comunicación con el servidor. Intenta nuevamente en un momento.";
}

function resetModalExcel() {
    document.getElementById('archivoExcel').value = '';
    document.getElementById('excelResultados').style.display = 'none';
    document.getElementById('tbExcelBody').innerHTML = '';
    document.getElementById('excelResumen').innerHTML = '';

    excelEstadoModal = 'inicial';
    excelValidosPendientes = [];
    excelTokenCarga = '';

    var btnProcesar = document.getElementById('btnCargarExcel');
    btnProcesar.disabled = false;
    btnProcesar.style.display = 'inline-block';
    btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Validar Archivo';

    document.getElementById('btnNuevoArchivo').style.display = 'none';

    var btnCerrar = document.getElementById('btnCerrarExcel');
    btnCerrar.textContent = 'Cerrar';
    btnCerrar.classList.remove('btn-success');
    btnCerrar.classList.add('btn-secondary');
}

$('#modalexcel').on('hidden.bs.modal', function() {
    resetModalExcel();
});

function formatearCantidadExcel(cantidad) {
    const n = parseFloat(cantidad);
    return isNaN(n) ? cantidad : n.toFixed(3);
}

function pintarResultadosExcel(resultados) {
    const tbody = document.getElementById('tbExcelBody');
    tbody.innerHTML = '';
    (resultados || []).forEach(function(r) {
        let statusBadge, rowClass;
        if (r.status === 'ok') {
            if (r.warn) {
                statusBadge = '<span class="badge badge-warning">Duplicado</span>';
                rowClass    = 'table-warning';
            } else {
                statusBadge = '<span class="badge badge-success">OK</span>';
                rowClass    = 'table-success';
            }
        } else {
            statusBadge = '<span class="badge badge-danger">Error</span>';
            rowClass    = 'table-danger';
        }
        const tr = document.createElement('tr');
        tr.className = rowClass;
        tr.innerHTML = `<td>${r.fila}</td><td>${r.idProducto}</td><td>${formatearCantidadExcel(r.cantidad)}</td><td>${r.lote || '-'}</td><td>${statusBadge}</td><td>${r.mensaje}</td>`;
        tbody.appendChild(tr);
    });
    document.getElementById('excelResultados').style.display = 'block';
}

function pintarResumenExcel(ok, error, textoOk, advertencia) {
    advertencia = advertencia || 0;
    const resumenClass = error > 0 ? (ok > 0 ? 'alert-warning' : 'alert-danger') : (advertencia > 0 ? 'alert-warning' : 'alert-success');
    const badgeAdvertencia = advertencia > 0
        ? `<span class="badge badge-warning ml-1">${advertencia} con advertencia</span>`
        : '';
    document.getElementById('excelResumen').innerHTML =
        `<div class="alert ${resumenClass} py-2 mb-0">
            <strong>Resultado:</strong>
            <span class="badge badge-success ml-2">${ok} ${textoOk}</span>
            ${badgeAdvertencia}
            <span class="badge badge-danger ml-1">${error} con error</span>
        </div>`;
}

// El botón único del modal cambia de rol según el estado: primero valida, luego procesa.
function procesarBotonExcel() {
    if (excelEstadoModal === 'validado') {
        confirmarExcelPedidos();
    } else {
        validarExcelPedidos();
    }
}

function validarExcelPedidos() {
    const fileInput = document.getElementById('archivoExcel');

    if (!fileInput.files || fileInput.files.length === 0) {
        swal("Advertencia!", "Seleccione un archivo Excel (.xlsx) primero.", "warning");
        return;
    }

    const file = fileInput.files[0];
    if (!file.name.toLowerCase().endsWith('.xlsx')) {
        swal("Advertencia!", "Solo se aceptan archivos con extensión .xlsx", "warning");
        return;
    }
    if (file.size > EXCEL_MAX_BYTES) {
        swal("Advertencia!", "El archivo supera el tamaño máximo permitido (8 MB). Divide la carga en archivos más pequeños.", "warning");
        return;
    }

    const formData = new FormData();
    formData.append('archivo', file);

    const btnProcesar = document.getElementById('btnCargarExcel');
    btnProcesar.disabled = true;
    btnProcesar.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Validando...';

    $.ajax({
        url:         "../../controller/documento.php?op=validar_excel_pedidos",
        type:        'POST',
        data:        formData,
        processData: false,
        contentType: false,
        dataType:    'json',
        success: function(response) {
            btnProcesar.disabled = false;

            if (response.status === 'error') {
                btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Validar Archivo';
                swal("Error!", response.message, "error");
                return;
            }

            const ok    = response.ok    || 0;
            const error = response.error || 0;
            const warn  = response.warn  || 0;
            pintarResumenExcel(ok, error, 'válidos', warn);
            pintarResultadosExcel(response.resultados);
            document.getElementById('excelAvisoNoGuardado').style.display = 'block';

            excelEstadoModal = 'validado';
            excelValidosPendientes = response.validos || [];
            excelTokenCarga = generarTokenCarga(); // token de esta carga validada (persiste en reintentos)

            document.getElementById('btnNuevoArchivo').style.display = 'inline-block';

            if (ok > 0) {
                btnProcesar.innerHTML = '<i class="fa fa-check"></i> Procesar ' + ok + ' registro(s) válido(s)';
                btnProcesar.disabled = false;
            } else {
                btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Validar Archivo';
                btnProcesar.disabled = true;
            }
        },
        error: function(xhr, status, errorThrown) {
            if (xhr.status === 401) return; // sesión expirada: lo maneja el interceptor global
            btnProcesar.disabled = false;
            btnProcesar.innerHTML = '<i class="fa fa-upload"></i> Validar Archivo';
            console.error('[validarExcelPedidos] Error HTTP:', status, errorThrown, xhr.responseText);
            swal("Error!", mensajeErrorHttpExcel(xhr), "error");
        }
    });
}

function confirmarExcelPedidos() {
    const tipo        = getUrlParameter('tipo');
    const consecutivo = getUrlParameter('consecutivo');

    if (!excelValidosPendientes.length) {
        swal("Advertencia!", "No hay registros válidos para procesar.", "warning");
        return;
    }

    const btnProcesar = document.getElementById('btnCargarExcel');
    btnProcesar.disabled = true;
    btnProcesar.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Procesando...';

    $.ajax({
        url:     "../../controller/documento.php?op=confirmar_excel_pedidos",
        type:    'POST',
        timeout: 120000, // 2 min: si se excede, avisamos claro (el token evita duplicar en el reintento)
        data: {
            tipo:    tipo,
            numdoc:  consecutivo,
            token:   excelTokenCarga,
            validos: JSON.stringify(excelValidosPendientes)
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'error') {
                btnProcesar.disabled = false;
                if (response.code === 'ya_procesada') {
                    // Reintento de una carga que el servidor ya había guardado: no se duplicó nada.
                    swal("Aviso", response.message, "warning");
                    listardetalle(tipo, consecutivo);
                } else {
                    swal("Error!", response.message, "error");
                }
                return;
            }

            const ok    = response.ok    || 0;
            const error = response.error || 0;
            const warn  = response.warn  || 0;
            pintarResumenExcel(ok, error, 'agregados', warn);
            pintarResultadosExcel(response.resultados);
            document.getElementById('excelAvisoNoGuardado').style.display = 'none';

            excelEstadoModal = 'inicial';
            excelValidosPendientes = [];
            excelTokenCarga = '';

            btnProcesar.style.display = 'none';
            var btnCerrar = document.getElementById('btnCerrarExcel');
            btnCerrar.innerHTML = '<i class="fa fa-check"></i> Listo';
            btnCerrar.classList.remove('btn-secondary');
            btnCerrar.classList.add('btn-success');

            if (ok > 0) {
                listardetalle(tipo, consecutivo);
            }
        },
        error: function(xhr, status, errorThrown) {
            if (xhr.status === 401) return; // sesión expirada: lo maneja el interceptor global
            btnProcesar.disabled = false;
            btnProcesar.innerHTML = '<i class="fa fa-check"></i> Procesar registros válidos';
            console.error('[confirmarExcelPedidos] Error HTTP:', status, errorThrown, xhr.responseText);
            const msg = (status === 'timeout')
                ? "La operación tardó demasiado. Revisa el detalle del documento antes de reintentar: el sistema no duplica productos ya cargados."
                : mensajeErrorHttpExcel(xhr);
            swal("Error!", msg, "error");
        }
    });
}


init();
