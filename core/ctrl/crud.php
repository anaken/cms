<?

class crudCtrl extends ctrl {

  const INSTALL_AVAILABLE = true;

  const ERROR_ACCESS_DENIED = 5601;

  protected function init() {
    if (self::request()->calls[1] == 'install' && self::INSTALL_AVAILABLE) {
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
      $object = model($table)->get($id);
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
    $objectTable->name = $table;
    $object = $id ? model($table)->get($id) : null;
    $data = $scope = $files = array();
    foreach ($objectTable->fields as $fieldName => $field) {
      $postFieldName = 'in_'.$fieldName;
      if (
        ! isset(self::request()->post[$postFieldName]) ||
        @$field->hidden ||
        $field->type == 'password' && ! $this->post($postFieldName)
      ) {
        continue;
      }

      if ($field->type == 'files') {
        $files[$fieldName] = $this->post($postFieldName);
        continue;
      }

      if ( is_array($this->post($postFieldName)) && $field->type == 'scope') {
        $scope[$fieldName] = $this->post($postFieldName);
        continue;
      }

      $data[$fieldName] = format::input($field, $this->post($postFieldName));

      if ( ! trim($data[$fieldName]) && @$field->required) {
        throw new Exception('Поле '.$field->caption.' должно быть заполнено', self::ERROR_USER_ERROR);
      }
    }
    if ( ! $data) {
      throw new Exception('Ошибка сохранения');
    }

    FC()->db->begin();

    if ($id && model($table)->get($id)) {
      model($table)->save($id, $data);
    }
    else {
      if ($id) {
        $data[$objectTable->id] = $id;
      }
      $newId = model($table)->save($data);
      $id = $id ? $id : $newId;
    }

    $this->_saveScope($id, $objectTable, $scope);
    $this->_saveFiles($id, $objectTable, $files);

    FC()->db->commit();

    $object = model($table)->get($id);
    $this->_json(array(
      'e'      => 0,
      'id'     => $id,
      'name'   => isset($objectTable->fields->name) ? $object->name : $object->id,
      'object' => ($object ? $object->data() : array()),
    ));
  }

  protected function _saveFiles($id, $objectTable, $files) {
    $existFiles = model('files')->get(array(
      'object_id'   => $id,
      'object_type' => $objectTable->name,
    ));
    $existFilesByNames = array();
    foreach ($existFiles as $existFile) {
      $existFilesByNames[$existFile->object_field][] = $existFile->id;
    }
    foreach ($objectTable->fields as $fieldName => $field) {
      if ($field->type != 'files') continue;
      if ($newFiles = array_diff((array)$files[$fieldName], (array)$existFilesByNames[$fieldName])) {
        foreach ($newFiles as $newFileId) {
          model('files')->save($newFileId, array(
            'object_id'    => $id,
            'object_type'  => $objectTable->name,
            'object_field' => $fieldName
          ));
        }
      }
      if ($delFiles = array_diff((array)$existFilesByNames[$fieldName], (array)$files[$fieldName])) {
        foreach ($delFiles as $delFileId) {
          model('files')->del($delFileId);
        }
      }
    }
  }

  protected function _saveScope($id, $objectTable, $scope) {
    foreach ($scope as $fieldName => $values) {
      $fieldFormat = $objectTable->fields->$fieldName->format;
      if ($fieldFormat->type == 'image') {
        $existImages = model($fieldFormat->table)->get(array( $fieldFormat->id => $id ));
        $existImagesIds = array_key_values($existImages, $fieldFormat->image);
        $delImagesIds = array_diff($existImagesIds, $values);
        $newImagesIds = array_diff($values, $existImagesIds);

        if ($delImagesIds) {
          model($fieldFormat->table)->delList(array( $fieldFormat->image => $delImagesIds ));
          model('images')->delList(array( 'id' => $delImagesIds ));
        }

        foreach ($newImagesIds as $value) {
          model($fieldFormat->table)->save(array(
            $fieldFormat->id    => $id,
            $fieldFormat->image => $value,
          ));
        }
      }
    }
  }

  function del() {
    $table = $this->post('object');
    $id = (int)$this->post('id');
    model($table)->del($id);
    $this->_json(array('e' => 0));
  }

  function childs() {
    $table = $this->post('object');
    $childs = modelObjectTable::getTableChilds($table);
    $this->_json(array(
      'e'      => 0,
      'name'   => $table,
      'childs' => $childs,
    ));
  }

  function upload() {
    $id = self::request()->get['id'];
    $is_multiple = self::request()->get['is_multiple'];

    if ($_FILES) {
      $id = $this->post('id');
      $is_multiple = $this->post('is_multiple');
      $uploaded = array();

      foreach ($_FILES['file']['name'] as $i => $v) {
        $file = array();
        foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $p) {
          $file[$p] = $_FILES['file'][$p][$i];
        }
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $data = array(
          'name' => $file['name'],
          'type' => $file['type'],
          'ext'  => $ext,
          'size' => $file['size']
        );

        FC()->db->begin();

        $file_id = model('files')->save($data);

        $dir = xFiles::getFilesDir(1).'/'.$file_id;
        if ( ! file_exists($dir)) {
          mkdir($dir, 0755, true);
        }
        $srcFile = $dir.'/src.'.$ext;
        $result = move_uploaded_file($file['tmp_name'], $srcFile);

        if ($result) {
          FC()->db->commit();
        }
        else {
          FC()->db->rollback();
        }

        $uploaded[] = array_merge($data, array(
          'id'   => $file_id,
          'file' => xFiles::getFilesDir().'/'.$file_id.'/src.'.$ext,
        ));
      }
    }
    return $this->view->render('crud/upload', array(
      'id'          => $id,
      'is_multiple' => $is_multiple,
      'uploaded'    => @$uploaded
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
    model($table)->saveList($items);
    $this->_json(array('e' => 0));
  }

  function install() {
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
    $order = $order ? $order : $table->id;
    $objects = model($tableName)->get($params, $order, $limit);
    return $this->view->render('crud/report', array(
      'table'     => $table,
      'tableName' => $tableName,
      'objects'   => $objects,
      'editable'  => $this->post('edit'),
      'defaults'  => $params
    ));
  }

  function menu() {
    return $this->view->render('crud/menu', array(
      'tables' => FC()->config('tables')
    ));
  }

}