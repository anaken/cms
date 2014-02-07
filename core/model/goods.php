<?

class xGoods extends model {

  protected $objectClass = 'modelGoodsObject';

  function delGoods($id, $params = array()) {
    $good = model('goods')->get($id);
    if ($good->image_id) {
      model('images')->del($good->image_id);
    }
    return model('goods')->force()->del($id, $params);
  }
  
}

class modelGoodsObject extends modelObject {

  function link() {
    if ( ! ($rubric = $this->rubric_id())) {
      return $this->link;
    }
    return $rubric->link() . '/' . $this->link . '-' . $this->id;
  }

}