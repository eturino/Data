<?php

trait EtuDev_Data_Traits_TableWithId {

	public function getById($id) {
		return $this->getRowById($id);
	}

	public function getRowById($id) {
		return $this->fetchRow(array('id = ?' => $id));
	}

	public function delete($where) {
		if (is_numeric($where)) {
			return $this->delete(array('id = ?' => $where));
		}

		return parent::delete($where);
	}
}