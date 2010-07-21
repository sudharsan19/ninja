<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Reads program status data
 */
class Program_status_Model extends Model
{
	/**
	 * Fetch all info from program_status table
	 */
	public function get_all()
	{
		$db = new Database();
		$sql = "SELECT * FROM program_status";
		$res = $db->query($sql);
		return (!$res || count($res) == 0) ? false : $res;
	}

	public function list_program_status()
	{
		$db = new Database();
		$sql = "SELECT instance_name, last_alive, is_running FROM program_status order by instance_name";
		$res = $db->query($sql);
		return (!$res || count($res) == 0) ? false : $res;
	}

	/**
	*	Check last_alive from program_status
	*	to detect when data was updated
	*/
	public function last_alive()
	{
		$db = new Database();
		$sql = "SELECT last_alive FROM program_status";
		$res = $db->query($sql);
		return (!$res || count($res) == 0) ? false : $res->current()->last_alive;
	}

	/**
	*	Fetch current global settings for notifications
	*	and active_service_checks
	*/
	public function notifications_checks()
	{
		$db = new Database();
		$sql = "SELECT notifications_enabled, active_service_checks_enabled FROM program_status";
		$res = $db->query($sql);
		return (!$res || count($res) == 0) ? false : $res;
	}
}
