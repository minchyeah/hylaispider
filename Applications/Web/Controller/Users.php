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
			$limit = 100;
		}
		// init response data
		$data = ['code' => 0, 'msg' => ''];

		$this->db()->select('COUNT(id)')->from('pw_spider_authors');
		$this->search();
		$count = $this->db()->single();
		$data['count'] = $count;

		$this->db()
			->select('id,author_id,author,sp_author,add_time,state')
			->from('pw_spider_authors');
		$this->search();
		$rows = $this->db()->setPaging($limit)->page($page)
					->order('id DESC')->query();
		if(is_array($rows) && !empty($rows)){
			foreach ($rows as &$row) {
				$row['add_time'] = date('Y-m-d H:i', $row['add_time']);
			}
		}
		$data['data'] = $rows;
		$this->json($data);
	}

	private function search()
	{
		if (isset($_GET['author']) && $_GET['author'] !== '') {
			$this->db()->where('author', trim(strval($_GET['author'])));
		}

		if (isset($_GET['sp_author']) && $_GET['sp_author'] !== '') {
			$this->db()->where('sp_author', trim(strval($_GET['sp_author'])));
		}
	}

	public function save()
	{
		$id = intval($_POST['id']);

		$sp_author = trim(strval($_POST['sp_author']));
		$sp_row = $this->db()
					->select('id')
					->from('pw_spider_authors')
					->where('sp_author', $sp_author)
					->row();
		if(isset($sp_row['id']) && $id != $sp_row['id']){
			$this->json(['code' => 99, 'msg' => '采集用户已存在']);
		}

		$author = trim(strval($_POST['author']));
		$row = $this->db('master')
					->select('uid,username')
					->from('pw_members')
					->where('username', $author)
					->row();
		if(!isset($row['uid'])){
			$this->json(['code' => 99, 'msg' => '发布用户不存在']);
		}

		$exrow = $this->db()
					->select('id')
					->from('pw_spider_authors')
					->where('author_id', $row['uid'])
					->row();
		if(isset($exrow['id']) && $id != $exrow['id']){
			$this->json(['code' => 99, 'msg' => '发布用户已存在']);
		}
		
	    $data = [
	        'author_id' => $row['uid'],
	        'author' => $row['username'],
	        'sp_author' => $sp_author,
	        'state' => 1
	    ];
	    if($id > 0){
	    	$rs = $this->db()->update('pw_spider_authors')->setCols($data)->where('id', $id)->query();
	    }else{
	    	$data['add_time'] = time();
	    	$rs = $this->db()->insert('pw_spider_authors')->cols($data)->query();
	    }

	    if($rs){
			$this->json(['code' => 0, 'msg' => '保存成功']);
	    }else{
			$this->json(['code' => 89, 'msg' => '保存失败']);
	    }
	}

	public function delete()
	{
		$id = intval($_POST['id']);
		$rs = $this->db()->delete('pw_spider_authors')->where('id', $id)->query();
	    if($rs){
			$this->json(['code' => 0, 'msg' => '操作成功']);
	    }else{
			$this->json(['code' => 89, 'msg' => '操作失败']);
	    }
	}
}



