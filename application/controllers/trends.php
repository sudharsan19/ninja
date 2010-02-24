<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Trends controller
 * Requires authentication
 *
 * @package    NINJA
 * @author     op5 AB
 * @license    GPL
 * @copyright 2009 op5 AB
 *  op5, and the op5 logo are trademarks, servicemarks, registered servicemarks
 *  or registered trademarks of op5 AB.
 *  All other trademarks, servicemarks, registered trademarks, and registered
 *  servicemarks mentioned herein may be the property of their respective owner(s).
 *  The information contained herein is provided AS IS with NO WARRANTY OF ANY
 *  KIND, INCLUDING THE WARRANTY OF DESIGN, MERCHANTABILITY, AND FITNESS FOR A
 *  PARTICULAR PURPOSE.
 */
class Trends_Controller extends Authenticated_Controller {
	public static $options = array(
		'rpttimeperiod' => 'report_timeperiod',
		'scheduleddowntimeasuptime' => 'scheduled_downtime_as_uptime',
		'assumestatesduringnotrunning' => 'assume_states_during_not_running',
		'includesoftstates' => 'include_soft_states',
		'assumeinitialstates' => 'assume_initial_states'
	);

	public static $dep_vars = array(
		'assumeinitialstates' => array(
			'initialassumedhoststate' => 'initial_assumed_host_state',
			'initialassumedservicestate' => 'initial_assumed_service_state'
		)
	);

	public static $setup_keys = array(
		'report_name',
		'info',
		'rpttimeperiod',
		'report_period',
		'start_time',
		'end_time',
		'report_type',
		'initialassumedhoststate',
		'initialassumedservicestate',
		'assumeinitialstates',
		'scheduleddowntimeasuptime',
		'assumestatesduringnotrunning',
		'includesoftstates'
	);

	public static $map_type_field = array(
		'hosts' => "host_name",
		'services' => "service_description",
		'hostgroups' => "hostgroup",
		'servicegroups' => "servicegroup"
	);

	private $err_msg = '';

	public static $initial_assumed_host_states = array(
	   -1 => 'Current state',
	   -2 => 'Unspecified',
	   -3 => 'First Real State',
	    0 => 'Host Up',
	    1 => 'Host Down',
	    2 => 'Host Unreachable',
	);

	public static $initial_assumed_service_states = array(
	   -1 => 'Current state',
	   -2 => 'Unspecified',
	   -3 => 'First Real State',
	    0 => 'Service Ok',
	    1 => 'Service Warning',
	    2 => 'Service Critical',
	    3 => 'Service Unknown',
	);

	public static $sla_field_names = array(
		'hosts' => 'PERCENT_TOTAL_TIME_UP',
		'hostgroups' => 'PERCENT_TOTAL_TIME_UP',
		'services' => 'PERCENT_TOTAL_TIME_OK',
		'servicegroups' => 'PERCENT_TOTAL_TIME_OK'
	);

	private $state_values = false;

	private $initialassumedhoststate = -1;
	private $initialassumedservicestate = -1;

	private $abbr_month_names = false;
	private $month_names = false;
	private $day_names = false;
	private $abbr_day_names = false;
	private $first_day_of_week = 1;

	# default values
	private $assume_state_retention = true;
	private $assume_states_during_not_running = true;
	private $include_soft_states = false;
	private $cluster_mode = false;
	private $scheduled_downtime_as_uptime = false;
	private $csv_output = false;
	private $create_pdf = false;
	private $pdf_data = false;

	private $assume_initial_states = true;
	private $initial_assumed_host_state = -3;
	private $initial_assumed_service_state = -3;

	private $use_average = 0;

	private $type = 'avail';
	private $xajax = false;
	private $report_id = false;
	private $data_arr = false;
	private $report_type = false;
	private $object_varname = false;

	private $status_link = "status/host/";
	private $trend_link = "trends/index";
	private $histogram_link = "histogram/index";
	private $history_link = "history/index";
	private $notifications_link = "notifications/index";

	private $reports_model = false;
	public $start_date = false;
	public $end_date = false;
	private $report_options = false;
	private $in_months = false;

	public function __construct()
	{
		parent::__construct();

		$this->reports_model = new Reports_Model();

		$this->xajax = get_xajax::instance();
		$this->abbr_month_names = array(
			$this->translate->_('Jan'),
			$this->translate->_('Feb'),
			$this->translate->_('Mar'),
			$this->translate->_('Apr'),
			$this->translate->_('May'),
			$this->translate->_('Jun'),
			$this->translate->_('Jul'),
			$this->translate->_('Aug'),
			$this->translate->_('Sep'),
			$this->translate->_('Oct'),
			$this->translate->_('Nov'),
			$this->translate->_('Dec')
		);

		$this->month_names = array(
			$this->translate->_('January'),
			$this->translate->_('February'),
			$this->translate->_('March'),
			$this->translate->_('April'),
			$this->translate->_('May'),
			$this->translate->_('June'),
			$this->translate->_('July'),
			$this->translate->_('August'),
			$this->translate->_('September'),
			$this->translate->_('October'),
			$this->translate->_('November'),
			$this->translate->_('December')
		);

		$this->abbr_day_names = array(
			$this->translate->_('Sun'),
			$this->translate->_('Mon'),
			$this->translate->_('Tue'),
			$this->translate->_('Wed'),
			$this->translate->_('Thu'),
			$this->translate->_('Fri'),
			$this->translate->_('Sat')
		);

		$this->day_names = array(
			$this->translate->_('Sunday'),
			$this->translate->_('Monday'),
			$this->translate->_('Tuesday'),
			$this->translate->_('Wednesday'),
			$this->translate->_('Thursday'),
			$this->translate->_('Friday'),
			$this->translate->_('Saturday')
		);

		$this->state_values = array(
			'OK' => $this->translate->_('OK'),
			'WARNING' => $this->translate->_('WARNING'),
			'UNKNOWN' => $this->translate->_('UNKNOWN'),
			'CRITICAL' => $this->translate->_('CRITICAL'),
			'PENDING' => $this->translate->_('PENDING'),
			'UP' => $this->translate->_('UP'),
			'DOWN' => $this->translate->_('DOWN'),
			'UNREACHABLE' => $this->translate->_('UNREACHABLE')
		);
	}

	/**
	*	Display report selection/setup page
	*/
	public function index()
	{
		$this->template->disable_refresh = true;

		$scheduled_downtime_as_uptime_checked  =
			arr::search($_REQUEST, 'scheduleddowntimeasuptime', $this->scheduled_downtime_as_uptime) ? 'checked="checked"' : '';
		$cluster_mode_checked =
			arr::search($_REQUEST, 'cluster_mode', $this->cluster_mode) ? 'checked="checked"' : '';
		$assume_initial_states_checked =
			arr::search($_REQUEST, 'assumeinitialstates', $this->assume_initial_states) ? 'checked="checked"' : '';
		$assume_states_during_not_running_checked =
			arr::search($_REQUEST, 'assumestatesduringnotrunning', $this->assume_states_during_not_running) ? 'checked="checked"' : '';
		$include_soft_states_checked = 'checked="checked"';
		$old_config_names = false; #Saved_reports_Model::get_all_report_names($this->type);
		$old_config_names_js = empty($old_config_names) ? "false" : "new Array('".implode("', '", $old_config_names)."');";
		$this->report_id =
			arr::search($_REQUEST, 'report_id', false);
		$initial_assumed_host_state_selected =
			arr::search($_REQUEST, 'initialassumedhoststate', $this->initial_assumed_host_state);
		$initial_assumed_service_state_selected =
			arr::search($_REQUEST, 'initialassumedservicestate', $this->initial_assumed_service_state);
		$csv_output_checked =
			arr::search($_REQUEST, 'csvoutput', $this->csv_output) ? 'checked="checked"' : '';

		$use_average_yes_selected = $use_average_no_selected = '';
		if(arr::search($_REQUEST, 'use_average', $this->use_average) == 1)
			$use_average_yes_selected = 'selected="selected"';
		else
			$use_average_no_selected = 'selected="selected"';

		$xajax = $this->xajax;
		#$filters = 1;

		$this->xajax->registerFunction(array('get_group_member',$this,'_get_group_member'));
		$this->xajax->registerFunction(array('get_report_periods',$this,'_get_report_periods'));
		$this->xajax->registerFunction(array('get_saved_reports',$this,'_get_saved_reports'));
		#$xajax->registerFunction('fetch_scheduled_field_value');
		#$xajax->registerFunction('delete_schedule_ajax');
		$this->xajax->processRequest();

		$this->template->content = $this->add_view('trends/setup');
		$template = $this->template->content;

		# we should set the required js-files
		$this->template->js_header = $this->add_view('js_header');
		$this->xtra_js[] = 'application/media/js/date';
		$this->xtra_js[] = 'application/media/js/jquery.datePicker';
		$this->xtra_js[] = 'application/media/js/jquery.timePicker';
		$this->xtra_js[] = $this->add_path('reports/js/json');
		$this->xtra_js[] = $this->add_path('reports/js/move_options');
		$this->xtra_js[] = $this->add_path('reports/js/common');
		$this->xtra_js[] = $this->add_path('trends/js/trends');
		$this->xtra_js[] = 'application/media/js/jquery.fancybox.min';


		$this->template->js_header->js = $this->xtra_js;

		$this->template->css_header = $this->add_view('css_header');
		$this->xtra_css[] = $this->add_path('reports/css/datePicker');
		#$this->xtra_css[] = $this->add_path('css/default/jquery-ui-custom.css');
		$this->xtra_css[] = $this->add_path('trends/css/trends');
		$this->xtra_css[] = 'application/media/css/jquery.fancybox';
		$this->template->css_header->css = $this->xtra_css;

		$t = $this->translate;

		$saved_reports = null;
		$scheduled_ids = array();
		$scheduled_periods = null;
		$report_info = false;
		$scheduled_label = $t->_('Scheduled');
		$label_report = $this->translate->_('report');

		# fetch users date format in PHP style so we can use it
		# in date() below
		$date_format = cal::get_calendar_format(true);

		$js_month_names = "Date.monthNames = ".json::encode($this->month_names).";";
		$js_abbr_month_names = 'Date.abbrMonthNames = '.json::encode($this->abbr_month_names).';';
		$js_day_names = 'Date.dayNames = '.json::encode($this->day_names).';';
		$js_abbr_day_names = 'Date.abbrDayNames = '.json::encode($this->abbr_day_names).';';
		$js_day_of_week = 'Date.firstDayOfWeek = '.$this->first_day_of_week.';';
		$js_date_format = "Date.format = '".cal::get_calendar_format()."';";
		$js_start_date = "_start_date = '".date($date_format, mktime(0,0,0,1, 1, 1996))."';";

		# inline js should be the
		# var host =
		# var service =
		# 	etc...
		$this->inline_js .= "\n".$js_month_names."\n";
		$this->inline_js .= $js_abbr_month_names."\n";
		$this->inline_js .= $js_day_names."\n";
		$this->inline_js .= $js_abbr_day_names."\n";
		$this->inline_js .= $js_day_of_week."\n";
		$this->inline_js .= $js_date_format."\n";
		$this->inline_js .= $js_start_date."\n";

		if ($this->report_id) {
			$this->inline_js .= "$('#assumed_host_state').hide();
			$('#assumed_service_state').hide();\n";
		}
		if (!$report_info) {
			$this->inline_js .= "set_selection(document.getElementsByName('report_type').item(0).value);\n";
		} else {
			$this->inline_js .= "expand_and_populate(" . $json_report_info . ");\n";
		}

		if($assume_initial_states_checked) {
			$this->inline_js .= "show_state_options(true);\n";
			$this->inline_js .= "toggle_label_weight(true, 'assume_initial');\n";
		}
		if($scheduled_downtime_as_uptime_checked)
			$this->inline_js .= "toggle_label_weight(true, 'sched_downt');\n";
		if($include_soft_states_checked)
			$this->inline_js .= "toggle_label_weight(true, 'include_softstates');\n";
		if($assume_states_during_not_running_checked)
			$this->inline_js .= "toggle_label_weight(true, 'assume_progdown');\n";
		if($csv_output_checked)
			$this->inline_js .= "toggle_label_weight(true, 'csvout');\n";
		$this->inline_js .= "invalid_report_names = ".$old_config_names_js .";\n";
		$this->inline_js .= "uncheck('save_report_settings', 'report_form');\n";
		$this->inline_js .= "$('#report_save_information').hide();\n";

		$this->js_strings .= "var _edit_str = '".$t->_('edit')."';\n";
		$this->js_strings .= "var _hide_str = '".$t->_('hide')."';\n";
		$this->js_strings .= "var _scheduled_label = '".$scheduled_label."';\n";
		$this->js_strings .= "var _label_report = '".$label_report."';\n";
		$this->js_strings .= "var nr_of_scheduled_instances = ". (!empty($scheduled_info) ? sizeof($scheduled_info) : 0).";\n";
		$this->js_strings .= "var _reports_edit_information = '".$t->_('Double click to edit')."';\n";
		$this->js_strings .= "var _reports_schedule_deleted = '".$t->_('Your schedule has been deleted')."';\n";
		$this->js_strings .= "var _reports_propagate = '".$t->_('Would you like to propagate this value to all months')."';\n";
		$this->js_strings .= "var _reports_propagate_remove = '".$t->_("Would you like to remove all values from all months")."';\n";

		$this->js_strings .= "var _schedule_change_filename = \"".$t->_('Would you like to change the filename based on your selections?')."\";\n";
		$this->js_strings .= reports::js_strings();
		$this->js_strings .= "var _reports_error_name_exists = '".sprintf($t->_("You have entered a name for your report that already exists. %sPlease select a new name"), '<br />')."';\n";
		$this->js_strings .= "var _reports_error_name_exists_replace = \"".$t->_("The entered name already exists. Press 'Ok' to replace the entry with this name")."\";\n";
		$this->js_strings .= "var _reports_missing_objects = \"".$t->_("Some items in your saved report doesn't exist anymore and has been removed")."\";\n";
		$this->js_strings .= "var _reports_missing_objects_pleaseremove = '".$t->_('Please modify the objects to include in your report below and then save it.')."';\n";
		$this->js_strings .= "var _reports_confirm_delete = '".$t->_("Are you really sure that you would like to remove this saved report?")."';\n";
		$this->js_strings .= "var _reports_confirm_delete_schedule = \"".sprintf($t->_("Do you really want to delete this schedule?%sThis action can't be undone."), '\n')."\";\n";
		$this->js_strings .= "var _reports_confirm_delete_warning = '".sprintf($t->_("Please note that this is a scheduled report and if you decide to delete it, %s" .
			"the corresponding schedule will be deleted as well.%s Are you really sure that this is what you want?"), '\n', '\n\n')."';\n";

		$this->template->inline_js = $this->inline_js;

		$this->template->xajax_js = $xajax->getJavascript(get_xajax::web_path());
		$template->type = $this->type;
		$template->scheduled_label = $scheduled_label;
		$template->title_label = $t->_('schedule');
		$template->label_select = $t->_('Select');
		$template->label_new = $t->_('New');
		$template->new_saved_title = $t->_('Create new saved trends report');
		$template->label_delete = $t->_('Delete report');
		$template->label_dblclick = $t->_('Double click to edit');
		$template->label_sch_interval = $t->_('Interval');
		$template->label_sch_recipients = $t->_('Recipients');
		$template->label_sch_filename = $t->_('Filename');
		$template->label_sch_description = $t->_('Description');
		$template->label_create_new = $t->_('Trends report');
		$template->label_saved_reports = $t->_('Saved reports');
		$template->label_hostgroups = $t->_('Hostgroups');
		$template->label_hosts = $t->_('Hosts');
		$template->label_servicegroups = $t->_('Servicegroups');
		$template->label_services = $t->_('Services');
		$template->label_available = $t->_('Available');
		$template->label_selected = $t->_('Selected');
		$template->label_report_period = $t->_('Reporting period');
		$template->label_today = $t->_('Today');
		$template->label_last24 = $t->_('Last 24 Hours');
		$template->label_yesterday = $t->_('Yesterday');
		$template->label_thisweek = $t->_('This Week');
		$template->label_last7days = $t->_('Last 7 Days');
		$template->label_lastweek = $t->_('Last Week');
		$template->label_thismonth = $t->_('This Month');
		$template->label_last31days = $t->_('Last 31 Days');
		$template->label_lastmonth = $t->_('Last Month');
		$template->label_last3months = $t->_('Last 3 Months');
		$template->label_last6months = $t->_('Last 6 months');
		$template->label_last12months = $t->_('Last 12 months');
		$template->label_lastquarter = $t->_('Last Quarter');
		$template->label_thisyear = $t->_('This Year');
		$template->label_lastyear = $t->_('Last Year');
		$template->label_custom_period = $t->_('CUSTOM REPORT PERIOD');
		$template->label_startdate = $t->_('Start date');
		$template->label_enddate = $t->_('End date');
		$template->label_startdate_selector = $t->_('Date Start selector');
		$template->label_enddate_selector = $t->_('Date End selector');
		$template->label_click_calendar = $t->_('Click calendar to select date');
		$template->label_initialassumedhoststate = $t->_('First assumed host state');
		$template->label_initialassumedservicestate = $t->_('First assumed service state');
		$template->label_assumestatesduringnotrunning = $t->_('Assume states during program downtime');
		$template->label_assumeinitialstates = $t->_('Assume initial states');
		#$template->label_cluster_mode = $t->_('Cluster mode');
		$template->label_propagate = $t->_('Click to propagate this value to all months');
		#$template->label_enter_sla = $t->_('Enter SLA');
		$template->label_show_event_duration = $t->_('Show event duration');
		$template->reporting_periods = Reports_Controller::_get_reporting_periods();
		$template->scheduled_downtime_as_uptime_checked = $scheduled_downtime_as_uptime_checked;
		$template->cluster_mode_checked = $cluster_mode_checked;
		$template->assume_initial_states_checked = $assume_initial_states_checked;
		$template->initial_assumed_host_states = self::$initial_assumed_host_states;
		$template->initial_assumed_service_states = self::$initial_assumed_service_states;
		$template->assume_states_during_not_running_checked = $assume_states_during_not_running_checked;
		$template->include_soft_states_checked = $include_soft_states_checked;
		$template->label_includesoftstates = $t->_('Include soft states');
		$template->label_avg = $t->_('Average');
		$template->label_create_report = $t->_('Create report');
		$template->label_save_report = $t->_('Save report');
		$template->use_average_yes_selected = $use_average_yes_selected;
		$template->use_average_no_selected = $use_average_no_selected;
		$template->initial_assumed_host_state_selected = $initial_assumed_host_state_selected;
		$template->initial_assumed_service_state_selected = $initial_assumed_service_state_selected;
		$template->csv_output_checked = $csv_output_checked;
		$template->months = $this->abbr_month_names;
		$template->is_scheduled_report = $t->_('This is a scheduled report');
		$edit_str = $t->_('edit');
		$template->edit_str = $edit_str;
		$template->is_scheduled_clickstr = sprintf($t->_("This report has been scheduled. Click on '[%s]' to change settings"), $edit_str);

		$template->report_id = $this->report_id;
		$template->report_info = $report_info;
		$template->old_config_names_js = $old_config_names_js;
		$template->old_config_names = $old_config_names;
		$template->scheduled_ids = $scheduled_ids;
		$template->scheduled_periods = $scheduled_periods;
		$template->saved_reports = $saved_reports;

		# decide what report periods to print
		$report_period_strings = Reports_Controller::_report_period_strings();

		$report_periods = $report_period_strings["report_period_strings"];
		$report_periods['custom'] = "* " . $template->label_custom_period . " *";
		$template->report_periods = $report_periods;
		$template->selected = $report_period_strings["selected"];

		#$this->js_strings .= "var _report_types_json = '(".json::encode($report_types).")';\n";
		#$this->js_strings .= "var _saved_avail_reports = '(".json::encode($avail_reports_arr).")';\n";
		#$this->js_strings .= "var _saved_sla_reports = '(".json::encode($sla_reports_arr).")';\n";
		$this->js_strings .= "var _reports_successs = '".$t->_('Success')."';\n";
		$this->js_strings .= "var _reports_error = '".$t->_('Error')."';\n";
		$this->js_strings .= "var _reports_schedule_error = '".$t->_('An error occurred when saving scheduled report')."';\n";
		$this->js_strings .= "var _reports_schedule_update_ok = '".$t->_('Your schedule has been successfully updated')."';\n";
		$this->js_strings .= "var _reports_schedule_create_ok = '".$t->_('Your schedule has been successfully created')."';\n";
		$this->js_strings .= "var _reports_fatal_err_str = '".$t->_('It is not possible to schedule this report since some vital information is missing.')."';\n";

		$this->template->js_strings = $this->js_strings;

	}

	/**
	*	Generate trends report with settings from the setup page
	*/
	public function generate()
	{
		$this->template->disable_refresh = true;
	#	echo Kohana::debug($_REQUEST);


		$report_options = false;
		foreach (self::$setup_keys as $k)	$report_options[$k] = false;

		// store all variables in array for later use
		foreach ($_REQUEST as $key => $value) {
			if (in_array($key, self::$setup_keys)) {
				if (arr::search($_REQUEST, 'report_period') == 'custom' && ($key=='start_time' || $key=='end_time')) {
					$report_options[$key] = strtotime($value);
				} else {
					$report_options[$key] = $value;
				}
			}
		}

		$t = $this->translate;

		$this->report_options = $report_options;
		$obj_field = $report_options['report_type'] !== false ? self::$map_type_field[$report_options['report_type']] : false;
		$obj_value = arr::search($_REQUEST, $obj_field, array());
		// obj_value is ALWAYS an array

		$this->template->js_header = $this->add_view('js_header');
		$this->xtra_js[] = 'application/media/js/date';
		$this->xtra_js[] = 'application/media/js/jquery.datePicker';
		$this->xtra_js[] = 'application/media/js/jquery.timePicker';
		$this->xtra_js[] = 'application/media/js/timeline/timeline_js/timeline-api';
		$this->xtra_js[] = $this->add_path('reports/js/move_options');
		$this->xtra_js[] = 'application/media/js/jquery.fancybox.min';
		$this->xtra_js[] = $this->add_path('reports/js/common');
		$this->xtra_js[] = $this->add_path('trends/js/trends');

		$this->template->js_header->js = $this->xtra_js;

		$this->xtra_css[] = $this->add_path('reports/css/datePicker');
		$this->xtra_css[] = $this->add_path('trends/css/trends');
		$this->xtra_css[] = 'application/media/css/jquery.fancybox';
		$this->template->css_header = $this->add_view('css_header');
		$this->template->css_header->css = $this->xtra_css;

		$this->template->content = $this->add_view('trends/index'); # base template with placeholders for all parts
		$template = $this->template->content;

		$in_host 			= arr::search($_REQUEST, 'host', false);
		if ($in_host === false)
			$in_host 		= arr::search($_REQUEST, 'host_name', false);
		$in_service 		= arr::search($_REQUEST, 'service', array());
		if (empty($in_service))
			$in_service 	= arr::search($_REQUEST, 'service_description', array());

		$in_hostgroup 		= arr::search($_REQUEST, 'hostgroup', array());
		$in_servicegroup	= arr::search($_REQUEST, 'servicegroup', array());
		$show_event_duration = arr::search($_REQUEST, 'show_event_duration', false);

		$mon_auth = new Nagios_auth_Model();
		if (is_string($in_host)) {
			# @@@FIXME: is the following still valid?
			// shorthand aliases - host=all is used for 'View trends for all hosts'
			if ($in_host == 'all') {
				$in_host = $mon_auth->get_authorized_hosts();
			} elseif($in_host == 'null' && is_string($in_service) && $in_service == 'all') {
				// Used for link 'View avail for all services'
				$in_host = $mon_auth->get_authorized_hosts();
				$in_service = $mon_auth->get_authorized_services();
			} else {
				// handle call from trends.cgi, which does not pass host parameter as array
				if ($mon_auth->is_authorized_for_host($in_host))
					$in_host = array($in_host);
				else
					$in_host = array();
			}
		} elseif (is_array($in_host) && !empty($in_host)) {
			foreach ($in_host as $k => $host) {
				if (!$mon_auth->is_authorized_for_host($host))
					unset($in_host[$k]);
			}
		}

		$this->report_type 	= arr::search($_REQUEST, 'report_type');
		$in_csvoutput		= arr::search($_REQUEST, 'csvoutput');
		$start_time			= arr::search($_REQUEST, 't1') ? arr::search($_REQUEST, 't1') : arr::search($_REQUEST, 'start_time');
		$end_time			= arr::search($_REQUEST, 't2') ? arr::search($_REQUEST, 't2') : arr::search($_REQUEST, 'end_time');
		$report_period		= arr::search($_REQUEST, 'timeperiod') ? arr::search($_REQUEST, 'timeperiod') : arr::search($_REQUEST, 'report_period');
		$rpttimeperiod 		= arr::search($_REQUEST, 'rpttimeperiod', '');
		$cluster_mode		= arr::search($_REQUEST, 'cluster_mode', '');
		$hostgroup			= false;
		$hostname			= false;
		$servicegroup		= false;
		$service			= false;
		$sub_type			= false;
		$time_parts 		= false;

		// cgi compatibility variables
		// Start dates
		$syear 	= (int)arr::search($_REQUEST, 'syear');
		$smon 	= (int)arr::search($_REQUEST, 'smon');
		$sday 	= (int)arr::search($_REQUEST, 'sday');
		$shour 	= (int)arr::search($_REQUEST, 'shour');
		$smin 	= (int)arr::search($_REQUEST, 'smin');
		$ssec 	= (int)arr::search($_REQUEST, 'ssec');
		// end dates
		$eyear 	= (int)arr::search($_REQUEST, 'eyear');
		$emon 	= (int)arr::search($_REQUEST, 'emon');
		$eday 	= (int)arr::search($_REQUEST, 'eday');
		$ehour 	= (int)arr::search($_REQUEST, 'ehour');
		$emin 	= (int)arr::search($_REQUEST, 'emin');
		$esec 	= (int)arr::search($_REQUEST, 'esec');

		$this->report_type = Reports_Controller::_check_report_type($this->report_type, $in_host, $in_service, $servicegroup, $hostgroup);

		if(!isset($_REQUEST['initialassumedhoststate']))
			$_REQUEST['initialassumedhoststate'] = $this->initialassumedhoststate;

		if(!isset($_REQUEST['initialassumedservicestate']))
			$_REQUEST['initialassumedservicestate'] = $this->initialassumedservicestate;

		$get_vars = "&report_period=$report_period";

		$err_msg = "";
		$report_class = $this->reports_model;
		foreach (self::$options as $var => $new_var) {
			$get_vars .= '&'.$var.'='.Reports_Controller::_convert_yesno_int(arr::search($_REQUEST, $var));
			if (!$report_class->set_option($new_var, arr::search($_REQUEST, $var))) {
				$err_msg .= sprintf($t->_("Could not set option '%s' to '%s'"), $new_var, Reports_Controller::_convert_yesno_int(arr::search($_REQUEST, $var)))."'<br />";
			}
		}
		$get_vars .= "&initialassumedhoststate=".$this->initial_assumed_host_state;
		$get_vars .= "&initialassumedservicestate=".$this->initial_assumed_service_state;

		$report_class->set_option('keep_logs', true);

		// convert report period to timestamps
		if ($report_period == 'custom' && !empty($syear) && !empty($eyear)) {
			// cgi compatibility
			$time_parts[0] = mktime($shour, $smin, $ssec, $smon, $sday, $syear);
			$time_parts[1] = mktime($ehour, $emin, $esec, $emon, $eday, $eyear);
		} elseif(!empty($report_period)) {
			$time_parts = Reports_Controller::_calculate_time($report_period, $start_time, $end_time);
		} else {
			# Use time from t1 and t2 - when called from trends.cgi
			$time_parts = array($start_time, $end_time);
		}
		$this->start_date = $time_parts[0]; // used in calculations by lib_report
		$this->end_date = $time_parts[1];  // used in calculations by lib_report
		$str_start_date = date(nagstat::date_format(), $this->start_date); // used to set calendar
		$str_end_date 	= date(nagstat::date_format(), $this->end_date); // used to set calendar
		$report_class->set_option('start_time', $this->start_date);
		$report_class->set_option('end_time', $this->end_date);

		if('custom' == $report_period)
			$report_time_formatted  = sprintf($t->_("%s to %s"), $str_start_date, $str_end_date);
		else
			$report_time_formatted  = (isset($report_period_strings[$report_period]) ? $report_period_strings[$report_period] : $report_period);

		if($rpttimeperiod != '')
			$report_time_formatted .= " - $rpttimeperiod";

		$group_name = false;
		$statuslink_prefix = '';
		switch ($this->report_type) {
			case 'hostgroups':
				$sub_type = "host";
				$hostgroup = $in_hostgroup;
				$group_name = $hostgroup;
				$label_type = $t->_('Hostgroup(s)');
				$this->object_varname = 'host_name';
				break;
			case 'servicegroups':
				$sub_type = "service";
				$servicegroup = $in_servicegroup;
				$group_name = $servicegroup;
				$label_type = $t->_('Servicegroup(s)');
				$this->object_varname = 'service_description';
				break;
			case 'hosts':
				$sub_type = "host";
				$statuslink_prefix = 'service/';
				$hostname = $in_host;
				$label_type = $t->_('Host(s)');
				$this->object_varname = 'host_name';
				break;
			case 'services':
				$sub_type = "service";
				$service = $in_service;
				$label_type = $t->_('Services(s)');
				$this->object_varname = 'service_description';
				break;
			default:
				url::redirect(Router::$controller.'/index');
		}

		$report_class->set_option('host_name', $hostname);
		$report_class->set_option('service_description', $service);
		$selected_objects = ""; // string containing selected objects for this report

		$scheduled_downtime_as_uptime     = arr::search($_REQUEST, 'scheduleddowntimeasuptime');
		$assume_initial_states            = arr::search($_REQUEST, 'assumeinitialstates');
		$assume_states_during_not_running = arr::search($_REQUEST, 'assumestatesduringnotrunning');
		$include_soft_states              = arr::search($_REQUEST, 'includesoftstates');
		$this->initial_assumed_host_state = arr::search($_REQUEST, 'initialassumedhoststate', $this->initial_assumed_host_state);
		$this->initial_assumed_service_state = arr::search($_REQUEST, 'initialassumedservicestate', $this->initial_assumed_service_state);
		$use_average = arr::search($_REQUEST, 'use_average', 0);
		$use_alias = arr::search($_REQUEST, 'use_alias', 0);

		# fetch users date format in PHP style so we can use it
		# in date() below
		$date_format = cal::get_calendar_format(true);

		$js_month_names = "Date.monthNames = ".json::encode($this->month_names).";";
		$js_abbr_month_names = 'Date.abbrMonthNames = '.json::encode($this->abbr_month_names).';';
		$js_day_names = 'Date.dayNames = '.json::encode($this->day_names).';';
		$js_abbr_day_names = 'Date.abbrDayNames = '.json::encode($this->abbr_day_names).';';
		$js_day_of_week = 'Date.firstDayOfWeek = '.$this->first_day_of_week.';';
		$js_date_format = "Date.format = '".cal::get_calendar_format()."';";
		$js_start_date = "_start_date = '".date($date_format, mktime(0,0,0,1, 1, 1996))."';";

		# inline js should be the
		# var host =
		# var service =
		# 	etc...
		$this->inline_js .= "\n".$js_month_names."\n";
		$this->inline_js .= $js_abbr_month_names."\n";
		$this->inline_js .= $js_day_names."\n";
		$this->inline_js .= $js_abbr_day_names."\n";
		$this->inline_js .= $js_day_of_week."\n";
		$this->inline_js .= $js_date_format."\n";
		$this->inline_js .= $js_start_date."\n";

		# this part is probably not needed anymore since we won't have
		# any 'old' cgi's anymore
		if(!isset($_REQUEST['new_report_setup']))
		{
			$this->initial_assumed_host_state = Reports_Controller::_convert_assumed_state($this->initial_assumed_host_state, $sub_type);
			$this->initial_assumed_service_state = Reports_Controller::_convert_assumed_state($this->initial_assumed_service_state, $sub_type);

			$_REQUEST['initialassumedhoststate'] = $this->initial_assumed_host_state;
			$_REQUEST['initialassumedservicestate'] = $this->initial_assumed_service_state;
		}

		# $objects is an array used when creating report_error page (template).
		# Imploded into $missing_objects
		$objects = false;
		if (($this->report_type == 'hosts' || $this->report_type == 'services')) {
			if (is_array($in_host)) {
				foreach ($in_host as $host) {
					$html_options[] = array('hidden', 'host_name[]', $host);
					$selected_objects .= "&host_name[]=".$host;
					$objects[] = $host;
				}
			}
			if (is_array($in_service)) {
				foreach ($in_service as $svc) {
					$html_options[] = array('hidden', 'service_description[]', $svc);
					$selected_objects .= "&service_description[]=".$svc;
					$objects[] = $svc;
				}
			}
		} else {
			if (is_array($hostgroup)) {
				foreach ($hostgroup as $h_gr) {
					$html_options[] = array('hidden', 'hostgroup[]', $h_gr);
					$selected_objects .= "&hostgroup[]=".$h_gr;
					$objects[] = $h_gr;
				}
			}
			if (is_array($servicegroup)) {
				foreach ($servicegroup as $s_gr) {
					$html_options[] = array('hidden', 'servicegroup[]', $s_gr);
					$selected_objects .= "&servicegroup[]=".$s_gr;
					$objects[] = $s_gr;
				}
			}
		}

		$report_class->set_option('keep_logs', true);
		$this->data_arr = $group_name!== false
			? $this->_expand_group_request($group_name, substr($this->report_type, 0, strlen($this->report_type)-1), $this->start_date, $this->end_date)
			: $report_class->get_uptime(false, false, $this->start_date, $this->end_date, $hostgroup, $servicegroup);

		if ((empty($this->data_arr) || (sizeof($this->data_arr)==1 && empty($this->data_arr[0])))) {
			# error!
			# what objects were submitted?
			$template->report_header = $t->_('Empty report');

			$template->error = $this->add_view('reports/error');

			$template->error->error_msg = sprintf($t->_("The selected objects for this %s report doesn't seem to exist anymore.%s
			The reason for this is most likely that they have been removed or renamed in your configuration."), ucfirst(substr($this->report_type, 0, strlen($this->report_type)-1)), '<br />');
			if (!empty($objects)) {
				# @@@FIXME: move presentation code to template
				$template->error->label_missing_objects = $t->_('Missing objects');
				$template->error->missing_objects = $objects;
			}
		} else {
			# ==========================================
			# ========= REPORT STARTS HERE =============
			# ==========================================
			$html_options[] = array('hidden', 'report_type', $this->report_type);
			$html_options[] = array('hidden', 'rpttimeperiod', $rpttimeperiod);
			if($include_soft_states)
				$html_options[] = array('hidden', 'includesoftstates', $include_soft_states);

			if($assume_states_during_not_running)
				$html_options[] = array('hidden', 'assumestatesduringnotrunning', $assume_states_during_not_running);

			$label_report_period = $t->_('Reporting period');
			$label_custom_period = $t->_('CUSTOM REPORT PERIOD');

			# decide what report periods to print
			$report_period_strings = Reports_Controller::_report_period_strings();
			$report_periods = $report_period_strings["report_period_strings"];
			$report_periods['custom'] = "* " . $label_custom_period . " *";
			$this->template->content->report_options = $this->add_view('trends/options');
			$tpl_options = $this->template->content->report_options;

			$tpl_options->label_report_period = $label_report_period;

			$tpl_options->report_periods = $report_periods;
			$tpl_options->selected = $report_period;
			$tpl_options->label_settings = $t->_('Report settings');
			$tpl_options->label_startdate = $t->_('Start date');
			$tpl_options->label_enddate = $t->_('End date');
			$tpl_options->label_show_event_duration = $t->_('Show event duration');
			$tpl_options->label_startdate_selector = $t->_('Date Start selector');
			$tpl_options->label_enddate_selector = $t->_('Date End selector');
			$tpl_options->label_click_calendar = $t->_('Click calendar to select date');
			$tpl_options->label_assumeinitialstates = $t->_('Assume initial states');
			$tpl_options->label_initialassumedhoststate = $t->_('First assumed host state');
			$tpl_options->label_scheduleddowntimeasuptime = $t->_('Count scheduled downtime as uptime');
			$tpl_options->label_initialassumedservicestate = $t->_('First assumed service state');
			$tpl_options->initial_assumed_host_states = self::$initial_assumed_host_states;
			$tpl_options->selected_initial_assumed_host_state = $this->initial_assumed_host_state;
			$tpl_options->initial_assumed_service_states = self::$initial_assumed_service_states;
			$tpl_options->selected_initial_assumed_service_state = $this->initial_assumed_service_state;
			$tpl_options->label_update = $t->_('Update report');
			$tpl_options->label_save = $t->_('Save');
			$tpl_options->label_clear = $t->_('Clear');

			$tpl_options->label_edit_settings = $t->_('edit settings');
			$tpl_options->html_options = $html_options;

			$tpl_options->start_date = date($date_format, $report_class->start_time);
			$tpl_options->start_time = date('H:i', $report_class->start_time);
			$tpl_options->end_date = date($date_format, $report_class->end_time);
			$tpl_options->end_time = date('H:i', $report_class->end_time);

			$this->inline_js .= "set_initial_state('host', '".$this->initial_assumed_host_state."');\n";
			$this->inline_js .= "set_initial_state('service', '".$this->initial_assumed_service_state."');\n";
			$this->inline_js .= "set_initial_state('assumeinitialstates', '".$assume_initial_states."');\n";
			$this->inline_js .= "set_initial_state('scheduleddowntimeasuptime', '".$scheduled_downtime_as_uptime."');\n";
			$this->inline_js .= "set_initial_state('report_period', '".$report_period."');\n";
			$this->inline_js .= "show_calendar('".$report_period."');\n";
			$this->js_strings .= reports::js_strings();
			$this->js_strings .= "var Timeline_ajax_url = '".Kohana::config('config.site_domain')."application/media/js/timeline/timeline_ajax/simile-ajax-api.js';\n";
			$this->js_strings .= "var Timeline_urlPrefix = '".Kohana::config('config.site_domain')."application/media/js/timeline/timeline_js/';\n";
			$this->js_strings .= "var Timeline_parameters = 'bundle=true';\n";

			# for filter boxes
			$this->js_strings .= "var _clear_all = '".$t->_("Clear All")."';\n";
			$this->js_strings .= "var _filter_str = '".$t->_("Filter")."';\n";
			$this->js_strings .= "var _highlight_str = '".$t->_("Highlight")."';\n";

			$trends_data['dateTimeFormat'] ='iso8601';
			$avail_data = false;
			$raw_trends_data = false;
			$multiple_items = false; # structure of avail_data

			if (isset($this->data_arr[0])) {
				$avail_template = $this->add_view('trends/multiple_'.$sub_type.'_states');
				$avail_template->create_pdf = $this->create_pdf;
				$avail_template->hide_host = false;
				$avail_template->get_vars = $get_vars;
				if ($group_name) {
					foreach ($this->data_arr as $data) {
						if (empty($data))
							continue;
						array_multisort($data);
						$avail_data[] = Reports_Controller::_get_multiple_state_info($data, $sub_type, $get_vars, $this->start_date, $this->end_date, $this->type);
					}
				} else {
					array_multisort($this->data_arr);
					$avail_data[] = Reports_Controller::_get_multiple_state_info($this->data_arr, $sub_type, $get_vars, $this->start_date, $this->end_date, $this->type);
				}
				$multiple_items = true;
				$avail_template->multiple_states = $avail_data;

				# hostgroups / servicegroups or >= 2 hosts or services
				$i=0;
				foreach ($this->data_arr as $key => $data) {
					# >= 2 hosts or services won't have the extra
					# depth in the array, so we break out early
					if (empty($data['log']) || !is_array($data['log'])) {
						$raw_trends_data = $this->data_arr['log'];
						break;
					}

					# $data is the outer array (with, source, log,
					# states etc)
					if (empty($raw_trends_data)) {
						$raw_trends_data = $data['log'];
					} else {
						$raw_trends_data = array_merge($data['log'], $raw_trends_data);
					}
				} # end foreach
			} else {
				$avail_data = Reports_Controller::_print_state_breakdowns($this->data_arr['source'], $this->data_arr['states'], $this->report_type);
				$avail_template = $this->add_view('trends/avail');
				$avail_template->avail_data = $avail_data;
				$avail_template->source = $this->data_arr['source'];
				$avail_template->header_string = $t->_("Service state breakdown");
				$avail_template->label_type_reason = $t->_('Type / Reason');
				$avail_template->label_time = $t->_('Time');
				$avail_template->label_tot_time = $t->_('Total time');
				$avail_template->label_unscheduled = $t->_('Unscheduled');
				$avail_template->label_scheduled = $t->_('Scheduled');
				$avail_template->label_total = $t->_('Total');
				$avail_template->label_undetermined = $t->_('Undetermined');
				$avail_template->label_not_running = $t->_('Not running');
				$avail_template->label_insufficient_data = $t->_('Insufficient data');
				$avail_template->label_all = $t->_('All');
				$trend_links = false;
				$host_name = $avail_data['values']['HOST_NAME'];
				$avail_link = url::site().'reports/generate?type=avail'.
				"&host_name[]=". $host_name. "&report_type=hosts" .
				'&start_time=' . $this->start_date . '&end_time=' . $this->end_date .$get_vars;

				#@@@FIXME: Add links to  Alert History + Noticications when available
				if (isset($avail_data['values']['SERVICE_DESCRIPTION']) ) {
					$service_description = $avail_data['values']['SERVICE_DESCRIPTION'];
					$avail_link .= '&service_description[]=' . "$host_name;$service_description";
					$avail_link_name = $t->_('Availability Report For This Service');
				} else {
					$service_description = false;
					$avail_link_name = $t->_('Availability Report For This Host');

					$statuslink = url::site().'status/service?name='.$host_name;
					$trend_links[$t->_('Status Detail For This Host')] = $statuslink;
				}
				$trend_links[$avail_link_name] = $avail_link;

				$avail_template->trend_links = $trend_links;
				$avail_template->state_values = $this->state_values;
				$avail_template->create_pdf = $this->create_pdf;
				/*
			LINKS:
				host:
					View Availability Report For This Host
						View Status Detail For This Host
					View Alert History For This Host
					View Notifications For This Host

				service:
						View Trends For This Host
					View Availability Report For This Service
						View Alert Histogram For This Service
					View Alert History For This Service
					View Notifications For This Service
				*/

				# hosts or services
				if (isset($this->data_arr['log'])) {
					$raw_trends_data = $this->data_arr['log'];
				}
			}
		}

		$filter_states = array('' => ''); # set first empty option
		$to = $t->_('to');
		if (is_array($raw_trends_data) && !empty($raw_trends_data)) {
			foreach ($raw_trends_data as $id => $row) {
				# skip the first (faked) log-entry
				if ($id == 0 || empty($row['host_name']))
					continue;
				if (empty($row['service_description'])) {
					$sub_type = 'host';
					$title = $row['host_name'];
					$info_url = urlencode($title);
				} else {
					$sub_type = 'service';
					$info_url = $row['host_name'] . '?service=' .
						urlencode($row['service_description']);
					$title = $row['host_name'] . ';' . $row['service_description'];
				}
				$description_links = ''; #@@@FIXME: create working links for all pop-ups and objects
				/*
				$description_links = '<br /><a href="'.url::site().'status/'.$statuslink_prefix.$sub_type.'/'.$row[$obj_field].'">'.$t->_('Status details').'</a><br />'.
					'<a href="'.url::site().'notifications/'.$sub_type.'/'.$row[$obj_field].'">'.$t->_('Notifications').'</a><br />'.
					'<a href="'.url::site().'history/'.$sub_type.'/'.$row[$obj_field].'">'.$t->_('History').'</a><br />';
				*/
				$start_time_str = date(nagstat::date_format(), $row['the_time']).': ';

				$end_time_str = ($row['duration'] > 0 ) ? ' '.$to.' '.date(nagstat::date_format(), ($row['the_time']+$row['duration'])).' ' : '';

					$tmp = array(
						'start' => date('c', $row['the_time']),
						'title' => htmlspecialchars($title),
						'description' => $row['output'].$description_links,
						'color' => $this->_state_colors($sub_type, $row['state']),
						'textColor' => "#000000",
						'image' => url::base(false).$this->add_path('icons/16x16/shield-'.$this->_translate_state_to_string($row['state'], $sub_type).'.png'),
						'icon' => url::base(false).$this->add_path('icons/12x12/shield-'.$this->_translate_state_to_string($row['state'], $sub_type).'.png'),
						'link' => url::site().'extinfo/details/'.$sub_type.'/'.$info_url,
						'caption' => $start_time_str.$end_time_str.$row['output']
						);

					if (isset($row['hard'])) {
						switch ($row['hard']) {
							case 1:
								$tmp['hard'] = 1;
								break;
							case 0:
								$tmp['soft'] = 1;
								break;
						}
					}

					$tmp[$this->_translate_state_to_string($row['state'], $sub_type)] = 1;
					if (!in_array($this->_translate_state_to_string($row['state'], $sub_type), $filter_states)) {
						$filter_states[$this->_translate_state_to_string($row['state'], $sub_type)] = $this->_translate_state_to_string($row['state'], $sub_type);
					}
					if ((!empty($row['duration']) && $row['duration'] > 0) && $show_event_duration || !$multiple_items) {
						$tmp['end'] = date('c', ($row['the_time']+$row['duration']));
					}
					$trends_data['events'][] = $tmp;
				/*} else {
					$trends_data['events'][] = array(
						'start' => date('c', $row['the_time']),
						'end' => date('c', ($row['the_time']+$row['duration'])),
						'title' => htmlspecialchars($title),
						'description' => $row['output'].' <br /><a href=""><strong>test</strong></a>',
						'color' => $this->_state_colors($sub_type, $row['state']),
						'textColor' => "#000000",
						'image' => url::base(false).$this->add_path('icons/16x16/shield-'.$this->_translate_state_to_string($row['state'], $sub_type).'.png'),
						'link' => url::site().'extinfo/details/'.$sub_type.'/'.$row[$obj_field],
						'caption' => $start_time_str.$row['output']
						);
				}*/
			}
		}
		$this->template->content->content = $this->add_view('trends/report');
		$report = $this->template->content->content;
		$report->filter_states = $filter_states;
		$report->avail_data = $avail_data;
		$avail_template->use_alias = false;
		$avail_template->use_average = false;
		if ($multiple_items) {
			$avail_template->multiple_states = $avail_data;
			$this->js_strings .= "var _is_single=0;";
		} else {
			$avail_template->avail_data = $avail_data;
			$this->js_strings .= "var _is_single=1;";
		}
		$report->avail_template = $avail_template;
		$report->multiple_items = $multiple_items;
		$report->label_filter_states = $t->_('Filter on state');
		$label_click_to_view = $t->_('Click to view');
		$report->label_click_to_view = $label_click_to_view;
		$label_click_to_hide = $t->_('Click to hide');
		$report->label_click_to_hide = $label_click_to_hide;
		$report->rpttimeperiod = $rpttimeperiod;
		$report->str_start_date = $str_start_date;
		$report->str_end_date = $str_end_date;
		$report->title = sprintf($t->_('State History for %s'), $label_type);
		$report->objects = $objects;
		$report_duration = $this->end_date - $this->start_date;
		$report->duration = time::to_string($report_duration);
		$report->label_duration = $t->_('Duration');
		$report->label_only_hard_events = $t->_('Show only Hard events');

		$this->js_strings .= "var t1;";

		$this->js_strings .= "var json=".json::encode($trends_data).";";
		$this->js_strings .= "var resizeTimerID = null;";
		$this->js_strings .= "var _label_click_to_view = '".$label_click_to_view."';";
		$this->js_strings .= "var _label_click_to_hide = '".$label_click_to_hide."';";
		$graph_center = ceil((($this->end_date - $this->start_date)/2) + $this->start_date);
		$graph_center = date('c', $graph_center);
		#$this->js_strings .= "var _left_boundary = '".date('c', $this->start_date - 86400)."';";
		$this->js_strings .= "var _graph_center = '".$graph_center."';";
		$this->inline_js .= "onLoad('".date('c', $this->start_date)."', '".date('c', $this->end_date)."');";
		$this->inline_js .= "$(window).resize(function() {onResize();});";

		# decide granularity of trends graph depending on report period
		$magnify = 1;
		$zoneperiod = 'DAY';
		$intervalunit = 'MONTH';
		switch ($report_period) {
			case 'today':
				$magnify = 30;
				$zoneperiod = 'DAY';
				$intervalunit = 'DAY';
				break;
			case 'last24hours': case 'yesterday':
				$magnify = 10;
				$zoneperiod = 'HOUR';
				$intervalunit = 'DAY';
				break;
			case 'thisweek': case 'last7days': case 'lastweek':
				$magnify = 2;
				$zoneperiod = 'DAY';
				$intervalunit = 'DAY';
				break;
			case 'thismonth': case 'last31days': case 'lastmonth':
				$magnify = 15;
				$zoneperiod = 'WEEK';
				$intervalunit = 'MONTH';
				break;
			case 'thisyear':
				$magnify = 20;
				$zoneperiod = 'WEEK';
				$intervalunit = 'MONTH';
				break;
			case 'lastyear':
				$magnify = 2;
				$zoneperiod = 'MONTH';
				$intervalunit = 'MONTH';
				break;
		}
		$this->js_strings .= "var _magnify = '".$magnify."';";
		$this->js_strings .= "var _zoneperiod = '".$zoneperiod."';";
		$this->js_strings .= "var _intervalunit = '".$intervalunit."';";

		$this->template->inline_js = $this->inline_js;
		$this->template->js_strings = $this->js_strings;
	}

	/**
	*	Fetch requested items for a user depending on type (host, service or groups)
	* 	Found data is returned through xajax helper to javascript function populate_options()
	*/
	public function _get_group_member($input=false, $type=false, $erase=true)
	{
		$xajax = $this->xajax;
		return get_xajax::group_member($input, $type, $erase, $xajax);
	}

	/**
	*  	Since a lot of the help texts are identical to the ones
	* 	in teh reports controller we might as well return them to
	* 	save us the extra work.
	*/
	public static function _helptexts($id)
	{
		# filter
		$translate = zend::instance('Registry')->get('Zend_Translate');

		$nagios_etc_path = Kohana::config('config.nagios_etc_path');
		$nagios_etc_path = $nagios_etc_path !== false ? $nagios_etc_path : Kohana::config('config.nagios_base_path').'/etc';

		# Tag unfinished helptexts with @@@HELPTEXT:<key> to make it
		# easier to find those later
		$helptexts = array(
			'filter' => $translate->_("Those filters (Hard Events and State) does currently NOT work together with the 'Filter' box above".
				" and should still be considered as experimental."),
			'show_event_duration' => $translate->_("Check this option to always show event duration in the generated graph. ".
			"This is always the case when selecting a single object. Please note that this might make the graph harder to read with many ".
			"selected objects since the state icon is replaced by a duration bar.")
		);
		if (array_key_exists($id, $helptexts)) {
			echo $helptexts[$id];
		} else
			return Reports_Controller::_helptexts($id);
	}

	/**
	 * Expands a series of groupnames (host or service) into its member objects, and calculate uptime for each
	 * This method is almost identical to reports_controller::_expand_group_request() but some data differs in
	 * self::$options etc
	 *
	 * @param array $arr List of groups
	 * @param string $type The type of objects in $arr. Valid values are "hostgroup" or "servicegroup".
	 * @param mixed $start_date datetime or unix timestamp
	 * @param mixed $end_date datetime or unix timestamp
	 * @global array Report options
	 * @global array Dependent report options
	 * @global array
	 * @global string Error log.
	 * @return array Calculated uptimes.
	 */
	public function _expand_group_request($arr=false, $type='hostgroup', $start_date, $end_date)
	{
		$options = self::$options;
		$dep_vars = self::$dep_vars;
		$err_msg = $this->err_msg;

		if (empty($arr))
			return false;
		if ($type!='hostgroup' && $type!='servicegroup')
			return false;
		$hostgroup = false;
		$servicegroup = false;
		$data_arr = false;
		foreach ($arr as $$type) {
			$rpt_class = new Reports_Model();
			foreach ($options as $var => $new_var) {
				if (!$rpt_class->set_option($new_var, arr::search($_REQUEST, $var, false))) {
					$err_msg .= sprintf($this->translate->_("Could not set option '%s' to %s'"),
						$new_var, Reports_Controller::_convert_yesno_int(arr::search($_REQUEST, $var, false)))."'<br />";
				}
			}
			foreach ($dep_vars as $check => $set)
				if (isset($_REQUEST[$check]) && !empty($_REQUEST[$check]))
					foreach ($set as $dep => $key)
						if (!$rpt_class->set_option($key, $_REQUEST[$dep]))
							$err_msg .= sprintf($this->translate->_("Could not set option '%s' to %s'"),
								$key, $_REQUEST[$dep])."'<br />";

			$rpt_class->set_option(substr($type, 0, strlen($type)).'_name', $$type);
			$rpt_class->set_option('keep_logs', true);
			$data_arr[] = $rpt_class->get_uptime(false, false, $start_date, $end_date, $hostgroup, $servicegroup);
			unset($rpt_class);
		}
		return $data_arr;
	}

	/**
	*	Determine what color to assign to an event
	*/
	public function _state_colors($type='host', $state=false)
	{
		$arr = Reports_Controller::$colors;
		$colors['host'] = array(
			Reports_Model::HOST_UP => $arr['green'],
			Reports_Model::HOST_DOWN => $arr['red'],
			Reports_Model::HOST_UNREACHABLE => $arr['orange'],
			Reports_Model::HOST_PENDING => $arr['grey']
		);
		$colors['service'] = array(
			Reports_Model::SERVICE_OK => $arr['green'],
			Reports_Model::SERVICE_WARNING => $arr['orange'],
			Reports_Model::SERVICE_CRITICAL => $arr['red'],
			Reports_Model::SERVICE_UNKNOWN => $arr['grey'],
			Reports_Model::SERVICE_PENDING => $arr['grey']
		);
		return $colors[$type][$state];
	}

	/**
	*	Translate a state from db to string for use when deciding
	* 	what image to use. Very similar to Current_status_Model::status_text()
	* 	but since the value for PENDING differs from what we use in reports,
	* 	we have to have this method.
	*/
	public function _translate_state_to_string($state=false, $type='host')
	{
		if ($state === false) {
			return false;
		}

		if ($state == -1) {
			return 'pending';
		}
		return strtolower(Current_status_Model::status_text($state, $type));
	}
}
