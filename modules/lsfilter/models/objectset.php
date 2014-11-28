<?php

require_once( dirname(__FILE__).'/base/baseobjectset.php' );

/**
 * Describes a set of objects from livestatus
 */
abstract class ObjectSet_Model extends BaseObjectSet_Model {
	/**
	 * Return resource name of this object
	 * @return string
	 */
	public function mayi_resource() {
		throw new ORMException("The " . $this->table . " model set have not defined mayi resource(s), I don't know if you are allowed to be here");
	}

	/**
	 * Get statistics about the set, by default only the count
	 */
	public function get_totals() {
		return array('count' => array($this->get_query(), count($this)));
	}

	/**
	 * Get the query representing the set
	 */
	public function get_query() {
		return '['.$this->table.'] '.$this->filter->visit(new LSFilterQueryBuilderVisitor(), 0);
	}

	/**
	 * Get the first matching object in the set
	 */
	public function one($columns = array()) {
		if($columns) {
			$columns = (array) $columns;
		}
		return $this->it($columns, array(),1,0)->current();
	}

	/**
	 * FOR TESTING PURPOSE!
	 *
	 * Visit filter with an visitor and return the result
	 */
	public function test_visit_filter(LivestatusFilterVisitor $visitor, $data) {
		return $this->filter->visit($visitor,$data);
	}
}
