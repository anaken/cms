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
      $configFile = APP_PATH.'/config/'.$name.'.json';
      if ( ! file_exists($configFile) && file_exists($configFile . '.dist')) {
        if ( ! @copy($configFile . '.dist', $configFile)) {
          throw new xException("Невозможно скопировать исходный файл конфигурации {$configFile}.dist => {$configFile}", 1);
        }
      }
      $config = json_decode(file_get_contents($configFile));
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
    $r = self::log('exceptions', "[".$e->getCode()."] ".(method_exists($e, 'getSystemMessage') ? $e->getSystemMessage() : '').".\nUser message: ".$e->getMessage()."\nFile: ".$e->getFile().':'.$e->getLine()."\nTrace: ".$e->getTraceAsString());
    //$this->sendErrorMail($error);
    if (ctrl()->request()->is_ajax) {
      ob_clean();
      ctrl()->_json(array('e' => ($e->getCode() ? $e->getCode() : 1), 'msg' => $e->getMessage()));
    }
    $r || die($e->getMessage());
  }

  public static function defaultErrorHandler($errno, $errstr = '', $errfile = '', $errline = '') {
    if ($errno == E_NOTICE || $errno == E_STRICT) {
      return;
    }
    $r = self::log('errors', "[".$errno."] ".$errstr."\nFile: ".$errfile.':'.$errline);
    //$this->sendErrorMail($error);
    if (ctrl()->request()->is_ajax) {
      ob_clean();
      ctrl()->_json(array('e' => 1, 'msg' => 'Системная ошибка'));
    }
    $r || die($errstr);
  }

  public static function defaultShutdownHandler() {
    $error = error_get_last();
    if ( ! $error || $error['type'] == E_NOTICE || $error['type'] == E_STRICT) {
      return;
    }
    self::log('shutdowns', print_r($error,1));
    //$this->sendErrorMail(print_r($error,1));
    if (ctrl()->request()->is_ajax) {
      //ob_clean();
      //ctrl()->_json(array('e' => 1, 'msg' => $error['message']));
    }
  }

  public static function sendErrorMail($error) {
    @mail(FC()->config()->adminEmail, 'Ошибка на сайте '.$_SERVER['HTTP_HOST'], $error);
  }

  public static function log($type, $message) {
    return file_put_contents(APP_PATH."/logs/{$type}.log", date('Y-m-d H:i:s').": {$message}\n\n", FILE_APPEND);
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

set_exception_handler(array('FC', 'defaultExceptionHandler'));

set_error_handler(array('FC', 'defaultErrorHandler'));

register_shutdown_function(array('FC', 'defaultShutdownHandler'));