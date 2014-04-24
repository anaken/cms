<? if ($text) { ?>
  <?=$text->text?>
<? } ?>

<? if (FC()->user->is_admin) { ?>
<?=format::btn('edit', 'texts', $id, array('type' => format::BUTTON_TYPE_SMALL))?>
<? } ?>