<?
$itemsPerRow = 3;
?>
<h1>Корзина</h1>

<div class="cartPage">
  <div class="goodsBlock">
  <? if ($goods) { ?>
    <? foreach ($goods as $k => $good) { ?>
      <div class="good block goodItem<?=$good->id?>">

        <h4><a href="/good/<?=$good->link.'-'.$good->id?>"><?=$good->name?></a></h4>

        <? if ($good->image_id) { ?>
        <div class="goodImage">
          <? if ($good->not_available) { ?><span class="goodNotAvailable">Нет в наличии</span><? } ?>
          <a href="/good/<?=$good->link.'-'.$good->id?>"><img src="<?=$good->image()->thumb()?>" alt="<?=$good->name?>"/></a>
        </div>
        <? } ?>

        <? if ($good->price) { ?>
        <span class="goodPrice"><?=$good->price?>р.</span>
        <? } ?>

        <?=format::smallButton('Убрать', array('tag' => 'span', 'class' => "buyGoods buyGoodsDel{$good->id}", 'click' => "cart.del({$good->id}, {callback:function(){ $('.goodItem{$good->id}').remove(); }})"))?>

      </div>
      <? if (($k + 1) % $itemsPerRow == 0) { ?>
      <br clear="all"/>
      <? } ?>
    <? } ?>

    <br clear="all"/>
    <?=format::smallButton('Оформить заказ', array('tag' => 'a', 'class' => 'checkoutOrder', 'href' => '/order'))?>

  <? } else { ?>
  В корзине нет товаров
  <? } ?>
  </div>
</div>
<br clear="all"/>

<? if (FC()->user->is_admin) { ?>
<script>
function orderDetails() {
  $('.crudReportorders .crudReportItem').live('click', function(){
    crud.report('order_goods', {params: { order_id: $(this).attr('data-id') }, order: 'id'});
  });
}
</script>
<button onclick="crud.report('orders', {order: 'id desc', limit: 50}, function(){ orderDetails(); })">Последние заказы</button>
<? } ?>