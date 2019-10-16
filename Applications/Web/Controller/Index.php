<?php

namespace Web\Controller;

class Index extends Base
{
	public function index()
	{
		$this->render('index.html');
	}

	public function login()
	{
		$this->render('login.html');
	}

	public function doLogin()
	{
		$username = 'admin';
		$password = '111111';
		$this->checkLogin($username, $password);
	}
}