var usu_id = $('#usu_id').val();
var rol_id = $('#rol_id').val();

function init() {

}

$(document).ready(function () {

    $.post("../../controller/tipodoctos.php?op=doctos",function(data, status){
        $('#idTipo').html(data);    
     });

     // Agregar el evento de búsqueda cuando se escriba en el campo de búsqueda
    $('#searchInput').keyup(function() {
      var searchText = $(this).val().toLowerCase();
      // Filtrar las filas de la tabla para mostrar solo las que coincidan con el texto de búsqueda
      $("#dataTable tbody tr").filter(function() {
          $(this).toggle($(this).text().toLowerCase().indexOf(searchText) > -1)
      });
  });    
   
});


//Cuando el usuario presiona el boton agregar se abre una ventana modal para llenar la informacion solicitada
$(document).on("click", "#btnfecha", function () {
    let fechaFactura = $("#fecha_factura").val(); // Obtiene la fecha de la modal
  
    // Obtiene los valores de los checkboxes seleccionados y los agrega a un array
    let checkboxesSeleccionados = [];
    $('input[name="id[]"]:checked').each(function () {
      checkboxesSeleccionados.push($(this).val());
    });
  
    // Comprueba que se haya seleccionado al menos un checkbox
    if (checkboxesSeleccionados.length === 0) {
      swal("Error", "Debes seleccionar al menos un registro.", "error");
      return;
    }
  
    // Crea un objeto FormData y agrega la fecha y los checkboxes seleccionados
    let formData = new FormData();
    formData.append("fecha_factura", fechaFactura);
    checkboxesSeleccionados.forEach((checkbox) => {
      formData.append("ids_seleccionados[]", checkbox);
    });
  
    // Realiza la petición AJAX al controlador
    $.ajax({
      url: "../../controller/documento.php?op=update_fecha",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      success: function (datos) {
        console.log(datos);

        swal({
          title: "Correcto!", 
          text: "Fecha Actualizada correctamente", 
          type: "success"
      }, function(){
        $("#lot").modal("hide");        
        $("#fecha_factura").val("");
        location.reload(true); 
      });  
        
      },
      error: function (error) {
        console.log(error);
        swal("Error", "Ocurrió un error al actualizar la fecha.", "error");
      },
    });
  });



init();