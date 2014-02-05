<?

class xRubrics extends model {

  protected $objectClass = 'modelRubricsObject';

  function delRubrics($id, $params = array()) {
    model()->goods->delList(array('rubric_id' => $id));
    return model()->rubrics->force()->del($id, $params);
  }
  
}

class modelRubricsObject extends modelObject {
  
  function manufacturers($params = array()) {
    $sql = "
      SELECT m.*
      FROM goods g
      INNER JOIN manufacturers m ON g.manufacturer_id = m.id
      WHERE g.rubric_id = {$this->id}
      GROUP BY g.manufacturer_id
    ";
    return model()->manufacturers->wrap(FC()->db->query($sql)->all());
  }

  private static $fullLink;

  function link() {
    if ( ! self::$fullLink[$this->id]) {
      $rubrics = array();
      $parent = $this;
      while ($parent->parent_id && ($parent = model()->rubrics->get($parent->parent_id))) {
        array_unshift($rubrics, $parent->link . '/');
      }
      self::$fullLink[$this->id] = '/'.implode('/', $rubrics).$this->link;
    }
    return self::$fullLink[$this->id];
  }

}