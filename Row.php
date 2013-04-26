<?php

class EtuDev_Data_Row extends EtuDev_Data_ObservableRow implements EtuDev_Data_HighlightDataObject
{

    /**
     * to be extended, returns an array with the default data for a new entity
     *
     * @return array
     */
    public function _getDefaultData()
    {
        return array();
    }

    /**
     * returns the $data parameter if it is a valid row of table $table, or creates a row with that info
     *
     * @static
     *
     * @param EtuDev_Data_Table $table
     * @param                   $data
     *
     * @return array|EtuDev_Interfaces_ToArrayAble|EtuDev_Interfaces_ToArrayAbleFull|null
     */
    static public function checkOrCreateRowOfTable(EtuDev_Data_Table $table, $data)
    {
        if (!$data) {
            return null;
        }

        if ($table->isRow($data)) {
            return $data;
        }

        $c = null;

        if (is_array($data)) {
            /** @var $data array */
            $c = $table->createRow();
            $c->setFromArray($data);
        } elseif (($data instanceof EtuDev_Interfaces_ToArrayAbleFull) || method_exists($data, 'toArrayFull')) {
            /** @var $data EtuDev_Interfaces_ToArrayAbleFull */
            $c = $table->createRow();
            $c->setFromArray($data->toArrayFull());
        } elseif (($data instanceof EtuDev_Interfaces_ToArrayAble) || method_exists($data, 'toArray')) {
            /** @var $data EtuDev_Interfaces_ToArrayAble */
            $c = $table->createRow();
            $c->setFromArray($data->toArray());
        }

        return $c ? : $data;
    }

    /**
     * @return EtuDev_Data_PseudoArray
     */
    public function _setDefaultDataWhenNull()
    {
        $this->setValuesOnlyIfNull($this->_getDefaultData());

        return $this;
    }

    protected function _insert()
    {
        $this->_setDefaultDataWhenNull();
        $this->calculateBeforeModify();

        return parent::_insert();
    }

    protected function _update()
    {
        $this->calculateBeforeModify();
    }

    protected function calculateBeforeModify()
    {
        foreach ($this->_getters as $k => $gt) {
            if ($this->_isGetterBeforeModify($k)) {
                $this->_setDirect($k, $this->$gt());
                $this->addModifiedKeyIfNeeded($k);
            }
        }
    }

    /**
     * to be extended: check if attribute can be used in calculateBeforeModify() or has to be ignored
     *
     * @param string $attribute
     *
     * @return bool if true it is used, if false ignored
     */
    protected function _isGetterBeforeModify($attribute)
    {
        return true;
    }

    /**
     * @var array con el highlight data
     */
    protected $_highlightData = array();

    /**
     * @param array|SolrObject|Traversable $data
     *
     * @return EtuDev_Data_Row
     */
    public function setHighlightData($data)
    {
        $a = array();
        foreach ($data as $field => $value) {
            $a[$field] = $value[0];
        }

        $this->_highlightData = $a;

        return $this;
    }


    public function isHighlighted($origkey)
    {
        $key = $this->getDefinedAlias($origkey);

        return $this->_isHighlighted($key);
    }

    protected function _isHighlighted($key)
    {
        return @isset($this->_highlightData[$key]);
    }

    public function getDirectHighlighted($origkey)
    {
        $key = $this->getDefinedAlias($origkey);

        return $this->_getDirectFromHighlightedData($key);
    }

    protected function _getDirectFromHighlightedData($key)
    {
        return @$this->_highlightData[$key];
    }

    static public function getIdField()
    {
        return 'id';
    }

}
