<?php
class IndexAction extends Action {

    public function index(){
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
            $uid = $uid_get['uid'];
            dump($uid);
        }else{
           die('授权失败');
        }
    }
}