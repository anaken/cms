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
  <button class="objectAddBtn" onclick="crud.form('menu')">Добавить пункт меню</button>
<? } ?>

</div>