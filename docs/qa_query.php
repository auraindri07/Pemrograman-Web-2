<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (!str_starts_with(getenv('EDULMS_DB_NAME') ?: '', 'edulms_qa_')) exit('QA database required');
require __DIR__ . '/../includes/db.php';
$stmt = $pdo->prepare($argv[1]);
$stmt->execute(json_decode($argv[2] ?? '[]',true));
echo json_encode(['rows'=>$stmt->columnCount() ? $stmt->fetchAll() : [], 'id'=>$pdo->lastInsertId()]);
