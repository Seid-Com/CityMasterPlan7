<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Error</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; }
        h1 { color: #dc3545; }
    </style>
</head>
<body>
    <h1>An Error Occurred</h1>
    <div class="error">
        <?php if (isset($message)) : ?>
            <p><?= esc($message) ?></p>
        <?php else : ?>
            <p>An unexpected error occurred.</p>
        <?php endif ?>
    </div>
    <a href="<?= base_url() ?>">Go to Homepage</a>
</body>
</html>