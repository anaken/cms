<? if ($text->text) { ?>
<div class="textBlock">
  <? if ($text->name) { ?><h1><?=$text->name?></h1><? } ?>
  <div class="block">
    <?=$text->text?>
  </div>
</div>
<? } ?>

<? if ($id == 23) {
/*
foreach (model()->rubrics->get(null) as $rubric) {
  model()->rubrics->save($rubric->id, $rubric->data());
}
*/

/*
$tables = FC()->config('tables');
foreach ($tables as $table => $cfg) {
  FC()->db->query('SET NAMES latin1');
  $items = model()->$table->get(null);
  FC()->db->query('SET NAMES UTF8');
  foreach ($items as $item) {
    model()->$table->save($item->id, $item->data());
  }
}
*/
} ?>

<? if (FC()->user->is_admin) { ?>
<button onclick="crud.form('texts', '<?=$id?>', {<?=$link ? "defaults: {link: '{$link}'}" : ''?>})">Редактировать</button>
<? } ?>