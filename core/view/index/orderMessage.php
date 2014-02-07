<style>
.goodTable td {
  padding: 0 5px
}
.orderTable caption {
  font-weight: bold;
}
</style>
<table class="orderTable">
  <caption>Заказ</caption>
  <tr>
    <th align="left">Ф.И.О.</th>
    <td><?=$name?></td>
  </tr>
  <tr>
    <th align="left">Телефон</th>
    <td><?=$phone?></td>
  </tr>
  <tr>
    <th align="left">Дополнительно</th>
    <td><?=$desc?></td>
  </tr>
  <tr>
    <td colspan="2">
      <table class="goodTable">
        <caption>Заказанные товары</caption>
        <tr>
          <th>Наименование</th>
          <th>Количество</th>
          <th>Цена</th>
          <th>Сумма</th>
        </tr>
        <? $sum = 0 ?>
        <? foreach ($goods as $good) { ?>
        <tr>
          <td><?=$good->name?></td>
          <td align="center"><?=$_COOKIE['goods'][$good->id]?></td>
          <td><?=$good->price?></td>
          <td><?=$good->price * $_COOKIE['goods'][$good->id]?></td>
          <? $sum += $good->price * $_COOKIE['goods'][$good->id] ?>
        </tr>
        <? } ?>
        <tr>
          <th align="left" colspan="3">Итого</th>
          <th><?=$sum?></th>
        </tr>
      </table>
    </td>
  </tr>
  
</table>