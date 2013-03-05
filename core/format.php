<?

class format {

  const ERROR_FORMAT_NOT_FOUND = 211;

  protected static $instance;

  protected static $imgNumber = 0;

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
    return '<'.$tag.' class="gold"'.$href.'><div class="gold21"><div class="gold23"><div class="gold32"><div class="gold11"><div class="gold13"><div class="gold31"><div class="gold33">'.$inside.'</div></div></div></div></div></div></div></'.$tag.'>';
  }

  public static function smallButton($inside, $params) {
    $tag = @$params['tag'] ? $params['tag'] : 'div';
    $href = @$params['href'] ? ' href="'.$params['href'].'"' : '';
    $class = @$params['class'] ? ' '.$params['class'] : '';
    $click = @$params['click'] ? ' onclick="'.$params['click'].'"' : '';
    return '<'.$tag.' class="sgold'.$class.'"'.$href.$click.'><div class="sgl"><div class="sgr"><div class="sgm">'.$inside.'</div></div></div></'.$tag.'>';
  }

  public static function editBtn(modelObject $object) {
    $table = $object->table();
    return '<button class="objectEditBtn" onclick="crud.form(\''.$table->name.'\', '.$object->{$table->id}.')">Редактировать</button>';
  }

  public static function delBtn(modelObject $object) {
    $table = $object->table();
    return '<button class="objectDelBtn" onclick="crud.del(\''.$table->name.'\', '.$object->{$table->id}.')">Удалить</button>';
  }
  
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
    if ( ! in_array($format, array('image', 'list'))) {
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
    return '<input class="crudCheckbox" type="checkbox" name="in_'.$field->name.'"'.($value ? ' checked' : '').' value="1"/>';
  }

  function _in_string($field, $object = null, $params = array()) {
    $value = ($object ? $object->{$field->name} : @$params['value']);
    $attr = $params['attr'];
    if (is_array($attr)) {
      foreach ($attr as $a => $v) {
        $attr[$a] = ' ' . $a . '="' . str_replace('"', '\\"', $v) . '"';
      }
    }
    return '<input type="text" name="in_'.$field->name.'" value="'.$value.'"'.implode('', (array)$attr).'/>';
  }

  function _in_text($field, $object = null, $params = array()) {
    $value = ($object ? $object->{$field->name} : @$params['value']);
    return '<textarea name="in_'.$field->name.'">'.htmlspecialchars($value).'</textarea>';
  }

  function _in_editor($field, $object = null, $params = array()) {
    $value = ($object ? $object->{$field->name} : @$params['value']);
    return '<textarea name="in_'.$field->name.'" class="ckeditor" id="in_'.$field->name.'">'.htmlspecialchars($value).'</textarea><script>var editor = CKEDITOR.replace("in_'.$field->name.'");CKFinder.setupCKEditor( editor, { basePath : "/ext/ckfinder/", rememberLastFolder : false } ) ;</script>';
  }

  function _in_image($field, $object = null, $params = array()) {
    self::$imgNumber++;
    $img = '';
    if ($object && $object->{$field->name}) {
      $imageId = (int)$object->{$field->name};
      $image = model()->images->get($imageId);
      $img = '<img src="/img/user/'.$imageId.($image->ext ? '.'.$image->ext : '').'"/>';
    }
    return '<input class="imageHiddenField'.self::$imgNumber.'" type="hidden" name="in_'.$field->name.'" value="'.$imageId.'"/><div class="formatImage"><div class="imageViewPlace imageView'.self::$imgNumber.'">'.$img.'</div><div class="fileIframe"><iframe id="uploadFile'.self::$imgNumber.'" name="uploadFile'.self::$imgNumber.'" src="/crud/upload/'.self::$imgNumber.'/'.$imageId.'"></iframe></div><button class="uploadImageBtn" onclick="crud.upload('.self::$imgNumber.(@$imageId ? ','.$imageId : '').')">Выбрать</button></div>';
  }

  function _in_list($field, $object = null, $params = array()) {
    $table = $field->format->table;
    $filter = array();
    if ($field->format->except && $object) {
      $except = explode(':', $field->format->except);
      $filter[$except[0].' !='] = $object->{$except[1]};
    }
    $items = model()->$table->get($filter);
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
      $result .= '<button class="objectAddBtn" onclick="crud.form(\''.$table.'\', null, {appendToList: \'.inlist'.$field->name.'\'})">Добавить</button>';
      $result .= '<button class="objectDelBtn" onclick="crud.del(\''.$table.'\', $(\'.inlist'.$field->name.'\').val(), {removeFrom: \'.inlist'.$field->name.'\'})">Удалить</button>';
    }
    return $result;
  }

  function _out_image($field, $object, $params) {
    $imageId = (int)$object->$field;
    if ( ! $imageId) {
      return '';
    }
    $image = model()->images->get($imageId);
    return '<img src="/img/user/'.$imageId.($image->ext ? '.'.$image->ext : '').'">';
  }

  function _out_list($field, $object, $params) {
    $fieldConfig = $object->table->fields->$field;
    $id = (int)$object->$field;
    return model()->{$fieldConfig->format->table}->get($id)->{$fieldConfig->format->name};
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
    $target = @$data[$field->preform->target];
    if ( ! is_null($target)) {
      return funcs::url($data[$field->preform->target]);
    }
    return $target;
  }

}