<?php
// register setting page
Route::settings('social_login', function () {
    Route::get('/', [
        'as' => 'social_login::settings',
        'uses' => 'SettingsController@index',
        'permission' => 'user.setting',
        'settings_menu' => 'user.social_login@default'
    ]);
    Route::group(['prefix'=>'providers'], function(){
        Route::get('{provider}', [
            'as' => 'social_login::settings.provider.show',
            'uses' => 'SettingsController@show',
            'permission' => 'user.setting'
        ]);
        Route::get('{provider}/edit', [
            'as' => 'social_login::settings.provider.edit',
            'uses' => 'SettingsController@edit',
            'permission' => 'user.setting'
        ]);
        Route::put('{provider}', [
            'as' => 'social_login::settings.provider.update',
            'uses' => 'SettingsController@update',
            'permission' => 'user.setting'
        ]);
    });
});

Route::fixed('social_login', function () {
    Route::group(['prefix' => 'login'], function () {
        Route::get('/', ['as' => 'social_login::login', 'uses' => 'ConnectController@login']);
        Route::get('{provider}', ['as' => 'social_login::connect', 'uses' => 'ConnectController@connect',]);
    });
    // register each provider's connect page
    Route::group(['prefix' => 'disconnect', 'middleware' => 'auth'], function () {
        Route::get('{provider}', [
            'as' => 'social_login::disconnect',
            'uses' => 'ConnectController@disconnect',
        ]);
    });
});