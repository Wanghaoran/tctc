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

    public function statistics(){
        $User = M('User');
        $apply_num = $User -> query("SELECT count(*) as num,from_unixtime(`applyTime`, '%Y-%m-%d') as time FROM tctc_user GROUP BY from_unixtime(`applyTime`, '%Y-%m-%d')");

        $apply_str = '';
        foreach($apply_num as $value){
            $apply_str .= '<tr align="center"><td>' . $value["time"] . '</td><td>' . $value["num"] . '</td></tr>';
        }

        $this -> show('<h1>数据统计</h1>');
        $this -> show('<h3>每日申请人数</h3>');
        $this -> show('<table border="1"><tr><th>日期</th><th>申请人数</th></tr>' . $apply_str . '</table>');

        $office_num = $User -> query("SELECT count(*) as num,from_unixtime(`applyTime`, '%Y-%m-%d') as time FROM tctc_user WHERE offline=2 GROUP BY from_unixtime(`applyTime`, '%Y-%m-%d')");
        $office_str = '';
        foreach($office_num as $value){
            $office_str .= '<tr align="center"><td>' . $value["time"] . '</td><td>' . $value["num"] . '</td></tr>';
        }

        $this -> show('<h3>每日线下验证人数</h3>');
        $this -> show('<table border="1"><tr><th>日期</th><th>申请人数</th></tr>' . $office_str . '</table>');

    }



}