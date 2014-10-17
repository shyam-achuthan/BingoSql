<?php

namespace BingoSql;

class Instance {

    public function __construct($options) {
        Connection::$engine = isset($options['DATABASE_ENGINE']) ? $options['DATABASE_ENGINE'] : Connection::$engine;
        Connection::$host = isset($options['DATABASE_HOST']) ? $options['DATABASE_HOST'] : '';
        Connection::$user = isset($options['DATABASE_USER']) ? $options['DATABASE_USER'] : '';
        Connection::$pass = isset($options['DATABASE_PASSWORD']) ? $options['DATABASE_PASSWORD'] : '';
        Connection::$database = isset($options['DATABASE_NAME']) ? $options['DATABASE_NAME'] : '';
        if (isset($options['MODELS_PATH']))
            foreach (glob(__DIR__ . "/../" . $options['MODELS_PATH'] . '/' . "*.php") as $filename) {
                require_once($filename);
                echo $filename;
            }
    }

}

class Connection extends \PDO {

    static $engine = 'mysql';
    static $host;
    static $database;
    static $user;
    static $pass;

    public function __construct() {
        $dns = self::$engine . ':dbname=' . self::$database . ";host=" . self::$host;
        parent::__construct($dns, self::$user, self::$pass);
    }

}

class Model {

    private $fields = array();
    protected $table = '';
    protected $key = 'id';
    static $pdoConn = '';
    protected $belongs_to = '';
    protected $has_many = '';

    public function __construct() {
        self::$pdoConn = new Connection();

        if ($this->table == '')
            $this->table = get_class($this);
    }

    public function __set($name, $value) {
        $this->fields[$name] = $value;
    }

    public function &__get($name) {

        if (isset($this->fields[$name]))
            return $this->fields[$name];
        else {

            return $this->returnRel($name);
        }
        return FALSE;
    }

    public function __call($name, $arguments = array()) {

        if ($this->table == '' && $name != 'table')
            $this->table = $this->table == '' ? (get_class($this)) : $this->table;

        $s = $this->$name($arguments);
        if ($s != '')
            return $s;
        else
            return $this;
    }

    public static function query($query,$args = FALSE,$return=TRUE,$fetchAll=true) {


        $sth = self::$pdoConn->prepare($query);
        $s = ($args) ? $sth->execute($args):$sth->execute();
        if($return)
        if($fetchAll)
            return $s ? $sth->fetchAll(\PDO::FETCH_ASSOC) : FALSE;
        else
            return $s ? $sth->fetch(\PDO::FETCH_ASSOC) : FALSE;    
    }

    protected function returnRel($name) {
        if (isset($this->belongs_to[$name])) {
            $tmp_keys = explode('|', $this->belongs_to[$name]);
            $query = "SELECT * FROM {$name} WHERE  `{$tmp_keys[1]}` = :idval";

            $args = array(
                ':idval' => $this->__get($tmp_keys[0])
            );

            $data = self::query($query,$args,TRUE,FALSE);

            $belongs_to = $this->getTableModel($name);

            foreach ($data as $k => $v)
                $belongs_to->{$k} = $v;

            return $belongs_to;
        }

        if (isset($this->has_many[$name])) {
            $tmp_keys = explode('|', $this->has_many[$name]);
            $query = "SELECT * FROM {$name} WHERE  `{$tmp_keys[1]}` = :idval";

            $args = array(
                ':idval' => $this->__get($tmp_keys[0])
            );

           $rows = self::query($query,$args,TRUE,TRUE);
           
           $has_many_objs = array();
            foreach ($rows as $data) {
                $has_many = $this->getTableModel($name);
                foreach ($data as $k => $v) {

                    $has_many->{$k} = $v;
                }
                $has_many_objs[] = $has_many;
            }
            return $has_many_objs;
        }
    }

    protected function table($arguments) {
        $this->table = $arguments[0];
    }

    protected function getTableName() {
        return $this->table;
    }

    protected function create($arguments) {
        foreach ($arguments[0] as $key => $value) {
            $this->__set($key, $value);
        }
        $this->save();
    }

    protected function getTableModel($tablename) {

        foreach (get_declared_classes() as $class_name) {



            $pclass = get_parent_class($class_name);
            $glob_class_name = "\\" . $class_name;

            if ($pclass == 'BingoSql\Model') {
                if (strtolower($tablename) == strtolower($class_name)) {

                    return new $glob_class_name();
                }



                $s = new $glob_class_name();

                if ($s->getTableName() == $tablename)
                    return $s;
            }
        }

        return new Model();
    }

    protected function &find($arguments) {
        $options = isset($arguments[1]) ? $arguments[1] : array();

        if (isset($options['where'])) {
            if (is_array($options['where'])) {
                $where = implode(' AND ', $options['where']);
            } else
                $where = $options['where'];
        } else
            $where = '';

        if (isset($options['order_by'])) {
            if (is_array($options['order_by'])) {
                $order_by = implode(',', $options['order_by']);
            } else
                $order_by = $options['order_by'];
        } else
            $order_by = '';

        $limit = isset($options['limit']) ? $options['limit'] : '';


        if (is_int($arguments[0])) {
            $query = "SELECT * FROM {$this->table} where $this->key=" . $arguments[0];
            $this->fields = self::query($query,FALSE,TRUE,FALSE);
            return $this;
        } else if ($arguments[0] == 'all') {
            $query = "SELECT * FROM {$this->table}";
            $query.= $where == '' ? '' : " WHERE {$where}";
            $query.= $order_by == '' ? '' : " ORDER BY {$order_by}";
            $query.= $limit == '' ? '' : " LIMIT {$limit}";
            $rows = array();
            $data = self::query($query,FALSE,TRUE,TRUE);
            $rows = array();
            foreach ($data as $row) {
                $user = new Model();

                foreach ($row as $key => $value)
                    $user->{$key} = $value;

                $rows[] = $user;
            }

            return $rows;
        }
    }

    //Used to insert or update rows of a table
    protected function save($arguments = array()) {
        // Creating the PDO connection
        if (isset($this->fields[$this->key])) {
            // write update commands here
            $query = "UPDATE {$this->table} SET";
            foreach ($this->fields as $key => $value) {
                $query .= PHP_EOL . "`{$key}`=:{$key},";
                $matches[':' . $key] = $value;
            }
            $query = rtrim($query, ',');
            $query .= " WHERE {$this->key}=:{$this->key}";
            self::query($query,$matches,FALSE);
        } else {
            $query = "INSERT INTO {$this->table} ";
            $fields = '';
            $values = '';
            $matches = array();
            foreach ($this->fields as $key => $value) {
                $fields .= "`{$key}`,";
                $values .= " :{$key},";
                $matches[':' . $key] = $value;
            }
            $fields = rtrim($fields, ',');
            $values = rtrim($values, ',');
            $query.= " ({$fields}) VALUES ({$values})";
            self::query($query,$matches,FALSE);
            $this->fields[$this->key] = self::$pdoConn->lastInsertId();
              
        }
    }

    public static function CreateModel($name, $options) {
        if (!class_exists($name)) {
            $class_options = '';
            $class_options .= isset($options['table']) ? " protected \$table='{$options['table']}';" . PHP_EOL : '';
            $class_options .= isset($options['primary_key']) ? " protected \$key='{$options['primary_key']}';" . PHP_EOL : '';
            $class_creation = "class $name extends \BingoSql\Model { $class_options }";
            eval($class_creation);
        }
        return true;
    }

}

function __autoload($class_name) {

    if (file_exists(__DIR__ . "/../" . MODELS_PATH . '/' . $class_name . '.php')) {
        require_once (__DIR__ . "/../" . MODELS_PATH . '/' . $class_name . '.php');
        return;
    }
}
