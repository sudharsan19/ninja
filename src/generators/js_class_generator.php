<?php

abstract class js_class_generator {
	protected $fp;
	protected $indent_lvl = array();
	protected $class_suffix = '';
	protected $class_dir = 'js';
	protected $class_basedir = '.';
	protected $classname;
	
	public function generate( $skip_generated_note = false ) {
		$class_dir = dirname( $this->get_filename() );
		
		if( !is_dir( $class_dir ) && !mkdir( $class_dir, 0755, true ) )
			throw new GeneratorException( "Could not create dir $class_dir" );
		
		$this->fp = fopen( $this->get_filename(), 'w' );

		if( $this->fp === false )
			throw new GeneratorException( "Could not open ".$this->get_filename()." for writing" );
		
		if( !$skip_generated_note )
			$this->comment( "\nNOTE!\n\nThis is an auto generated file. Changes to this file will be overwritten!\n" );
	}
	
	public function set_class_suffix( $class_suffix ) {
		$this->class_suffix = $class_suffix;
	}
	
	public function set_class_dir( $class_dir ) {
		$this->class_dir = $class_dir;
	}
	
	public function set_basedir( $class_basedir ) {
		$this->class_basedir = $class_basedir;
	}
	
	public function exists() {
		return file_exists( $this->get_filename() );
	}
	
	public function get_classname() {
		return $this->classname . $this->class_suffix;
	}
	
	protected function get_filename() {
		return $this->class_basedir . DIRECTORY_SEPARATOR . $this->class_dir . DIRECTORY_SEPARATOR . $this->classname . '.js';
	}
	
	protected function init_class( $args = array() ) {
		$argstr = implode(', ', $args);
		$this->write();
		$this->write( 'var '.$this->get_classname() . ' = function '.$this->get_classname().'('.$argstr.'){' );
	}
	
	protected function finish_class() {
		$this->write( "};" );
	}
	
	protected function variable( $name, $default = null ) {
		$this->write( "this.$name = %s;", $default );
	}
	
	protected function init_function( $name, $args = array() ) {
		$argstr = implode(', ', $args);
		if( $name === false ) {
			$this->write( "function($argstr) {" );
		} else {
			$this->write( "this.$name = function($argstr) {" );
		}
	}
	
	protected function finish_function() {
		$this->write( "};" );
		$this->write();
	}
	
	protected function comment( $comment ) {
		$lines = explode( "\n", $comment );
		$curlvl = array_sum( $this->indent_lvl );
		foreach( $lines as $line ) {
			fwrite( $this->fp, str_repeat( "\t", $curlvl ) . "// " . trim($line) . "\n" );
		}
	}
	protected function write( $block = '' ) {
		$args = func_get_args();
		$block = array_shift( $args );
		$args_str = array_map( function($var){return json_encode($var);}, $args );
		$block = vsprintf($block,$args_str);

		$lines = explode( "\n", $block );
		foreach( $lines as $line ) {
			for($i=substr_count( $line, '}' ); $i>0; $i--)
				array_pop( $this->indent_lvl );
			$curlvl = array_sum( $this->indent_lvl );
			if( substr( trim($line), 0, 4) == 'case' )
				$curlvl--;
			fwrite( $this->fp, str_repeat( "\t", $curlvl ) . $line . "\n" );
			for($i=substr_count( $line, '{' ); $i>0; $i--)
				$this->indent_lvl[] = (strpos( $line, "switch" ) !== false) ? 2 : 1;
		}
	}
}