<?php
class IndexAction extends Action {

    public function index(){

        //新浪微博登录
        if(!empty($_GET['uid']) && $_GET['type'] == 'weibo'){
            include_once( './saetv2.ex.class.php' );

            $c = new SaeTClientV2(C('WB_AKEY'), C('WB_SKEY'), $_SESSION['token']['access_token']);
            $uid_get = $c->get_uid();
            $uid = $uid_get['uid'];
            $user_message = $c->show_user_by_id($uid);//根据ID获取用户等基本信息
            $this -> assign('user_message', $user_message);
            $this -> assign('type', $this -> _get('type'));
            $this -> assign('uid', $this -> _get('uid'));
        }elseif(!empty($_GET['uid']) && $_GET['type'] == 'tencent'){

            $get_user_info = "https://graph.qq.com/user/get_user_info?" . "access_token=" . $_SESSION['access_token'] . "&oauth_consumer_key=" . $_SESSION["appid"] . "&openid=" . $_SESSION["openid"] . "&format=json";

            $info = file_get_contents($get_user_info);
            $user_message = json_decode($info, true);
            $user_message['name'] = $user_message['nickname'];

            $this -> assign('user_message', $user_message);
            $this -> assign('type', $this -> _get('type'));
            $this -> assign('uid', $this -> _get('uid'));

            //腾讯用户直接登录，不弹层
            //session('tctc_uid', 979);
            //session('tctc_name', $user_message['name']);
            //redirect(_PHP_FILE_ . '/Index/index#sharebox');


        }

        $this -> display();
    }

    public function mobile(){
        $this -> display();
    }

    public function mobile_add(){
        $this -> display();
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
        $data['add_1'] = $this -> _post('add_1');
        $data['add_2'] = $this -> _post('add_2');
        $data['add_3'] = $this -> _post('add_3');
        $data['source'] = $this -> _post('source');
        $data['applyTime'] = time();
        if($uid = $User -> add($data)){

            //自动推送
            $Offline = M('Offline');
            $where = array();
            $where['province'] = $data['add_1'];
            if($data['add_1'] == '北京' || $data['add_1'] == '天津' || $data['add_1'] == '上海' || $data['add_1'] == '重庆'){
                $where['city'] = $where['province'];
            }else{
                $where['city'] = str_replace('市', '', $data['add_2']);
            }
            $where['county'] = $data['add_3'];

            $result_offline = $Offline -> field('uid,name,province,regoin,city,county') -> where($where) -> select();


            //有此经销商则自动推送
            if($result_offline){

                $check = $result_offline[array_rand($result_offline)];


                $post_data = array();
                $post_data['name'] = $data['name'];
                $post_data['sex'] = $data['sex'];
                $post_data['phone'] = $data['tel'];
                $post_data['province'] = $data['add_1'];
                $post_data['city'] = $data['add_2'];
                $post_data['address'] = $data['add_3'];
                $post_data['carstyle'] = 7;//RAV4
                $post_data['havecarstyle'] = '';
                $post_data['time'] = '暂不考虑';
                $post_data['dealers'] = $check['uid'];
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

                /*
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
                */

                if($data == '200'){
                    $data_update = array();
                    $data_update['id'] = $uid;
                    $data_update['status'] = 2;
                    $data_update['province'] = $check['uid'];
                    $User -> save($data_update);
                }
            }

            echo 1;
        }else{
            echo 0;
        }
    }

    public function weibologin(){
        include_once('./saetv2.ex.class.php');
        $o = new SaeTOAuthV2(C('WB_AKEY'), C('WB_SKEY'));
        $code_url = $o->getAuthorizeURL(C('WB_CALLBACK_URL'));
        redirect($code_url);
    }

    public function weibocheck(){
        include_once( './saetv2.ex.class.php' );
        $o = new SaeTOAuthV2(C('WB_AKEY'), C('WB_SKEY'));

        if (isset($_REQUEST['code'])) {
            $keys = array();
            $keys['code'] = $_REQUEST['code'];
            $keys['redirect_uri'] = C('WB_CALLBACK_URL');
            try {
                $token = $o->getAccessToken('code', $keys ) ;
            } catch (OAuthException $e) {
            }
        }

        if ($token) {
            $_SESSION['token'] = $token;
            setcookie('weibojs_'.$o->client_id, http_build_query($token));

            $c = new SaeTClientV2(C('WB_AKEY'), C('WB_SKEY'), $_SESSION['token']['access_token']);
            $uid_get = $c->get_uid();
            if($uid_get['error'] && $uid_get['error_code'] == 21321){
                die('新浪微博登录功能正在等待微博方面审核，请稍后再试试');
            }else if($uid_get['error'] && $uid_get['error_code'] != 21321){
                die($uid_get['error']);
            }else{
                $uid = $uid_get['uid'];
                //查找此uid是否已经绑定账户
                $User = M('User');
                $user_info = $User -> field('id,name') -> where(array('weiboId' => $uid)) -> find();
                //存在此用户直接写session
                if($user_info){
                    session('tctc_uid', $user_info['id']);
                    session('tctc_name', $user_info['name']);
                    redirect(_PHP_FILE_ . '/Index/index#sharebox');
                    //不存在则跳转到首页，并绑定用户
                }else{
                    redirect(_PHP_FILE_ . '/Index/index/uid/' . $uid . '/type/weibo');
                }
            }
        }else{
           die('授权失败');
        }
    }

    public function tencentlogin(){
        //申请到的appid
        $_SESSION["appid"]    = '101058925';
        //申请到的appkey
        $_SESSION["appkey"]   = "938492aa272936763f83622c19fbe64f";
        //QQ登录成功后跳转的地址,请确保地址真实可用，否则会导致登录失败。
        $_SESSION["callback"] = "http://1000kmpacificrav4.tctc.com.cn/Index/tencentcheck";
        //QQ授权api接口.按需调用
        $_SESSION["scope"] = "get_user_info";

        $_SESSION['state'] = md5(uniqid(rand(), TRUE)); //CSRF protection
        $login_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
            . $_SESSION["appid"] . "&redirect_uri=" . urlencode($_SESSION["callback"])
            . "&state=" . $_SESSION['state']
            . "&scope=".$_SESSION["scope"] ;
        header("Location:$login_url");

    }

    public function tencentcheck(){

        //回调验证
        if($_REQUEST['state'] == $_SESSION['state']) //csrf
        {
            $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
                . "client_id=" . $_SESSION["appid"]. "&redirect_uri=" . urlencode($_SESSION["callback"])
                . "&client_secret=" . $_SESSION["appkey"]. "&code=" . $_REQUEST["code"];

            $response = file_get_contents($token_url);
            if (strpos($response, "callback") !== false)
            {
                $lpos = strpos($response, "(");
                $rpos = strrpos($response, ")");
                $response  = substr($response, $lpos + 1, $rpos - $lpos -1);
                $msg = json_decode($response);
                if (isset($msg->error))
                {
                    echo "<h3>error:</h3>" . $msg->error;
                    echo "<h3>msg  :</h3>" . $msg->error_description;
                    exit;
                }
            }

            $params = array();
            parse_str($response, $params);
            $_SESSION["access_token"] = $params["access_token"];

        }
        else
        {
            die("The state does not match. You may be a victim of CSRF.");
        }


        //获取openID
        $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=" . $_SESSION['access_token'];

        $str  = file_get_contents($graph_url);
        if (strpos($str, "callback") !== false)
        {
            $lpos = strpos($str, "(");
            $rpos = strrpos($str, ")");
            $str  = substr($str, $lpos + 1, $rpos - $lpos -1);
        }

        $user = json_decode($str);
        if (isset($user->error))
        {
            echo "<h3>error:</h3>" . $user->error;
            echo "<h3>msg  :</h3>" . $user->error_description;
            exit;
        }

        $openid = $user->openid;
        $_SESSION['openid'] = $openid;


        //查找此uid是否已经绑定账户
        $User = M('User');
        $user_info = $User -> field('id,name') -> where(array('tencentId' => $openid)) -> find();
        //存在此用户直接写session
        if($user_info){
            session('tctc_uid', $user_info['id']);
            session('tctc_name', $user_info['name']);
            redirect(_PHP_FILE_ . '/Index/index#sharebox');
            //不存在则跳转到首页，并绑定用户
        }else{
            redirect(_PHP_FILE_ . '/Index/index/uid/' . $openid . '/type/tencent');
        }

    }

    public function bindingcheck(){
        $User = M('User');
        $where = array();
        $where['name'] = $this -> _post('name');
        $where['tel'] = $this -> _post('tel');
        $user_info = $User -> field('id,name') -> where($where) -> find();
        if(!$user_info){
            echo 1;
            return;
        }
        $data = array();
        $data['id'] = $user_info['id'];
        if($_POST['type'] == 'weibo'){
            $data['weiboId'] = $this -> _post('uid');
        }else{
            $data['tencentId'] = $this -> _post('uid');
        }
        if($User -> save($data)){
            session('tctc_uid', $user_info['id']);
            session('tctc_name', $user_info['name']);
            echo 2;
            return;
        }else{
            echo 3;
            return;
        }
    }

    public function uploads(){
        import('ORG.Net.UploadFile');
        $upload = new UploadFile();// 实例化上传类
        $upload -> maxSize  = 2097152 ;// 小于2M
        $upload -> savePath =  './Uploads/';// 设置附件上传目录
        $upload -> saveRule = 'uniqid';
        $upload -> allowExts  = array('jpg', 'png', 'jpeg');// 设置附件上传类型
        //path
        $upload -> autoSub = true;
        $upload -> subType = 'date';
        $upload -> dateFormat = 'Y-m-d';
        //thumb
        $upload -> thumb = true;
        $upload -> thumbMaxWidth = '246';
        $upload -> thumbMaxHeight = '246';
        $upload -> thumbPrefix = 's_';
//        $upload -> thumbRemoveOrigin = true;
        if(!$upload->upload()) {// 上传错误提示错误信息
            $error_info = $upload->getErrorMsg();
            $this -> show('<script>alert("' . $error_info . '");window.location="http://1000kmpacificrav4.tctc.com.cn/#sharebox";</script>');
        }else{// 上传成功 获取上传文件信息
            $info =  $upload->getUploadFileInfo();
        }
        //大图地址
        $b_img = str_replace('/', '/', $info[0]['savename']);
        //小图地址
        $s_img = str_replace('/', '/s_', $info[0]['savename']);

        $Article = M('Article');
        $add_data = array();
        $add_data['uid'] = $_SESSION['tctc_uid'];
//        $add_data['uid'] = 1;
        $add_data['title'] = $this -> _post('title');
        $add_data['content'] = $this -> _post('content');
        $add_data['type'] = $this -> _post('type');
        $add_data['s_img'] = $s_img;
        $add_data['b_img'] = $b_img;
        $add_data['addtime'] = time();

        if($Article -> add($add_data)){
            $this -> show('<script>alert("您的故事已经提交成功，审核后可见，感谢您的参与！");window.location="http://1000kmpacificrav4.tctc.com.cn";</script>');
        }else{
            //添加失败删除上传文件
            unlink('./Uploads/' . $b_img);
            unlink('./Uploads/' . $s_img);
            $this -> show('<script>alert("数据添加失败，请稍后重试！");window.location="http://1000kmpacificrav4.tctc.com.cn/#sharebox";</script>');
        }

    }


    public function getsharelist(){
        $Article = M('Article');
        $where = array();
        $where['type'] = $this -> _post('type');
        $where['status'] = 2;
        if($_POST['order'] == 'time'){
            $order = 'addtime DESC';
        }else{
            $order = 'zan DESC';
        }

        $page = $_POST['page'] ? $_POST['page'] : 1;
        $start = ($page-1) * 10;

        $count = $Article -> where($where) -> count();

        $result = array();
        $result['data'] = $Article -> field('id,title,s_img,type,addtime') -> where($where) -> order($order) -> limit($start,10) -> select();

        $result['order'] = $_POST['order'];
        $result['type'] = $_POST['type'];
        $result['page'] = $page;
        $result['counts'] = $count;

        foreach($result['data'] as $key => $value){
            $result['data'][$key]['addtime'] = date('Y-m-d', $value['addtime']);
        }

        echo json_encode($result);
    }

    public function getshareinfo(){
        $Article = M('Article');
        $result = $Article -> alias('a') -> field('u.name as uname,a.title,a.type,a.content,a.b_img,a.addtime') -> join('tctc_user as u ON a.uid = u.id') -> where(array('a.id' => $this -> _post('aid'))) -> find();
        $result['addtime'] = date('Y-m-d', $result['addtime']);
        $result['content'] = nl2br($result['content']);
        echo json_encode($result);
    }

    public function tosharezan(){
        $aid = $this -> _post('aid');
        if(in_array($aid, $_SESSION['zanlist'])){
            echo 1;
            return;
        }
        $Article = M('Article');
        if($Article -> where(array('id' => $aid)) -> setInc('zan')){
            $_SESSION['zanlist'][] = $aid;
            echo 2;
            return;
        }else{
            echo 3;
            return;
        }
    }

    public function winners(){
        $this -> display();
    }
}