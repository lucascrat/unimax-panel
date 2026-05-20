<?php
// NADA de AES aqui

// e vem assim: ?UUID=...&token=...&wifi=...&lan=...&key=...&appMobile=0
$e = isset($_GET['e']) ? $_GET['e'] : '';

// Se vier começando com "?", tira o "?"
if ($e !== '' && $e[0] === '?') {
    $e = substr($e, 1);
}

// Monta a URL real do middleware /api
$url = 'https://testes.painelmaster.lol/htvv/api/authentication.php?' . $e;

// Ideal usar cURL, mas pra simplificar:
$ab = file_get_contents($url);

header('Content-Type: application/json; charset=utf-8');
echo $ab;
