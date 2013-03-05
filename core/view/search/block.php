<div class="searchBlock block">
  <form action="/search/" class="searchForm">
    <input class="searchText" type="text" name="text" value="поиск" onfocus="this.value=='поиск' ? this.value='' : ''"/>
    <?=format::smallButton('Найти', array('class' => 'searchSubmit', 'click' => "$('.searchForm').submit()"))?>
  </form>
</div>