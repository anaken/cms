<h1>Оформление заказа</h1>
<? if ($error) { ?>
<p class="errorMsg"><?=$error?></p>
<? } ?>
<form method="post" id="orderForm">
<table class="orderForm">
  <tr>
    <th>Ф.И.О.:</th>
    <td><input type="text" name="name" value="<?=$name?>"/></td>
  </tr>
  <tr>
    <th>Телефон:</th>
    <td><input type="text" name="phone" value="<?=$phone?>"/></td>
  </tr>
  <tr>
    <th>Дополнительно:</th>
    <td><textarea name="desc"><?=$desc?></textarea></td>
  </tr>
  <tr class="sendOrderRow">
    <td colspan="2" align="center">
      <?=format::smallButton('Отправить', array('tag' => 'span', 'class' => 'sendOrder', 'click' => "$('#orderForm').submit()"))?>
    </td>
  </tr>
</table>
</form>