<div class="rubricsBlock block">
<? if ($rubrics) { ?>
  <table class="rubrics">
  <tbody<? if (FC()->user->is_admin) { ?> class="crudListSortable" crud-object="rubrics"<? } ?>>
  <? foreach ($rubrics as $rubric) { ?>
    <tr<? if (FC()->user->is_admin) { ?> crud-id="<?=$rubric->id?>"<? } ?>>
      <td>
        <a<?=$active && ($active->id == $rubric->id || $active->parent_id == $rubric->id) ? ' class="active"' : ''?> href="/catalog/<?=$rubric->link.'-'.$rubric->id?>"><?=$rubric->name?></a>
        <? if (FC()->user->is_admin) { ?>
        <span class="crudBtns"><?=$rubric->editBtn()?> <?=$rubric->delBtn()?></span>
        <? } ?>
        <? if ($subrubrics && $active->id == $rubric->id) { ?>
          <ul type="circle" class="subrubrics crudListSortable"<? if (FC()->user->is_admin) { ?> crud-object="rubrics"<? } ?>>
          <? foreach ($subrubrics as $subrubric) { ?>
            <li<? if (FC()->user->is_admin) { ?> crud-id="<?=$subrubric->id?>"<? } ?>>
              <a<?=$selected && $selected->id == $subrubric->id ? ' class="active"' : ''?> href="/catalog/<?=$subrubric->id?>"><?=$subrubric->name?></a>
              <? if (FC()->user->is_admin) { ?>
              <span class="crudBtns"><?=$subrubric->editBtn()?> <?=$subrubric->delBtn()?></span>
              <? } ?>
            </li>
          <? } ?>
          </ul>
        <? } ?>
      </td>
    </tr>
  <? } ?>
  </tbody>
  </table>
<? } ?>

<? if (FC()->user->is_admin) { ?>
<button onclick="crud.form('rubrics')">Добавить группу товара</button>
<? } ?>
</div>