<?php

namespace Geetest;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

trait GeetestCaptcha
{
    /**
     * Get geetest.
     */
    public function getGeetest()
    {
        $data = [
            'user_id'     => uniqid(),
            'client_type' => 'web',
            'ip_address'  => Request::ip()
        ];

        $status = Geetest::preProcess($data, true);
        $result = Geetest::getResponse();
        $result['geetest_key'] = uniqid(Str::random(), true);
        $key = sprintf(Config::get('geetest.cache_key'), $result['geetest_key']);

        Cache::put($key, [
            'status'  => $status,
            'user_id' => $data['user_id'],
        ], Config::get('geetest.cache_seconds', 300));

        return $result;
    }
}
