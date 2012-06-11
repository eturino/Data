<?php

class EtuDev_Data_PseudoArray extends EtuDev_PseudoArray_Object {

	public function __construct($originalData = null, $propertiesFlag = null) {
		if (is_array($originalData) && count($originalData) == 4 && isset($originalData['table']) && isset($originalData['data']) && isset($originalData['stored'])) {
			// treated like a Zend Db Row
			$originalData = $originalData['data'];
		}
		parent::__construct($originalData, $propertiesFlag);
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
}