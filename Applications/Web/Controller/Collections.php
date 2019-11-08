<?php

namespace Web\Controller;

class Collections extends Base
{
	public function index()
	{
		$this->render('collections.html');
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
			$limit = 100;
		}
		// init response data
		$data = ['code' => 0, 'msg' => ''];

		$this->db()->select('COUNT(id)')->from('pw_spider');
		$this->search();
		$count = $this->db()->single();
		$data['count'] = $count;

		$this->db()
			->select('id,tid,url,author,subject,post_time,spide_time,state,new_tid,new_author,new_post_time,new_url,new_state')
			->from('pw_spider');
		$this->search();
		$rows = $this->db()->setPaging($limit)->page($page)
					->order('id DESC')->query();
		if(is_array($rows) && !empty($rows)){
			foreach ($rows as &$row) {
				$row['post_time'] = date('m-d H:i', $row['post_time']);
				$row['spide_time'] = date('m-d H:i', $row['spide_time']);
				$row['new_post_time'] = $row['new_post_time'] ? date('m-d H:i', $row['new_post_time']) : '-';
			}
		}
		$data['data'] = $rows;
		$this->json($data);
	}

	private function search()
	{
		if (isset($_GET['state']) && $_GET['state'] !== '') {
			$state = intval($_GET['state']);
			$this->db()->where('state', $state < 0 ? 0 : $state);
		}

		if (isset($_GET['author']) && $_GET['author'] !== '') {
			$this->db()->where('author', trim(strval($_GET['author'])));
		}

		if (isset($_GET['sp_author']) && $_GET['sp_author'] !== '') {
			$this->db()->where('sp_author', trim(strval($_GET['sp_author'])));
		}
	}
}