<?

class xFiles extends model {

  const ERROR_DIR_NOT_FOUND = 5021;

  public static function getFilesDir($full = false) {
    $path = '/img/user';
    if ( ! $full) {
      return $path;
    }
    $fullPath = APP_PATH.'/..'.$path;
    $realPath = realpath($fullPath);
    if ( ! $realPath) {
      throw new xException('Path for upload files not found in '.$fullPath, self::ERROR_DIR_NOT_FOUND);
    }
    return $realPath;
  }

  protected $objectClass = 'modelFilesObject';

  function delFiles($id, $params = array()) {
    $dir = self::getFilesDir().'/'.$id.'/';
    funcs::removeDir($dir);
    return $this->force()->del($id, $params);
  }
  
}

class modelFilesObject extends modelObject {

  function thumb($width, $height, $type = IMAGETYPE_JPEG) {
    $name = $width . 'x' . $height . 'x' . $type;
    $fileDir = '/'.$this->id.'/thumb/';
    if ( ! file_exists(xFiles::getFilesDir(1).$fileDir)) {
      mkdir(xFiles::getFilesDir(1).$fileDir, 0755, true);
    }
    $file = $fileDir.$name.($this->ext ? '.'.$this->ext : '');
    if ( ! file_exists(xFiles::getFilesDir(1).$file)) {
      funcs::imageThumb($this->path(1), xFiles::getFilesDir(1).$file, $width, $height, $type, false);
    }
    return xFiles::getFilesDir().$file;
  }

  function path($full = false) {
    return xFiles::getFilesDir($full).'/'.$this->id.'/src'.($this->ext ? '.'.$this->ext : '');
  }

}