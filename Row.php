<?php

class EtuDev_Data_Row extends EtuDev_Data_ObservableRow implements EtuDev_Data_HighlightDataObject {

	/**
	 * to be extended, returns an array with the default data for a new entity
	 *
	 * @return array
	 */
	public function _getDefaultData() {
		return array();
	}


	/**
	 * @return EtuDev_Data_PseudoArray
	 */
	public function _setDefaultDataWhenNull() {
		$this->setValuesOnlyIfNull($this->_getDefaultData());
		return $this;
	}

	protected function _insert() {
		$this->_setDefaultDataWhenNull();
		$this->calculateBeforeModify();
		return parent::_insert();
	}

	protected function _update() {
		$this->calculateBeforeModify();
	}

	protected function calculateBeforeModify() {
		foreach ($this->_getters as $k => $gt) {
			$this->_setDirect($k, $this->$gt());
			$this->addModifiedKeyIfNeeded($k);
		}
	}


	/**
	 * @var array con el highlight data
	 */
	protected $_highlightData = array();

	/**
	 * @param array|SolrObject|Traversable $data
	 * @return EtuDev_Data_Row
	 */
	public function setHighlightData($data){
		$a = array();
		foreach($data as $field => $value) {
			$a[$field] = $value[0];
		}

		$this->_highlightData = $a;
		return $this;
	}


	public function isHighlighted($origkey){
		$key = $this->getDefinedAlias($origkey);
		return $this->_isHighlighted($key);
	}

	protected function _isHighlighted($key){
		return @isset($this->_highlightData[$key]);
	}

	public function getDirectHighlighted($origkey){
		$key = $this->getDefinedAlias($origkey);
		return $this->_getDirectFromHighlightedData($key);
	}

	protected function _getDirectFromHighlightedData($key){
		return @$this->_highlightData[$key];
	}

	static public function getIdField(){
		return 'id';
	}

}