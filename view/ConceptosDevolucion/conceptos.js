// ── Configuración ────────────────────────────────────────────────────────────
const CONCEPTOS_URL = '../../controller/conceptosdevolucion.php';

// ── DataTable ─────────────────────────────────────────────────────────────────
var tablaConceptos;

$(document).ready(function () {
    tablaConceptos = $('#tbConceptos').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url:  CONCEPTOS_URL + '?op=listar',
            type: 'POST',
            data: function (d) {
                d.busqueda = $('#inputBusqueda').val().trim();
            }
        },
        columns: [
            { data: 0, className: 'text-center' },
            { data: 1 },
            { data: 2, className: 'text-center', orderable: false },
            { data: 3, className: 'text-center' },
            { data: 4, className: 'text-center', orderable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 15,
        lengthMenu: [10, 15, 25, 50],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
        }
    });

    $('#inputBusqueda').on('keypress', function (e) {
        if (e.which === 13) buscar();
    });
});

// ── Búsqueda ──────────────────────────────────────────────────────────────────
function buscar() {
    tablaConceptos.ajax.reload();
}

function limpiarBusqueda() {
    $('#inputBusqueda').val('');
    tablaConceptos.ajax.reload();
}

// ── Modal Crear ───────────────────────────────────────────────────────────────
function abrirModalCrear() {
    $('#conceptoId').val('');
    $('#conceptoNombre').val('');
    $('#conceptoEstado').val('1');
    $('#divEstado').hide();
    $('#modalConceptoTitle').text('Nuevo Concepto');
    $('#btnGuardarConcepto').html('<i class="fa fa-save"></i> Guardar');
    $('#modalConcepto').modal('show');
    setTimeout(function () { $('#conceptoNombre').focus(); }, 400);
}

// ── Modal Editar ──────────────────────────────────────────────────────────────
function abrirModalEditar(id) {
    $.ajax({
        url:      CONCEPTOS_URL + '?op=get_concepto&id=' + id,
        type:     'GET',
        dataType: 'json',
        success: function (resp) {
            if (resp.status !== 'success') {
                swal('Error', resp.message, 'error');
                return;
            }
            var c = resp.data;
            $('#conceptoId').val(c.id);
            $('#conceptoNombre').val(c.nombre);
            $('#conceptoEstado').val(c.estado);
            $('#divEstado').show();
            $('#modalConceptoTitle').text('Editar Concepto #' + c.id);
            $('#btnGuardarConcepto').html('<i class="fa fa-save"></i> Actualizar');
            $('#modalConcepto').modal('show');
            setTimeout(function () { $('#conceptoNombre').focus(); }, 400);
        },
        error: function () {
            swal('Error', 'No se pudo cargar el concepto.', 'error');
        }
    });
}

// ── Guardar (crear o editar) ──────────────────────────────────────────────────
function guardarConcepto() {
    var id     = $('#conceptoId').val().trim();
    var nombre = $('#conceptoNombre').val().trim();
    var estado = $('#conceptoEstado').val();

    if (nombre === '') {
        swal('Advertencia', 'El nombre no puede estar vacío.', 'warning');
        $('#conceptoNombre').focus();
        return;
    }

    var esEdicion = id !== '';
    var op        = esEdicion ? 'editar' : 'crear';
    var datos     = { nombre: nombre };
    if (esEdicion) {
        datos.id     = id;
        datos.estado = estado;
    }

    $('#btnGuardarConcepto').prop('disabled', true)
                            .html('<i class="fa fa-spinner fa-spin"></i> Guardando...');

    $.ajax({
        url:      CONCEPTOS_URL + '?op=' + op,
        type:     'POST',
        data:     datos,
        dataType: 'json',
        success: function (resp) {
            $('#btnGuardarConcepto').prop('disabled', false)
                                    .html('<i class="fa fa-save"></i> ' + (esEdicion ? 'Actualizar' : 'Guardar'));
            if (resp.status === 'success') {
                swal('Correcto', resp.message, 'success');
                $('#modalConcepto').modal('hide');
                tablaConceptos.ajax.reload(null, false);
            } else {
                swal('Error', resp.message, 'error');
            }
        },
        error: function () {
            $('#btnGuardarConcepto').prop('disabled', false)
                                    .html('<i class="fa fa-save"></i> ' + (esEdicion ? 'Actualizar' : 'Guardar'));
            swal('Error', 'Error de comunicación con el servidor.', 'error');
        }
    });
}

// ── Cambiar Estado (activar/inactivar) ────────────────────────────────────────
function cambiarEstado(id, nuevoEstado, nombre) {
    var accion  = nuevoEstado == 1 ? 'activar' : 'inactivar';
    var titulo  = nuevoEstado == 1 ? '¿Activar concepto?' : '¿Inactivar concepto?';
    var btnText = nuevoEstado == 1 ? 'Sí, activar' : 'Sí, inactivar';
    var tipo    = nuevoEstado == 1 ? 'info' : 'warning';

    swal({
        title:              titulo,
        text:               'Se va a ' + accion + ' el concepto: <b>' + nombre + '</b>.',
        html:               true,
        type:               tipo,
        showCancelButton:   true,
        confirmButtonClass: nuevoEstado == 1 ? 'btn-success' : 'btn-warning',
        confirmButtonText:  btnText,
        cancelButtonText:   'Cancelar',
        closeOnConfirm:     false
    }, function (isConfirm) {
        if (!isConfirm) return;
        swal({ title: 'Procesando...', text: 'Por favor espere', type: 'info', showConfirmButton: false });

        $.ajax({
            url:      CONCEPTOS_URL + '?op=cambiar_estado',
            type:     'POST',
            data:     { id: id, estado: nuevoEstado },
            dataType: 'json',
            success: function (resp) {
                if (resp.status === 'success') {
                    swal('Listo', resp.message, 'success');
                    tablaConceptos.ajax.reload(null, false);
                } else {
                    swal('Error', resp.message, 'error');
                }
            },
            error: function () {
                swal('Error', 'Error de comunicación con el servidor.', 'error');
            }
        });
    });
}
