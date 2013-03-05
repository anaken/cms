<div class="searchIndex">
  <h1>Поиск</h1>
  <form class="searchForm">
    <input class="searchText" type="text" name="text" value="<?=$text?>"/>
    <?=format::smallButton('Найти', array('class' => 'searchSubmit', 'click' => "$('.searchForm').submit()"))?>
  </form><br clear="all"/>
  <? if ($objects) { ?>
    <table class="searchItems">
    <? foreach ($objects as $k => $object) { if ( ! $object->name) continue ?>
      <tr class="searchItem">
        <td class="searchIcon search<?=ucfirst($object->table)?>"><?=$object->caption?></td>
        <td><a href="<?=$object->link?>"><?=$object->name?></a></td>
      </tr>
    <? } ?>
    </table>
  <? } else { ?>
  <p>Ничего не найдено</p>
  <? } ?>
</div>

<? if (FC()->user->is_admin) { ?>
<button onclick="crud.report('searches', {order: 'id desc', limit: 50})">Последние поисковые запросы</button>
<? } ?>