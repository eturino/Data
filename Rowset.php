<?php

class EtuDev_Data_Rowset extends Zend_Db_Table_Rowset {

	protected $firstKey;
	protected $endKey;

	public function __construct(array $config) {
		parent::__construct($config);
		if ($this->_data) {
			$this->firstKey = key(array_slice($this->_data, 0, 1, TRUE));
			$this->endKey   = key(array_slice($this->_data, -1, 1, TRUE));
		}
	}

	/**
	 * @return array
	 */
	public function toArrayOfRows() {
		if (count($this->_rows) != count($this->_data)) {
			$a = array();
			foreach ($this as $r) {
				$a[] = $r;
			}
			return $a;
		}

		return $this->_rows;
	}

	public function firstRow() {
		if($this->_data){
			return $this->getRow($this->firstKey);
		}
		return null;
	}

	public function endRow() {
		if($this->_data){
			return $this->getRow($this->endKey);
		}

		return null;
	}

}