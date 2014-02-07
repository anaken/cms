<?

class authCtrl extends ctrl {

  const SALT = 'lol';

  const ERROR_USER_NOT_FOUND = 6011;

  function index() {
    $call = ctrl::call('index');
    return $call->result . $this->view->render('auth/index');
  }

  function login() {
    $login = $this->post('login');
    $pass = $this->post('pass');
    try {
      if ( ! $login || ! $pass) {
        throw new xException("Не указаны необходимые параметры", self::ERROR_PARAMS);
      }
      $user = array_shift(model('users')->get(array(
        'login' => $login,
        'pass'  => md5($pass.self::SALT)
      )));
      if ( ! $user) {
        throw new xException("Пользователь не найден", self::ERROR_USER_NOT_FOUND, "Пользователь не найден");
      }
      $_SESSION['user'] = $user->data();
      $this->_json(array('e' => 0));
    }
    catch (Exception $e) {
      $this->_json(array('e' => 1, 'msg' => $e->getMessage()));
    }
  }

  function logoutForm() {
    return '<div class="logoutButton"><button onclick="document.location=\'/auth/logout/\'">Выйти</button></div>';
  }

  function logout() {
    unset($_SESSION['user']);
    header('Location: /');
    exit;
  }

}
