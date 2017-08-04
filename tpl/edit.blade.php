<div>
    <h5 class="btn-link"><strong>{{ $provider }} 설정</strong></h5>
    <hr>
    <form action="{{ route('social_login::settings.provider.update', ['provider'=>$provider]) }}" method="POST" data-submit="xe-ajax" data-callback="updateSocialLogin" >
        {{ method_field('PUT') }}
        <input type="hidden" name="title" value="{{ array_get($providers, $provider.'.title') }}">
        {{ uio('formSelect', ['label'=>'사용하시겠습니까?', 'name'=>'activate', 'options'=>['Y' => '사용', 'N' => '사용 안 함'], 'value'=> array_get($providers, $provider.'.activate') ? 'Y' : 'N']) }}
        {{ uio('formText', ['label'=> 'client_id', 'name'=>'client_id', 'value' => array_get($providers, $provider.'.client_id')]) }}
        {{ uio('formText', ['label'=> 'client_secret', 'name'=>'client_secret', 'value' => array_get($providers, $provider.'.client_secret')]) }}

        {{ uio('formText', ['label'=> 'callback url', 'readonly'=>'readonly', 'value' => route('social_login::connect', $provider), 'description'=>'이 callback url을 '.$provider.'의 OAuth API를 설정할 때 사용하세요.']) }}

        <div class="pull-right">
            <button href="{{ route('social_login::settings.provider.show', ['provider'=>$provider]) }}"
                    data-toggle="xe-page" data-target=".__xe_social_login.{{ $provider }}"
                    type="button" class="xe-btn xe-btn-secondary">{{ xe_trans('xe::cancel') }}</button>
            <button type="submit" class="xe-btn xe-btn-primary" data-dismiss="xe-modal">{{ xe_trans('xe::save') }}</button>
        </div>
    </form>
</div>
