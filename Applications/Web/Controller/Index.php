<?php

namespace Web\Controller;

class Index extends Base
{
	public function index()
	{
		$this->render('index.html');
	}
}