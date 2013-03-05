<script>
$(function(){
  $('.authWin').dialog("option", "buttons", {'Войти': function(){
    $.json('/auth/login', {
      login : $('.login').val(),
      pass  : $('.pass').val(),
    }, function(){
      document.location = '/';
    });
  }});
})
</script>
<div class="authWin window" title="Авторизация">
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
</div>