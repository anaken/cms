<? if ($good) { ?>
<div class="hit block">

  <h3>Хит продаж</h3>

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

  <?=format::smallButton('Купить', array('tag' => 'span', 'class' => "buyGoods buyGoodsAdd{$good->id}".(isset($_COOKIE['goods'][$good->id]) ? ' hide' : ''), 'click' => "cart.add({$good->id}, 1)"))?>
  <?=format::smallButton('Убрать из корзины', array('tag' => 'span', 'class' => "buyGoods buyGoodsDel{$good->id}".( ! isset($_COOKIE['goods'][$good->id]) ? ' hide' : ''), 'click' => "cart.del({$good->id})"))?>

  <? if (FC()->user->is_admin) { ?>
    <span class="crudBtns"><?=$good->editBtn()?> <?=$good->delBtn()?></span>
  <? } ?>

</div>
<? } ?>