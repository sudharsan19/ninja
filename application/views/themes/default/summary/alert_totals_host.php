<?php defined('SYSPATH') OR die("No direct access allowed");?>

<div style="margin: 0px 1%; border: 1px solid white">
	<h2 style="margin-top: 11px; margin-bottom: 0px"><?php echo $label_overall_totals ?></h2>
	<?php $this->_print_duration($options['start_time'], $options['end_time']); ?>
	<?php
	foreach ($result as $host_name => $ary) {
		$this->_print_alert_totals_table($label_host_alerts, $ary['host'], $host_state_names, $ary['host_totals'],$host_name);
		$this->_print_alert_totals_table($label_service_alerts, $ary['service'], $service_state_names, $ary['service_totals'],$host_name);
	}
	//printf("Report completed in %.3f seconds<br />\n", $completion_time);
	?>
</div>
