<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../includes/db.php';
$pdo->exec(file_get_contents(__DIR__.'/../database/portal_actions.sql'));
echo "Portal action tables ready in $dbname. Existing data retained.\n";
