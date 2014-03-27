<?php
class OnlineAction extends CommonAction {

  public function index(){
      $where = array();
      if(!empty($_POST['name'])){
          $where['name'] = array('LIKE', '%' . $_POST['name'] . '%');
      }
      R('Public/select', array('User', 'id,name,sex,tel,add_1,add_2,add_3,applyTime,status', $where, 'applyTime DESC'));
      $this -> display();
  }

    public function deluser(){
        R('Public/del', array('User'));
    }

    public function pushDLR(){
        $User = M('User');
        $result = $User -> field('name,sex,tel,add_1,add_2,add_3') -> find($this -> _get('id', 'intval'));
        $this -> assign('result', $result);
        $this -> display();
    }

    public function checkpush(){
        $User = M('User');
        $User -> create();
        $User -> save();

        $post_data = array();
        $post_data['name'] = $_POST['name'];
        $post_data['sex'] = $_POST['sex'];
        $post_data['phone'] = $_POST['tel'];
        $post_data['province'] = $_POST['province'];
        $post_data['city'] = $_POST['city'];
        $post_data['address'] = $_POST['address'];
        $post_data['carstyle'] = 7;//RAV4
        $post_data['havecarstyle'] = '';
        $post_data['time'] = '暂不考虑';
        $post_data['dealers'] = $_POST['dealers'];
        $post_data['source'] = '378';
        $post_data['vercode'] = strtoupper(md5($post_data['name'] . $post_data['phone'] . $post_data['source']));
        $post_data['agreepush'] = '2';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://59.151.103.164/ftdlr/api/acardnew.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "Uniquead To DLR Beta");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $data = curl_exec($ch);
        curl_close($ch);

        $return_code = array(
            '200' => '提交成功',
            '1000' => '系统错误',
            '1001' => '经销商代码为空',
            '1002' => '经销商代码不存在',
            '1003' => '姓名为空',
            '1004' => '性别为空',
            '1005' => 'Email为空',
            '1006' => 'Email格式错误',
            '1007' => '手机号码为空',
            '1008' => '手机号码位数错误',
            '1009' => '信息来源为空',
            '1011' => '没有选择省',
            '1012' => '没有选择市',
            '1013' => '地址为空',
            '1014' => '预约车型为空',
            '1015' => '预约时间为空',
            '2001' => '信息已经存在',
            '2002' => '校验码错误',
        );

        if($data == '200'){
            $data_update = array();
            $data_update['id'] = $_POST['id'];
            $data_update['status'] = 2;
            $User -> save($data_update);
            $this -> success($return_code[$data]);
        }else{
            $this -> error($return_code[$data]);
        }


    }


}
