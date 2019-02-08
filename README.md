<p align="center"> 
  <img src="https://raw.githubusercontent.com/xpressengine/plugin-social_login/master/icon.png">
 </p>

# XE3 Social Login
이 어플리케이션은 Xpressengine3(이하 XE3)의 플러그인입니다.

이 플러그인은 XE3에서 Social Login 기능을 제공합니다.

Naver, Github, Google, Facebook, Twitter 서비스의 OAuth 인증을 사용하여 사이트에 로그인/가입 할 수 있도록 도와줍니다.
소셜로그인을 사용하기 위해서는 먼저 각 인증 서비스의 어플리케이션 설정을 해야합니다.

![License](http://img.shields.io/badge/license-GNU%20LGPL-brightgreen.svg)

# Installation
### Console
```
$ php artisan plugin:install social_login
```

### Web install
- 관리자 > 플러그인 & 업데이트 > 플러그인 목록 내에 새 플러그인 설치 버튼 클릭
- `social_login` 검색 후 설치하기

# Usage
'관리페이지 > 회원 > 소셜 로그인'에서 사용하려는 서비스의 '소셜로그인 프로바이더 설정'을 등록 후 사용할 수 있습니다.

'소셜로그인 프로바이더 설정'에서 서비스 별로 발급한 client_id와 client_secret 값을 입력할 수 있으며, 각 서비스의 애플리케이션 등록 시 필요한 callback url을 확인할 수 있습니다.

## Configuration plugin
* Naver의 네아로(네이버 아이디로 로그인) 서비스는 ![이곳](https://developers.naver.com/apps/#/list) 에서 관리 & 생성 할 수 있습니다.
* Facebook의 로그인 API는 [이곳](https://developers.facebook.com/apps/)에서 관리 & 생성 할 수 있습니다.
* Twitter의 로그인 API는 [이곳](https://developer.twitter.com/en/apps)에서 관리 & 생성 할 수 있습니다.
* Github의 로그인 API는 [이곳](https://github.com/settings/developers)에서 관리 & 생성 할 수 있습니다.
* Google의 로그인 API는 [이곳](https://console.developers.google.com/)에서 관리 & 생성 할 수 있습니다.

## License
이 플러그인은 LGPL라이선스 하에 있습니다. <https://opensource.org/licenses/LGPL-2.1>
