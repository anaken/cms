<?

class searchCtrl extends ctrl {

  function index() {
    $text = trim($_GET['text']);
    $this->request()->is_search_page = true;
    if ($text) {
      model()->searches->save(array('name' => $text));
    }
    view::subTitle('Поиск');
    return $this->view->render('search/index', array(
      'text'    => $text,
      'objects' => $this->_findObjects($text)
    ));
  }

  private function _findObjects($text) {
    $text = str_replace(array('%', "'"), array('\\%', "\\'"), $text);
    if ( ! $text) {
      return array();
    }
    $words = explode(' ', $text);
    foreach ($words as $k => $word) {
      if (strlen($word) > 12) {
        $word = substr($word, 0, -4);
      }
      $words[$k] = trim($word);
    }
    $text = implode(' ', $words);
    $tables = FC()->config('tables');
    $config = FC()->config('search');
    $tbls = array();
    foreach ($config as $objectName => $objectConfig) {
      if ( ! $objectConfig->name || ! @$tables->$objectName->link) continue;
      $link = str_replace(array('[', ']'), array("', `", "`, '"), $tables->$objectName->link);
      $wheres = array();
      foreach ($objectConfig->search as $field) {
        $wheres[] = "LOWER(`{$field}`) LIKE LOWER('%{$text}%')";
      }
      $wheres = implode(' OR ', $wheres);
      $tbls[] = "
        SELECT '{$tables->$objectName->caption}' as `caption`, '{$objectName}' as `table`, `{$objectConfig->name}` as `name`, CONCAT_WS('', '{$link}') as `link`
        FROM {$objectName}
        WHERE {$wheres}
      ";
    }
    $sql = implode("\nUNION\n", $tbls);
    return FC()->db->query($sql)->all();
  }

  function block() {
    if (@$this->request()->is_search_page) {
      return '';
    }
    return $this->view->render('search/block');
  }

}
