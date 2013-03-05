<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <title><?=view::title()?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="icon" type="image/png" href="/favicon.png" />
    <link href="/css/style.css?1" rel="stylesheet" type="text/css"/>
    <link href="/css/jquery.ui/smoothness/jquery-ui-1.10.0.custom.min.css" rel="stylesheet" type="text/css"/>
    <script src="/js/jquery.min.js"></script>
    <script src="/js/jquery.ui.min.js"></script>
    <script src="/js/jquery.cookie.js"></script>
    <script src="/js/custom.js"></script>
    <?=view::outJs()?>
    <?=view::outCss()?>
  </head>
  <body>

  <div class="page">
    <table class="layout_table" cellspacing="0" cellpadding="0">
      <tr>
        <td rowspan="4" class="laybgl">&nbsp;</td>
        <td colspan="2" class="header">
          <div class="phones">
            Телефон<br/>
            <span><?=ctrl::call('index.string', 2)?></span>
          </div>

          <a href="/" class="logo">
            <img alt="Sweet Dream" src="/img/logo.gif"/>
            <span class="dsc">Интернет-магазин текстиля для дома г. Курган</span>
          </a>

          <div class="cartBlock block">
          <?=ctrl::call('index.cartBlock')?>
          </div>

          <? if (FC()->user->id) { ?>
          <?=ctrl::call('auth.logoutForm')?>
          <? } ?>
        </td>
        <td rowspan="4" class="laybgr">&nbsp;</td>
      </tr>
      <tr>
        <td colspan="2" class="menu-row">
          <?=ctrl::call('index.menu')?>
        </td>
      </tr>
      <tr>
        <td class="left-side">
          <?=ctrl::call('index.rubricCol')?>
          <br clear="all"/>
          <?=ctrl::call('search.block')?>
          <br clear="all"/>
          <?=ctrl::call('index.hit')?>
        </td>
        <td class="body">
          <?=$call?>
          <br clear="all"/>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="footer">
          <div class="footer-body">
            <?=ctrl::call('index.string', 3)?>

            <div class="counters">


            </div>
          </div>

        </td>
      </tr>
    </table>
  </div>

  </body>
</html>