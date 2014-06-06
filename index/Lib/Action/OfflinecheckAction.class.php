<?php
class OfflinecheckAction extends Action {

    public function _initialize(){
        $this -> show('<h1>活动已结束！</h1>');
        exit;
        if(!$_SESSION['offline_user']){
            redirect(PHP_FILE . '/Public/login');
        }
    }

    public function index(){
        $this -> display();
    }

    public function sign(){
        $this -> display();
    }

    public function getoffline(){
        $keyword = $this -> _post('keyword');
        $Offline = M('Offline');
        $where = array();
        $where['uid'] = array('LIKE', '%' . $keyword . '%');
        $where['name'] = array('LIKE', '%' . $keyword . '%');
        $where['_logic'] = 'OR';
        $result = $Offline -> where($where) -> select();

        $return_arr = array();
        foreach($result as $value){
            $return_arr['data'][]['title'] = $value['uid'] . '(' . $value['name'] . ')';
        }
        echo json_encode($return_arr);
    }

    public function add(){
        $User = M('User');
        $re = $User -> getFieldBytel($this -> _post('tel'), 'id');
        if($re){
            echo 2;
            return;
        }
        $data = array();
        $data['name'] = $this -> _post('name');
        $data['sex'] = $this -> _post('sex');
        $data['tel'] = $this -> _post('tel');
        $data['email'] = $this -> _post('email');
        $code = preg_replace( '/\((.*)\)/', '',$_POST['code']);
        $data['province'] = $code;
        $data['number'] = $this -> _post('number');
        $data['offline'] = 2;
        $data['offlineTime'] = time();


        //地址信息从经销商表获取
        $add_info = M('Offline') -> field('province,city,county') -> where(array('uid' => $code)) -> find();


        $data['add_1'] = $add_info['province'];
        $data['add_2'] = $add_info['city'];
        $data['add_3'] = $add_info['county'];


        $data['source'] = $this -> _post('source');
        $data['applyTime'] = time();

        if($User -> add($data)){
            echo 1;
        }else{
            echo 0;
        }
    }

    public function offlinecheck(){
        $where = array();
        $where['name'] = $this -> _post('username');
        $where['tel'] = $this -> _post('tel');
        $result = M('User') -> where($where) -> find();
        echo json_encode($result);
    }

    public function validation(){
        $User = M('User');
        $check = $User -> field('offline,status') -> where(array('id' => $this -> _get('uid', 'intval'))) -> find();
        if($check['offline'] == 2){
            $this -> error('此用户已通过验证，请勿重复验证！');
        }
        if($check['status'] == 1){
            $this -> error('此用户还未推送至DLR接口，无法验证！');
        }
        $result = $User -> alias('u') -> field('u.id,u.name,u.tel,u.sex,u.email,u.province,o.name as oname') -> join('tctc_offline as o ON u.province = o.uid') -> where(array('u.id' => $this -> _get('uid', 'intval'))) -> find();
        $this -> assign('result', $result);
        $this -> display();
    }

    public function tovalidation(){
        $User = M('User');
        $data = array();
        $data['id'] = $this -> _post('uid', 'intval');
        $data['number'] = $this -> _post('number');
        $data['offlineTime'] = time();
        $data['offline'] = 2;
        if($User -> save($data)){
            echo '1';
        }else{
            echo '2';
        }
    }

}