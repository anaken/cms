<?

class xGoods extends model {

  protected $objectClass = 'modelGoodsObject';

  function delGoods($id, $params = array()) {
    $good = model()->goods->get($id);
    if ($good->image_id) {
      model()->images->del($good->image_id);
    }
    return model()->goods->force()->del($id, $params);
  }
  
}

class modelGoodsObject extends modelObject {
  
  function image() {
    if ( ! $this->image_id) {
      return null;
    }
    return model()->images->get($this->image_id);
  }

}