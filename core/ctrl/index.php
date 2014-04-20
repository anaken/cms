<?

class indexCtrl extends ctrl {

  const ERROR_SEND_ORDER = 10101;

  function index() {
    return $this->text(1) . ctrl::call('news.block') . ctrl::call('index.newGoods');
  }

  function defaults() {
    $type = array(
      'text'    => 'texts',
      'catalog' => 'rubrics',
      'good'    => 'goods'
    );
    $objectCall = isset($type[$this->request()->calls[0]]) ? $this->request()->calls[0] : 'text';
    $link = urldecode(implode('/', $this->request()->calls));
    $objectType = @$type[$objectCall];
    if ( ! $objectType || ! $link) {
      throw new xException("Action {$link} not defined", self::ERROR_ACTION_NOT_FOUND);
    }
    $objectId = $this->request()->item_id;
    $objectFilter = ($objectCall == 'text' ? array('link' => $link) : array('id' => $objectId));
    $object = current(model($objectType)->get($objectFilter));
    if ($object) {
      return $this->$objectCall($object->id);
    }
    else if ($objectCall == 'text' && FC()->user->is_admin) {
      return $this->view->render('index/text', array(
        'link' => $link,
        'text' => $object
      ));
    }
    else {
      throw new xException("Action {$link} not defined", self::ERROR_ACTION_NOT_FOUND);
    }
  }

  function rubricCol() {
    if ($this->request()->rubric) {
      $active = ($this->request()->rubric->parent_id > 0 ? model('rubrics')->get($this->request()->rubric->parent_id) : $this->request()->rubric);
      $subrubrics = model('rubrics')->get(array('parent_id' => $active->id), 'sort, name');
    }
    return $this->view->render('index/rubricCol', array(
      'rubrics'    => model('rubrics')->get(array('parent_id' => null), 'sort, name'),
      'selected'   => $this->request()->rubric,
      'active'     => $active,
      'subrubrics' => $subrubrics,
    ));
  }

  function catalog($id = null) {
    $id = is_null($id) ? (int)current($this->request()->params) : $id;
    if ( ! $id || ! ($rubric = model('rubrics')->get($id))) {
      $this->error404();
    }
    $this->request()->rubric = $rubric;
    view::subTitle($rubric->name);
    return $this->view->render('index/catalog', array(
      'rubric'    => $rubric,
      'goodsList' => $this->catalogGoods($id)
    ));
  }

  function catalogGoods($id = null) {
    if ( ! $id) {
      $id = (int)$this->post('rubric_id');
      if ( ! $id) {
        $this->error404();
      }
    }
    if ( ! $id || ! ($rubric = model('rubrics')->get($id))) {
      $this->error404();
    }
    $rubricIds = array($rubric->id);
    $rubricIds = array_merge($rubricIds, array_key_values(model('rubrics')->get(array('parent_id' => $rubric->id)), 'id'));
    $goodsFilter = array('rubric_id' => $rubricIds);
    $manufacturersId = array_map('intval', array_keys((array)$this->post('manufacturers'), 'checked'));
    if ($manufacturersId) {
      $goodsFilter['manufacturer_id'] = $manufacturersId;
    }
    $goods = model('goods')->get($goodsFilter);
    if ($this->request()->is_ajax) {
      ctrl::setLayout(null);
    }
    return $this->view->render('index/catalogGoods', array(
      'goods' => $goods,
    ));
  }

  function good($id = null) {
    $id = is_null($id) ? (int)current($this->request()->params) : $id;
    $good = model('goods')->get($id);
    if ( ! $good) {
      $this->error404();
    }
    $rubric = model('rubrics')->get($good->rubric_id);
    $this->request()->rubric = $rubric;
    view::subTitle($rubric->name);
    view::subTitle($good->name);
    view::addLib('fancybox');
    return $this->view->render('index/good', array(
      'good'   => $good,
      'rubric' => $rubric
    ));
  }

  function hit() {
    return $this->view->render('index/hit', array(
      'good' => current(model('goods')->get(array('hit' => 1), 'rand()', 1))
    ));
  }

  function newGoods() {
    $goods = model('goods')->get(array('novelty' => 1), 'rand()', 3);
    $goodsList = $this->view->render('index/catalogGoods', array(
      'goods' => $goods
    ));
    return $this->view->render('index/newGoods', array(
      'goods'     => $goods,
      'goodsList' => $goodsList
    ));
  }

  private function _getCartGoods() {
    if ( ! @$_COOKIE['goods'] || ! is_array($_COOKIE['goods'])) {
      return array();
    }
    $goodsIds = array_map('intval', array_keys($_COOKIE['goods']));
    $goods = model('goods')->get(array('id' => $goodsIds));
    return $goods;
  }

  function cartBlock() {
    $sum = $cnt = 0;
    $goods = $this->_getCartGoods();
    foreach ($goods as $good) {
      $sum += (int)$good->price * $_COOKIE['goods'][$good->id];
      $cnt += $_COOKIE['goods'][$good->id];
    }
    $result = $this->view->render('index/cartBlock', array(
      'goods' => $goods,
      'sum'   => $sum,
      'cnt'   => $cnt
    ));
    if ($this->request()->is_ajax) {
      die((string)$result);
    }
    return $result;
  }

  function cart() {
    $goods = $this->_getCartGoods();
    view::subTitle('Корзина');
    return $this->view->render('index/cart', array(
      'goods'  => $goods
    ));
  }

  function order() {
    $error = $notice = '';
    $goods = $this->_getCartGoods();
    if ( ! $goods) {
      header('Location: /cart/');
      exit;
    }

    $params = array(
      'name'  => $this->post('name'),
      'phone' => $this->post('phone'),
      'desc'  => $this->post('desc'),
    );
    
    if ($params['name'] && $params['phone']) {
      $msg = $this->view->render('index/orderMessage', array_merge($params, array(
        'goods' => $goods
      )));
      $result = @mail(FC()->config()->orderEmail, 'Заказ с сайта '.$_SERVER['HTTP_HOST'], (string)$msg, 'Content-type: text/html; charset=utf-8' . "\r\n");
      if ( ! $result) {
        throw new xException("Ошибка отправки заказа", self::ERROR_SEND_ORDER);
      }
      else {
        $orderId = model('orders')->save(array(
          'created' => date('Y-m-d H:i:s'),
          'user'    => $params['name'],
          'phone'   => $params['phone'],
          'desc'    => $params['desc'],
        ));
        foreach ($_COOKIE['goods'] as $goodId => $goodCnt) {
          model('order_goods')->save(array(
            'order_id' => $orderId,
            'good_id'  => (int)$goodId,
            'cnt'      => (int)$goodCnt
          ));
          setcookie(urlencode('goods['.$goodId.']'), '', time()-3600);
        }
        unset($_COOKIE['goods']);
        return '<p class="noticeMsg">Спасибо! Ваш заказ успешно отправлен!</p>';
      }
    }
    else if ($this->post()) {
      if ( ! $params['name']) {
        $error = 'Заполните поле Ф.И.О.';
      }
      if ( ! $params['phone']) {
        $error = 'Заполните поле Телефон';
      }
    }
    return $this->view->render('index/order', array_merge($params, array(
      'error'  => $error,
      'notice' => $notice,
    )));
  }

  protected function _getText($id) {
    if ($id) {
      $text = model('texts')->get($id);
    }
    return @$text;
  }

  function text($id = null) {
    if ( ! is_numeric($id)) {
      $id = (int)current($this->request()->params);
    }
    $text = $this->_getText($id);
    view::subTitle($text->name);
    return $this->view->render('index/text', array(
      'id'   => (int)$id,
      'text' => $text
    ));
  }

  function string($id = null) {
    if ( ! is_numeric($id)) {
      $id = (int)current($this->request()->params);
    }
    $text = $this->_getText($id);
    return $this->view->render('index/string', array(
      'id'   => (int)$id,
      'text' => $text
    ));
  }

  function menu() {
    return $this->view->render('index/menu', array(
      'items' => model('menu')->get(null, 'sort desc, name')
    ));
  }

}
