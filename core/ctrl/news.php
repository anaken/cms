<?

class newsCtrl extends ctrl {

  function index() {
    return $this->block(array('count' => 100, 'main' => 1));
  }

  function defaults() {
    $newId = (int)$this->request()->item_id;
    $new = model()->news->get($newId);
    if ( ! $new) {
      throw new xException('Action {$newName} not found in '.get_class($this), self::ERROR_ACTION_NOT_FOUND);
    }
    return $this->show($newId);
  }

  function block($params = array()) {
    $count = isset($params['count']) ? (int)$params['count'] : 3;
    $isMainPage = @$params['main'] ? 1 : 0;
    if ($isMainPage) {
      view::subTitle('Новости');
    }
    return $this->view->render('news/block', array(
      'main' => $isMainPage,
      'news' => model()->news->get(array('date <=' => date('Y-m-d')), 'date desc', $count)
    ));
  }

  function show($id) {
    $new = model()->news->get($id);
    view::subTitle($new->name);
    return $this->view->render('news/show', array(
      'new' => $new
    ));
  }

}

?>