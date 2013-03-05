<?

class crudCtrl extends ctrl {

  const INSTALL_AVAILABLE = true;

  const ERROR_ACCESS_DENIED = 5601;

  protected function init() {
    if ($this->request()->calls[1] == 'install' && self::INSTALL_AVAILABLE) {
      return;
    }
    if ( ! FC()->user->is_admin) {
      throw new xException("Доступ закрыт", self::ERROR_ACCESS_DENIED);
    }
    self::setLayout('crud/layout');
  }

  function form() {
    $table = $this->post('object');
    $id = (int)$this->post('id');
    $defaults = $this->post('defaults');
    $tables = FC()->config('tables');
    if ( ! isset($tables->$table)) {
      throw new xException("Объект {$table} не найден", self::ERROR_PARAMS);
    }
    $object = null;
    if ($id) {
      $object = model()->$table->get($id);
    }
    return $this->view->render('crud/form', array(
      'tableName' => $table,
      'table'     => $tables->$table,
      'id'        => $id,
      'object'    => $object,
      'defaults'  => $defaults
    ));
  }

  function save() {
    $table = $this->post('_table_');
    $id = (int)$this->post('_id_');
    $objectTable = FC()->config('tables')->$table;
    $data = array();
    foreach ($objectTable->fields as $fieldName => $field) {
      $postFieldName = 'in_'.$fieldName;
      if ( ! isset($this->request()->post[$postFieldName]) || @$field->hidden) continue;
      $data[$fieldName] = format::input($field, $this->post($postFieldName));
      if ( ! trim($data[$fieldName]) && @$field->required) {
        throw new xException('Поле '.$field->caption.' должно быть заполнено', self::ERROR_USER_ERROR, 'Поле '.$field->caption.' должно быть заполнено');
      }
    }
    if ( ! $data) {
      throw new xException('Ошибка сохранения', self::ERROR_PARAMS);
    }
    if ($id && model()->$table->get($id)) {
      model()->$table->save($id, $data);
    }
    else {
      if ($id) {
        $data[$objectTable->id] = $id;
      }
      $id = model()->$table->save($data);
    }
    $object = model()->$table->get($id);
    $this->_json(array(
      'e'      => 0,
      'id'     => $id,
      'name'   => isset($objectTable->fields->name) ? $object->name : $object->id,
      'object' => ($object ? $object->data() : array()),
    ));
  }

  function del() {
    $table = $this->post('object');
    $id = (int)$this->post('id');
    $objectTable = FC()->config('tables')->$table;
    model()->$table->del($id);
    $this->_json(array('e' => 0));
  }

  function upload() {
    if ($_FILES) {
      $number = (int)$this->post('number');
      $id = (int)$this->post('id');
      $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
      $data = array(
        'name' => $_FILES['file']['name'],
        'type' => $_FILES['file']['type'],
        'ext'  => $ext
      );
      FC()->db->begin();
      if ($id) {
        model()->images->save($id, $data);
      }
      else {
        $id = model()->images->save($data);
      }
      $imgFile = APP_PATH.'/../img/user/'.$id.'.'.$ext;
      $thumbFile = APP_PATH.'/../img/user/thumb/'.$id.'.'.$ext;
      $thumbBigFile = APP_PATH.'/../img/user/thumb/big/'.$id.'.'.$ext;
      $result = move_uploaded_file($_FILES['file']['tmp_name'], $imgFile);
      funcs::imageThumb($imgFile, $thumbFile, 170, 100, IMAGETYPE_JPEG);
      funcs::imageThumb($imgFile, $thumbBigFile, 350, 350, IMAGETYPE_JPEG);
      if ($result) {
        FC()->db->commit();
      }
      else {
        FC()->db->rollback();
      }
      $uploaded = array_merge($data, array(
        'file' => '/img/user/'.$id.'.'.$ext
      ));
    }
    else {
      $number = (int)array_shift($this->request()->params);
      $id = (int)array_shift($this->request()->params);
    }
    return $this->view->render('crud/upload', array(
      'id'       => $id,
      'number'   => $number,
      'uploaded' => @$uploaded
    ));
  }

  function resort() {
    $order = $this->post('order');
    $table = $this->post('object');
    $objectTable = FC()->config('tables')->$table;
    $items = array();
    foreach ($order as $k => $id) {
      $items[] = array(
        $objectTable->id    => $id,
        $objectTable->order => $k + 1,
      );
    }
    model()->$table->saveList($items);
    $this->_json(array('e' => 0));
  }

  function install($table = null) {
    $tables = FC()->config('tables');
    foreach ((array)$tables as $tableName => $table) {
      model()->install($tableName);
      echo "Installing table {$tableName}".str_repeat('.', 30)."OK<br/>";
    }
    exit;
  }

  function report() {
    $tableName = $this->post('table');
    $params = $this->post('params');
    $order = $this->post('order');
    $limit = $this->post('limit');
    $table = FC()->config('tables')->$tableName;
    $objects = model()->$tableName->get($params, $order, $limit);
    return $this->view->render('crud/report', array(
      'table'     => $table,
      'tableName' => $tableName,
      'objects'   => $objects,
    ));
  }

}

?>