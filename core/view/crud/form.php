<div class="crudFormWin window crudForm<?=$tableName?>">
  <table class="crudFormTable">
    <tr class="hide">
      <td>
        <input type="hidden" name="_table_" value="<?=$tableName?>"/>
        <input type="hidden" name="_<?=$table->id?>_" value="<?=$id?>"/>
      </td>
    </tr>
    <? foreach ($table->fields as $fieldName => $field) { ?>
    <? 
    $params = array();
    if (isset($defaults[$field->name])) {
      $params['value'] = $defaults[$field->name];
    }
    ?>
    <? if (@$field->hidden) continue ?>
      <? if ($field->format && $field->format->type == 'editor') { ?>
      <tr>
        <th><?=$field->caption?></th>
        <td></td>
      </tr>
      <tr>
        <td colspan="2"><?=format::in($field, $object, $params)?></td>
      </tr>
      <? } else { ?>
      <tr>
        <th><?=$field->caption?></th>
        <td><?=format::in($field, $object, $params)?></td>
      </tr>
      <? } ?>
    <? } ?>
  </table>
</div>