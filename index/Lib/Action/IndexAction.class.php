<?php
class IndexAction extends Action {

    public function index(){
        $this -> display();
    }

    public function mobile(){
        $this -> show('<h1>Mobile Page 稍后上线，敬请期待!</h1>');
    }

    public function add(){
        $User = M('User');
        $data = array();
        $data['name'] = $this -> _post('name');
        $data['sex'] = $this -> _post('sex');
        $data['tel'] = $this -> _post('tel');
        $data['add_1'] = $this -> _post('add_1');
        $data['add_2'] = $this -> _post('add_2');
        $data['add_3'] = $this -> _post('add_3');
        $data['applyTime'] = time();
        if($User -> add($data)){
            echo 1;
        }else{
            echo 0;
        }
    }

}