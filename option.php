<?php

return [
    'facebook' => [
        'title' => 'social_login::facebook',
        'client_id' => env('SERVICE_FACEBOOK_CLIENTID', ''),
        'client_secret' => env('SERVICE_FACEBOOK_CLIENTSECRET', ''),
    ],
	'kakao' => [ //02.01수정
        'title' => 'social_login::kakao',
        'client_id' => env('SERVICE_KAKAO_CLIENTID', ''),
        'client_secret' => env('SERVICE_KAKAKO_CLIENTSECRET', ''),
    ],
    'naver' => [
        'title' => 'social_login::naver',
        'client_id' => env('SERVICE_NAVER_CLIENTID', ''),
        'client_secret' => env('SERVICE_NAVER_CLIENTSECRET', ''),
    ],
    'twitter' => [
        'title' => 'social_login::twitter',
        'client_id' => env('SERVICE_TWITTER_CLIENTID', ''),
        'client_secret' => env('SERVICE_TWITTER_CLIENTSECRET', ''),
    ],
    'google' => [
        'title' => 'social_login::google',
        'client_id' => env('SERVICE_GOOGLE_CLIENTID', ''),
        'client_secret' => env('SERVICE_GOOGLE_CLIENTSECRET', ''),
    ],
    'github' => [
        'title' => 'social_login::github',
        'client_id' => env('SERVICE_GITHUB_CLIENTID', ''),
        'client_secret' => env('SERVICE_GITHUB_CLIENTSECRET', ''),
    ],
    'apple' => [
        'title' => 'social_login::apple',
        'client_id' => env('SERVICE_APPLE_CLIENTID', ''),
        'client_secret' => env('SERVICE_APPLE_CLIENTSECRET', ''),
    ],
    'soundcloud' => [
        'title' => 'social_login::soundcloud',
        'client_id' => env('SERVICE_SOUNDCLOUD_CLIENTID', ''),
        'client_secret' => env('SERVICE_SOUNDCLOUD_CLIENTSECRET', ''),
    ]
];
