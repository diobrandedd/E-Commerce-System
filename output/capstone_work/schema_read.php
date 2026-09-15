<?php
require __DIR__.'/../../includes/db.php';
$p=ims_pdo();
$q=$p->query("SELECT TABLE_NAME,COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,COLUMN_KEY,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME,ORDINAL_POSITION");
file_put_contents(__DIR__.'/schema.json',json_encode($q->fetchAll(),JSON_PRETTY_PRINT));
$q=$p->query("SELECT TABLE_NAME,COLUMN_NAME,REFERENCED_TABLE_NAME,REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL");
file_put_contents(__DIR__.'/relations.json',json_encode($q->fetchAll(),JSON_PRETTY_PRINT));
echo 'Schema metadata saved; no business records read.';
