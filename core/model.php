<?php

class model {

  /**
   * Список таблиц по алиасам
   * @var array [alias => table_name]
   */
  public $tables = array();

  /**
   * Текущие параметры таблицы
   * @var array
   */
  private $_tableParams;

  /**
   * Алиас таблицы с которой работаем в данный момент
   * @var string
   */
  public $tblAlias;

  /**
   * Вызвать родительский метод без проверки в дочернем
   * @var bool
   */
  protected $_force = false;

  /**
   * Экземпляры моделей
   * @var array
   */
  protected static $is = array();

  /**
   * Экземпляры моделей таблиц
   * @var array
   */
  protected static $instances = array();

  protected static $objects = array();

  protected $objectClass = 'modelObject';

  const ERROR_TABLE_NOT_DEFINED  = 5001;
  const ERROR_TABLE_NOT_FOUND    = 5002;
  const ERROR_CLASS_NOT_FOUND    = 5003;
  const ERROR_PARAMS             = 5004;
  const ERROR_SQL_TYPE_NOT_FOUND = 5005;

  protected function __construct() {
    $this->tables = FC()->config('tables');
  }

  public static function i($name) {
    $name = is_string($name) ? $name : '';
    if ( ! isset(self::$is[$name])) {
      self::$is[$name] = new self();
    }
    return self::$is[$name];
  }

  /**
   * Получение экземпляра библиотеки для конкретной таблицы
   * @param  string $name
   * @return scheme_template
   */
  public function __get($name) {
    if (isset(self::$instances[$name])) {
      return self::$instances[$name];
    }
    if (isset($this->tables->$name) || $this->_dynamicTable($name)) {
      if ( ! ($class = $this->findModel($name))) {
        $class = get_class($this);
      }
      $object = new $class();
      $object->tblAlias = $name;
      self::$instances[$name] = $object;
      return $object;
    }
    if ( ! isset($this->$name)) {
      throw new xException('Table '.$name.' not found', self::ERROR_TABLE_NOT_FOUND);
    }
    return $this->$name;
  }

  protected function findModel($name) {
    $file = APP_PATH.'/model/'.$name.'.php';
    if ( ! file_exists($file)) {
      return null;
    }
    require_once($file);
    $className = 'x'.ucfirst(end(explode('/', $name)));
    if ( ! class_exists($className)) {
      throw new xException("Class {$className} not found in file {$file}", self::ERROR_CLASS_NOT_FOUND);
    }
    return $className;
  }

  /**
   * Получить параметры таблицы
   * @return mixed
   * @throws xException
   */
  final public function tableParams() {
    if (isset($this->_tableParams[$this->tblAlias])) {
      return $this->_tableParams[$this->tblAlias];
    }
    if ( ! ($table = @$this->tables->{$this->tblAlias})) {
      if ( ! ($table = $this->_dynamicTable($this->tblAlias))) {
        throw new xException("Object table params not defined for alias {$this->tblAlias}", self::ERROR_TABLE_NOT_DEFINED);
      }
    }
    $table->name = $this->tblAlias;
    $table->id = isset($table->id) ? $table->id : 'id';
    $this->_tableParams[$this->tblAlias] = $table;
    return $this->_tableParams[$this->tblAlias];
  }

  /**
   * Получить название таблицы
   * @throws xException
   * @return string
   */
  final public function table() {
    $tableName = $this->tableParams()->name;
    if (!$tableName) {
      throw new xException("Object table name not defined for alias {$this->tblAlias}", self::ERROR_TABLE_NOT_DEFINED);
    }
    return $tableName;
  }

  /**
   * Получить наименование поля primary key
   * @return mixed
   * @throws xException
   */
  final public function idKey() {
    $idKey = $this->tableParams()->id;
    if (!$idKey) {
      throw new xException("Object id key not defined for alias {$this->tblAlias}", self::ERROR_TABLE_NOT_DEFINED);
    }
    return $idKey;
  }

  /**
   * Определение динамических таблиц например inet_aggr_YYYYMM
   * @param  string $name - алиас таблицы
   * @return string - полное название таблицы
   */
  protected function _dynamicTable($name) { return ''; }

  /**
   * Установить флаг вызова родительского метода без проверки в дочернем
   * @return scheme_template
   */
  final public function force() {
    $this->_force = true;
    return $this;
  }

  /**
   * Получение объекта, либо списка объектов
   * @param array|int $filter - условия выборки объекта либо ID объекта
   * @param mixed $order
   * @param mixed $limit
   * @return array|object
   */
  final public function get($filter, $order = null, $limit = null) {

    if ($action = $this->_ownFunc(__FUNCTION__)) {
      return $this->$action($filter, $order, $limit);
    }
    $where = $filter;
    if (is_numeric($filter) || is_string($filter)) {
      if ($cached = $this->_cache($filter)) {
        return $cached;
      }
      $where = array($this->idKey() => $filter);
    }

    if (is_array($order)) {
      $orderList = array();
      foreach ($order as $key => $dir) {
        $orderList[] = "{$key} {$dir}";
      }
      $order = implode(',', $orderList);
    }

    try {
      $result = FC()->db->select('*', $this->table(), array('where' => $where, 'order' => $order, 'limit' => $limit));
    }
    catch (Exception $e) {
      if ($e->getCode() == simpleDb::ERROR_TABLE_NOT_EXIST) {
        $this->install($this->table());
        $result = FC()->db->select('*', $this->table(), array('where' => $where, 'order' => $order, 'limit' => $limit));
      }
      else {
        throw $e;
      }
    }
    
    $objectClass = $this->objectClass;

    if (is_numeric($filter) || is_string($filter)) {
      $item = $result->next();
      if ( ! $item) {
        return null;
      }
      $objects = $this->wrap(array($item));
      $object = array_shift($objects);
      $this->_cache($filter, $object);
      return $object;
    }
    return $this->wrap($result->all());

  }

  /**
   * Проверка на существование метода для данного объекта
   * @param  string $name - наименование функции
   * @param  string $suffix - окончание наименования функции
   * @return string|bool - если метод существует - возвращается его название, в противном случае false
   */
  final protected function _ownFunc($name, $suffix = '') {
    if ($this->_force) {
      return $this->_force = false;
    }
    $func = $name . ucfirst(preg_replace('/(?:\.|_)([a-z])/ie', "strtoupper('\\1')", $this->tblAlias)) . $suffix;
    if (is_callable(array($this, $func)) && method_exists($this, $func)) {
      return $func;
    }
    return false;
  }

  /**
   * Сохранение существующего либо добавление нового объекта, если ID не указан
   * @param  mixed $id - ID сохраняемого объекта в случае нового объекта
   * @param  array $data - параметры объекта
   * @return mixed
   */
  final public function save($id, $data = null) {
    if ($action = $this->_ownFunc(__FUNCTION__)) {
      return $this->$action($id, $data);
    }
    if ( ! is_scalar($id)) {
      $result = $this->_add((array)$id);
    }
    else {
      $result = $this->_edit($id, (array)$data);
    }

    return $result;
  }

  /**
   * Добавление объекта в БД
   * @throws xException
   * @param  array $data - параметры объекта
   * @return mixed
   */
  final protected function _add($data) {
    if (!is_array($data)) {
      throw new xException('Error during adding object, $data is not array.', self::ERROR_PARAMS);
    }
    if ($action = $this->_ownFunc(__FUNCTION__)) {
      return $this->$action($data);
    }
    $data = $this->_preformData($data);
    $table = $this->table();
    $id = FC()->db->insert($this->table(), $data);
    $this->log($data);
    return $id;
  }

  /**
   * Обновление параметров объекта
   * @throws xException
   * @param  int $id - ID объекта
   * @param  array $data - параметры объекта
   * @return mixed
   */
  final protected function _edit($id, $data) {
    if ( ! is_array($data)) {
      throw new xException('Error during adding object, $data is not array.', self::ERROR_PARAMS);
    }
    if ( ! $id) {
      throw new xException('Object id not defined', self::ERROR_PARAMS);
    }
    $this->_cache($id, null);
    if ($action = $this->_ownFunc(__FUNCTION__)) {
      return $this->$action($id, $data);
    }
    $data = $this->_preformData($data);
    $table = $this->table();
    $result = FC()->db->update($table, $data, array($this->idKey() => $id));
    if ($result) {
      $this->log($id, $data);
    }
    return $result;
  }

  /**
   * Удаление объекта
   * @param  int $id - ID удаляемого объекта
   * @param  array $params - параметры
   * @return bool
   * @throws xException
   */
  final public function del($id, $params = array()) {
    if (!$id) {
      throw new xException('Object id not defined', self::ERROR_PARAMS);
    }
    $this->_cache($id, null);
    if ($action = $this->_ownFunc(__FUNCTION__)) {
      return $this->$action($id, $params);
    }
    $table = $this->table();
    $result = FC()->db->delete($table, array($this->idKey() => $id));
    if ($result) {
      //$this->log("Удаление строки {$id} таблицы {$table}");
    }
    return $result;
  }

  /**
   * Редактирование списка объектов
   * Если параметр $data = null, то $filter рассматривается как список объектов для сохранения
   * @param  array $filter - фильтр для поиска объектов для изменения
   * @param  array $data - параметры для изменения
   */
  public function saveList($filter, $data = null) {
    $forced = $this->_force;
    if ($action = $this->_ownFunc('save', 'List')) {
      return $this->$action($filter, $data);
    }
    $idKey = $this->idKey();
    if (is_null($data)) {
      $list = $filter;
    }
    else {
      $list = $this->get($filter);
    }
    FC()->db->begin();
    foreach ($list as $object) {
      if ($forced) $this->force();
      if (is_null($data)) {
        $obj = (object)$object;
        if ( ! isset($obj->$idKey)) {
          $this->save((array)$obj);
        }
        else {
          $this->save($obj->$idKey, (array)$obj);
        }
      }
      else {
        $this->_edit($object->$idKey, $data);
      }
    }
    FC()->db->commit();
  }

  /**
   * Удаление списка объектов
   * @param array $filter - фильтр для поиска объектов для удаления
   * @param array $params - параметры
   */
  public function delList($filter, $params = array()) {
    $forced = $this->_force;
    if ($action = $this->_ownFunc('del', 'List')) {
      return $this->$action($filter);
    }
    $idKey = $this->idKey();
    $list = $this->get($filter, "{$idKey} desc");
    FC()->db->begin();
    foreach ($list as $object) {
      if ($forced) $this->force();
      $this->del($object->$idKey, $params);
    }
    FC()->db->commit();
  }

  final protected function _preformData($data) {
    $fields = $this->tableParams()->fields;
    foreach ($fields as $key => $field) {
      if (isset($field->preform)) {
        try {
          $data[$key] = format::preform($field, $data);
        }
        catch (Exception $e) {
          if ($e->getCode() == format::ERROR_CANNON_APPLY_PREFORM) {
            unset($data[$key]);
          }
          else {
            throw $e;
          }
        }
      }
    }
    return $data;
  }

  /**
   * Записать объект в кеш
   * @param $id
   * @param $object
   */
  final protected function _cache($id, $object = false) {
    if ($object === false) {
      return isset(self::$objects[$this->table()][$id]) ? self::$objects[$this->table()][$id] : null;
    }
    return self::$objects[$this->table()][$id] = $object;
  }

  /**
   * Обернуть данные в объекты
   * @param  array $items
   * @param  array
   * @return array
   */
  final public function wrap($items) {
    $objectClass = $this->objectClass;
    $result = array();
    foreach ($items as $item) {
      $result[] = new $objectClass($this->tableParams(), $item);
    }
    return $result;
  }

  final protected function log($id, $data = null) {
    $dir = APP_PATH.'/logs/model/'.$this->table();
    if ( ! file_exists($dir)) {
      mkdir($dir, 0700);
    }
    file_put_contents($dir.'/'.date('Y-m').'.log', date('d H:i:s: [').$_SERVER['REMOTE_ADDR']."]: [".(is_array($id) ? 'add' : 'edit:'.$id)."] ".funcs::varToLog($data)."\n", FILE_APPEND);
  }

  final public function install($tableName) {
    $typeMatch = array(
      'int'      => 'int(11)',
      'image'    => 'int(11)',
      'string'   => 'varchar(250)',
      'password' => 'varchar(250)',
      'text'     => 'text',
      'date'     => 'date',
      'datetime' => 'timestamp',
      'bool'     => 'tinyint(1)',
    );
    $tables = FC()->config('tables');
    if ( ! isset($tables->$tableName)) {
      throw new xException('Error creating table, descrition not defined for table '.$tableName, self::ERROR_TABLE_NOT_DEFINED);
    }
    $table = $tables->$tableName;
    $fields = array();
    foreach ($table->fields as $fieldName => $fieldConfig) {
      $sqlType = @$typeMatch[$fieldConfig->type];
      if ( ! $sqlType) {
        throw new xException('Type '.$fieldConfig->type.' not found in sql-types', self::ERROR_SQL_TYPE_NOT_FOUND);
      }
      $required = @$fieldConfig->required || isset($fieldConfig->default) ? 'NOT NULL' : '';
      $increment = $fieldName == $table->id ? 'auto_increment primary key' : '';
      $default = isset($fieldConfig->default) ? 'DEFAULT '.$fieldConfig->default : '';
      $fields[] = "`{$fieldName}` {$sqlType} {$required} {$increment} {$default}";
    }
    $begins = @$table->begins ? 'AUTO_INCREMENT='.$table->begins : '';
    $sql = "
      CREATE TABLE IF NOT EXISTS `{$tableName}` (
        ".implode(',', $fields)."
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8 {$begins};
    ";
    FC()->db->query($sql);
    if (@$table->data) {
      foreach ($table->data as $data) {
        FC()->db->insert($tableName, (array)$data, array('ignore' => 1));
      }
    }
  }

}

function model($name = null) {
  return model::i($name);
}

class modelObject {

  var $table;
  var $params;

  function __construct($table, $params) {
    $this->table = $table;
    $this->params = (object)$params;
  }

  function __get($name) {
    return @$this->params->$name;
  }

  function data() {
    return $this->params;
  }

  function table() {
    return $this->table;
  }

  function editBtn() {
    return format::editBtn($this);
  }

  function delBtn() {
    return format::delBtn($this);
  }

}