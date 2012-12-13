<?php

class ListView_Controller extends Authenticated_Controller {
	public function index($q = "[hosts] state = 0") {
		$this->xtra_js = array();
		$query = $this->input->get('q', $q);
		
		
		$basepath = 'modules/lsfilter/';
		$ormpath = 'modules/livestatusorm/';

		$this->xtra_js[] = $ormpath.'js/LivestatusStructure.js';

		$this->xtra_js[] = $basepath.'js/LSFilter.js';
		$this->xtra_js[] = $basepath.'js/LSFilterLexer.js';
		$this->xtra_js[] = $basepath.'js/LSFilterParser.js';
		$this->xtra_js[] = $basepath.'js/LSFilterPreprocessor.js';
		$this->xtra_js[] = $basepath.'js/LSFilterVisitor.js';
		$this->xtra_js[] = $basepath.'views/themes/default/js/LSFilterRenderer.js';
		$this->xtra_js[] = $basepath.'views/themes/default/js/LSFilterSearch.js';
		$this->xtra_js[] = $basepath.'views/themes/default/js/LSFilterVisualizer.js';

		$this->template->js_header = $this->add_view('js_header');
		$this->template->js_header->js = $this->xtra_js;

		$this->template->css_header = $this->add_view('css_header');
		$this->xtra_css = array();
		$this->xtra_css[] = $basepath.'views/themes/default/css/LSFilterStyle.css';
		$this->template->css_header->css = $this->xtra_css;

		$this->template->title = _('List view');
		$this->template->content = $lview = $this->add_view('listview/listview');
		$this->template->disable_refresh = true;

		$lview->query = $query;
	}

	public function fetch_ajax() {
		$query = $this->input->get('query','');
		$columns = $this->input->get('columns',false);
		$sort = $this->input->get('sort',array());
		$sort_asc = $this->input->get('sort_asc',true);
		
		$limit = $this->input->get('limit',false);
		$offset = $this->input->get('offset',false);

		if( $limit === false )
			json::ok( array( 'status' => 'error', 'data' => "No limit specified") );
		
		/* TODO: Fix sorting better sometime
		 * Do it though ORM more orm-ly
		 * Check if columns exists and so on...
		 */
		$sort = array_map(function($el){return str_replace('.','_',$el);},$sort);
		
		if(!$sort_asc)
			$sort = array_map(function($el){return $el.' desc';},$sort);
		
		try {
			$result_set = ObjectPool_Model::get_by_query( $query );
			
			$data = array();
			foreach( $result_set->it($columns,$sort,$limit,$offset) as $elem ) {
				$data[] = $elem->export();
			}

			json::ok( array( 'status' => 'success', 'totals' => $result_set->get_totals(), 'data' => $data ) );
		} catch( Exception $e ) {
			json::ok( array( 'status' => 'error', 'data' => $e->getMessage() ) );
		}
	}

	public function fetch_saved_queries() {
		$queries = LSFilter_Saved_Queries_Model::get_queries();
		json::ok( array( 'status' => 'success', 'data' => $queries ) );
	}

	public function save_query() {
		$name = $this->input->get('name',false);
		$query = $this->input->get('query','');
		$scope = $this->input->get('scope','user');

		try {
			
			$result = LSFilter_Saved_Queries_Model::save_query($name, $query, $scope);
			
			if( $result !== false )
				json::ok( array('status'=>'error', 'data' => $result) );
			
			
			json::ok( array( 'status' => 'success', 'data' => 'success' ) );
		}
		catch( Exception $e ) {
			json::ok( array( 'status' => 'error', 'data' => $e->getMessage() ) );
		}
	}
}