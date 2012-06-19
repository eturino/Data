<?php

class EtuDev_Data_NestedSet {

	/**
	 * @static
	 *
	 * @param Zend_Db_Table_Abstract $tableObject
	 * @param int                    $id
	 *
	 * @return Zend_Db_Select
	 */
	static public function getSelectPath($tableObject, $id) {

		$tableName = $tableObject->info(Zend_Db_Table_Abstract::NAME);
		$select    = $tableObject->select(false);
		$select->from(array('p' => $tableName));
		$select->join(array('n' => $tableName), 'n.left_ BETWEEN p.left_ AND p.right_ ' . ($id > 1 ? 'AND p.level > 0' : ''), array()); //level > 0 para quitar ROOT, siempre que no estemos pidiendo root
		$select->where('n.id = ?', $id);
		$select->order('p.left_ ASC');
		return $select;
	}

	/**
	 * @static
	 *
	 * @param Zend_Db_Table_Abstract $tableObject
	 * @param int                    $id
	 *
	 * @return Zend_Db_Select
	 */
	static public function getSelectChildrenIds($tableObject, $id, $only_direct_children = true, $include_self = false) {
//		$categories = $this->db->GetAll('SELECT * FROM ' . $this->table . ' WHERE left_ BETWEEN \'' . ($row['left_'] + 1) . '\' AND \'' . $row['right_'] . '\'' . (0 < $levels ? 'AND level BETWEEN ' . ($row['level'] + 1) . ' AND ' . ($row['level'] + $levels) : '') . ('' . ' ' . $where . ' ORDER BY left_'));


		$tableName = $tableObject->info(Zend_Db_Table_Abstract::NAME);
		$select    = $tableObject->select(false);
		$select->from(array('n' => $tableName), array('id'));
		$select->join(array('p' => $tableName), 'n.left_ BETWEEN p.left_ AND p.right_ ' . ($include_self ? '' : 'AND n.id != p.id ') . ($only_direct_children ? 'AND n.level = p.level + 1' : ''), array()); //level > 0 para quitar ROOT
		$select->where('p.id = ?', $id);
		$select->order('n.left_ ASC');
		return $select;
	}


	/**
	 * @static
	 *
	 * @param Zend_Db_Table_Abstract $tableObject
	 * @param int                    $id
	 *
	 * @return Zend_Db_Select
	 */
	static public function getSelectDescendentIds($tableObject, $id) {
		return static::getSelectChildrenIds($tableObject, $id, false, true);
	}
}