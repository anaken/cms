<?

class format {

  const ERROR_FORMAT_NOT_FOUND     = 211;
  const ERROR_CANNON_APPLY_PREFORM = 3311;

  const BUTTON_TYPE_NORMAL = 1;
  const BUTTON_TYPE_SMALL  = 2;

  protected static $instance;

  protected static $btnTypes = array(
    'add'    => array('icon' => 'plus',        'act' => 'form',   'caption' => 'Добавить'),
    'edit'   => array('icon' => 'pencil',      'act' => 'form',   'caption' => 'Редактировать'),
    'del'    => array('icon' => 'close',       'act' => 'del',    'caption' => 'Удалить'),
    'childs' => array('icon' => 'folder-open', 'act' => 'childs', 'caption' => 'Открыть'),
  );

  private function __construct() {}

  protected static function i() {
    if ( ! self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  public static function button($inside, $params) {
    $tag = @$params['tag'] ? $params['tag'] : 'div';
    $href = @$params['href'] ? ' href="'.$params['href'].'"' : '';
    return '<'.$tag.' class="button"'.$href.'>'.$inside.'</'.$tag.'>';
  }

  public static function smallButton($inside, $params) {
    $tag = @$params['tag'] ? $params['tag'] : 'div';
    $href = @$params['href'] ? ' href="'.$params['href'].'"' : '';
    $class = @$params['class'] ? ' '.$params['class'] : '';
    $click = @$params['click'] ? ' onclick="'.$params['click'].'"' : '';
    return '<'.$tag.' class="sgold'.$class.'"'.$href.$click.'><div class="sgl"><div class="sgr"><div class="sgm">'.$inside.'</div></div></div></'.$tag.'>';
  }

  public static function btn($type, $tableName, $id = null, $params = array()) {
    $button = self::$btnTypes[$type];
    $caption = @$params['caption'] ? $params['caption'] : $button['caption'];
    $crudParams = (object)array_intersect_key($params, array_flip(array(
      'defaults', 'appendToList'
    )));
    $buttonType = $params['type'] ? $params['type'] : self::BUTTON_TYPE_NORMAL;
    return '<button class="'.$button['act'].'ObjectBtn crudObjectBtn" button-type="'.$buttonType.'" data-table="'.$tableName.'" data-id="'.$id.'" icon="'.$button['icon'].'" onclick="return crud.'.$button['act'].'(this, '.htmlspecialchars(json_encode($crudParams)).')">'.$caption.'</button>';
  }

  /**
   * @todo часть функционала из _in_string должна перекочевать сюда (всякие проверки на default, editable и прочее..)
   */
  public static function in($field, modelObject $object = null, $params = array()) {
    $format = (@$field->format ? $field->format->type : $field->type);
    $method = '_in_'.$format;
    if ( ! method_exists(self::i(), $method)) {
      throw new xException("Формат {$format} не найден.", self::ERROR_FORMAT_NOT_FOUND);
    }
    return self::i()->$method($field, $object, $params);
  }

  public static function out($field, $object, $params = array()) {
    $fieldConfig = $object->table()->fields->$field;
    $format = (@$fieldConfig->format ? $fieldConfig->format->type : $fieldConfig->type);
    if ( ! in_array($format, array('files', 'list', 'password'))) {
      return $object->$field;
    }
    $method = '_out_'.$format;
    if ( ! method_exists(self::i(), $method)) {
      throw new xException("Формат {$format} не найден.", self::ERROR_FORMAT_NOT_FOUND);
    }
    return self::i()->$method($field, $object, $params);
  }

  function _in_int($field, $object = null, $params = array()) {
    return $this->_in_string($field, $object);
  }

  function _in_date($field, $object = null, $params = array()) {
    return $this->_in_string($field, $object, array('attr' => array('class' => 'datepicker')));
  }

  function _in_datetime($field, $object = null, $params = array()) {
    return $this->_in_string($field, $object);
  }

  function _in_bool($field, $object = null, $params = array()) {
    $value = ($object ? $object->{$field->name} : @$params['value']);
    return '<input class="crudCheckbox" type="checkbox" name="in_'.$field->name.'"'.($value || ! $object && $field->default ? ' checked' : '').' value="1"/>';
  }

  function _in_password($field, $object = null, $params = array()) {
    return '<input type="password" name="in_'.$field->name.'"/>';
  }

  function _in_string($field, $object = null, $params = array()) {
    $value = ($object ? $object->{$field->name} : @$params['value']);
    $attr = $params['attr'];
    if (is_array($attr)) {
      foreach ($attr as $a => $v) {
        $attr[$a] = ' ' . $a . '="' . str_replace('"', '\\"', $v) . '"';
      }
    }
    $value = $object || $value ? $value : $field->default;
    $editable = isset($field->editable) && ! $field->editable ? 'disabled' : '';
    return '<input '.$editable.' type="text" name="in_'.$field->name.'" value="'.$value.'"'.implode('', (array)$attr).'/>';
  }

  function _in_text($field, $object = null, $params = array()) {
    $value = ($object ? $object->{$field->name} : @$params['value']);
    return '<textarea name="in_'.$field->name.'">'.htmlspecialchars($value).'</textarea>';
  }

  function _in_editor($field, $object = null, $params = array()) {
    $value = ($object ? $object->{$field->name} : @$params['value']);
    return '<textarea name="in_'.$field->name.'" class="ckeditor" id="in_'.$field->name.'">'.htmlspecialchars($value).'</textarea><script>var editor = CKEDITOR.replace("in_'.$field->name.'");CKFinder.setupCKEditor( editor, { basePath : "/ext/ckfinder/", rememberLastFolder : false } ) ;</script>';
  }

  function _in_list($field, $object = null, $params = array()) {
    $table = $field->format->table;
    $filter = array();
    if ($field->format->except && $object) {
      $except = explode(':', $field->format->except);
      $filter[$except[0].' !='] = $object->{$except[1]};
    }
    $items = model($table)->get($filter);
    $result = '<select name="in_'.$field->name.'" class="formatInList formatInEditableList inlist'.$field->name.'">';
    if ( ! @$field->required) {
      $result .= '<option value=""></option>';
    }
    foreach ($items as $item) {
      $selected = (($object && $item->{$field->format->id} && $object->{$field->name} == $item->{$field->format->id}) || @$params['value'] == $item->{$field->format->id}) ? ' selected' : '';
      $result .= '<option'.$selected.' value="'.$item->{$field->format->id}.'">'.$item->{$field->format->name}.'</option>';
    }
    $result .= '</select>';
    if (@$field->format->editable) {
      $result .= '<button class="crudObjectBtn" button-type="2" icon="plus" onclick="crud.form(this, {table: \''.$table.'\', appendToList: \'.inlist'.$field->name.'\'})">Добавить</button>';
      $result .= '<button class="crudObjectBtn" button-type="2" icon="close" onclick="crud.del(this, {table: \''.$table.'\', id: $(\'.inlist'.$field->name.'\').val(), removeFrom: \'.inlist'.$field->name.'\'})">Удалить</button>';
    }
    return $result;
  }

  function _in_files($field, $object = null, $params = array()) {
    $inputIdent = md5(time()*rand());
    $data = '';
    $inputName = 'in_'.$field->name.'[]';
    if ($object) {
      $files = (array)$object->{$field->name}();
      foreach ($files as $file) {
        $filePreview = $file->isImage() ? '<img src="'.$file->path().'"/>' : '<a href="'.$file->path().'">'.$file->name.'</a>';
        $data .= '<div class="crudFilesInput"><button button-type="2" icon="close" class="formatFileRemove objectDelBtn" onclick="crud.removeFile(this)">удалить</button><input type="hidden" name="'.$inputName.'" value="'.$file->id.'"/>'.$filePreview.'</div>';
      }
    }
    return '<div class="fileViewPlace" id="crudFileView'.$inputIdent.'" data-name="'.$inputName.'" data-multiple="1"><div class="crudFilesInputs">'.$data.'</div><iframe class="fileIframe" src="/crud/upload/?id='.$inputIdent.'&is_multiple=1"></iframe><button class="uploadFileBtn" onclick="crud.upload(\''.$inputIdent.'\')">Выбрать</button></div>';
  }

  function _out_files($field, $object, $params) {
    $files = $object->{$field}();
    $result = array();
    foreach ($files as $file) {
      if ($file->isImage()) {
        $params = array_merge(array('width' => 100, 'height' => 100), $params);
        $src = $params['width'] && $params['height'] ? $file->thumb($params['width'], $params['height']) : $file->path();
        $result[] = '<img src="'.$src.'">';
      }
      else {
        $result[] = '<a href="'.$file->path().'">'.$file->name.'</a>';
      }
    }
    return implode("\n", $result);
  }

  function _out_list($field, $object, $params) {
    $fieldConfig = $object->table()->fields->$field;
    $id = (int)$object->$field;
    if ( ! $id) {
      return '';
    }
    return model($fieldConfig->format->table)->get($id)->{$fieldConfig->format->name};
  }

  function _out_password($field, $object, $params) {
    return '***';
  }

  public static function input($field, $data) {
    if ($field->type == 'int' && ! is_numeric($data)) {
      $data = null;
    }
    if ($field->type == 'bool' && $data) {
      $data = 1;
    }
    return $data;
  }

  public static function preform($field, $data) {
    $type = $field->preform->type;
    $method = '_preform_'.$type;
    if ( ! method_exists(self::i(), $method)) {
      throw new xException("Формат {$type} не найден.", self::ERROR_FORMAT_NOT_FOUND);
    }
    return self::i()->$method($field, $data);
  }

  private function _preform_link($field, $data) {
    if (isset($field->preform->target)) {
      $target = @$data[$field->preform->target];
      if ( ! $target) {
        throw new Exception('Нельзя применить формат link', self::ERROR_CANNON_APPLY_PREFORM);
      }
      $url = $data[$field->preform->target];
    }
    else {
      $url = strpos($data[$field->name], 'http') === 0 ?
        $data[$field->name] :
        ltrim($data[$field->name], '/');
    }
    return funcs::url($url);
  }

  private function _preform_md5($field, $data) {
    ctrl::i('auth');
    return md5($data[$field->name].authCtrl::SALT);
  }

}