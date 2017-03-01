<?php
$path=$_REQUEST['path'];
$sugar_config=array();
require ($path . DIRECTORY_SEPARATOR . 'config.php');
require ($path . DIRECTORY_SEPARATOR . 'config_override.php');
$host =     $sugar_config['dbconfig']['db_host_name'];
$user =     $sugar_config['dbconfig']['db_user_name'];
$password = $sugar_config['dbconfig']['db_password'];
$database = $sugar_config['dbconfig']['db_name'];
$mysqli = mysqli_connect($host,$user,$password,$database);
if (!$mysqli) {
    echo "Error: Unable to connect to MySQL." . PHP_EOL;
    echo "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
    echo "Debugging error: " . mysqli_connect_error() . PHP_EOL;
    exit;
}
$query ="SELECT id, name, date_modified FROM acl_roles WHERE deleted = 0 ORDER BY name ASC";
if ($result = $mysqli->query($query)) {
    $body="<tbody>";
    while ($row = $result->fetch_row()) {
        $body .= "\n<tr>";
        foreach($row as $data) {
            $body .= "<td>{$data}</td>";
        }
        $body .= "</tr>";
    }
    $body .= "</tbody>";
    /* free result set */
    $result->close();
} else {
    echo "No data";
}
$header = "<thead><tr><th>id</th><th>Name</th><th>Date Modified</th></tr></thead>";
$return_value = "<table id='ACL_table' class='row-border' cellspacing='0'>";
$return_value .= $header . $body;
$return_value .= "</table>";

echo $return_value;