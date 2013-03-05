<? if ($text->text) { ?>
<div class="textBlock">
  <? if ($text->name) { ?><h1><?=$text->name?></h1><? } ?>
  <div class="block">
    <?=$text->text?><br clear="all"/>
  </div>
</div>
<? } ?>

<? if (FC()->user->is_admin) { ?>
<button onclick="crud.form('texts', '<?=$id?>', {<?=$link ? "defaults: {link: '{$link}'}" : ''?>})">Редактировать</button>
<? } ?>