<div class="crudFormWin window crudForm<?=$tableName?>" title="<?=$table->caption?>">
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
    $uniqueid = md5(rand());
    ?>
    <? if (@$field->hidden) continue ?>
      <tr>
        <th><label for="cf_<?=$uniqueid?>"><?=$field->caption?></label></th>
      <? if ($field->format && $field->format->type == 'editor') { ?>
        <td></td>
      </tr>
      <tr>
        <td colspan="2"><?=format::in($field, $object, $params)?></td>
      </tr>
      <? } else { ?>
        <td id="cf_<?=$uniqueid?>"><?=format::in($field, $object, $params)?></td>
      </tr>
      <? } ?>
    <? } ?>
  </table>
</div>