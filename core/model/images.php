<?

class xImages extends model {

  protected $objectClass = 'modelImagesObject';

  function delImages($id, $params = array()) {
    $image = model()->images->get($id);
    $file = APP_PATH.'/../img/user/'.$id.($image->ext ? '.'.$image->ext : '');
    $fileThumb = APP_PATH.'/../img/user/thumb/'.$id.($image->ext ? '.'.$image->ext : '');
    $fileThumbBig = APP_PATH.'/../img/user/thumb/big/'.$id.($image->ext ? '.'.$image->ext : '');
    @unlink($file);
    @unlink($fileThumb);
    @unlink($fileThumbBig);
    return model()->images->force()->del($id, $params);
  }
  
}

class modelImagesObject extends modelObject {
  
  function thumb($type = '') {
    return '/img/user/thumb/'.($type ? $type.'/' : '').$this->id.($this->ext ? '.'.$this->ext : '');
  }

  function src() {
    return '/img/user/'.$this->id.($this->ext ? '.'.$this->ext : '');
  }

}