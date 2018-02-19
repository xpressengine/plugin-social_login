<div class="row">
    <div class="col-sm-12">
        <div class="panel">
            <div class="panel-body">

                <div class="form-group">
                    <label>{{ xe_trans('social_login::socialLoginProviderSetting') }}</label>
                    <p>{!! xe_trans('social_login::descSocialLoginProviderSetting') !!}</p>
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
