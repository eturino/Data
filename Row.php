<?php

class EtuDev_Data_Row extends EtuDev_Data_ObservableRow {

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
		return parent::_insert();
	}

}