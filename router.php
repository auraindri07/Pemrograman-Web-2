<?php
// Development router: enforce the same private directories as Apache.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (preg_match('~^/(uploads|docs|database|includes|prompt|\.git|\.qa-tools)(/|$)~i', $path) || str_contains($path, '..')) {
    http_response_code(403); exit('Akses ditolak.');
}
return false;
