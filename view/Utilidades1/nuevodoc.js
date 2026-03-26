var tabla;
var usu_id =  $('#user_id').val();
var rol_id =  $('#rol_id').val();


function init(){   

}

$(document).ready(function() {    
    
    //Solo para documentos de entrada
   /* $.post("../../controller/tipodoctos.php?op=combo_entradas",function(data, status){
       $('#idTipo').html(data);    
    });*/

    //Para todos los tipos de documentos
    $.post("../../controller/tipodoctos.php?op=doctos",function(data, status){
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

    var tipo =  getUrlParameter('tipo');
    var consecutivo =  getUrlParameter('consecutivo');

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

$(document).on("click","#btnupdate1", function(event){

    event.preventDefault();       

    let tipo = document.getElementById("idTipo").value;
    let consecutivo = document.getElementById("consecutivo").value;    
    let numero = document.getElementById("numero").value;
    let formData = new FormData($("#doc_form")[0]);

    if ((tipo =='') || (consecutivo =='') || (numero =='')){
        swal("Advertencia!", "Campos Vacios", "warning");
    }else{
         // Muestra el mensaje de espera
        $.blockUI({ message: '<h2>Cargando favor Espere...</h2>' });  
        $.ajax({
            url: "../../controller/documento.php?op=update_doc_ref",
            type: "POST",
            data: formData,
            contentType: false,
            processData: false,
            success: function(datos){   
                console.log(datos);               
                swal({
                    title: "Correcto!", 
                    text: "Documento Actualizado Correctamente", 
                    type: "success"
                }, function(){
                    location.reload();
                });
                                    
            },
            complete: function() {
                // Oculta el mensaje de espera
                $.unblockUI();
            }
        });
        
    }

    $("#btnupdate").prop('disabled',true)
    
    return false;
});



// Evento click para el botón actualizar
$(document).on("click","#btnupdate", function(event){

    event.preventDefault(); // Prevenir comportamiento por defecto
    
    // Array para almacenar los registros modificados
    let registrosModificados = [];
        
    // Recorrer todos los inputs de la tabla
    $("#tb-doc tbody input[type='text']").each(function() {
        let input = $(this);
        let id = input.attr('id');
        let valor = input.val();
            
        // Separar el tipo y número de documento del ID
        let [tipo, numeroDocumento] = id.split('_');
            
        registrosModificados.push({
            tipo: tipo,
            numeroDocumento: numeroDocumento,
            numeroDoctoBase: valor
        });
    });

    console.log('Datos a enviar:', registrosModificados); // Para debug
        
        // Llamada AJAX para actualizar
        $.ajax({
            url: '../../controller/documento.php?op=update_doc_ref',
            type: 'POST',
            data: {
                registros: JSON.stringify(registrosModificados)
            },
            success: function(response) {
                console.log('Respuesta raw del servidor:', response); // Para debug
                try {
                    let data = JSON.parse(response);
                    if(data.status) {
                        swal("¡Éxito!", "Registros actualizados correctamente", "success");
                        // Opcional: recargar la tabla
                        $("#tb-doc").submit();
                    } else {
                        swal("Error", "No se pudieron actualizar los registros", "error");
                    }
                } catch(e) {
                    console.error(e);
                    swal("Error", "Ocurrió un error al procesar la respuesta", "error");
                }
            },
            error: function() {
                swal("Error", "Error en la comunicación con el servidor", "error");
            }
        });
});





init();
