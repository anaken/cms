<?

class view {

  const ERROR_TEMPLATE_NOT_FOUND = 5501;

  /**
   * @var array
   */
  private $params = array();

  protected static $js = array();

  protected static $css = array();

  protected static $title = 'Sweet Dream - Интернет-магазин текстиля для дома г. Курган';

  protected static $header = '';

  protected static $lastSubTitle = '';

  protected static $noHeader = false;

  protected static $libs = array(
    'fancybox' => array(
      'css' => array('jquery.fancybox'),
      'js'  => array('jquery.easing.min', 'jquery.fancybox.min'),
    ),
  );

  function set($name, $value) {
    $this->params[$name] = $value;
  }

  function render($name, $params = array()) {
    if ( ! ($file = $this->findFile($name))) {
      throw new xException("Шаблон {$name} не найден", self::ERROR_TEMPLATE_NOT_FOUND);
    }
    $params = array_merge($params, $this->params);
    extract($params);
    ob_start();
    require($file);
    return ob_get_clean();
  }

  function findFile($name) {
    $file = dirname(__FILE__).'/view/'.$name.'.php';
    return file_exists($file) ? $file : false;
  }

  public static function setNoHeader() {
    self::$noHeader = true;
  }

  public static function subTitle($name = '') {
    if ( ! trim($name)) {
      if ( self::$noHeader ) {
        return '';
      }
      return self::$lastSubTitle;
    }
    self::$lastSubTitle = $name;
    return self::$title = $name . ' - ' . self::$title;
  }

  public static function title($name = null) {
    if ($name) {
      self::$title = $name;
    }
    return self::$title;
  }

  public static function header($name = null) {
    if ($name) {
      self::$header = $name;
    }
    return (self::$header ? self::$header : self::subTitle());
  }

  public static function addJs($name) {
    $file = ($name{0} == '/' ? '' : '/js/').$name.'.js';
    if ( ! in_array($file, self::$js)) {
      self::$js[] = $file;
    }
  }

  public static function outJs() {
    if ( ! self::$js) {
      return '';
    }
    return '<script src="'.implode("\"></script>\n<script src=\"", self::$js).'"></script>';
  }

  public static function addCss($name) {
    $file = '/css/'.$name.'.css';
    if ( ! in_array($file, self::$css)) {
      self::$css[] = $file;
    }
  }

  public static function outCss() {
    if ( ! self::$css) {
      return '';
    }
    return '<link href="'.implode("\" rel=\"stylesheet\" type=\"text/css\"/>\n<link href=\"", self::$css).'" rel="stylesheet" type="text/css"/>';
  }

  public static function addLib($name) {
    if (isset(self::$libs[$name])) {
      foreach ((array)self::$libs[$name]['js'] as $js) self::addJs($js);
      foreach ((array)self::$libs[$name]['css'] as $css) self::addCss($css);
    }
  }

}
