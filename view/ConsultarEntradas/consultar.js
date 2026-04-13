var tabla;

function init(){
    // Si quieres añadir algún botón para crear nueva entrada
    $("#btnNueva").click(function(){
        window.location.href = "../Entradas/index.php";
    });
}

$(document).ready(function(){    
    
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
                    columns: [0, 1, 2, 3, 4, 5, 6, 7] // Exportar todas las columnas excepto la de acciones
                }
            }
        ],
        "ajax":{
            url: '../../controller/documento.php?op=listar_entradas',
            type : "post",
            dataType : "json",						
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
                "targets": [8], // Columna de acciones
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
});

// Función para ver detalle (ya no es necesaria ya que usamos un enlace directo en el controlador)
function ver(tipo, consecutivo) {
    window.location.href = `nuevodoc.php?tipo=${tipo}&consecutivo=${consecutivo}`;
}

init();