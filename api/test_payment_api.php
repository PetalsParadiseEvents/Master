<?php
// Disabled for security (diagnostic script must not be exposed in production)
http_response_code(404);
echo "404 Not Found";
exit;
