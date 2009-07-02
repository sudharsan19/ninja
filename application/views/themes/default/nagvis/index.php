<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>

<div style="margin-left: 20px;">
	<iframe name="nagvis" src="<?php echo Kohana::config('config.nagvis_path'); ?>" width="100%" height="700" frameborder="no">
		Could not load NagVis!
	</iframe>
	<div>
		<ul>
			<li><a href="<?php echo Kohana::config('config.nagvis_path'); ?>nagvis/index.php" target="nagvis">Index</a></li>
			<?php
			foreach ($maps as $map)
			{
				echo '<li>';
				echo '<a href="'.Kohana::config('config.nagvis_path').'nagvis/index.php?map='.$map.'" target="nagvis">'.$map.'</a>';
				echo '&nbsp;';
				echo '(<a href="'.Kohana::config('config.nagvis_path').'wui/index.php?map='.$map.'" target="nagvis">edit</a>)';
				echo '</li>';
			}
			?>
			<li><a href="<?php echo Kohana::config('config.nagvis_path'); ?>nagvis/index.php?automap=1" target="nagvis">Automap</a></li>
		</ul>
	</div>
</div>
