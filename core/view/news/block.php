<?
$mainTag = $main ? 'h1' : 'h3';
?>
<div class="newsBlock"<?=$main ? ' style="padding-top:0"' : ''?>>
  <? if ($news) { ?>
  <<?=$mainTag?>>
    <? if ( ! $main) { ?><a href="/news"><? } ?>Новости<? if ( ! $main) { ?></a><? } ?>
  </<?=$mainTag?>>
  <div class="newsItems">
    <? foreach ($news as $new) { ?>
    <div class="newsItem block">
      <h4><a href="/news/<?=$new->link.'-'.$new->id?>"><?=$new->name?></a><span class="date"><?=$new->date?></span></h4>
      <div class="newsPreview">
        <?=$new->desc?>
      </div>
      <? if (FC()->user->is_admin) { ?>
        <span class="crudBtns"><?=$new->editBtn()?> <?=$new->delBtn()?></span>
      <? } ?>
    </div>
    <? } ?>
  </div>
  <? } ?>

  <? if (FC()->user->is_admin) { ?>
    <?=format::btn('add', 'news', null, array('caption' => 'Добавить новость'))?>
  <? } ?>
</div>