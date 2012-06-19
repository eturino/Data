<?php

/**
 * originally developed by Sergio Gago @sergiogh
 *
 * @author sergiogh
 */
class EtuDev_Data_ObservableRow extends Zend_Db_Table_Row_Abstract {

	const LOG_CLASS = 'EtuDev_Data_Log';

	static public function log($caller, $message, $level, $module = NULL) {
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
	 *      * Divescover_Db_Table_Observerable $row
	 *
	 * @param string $class
	 *
	 * @return boolean
	 */
	public static function attachObserver($class) {
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
	public static function detachObserver($class) {
		if (!isset(self::$_observers[$class])) {
			return false;
		}

		unset(self::$_observers[$class]);
		return true;
	}

	protected function _postInsert() {
		$this->notifyObservers('post-insert');
	}

	protected function _postUpdate() {
		$this->notifyObservers('post-update');
	}

	protected function _postDelete() {
		$this->notifyObservers('post-delete');
	}

	/*
	public function save() {
		$obj = parent::save();
		$this->notifyObservers('save');
		return $obj;
	}
	*/

	protected function notifyObservers($event) {
		if (!empty(self::$_observers)) {
			foreach (array_keys(self::$_observers) as $observer) {
				try {
					call_user_func(array($observer, 'observe'), $event, $this);
				} catch (Exception $e) {
					static::log('ObservableRow/notifyObserver', 'Fallo al notificar: ' . $event . ' de: ' . $this->getTableClass() . ' Error: ' . $e->getMessage(), Zend_Log::ERR);
				}
			}
		}
	}

	//SECTION TO PSEUDOARRAY


	/**
	 * if true then we'll check for column name transformation, if not we'll ignore it!!
	 * @var bool
	 */
	protected $_useColumnNameTransformation = false;

	/**
	 * if false we wont check if the column already exists!!!
	 * @var bool
	 */
	protected $_checkColumnExistsBeforeGet = false;

	/**
	 * if false we wont check if the column already exists!!!
	 * @var bool
	 */
	protected $_checkColumnExistsBeforeSet = false;


	/**
	 * Initialize object
	 *
	 * Called from {@link __construct()} as final step of object instantiation.
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		$this->_loadClassInfo();
		$this->_readyBeforeUse();
	}

	protected function _loadClassInfo() {
		$info                       = EtuDev_PseudoArray_Factory::getInfo(get_called_class());
		$keyscont                   = array_keys($this->_data);
		$this->_aliases             = $keyscont ? array_merge(array_combine($keyscont, $keyscont), (array) $info['aliases']) : (array) $info['aliases'];
		$this->_getters             = $info['getters'];
		$this->_setters             = $info['setters'];
		$this->_properties_by_level = $info['levels'];
	}

	protected function _readyBeforeUse() {

	}

	/**
	 * prepare some attributes before cached
	 * @return void
	 */
	public function prepareForCache() {
		$this->_aliases             = array();
		$this->_getters             = array();
		$this->_setters             = array();
		$this->_properties_by_level = array();
	}

	/**
	 * prepare some attributes before cached
	 * @return void
	 */
	public function afterCache() {
		$this->_loadClassInfo();
	}

	public function __wakeup() {
		$this->_loadClassInfo();
		$this->_readyBeforeUse();
	}


	/**
	 * get the constant in the object, not using "self::" if it can be redefined
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function _getConstant($name) {
		$r = new ReflectionObject($this);
		return $r->hasConstant($name) ? $r->getConstant($name) : null;
	}


	protected function _getTransformedColumn($columnName) {
		if (!$this->_useColumnNameTransformation) {
			return $columnName;
		}
		return $this->_calculateTransformedColumn($columnName);
	}

	protected function _calculateTransformedColumn($columnName) {
		//default => use alias
		return @$this->_aliases[$columnName];
	}


	protected $_getters = array();
	protected $_setters = array();
	protected $_aliases = array();
	private $_properties_level_flag = EtuDev_PseudoArray_Object::PROPERTIES_LEVEL_ALL;

	/** @var array precalculated properties */
	protected $_properties_by_level = array();

	final public function _getContainer() {
		return $this->_data;
	}


	/**
	 * check if this EtuDev_PseudoArray_Object will only accept properties defined in the DocBlock
	 * @return int
	 */
	public function _getPropertiesLevelFlag() {
		return (array) $this->_properties_level_flag;
	}

	/**
	 * sets the flag that says if this EtuDev_PseudoArray_Object will accept only properties defined in the DocBlock
	 *
	 * @param int $level_flag
	 *
	 * @return EtuDev_PseudoArray_Object
	 */
	public function _setPropertiesLevelFlag($level_flag) {
		$this->_properties_level_flag = $level_flag;
		return $this;
	}


	/**
	 * replace the data content with this one, WARNING: use ONLY when it is clear that we can do this
	 *
	 * @param array $originalData
	 *
	 */
	public function replaceWholeContainer(array $originalData) {
		$this->_data = $originalData;
		if ($originalData) {
			$newkeys        = array_keys($this->_data);
			$this->_aliases = array_merge(array_combine($newkeys, $newkeys), (array) $this->_aliases);
		}
	}


	/**
	 * foreach element in the originalData, we call $this->$k = $v
	 *
	 * @param array|Traversable $originalData
	 *
	 * @uses __set()
	 * @throws Exception
	 */
	public function setValuesFromOriginalData($originalData) {

		if ($originalData) {
			try {
				if (is_array($originalData) && $originalData) {
					//los que no tienen setter se meten directamente
					$notSetter   = array_diff_key($originalData, $this->_setters);
					$this->_data = array_merge($this->_data, $notSetter);
					//aseguramos los nuevos alias
					$newkeys        = array_keys($this->_data);
					$this->_aliases = array_merge(array_combine($newkeys, $newkeys), (array) $this->_aliases);

					//si hay más, van por setter (no es necesario añadir alias, pues si tienen setter es que están definidos en la clase)
					if (count($notSetter) < count($originalData)) {
						$withSetter = array_diff_key($originalData, $notSetter);
						foreach ($withSetter as $k => $v) {
							$s = $this->_setters[$k];
							$this->$s($v);
						}
					}
				} else {
					foreach ($originalData as $k => $v) {
						$this->_set($k, $v);
					}
				}


			} catch (Exception $e) {
				//eturino 20110107 we no longer use Traversable because there are some traversable classes that don't implements this (like Solr Objects)
				throw new Exception("non Traversable data (nor trav. object nor array)");
			}

		}
	}


	/**
	 * foreach element in the originalData, we call $this->$k = $v, ONLY if it is not already set
	 *
	 * @param array|Traversable $originalData
	 *
	 * @uses _set()
	 * @throws Exception
	 */
	public function setValuesOnlyIfNotSetted($originalData) {
		if ($originalData) {
			try {
				if (is_array($originalData) && $originalData) {
					if (!$this->_data) {
						$this->setValuesFromOriginalData($originalData);
						return;
					}
					//podemos quitar los que ya estén en el container
					$not_data = array_diff_key($originalData, $this->_data);
					$this->setValuesFromOriginalData($not_data);
					return;
				} else {
					foreach ($originalData as $k => $v) {
						if (!$this->offsetExists($k)) {
							$this->_set($k, $v);
						}
					}
				}
			} catch (Exception $e) {
				//eturino 20110107 we no longer use Traversable because there are some traversable classes that don't implements this (like Solr Objects)
				throw new Exception("non Traversable data (nor trav. object nor array)");
			}

		}
	}

	/**
	 * foreach element in the originalData, we call $this->$k = $v, ONLY if it is not already set
	 *
	 * @param array|Traversable $originalData
	 *
	 * @uses _set()
	 * @throws Exception
	 * @return boolean
	 */
	public function setValuesOnlyIfNull($originalData) {
		if ($originalData) {
			try {
				if (is_array($originalData) && $originalData) {
					if (!$this->_data) {
						$this->setValuesFromOriginalData($originalData);
						return true;
					}
					//podemos quitar los que ya estén en el container (salvo null)
					$data     = array_filter($this->_data, function($v) {
						return !is_null($v);
					});
					$not_data = array_diff_key($originalData, $data);

					$this->setValuesFromOriginalData($not_data);
					return true;
				} else {
					foreach ($originalData as $k => $v) {
						if (!isset($this->_data[$k])) { //isset devuelve false si es NULL
							$this->_set($k, $v);
						}
					}
					return true;
				}
			} catch (Exception $e) {
				//eturino 20110107 we no longer use Traversable because there are some traversable classes that don't implements this (like Solr Objects)
				throw new Exception("non Traversable data (nor trav. object nor array)");
			}

		}
	}

	/**
	 * comprueba si el valor es válido (para extender, por defecto siempre TRUE)
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function valueIsValid($value) {
		//TODO extender
		return true;
	}


	final public function getDefinedAlias($key) {
		return @$this->_aliases[$key] ?: $key;
	}


	/**
	 * establece el elemento en el container, si el elemento es válido
	 *
	 * @param string $key si es '' se hace $this->_data[] = $value
	 * @param mixed  $value
	 *
	 * @uses $_data
	 * @uses valueIsValid()
	 * @return bool
	 */
	public function __set($key, $value) {
		return $this->_set($key, $value);
	}


	protected function _transformColumn($columnName) {
		return @$this->_aliases[$columnName];
	}

	/**
	 * common setter, used by __set() & offsetSet()
	 *
	 * @param string $atkey si es '' se hace $this->_data[] = $value
	 * @param mixed  $value
	 *
	 * @uses $_data
	 * @uses valueIsValid()
	 * @uses hasSetter()
	 * @uses hasAttribute()
	 * @return bool
	 * @throws Exception
	 * @throws Zend_Db_Table_Row_Exception
	 */
	protected function _set($atkey, $value) {
		if (!$this->valueIsValid($value)) {
			throw new Exception("Value invalid");
		}
		//por setter no es necesario modificar alias, ya está definido en la clase
		$setter = @$this->_setters[$atkey];
		if ($setter) {
			return $this->$setter($value);
		}

		$key = @$this->_transformColumn($atkey) ?: $atkey; //por si no está definido ya
		if ($this->_checkColumnExistsBeforeSet && !array_key_exists($key, $this->_data)) {
			require_once 'Zend/Db/Table/Row/Exception.php';
			throw new Zend_Db_Table_Row_Exception("Specified column \"$key\" is not in the row");
		}

		$this->_data[$key] = $value;
		$this->addModifiedKeyIfNeeded($key);
		$this->_aliases[$atkey] = $key; //por si fuera necesario almacenarlo (mejor directamente que mirar a ver si ya está)

		return true;
	}


	final protected function _setDirectByDynamic($key, $value) {
		if ($this->_checkColumnExistsBeforeSet && !array_key_exists($key, $this->_data)) {
			require_once 'Zend/Db/Table/Row/Exception.php';
			throw new Zend_Db_Table_Row_Exception("Specified column \"$key\" is not in the row");
		}
		$this->_data[$key]    = $value;
		$this->_aliases[$key] = $key;
		$this->addModifiedKeyIfNeeded($key);

		return true;
	}

	final protected function _setBySetter($setter, $value) {
		return $this->$setter($value); //no es necesario modificar alias (si hay setter está definido en la clase)
	}

	protected function addModifiedKeyIfNeeded($key) {
		if ($this->_cleanData) {
			if (array_key_exists($key, $this->_cleanData)) { //solo si existe
				$this->_modifiedFields[$key] = true;
			}
		} elseif ($this->hasRowColumn($key)) {
			$this->_modifiedFields[$key] = true;
		}
	}

	protected $_row_columns = array();

	protected function hasRowColumn($columnName) {
		return in_array($columnName, $this->getRowColumns());
	}

	protected function getRowColumns() {
		if (!$this->_row_columns && $this->getTable()) {
			$this->_row_columns = $this->getTable()->info(Zend_Db_Table_Abstract::COLS);
		}
		return $this->_row_columns;
	}


	/**
	 * Set the table object, to re-establish a live connection
	 * to the database for a Row that has been de-serialized.
	 *
	 * @param Zend_Db_Table_Abstract $table
	 *
	 * @return boolean
	 * @throws Zend_Db_Table_Row_Exception
	 */
	public function setTable(Zend_Db_Table_Abstract $table = null) {
		if ($table == null) {
			$this->_table     = null;
			$this->_connected = false;
			return false;
		}

		$tableClass = get_class($table);
		if (!$table instanceof $this->_tableClass) {
			require_once 'Zend/Db/Table/Row/Exception.php';
			throw new Zend_Db_Table_Row_Exception("The specified Table is of class $tableClass, expecting class to be instance of $this->_tableClass");
		}

		$this->_table      = $table;
		$this->_tableClass = $tableClass;

		$info = $this->_table->info();

		if ($this->_cleanData && $info['cols'] != array_keys($this->_cleanData)) {
			require_once 'Zend/Db/Table/Row/Exception.php';
			throw new Zend_Db_Table_Row_Exception('The specified Table does not have the same columns as the Row');
		}

		if (!array_intersect((array) $this->_primary, $info['primary']) == (array) $this->_primary) {

			require_once 'Zend/Db/Table/Row/Exception.php';
			throw new Zend_Db_Table_Row_Exception("The specified Table '$tableClass' does not have the same primary key as the Row");
		}

		$this->_connected = true;
		return true;
	}


	/**
	 * common getter, used by __get() & offsetGet()
	 *
	 * @param string $origkey
	 *
	 * @uses $_data
	 * @return mixed
	 * @throws Zend_Db_Table_Row_Exception
	 */
	protected function _get($origkey) {
		//cehck if we know how we need to set it
		$getter = @$this->_getters[$origkey];

		if ($getter) {
			$ret = $this->$getter();
		} else {

			$key = $this->_transformColumn($origkey); //nos hemos asegurado en los sets y en la carga de info que todos los definidos en container están en alias, por lo que podemos hacer esto (es más rápido que comprobar) Mejoramos la velocidad de GET con respecto a la de SET (se hace siempre muchas más veces el GET)
			if ($this->_checkColumnExistsBeforeGet && !array_key_exists($key, $this->_data)) {
				require_once 'Zend/Db/Table/Row/Exception.php';
				throw new Zend_Db_Table_Row_Exception("Specified column \"$key\" is not in the row");
			}
			$ret = @$this->_data[$key];
		}

		return $ret;
	}

	final protected function _getByGetter($getter) {
		return $this->$getter();
	}

	final protected function _getDirectByDynamic($key) {
		// eturino: resulta mucho más rápido esto
		//		return array_key_exists($key,$this->_data) ? $this->_data[$key] : null;
		return @$this->_data[$key];
	}


	final protected function _setDirect($key, $value) {
		if ($key === '' || $key === null) {
			$this->_data[] = $value;
			end($this->_data);
			$newKey = key($this->_data);
			reset($this->_data);
			$this->_aliases[$newKey] = $newKey;
		} else {
			$this->_data[$key]    = $value;
			$this->_aliases[$key] = $key;
		}
	}

	final protected function _getDirect($origkey, $convertWrappersToArray = true) {
		$key = @$this->_aliases[$origkey];
		$ret = @$this->_data[$key];


		/** @var $ret EtuDev_PseudoArray_Object */
		if ($ret && $convertWrappersToArray && $ret instanceof EtuDev_PseudoArray_Object && $ret->isWrapperOfArray()) {
			return $ret->toArray();
		}

		return $ret;
	}

	/**
	 * recoge el elemento (null si no existe)
	 *
	 * @param string $key
	 *
	 * @uses _get()
	 * @return mixed
	 */
	public function __get($key) {
		return $this->_get($key);
	}

	public function getArrayCopy($level = null, $toArrayPseudoArrays = true) {
		return $this->toArray($level, $toArrayPseudoArrays);
	}

	public function _autoFillProperties() {
		//only needed for the setters => we get the container
		$setters_with_info_in_data = array_intersect_key($this->_setters, $this->_data);
		foreach ($setters_with_info_in_data as $key => $setter) {
			$this->$setter($this->_data[$key]);
		}
	}


	/**
	 * returns an actual array with the same elements the iterator can access
	 *
	 * @param string $level filter with the given level
	 * @param bool   $toArrayPseudoArrays if true the pseudoarrays
	 *
	 * @return array
	 */
	public function toArray($level = null, $toArrayPseudoArrays = true) {
		if (is_null($level)) {
			$level = EtuDev_PseudoArray_Object::TO_ARRAY_LEVEL_DEFAULT ?: EtuDev_PseudoArray_Object::LEVEL_ALL;
		}

		if ($level == EtuDev_PseudoArray_Object::LEVEL_ALL) {
			$reals = array_unique(array_values($this->_aliases));
			if (!$reals) {
				return array();
			}
		} else { //si filtramos por level, tenemos que dar solo los del level? (en principio si)
			$reals = @$this->_properties_by_level[$level];
		}
		if (!$reals) {
			return array();
		}

		$st = array_fill_keys($reals, null);
		if ($this->_data) {
			$st = array_merge($st, $this->_data);
		}
		return $st;
	}

}
