<?php

namespace Web\Controller;

class Index extends Base
{
	public function index()
	{
		$this->set('sp_domain', '');
		$this->set('domain', '');
		$settings = $this->db()
						->select('skey,svalue')
						->from('pw_spider_settings')
						->where('skey', 'sp_domain')
						->orWhere('skey', 'domain')
						->query();
		if(is_array($settings)){
			foreach ($settings as $set) {
				$this->set($set['skey'], $set['svalue']);
			}
		}
		$this->render('index.html');
	}
}