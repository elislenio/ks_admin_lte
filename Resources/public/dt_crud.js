"use strict";

function CrudDT(id, dt_config, url_data, url_create, url_edit, url_delete, url_export, autoload) 
{
	var self = this;
	
	this.id = id;
	this.url_data = url_data;
	this.url_create = url_create;
	this.url_edit = url_edit;
	this.url_delete = url_delete;
	this.url_export = url_export;
	this.autoload = autoload;
	
	// JQuery refs
	this.ui_container = $('#'+id+'_box');
	this.dt_container = $('#'+id+'_container');
	this.form_filters = $('#'+id+'_form_filters');
	this.btn_filters = $('#'+id+'_btn_filters');
	this.btn_reload = $('#'+id+'_btn_reload');
	this.btn_export = $('#'+id+'_btn_export');
	this.btn_add = $('#'+id+'_btn_add');
	this.btn_delete = $('#'+id+'_btn_delete');
	this.btn_search = $('#'+id+'_btn_search');
	this.btn_adv_search = $('#'+id+'_btn_adv_search');
	this.btn_basic_search = $('#'+id+'_btn_basic_search');
	this.last_post_data = false;
	
	this.blockUI = function()
	{
		self.ui_container.block({ message: '<img src="/bundles/ksadminltetheme/images/abm_loader.gif" />', css: { width: '75px' }}); 
	};
	
	this.unblockUI = function()
	{
		self.ui_container.unblock(); 
	};
	
	this.reload = function()
	{
		self.table.ajax.reload();
	};
	
	this.editRecord = function(id)
	{
		$(location).attr('href', this.url_edit + "/" + id);
	};
	
	this.deleteRecord = function(id)
	{
		if (! confirm("¿Confirma que desea eliminar el registro?"))
			return false;
		
		var v_data = {};
		v_data.ids = [];
		v_data.ids.push(id);
		
		$.ajax({
			url: this.url_delete,
			method: "POST",
			dataType: 'json',
			data: v_data
		}).done(function(data) {
			if( data.result != 'ok')
			{
				show_error(data.result);
				return;
			}
			self.table.ajax.reload();
		});
	};
	
	this.expandRecord = function(e, data)
	{
		e.stopPropagation();
		alert(data);
	};
	
	// Initialize DT
	this.table = $('#'+id);
	
	// on ajax call complete
	this.table.on('xhr.dt', function ( e, settings, json, xhr ) 
	{
		if (json && json.server_msg)
		{
			if (json.server_msg == 'Ajax request denied')
				window.location = json.redirectTo;
			else
				show_error(json.server_msg);
		}
	});
	
	dt_config.ajax = {
		url: this.url_data,
		type: 'POST',
		data: function ( d ) {
			self.last_post_data = $.extend( {}, d, {"extra_search": self.form_filters.serializeArray()} );
			return self.last_post_data;
		}
	};
	
	// DT initialization
	this.table = $('#'+id).DataTable(dt_config);
	this.select_all = $('#'+this.id+'_select_all');
	
	// Selected / deselect all rows
	this.select_all.change(function () {
		
		var rows = self.table.rows({ page: 'current' });
		
		if($(this).is(":checked"))
			rows.select();
		else
			rows.deselect();
	});
	
	// On page change event
	this.table.on('page.dt', function () {
		self.select_all.prop("checked",'');
	});

	// Reload Button
	this.btn_reload.on('click', function () {
		self.table.ajax.reload();
	});
	
	// Export Button
	this.btn_export.on('click', function () {
		var params = $.extend( {}, self.last_post_data, {crud_action: 'export'});
		window.location = self.url_export + "?" + $.param( params );
	});
	
	// Add record Button
	this.btn_add.on('click', function () {
		$(location).attr('href', self.url_create);
	});
	
	// Delete selected records Button
	this.btn_delete.on('click', function () {
		
		var rows = self.table.rows( { selected: true } );
		
		if (rows.count() == 0) {
			show_error('No ha seleccionado ningun registro para eliminar');
			return;
		}
		
		if (! confirm("¿Confirma que desea eliminar los registros seleccionados?"))
			return false;
	
		var v_data = {};
		v_data.ids = [];
		
		$.each(rows.data(), function( key, value ) {
			v_data.ids.push(value.id);
		});
		
		$.ajax({
			url: self.url_delete,
			method: "POST",
			dataType: 'json',
			data: v_data
		}).done(function(data) {
			if( data.result != 'ok')
			{
				show_error(data.result);
				return;
			}
			self.table.ajax.reload();
			self.select_all.prop("checked",'');
		});
	
	});
	
	// Search form
	this.btn_search.on('click', function (e) {
		e.preventDefault();
		self.table.ajax.reload();
		
		if (! self.autoload)
		{
			self.dt_container.show();
			self.btn_reload.prop('disabled', false);
			self.btn_export.prop('disabled', false);
		}
	});
	
	this.btn_adv_search.on('click', function () {
		self.btn_adv_search.toggle();
		self.btn_basic_search.toggle();
		$('.ks-cond-filter', self.form_filters).css('display', 'inline');
	});
	
	this.btn_basic_search.on('click', function () {
		self.btn_adv_search.toggle();
		self.btn_basic_search.toggle();
		$('.ks-cond-filter', self.form_filters).hide();
	});
	
	if (! this.autoload)
	{
		this.dt_container.hide();
		this.btn_reload.prop('disabled', true);
		this.btn_export.prop('disabled', true);
	}
}