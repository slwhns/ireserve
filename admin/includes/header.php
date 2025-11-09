<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Admin Dashboard'; ?> | iReserve</title>
    <link rel="stylesheet" href="/ireserve/assets/css/admin.css?v=10">
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <!-- ============ Navigation Bar ============ -->
    <div class="container">
