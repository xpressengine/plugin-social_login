{{ XeFrontend::css('assets/core/xe-ui/css/xe-ui-without-base.css')->load() }}
{{ XeFrontend::js('assets/core/user/user_register.js')->load() }}
{{ XeFrontend::js('assets/core/xe-ui-component/js/xe-page.js')->load() }}

<div class="user user--signup">
    <h2 class="user__title">{{ xe_trans('xe::signUp') }}</h2>
    <p class="user__text">{!! nl2br($registerConfig->get('register_guide')) !!}</p>

    <form action="{{ route('social_login::register') }}" method="post" data-rule="join" data-rule-alert-type="form">
        {{ csrf_field() }}
        <input type="hidden" name="account_id" value="{{ Request::old('account_id', $userContract->getId()) }}">
        <input type="hidden" name="provider_name" value="{{ Request::old('provider_name', $providerName) }}">
        <input type="hidden" name="token" value="{{ Request::old('token', $userContract->token) }}">
        <input type="hidden" name="token_secret" value="{{ Request::old('token_secret', $userContract->tokenSecret ?? '') }}">
        <input type="hidden" name="contract_email" value="{{ Request::old('contract_email', $userContract->getEmail()) }}">
        <input type="hidden" name="contract_avatar" value="{{ Request::old('contract_avatar', $userContract->getAvatar()) }}">
        <fieldset>
            <legend>{{ xe_trans('xe::signUp') }}</legend>

            <div class="xu-form-group xu-form-group--large">
                <label class="xu-form-group__label" for="f-email">{{ xe_trans('xe::email') }}</label>
                <div class="xu-form-group__box">
                    <input type="text" id="f-email" class="xe-form-control xu-form-group__control"
                           placeholder="{{ xe_trans('xe::enterEmail') }}" name="email" value="{{ old('email', $userContract->getEmail()) }}"
                           required data-valid-name="{{ xe_trans('xe::email') }}"
                           @if ($userContract->getEmail() !== null && $isEmailDuplicated === false) readonly @endif>
                </div>
            </div>

            <div class="xu-form-group xu-form-group--large">
                <label class="xu-form-group__label" for="f-login_id">{{ xe_trans('xe::id') }}</label>
                <div class="xu-form-group__box">
                    <input type="text" id="f-login_id" class="xe-form-control xu-form-group__control"
                           placeholder="{{ xe_trans('xe::enterId') }}" name="login_id" value="{{ old('login_id', strtok($userContract->getEmail(), '@')) }}"
                           required data-valid-name="{{ xe_trans('xe::id') }}">
                </div>
            </div>

            @if (app('xe.config')->getVal('user.register.use_display_name') === true)
                <div class="xu-form-group xu-form-group--large">
                    <label class="xu-form-group__label" for="f-name">{{ xe_trans(app('xe.config')->getVal('user.register.display_name_caption')) }}</label>
                    <div class="xu-form-group__box">
                        <input type="text" id="f-name" class="xu-form-group__control"
                               placeholder="{{ xe_trans('xe::enterDisplayName', ['displayNameCaption' => xe_trans(app('xe.config')->getVal('user.register.display_name_caption'))]) }}"
                               name="display_name" value="{{ old('display_name', $userContract->getNickname() ?: $userContract->getName()) }}"
                               required data-valid-name="{{ xe_trans(app('xe.config')->getVal('user.register.display_name_caption')) }}">
                    </div>
                </div>
            @endif

            <div class="user-signup">
                @foreach ($parts as $part)
                    {{ $part->render() }}
                @endforeach
            </div>

            @if($terms->count() > 0)
                <div class="terms-box __xe-register-aggrements">
                    <label class="xu-label-checkradio">
                        <input type="checkbox" name="agree" class="__xe-register-aggrement-all">
                        <span class="xu-label-checkradio__helper"></span>
                        <span class="xu-label-checkradio__text">{{ xe_trans('xe::msgAgreeAllTerms') }}</span>
                    </label>
                    <ul class="terms-list xu-form-group">
                        @foreach ($terms as $term)
                            <li>
                                <label class="xu-label-checkradio">
                                    <input type="checkbox" name="user_agree_terms[]" value="{{ $term->id }}" class="__xe-register-aggrement--{{ $term->isRequire() ? 'require' : 'optional' }}" @if($term->isRequire()) data-valid="required" required @endif>
                                    <span class="xu-label-checkradio__helper"></span>
                                    <span class="xu-label-checkradio__text">{{ xe_trans($term->title) }}
                                        @if ($term->isRequire() === true)
                                            <span class="xu-label-checkradio__empase">({{ xe_trans('xe::require') }})</span>
                                        @else
                                            <span class="xu-label-checkradio__empase">({{ xe_trans('xe::optional') }})</span>
                                        @endif
                                    </span>
                                </label>
                                <a href="{{ route('auth.terms', $term->id) }}" class="terms-list__term-button __xe_terms">{{ xe_trans('xe::viewContents') }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                {{
                XeFrontend::html('auth.register.terms')->content("
                <script>
                    $(function($) {
                        $('.__xe_terms').click(function (e) {
                            e.preventDefault();

                            XE.pageModal($(this).attr('href'));
                        });
                    });
                </script>
                ")->load()
                }}

                <style>
                    .xu-label-checkradio input[type="checkbox"], .xu-label-checkradio input[type="radio"] {
                        opacity: 0;
                        width: unset;
                        height: unset;
                        left: unset;
                    }
                </style>
            @endif

            <button type="submit" class="xu-button xu-button--primary xu-button--block xu-button--large user-signup__button-signup">
                <span class="xu-button__text">회원가입</span>
            </button>
        </fieldset>
    </form>
</div>

<script>
    $(function () {
        $('input').each(function (index, item) {
            $(item).trigger('focusout')
        })
    })
</script>
