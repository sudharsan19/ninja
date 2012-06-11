<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Help class for reports
 */
class reports_Core
{
	/** Array of month_number => days_in_month */
	public static $days_per_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	/** Array of weekday names */
	public static $valid_weekdays = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
	/** Array of month names */
	public static $valid_months = array
	(
		1  => 'january',
		2  => 'february',
		3  => 'march',
		4  => 'april',
		5  => 'may',
		6  => 'june',
		7  => 'july',
		8  => 'august',
		9  => 'september',
		10 => 'october',
		11 => 'november',
		12 => 'december'
	);

	/**
	 * Called by PHP as an assert callback to format errors usefully
	 */
	public function lib_reports_assert_handler($filename, $line, $code)
	{
		if (!posix_isatty(0))
			echo "<pre>\n";

		echo "ASSERT FAILED\n";
		debug_print_backtrace();

		echo "File: $filename\n\n";
		echo "Line: $line\n";
		echo "Assertion: $code\n";

		if (!posix_isatty(0))
			echo "</pre>\n";
	}

	/**
	 * Generate a percentage easily
	 *
	 * @param $dividend The whole
	 * @param $divisor The part
	 * @return The percentage
	 */
	public function percent($dividend, $divisor)
	{
		if (!$dividend || !$divisor)
			return 0;

		return ($dividend / $divisor) * 100;
	}

	/**
	 * Assigns color to labels to be used in a piechart
	 */
	public function get_color_values($labels=false)
	{
		if (empty($labels)) return false;
		$green 	= '#88cd18';
		$yellow = '#ffd92f';
		$orange = '#ff9d08';
		$red 	= '#f7261b';
		$grey 	= '#a0a084';

		$return = false;
		$colors = array(
			'OK' => $green,
			'UP' => $green,
			'WARNING' => $yellow,
			'UNREACHABLE' => $orange,
			'UNKNOWN' => $orange,
			'DOWN' => $red,
			'CRITICAL' => $red,
			'UNDETERMINED' => $grey
		);
		foreach ($labels as $key) {
			$return[] = array($colors[strtoupper($key)], NULL, NULL);
		}
		return $return;
	}

	/**
	 * Converts an html hexadecimal color from the form "#rrggbb" to
	 * its separate components.
	 * @param $color_str string: Color in html hex
	 * @return False on error. Array of 3 decimal values on success
	*/
	public function convert_hex_color($color_str)
	{
		if (empty($color_str)) return false;
		$color_str = str_replace('#', '', $color_str);
		$c1 = substr($color_str, 0, 2);
		$c2 = substr($color_str, 2, 2);
		$c3 = substr($color_str, 4, 2);
		return array(hexdec($c1), hexdec($c2), hexdec($c3) );
	}

	/**
	 * Fetch date ranges from reports class
	 * @return Array of date ranges
	 */
	public function get_date_ranges()
	{
		$sql = "SELECT MIN(timestamp) AS min_date, ".
				"MAX(timestamp) AS max_date ".
			"FROM ".self::db_table;
		$db = Database::instance();
		$res = $db->query($sql);

		if (!$res)
			return false;
		$row = $res->current();
		$min_date = $row->min_date;
		$max_date = $row->max_date;
		return array($min_date, $max_date);
	}

	/**
	*	Format report value output
	*/
	public function format_report_value($val)
	{
		$return = 0;
		if ($val == '0.000' || $val == '100.000')
			$return = number_format($val, 0);
		else
			$return = number_format($val, 3);

		return $return;
	}

	/**
	 * Validate report item
	 * @param $k Unused FIXME
	 * @param $data A data array that should have states
	 * return true if valid, false otherwise
	 */
	public function is_proper_report_item($k, $data)
	{
		if (is_array($data) && !empty($data['states']) && is_array($data['states']))
			return true;

		return false;
	}

	/**
	*	Create common translated javascript strings
	*/
	public static function js_strings()
	{
		$js_strings = false;
		$js_strings .= "var _ok_str = '"._('OK')."';\n";
		$js_strings .= "var _cancel_str = '"._('Cancel')."';\n";
		$js_strings .= "var _reports_err_str_noobjects = '".sprintf(_("Please select what objects to base the report on by moving %sobjects from the left selectbox to the right selectbox"), '<br />')."';\n";
		$js_strings .= "var _reports_err_str_nostatus = '"._("You must provide at least one status to filter on")."';\n";
		$js_strings .= "var _reports_invalid_startdate = \""._("You haven't entered a valid Start date")."\";\n";
		$js_strings .= "var _reports_invalid_enddate = \""._("You haven't entered a valid End date")."\";\n";
		$js_strings .= "var _reports_invalid_timevalue = \""._("You haven't entered a valid time value")."\";\n";
		$js_strings .= "var _reports_enddate_infuture = '".sprintf(_("You have entered an End date in the future.%sClick OK to change this to current time or cancel to modify."), '\n')."';\n";
		$js_strings .= "var _reports_enddate_lessthan_startdate = '"._("You have entered an End date before Start Date.")."';\n";
		$js_strings .= "var _reports_send_now = '"._('Send this report now')."';\n";
		$js_strings .= "var _reports_send = '"._('Send')."';\n";
		$js_strings .= "var _reports_errors_found = '"._('Found the following error(s)')."';\n";
		$js_strings .= "var _reports_please_correct = '"._('Please correct this and try again')."';\n";
		$js_strings .= "var _reports_schedule_interval_error = '"._(' -Please select a schedule interval')."';\n";
		$js_strings .= "var _reports_schedule_recipient_error = '"._(' -Please enter at least one recipient')."';\n";
		$js_strings .= "var _reports_invalid_email = '"._('You have entered an invalid email address')."';\n";
		$js_strings .= "var _label_direct_link = '"._('Direct link')."';\n";

		return $js_strings;
	}
}
