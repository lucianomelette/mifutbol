$(function(){	
	
	// Stadium plugins
	(function ($) {
		
		// Show message
		$.stdShowMessage = function (options) {

			var defaults = {
					message: 'Hola Stadium',
					type: 'info'
				},
				plugin = this,
				options = options || {};


			plugin.init = function () {
				var settings = $.extend({}, defaults, options);
				
				$.notify({
					// options
					icon: settings.icon,
					message: settings.message.trim(), 
				},{
					// settings
					type: settings.type,
					allow_dismiss: true,
					newest_on_top: true,
					delay: 3000,
					timer: 1000,
				});
			}

			plugin.init();
		}
		
		// jTable with Bootstrap style
		$.stdjTableBootstrapStyle = function() {
			
			var plugin = this;
			
			plugin.init = function () {
				
				// Table title
				var title = $('div.jtable-title-text').text();
				$('div.jtable-title-text').replaceWith('<h6 class="jtable-title-text card-title">' + title + '</h6>')
				
				// Add new record button
				$('span.jtable-toolbar-item-text').first().addClass("badge badge-primary p-2");
				$('span.jtable-toolbar-item-text').prepend('<i class="fa fa-plus"></i> ');
				
				// Table style
				$('table.jtable').addClass("table table-striped table-hover");
			}
			
			plugin.init();
		}
		
		$.stdGetCustomDateTime = function(date) {
			
			var plugin = this;
			var customDateTime;
			
			plugin.init = function (date) {
				var monthNames = ["01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11", "12"];
				var day = ("" + date.getDate()).padStart(2, "0");
				var month = monthNames[date.getMonth()];
				var year = ("" + date.getFullYear()).substr(2, 2);
				var hours = ("" + date.getHours()).padStart(2, "0");
				var minutes = ("" + date.getMinutes()).padStart(2, "0");
				
				customDateTime = day + '/' + month + '/' + year + ' ' + hours + ':' + minutes;
			}
			
			plugin.init(date);
			
			return customDateTime;
		}

	} (jQuery));
	
	// custom date
	function getCustomDate() {
		var monthNames = ["ene", "feb", "mar", "abr", "may", "jun", "jul", "ago", "sep", "oct", "nov", "dic"];
		var date = new Date();
		var day = date.getDate();
		var month = monthNames[date.getMonth()];
		var year = date.getFullYear();
		
		return '<i class="far fa-calendar"></i> Hoy es ' + day + ' de ' + month + ' de ' + year;
	}
	$('[name="currentDateText"]').html(getCustomDate()).addClass('small');
	
	// logout
	$('a[name="btnLogout"]').on('click', function(e){
		e.preventDefault();
		
		$.ajax({
			method: 'POST',
			url: '/login/logout',
			success: function(data, textStatus, jqXHR) {
				if (data.status == 'ok') {
					window.location.replace('/login');
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.warn(jqXHR.responseText);
			},
		});
	});
	
	// datepicker
	$('input[name="datepicker"]').datepicker({
		dateFormat: "dd/mm/yy"
	});
});