<?php
if (!isset($EVENT)) return;

$ip   = $_SERVER['REMOTE_ADDR'];
$evt  = $EVENT;
$file = $FILE ?? '-';
$size = $SIZE ?? '-';
$type = $TYPE ?? '-';
$hash = $HASH ?? '-';

$log = date("Y-m-d H:i:s")
     . " | IP=$ip | EVENT=$evt"
     . " | FILE=$file | SIZE=$size"
     . " | TYPE=$type | HASH=$hash\n";

file_put_contents("app_events.log", $log, FILE_APPEND);
?>
