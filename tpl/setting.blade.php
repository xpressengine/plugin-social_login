<div class="row">
    <div class="col-sm-12">
        <div class="panel">
            <div class="panel-body">

                <div class="form-group">
                    <label>소셜로그인 프로바이더 설정</label>
                    <p>소셜로그인 플러그인은 Naver, Github, Google, Facebook, Twitter 서비스의 OAuth 인증을 사용하여 사이트에 로그인/가입 할 수 있도록 도와줍니다.
                        소셜로그인을 사용하기 위해서는 먼저 각 인증 서비스의 어플리케이션 설정을 해야합니다. <br> 그 다음 설정된 어플리케이션의 정보를 이 페이지에 입력하십시오.</p>
                    <div class="list-group">
                    @foreach(['github', 'facebook', 'naver', 'twitter', 'google'] as $provider)
                        <div class="list-group-item __xe_social_login {{ $provider }}">
                        @include('social_login::tpl.show', compact('provider', 'providers'))
                        </div>
                    @endforeach
                    </div>
                </div>

            </div>
        </div>


    </div>
</div>

{!! app('xe.frontend')->html('social_login.update')->content("
<script>
    $(function($) {
        window.updateSocialLogin = function (data) {
            XE.toast(data.type, data.message);
            XE.page(data.url, '.__xe_social_login.'+data.provider);
        };
    });
</script>
")->load() !!}
