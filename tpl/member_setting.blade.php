<h1>{{ xe_trans('social_login::socialLoginSetting') }}</h1>
<p>{{ xe_trans('social_login::descSocialLoginSetting') }}</p>
<div class="setting-card login-connect">
    <h2>{{ xe_trans('social_login::connectAccounts') }}</h2>

    @foreach($providers as $provider => $info)
        @if(array_get($info, 'activate', false))
            <div class="setting-group">

                <div class="setting-group-content">

                    <!--[D] 계정 연결되어 있는 경우 on 클래스 추가-->
                    @if(isset($accounts[$provider]))
                        <div class="setting-left on">
                            <i class="xi-{{ $provider }}"></i>{{ xe_trans('social_login::connectedTo', ['provider' => xe_trans($info['title'])]) }}
                        </div>
                        <div class="setting-right">
                            <button data-link="{{ route("social_login::disconnect", ['provider' => $provider]) }}" class="__xe_socialDisconnect xe-btn xe-btn-text">{{ xe_trans('social_login::disconnect') }}</button>
                        </div>
                    @else
                        <div class="setting-left">
                            <i class="xi-{{ $provider }}"></i>{{ xe_trans('social_login::connectableTo', ['provider' => xe_trans($info['title'])]) }}
                        </div>
                        <div class="setting-right">
                            <button data-link="{{ route("social_login::auth", ['provider' => $provider, '_p' => 1]) }}" class="__xe_socialConnect xe-btn xe-btn-text">{{ xe_trans('social_login::connect') }}</button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>

{{ XeFrontend::html('social_login::addlink')->content("
<script>
    $(function () {
        $('.__xe_socialConnect').click(function(){
            window.open($(this).data('link'), 'social_login_connect',\"width=600,height=400,scrollbars=no\");
        });
        $('.__xe_socialDisconnect').click(function(){
            location.href = $(this).data('link');
        })
    });
</script>
")->load() }}