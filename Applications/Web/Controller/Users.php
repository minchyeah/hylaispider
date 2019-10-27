<?php

namespace Web\Controller;

class Users extends Base
{
	public function index()
	{
		$this->render('users.html');
	}
}