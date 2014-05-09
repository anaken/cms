<?php

class model {

  /**
   * Список таблиц по алиасам
   * берется из конфига
   * @var array [alias => table_name]
   */
  public $tables = array();

  /**
   * Список дочерних таблиц
   * Может быть представлен одним из видов:
   * [
   *   table_name,
   *   alias => table_name,
   *   alias => ['name' => table_name, 'object' => 'modelNameObject']
   *   ..
   * ]
   * @var array
   */
  protected $_childs = array();

  /**
   * Текущие параметры таблицы
   * @var array
   */
  private $_tableParams;

  /**
   * Алиас таблицы с которой работаем в данный момент
   * @var string
   */
  public $_tableAlias;

  /**
   * Вызвать родительский метод без проверки в дочернем
   * @var bool
   */
  protected $_force = false;

  /**
   * Дерево объектов
   * @var array
   */
  private $_tree;

  /**
   * Вернуть количество элементов
   * @var bool
   */
  protected $_count;

  /**
   * Экземпляры моделей
   * @var model[]
   */
  protected static $instances = array();

  protected static $objects = array();

  const DEFAULT_CLASS_OBJECT_NAME = 'modelObject';

  protected $objectClass = self::DEFAULT_CLASS_OBJECT_NAME;

  const ERROR_TABLE_NOT_DEFINED  = 5001;
  const ERROR_TABLE_NOT_FOUND    = 5002;
  const ERROR_CLASS_NOT_FOUND    = 5003;
  const ERROR_PARAMS             = 5004;
  const ERROR_SQL_TYPE_NOT_FOUND = 5005;
  const ERROR_CLASS_INHERITANCE  = 5006;

  protected function __construct($name, $params = array()) {
    $this->tables = FC()->config('tables');
    $this->_tableAlias = $name;
    $this->_updateChilds(array('child' => @$params['child']));
  }

  private function _updateChilds($params = array()) {
    if (@$params['child']) {
      $this->_childs = array();
    }
    else if ($this->_childs) {
      $childs = array();
      foreach ($this->_childs as $alias => $child) {
        $childParams = is_array($child) ? $child : array('name' => $child);
        $childs[(is_numeric($alias) ? $childParams['name'] : $alias)] = $childParams;
      }
      $this->_childs = $childs;
    }
  }

  /**
   * Возвращает модель
   * @param  string $name
   * @return model
   */
  final public static function i($name) {
    if (isset(self::$instances[$name])) {
      return self::$instances[$name];
    }
    if ( ! ($class = self::findModel($name))) {
      $class = __CLASS__;
    }
    $model = new $class($name);
    self::$instances[$name] = $model;
    return $model;
  }

  /**
   * Получение экземпляра библиотеки для конкретной таблицы
   * @throws xException
   * @param string $name
   * @return model
   */
  public function __get($name) {
    $class = get_class($this);
    $instanceName = $this->_tableAlias.'.'.$name;
    if (isset(self::$instances[$instanceName])) {
      return self::$instances[$instanceName];
    }
    if (isset($this->tables->$name) && isset($this->_childs[$name])) {
      $object = new $class($name, array('child' => true));
      self::$instances[$instanceName] = $object;
      return $object;
    }
    else {
      throw new xException('Table '.$name.' not found', self::ERROR_TABLE_NOT_FOUND);
    }
  }

  /**
   * Выполняет поиск модели
   * @throws xException
   * @param  string $name
   * @return string
   */
  protected static function findModel($name) {
    $file = APP_PATH.'/model/'.$name.'.php';
    if ( ! file_exists($file)) {
      return null;
    }
    require_once($file);
    $className = 'x'.ucfirst(end(explode('/', $name)));
    if ( ! class_exists($className)) {
      throw new xException("Class {$className} not found in file {$file}", self::ERROR_CLASS_NOT_FOUND);
    }
    if ( ! (is_subclass_of($className, __CLASS__))) {
      throw new xException("Class {$className} is not a subclass of class ".__CLASS__, self::ERROR_CLASS_INHERITANCE);
    }
    return $className;
  }

  /**
   * Получить параметры таблицы
   * @return mixed
   * @throws xException
   */
  final public function tableParams() {
    if (isset($this->_tableParams[$this->_tableAlias])) {
      return $this->_tableParams[$this->_tableAlias];
    }
    if ( ! ($table = @$this->tables->{$this->_tableAlias})) {
      throw new xException("Object table params not defined for alias {$this->_tableAlias}", self::ERROR_TABLE_NOT_DEFINED);
    }
    $table->name = $this->_tableAlias;
    $table->id = isset($table->id) ? $table->id : 'id';
    $this->_tableParams[$this->_tableAlias] = $table;
    return $this->_tableParams[$this->_tableAlias];
  }

  /**
   * Получить название таблицы
   * @throws xException
   * @return string
   */
  final public function table() {
    $tableName = $this->tableParams()->name;
    if (!$tableName) {
      throw new xException("Object table name not defined for alias {$this->_tableAlias}", self::ERROR_TABLE_NOT_DEFINED);
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
      throw new xException("Object id key not defined for alias {$this->_tableAlias}", self::ERROR_TABLE_NOT_DEFINED);
    }
    return $idKey;
  }

  /**
   * Установить флаг вызова родительского метода без проверки в дочернем
   * @return self
   */
  final public function force() {
    $this->_force = true;
    return $this;
  }

  /**
   * Получение количества элементов в выборке
   * @param $filter
   * @param mixed $order
   * @param mixed $limit
   * @return int
   */
  public function count($filter, $order = null, $limit = null) {
    $this->_count = true;
    return $this->get($filter, $order, $limit);
  }

  /**
   * Получение объекта, либо списка объектов
   * @throws Exception
   * @param  array|int $filter - условия выборки объекта либо ID объекта
   * @param  mixed $order
   * @param  mixed $limit
   * @return modelObject|modelObject[]
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

    $select = '*';
    if ($this->_count) {
      $select = 'count(*) as `count`';
    }

    try {
      $result = FC()->db->select($select, $this->table(), array('where' => $where, 'order' => $order, 'limit' => $limit));
    }
    catch (Exception $e) {
      if ($e->getCode() == simpleDb::ERROR_TABLE_NOT_EXIST) {
        $this->install($this->table());
        $result = FC()->db->select($select, $this->table(), array('where' => $where, 'order' => $order, 'limit' => $limit));
      }
      else {
        throw $e;
      }
    }

    if ($this->_count) {
      $this->_count = false;
      return $result->next()->count;
    }
    
    if (is_numeric($filter) || is_string($filter)) {
      $item = $result->next();
      if ( ! $item) {
        return null;
      }
      $objects = $this->wrap(array($item));
      $object = current($objects);
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
    $func = $name . ucfirst(preg_replace('/(?:\.|_)([a-z])/ie', "strtoupper('\\1')", $this->_tableAlias)) . $suffix;
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
      if (isset($field->preform) && (@$field->hidden || ! $data[$key])) {
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
   * @return object
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
      mkdir($dir, 0700, 1);
    }
    file_put_contents($dir.'/'.date('Y-m').'.log', date('d H:i:s: [').$_SERVER['REMOTE_ADDR']."]: [".(is_array($id) ? 'add' : 'edit:'.$id)."] ".funcs::varToLog($data ? $data : $id)."\n", FILE_APPEND);
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
    try {
      $existTableDataFields = FC()->db->select('*', $tableName, array('limit' => 1))->row();
    }
    catch (Exception $e) {
      if ($e->getCode() != simpleDb::ERROR_TABLE_NOT_EXIST) {
        throw $e;
      }
    }
    $table = $tables->$tableName;
    $fields = $indexes = array();
    $previousField = null;
    foreach ($table->fields as $fieldName => $fieldConfig) {
      $sqlType = @$typeMatch[$fieldConfig->type];
      if (in_array($fieldConfig->type, array('scope', 'files'))) {
        continue;
      }
      if ( ! $sqlType) {
        throw new xException('Type '.$fieldConfig->type.' not found in sql-types', self::ERROR_SQL_TYPE_NOT_FOUND);
      }
      if (
        $fieldName != $table->id &&
        (
          (
            in_array($fieldConfig->type, array('int', 'bool')) ||
            @$fieldConfig->format->type == 'enum'
          ) && ! @$fieldConfig->index ||
            @$fieldConfig->index
        )
      ) {
        $index = "KEY `{$fieldName}_idx` (`{$fieldName}`)";
        $indexes[] = $index;
      }
      else {
        $index = null;
      }
      if (@$fieldConfig->format->type == 'enum' && is_array($fieldConfig->format->values)) {
        $sqlType = "enum('".implode("','", $fieldConfig->format->values)."')";
      }
      $required = @$fieldConfig->required || isset($fieldConfig->default) ? 'NOT NULL' : '';
      $increment = $fieldName == $table->id ? 'auto_increment primary key' : '';
      $default = isset($fieldConfig->default) ? 'DEFAULT '.$fieldConfig->default : '';
      $comment = str_replace("'", "''", $fieldConfig->caption);
      $field = "`{$fieldName}` {$sqlType} {$required} {$increment} {$default} COMMENT '{$comment}'";
      if (@$existTableDataFields && ! array_key_exists($fieldName, $existTableDataFields)) {
        FC()->db->query("
          ALTER TABLE `{$tableName}` ADD COLUMN {$field} ".($previousField ? " AFTER `{$previousField}`" : '')."
        ");
        if ($index) {
          FC()->db->query("
            ALTER TABLE `{$tableName}` ADD {$index}
          ");
        }
      }
      else if ( ! $existTableDataFields) {
        $fields[] = $field;
      }
      $previousField = $fieldName;
    }
    $begins = @$table->begins ? 'AUTO_INCREMENT='.$table->begins : '';
    $tableComment = str_replace("'", "''", $table->caption);
    if (@$existTableDataFields) {
      foreach ($existTableDataFields as $existFieldName => $existFieldData) {
        if ( ! array_key_exists($existFieldName, $table->fields)) {
          FC()->db->query("
            ALTER TABLE `{$tableName}` DROP COLUMN `{$existFieldName}`
          ");
        }
      }
    }
    else {
      FC()->db->query("
        DROP TABLE IF EXISTS {$tableName}
      ");
      $sql = "
        CREATE TABLE IF NOT EXISTS `{$tableName}` (
          ".implode(',', $fields)."
          ".($indexes ? ', '.implode(',', $indexes) : '')."
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='{$tableComment}' {$begins};
      ";
      FC()->db->query($sql);
      if (@$table->data) {
        foreach ($table->data as $data) {
          FC()->db->insert($tableName, (array)$data, array('ignore' => 1));
        }
      }
    }

  }

  /**
   * Получить дерево объектов
   * @param  int $parentId
   * @param  string $name
   * @return array
   */
  function tree($parentId = null, $name = 'parent_id') {
    if ( ! isset($this->_tree[$name])) {
      $items = model($this->_tableAlias)->get(null, 'id');
      $this->_tree[$name] = array();
      foreach ($items as $item) {
        $this->_tree[$name][(int)$item->{$name}][$item->id] = $item;
      }
    }
    return (array)(isset($parentId) ? @$this->_tree[$name][(int)$parentId] : $this->_tree[$name]);
  }

}

/**
 * Возвращает модель
 * @param  string $name
 * @return model
 */
function model($name = null) {
  return model::i($name);
}

class modelObject {

  var $tableName;
  var $params;
  private $_files;
  protected static $tables;

  function __construct($table, $params) {
    $this->params = (object)$params;
    $this->tableName = $table->name;
    self::$tables[$table->name] = new modelObjectTable($table, $this);
  }

  function __get($name) {
    return @$this->params->$name;
  }

  public function __call($name, $args) {
    $table = $this->table();
    if (array_key_exists($name, (array)$table->fields)) {
      if (@$table->fields->$name->format->type == 'image' && $this->params->$name) {
        return model('images')->get($this->params->$name);
      }
      if (@$table->fields->$name->format->type == 'list' && $this->$name) {
        return model($table->fields->$name->format->table)->get($this->$name);
      }
      if (@$table->fields->$name->type == 'scope' && $table->fields->$name->format->type == 'image') {
        $images = array_key_values(model($table->fields->$name->format->table)->get(array(
          $table->fields->$name->format->id => $this->id
        )), $table->fields->$name->format->image);
        return model('images')->get(array('id' => $images));
      }
      if (@$table->fields->$name->type == 'files') {
        if ( ! is_array($this->_files)) {
          $files = model('files')->get(array(
            'object_type' => $table->name,
            'object_id'   => $this->id,
          ));
          foreach ($files as $file) {
            $this->_files[$file->object_field][] = $file;
          }
        }
        return (array)$this->_files[$name];
      }
      if (@$table->fields->$name->type == 'date') {
        $t = strtotime($this->{$name});
        $date = date($args[0], $t);
        $date = str_replace('м', funcs::month(date('n', $t)), $date);
        $date = str_replace('М', funcs::month(date('n', $t), 2), $date);
        return $date;
      }
    }
    else if (strpos($name, 'Btn') == strlen($name) - 3 || strpos($name, 'Button') == strlen($name) - 6) {
      $type = strpos($name, 'Btn') == strlen($name) - 3 ? 2 : 1;
      return format::btn(substr($name, 0, strpos($name, 'B')), $this->tableName, $this->id, array_merge(array('type' => $type), (array)$args[0]));
    }
  }

  function data() {
    return $this->params;
  }

  function table() {
    return self::$tables[$this->tableName];
  }

}

class modelObjectTable {

  protected $params;
  protected $object;
  protected static $childs;

  function __construct($table, modelObject $object) {
    $this->params = (object)$table;
    $this->object = $object;
  }

  function __get($name) {
    return @$this->params->$name;
  }

  function childs() {
    return self::getTableChilds($this->name);
  }

  static function getTableChilds($name) {
    if ( ! self::$childs[$name]) {
      foreach ( FC()->config('tables') as $tableName => $table) {
        foreach ( $table->fields as $fieldName => $field ) {
          if ( @$field->format->type == 'list' && $field->format->table == $name ) {
            $table->name = $tableName;
            self::$childs[$name][$fieldName] = $table;
          }
        }
      }
    }
    return self::$childs[$name];
  }
}