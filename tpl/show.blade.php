    <a href="{{ route('social_login::settings.provider.edit', ['provider' => $provider]) }}"
       data-toggle="xe-page" data-target=".__xe_social_login.{{ $provider }}" style="display: block;padding-top:20px;padding-bottom: 20px;">
        <span class="btn-link"><strong>{{ $provider }}</strong></span>
        @if(array_get($info, 'activate'))
            <span class="pull-right xe-badge xe-success">{{xe_trans('xe::inUsed')}}</span>
        @else
            <span class="pull-right xe-badge xe-black">{{xe_trans('xe::notInUsed')}}</span>
        @endif
    </a>
