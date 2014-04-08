<?php
class IndexAction extends Action {

    public function index(){
        if(!empty($_GET['uid'])){
            dump($_GET['uid']);
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
                    redirect('Index/index');
                    //不存在则跳转到首页，并绑定用户
                }else{
                    redirect('Index/index/uid' . $uid);
                }
            }
        }else{
           die('授权失败');
        }
    }
}