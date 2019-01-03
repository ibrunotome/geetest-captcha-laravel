<?php

namespace Geetest;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;

class GeetestController extends Controller
{
    use GeetestCaptcha;

	public function __construct()
	{
        $this->middleware(Config::get('geetest.middleware'));
	}

}