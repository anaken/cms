<div class="crudReportWin window crudReport<?=$tableName?>" title="<?=$table->caption?>">
  <table class="crudReport">
    <thead>
      <tr>
      <? foreach ($table->fields as $fieldName => $field) { if ($field->hidden || @$field->format->type == 'editor') continue ?>
        <th>
          <?=$field->caption?>
        </th>
      <? } ?>
      <? if ($editable) { ?>
        <th>Изменение</th>
      <? } ?>
      </tr>
    </thead>
    <tbody>
      <? foreach ($objects as $k => $object) { ?>
        <tr data-id="<?=$object->id?>" class="crudReportItem<?=$k % 2 == 0 ? ' trEven' : ''?>">
        <? foreach ($table->fields as $fieldName => $field) { if ($field->hidden || @$field->format->type == 'editor') continue ?>
        <?
        $params = array();
        $is_image = $field->format && $field->format->type == 'image';
        if ($is_image) {
          $params['width'] = $params['height'] = 50;
        }
        $out = format::out($fieldName, $object, $params);
        ?>
          <td<?=$is_image ? ' align="center"' : ''?>>
            <?=$out != '' ? $out : '&nbsp;'?>
          </td>
        <? } ?>
        <? if ($editable) { ?>
          <td align="center"><span class="crudBtns"><?=$object->table()->childs() ? '<div class="crudChildsPlace">'.$object->childsBtn().'</div>' : ''?> <?=$object->editBtn()?> <?=$object->delBtn()?></span></td>
        <? } ?>
        </tr>
      <? } ?>
    </tbody>
  </table>
  <? if ($editable && ( ! $table->system || ( ! isset($table->addable) || $table->addable))) { ?>
    <p><center><?=format::btn('add', $tableName, null, array('defaults' => $defaults))?></center></p>
  <? } ?>
</div>