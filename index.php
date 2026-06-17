<?php

/**
 * Access Denied
 *
 * All requests should be routed through the `public/index.php` file.
 */

http_response_code(403);
echo "Access denied.";
exit();
