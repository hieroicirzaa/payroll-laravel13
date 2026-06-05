<?php

return [
    'access_token_expiration_minutes' => (int) env('ACCESS_TOKEN_EXPIRATION_MINUTES', 30),
    'refresh_token_expiration_days' => (int) env('REFRESH_TOKEN_EXPIRATION_DAYS', 14),
];
