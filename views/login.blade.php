@inject('plugin', 'Xpressengine\Plugins\SocialLogin\Plugin')

{{ XeFrontend::css($plugin->asset('assets/auth.css'))->load() }}
{{ XeFrontend::css('assets/core/xe-ui/css/xe-ui-without-base.css')->load() }}

<div class="user __xe_userLogin">
    <div class="auth-sns-user">
        <h2 class="user__title">{{ xe_trans('xe::login') }}</h2>

        <form action="{{ route('login') }}" method="post">
            {{ csrf_field() }}
            <fieldset>
                <legend>{{ xe_trans('xe::doLogin') }}</legend>
                <div class="user-login">
                    <div class="xu-form-group xu-form-group--large">
                        <input type="text" name="email" class="xu-form-group__control" placeholder="{{ xe_trans('xe::id') }} {{ xe_trans('xe::or') }} {{ xe_trans('xe::email') }}" value="{{ old('email') }}">
                    </div>
                    <div class="xu-form-group xu-form-group--large">
                        <input type="password" name="password" class="xu-form-group__control" id="password" placeholder="{{ xe_trans('xe::password') }}">
                    </div>
                    <label class="xu-label-checkradio xu-label-checkradio--small">
                        <input type="checkbox" class="__xe_keep_login">
                        <span class="xu-label-checkradio__helper"></span>
                        <span class="xu-label-checkradio__text">{{ xe_trans('xe::keepLogin') }}</span>
                    </label>
                    <!--[D] 체크 시 하단메시지 노출-->
                    <p class="auth-noti" id="__xe_infoRemember" style="display: none;">
                        {{xe_trans('xe::keepLoginDescription')}}
                    </p>

                    {{-- recaptcha--}}
                    @if ($config['useCaptcha'] === true)
                        {!! uio('captcha') !!}
                    @endif

                    <button type="submit" class="xu-button xu-button--primary xu-button--block xu-button--large user-login__button">
                        <span class="xu-button__text">{{ xe_trans('xe::login') }}</span>
                    </button>

                    <div class="auth-user__text-box">
                        <p class="auth-user__text">아직 회원이 아닌신가요? <a href="{{ route('auth.register') }}">{{ xe_trans('xe::doSignUp') }}</a></p>
                        <p class="auth-user__text">비밀번호를 잊으셨나요? <a href="{{ route('auth.reset') }}">{{ xe_trans('xe::forgotPassword') }}</a></p>
                    </div>

                </div>
            </fieldset>
        </form>

        <p class="user-text__extra">or</p>

        <ul class="auth-sns-user-list">
            @foreach($providers as $provider => $info)
            <li class="auth-sns-user__item--{{ $provider }}">
                <a href="{{ route('social_login::auth', ['provider' => $provider]) }}">
                    {{ xe_trans('social_login::signInBy', ['provider' => xe_trans($info['title'])]) }}
                </a>
            </li>
            @endforeach
        </ul>

    </div>
</div>

{!! app('xe.frontend')->html('user.keep_login')->content("<script>
    $(function($) {
        $('.__xe_keep_login').change(function() {
            if(this.checked) {
                $('#__xe_infoRemember').slideDown('fast');
            } else {
                $('#__xe_infoRemember').slideUp('fast');
            }
        })
    });
</script>")->load()  !!}
