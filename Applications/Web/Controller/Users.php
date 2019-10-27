<?php

namespace Web\Controller;

class Users extends Base
{
	public function index()
	{
		$this->render('users.html');
	}

	public function data()
	{
		if (isset($_GET['page']) && intval($_GET['page'])>0) {
			$page = intval($_GET['page']);
		}else{
			$page = 1;
		}
		if (isset($_GET['limit']) && intval($_GET['limit'])>0) {
			$limit = intval($_GET['limit']);
		}else{
			$limit = 15;
		}
		// init response data
		$data = ['code' => 0, 'msg' => ''];

		$count = $this->db()->select('COUNT(id)')
					->from('pw_spider_authors')->single();
		$data['count'] = $count;

		$rows = $this->db()
					->select('id,author_id,author,sp_author,add_time,state')
					->from('pw_spider_authors')
					->setPaging($limit)->page($page)
					->order('id DESC')->query();
		$data['data'] = $rows;
		echo json_encode($data);
	}
}