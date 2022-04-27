<?php
class DB_Manager
{
    public static function getTable($sql)
    {
        $result = new DB_Object();
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_errno) {
            $result->count = -1;
            $result->data = "Verbindung fehlgeschlagen: " . $conn->connect_error;
        }
        else
        {
            $rows = $conn->query($sql);
            if(!$rows)
            {
                $result->count = -1;
                $result->error = "statement failed: ".mysqli_errno($conn);                    
            }
            else
            {
                $result->data = array();
                $result->count = $rows->num_rows;
                while($row = mysqli_fetch_assoc($rows)) 
                {
                    array_push($result->data, $row);
                }

                mysqli_free_result($rows);
            }
        }
        
        $conn->close();

        return $result;
    }

    public static function executeSql($sql)
    {
        $result = new DB_Object();
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        $rows = $conn->query($sql);

        if (!$rows) {
            $result->count = -1;
            $result->error = "statement failed: ".mysqli_errno($conn); 
        }
        $conn->close();

        return $result;
    }
}

class DB_Object
{
    public $count;
    public $error;
    public $data; 
}
?>