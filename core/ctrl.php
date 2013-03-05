<?

require_once(APP_PATH.'/model.php');

require_once(APP_PATH.'/view.php');

require_once(APP_PATH.'/format.php');

class ctrl {

  const ERROR_CONTROLLER_NOT_FOUND = 100;
  const ERROR_ACTION_NOT_FOUND     = 101;
  const ERROR_PARAMS               = 201;

  const ERROR_USER_ERROR           = 11;

  const FLOAT_METHOD = 'defaults';

  /**
   * @var view
   */
  protected $view;

  /**
   * @var object
   */
  protected static $_request;

  private static $_instances = array();

  protected static $layout = 'layout';

  private function __construct() {
    $this->view = new view();
    $this->init();
  }

  protected function init() {}

  public static function i($name = '') {
    if (isset(self::$_instances[$name])) {
      return self::$_instances[$name];
    }
    if ( ! $name) {
      return self::$_instances[$name] = new self();
    }
    if ( ! ($file = self::_findFile($name))) {
      throw new xException("Controller file not found for controller: {$name}", self::ERROR_CONTROLLER_NOT_FOUND);
    }
    require_once($file);
    $className = $name.'Ctrl';
    if ( ! class_exists($className)) {
      throw new xException("Class {$className} not found in file {$file} for {$name}", self::ERROR_CONTROLLER_NOT_FOUND);
    }
    return self::$_instances[$name] = new $className();
  }

  final public function handle() {
    $controller = $this->request()->calls[0];
    $action = $this->request()->calls[1];
    if (FC()->user->is_admin) {
      view::addJs('system');
      view::addJs('/ext/ckeditor/ckeditor');
      view::addJs('/ext/ckfinder/ckfinder');
      view::addCss('system');
    }
    try {
      $call = self::call($controller . '.' . $action);
      if (self::$layout) {
        return $this->view->render(self::$layout, array('call' => $call));
      }
      return $call;
    }
    catch (Exception $e) {
      if (in_array($e->getCode(), array(self::ERROR_CONTROLLER_NOT_FOUND, self::ERROR_ACTION_NOT_FOUND))) {
        $this->error404();
      }
      throw $e;
    }
  }

  final public function request($part = null) {
    if ( ! self::$_request) {
      $uri = $_SERVER['REQUEST_URI'];
      $p = strpos($uri, '?');
      if ($p) {
        $uri = substr($uri, 0, $p);
      }
      $parts = explode('/', trim($uri, ' /'));
      $calls = array();
      $params = array();
      foreach ($parts as $p) {
        if (is_numeric($p) && $p > 0) {
          $params[] = $p;
        }
        else {
          $calls[] = $p;
        }
      }
      if ($calls) {
        $itemParts = explode('-', $calls[count($calls) - 1]);
        $itemId = array_pop($itemParts);
      }
      self::$_request = (object)array(
        'item_id' => (int)$itemId,
        'params'  => $params,
        'parts'   => $parts,
        'calls'   => $calls,
        'url'     => $uri,
        'get'     => $_GET,
        'post'    => $_POST,
        'is_ajax' => @$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest'
      );
    }
    if (is_numeric($part)) {
      return self::$_request->parts[$part];
    }
    return self::$_request;
  }

  final public function post($name = null) {
    return (is_null($name) ? $this->request()->post : $this->request()->post[$name]);
  }

  private static function _findFile($name) {
    $file = dirname(__FILE__).'/ctrl/'.strtolower($name).'.php';
    return file_exists($file) ? $file : false;
  }

  public static function call($controllerMethod) {
    $detail = explode('.', (string)$controllerMethod);
    $callArgs = array_slice(func_get_args(), 1);
    $controller = @$detail[0] ? $detail[0] : 'index';
    $action = @$detail[1] ? $detail[1] : 'index';
    try {
      $object = self::i($controller);
    }
    catch (Exception $e) {
      if ($e->getCode() == self::ERROR_CONTROLLER_NOT_FOUND) {
        $object = self::i('index');
        if ($action == 'index') {
          $action = $controller;
        }
      }
      else {
        throw $e;
      }
    }
    if ( ! method_exists($object, $action)) {
      if (method_exists($object, self::FLOAT_METHOD)) {
        $action = self::FLOAT_METHOD;
      }
      else {
        throw new xException("Action {$action} not defined in controller {$ctrl}", self::ERROR_ACTION_NOT_FOUND);
      }
    }
    return new ctrlResult(array(
      'object' => $object,
      'result' => call_user_func_array(array($object, $action), $callArgs)
    ));
  }

  public static function setLayout($layout) {
    self::$layout = $layout;
  }

  public function error404() {
    ob_get_clean();
    if ( ! headers_sent()) {
      header("HTTP/1.0 404 Not Found");
    }
    die($this->view->render(self::$layout, array('call' => $this->view->render('404'))));
  }

  public function _json($data) {
    die(json_encode($data));
  }

}

function ctrl($name = '') {
  return ctrl::i($name);
}

class ctrlResult {

  var $params;

  function __construct($params) {
    $this->params = $params;
  }

  function __toString() {
    return $this->params['result'];
  }

  function __get($name) {
    return @$this->params[$name];
  }

}