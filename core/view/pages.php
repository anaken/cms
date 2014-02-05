<div class="pages">
<?

$result = array();
if ($pages > 1) {
  $dots = false;
  for ($i = 1; $i <= $pages; $i++) {
    if (abs($i - 1) == 0 || abs($i - $pages) == 0 || abs($i - $page) < 5) {
      if ($dots) {
        $dots = false;
        $result[] = '...';
      }
      $tag = $href ? 'a' : 'span';
      $hrefParam = $href ? ' href="'.$href.(substr_count($href, '?') ? '&' : '?').'p='.$i.'"' : '';
      $result[] = '<'.$tag.$hrefParam.' class="smallBtn' . ($i == $page ? ' currentPage' : '') . '">' . $i . '</'.$tag.'>';
    } else {
      $dots = true;
    }
  }
}

echo implode("\n", $result);

?>
</div>
