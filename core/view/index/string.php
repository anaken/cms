<? if ($text) { ?>
  <?=$text->text?>
<? } ?>

<? if (FC()->user->is_admin) { ?>
<button class="objectEditBtn" onclick="crud.form('texts'<?=$id ? ','.$id : ''?>)">Редактировать</button>
<? } ?>