<?php

namespace Web\Controller;

class Symbol extends Base
{
	public function index()
	{
		$this->set('data', json_encode(array_values($this->globaldata->symbols)));
		$this->render('symbol.html');
	}
}