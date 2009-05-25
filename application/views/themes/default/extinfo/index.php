<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<?php
if (!empty($widgets)) {
	foreach ($widgets as $widget) {
		echo $widget;
	}
}
?>

<div id="page_links">
	<?php
	if (isset($page_links)) {
		foreach ($page_links as $label => $link) {
			?>
			<li><?php echo html::anchor($link, $label) ?></li>
			<?php
		}
	}
	?>
</div>

<div class="widget left w98" id="extinfo_host-info">
	<table>
		<colgroup>
			<col style="width: 80px" />
			<col style="width: auto" />
		</colgroup>
		<tr>
			<td class="white"><strong><?php echo ucfirst($lable_type) ?></strong></td>
			<td class="white"><?php echo $main_object ?></td>
		</tr>
		<?php echo !empty($main_object_alias) ? '<tr><td class="white"><strong>'.$this->translate->_('Alias').'</strong></td><td class="white">'.$main_object_alias.'</td></tr>' : '' ?>
		<tr>
			<td class="white"><strong><?php echo $this->translate->_('IP address');?></strong></td>
			<td class="white">
				<?php echo isset($host_address) ? $host_address : ''; ?>
				<?php
					if ($type == 'service') {
						echo $lable_on_host.'<br />';
						echo (isset($host) ? ucfirst($host) : '').'<br />';
						echo isset($host_alias) ? '('.$host_alias.')' : '';
						echo !empty($host_link) ? '('.$host_link.')' : '';
					}
				?>
			</td>
		</tr>
		<tr>
			<td class="white"><strong><?php echo $lable_member_of ?></strong></td>
			<td class="white"><?php echo !empty($groups) ? implode(', ', $groups) : $no_group_lable ?></td>
		</tr>
	</table>
</div>

<?php
if (!empty($commands))
	echo $commands;
?>
<div class="widget left" id="extinfo_current">

	<table>
	<caption><?php echo $this->translate->_('Host State Information'); ?></caption>
		<tr class="odd">
			<td style="width: 160px" class="bt"><?php echo $lable_current_status ?></td>
			<td class="bt">
				<?php echo html::image('/application/views/themes/default/icons/12x12/shield-'.strtolower($current_status_str).'.png', array('alt' => $current_status_str, 'style' => 'margin-bottom: -2px'));?>
				<?php echo ucfirst(strtolower($current_status_str)) ?>
				(<?php echo $lable_for ?> <?php echo $duration ? $duration : $na_str ?>)
			</td>
		</tr>
		<tr class="even">
			<td><?php echo $lable_status_information ?></td>
			<td style="white-space: normal"><?php echo $status_info ?></td>
		</tr>
		<tr class="odd">
			<td><?php echo $lable_perf_data ?></td>
			<td style="white-space: normal"><?php echo $perf_data ?></td>
		</tr>
		<tr class="even">
			<td><?php echo $lable_current_attempt ?></td>
			<td><?php echo $current_attempt ?>/<?php echo $max_attempts ?>(<?php echo $state_type ?>)</td>
		</tr>
		<tr class="odd">
			<td><?php echo $lable_last_check ?></td>
			<td><?php echo $last_check ? date($date_format_str, $last_check) : $na_str ?></td>
		</tr>
		<tr class="even">
			<td><?php echo $lable_check_type ?></td>
			<td>
				<?php echo html::image('/application/views/themes/default/icons/12x12/shield-'.strtolower($check_type).'.png',array('alt' => $check_type, 'style' => 'margin-bottom: -2px; margin-right: 2px'));?>
				<?php echo ucfirst(strtolower($check_type)) ?>
			</td>
		</tr>
		<tr class="odd">
			<td><?php echo $lable_check_latency_duration ?></td>
			<td><?php echo $check_latency ?> / <?php echo $execution_time ?> <?php echo $lable_seconds ?></td>
		</tr>
		<tr class="even">
			<td><?php echo $lable_next_scheduled_check ?></td>
			<td><?php echo $next_check ? date($date_format_str, $next_check) : $na_str ?></td>
		</tr>
		<tr class="odd">
			<td><?php echo $lable_last_state_change ?></td>
			<td><?php echo $last_state_change ? date($date_format_str, $last_state_change) : $na_str ?></td>
		</tr>
		<tr class="even">
			<td><?php echo $lable_last_notification ?></td>
			<td><?php echo $last_notification ?>&nbsp;(<?php echo $lable_notifications ?> <?php echo $current_notification_number ?>)</td>
		</tr>
		<tr class="odd">
			<td><?php echo $lable_flapping ?></td>
			<td>
				<?php echo html::image('/application/views/themes/default/icons/12x12/flapping-'.strtolower($flap_value).'.png',$flap_value);?>
				<?php echo ucfirst(strtolower($flap_value)).' '.$percent_state_change_str; ?>
			</td>
		</tr>
		<tr class="even">
			<td><?php echo $lable_in_scheduled_dt ?></td>
			<td>
				<?php echo html::image('/application/views/themes/default/icons/12x12/sched-downtime-'.strtolower($scheduled_downtime_depth).'.png',$scheduled_downtime_depth);?>
				<?php echo ucfirst(strtolower($scheduled_downtime_depth)) ?>
			</td>
		</tr>
		<tr class="odd">
			<td><?php echo $lable_last_update ?></td>
			<td><?php echo $last_update ? date($date_format_str, $last_update) : $na_str ?> <?php echo $last_update_ago ?></td>
		</tr>
		<tr class="even">
			<td style="width: 160px"><?php echo $lable_active_checks ?></td>
			<td>
				<?php echo html::image('/application/views/themes/default/icons/12x12/shield-'.strtolower($active_checks_enabled).'.png',$active_checks_enabled);?>
				<?php echo ucfirst(strtolower($active_checks_enabled)) ?>
			</td>
		</tr>
		<tr class="even">
			<td><?php echo $lable_passive_checks ?></td>
			<td>
				<?php echo html::image('/application/views/themes/default/icons/12x12/shield-'.strtolower($active_checks_enabled).'.png',$active_checks_enabled);?>
				<?php echo ucfirst(strtolower($passive_checks_enabled)) ?>
			</td>
		</tr>
		<tr class="odd">
			<td><?php echo $lable_obsessing ?></td>
			<td>
				<?php echo html::image('/application/views/themes/default/icons/12x12/shield-'.strtolower($active_checks_enabled).'.png',$active_checks_enabled);?>
				<?php echo ucfirst(strtolower($obsessing)) ?>
			</td>
		</tr>
		<tr class="even">
			<td><?php echo $lable_notifications ?></td>
			<td>
				<?php echo html::image('/application/views/themes/default/icons/12x12/shield-'.strtolower($active_checks_enabled).'.png',$active_checks_enabled);?>
				<?php echo ucfirst(strtolower($notifications_enabled)) ?>
			</td>
		</tr>
		<tr class="odd">
			<td><?php echo $lable_event_handler ?></td>
			<td>
				<?php echo html::image('/application/views/themes/default/icons/12x12/shield-'.strtolower($active_checks_enabled).'.png',$active_checks_enabled);?>
				<?php echo ucfirst(strtolower($event_handler_enabled)) ?>
			</td>
		</tr>
		<tr class="even">
			<td><?php echo $lable_flap_detection ?></td>
			<td>
				<?php echo html::image('/application/views/themes/default/icons/12x12/shield-'.strtolower($active_checks_enabled).'.png',$active_checks_enabled);?>
				<?php echo ucfirst(strtolower($flap_detection_enabled)) ?>
			</td>
		</tr>
	</table>
</div>

<?php
if (isset($comments))
	echo $comments;
?>