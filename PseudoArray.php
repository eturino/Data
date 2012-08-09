<?php

class EtuDev_Data_PseudoArray extends EtuDev_PseudoArray_Object implements EtuDev_Data_HighlightDataObject {

	public function __construct($originalData = null) {
		if (is_array($originalData) && count($originalData) == 4 && isset($originalData['table']) && isset($originalData['data']) && isset($originalData['stored'])) {
			// treated like a Zend Db Row
			$originalData = $originalData['data'];
		}
		parent::__construct($originalData);
	}

	/**
	 *
	 * @uses setValuesFromOriginalData()
	 * @uses _getDefaultData()
	 * @return EtuDev_Data_PseudoArray
	 */
	public function _setDefaultData() {
		$this->setValuesFromOriginalData($this->_getDefaultData());
		return $this;
	}

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


	/**
	 * @var array con el highlight data
	 */
	protected $_highlightData = array();

	/**
	 * @param array|SolrObject|Traversable $data
	 * @return EtuDev_Data_PseudoArray
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