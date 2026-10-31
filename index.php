<?php
// Web root = project root (cPanel public_html): hand every request to the front controller in public/.
// Works without mod_rewrite thanks to DirectoryIndex + FallbackResource in .htaccess.
require __DIR__ . '/public/index.php';
