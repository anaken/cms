<?
$itemsPerRow = 3;
?>
<div class="goodsBlock">
<? if ($goods) { ?>
  <? foreach ($goods as $k => $good) { ?>
    <div class="good block">

      <h4><a href="/good/<?=$good->link.'-'.$good->id?>"><?=$good->name?></a></h4>

      <? if ($image = current($good->images())) { ?>
      <div class="goodImage">
        <a href="/good/<?=$good->link.'-'.$good->id?>">
          <? if ($good->not_available) { ?><span class="goodNotAvailable">Нет в наличии</span><? } ?>
          <img src="<?=$image->thumb(230, 230)?>" alt="<?=$good->name?>"/>
        </a>
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
    <? if (($k + 1) % $itemsPerRow == 0) { ?>
    <br clear="all"/>
    <? } ?>
  <? } ?>
<? } ?>
</div>
<br clear="all"/>