export default {
    methods: {
      printContent() {
            //   const contentToPrint = document.getElementById('printable-content').innerHTML;
            //   const contentToPrintTableProducts = document.getElementById('tabla_registro').innerHTML;
            //   const contentToPrintResume = document.getElementById('registro_documento').innerHTML;
              const contentToPrintLogoCompany = document.getElementById('logoCompany').innerHTML;
            //   const styles = `
            //   @import url('https://cdn.jsdelivr.net/npm/tailwindcss@2.2.17/dist/tailwind.min.css');
            //   //  @import url('https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css');
            //   `;
              const newWindow = window.open('', '_blank');
              const htmlContent = `
                <html>
                  <head>
                        <meta charset="UTF-8">
                        <meta http-equiv="X-UA-Compatible" content="IE=edge">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
                        <link rel="preconnect" href="https://fonts.googleapis.com">
                        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                        <link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+39&display=swap" rel="stylesheet">
                        <title>Orden de Salida # ${this.assetsHeaderDoc.numero_pedido}</title>
                        <style>
                        .barcode-font {
                           font-family: 'Libre Barcode 39', cursive;
                        }
                        </style>
                  </head>
                        <body>
                              <section id="printable-content" class="bg-white dark:bg-gray-900">
                                    <div class="py-8 px-4 mx-auto lg:py-16 w-screen">
                                          <div class="grid gap-1 sm:grid-cols-3 sm:gap-6 mb-5">
                                              ${contentToPrintLogoCompany}
                                                <div class="w-full mt-4">
                                                      <h2 class="text-xl font-bold text-gray-900 dark:text-white text-start">ORDEN DE PEDIDO # ${this.assetsHeaderDoc.numero_pedido}</h2> 
                                                      <p>NIT: 805.018.495-1</p>
                                                      <p>DIRECCION: CRA 29B # 11-90 ACOPI-YUMBO</p>
                                                      <p>TEL: (2) 488 1818</p>
                                                </div>
                                                <div>
                                                      <h2 class="text-9xl text-start barcode-font w-full">${this.assetsHeaderDoc.numero_pedido}</h2> 
                                                </div>
                                          </div>

                                          <div class="relative grid gap-4 sm:gap-6 border-4 mt-4 rounded-md p-2 ml-0 sm:ml-0 md:ml-0 lg:ml-0 xl:ml-0 hover:bg-orange-100">
                                              <h2 class="absolute  transform -translate-y-1/2 translate-x-4 bg-gray-200 hover:bg-gray-300 rounded-lg px-2 text-md font-bold text-gray-900 dark:text-white">
                                                DATOS REFERENTES A LA FACTURA
                                              </h2>  
                                              <div class="grid gap-4 grid-cols-6 mt-4 rounded-md p-2">
                                                 <div class="w-full">
                                                      <label for="nit" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">CLIENTE:</label>
                                                      <input type="text" value="${this.assetsHeaderDoc.nit}" name="nit" id="nit" class="font-bold bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                      <p class="font-bold uppercase">${this.assetsHeaderDoc.name_third_order}</p>
                                                 </div>   
                                                 <div class="w-full col-start-2 col-end-4">
                                                      <label for="name_address_third_address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">DIRECCION:</label>
                                                      <input type="text" value="${this.assetsHeaderDoc.name_address_third_address}" name="name_address_third_address" id="name_address_third_address" class="uppercase bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                 </div>
                                                 <div class="w-full">
                                                      <label for="sucursal_name_third_address" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">SUCURSAL:</label>
                                                      <input type="text" value="${this.assetsHeaderDoc.sucursal_name_third_address.trim()}-${this.assetsHeaderDoc.city_name_third_address.trim()}-${this.assetsHeaderDoc.neighborhood_address_third_address.trim()}" name="sucursal_name_third_address" id="sucursal_name_third_address" class="uppercasebg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                 </div>
                                                 <div class="w-full">
                                                      <label for="telefono1" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">TEL 1:</label>
                                                      <input type="text" value="${this.assetsHeaderDoc.telefono1}" name="telefono1" id="telefono1" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                 </div>
                                                 <div class="w-full col-start-6 col-end-7">
                                                      <label for="email_third" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">EMAIL:</label>
                                                      <input type="text" value="${this.assetsHeaderDoc.email_third}" name="email_third" id="email_third" class="uppercase bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                 </div>
                                              </div>
                                          </div>

                                          <div class="relative grid gap-4 sm:gap-6 border-4 mt-4 rounded-md p-2 ml-0 sm:ml-0 md:ml-0 lg:ml-0 xl:ml-0 hover:bg-orange-100">
                                              <h2 class="absolute  transform -translate-y-1/2 translate-x-4 bg-gray-200 hover:bg-gray-300 rounded-lg px-2 text-md font-bold text-gray-900 dark:text-white">
                                                DATOS COMERCIALES Y DE ENVIO
                                              </h2>  
                                              <div class="grid gap-4 grid-cols-6 mt-4 rounded-md p-2">
                                                 <div class="w-full">
                                                      <label for="vendedor" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">VENDEDOR:</label>
                                                      <input type="text" value="${this.obtenerNombreVendedor(this.assetsHeaderDoc.vendedor)}" name="vendedor" id="vendedor" class="uppercase bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                 </div>   
                                                 <div class="w-full">
                                                      <label for="condicion" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">CONDICION DE PAGO:</label>
                                                      <input type="text" value="${this.obtenerNombreCondicionPago(this.assetsHeaderDoc.condicion)}" name="condicion" id="condicion" class="uppercase bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                 </div>
                                                 <div class="w-full">
                                                      <label for="fecha_hora_pedido" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">FECHA REGISTRO:</label>
                                                      <input type="date" value="${this.assetsHeaderDoc.fecha_hora_pedido_imp}" name="fecha_hora_pedido" id="fecha_hora_pedido" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                 </div>
                                                 <div class="w-full">
                                                      <label for="fecha_hora_entrega" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">FECHA ENTREGA:</label>
                                                      <input type="date" value="${this.assetsHeaderDoc.fecha_hora_entrega_imp}" name="fecha_hora_entrega" id="fecha_hora_entrega" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                 </div>
                                                 <div class="w-full">
                                                      <label for="fecha_hora_limite_entrega" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">FECHA LIMITE:</label>
                                                      <input type="date" value="${this.assetsHeaderDoc.fecha_hora_limite_entrega_imp}" name="fecha_hora_limite_entrega" id="fecha_hora_limite_entrega" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full h-1 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required="" disabled>
                                                 </div>
                                              </div>
                                          </div>
                                           
                                          </div>  
                                          <!--Tabla de Registros-->
                                          <div class="grid gap-4 mt-4 w-full px-6"  id="tabla_registro">
                                                <table class="w-full">
                                                      <thead>
                                                        <tr class="bg-gray-100 border-b-2 border-gray-400 font-medium">
                                                          <th class="border-4">
                                                            <span>Codigo</span>
                                                          </th>
                                                          <th class="border-4">Nombre Producto</th>
                                                          <th class="border-4">Peso Bascula</th>
                                                          <th class="border-4">Cant</th>
                                                          <th class="border-4">Und</th>
                                                          <th class="border-4">Cant Despacho</th>
                                                        </tr>
                                                      </thead>
                                                      <tbody>
                                                        ${this.assetsDocumentosLinPed
                                                          .map(
                                                            (a) => `
                                                            <tr class="border-b border-gray-200 hover:bg-gray-100 hover:bg-orange-100">
                                                              <td class="text-center border-4"><b>${a.IdProducto}</b></td>
                                                              <td class="border-4">${this.obtenerNombreProducto(a.IdProducto)}</td>
                                                              <td class="text-center border-4"></td>
                                                              <td class="text-center border-4">${a.cantidad.toLocaleString('es-MX', {
                                                                minimumFractionDigits: 0,
                                                                maximumFractionDigits: 2
                                                              })}</td>
                                                              <td class="text-center border-4">${this.obtenerNombreUnidad(a.und)}</td>
                                                              <td class="text-center border-4"></td>
                                                            </tr>
                                                          `
                                                          )
                                                          .join('')}
                                                      </tbody>
                                                </table>
                                          </div> 
                                          <!--Fin Tabla de Registros--> 
                                          <!--Inicio resumen documento-->
                                          <div class="grid gap-4 sm:grid-cols-5 sm:gap-6 mt-4 font-bold">
                                                <div class="w-full pl-8 mt-2">
                                                      <label for="total_cantidad" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">TOTAL CANTIDAD: ${this.sumQuantity(this.assetsDocumentosLinPed)}</label>    
                                                </div>      
                                          </div>  
                                          <!--Fin resumen documento--> 
                                          <div class="w-full px-8 mt-2 font-bold">
                                                  <label for="notas" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white font-bold">NOTAS:</label>
                                                  <textarea value="" name="notas" id="notas" class="mb-4 uppercase h-20 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-600 focus:border-primary-600 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="" required=""  disabled>${this.assetsHeaderDoc.notas}
                                                  </textarea>
                                            </div>
                                    </div>
                              </section>
                              
                        </body>
                </html>
              `;
              newWindow.document.write(htmlContent);
              newWindow.document.close();
              // Retraso de 5 segundos antes de imprimir
              setTimeout(() => {
                newWindow.print();
              }, 500); // 5000 milisegundos = 5 segundos
        },
    },
  };