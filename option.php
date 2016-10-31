<?php

return [
    'facebook' => [
        'title' => '페이스북',
        'client_id' => env('SERVICE_FACEBOOK_CLIENTID', ''),
        'client_secret' => env('SERVICE_FACEBOOK_CLIENTSECRET', ''),
    ],
    'naver' => [
        'title' => '네이버',
        'client_id' => env('SERVICE_NAVER_CLIENTID', ''),
        'client_secret' => env('SERVICE_NAVER_CLIENTSECRET', ''),
    ],
    'twitter' => [
        'title' => '트위터',
        'client_id' => env('SERVICE_TWITTER_CLIENTID', ''),
        'client_secret' => env('SERVICE_TWITTER_CLIENTSECRET', ''),
    ],
    'google' => [
        'title' => '구글',
        'client_id' => env('SERVICE_GOOGLE_CLIENTID', ''),
        'client_secret' => env('SERVICE_GOOGLE_CLIENTSECRET', ''),
    ],
    'github' => [
        'title' => '깃허브',
        'client_id' => env('SERVICE_GITHUB_CLIENTID', ''),
        'client_secret' => env('SERVICE_GITHUB_CLIENTSECRET', ''),
    ],
];
