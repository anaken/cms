<?

class xImages extends model {

  protected $objectClass = 'modelImagesObject';

  function delImages($id, $params = array()) {
    $dir = APP_PATH.'/../img/user/'.$id.'/';
    funcs::removeDir($dir);
    return model()->images->force()->del($id, $params);
  }
  
}

class modelImagesObject extends modelObject {

  function thumb($width, $height, $type = IMAGETYPE_JPEG) {
    $name = $width . 'x' . $height . 'x' . $type;
    $dir = '/img/user/'.$this->id.'/thumb/';
    if ( ! file_exists(APP_PATH.'/..'.$dir)) {
      mkdir(APP_PATH.'/..'.$dir, 0755, true);
    }
    $file = $dir.$name.($this->ext ? '.'.$this->ext : '');
    if ( ! file_exists(APP_PATH.'/..'.$file)) {
      funcs::imageThumb(APP_PATH.'/..'.$this->src(), APP_PATH.'/..'.$file, $width, $height, $type, false);
    }
    return $file;
  }

  function src() {
    return '/img/user/'.$this->id.'/src'.($this->ext ? '.'.$this->ext : '');
  }

}