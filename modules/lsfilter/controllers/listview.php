<?php

class ListView_Controller extends Authenticated_Controller {
	public function index() {
		$this->xtra_js = array();
		$basepath = 'modules/lsfilter/';
		$this->xtra_js[] = $basepath.'js/LSFilter.js';
		$this->xtra_js[] = $basepath.'js/LSFilterLexer.js';
		$this->xtra_js[] = $basepath.'js/LSFilterParser.js';
		$this->xtra_js[] = $basepath.'js/LSFilterPreprocessor.js';
		$this->xtra_js[] = $basepath.'js/LSFilterVisitor.js';
		$this->xtra_js[] = $basepath.'views/themes/default/js/LSFilterVisualizer.js';

		$this->template->js_header = $this->add_view('js_header');
		$this->template->js_header->js = $this->xtra_js;
		$this->template->title = _('List view');
		$this->template->content = $lview = $this->add_view('listview/listview');
		$this->template->disable_refresh = true;
		
		$lview->query = '[hosts] name ~~ "kaka"';
	}
	
	public function fetch_ajax() {
		$query = $this->input->get('q','');
		
		$preprocessor = new LSFilterPP_Core();
		
		$metadata = new LSFilterMetadataVisitor_Core();
		
		$parser = new LSFilter_Core($preprocessor, $metadata);
		$parser->parse( $query );
		
		$setbuilder = new LSFilterSetBuilderVisitor_Core($metadata);
		$parser = new LSFilter_Core($preprocessor, $setbuilder);
		$set = $parser->parse( $query );
		
		foreach( $set->it($metadata->get_columns(),array()) as $elem ) {
			var_dump( $elem );
		}
		die();
	}
}