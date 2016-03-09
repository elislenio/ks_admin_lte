$.extend( $.fn.dataTable.defaults, {
    processing: true,
	serverSide: true,
	select: {
		style:    'multi'
	},
	language: {
		processing:     '<img src="/bundles/ksadminltetheme/images/abm_loader.gif" />',
		search:         "Buscar:",
		lengthMenu:     "Mostrar _MENU_ registros",
		info:           "Registros _START_ al _END_ de _TOTAL_",
		infoEmpty:      "",
		infoFiltered:   "(filtrado de un total de _MAX_ registros)",
		infoPostFix:    "",
		loadingRecords: '<img src="/bundles/ksadminltetheme/images/abm_loader.gif" />',
		zeroRecords:    "No se encontraron resultados",
		emptyTable:     "No se encontraron resultados",
		paginate: {
			first:    "Primero",
			previous: "Anterior",
			next:     "Siguiente",
			last:     "Último"
		},
		aria: {
			sortAscending:  ": Activar para ordenar la columna de manera ascendente",
			sortDescending: ": Activar para ordenar la columna de manera descendente"
		},
		select: {
            rows: {
                _: "%d registros seleccionados",
                0: "",
                1: "1 registro seleccionado"
            }
        }
	},
	dom: 
		"<'row ks-dt-row'<'col-sm-12 ks-dt-col'tr>>" +
		"<'row'<'col-sm-4'i><'col-sm-2 hidden-xs'l><'col-sm-6'p>>"
} );