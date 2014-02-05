<?

/**
 * @since 28.11.2011
 * @author korobejnikov
 */
class simpleDb
{
  var $c; // connect
  var $trans_status = true;
  var $sql;
  var $queries = array();

  const ERROR_TABLE_NOT_EXIST = 101146;

  /**
   * пример использования:
   * $db = new simpleDb($connect);
   */
  function __construct($connect)
  {
    if (is_array($connect) || is_object($connect)) {
      $this->connect((array)$connect);
    } else {
      $this->c = $connect;
    }
  }

  function __destruct()
  {
    file_put_contents(APP_PATH . '/logs/db/1.log', implode(";\n\n", $this->queries));
  }

  function connect($params)
  {
    $this->c = mysql_connect($params['host'], $params['user'], $params['pass']);
    if (@$params['db']) {
      $this->db($params['db']);
    }
  }

  function db($name)
  {
    mysql_select_db($name, $this->c);
    $this->query('SET NAMES UTF8');
  }

  function query($sql)
  {
    $this->sql = $sql;
    $r = mysql_query($this->sql, $this->c);
    $this->queries[] = $this->sql;
    if (!$r) {
      $errorCode = mysql_errno($this->c);
      file_put_contents(APP_PATH . '/logs/db.log', date('Y-m-d H:i:s: ') . "[{$errorCode}] " . mysql_error($this->c) . "\n{$this->sql}\n\n", FILE_APPEND);
      throw new xException("Db error", $errorCode + 100000);
    }
    return new simpleDbResult(array('resource' => $r, 'connect' => $this->c));
  }

  /**
   * пример использования:
   * $r = $db->select('*', 'tbl_name', array(
   *     'where' => array(
   *         'id' => 1,
   *     )
   * ));
   * while ($row = $r->next()) {
   *     // .. юзаем $row
   * }
   */
  function select($select, $table, $params = array())
  {
    $where = @$params['where'] ? "WHERE " . implode(" AND ", (array)$this->sqlWhereStatements($params['where'])) : '';
    $group = @$params['group'] ? "GROUP BY " . $params['group'] : '';
    $order = @$params['order'] ? "ORDER BY " . $params['order'] : '';
    $limit = @$params['limit'] ? "LIMIT " . $params['limit'] : '';
    $sql = "SELECT {$select} FROM {$table} {$where} {$group} {$order} {$limit}";
    return $this->query($sql);
  }

  /**
   * пример использования:
   * $r = $db->insert('tbl_name', array(
   *     'param1' => 'param1',
   * ));
   */
  function insert($table, $set, $params = array())
  {
    $sqlKeys = '`' . implode('`,`', array_keys($set)) . '`';
    $sqlValues = implode(",", $this->escape_param($set));
    $ignore = @$params['ignore'] ? ' IGNORE' : '';
    $sql = "INSERT{$ignore} INTO `{$table}` ({$sqlKeys}) VALUES ({$sqlValues})";
    $r = $this->query($sql);
    if (!$r) {
      $this->trans_status = false;
      return false;
    }
    return $r->id();
  }

  /**
   * пример использования:
   * $r = $db->update('tbl_name',
   *     array(
   *         'param1' => 'param1',
   *     ),
   *     array(
   *         'id' => 1,
   *     )
   * );
   */
  function update($table, $set, $where = array(), $params = array())
  {
    $sqlSet = implode(',', $this->sqlStatements($set));
    $sqlWhere = $where ? "WHERE " . implode(' AND ', (array)$this->sqlWhereStatements($where)) : '';
    $sql = "UPDATE {$table} SET {$sqlSet} {$sqlWhere}";
    $r = $this->query($sql);
    if (!$r) {
      $this->trans_status = false;
      return false;
    }
    return $r;
  }

  /**
   * пример использования:
   * $r = $db->delete('tbl_name',
   *     array(
   *         'id' => 1,
   *     )
   * );
   */
  function delete($table, $where = array())
  {
    $sqlWhere = $where ? "WHERE " . implode(' AND ', (array)$this->sqlWhereStatements($where)) : '';
    $sql = "DELETE FROM {$table} {$sqlWhere}";
    $r = $this->query($sql);
    if (!$r) {
      $this->trans_status = false;
      return false;
    }
    return $r;
  }

  function escape($v)
  {
    if (is_array($v)) {
      foreach ($v as $k => $s) {
        $v[$k] = $this->escape($s);
      }
      return $v;
    }
    return mysql_real_escape_string($v, $this->c);
  }

  function escape_param($v)
  {
    if (is_null($v) || is_string($v) && @strcasecmp($v, 'null') == 0) {
      return 'NULL';
    }
    $var = $this->escape($v);
    if (is_array($v)) {
      foreach ($v as $k => $i) {
        $v[$k] = $this->escape_param($i);
      }
      return $v;
    }
    return "'" . $var . "'";
  }

  function begin()
  {
    $this->query('BEGIN');
  }

  function commit()
  {
    $this->query('COMMIT');
  }

  function rollback()
  {
    $this->query('ROLLBACK');
  }

  function addColumn($table, $name, $params = array())
  {
    $type = @$params['type'] ? $params['type'] : 'int';
    $sql = "ALTER TABLE {$table} ADD COLUMN {$name} {$type}";
    return $this->query($sql);
  }

  function sqlWhereStatementsByFields($set, $fields)
  {
    $set = array_intersect_key($set, array_flip($fields));
    return $this->sqlWhereStatements($set);
  }

  function sqlWhereStatements($set)
  {
    return $this->sqlStatements($set, array('where' => 1));
  }

  function sqlStatements($set, $params = array())
  {
    if (!$set || !is_array($set)) {
      return $set;
    }
    $sqlSet = array();
    $set = $this->escape_param($set);
    foreach ($set as $k => $v) {
      $key = $k;
      $sign = '=';
      if (@$params['where']) {
        if (is_string($v) && strcasecmp($v, 'null') == 0) {
          $sign = 'IS';
        }
        if (is_array($v)) {
          $sign = 'IN';
          $v = '(' . implode(',', $v) . ')';
        }
      }
      if (preg_match('/^([^\s]+) ([<>!=]+)$/i', $k, $m)) {
        $key = $m[1];
        $sign = $m[2];
      }
      $sqlSet[] = "`{$key}` {$sign} {$v}";
    }
    return $sqlSet;
  }

  function getTableFields($table)
  {
    $r = $this->select('*', $table);
    return $r->fields();
  }

  /**
   * примеры использования:
   * $r = $db->createTable('tbl_name',
   *     array(
   *         'fields' => array(
   *             'id' => array(
   *                 'name' => 'id',
   *                 'type' => 'integer',
   *             ),
   *             'name' => array(
   *                 'name' => 'name',
   *                 'type' => 'varchar(20)',
   *             ),
   *         ),
   *     )
   * );
   * $r = $db->createTable('tbl_name',
   *     array(
   *         'from_table' => 'news',
   *         'with_data'  => 1,
   *         'is_temp'    => true,
   *     )
   * );
   */
  function createTable($table, $params = array())
  {
    $fields = @$params['fields'] ? $params['fields'] : null;
    if (@$params['from_table'] && !$fields) {
      $fields = $this->getTableFields($params['from_table']);
    }

    $sqlFields = array();
    foreach ($fields as $field) {
      $sqlFields[] = '"' . $field['name'] . '" ' . $field['type'];
    }
    $sqlFields = implode(',', $sqlFields);

    $isTempTable = @$params['is_temp'] ? 'TEMP' : '';

    $sql = "CREATE {$isTempTable} TABLE {$table} ({$sqlFields})";

    $r = $this->query($sql);

    if ($r && @$params['from_table'] && @$params['with_data']) {
      $fieldsList = '"' . implode('","', array_keys($fields)) . '"';
      $sql = "INSERT INTO {$table} ({$fieldsList}) SELECT {$fieldsList} FROM {$params['from_table']} WHERE {$params['with_data']}";
      $r = $this->query($sql);
    }

    return $r;
  }
}

class simpleDbResult
{
  var $r; // resource

  var $c; // connect

  function __construct($resource)
  {
    if (is_array($resource)) {
      $this->r = $resource['resource'];
      $this->c = $resource['connect'];
    } else {
      $this->r = $resource;
    }
  }

  function val($field = null)
  {
    $r = $this->next();
    if (!$r || !is_array($r)) {
      return $r;
    }
    if (!$field) {
      return array_shift($r);
    }
    return $r[$field];
  }

  function next()
  {
    return mysql_fetch_object($this->r);
  }

  function row()
  {
    return mysql_fetch_assoc($this->r);
  }

  function all()
  {
    $result = array();
    while ($row = $this->next()) {
      $result[] = $row;
    }
    return $result;
  }

  function fields()
  {
    $fields = array();
    $n = mysql_num_fields($this->r);
    for ($i = 0; $i < $n; $i++) {
      $fieldName = mysql_field_name($this->r, $i);
      $fields[$fieldName] = array(
        'name' => $fieldName,
        'type' => preg_replace('/\d/', '', mysql_field_type($this->r, $i)),
      );
    }
    return $fields;
  }

  function count()
  {
    $r = $this->rows();
    $f = $this->affected();
    return $f ? $f : $r;
  }

  function rows()
  {
    return mysql_num_rows($this->r);
  }

  function affected()
  {
    return mysql_affected_rows($this->r);
  }

  function error()
  {
    return mysql_error($this->r);
  }

  function id()
  {
    return mysql_insert_id($this->c);
  }
}
