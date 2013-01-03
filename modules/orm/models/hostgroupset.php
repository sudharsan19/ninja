<?php

require_once( dirname(__FILE__).'/base/basehostgroupset.php' );

class HostGroupSet_Model extends BaseHostGroupSet_Model {
	public function validate_columns($columns) {
		$columns = parent::validate_columns($columns);

		if( in_array( 'host_stats', $columns ) ) {
			$columns = array_diff( $columns, array('host_stats') );
			$columns[] = 'name';
		}
		if( in_array( 'service_stats', $columns ) ) {
			$columns = array_diff( $columns, array('service_stats') );
			$columns[] = 'name';
		}
		
		return $columns;
	}
}