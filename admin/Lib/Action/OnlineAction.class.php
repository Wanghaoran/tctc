<?php
class OnlineAction extends CommonAction {

  public function index(){
      $where = array();
      if(!empty($_POST['name'])){
          $where['u.name'] = array('LIKE', '%' . $_POST['name'] . '%');
      }

      if(!empty($_POST['tel'])){
          $where['u.tel'] = array('LIKE', '%' . $_POST['tel'] . '%');
      }

      if(!empty($_POST['status'])){
          $where['u.status'] = $_POST['status'];
      }

      R('Public/select', array('User', 'u.id,u.name,u.sex,u.tel,u.add_1,u.add_2,u.add_3,u.applyTime,u.status,u.offline,u.source,o.name as oname,u.offlineTime,u.province,u.weiboId,u.tencentId', $where, 'applyTime DESC', 'tctc_offline as o ON u.province = o.uid', 'u'));
      $User = M('User');
      $no_push = $User -> where('status=1') -> count();
      $this -> assign('no_push', $no_push);
      $no_check = $User -> where('offline=1') -> count();
      $this -> assign('no_check', $no_check);
      $this -> display();
  }

    public function deluser(){
        R('Public/del', array('User'));
    }

    public function pushDLR(){
        $User = M('User');
        $result = $User -> field('name,sex,tel,add_1,add_2,add_3,province') -> find($this -> _get('id', 'intval'));
        $this -> assign('result', $result);
        //Find Offline
        $Offline = M('Offline');
        $where = array();
        $where['province'] = $result['add_1'];
        if($result['add_1'] == '北京' || $result['add_1'] == '天津' || $result['add_1'] == '上海' || $result['add_1'] == '重庆'){
            $where['city'] = $where['province'];
        }else{
            $where['city'] = str_replace('市', '', $result['add_2']);
        }

        $result_offline = $Offline -> field('uid,name,province,regoin,city,county') -> where($where) -> select();

        $this -> assign('result_offline', $result_offline);
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
        $post_data['source'] = '379';
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

        $patterns = "/\d+/";
        preg_match_all($patterns,$data ,$arr);
        $data = $arr[0][0];

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
            $data_update['province'] = $_POST['dealers'];
            $User -> save($data_update);
            $this -> success($return_code[$data]);
        }else{
            $this -> error($return_code[$data]);
        }
    }


    public function share(){
        $where = array();
        if(!empty($_POST['name'])){
            $where['u.name'] = array('LIKE', '%' . $_POST['name'] . '%');
        }
        R('Public/select', array('Article', 'a.id,a.title,a.type,a.content,a.addtime,a.status,a.zan,u.name as uname', $where, 'addtime DESC', 'tctc_user as u ON a.uid = u.id', 'a'));
        $this -> display();
    }

    public function delshare(){
        $where = array();
        $where['id'] = array('in', $_POST['ids']);
        $result = M('Article') -> field('b_img,s_img') -> where($where) -> select();
        foreach($result as $value){
            unlink('./Uploads/' . $value['b_img']);
            unlink('./Uploads/' . $value['s_img']);
        }
        R('Public/del', array('Article'));
    }

    public function checkshare(){
        $Article = M('Article');
        if(!empty($_POST['id'])){
            if(!$Article -> create()){
                $this -> error($Article -> getError());
            }
            if($Article -> save()){
                $this -> success(L('DATA_UPDATE_SUCCESS'));
            }else{
                $this -> error(L('DATA_UPDATE_ERROR'));
            }
        }
        $info = $Article -> field('title,content,b_img,status') -> find($this -> _get('id', 'intval'));
        $this -> assign('info', $info);
        $this -> display();
    }


}
