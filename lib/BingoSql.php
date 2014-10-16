<?php
require_once('config.php');
define('HTML_EOL', '<br>');

class BingoSqlConn extends PDO {

    private $engine;
    private $host;
    private $database;
    private $user;
    private $pass;

    public function __construct() {
        $this->engine = 'mysql';
        $this->host = DATABASE_HOST;
        $this->database = DATABASE_NAME;
        $this->user = DATABASE_USER;
        $this->pass = DATABASE_PASSWORD;
        $dns = $this->engine . ':dbname=' . $this->database . ";host=" . $this->host;
        parent::__construct($dns, $this->user, $this->pass);
    }

    public static function init($options = array()) {
        if (isset($options['models_path'])) {
            echo __DIR__ . "../" . $options['models_path'];
        }
    }

}
class BingoSqlModel {

    private $fields = array();
    protected $table = '';
    protected $key = 'id';
    static $pdoConn = '';
    protected $belongs_to = '';
    protected $has_many = '';

    public function __construct() {
        self::$pdoConn = new BingoSqlConn();
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
            $this->table = $this->table == '' ? strtolower(get_class($this)) : $this->table;

        $s = $this->$name($arguments);
        if ($s != '')
            return $s;
        else
            return $this;
    }

    public static function query($query) {


        $sth = self::$pdoConn->prepare($query);
        $s = $sth->execute();
        return $s ? $sth->fetchAll(PDO::FETCH_ASSOC) : FALSE;
    }

    protected function returnRel($name) {
        if (isset($this->belongs_to[$name])) {
            $tmp_keys = explode('|', $this->belongs_to[$name]);
            $query = "SELECT * FROM {$name} WHERE  `{$tmp_keys[1]}` = :idval";

            $args = array(
                ':idval' => $this->__get($tmp_keys[0])
            );

            $sth = self::$pdoConn->prepare($query);
            $s = $sth->execute($args);

            if (!$s) {
                echo "\PDO::errorInfo():\n";
                print_r(self::$pdoConn->errorInfo());
            }


            $data = $sth->fetch(PDO::FETCH_ASSOC);

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

            $sth = self::$pdoConn->prepare($query);
            $s = $sth->execute($args);

            if (!$s) {
                echo "\PDO::errorInfo():\n";
                print_r(self::$pdoConn->errorInfo());
            }


            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

            $has_many_objs = array();
            $has_many = $this->getTableModel($name);
            foreach ($rows as $data) {
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
            if ($pclass == 'BingoSqlModel') {
                if (strtolower($tablename) == strtolower($class_name))
                    return new $class_name();

                $s = new $class_name();
                if ($s->getTableName() == $tablename)
                    return $s;
            }
        }
        return new BingoSqlModel();
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
            $sth = self::$pdoConn->prepare($query);
            $sth->execute();
            $this->fields = $sth->fetch(PDO::FETCH_BOTH);
            return $this;
        } else if ($arguments[0] == 'all') {
            $query = "SELECT * FROM {$this->table}";
            $query.= $where == '' ? '' : " WHERE {$where}";
            $query.= $order_by == '' ? '' : " ORDER BY {$order_by}";
            $query.= $limit == '' ? '' : " LIMIT {$limit}";


            $sth = self::$pdoConn->prepare($query);
            $sth->execute();
            $rows = array();

            $data = $sth->fetchAll(PDO::FETCH_ASSOC);
            $rows = array();
            foreach ($data as $row) {
                $user = new BingoSqlModel();
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
            $stm = self::$pdoConn->prepare($query);
            $stm->execute($matches);
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
            $stm = self::$pdoConn->prepare($query);
            try {
                $stm->execute($matches);
                $this->fields[$this->key] = self::$pdoConn->lastInsertId();
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
        }
    }
}

function __autoload($class_name) {

    if (file_exists(__DIR__ . "/../" . MODELS_PATH . '/' . $class_name . '.php')) {
        require_once (__DIR__ . "/../" . MODELS_PATH . '/' . $class_name . '.php');
        return;
    }
}

foreach (glob(__DIR__ . "/../" . MODELS_PATH . '/' . "*.php") as $filename) {
    require_once($filename);
}
