<?php

namespace Geetest;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class GeetestServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config.php' => config_path('geetest.php'),
        ], 'geetest');

        Route::get(Config::get('geetest.url'), 'Geetest\GeetestController@getGeetest');

        Validator::extend('geetest', function () {
            $request = \request();
            $req = $request->only('geetest_key', 'geetest_challenge', 'geetest_validate', 'geetest_seccode');

            [$geetest_key, $challenge, $validate, $seccode] = array_values($req);

            $key = sprintf(Config::get('geetest.cache_key'), $geetest_key);
            $cache = Cache::get($key);

            $data = [
                'user_id'     => $cache['user_id'],
                'client_type' => 'web',
                'ip_address'  => $request->ip()
            ];

            if ($cache['status'] === true) {
                $result = Geetest::successValidate($challenge, $validate, $seccode, $data);
            } else {
                $result = Geetest::failValidate($challenge, $validate);
            }

            return $result;
        }, 'Invalid captcha challenge. Are you human? Try again.');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('geetest', function () {
            $parameters = [
                'captchaId'  => Config::get('geetest.id'),
                'privateKey' => Config::get('geetest.key'),
            ];

            return $this->app->make(GeetestLib::class, $parameters);
        });
    }
}
