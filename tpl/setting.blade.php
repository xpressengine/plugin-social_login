@php
    use Xpressengine\Plugins\SocialLogin\Plugin;
@endphp

<div class="row">
    <div class="col-sm-12">
        <div class="panel">
            <div class="panel-body">
                <form method="post" action="{{ route('social_login::settings.config.update') }}">
                    {!! csrf_field() !!}

                    <div class="form-group">
                        <label>가입방식 설정</label>
                        <select name="registerType" class="form-control">
                            <option value="{{ Plugin::REGISTER_TYPE_SIMPLE }}" @if (app('xe.config')->getVal('social_login.registerType', Plugin::REGISTER_TYPE_SIMPLE) === Plugin::REGISTER_TYPE_SIMPLE) selected @endif >간단가입: 필수 정보 확인 후 가입</option>
                            <option value="{{ Plugin::REGISTER_TYPE_STEP }}" @if (app('xe.config')->getVal('social_login.registerType', Plugin::REGISTER_TYPE_SIMPLE) === Plugin::REGISTER_TYPE_STEP) selected @endif >회원가입: 회원가입 절차대로 가입</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>스킨</label>
                        <select name="skin" class="form-control">
                            <option>선택</option>
                            @foreach($skins as $skin)
                            <option value="{{ $skin->getId() }}" {{ $selected->getId() === $skin->getId() ? 'selected' : '' }}>{{ xe_trans($skin->getTitle()) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <p class="text-right btns">
                        <button type="submit" class="btn btn-primary">{{xe_trans('xe::save')}}</button>
                    </p>
                </form>
                <div class="form-group">
                    <label>{{ xe_trans('social_login::socialLoginProviderSetting') }}</label>
                    <p>{!! xe_trans('social_login::descSocialLoginProviderSetting') !!}</p>
                    <div class="list-group">
                    @foreach($providers as $provider => $info)
                        <div class="list-group-item __xe_social_login {{ $provider }}">
                        @include('social_login::tpl.show', compact('provider', 'info'))
                        </div>
                    @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
