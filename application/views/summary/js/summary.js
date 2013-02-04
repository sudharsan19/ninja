$(document).ready(function() {
	$('#report_mode_form input').on('change', function() {
		set_report_mode(this.value);
	});
	set_report_mode($('#report_mode_form input:checked').val());

	$("#report_period").bind('change', function() {
		show_calendar($(this).val());
	});
	show_calendar($('#report_period').val());

	$(".to_check").bind('submit', function() {
		loopElements();
		return check_form_values(this.form);
	});

	$("#saved_report_form").bind('submit', function() {
		return check_and_submit($(this));
	});

	// reset options and reload page
	$('#new_report').click(function() {
		var base_uri = _site_domain + _index_page + '/' + _current_uri;
		self.location.href = base_uri;
	});

	$('.comments').editable(_site_domain + _index_page + '/alert_history/add_comment', {
		data: function(value) {
			var that = $(this);
			that.addClass('editing-comments').on('blur', 'input', function() {
				that.removeClass('editing-comments');
			});
			return $(value).filter('.content').text()
		},
		submitdata: function(value, settings) {
			$(this).removeClass('editing-comments');
			var eventrow = $(this).parents('.eventrow');
			return {
				timestamp: eventrow.data('timestamp'),
				event_type: eventrow.data('statecode'),
				host_name: eventrow.data('hostname'),
				service_description: eventrow.data('servicename'),
				comment: $('input', this).val() // (today) you cannot use value, because it's the original HTML
			}
		},
		width: 'none'
	});

	// delete the report (and all the schedules if any)
	$("#delete_report").click(function (ev) {
		var btn = $(this);
		var id = $("#report_id").attr('value')

		var is_scheduled = $('#is_scheduled').text()!='' ? true : false;
		var msg = _reports_confirm_delete + "\n";
		var type = $('input[name=type]').attr('value');
		if (!id)
			return;
		if (is_scheduled) {
			msg += _reports_confirm_delete_warning;
		}
		msg = msg.replace("this saved report", "the saved report '"+$('#report_id option[selected=selected]').text()+"'");
		if (confirm(msg)) {
			btn.after(loadimg);
			$.ajax({
				url: _site_domain + _index_page + '/' + _controller_name + '/delete/',
				type: 'POST',
				data: {'id': id},
				complete: function() {
					btn.parent().find('img:last').remove();
				},
				success: function(data) {
					jgrowl_message(data, _reports_success);
					var input = $('#report_id');
					$(':selected', input).remove();
					$('[value=\'\']', input).selected();

					// clean up
					var a = document.createElement("a");
					a.href = window.location.href;
					if(a.search && a.search.indexOf("report_id="+id) !== -1) {
						window.location.href = a.search.replace(new RegExp("report_id="+id+"&?"), "");
					}
				},
				error: function() {
					jgrowl_message(_reports_error, _reports_error);
				},
				dataType: 'json'
			});
		}
	});
});

/**
*	Receive params as JSON object
*	Parse fields and populate corresponding fields in form
*	with values.
*/
function expand_and_populate(data)
{
	var reportObj = data;
	var field_obj = new field_maps();
	var tmp_fields = new field_maps3();
	var field_str = reportObj.report_type;
	if(!field_str) {
		return;
	}
	$('#report_type').val(field_str);
	set_selection(field_str);
	get_members(field_str, function() {
		if (reportObj.objects) {
			var to_id = field_obj.map[field_str];
			var from_id = tmp_fields.map[field_str];
			// select report objects
			for (prop in reportObj['objects']) {
				$('#' + from_id).selectOptions(reportObj['objects'][prop]);
			}
			// move selected options from left -> right
			moveAndSort(from_id, to_id);
		}
	});
}

function set_report_mode(type)
{
	switch (type) {
		case 'standard':
			$('.standard').show();
			$('.custom').hide();
			break;
		case 'custom':
			$('.standard').hide();
			$('.custom').show();
			break;
	}
}

function set_initial_state(what, val)
{
	var item = '';
	var elem = false;
	switch (what) {
		case 'obj_type':
			item = 'report_type';
			break;
		case '':
			item = '';
			break;
		default:
			item = what;
	}
	if (item) {
		// don't use name field - use ID!
		if ($('#' + item).is(':visible')) {
			$('#' + item + ' option').each(function() {
				if ($(this).val() == val) {
					$(this).attr('selected', true);
				}
			});
		}
	}
}