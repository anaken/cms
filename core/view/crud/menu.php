<div class="crudModules">
  <button class="showCrudMenu">Модули</button>
  <div class="crudMenu">
    <ul>
      <? foreach ($tables as $tableName => $table) { if ($table->system) continue ?>
        <li><a href="#" onclick="crud.report('<?=$tableName?>', {edit: 1, order: 'id desc', limit: 50});return false"><?=$table->caption?></a></li>
      <? } ?>
    </ul>
  </div>
</div>