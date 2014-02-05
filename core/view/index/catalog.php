<?
$manufacturers = $rubric->manufacturers();
?>
<script>
$(function(){
  $('.rubricManufacturers :checkbox').change(function(){
    $('.rubricGoodsList').pull('/catalogGoods/', $('.rubricManufacturers').inputs());
  });
});
</script>
<h1><?=$rubric->name?></h1>

<? if ($rubric->description) { ?>
<div class="rubricDescription">
  <?=$rubric->description?>
</div>
<? } ?>

<? if ($manufacturers) { ?>
<div class="rubricManufacturers">
  <input type="hidden" name="rubric_id" value="<?=$rubric->id?>"/>
  <? foreach ($manufacturers as $manufacturer) { ?>
  <div class="manufacturerItem">
    <label>
      <input name="manufacturers[<?=$manufacturer->id?>]" type="checkbox"/>
      <?=$manufacturer->name?>
    </label>
  </div>
  <? } ?>
  <br clear="all"/>
</div>
<? } ?>

<div class="rubricGoodsList">
<?=$goodsList?>
</div>

<? if (FC()->user->is_admin) { ?>
<?=format::btn('add', 'goods', null, array('caption' => 'Добавить товар', 'defaults' => array('rubric_id' => $rubric->id)))?>
<? } ?>