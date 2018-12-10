# plugin-social_login
이 어플리케이션은 Xpressengine3(이하 XE3)의 플러그인입니다.

이 플러그인은 XE3에서 Social Login 기능을 제공합니다.

[![License](http://img.shields.io/badge/license-GNU%20LGPL-brightgreen.svg)]

# Installation
### Console
```
$ php artisan plugin:install social_login
```

### Web install
- 관리자 > 플러그인 & 업데이트 > 플러그인 목록 내에 새 플러그인 설치 버튼 클릭
- `social_login` 검색 후 설치하기

### Ftp upload
- 다음의 페이지에서 다운로드
    * https://store.xpressengine.io/plugins/social_login
    * https://github.com/xpressengine/plugin-social_login/releases
- 프로젝트의 `plugins` 디렉토리 아래 `social_login` 디렉토리명으로 압축해제
- `social_login` 디렉토리 이동 후 `composer dump` 명령 실행

# Usage
관리자 > 회원 > 소셜 로그인에서 사용하려는 프로바이더의 설정 후 사용합니다.

## License
이 플러그인은 LGPL라이선스 하에 있습니다. <https://opensource.org/licenses/LGPL-2.1>
