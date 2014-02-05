<div class="menu">
<? if ($items) { ?>
  <div<? if (FC()->user->is_admin) { ?> class="crudListSortable" crud-object="menu"<? } ?>>
    <? foreach (array_reverse($items) as $item) { ?>
    <div class="menuItem"<? if (FC()->user->is_admin) { ?> crud-id="<?=$item->id?>"<? } ?>>
    <?=format::button($item->name, array('tag' => 'a', 'href' => $item->link))?>
      <? if (FC()->user->is_admin) { ?>
        <span class="crudBtns"><?=$item->editBtn()?> <?=$item->delBtn()?></span>
      <? } ?>
    </div>
    <? } ?>
  </div>
<? } ?>

<? if (FC()->user->is_admin) { ?>
  <?=format::btn('add', 'menu', null, array('caption' => 'Добавить пункт меню'))?>
<? } ?>

</div>