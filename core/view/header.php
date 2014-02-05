<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?=view::title()?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="keywords" content="">
  <meta name="description" content="">
  <!-- link rel="icon" type="image/png" href="/favicon.png" / -->
  <link href="/css/style.css?4" rel="stylesheet" type="text/css"/>
  <link href="/css/jquery.ui/smoothness/jquery-ui-1.10.0.custom.min.css" rel="stylesheet" type="text/css"/>
  <script src="/js/jquery.min.js"></script>
  <script src="/js/jquery.ui.min.js"></script>
  <script src="/js/jquery.cookie.js"></script>
  <script src="/js/jquery.dialog.fullscreen.js"></script>
  <script src="/js/custom.js?1"></script>
  <?=view::outJs()?>
  <?=view::outCss()?>
</head>
<body>

<? if (FC()->user->id) { ?>
<div class="mwcmsService">
  <?=ctrl::call('crud.menu')?>
  <?=ctrl::call('auth.logoutForm')?>
</div>
<? } ?>