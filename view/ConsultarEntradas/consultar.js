var tabla;

function init(){
    // Si quieres añadir algún botón para crear nueva entrada
    $("#btnNueva").click(function(){
        window.location.href = "../Entradas/index.php";
    });
}

function formatoFecha(date) {
    var y = date.getFullYear();
    var m = String(date.getMonth() + 1).padStart(2, '0');
    var d = String(date.getDate()).padStart(2, '0');
    return y + '-' + m + '-' + d;
}

function fijarFiltrosPorDefecto() {
    var hoy = new Date();
    var inicioMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

    $('#filtroFechaDesde').val(formatoFecha(inicioMes));
    $('#filtroFechaHasta').val(formatoFecha(hoy));
    $('#filtroNumDesde').val('');
    $('#filtroNumHasta').val('');
    $('#filtroTipo').val('');
    // Por defecto se muestran los documentos NO exportados (borradores sin guardar),
    // que es lo que se quiere detectar al entrar al módulo.
    $('#filtroExportado').val('N');
    // Por defecto se ocultan los documentos anulados.
    $('#filtroAnulado').val('N');
}

$(document).ready(function(){

    // Combo de tipos de documento de entrada permitidos para el usuario (mismo endpoint
    // que ya usa el formulario de creación de Entradas).
    $.post("../../controller/permisos.php?op=combo_entradas_permisos", function(data){
        console.log("combo_entradas_permisos respuesta:", data);
        $('#filtroTipo').html('<option value="">Todos mis tipos permitidos</option>' + data);
    }).fail(function(xhr, status, error){
        console.error("Error cargando combo_entradas_permisos:", status, error, xhr.responseText);
        swal("Error!", "No se pudo cargar el combo de tipos de documento.", "error");
    });

    fijarFiltrosPorDefecto();

    tabla = $('#doc_data').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ordering": false,
        dom: 'Bfrtip',
        "searching": true,
        lengthChange: false,
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            {
                extend: 'pdfHtml5',
                title: 'Consulta de Entradas',
                orientation: 'landscape', // Orientación apaisada para más columnas
                pageSize: 'LETTER',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7, 8] // Exportar todas las columnas excepto la de acciones
                }
            }
        ],
        "ajax":{
            url: '../../controller/documento.php?op=listar_entradas',
            type : "post",
            dataType : "json",
            data: function(d) {
                d.tipo        = $('#filtroTipo').val();
                d.fechaDesde  = $('#filtroFechaDesde').val();
                d.fechaHasta  = $('#filtroFechaHasta').val();
                d.numDesde    = $('#filtroNumDesde').val();
                d.numHasta    = $('#filtroNumHasta').val();
                d.exportado   = $('#filtroExportado').val();
                d.anulado     = $('#filtroAnulado').val();
            },
            error: function(e){
                console.log(e.responseText);
                swal("Error!", "No se pudieron cargar los datos. Intente nuevamente.", "error");
            }
        },
        "columnDefs": [
            {
                "targets": [7], // Columna de exportado
                "className": "text-center"
            },
            {
                "targets": [8], // Columna de anulado
                "className": "text-center"
            },
            {
                "targets": [9], // Columna de acciones
                "className": "text-center"
            }
        ],
        "bDestroy": true,
        "responsive": true,
        "bInfo": true,
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
    }).DataTable();

    $('#btnFiltrar').on('click', function(){
        tabla.ajax.reload();
    });

    $('#btnLimpiarFiltro').on('click', function(){
        fijarFiltrosPorDefecto();
        tabla.ajax.reload();
    });
});

// Función para ver detalle (ya no es necesaria ya que usamos un enlace directo en el controlador)
function ver(tipo, consecutivo) {
    window.location.href = `nuevodoc.php?tipo=${tipo}&consecutivo=${consecutivo}`;
}

init();
