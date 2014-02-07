<script>
  $(function(){
    $('.authWin').dialog({"buttons": {'Войти': function(){ authEnter(); }}, resizable: false});
  });
  function authEnter() {
    $.json('/auth/login', {
      login : $('.login').val(),
      pass  : $('.pass').val()
    }, function(){
      document.location = '/';
    });
    return false;
  }
</script>
<div class="authWin window" title="Авторизация">
  <form onsubmit="return authEnter()">
    <input type="submit"/>
    <table class="authForm">
      <tr>
        <th>Логин</th>
        <td><input type="text" name="login" class="login"/></td>
      </tr>
      <tr>
        <th>Пароль</th>
        <td><input type="password" name="pass" class="pass"/></td>
      </tr>
    </table>
  </form>
</div>