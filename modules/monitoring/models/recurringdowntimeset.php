<?php


/**
 * Autogenerated class RecurringDowntimeSet_Model
 *
 * @todo: documentation
 */
class RecurringDowntimeSet_Model extends BaseRecurringDowntimeSet_Model {
	/**
	 * Return resource name of this object
	 * @return string
	 */
	public function mayi_resource() {
		return "monitor.monitoring.downtimes.recurring";
	}

	protected function get_auth_filter() {
		$auth = Auth::instance();
		$has_auth = array(
			'hosts' => $auth->authorized_for('host_view_all'),
			'services' => $auth->authorized_for('service_view_all'),
			'hostgroups' => $auth->authorized_for('hostgroup_view_all'),
			'servicegroups' => $auth->authorized_for('servicegroup_view_all')
		);

		$auth_filter = new LivestatusFilterOr();
		$db = new Database();
		foreach ($has_auth as $type => $godmode) {
			$filter = new LivestatusFilterAnd();
			$filter->add(new LivestatusFilterMatch('downtime_type', $type, '='));
			// there are only four valid values, so always checking them is cheap	
			if ($godmode) {
				$auth_filter->add($filter);
				continue;
			}

			// this, though, isn't cheap :(
			$res_schedules = $db->query('SELECT recurring_downtime.id FROM recurring_downtime WHERE downtime_type = '.$db->escape($type));
			$schedules = array();
			$poolname = ucfirst(substr($type, 0, -1)).'Pool_Model';
			$id_check = new LivestatusFilterOr();
			foreach ($res_schedules as $schedule) {
				$set = $poolname::none();
				$objects = $db->query('SELECT recurring_downtime_objects.object_name FROM recurring_downtime_objects WHERE recurring_downtime_id = '.$schedule->id);
				$schedule_filter = new LivestatusFilterAnd();
				foreach ($objects as $object) {
					if ($type == 'services') {
						list($hname, $sdesc) = explode(';', $object->object_name);
						$set = $set->union($poolname::all()->reduce_by('host.name', $hname, '=')->reduce_by('description', $sdesc, '='));
					}
					else {
						$set = $set->union($poolname::all()->reduce_by('name', $object->object_name, '='));
					}
				}
				if (count($set) == count($objects)) {
					$id_check->add(new LivestatusFilterMatch('id', $schedule->id, '='));
				}
			}
			$filter->add($id_check);
			$auth_filter->add($filter);
		}
		$result_filter = new LivestatusFilterAnd();
		$result_filter->add($this->filter);
		$result_filter->add($auth_filter);
		return $result_filter;
	}
}
