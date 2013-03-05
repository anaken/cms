<div class="crudReportWin window crudReport<?=$tableName?>">
  <table class="crudReport" cellpadding="0" cellspacing="0">
    <caption><?=$table->caption?></caption>
    <thead>
      <tr>
      <? foreach ($table->fields as $fieldName => $field) { if ($field->hidden) continue ?>
        <th>
          <?=$field->caption?>
        </th>
      <? } ?>
      </tr>
    </thead>
    <tbody>
      <? foreach ($objects as $k => $object) { ?>
        <tr data-id="<?=$object->id?>" class="crudReportItem<?=$k % 2 == 0 ? ' trEven' : ''?>">
        <? foreach ($table->fields as $fieldName => $field) { if ($field->hidden) continue ?>
          <td>
            <?=format::out($fieldName, $object)?>
          </td>
        <? } ?>
        </tr>
      <? } ?>
    </tbody>
  </table>
</div>