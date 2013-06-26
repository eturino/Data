<?php

class EtuDev_Data_Rowset extends Zend_Db_Table_Rowset
{

    protected $firstKey;
    protected $endKey;

    public function __construct(array $config)
    {
        parent::__construct($config);
        $this->refreshKeys();
    }

    /**
     * @return array
     */
    public function toArrayOfRows()
    {
        if (count($this->_rows) != count($this->_data)) {
            $a = array();
            foreach ($this as $r) {
                $a[] = $r;
            }
            return $a;
        }

        return $this->_rows;
    }

    public function firstRow()
    {
        if ($this->_data) {
            return $this->getRow($this->firstKey);
        }
        return null;
    }

    public function endRow()
    {
        if ($this->_data) {
            return $this->getRow($this->endKey);
        }

        return null;
    }

    protected $total;

    public function getTotal()
    {
        if (is_null($this->total)) {
            $table = $this->getTable();
            if ($table instanceof EtuDev_Data_Table) {
                $this->total = $table->getFoundRows();
            }
        }
        return $this->total;
    }

    /**
     * @param mixed $total
     *
     * @return EtuDev_Data_Rowset
     */
    public function setTotal($total)
    {
        $this->total = $total;
        return $this;
    }

    /**
     * @param array|Zend_Db_Table_Rowset $list_rows
     */
    public function appendRows($list_rows)
    {
        foreach ($list_rows as $v) {
            if (is_array($v)) {
                $this->doAppendRowData($v);
            } elseif ($v instanceof Zend_Db_Table_Row_Abstract) {
                $this->doAppendRowData($v->toArray());
            }
        }
        $this->refreshKeys();
        $this->refreshCount();
        $this->refreshRows();
    }

    public function appendRow(Zend_Db_Table_Row $data)
    {
        $this->doAppendRowData($data->toArray());
        $this->refreshKeys();
        $this->refreshCount();
        $this->refreshRows();
    }

    public function appendRowData(array $data)
    {
        $this->doAppendRowData($data);
        $this->refreshKeys();
        $this->refreshCount();
        $this->refreshRows();
    }

    protected function doAppendRowData(array $data)
    {
        $this->_data[] = $data;
    }

    protected function refreshCount()
    {
        $this->_count = count($this->_data);
    }

    protected function refreshRows()
    {
        if (count($this->_rows) != count($this->_data)) {
            return $this->setTable($this->_table);
        }

        return $this->_connected;
    }

    protected function refreshKeys()
    {
        if ($this->_data) {
            $this->firstKey = key(array_slice($this->_data, 0, 1, true));
            $this->endKey   = key(array_slice($this->_data, -1, 1, true));
        }
    }
}