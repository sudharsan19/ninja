<?php

class LalrHTMLVisualizationGenerator {
	private $name;
	private $fsm;
	private $grammar;
	private $fp;
	private $filename;
	private $dir = 'html';

	public function __construct( $parser_name, $fsm, $grammar ) {
		$this->name = $parser_name;
		$this->filename = $parser_name . "Visualization";
		$this->grammar = $grammar;
		$this->fsm = $fsm;


		$this->goto_map = array();
		foreach( $this->fsm->get_statetable() as $state_id => $map ) {
			foreach( $map as $symbol => $action_arr ) {
				list( $action, $target ) = explode(':',$action_arr,2);
				if( $action == 'goto' ) {
					if( !isset( $this->goto_map[$symbol] ) )
						$this->goto_map[$symbol] = array();
					$this->goto_map[$symbol][$state_id] = $target;
				}
			}
		}
	}

	public function generate($moduledir) {
		$htmldir = $moduledir . DIRECTORY_SEPARATOR . $this->dir;
		if( !is_dir( $htmldir ) && !mkdir( $htmldir, 0755, true ) )
			throw new GeneratorException( "Could not create dir $class_dir" );

		$filename = $htmldir . DIRECTORY_SEPARATOR . $this->filename.'.html';
		printf("  -> %s\n", $filename);
		$this->fp = fopen( $filename, 'w' );

		ob_start( array( $this, 'write_block'), 1024 );
		$this->build_html();
		ob_end_flush();

		fclose( $this->fp );
	}

	public function write_block( $block ) {
		fwrite( $this->fp, $block );
	}

	private function build_html() {
?>
<!DOCTYPE html>
<html>
<head>
<title>Visualization of parser <?php echo htmlentities($this->name);?></title>
<style type="text/css">
td, th {
	margin: 0;
}

.bordered {
	border-right: 1px solid #bbbbbb;
}

td, th {
	vertical-align: top;
	text-align: left;
}
.hard_top {
	border-top: 3px solid black;
}

.inner_table {
	padding: 0px;
}

.inner_table table {
	width: 100%;
	margin: 0;
	padding: 0;
}

.inner_table td, .inner_table th {
	border: 0;
}

td.bar, th.bar {
	background-color: #dddddd;
}

td.target {
	text-decoration: underline;
}

td.mark {
	background-color: #dddddd;
}

</style>
</head>
<body>
<h1>Visualization of parser <?php echo htmlentities($this->name);?></h1>
<table>
<tr><td style="width: 50%;">
<h2>Lexical analysis</h2>
<table>
<?php foreach( $this->grammar->get_tokens() as $token => $match ): ?>
<tr>
<th><?php echo htmlentities( $token ); ?></th>
<td><?php echo htmlentities($match); ?></td>
</tr>
<?php endforeach; ?>
</table>
</td><td style="width: 50%;">
<h2>Grammar</h2>
<table>
<?php foreach( $this->grammar->get_rules() as $item ):?>
<tr>
<th><?php echo htmlentities($item->get_name());?></th>
<td class="target"><?php echo htmlentities($item->generates());?></td>
<td>=</td>
<?php foreach( $item->get_symbols() as $i=>$sym ): ?>
<td><?php echo $sym; ?></td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
</td></tr>
</table>

<h2>LR Parser table</h2>
<table class="visible" cellspacing="0">
<tr>
<th colspan="2">State</th>
<th class="bar"></th>
<th>Error handler</th>
<th class="bar"></th>
<?php foreach( $this->grammar->terminals() as $sym ): if($sym[0]=='_') continue;?>
<th><?php echo htmlentities($sym); ?></th>
<?php endforeach; ?>
<th class="bar"></th>
<?php foreach( $this->grammar->non_terminals() as $sym ): ?>
<th><?php echo htmlentities($sym); ?></th>
<?php endforeach; ?>
</tr>

<?php foreach( $this->fsm->get_statetable() as $state_id => $map ):?>
<tr>
<th class="hard_top bordered"><?php echo htmlentities($state_id); ?></th>
<td class="inner_table hard_top bordered">
<table>
<?php foreach( $this->fsm->get_state($state_id)->closure() as $item ):?>
<tr>
<th><?php echo htmlentities($item->get_name());?></th>
<td class="target"><?php echo htmlentities($item->generates());?></td>
<td>=</td>
<?php foreach( $item->get_symbols() as $i=>$sym ): ?>
<td<?php if( $item->get_ptr() == $i ) echo ' class="mark"';?>><?php echo $sym; ?></td>
<?php endforeach; ?>
<?php if( $item->complete() ):?><td class="mark">&nbsp;</td><?php endif;?>
</tr>
<?php endforeach; ?>
</table>
</td>
<td class="bar hard_top bordered"></td>
<td class="hard_top bordered"><?php echo $this->fsm->get_default_error_handler($state_id); ?></td>
<td class="bar hard_top bordered"></td>
<?php foreach( $this->grammar->terminals() as $sym ): if($sym[0]=='_') continue; ?>
<td class="hard_top bordered"><?php
if( isset( $map[$sym] ) ) {
	list($action, $target) = explode(':',$map[$sym],2);
	print $action.'<br/>'.$target;
}
?></td>
<?php endforeach; ?>
<td class="bar hard_top bordered"></td>
<?php foreach( $this->grammar->non_terminals() as $sym ): ?>
<td class="hard_top bordered"><?php
if( isset( $map[$sym] ) ) {
	list($action, $target) = explode(':',$map[$sym],2);
	print $action.'<br/>'.$target;
}
?></td>
<?php endforeach; ?>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>
<?php
	}
}