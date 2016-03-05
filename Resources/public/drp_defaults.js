drp_locale_defaults = {
	"separator": " - ",
	"applyLabel": "Aceptar",
	"cancelLabel": "Cancelar",
	"fromLabel": "Desde",
	"toLabel": "Hasta",
	"customRangeLabel": "Personalizado",
	"daysOfWeek": [
		"Do",
		"Lu",
		"Ma",
		"Mi",
		"Ju",
		"Vi",
		"Sa"
	],
	"monthNames": [
		"Enero",
		"Febrero",
		"Marzo",
		"Abril",
		"Mayo",
		"Junio",
		"Julio",
		"Agosto",
		"Septiembre",
		"Octubre",
		"Noviembre",
		"Deciembre"
	],
	"firstDay": 1
};

drp_datetime_locale = $.extend( {}, drp_locale_defaults, {
	"format": "DD/MM/YYYY HH:mm",
});
	
drp_datetime_defaults = {
	"timePicker": true,
	"timePicker24Hour": true,
	"timePickerIncrement": 5,
	"locale": drp_datetime_locale
};