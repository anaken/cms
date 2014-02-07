<?

class funcs
{

  /**
   * Обрезка фотки под нужный размер
   *
   * @param string $from
   * @param string $to
   * @param int $dst_w
   * @param int $dst_h
   * @param string $dst_type
   * @param bool $fit
   * @param bool $crop
   * @return bool
   */
  public static function imageThumb($from, $to, $dst_w, $dst_h, $dst_type, $fit = true, $crop = false)
  {
    $res = getimagesize($from);
    if (!$res) return trigger_error($from) && false;

    list ($src_w, $src_h, $src_type) = $res;

    $src_x = 0;
    $src_y = 0;

    if ($fit) {
      if (($src_w / $dst_w) > ($src_h / $dst_h))
        $dst_h = round($src_h * ($dst_w / $src_w));
      else
        $dst_w = round($src_w * ($dst_h / $src_h));
    } else {
      if (($src_w / $dst_w) > ($src_h / $dst_h)) {
        $w = round($dst_w * ($src_h / $dst_h));
        $src_x = round(($src_w - $w) / 2);
        $src_w = $w;
      } else {
        $h = round($dst_h * ($src_w / $dst_w));
        $src_y = round(($src_h - $h) / 2);
        $src_h = $h;
      }
    }

    $src_image = ($src_type == IMAGETYPE_PNG) ? imagecreatefrompng($from) :
      (($src_type == IMAGETYPE_GIF) ? imagecreatefromgif($from) :
        imagecreatefromjpeg($from));

    if (!is_resource($src_image)) return trigger_error(__FUNCTION__) && false;

    // create the target image
    if (!is_resource($dst_image = (function_exists('imagecreatetruecolor') ? imagecreatetruecolor($dst_w, $dst_h) : imagecreate($dst_w, $dst_h))))
      return trigger_error(__FUNCTION__) && false;

    // do the scaling
    if (!(function_exists('imagecopyresampled') ?
      imagecopyresampled($dst_image, $src_image, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) :
      imagecopyresized($dst_image, $src_image, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h))
    )
      trigger_error(__FUNCTION__);

    // free memory taken by the source image
    imagedestroy($src_image);

    // determine output function
    // check out http://php.net/imagejpeg and http://php.net/imagepng to control quality

    switch ($dst_type) {
      case IMAGETYPE_PNG:
        $res = imagepng($dst_image, $to);
        break;
      case IMAGETYPE_GIF:
        $res = imagegif($dst_image, $to);
        break;
      case IMAGETYPE_JPEG:
        $res = imagejpeg($dst_image, $to, 90);
        break;
      default:
        trigger_error(__FUNCTION__);
        return false;
    }

    // output image (to a browser or file)
    if (!$res) return trigger_error(__FUNCTION__) && false;

    imagedestroy($dst_image);
    return true;
  }

  /**
   * Установка водяного знака на фотку
   *
   * @param string $file
   * @param string $watermarkImageFile
   */
  public static function setImageWaterMark($file, $watermarkImageFile)
  {
    $src_size = getimagesize($file);
    $src_width = $src_size[0];
    $src_height = $src_size[1];
    $src_mime = $src_size['mime'];

    $WATERMARK_FILE = $watermarkImageFile;
    $watermark_size = getimagesize($WATERMARK_FILE);
    $watermark_width = $watermark_size[0];
    $watermark_height = $watermark_size[1];
    $watermark_mime = $watermark_size['mime'];

    if ($src_mime == 'image/jpeg') {
      $icfunc = "imagecreatefromjpeg";
      $viewfunc = "imagejpeg";
      $ext = '.jpg';
    }
    if ($src_mime == 'image/gif') {
      $icfunc = "imagecreatefromgif";
      $viewfunc = "imagegif";
      $ext = '.gif';
    }
    if ($src_mime == 'image/png') {
      $icfunc = "imagecreatefrompng";
      $viewfunc = "imagepng";
      $ext = '.png';
    }

    $h_img_src = $icfunc($file);
    $h_img_dest = imagecreatetruecolor($src_width, $src_height);

    imagecopyresampled($h_img_dest, $h_img_src, 0, 0, 0, 0, $src_width, $src_height, $src_width, $src_height);

    if ($src_width >= 100) {
      $watermark_offset_x = $src_width - $watermark_width - 10;
      $watermark_offset_y = 10;
      $watermark = imagecreatefrompng($WATERMARK_FILE);

      // левый верхний угол
      $offset_x = $watermark_offset_x;
      $offset_y = $watermark_offset_y;

      imagecopy($h_img_dest, $watermark, $offset_x, $offset_y, 0, 0, $watermark_width, $watermark_height);
    }

    $viewfunc($h_img_dest, "{$file}");
    imagedestroy($h_img_dest);
    imagedestroy($h_img_src);
  }

  public static function translite($str)
  {
    $tr = array(
      "А" => "A", "Б" => "B", "В" => "V", "Г" => "G",
      "Д" => "D", "Е" => "E", "Ж" => "J", "З" => "Z", "И" => "I",
      "Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N",
      "О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T",
      "У" => "U", "Ф" => "F", "Х" => "H", "Ц" => "TS", "Ч" => "CH",
      "Ш" => "SH", "Щ" => "SCH", "Ъ" => "", "Ы" => "YI", "Ь" => "",
      "Э" => "E", "Ю" => "YU", "Я" => "YA", "а" => "a", "б" => "b",
      "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "j",
      "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l",
      "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
      "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h",
      "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y",
      "ы" => "yi", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya"
    );
    return strtr($str, $tr);
  }

  public static function url($str)
  {
    $str = self::translite($str);
    $str = preg_replace('/[^a-z0-9\-]/i', '-', $str);
    $str = preg_replace('/\.\,\-\!\:\+ /', '-', $str);
    $str = preg_replace('/-+/', '-', $str);
    return strtolower($str);
  }

  public static function varToLog($var)
  {
    if (is_array($var)) {
      $arr = array();
      foreach ($var as $key => $val) {
        $arr[] = "[$key] => $val";
      }
      $var = implode(' | ', $arr);
    }
    return str_replace("\n", ' ', print_r($var, 1));
  }

  public static function removeDir($dir)
  {
    if ( ! is_readable($dir)) {
      return;
    }
    $list = @scandir($dir);
    unset($list[0], $list[1]);

    foreach ($list as $file) {
      if (is_dir($dir . $file)) {
        self::removeDir($dir . $file . '/');
        rmdir($dir . $file);
      } else {
        unlink($dir . $file);
      }
    }
  }

}

/**
 * Функция применяющая пользовательскую функцию к ключам массива
 *
 * @author Yury Korobeynikov
 * @param  callback $callback - функция для обработки ключей
 * @param  array $arr - массив параметров
 * @return mixed
 */
if (!function_exists('array_keys_map')) {
  function array_keys_map($callback, $arr)
  {
    return array_combine(
      array_map(
        $callback,
        array_keys($arr)
      ),
      $arr
    );
  }
}

/**
 * Получить массив содержащий значения подмассива по указанному ключу
 *
 * @author Yury Korobeynikov
 * @param  array $params - массив параметров
 * @return mixed
 */
if (!function_exists('array_key_values')) {
  function array_key_values($arr, $key)
  {
    return array_map(
      create_function('$i', 'return is_object($i) ? $i->' . $key . ' : $i["' . $key . '"];'),
      $arr
    );
  }
}

/**
 * Установить ключи массива по одному из ключей подмассива
 *
 * @author Yury Korobeynikov
 * @param  array $arr - массив для обработки
 * @param  string $key - ключ подмассива
 * @return mixed
 */
if (!function_exists('array_by_key')) {
  function array_by_key($arr, $key)
  {
    if (!$arr) {
      return $arr;
    }
    return array_combine(
      array_key_values($arr, $key),
      $arr
    );
  }
}

/**
 * Извлечь указанный по ключу элемент из массива и вернуть его
 *
 * @author Yury Korobeynikov
 * @param  array $arr - массив для выборки
 * @param  string $key - ключ
 * @return mixed
 */
if (!function_exists('array_shift_key')) {
  function array_shift_key(&$arr, $key)
  {
    $result = @$arr[$key];
    unset($arr[$key]);
    return $result;
  }
}

/**
 * Возвращает ассоциативный массив со значениями из массива arr1,
 * которые различаются или отсутствуют в массиве arr2
 * сравнение происходит по соответствующим ключам
 *
 * @author Yury Korobeynikov
 * @param  array $arr1
 * @param  array $arr2
 * @return array
 */
if (!function_exists('array_key_diff')) {
  function array_key_diff($arr1, $arr2)
  {
    $c = array();
    foreach ($arr1 as $k => $v) {
      if (isset($arr2[$k]) && $arr2[$k] == $v) {
        continue;
      }
      $c[$k] = $v;
    }
    return $c;
  }
}