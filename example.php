<?php
require __DIR__ . '/src/PokerProvide.php';

use PokerProvide\PokerProvide;

$pp = new PokerProvide(
    'pk_sbx_YOUR_PUBLIC_KEY',                    // your sandbox public key
    getenv('PP_SECRET_KEY')     // NEVER hard-code the secret
);

$session = $pp->createSession('P_10293', 'LuckyAce', 'EUR');
echo $session['launch_url'] . PHP_EOL;
