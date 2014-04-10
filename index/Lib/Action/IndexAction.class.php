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
            $arr = json_decode($info, true);

            dump($arr);
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
        if($User -> add($data)){
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
        $this -> show('<script>alert("您的故事已经提交成功，审核后可见，感谢您的参与！");window.location="http://1000kmpacificrav4.tctc.com.cn";</script>');
//        $this -> display();
    }
}