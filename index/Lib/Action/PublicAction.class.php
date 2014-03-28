<?php
class PublicAction extends Action {

    public function login(){
        $this -> display();
    }

    public function check(){
        $OfflineUser = M('OfflineUser');
        $where_check = array();
        $where_check['username'] = $this -> _post('username');
        $where_check['password'] = $this -> _post('password', 'md5');
        $result = $OfflineUser -> where($where_check) -> find();
        if($result){
            session('offline_user', $result['id']);
            $this -> success('登录成功，欢迎光临', U('Offlinecheck/index'));
        }else{
            $this -> error('登录失败，账号或密码不正确！');
        }
    }

}