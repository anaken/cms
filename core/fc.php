<?

define('APP_PATH', dirname(__FILE__));

require_once(APP_PATH.'/funcs.php');

require_once(APP_PATH.'/db.php');

require_once(APP_PATH.'/ctrl.php');

require_once(APP_PATH.'/model.php');

class FC {

  /**
   * Экземпляр контроллера
   * @var FC
   */
  protected static $instance;

  /**
   * Класс для работы с базой данных
   * @var simpleDb
   */
  public $db;

  /**
   * Конфигурация
   * @var array
   */
  private $config;

  /**
   * Данные авторизованного пользователя
   * @var object
   */
  public $user;

  private function __construct() {
    $this->init();
  }

  public static function i() {
    if ( ! self::$instance) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function init() {
    session_start();
    $this->db = new simpleDb($this->config()->db);
    $userData = (array)@$_SESSION['user'];
    $this->user = new modelObject($this->config('tables')->users, $userData);
  }

  public function config($name = 'index') {
    if ( ! isset($this->config[$name])) {
      $config = json_decode(file_get_contents(APP_PATH.'/config/'.$name.'.json'));
      if ( ! $config) {
        throw new xException("Ошибка конфигурации {$name}", 1);
      }
      $this->config[$name] = $config;
    }
    return $this->config[$name];
  }

  public function handle() {
    return ctrl()->handle();
  }

  public function defaultExceptionHandler($e) {
    if ($e->getCode() == E_NOTICE || $e->getCode() == E_STRICT) {
      return;
    }
    file_put_contents(APP_PATH.'/logs/exceptions.log', date('Y-m-d H:i:s').': '."[".$e->getCode()."] ".$e->getMessage()."\nFile: ".$e->getFile().':'.$e->getLine()."\nTrace: ".$e->getTraceAsString()."\n\n", FILE_APPEND);
    //$this->sendErrorMail($error);
    if (ctrl()->request()->is_ajax) {
      ob_clean();
      ctrl()->_json(array('e' => ($e->getCode() ? $e->getCode() : 1), 'msg' => $e->getMessage()));
    }
    //die($e->getMessage());
  }

  public function defaultErrorHandler($errno, $errstr = '', $errfile = '', $errline = '') {
    if ($errno == E_NOTICE || $errno == E_STRICT) {
      return;
    }
    file_put_contents(APP_PATH.'/logs/errors.log', date('Y-m-d H:i:s').': '."[".$errno."] ".$errstr."\nFile: ".$errfile.':'.$errline."\n\n", FILE_APPEND);
    //$this->sendErrorMail($error);
    if (ctrl()->request()->is_ajax) {
      ob_clean();
      ctrl()->_json(array('e' => 1, 'msg' => 'Системная ошибка'));
    }
    //die($errstr);
  }

  public function defaultShutdownHandler() {
    $error = error_get_last();
    if ( ! $error || $error['type'] == E_NOTICE || $error['type'] == E_STRICT) {
      return;
    }
    file_put_contents(APP_PATH.'/logs/shutdowns.log', date('Y-m-d H:i:s').': '.print_r($error,1)."\n\n", FILE_APPEND);
    //$this->sendErrorMail(print_r($error,1));
    if (ctrl()->request()->is_ajax) {
      //ob_clean();
      //ctrl()->_json(array('e' => 1, 'msg' => $error['message']));
    }
  }

  public function sendErrorMail($error) {
    @mail(FC()->config()->adminEmail, 'Ошибка на сайте '.$_SERVER['HTTP_HOST'], $error);
  }

}

function FC() {
  return FC::i();
}

class xException extends Exception {

  private $systemMessage;
  
  function __construct($systemMessage, $code, $message = 'Системная ошибка') {
    $this->systemMessage = $systemMessage;
    parent::__construct($message, $code);
  }

  function getSystemMessage() {
    return $this->systemMessage;
  }

}

set_exception_handler(array(FC(), 'defaultExceptionHandler'));

set_error_handler(array(FC(), 'defaultErrorHandler'));

register_shutdown_function(array(FC(), 'defaultShutdownHandler'));