ARG BASE_IMAGE
FROM $BASE_IMAGE

RUN rm -f /var/www/html/index.html
RUN cat > /var/www/html/index.php <<'PHPEOF'
<?php $site = getenv('DDEV_SITENAME') ?: 'translatr'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EXT:translatr - DDEV Environment</title>
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;max-width:800px;margin:50px auto;padding:0 20px;line-height:1.6;color:#333;background:#f5f5f5}
        .container{background:#fff;padding:40px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1)}
        h1{color:#ff8700;border-bottom:3px solid #ff8700;padding-bottom:10px}
        .card{background:#f9f9f9;border-left:4px solid #ff8700;padding:20px;margin:15px 0;border-radius:4px}
        .btn{display:inline-block;padding:8px 18px;margin-right:10px;background:#0078e7;color:#fff;border-radius:4px;text-decoration:none}
        code{background:#eee;padding:2px 6px;border-radius:3px}
    </style>
</head>
<body>
<div class="container">
    <h1>EXT:translatr Development Environment</h1>
    <?php foreach (['v13' => 'TYPO3 13.4 LTS', 'v14' => 'TYPO3 14.3 LTS'] as $v => $label): ?>
    <div class="card">
        <strong><?= $label ?></strong> &nbsp; <code>ddev install-<?= $v ?></code><br><br>
        <a class="btn" href="https://<?= $v ?>.<?= $site ?>.ddev.site/">Frontend</a>
        <a class="btn" href="https://<?= $v ?>.<?= $site ?>.ddev.site/typo3/">Backend</a>
    </div>
    <?php endforeach; ?>
    <div class="card">
        <strong>Backend login:</strong> <code>admin</code> / <code>Joh316!!</code>
    </div>
</div>
</body>
</html>
PHPEOF

RUN for v in v13 v14; do \
      mkdir -p /var/www/html/$v/public/typo3 && \
      echo "<h1>Perform this first</h1> <code>ddev install-$v</code>" > /var/www/html/$v/public/index.html && \
      cp /var/www/html/$v/public/index.html /var/www/html/$v/public/typo3/index.html; \
    done

ARG uid
ARG gid
RUN chown -R $uid:$gid /var/www/html
