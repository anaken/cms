<? require_once(dirname(__FILE__).'/header.php') ?>

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

<? require_once(dirname(__FILE__).'/footer.php') ?>