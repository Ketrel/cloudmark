<?php

require_once ('./inc/classes/class.cloudmarkJSON.php');

header("Content-type: text/plain");
//header("Content-type: text/html");
//header("Content-type: text/xml");

$currentCategory = (isset($_GET['cat'])) ? (int)$_GET['cat'] : 0;

$linkpage = new cloudmarksDB('cloudmarks.db');

$output = $linkpage->getCats($currentCategory);

var_export($output);
exit();
?>
