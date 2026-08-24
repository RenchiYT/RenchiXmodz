<?php
require_once 'config.php';

$params = http_build_query([
    'client_id' => DISCORD_CLIENT_ID,
    'redirect_uri' => DISCORD_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'identify email'
]);

redirect('https://discord.com/api/oauth2/authorize?' . $params);
