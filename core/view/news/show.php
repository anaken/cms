<div class="newsShow">
  <div class="breadCrumbs">
    <a href="/news/">новости</a> / <h1><?=$new->name?></h1>
  </div>
  <div class="date"><?=$new->date?></div>
  <div class="newsItem block">
    <div class="newsText">
      <?=$new->text?>
      <br clear="all"/>
    </div>
  </div>

  <? if (FC()->user->is_admin) { ?>
  <button onclick="crud.form('news', <?=$new->id?>)">Редактировать</button>
  <button onclick="crud.del('news', <?=$new->id?>)">Удалить</button>
  <? } ?>
</div>