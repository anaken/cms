<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head></head>
  <body>

  <? if ($uploaded) { ?>
  <script>
  window.parent.crud.uploaded('<?=$id?>',
    [<? foreach ($uploaded as $i => $file) { ?><?=$i ? ',' : ''?>{id:'<?=$file['id']?>', file:'<?=$file['file']?>', type:'<?=$file['type']?>', name:'<?=$file['name']?>'}<? } ?>]
  );
  </script>
  <? } ?>

  <form action="/crud/upload/" method="post" enctype="multipart/form-data" id="form">
    <input onchange="this.form.submit()" id="file" type="file" name="file[]" <?=$is_multiple ? 'multiple' : ''?>/>
    <input type="hidden" name="id" value="<?=$id?>"/>
    <input type="hidden" name="is_multiple" value="<?=$is_multiple?>"/>
  </form>

  </body>
</html>