<?php

/**
 * originally developed by Sergio Gago (sergiogh) modified by Eduardo Turiño
 *
 * This class attaches its inherited objects the notification methods listed below.
 *
 * Is always better to use the row objects as we can control much better what to insert (in
 * the postInsert or postUpdate methods). However, if we need to use the table object we
 * can attach de self::notifyObservers in the methods from Zend_Db_Table insert, delete, etc.. even
 * fetchAll.
 *
 * Is recommended to use this observers only to logging and debug purpsoes.
 *
 * Note that if insert is used, is possible to observe the same event twice, one with the
 * row_observable and other with the table_observable. In any event notification is necessary
 * to pass the table object, the event, and also the row affected
 */
class EtuDev_Data_ObservableTable extends Zend_Db_Table_Abstract
{

    const LOG_CLASS = 'EtuDev_Util_Log';

    static public function log($caller, $message, $level, $module = null)
    {
        /** @var $logger EtuDev_Util_Log */
        $logger = static::LOG_CLASS;
        if ($logger) {
            return $logger::log($caller, $message, $level, $module);
        }

        return false;
    }

    /**
     * @var array Array of observers
     */
    protected static $_observers = array();

    /**
     * Attach an observer class
     *
     * Allows observation of pre/post insert/update/delete events.
     *
     * Expects a valid class name; that class must have a public
     * static method 'observeTable' that accepts two arguments:
     *      * string $eventname
     *      * EtuDev_Data_ObservableTable $row
     *
     * @param string $class
     *
     * @return boolean
     */
    public static function attachObserver($class)
    {

        if (!is_string($class) || !class_exists($class) || !is_callable(array($class, 'observe'))) {
            return false;
        }

        if (!isset(self::$_observers[$class])) {
            self::$_observers[$class] = true;
        }

        return true;
    }

    /**
     * Detach an observer
     *
     * @param string $class
     *
     * @return boolean
     */
    public static function detachObserver($class)
    {
        if (!isset(self::$_observers[$class])) {
            return false;
        }

        unset(self::$_observers[$class]);
        return true;
    }


    public function insert(array $data)
    {
        $primaryKey = parent::insert($data);
        $data['pk'] = $primaryKey;
        //self::notifyObservers('insert', $data);
        return $primaryKey;
    }

    public function update(array $data, $where)
    {
        $ret = parent::update($data, $where);
        //self::notifyObservers('update', $data);
        return $ret;
    }

    public function delete($where)
    {
        $ret = parent::delete($where);
        //self::notifyObservers('delete');
        return $ret;
    }

    protected function notifyObservers($event, $data)
    {
        if (!empty(self::$_observers)) {
            foreach (array_keys(self::$_observers) as $observer) {
                try {
                    call_user_func(array($observer, 'observe'), $event, $data);
                } catch (Exception $e) {
                    static::log(
                        'ObservableRow/notifyObserver',
                            'Fallo al notificar: ' . $event . ' de: ' . $this->_name . ' Error: ' . $e->getMessage(),
                        Zend_Log::ERR
                    );
                }
            }
        }
    }


}
