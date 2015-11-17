<?php

return [
    'facebook' => [
        'title' => '페이스북',
        'client_id' => getenv('SERVICE_FACEBOOK_CLIENTID'),
        'client_secret' => getenv('SERVICE_FACEBOOK_CLIENTSECRET'),
    ],
    'naver' => [
        'title' => '네이버',
        'client_id' => getenv('SERVICE_NAVER_CLIENTID'),
        'client_secret' => getenv('SERVICE_NAVER_CLIENTSECRET'),
    ],
    'twitter' => [
        'title' => '트위터',
        'client_id' => getenv('SERVICE_TWITTER_CLIENTID'),
        'client_secret' => getenv('SERVICE_TWITTER_CLIENTSECRET'),
    ],
    'google' => [
        'title' => '구글',
        'client_id' => getenv('SERVICE_GOOGLE_CLIENTID'),
        'client_secret' => getenv('SERVICE_GOOGLE_CLIENTSECRET'),
    ],
    'github' => [
        'title' => '깃허브',
        'client_id' => getenv('SERVICE_GITHUB_CLIENTID'),
        'client_secret' => getenv('SERVICE_GITHUB_CLIENTSECRET'),
    ],
];
