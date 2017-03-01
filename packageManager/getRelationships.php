<?php
$path=$_REQUEST['path'];

$metadataPath = $path . DIRECTORY_SEPARATOR . 'custom/metadata/';
$files = glob($metadataPath.'*MetaData.php');

$body="<tbody>";
foreach ($files as $file) {
    $dictionary=array();
    $relationships=array();
    include($file);
    $name=substr(basename($file),0,-12);
    $type = $dictionary[$name]['true_relationship_type'];
    $rhs_module = $dictionary[$name]['relationships'][$name]['rhs_module'];
    $lhs_module = $dictionary[$name]['relationships'][$name]['lhs_module'];
    $join_table = $dictionary[$name]['relationships'][$name]['join_table'];
    $body .= "<td>$name<br>{$type}</td>";
    $body .= "<td>$rhs_module</td>";
    $body .= "<td>$lhs_module</td>";
    $body .= "<td>$join_table</td>";
    $date=date ('F d Y H:i:s', filemtime($file));
    $body .= "<td>" . $date . "</td></tr>";
}
$body .= "</tbody>";

$header = "<thead><tr><th>Relationship Name</th>
                      <th>RHS Module</th>
                      <th>LHS Module</th>
                      <th>Join Table</th>
                      <th>Date Modified</th></tr></thead>";
$return_value = "<table id='relationship_table' class='row-border' cellspacing='0'>";
$return_value .= $header . $body;
$return_value .= "</table>";

echo $return_value;
