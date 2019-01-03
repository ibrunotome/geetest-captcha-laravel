<?php

namespace Geetest;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;

/**
 * Class Geetest
 */
class GeetestLib
{
    const GT_SDK_VERSION = 'php_3.0.0';

    const GEETEST_API = '://api.geetest.com/';

    /**
     * @var int $timeout
     */
    public static $timeout = 5;

    /**
     * @var $response
     */
    private $response;

    /**
     * @var $captchaId
     */
    private $captchaId;

    /**
     * @var $privateKey
     */
    private $privateKey;

    /**
     * Geetest constructor.
     *
     * @param $captchaId
     * @param $privateKey
     */
    public function __construct($captchaId, $privateKey)
    {
        $this->captchaId = $captchaId;
        $this->privateKey = $privateKey;
    }

    /**
     * @param array $param
     * @param true  $newCaptcha
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function preProcess(array $param, $newCaptcha = true)
    {
        $data = [
            'gt'          => $this->captchaId,
            'new_captcha' => intval($newCaptcha)
        ];

        $data = array_merge($data, $param);
        $query = http_build_query($data);
        $uri = 'register.php' . '?' . $query;
        $client = new Client([
            'base_uri' => Config::get('geetest.protocol') . self::GEETEST_API,
            'timeout'  => self::$timeout,
        ]);

        $response = $client->request('GET', $uri);

        if ($response->getStatusCode() === 200) {
            $challenge = $response->getBody();

            if (strlen($challenge) === 32) {
                $this->successProcess($challenge);
                return true;
            }
        }

        $this->failProcess();

        return false;
    }

    /**
     * @param $challenge
     */
    private function successProcess($challenge)
    {
        $challenge = md5($challenge . $this->privateKey);
        $result = [
            'success'     => 1,
            'gt'          => $this->captchaId,
            'challenge'   => $challenge,
            'new_captcha' => 1
        ];
        $this->response = $result;
    }


    private function failProcess()
    {
        $rnd1 = md5(rand(0, 100));
        $rnd2 = md5(rand(0, 100));
        $challenge = $rnd1 . substr($rnd2, 0, 2);
        $result = [
            'success'     => 0,
            'gt'          => $this->captchaId,
            'challenge'   => $challenge,
            'new_captcha' => 1
        ];
        $this->response = $result;
    }

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $challenge
     * @param string $validate
     * @param string $seccode
     * @param array  $param
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function successValidate($challenge, $validate, $seccode, $param)
    {
        if (!$this->checkValidate($challenge, $validate)) {
            return false;
        }

        $query = [
            'seccode'     => $seccode,
            'timestamp'   => time(),
            'challenge'   => $challenge,
            'captchaid'   => $this->captchaId,
            'json_format' => 1,
            'sdk'         => self::GT_SDK_VERSION
        ];

        $query = array_merge($query, $param);
        $uri = 'validate.php';
        $client = new Client([
            'base_uri' => Config::get('geetest.protocol') . self::GEETEST_API,
            'timeout'  => self::$timeout,
        ]);

        $options = [
            'form_params' => $query,
        ];

        $response = $client->request('POST', $uri, $options);

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $validate = $response->getBody();
        $obj = json_decode($validate, true);

        if (!$obj) {
            return false;
        }

        return $obj['seccode'] === md5($seccode);
    }

    /**
     * @param $challenge
     * @param $validate
     *
     * @return bool
     */
    private function checkValidate($challenge, $validate)
    {
        if (strlen($validate) !== 32) {
            return false;
        }

        if (md5($this->privateKey . 'geetest' . $challenge) !== $validate) {
            return false;
        }

        return true;
    }

    /**
     * @param $challenge
     * @param $validate
     *
     * @return bool
     */
    public function failValidate($challenge, $validate)
    {
        return md5($challenge) === $validate;
    }

}