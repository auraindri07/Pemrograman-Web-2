<?php require_once __DIR__.'/ui.php'; ?>
<!DOCTYPE html><html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($uiTitle) ?> - EduLMS</title>
<link rel="stylesheet" href="../assets/css/tailwind.css"><link rel="stylesheet" href="../assets/fonts/jakarta.css">
<?php if (!empty($uiChart)): ?><script src="../assets/js/chart.umd.js"></script><?php endif; ?>
<?php require __DIR__.'/portal_assets.php'; ?>
</head><body class="ui-page <?= e($uiPage ?? '') ?> <?= ($_SESSION['role'] ?? '') === 'guru' ? 'ui-teacher' : 'ui-student' ?>">
<?php if (empty($uiNoNav)) require __DIR__.'/portal_nav.php'; ?>
