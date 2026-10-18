<?php
require dirname(__DIR__).'/app/bootstrap.php';
use App\Core\Database;
if(PHP_SAPI!=='cli'){http_response_code(403);exit("Run this repair from the command line.\n");}
$db=Database::connection();
$sql=file_get_contents(__DIR__.'/fix_encoding.sql');
$statements=array_filter(array_map('trim',explode(';',$sql)));
$db->beginTransaction();
try{foreach($statements as $statement){if(str_starts_with(strtoupper($statement),'SET NAMES')||str_starts_with(strtoupper($statement),'USE '))continue;$db->exec($statement);}$db->commit();echo "ReBox sample text has been repaired.\n";}catch(Throwable $e){if($db->inTransaction())$db->rollBack();fwrite(STDERR,"Repair failed: ".$e->getMessage()."\n");exit(1);}
