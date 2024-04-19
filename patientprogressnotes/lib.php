<?php
class sqlDb
{
    public $error = "";
    private $mysql_db;
    public function __construct($db)
    {
        $this->mysql_db = $db;
    }
    public function error($query, $errno, $error)
    {
        // global $mysql_db;
        $this->mysql_db->query("rollback");
        $this->error = $error . " " . $query;
        return false;

    }

    public function query($query)
    {
        //echo $query; exit();
        $result = $this->mysql_db->query($query) or $this->error($query, mysqli_errno($this->mysql_db), mysqli_error($this->mysql_db));
        return $result;
        $result->free();
    }
    /**
     * Perform a modification query on database
     *
     * @param string $table
     * @param object $data
     * @param string $action
     * @param string $parameters
     * @return data resource
     */
    public function perform($table, $data, $action = 'insert', $parameters = '')
    {
        reset($data);
        // print_r($data);
        if ($action == 'insert') {
            $query = 'INSERT INTO ' . $table . ' (' . join(', ', array_keys($data)) . ') VALUES (';
            reset($data);
            foreach ($data as $value) {
                if (preg_match("/^func:/i", $value)) {
                    $query .= substr($value, 5) . ', ';
                } else {
                    switch ((string) $value) {
                        case 'now()':
                            $query .= 'NOW(),';
                            break;
                        case 'null':
                            $query .= 'NULL,';
                            break;
                        case '':
                            $query .= 'NULL,';
                            break;
                        default:
                            // echo $value . "<br>";
                            $query .= '"' . $this->input($value) . '",';
                            break;
                    }
                }
            }
            $query = rtrim($query, ',') . ')';
            // echo $query;exit();
        } elseif ($action == 'update') {
            $query = 'UPDATE ' . $table . ' SET ';
            // print_r($data); exit();
            foreach ($data as $columns => $value) {
                if (preg_match("/^func:/i", $value)) {
                    $query .= $columns . substr($value, 5) . ', ';
                } else {
                    switch ((string) $value) {
                        case 'now()':
                            $query .= $columns . ' = NOW(),';
                            break;
                        case 'null':
                            $query .= $columns . ' = NULL,';
                            break;
                        case '++':
                            $query .= $columns . ' = ' . $columns . ' + 1,';
                            break;
                        case '':
                            $query .= $columns . ' = NULL,';
                            break;
                        default:
                            $query .= $columns . ' = "' . $this->input($value) . '",';
                            break;
                    }
                }
            }
            $query = rtrim($query, ',');
            if ($parameters !== '') {
                $query .= ' WHERE ' . $parameters;
            }

        }
        return $this->query($query);
    }
    public function fetch_object($result)
    {
        return mysql_fetch_object($result);
    }
    public function fetch_array($result)
    {
        return mysql_fetch_array($result, MYSQL_ASSOC);
    }
    public function fetch_row($result)
    {
        return mysql_fetch_row($result);
    }
    public function num_rows($result)
    {
        return mysql_num_rows($result);
    }
    public function data_seek($result, $row_number)
    {
        return mysql_data_seek($result, $row_number);
    }
    public function insert_id()
    {
        return mysql_insert_id();
    }
    public function affected_rows()
    {

        return $this->mysql_db->affected_rows;
    }
    public function free_result($result)
    {
        return mysql_free_result($result);
    }
    public function fetch_fields($result)
    {
        return mysql_fetch_field($result);
    }
    public function output($string)
    {
        return htmlspecialchars($string);
    }
    public function input($string)
    {

        return $this->mysql_db->real_escape_string($string);
    }
    public function prepare_input($string)
    {
        if (is_string($string)) {
            return trim(stripslashes($string));
        } elseif (is_array($string)) {
            reset($string);
            while (list($key, $value) = @each($string)) {
                $string[$key] = $this->prepare_input($value);
            }
            return $string;
        } else {
            return $string;
        }
    }
}
/*$a=new sqldb($conn);
echo $a->input('check"');*/