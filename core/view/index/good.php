<div class="goodBlock">
<? if ($good) { ?>
  <div class="breadCrumbs">
    <a href="/catalog/<?=$rubric->link.'-'.$rubric->id?>"><?=$rubric->name?></a> / <h1><?=$good->name?></h1>
  </div>
  <div class="block">
    <? if ($good->image_id) { ?>
    <div class="goodImage">
      <a title="<?=$good->name?>" href="<?=$good->image_id()->src()?>" rel="fancybox"><img title="<?=$good->name?>" src="<?=$good->image_id()->thumb(392, 392)?>" alt="<?=$good->name?>"/></a>
    </div>
    <? } ?>
    <? if ($good->price) { ?>
    <span class="goodSpecField">Цена <span><?=$good->price?>р.</span></span><br/>
    <? } ?>

    <div class="buyGoodsBtn">
      <?=format::smallButton('Купить', array('tag' => 'span', 'class' => "buyGoods buyGoodsAdd{$good->id}".(isset($_COOKIE['goods'][$good->id]) ? ' hide' : ''), 'click' => "cart.add({$good->id}, 1)"))?>
      <?=format::smallButton('Убрать из корзины', array('tag' => 'span', 'class' => "buyGoods buyGoodsDel{$good->id}".( ! isset($_COOKIE['goods'][$good->id]) ? ' hide' : ''), 'click' => "cart.del({$good->id})"))?>
    </div>

    <?=$good->description?>
    <br clear="all"/>
  </div>
<? } ?>

<? if (FC()->user->is_admin) { ?>
  <?=$good->editButton()?>
  <?=$good->delButton()?>
<? } ?>
</div>