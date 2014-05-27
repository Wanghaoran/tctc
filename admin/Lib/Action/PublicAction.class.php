<?php
class PublicAction extends Action {
  public function login(){
    $this -> display();
  }

  public function checkLogin(){
    if(empty($_POST['account'])){
      $this -> error(L('LOGIN_NAME_EMPTY'));
    }else if(empty($_POST['password'])){
      $this -> error(L('LOGIN_PWD_EMPTY'));
    }else if(empty($_POST['verify'])){
      $this -> error(L('LOGIN_VERIFY_EMPTY'));
    }
    if($this -> _session('verify') != $this -> _post('verify', 'md5')){
      $this -> error(L('VERIFY_ERROR'));
    }
    $admin = M('Admin');
    $where = array();
    $where['name'] = $this -> _post('account');
    $result = $admin -> field('id,name,password,last_login_time,last_login_ip,logincount') -> where($where) -> find();
    if(!$result){
      $this -> error(L('NAME_ERROR'));
    }
    if($result['password'] != $this -> _post('password', 'md5')){
      $this -> error(L('PASSWORD_ERROR'));
    }
    session(C('USER_AUTH_KEY'), $result['id']);
    session('last_login_time', $result['last_login_time']);
    session('last_login_ip', $result['last_login_ip']);
    session('logincount', $result['logincount']);
    session('name', $result['name']);
    $data = array();
    $data['last_login_time'] = time();
    $data['last_login_ip'] = get_client_ip();
    $data['logincount'] = $result['logincount']+1;
    $data['id'] = session(C('USER_AUTH_KEY'));
    M('AdminLoginLog') -> add(array('aid' => $result['id'], 'login_time' => $data['last_login_time'], 'login_ip' => $data['last_login_ip']));
    if(!$admin -> save($data)){
      $this -> error(L('LOGIN_ERROR'));
    }
    $this -> success(L('LOGIN_SUCCESS'), U('Index/index'));
  }

  public function main(){
   $info = array(
     '操作系统'=>PHP_OS,
     '运行环境'=>$_SERVER["SERVER_SOFTWARE"],
     'PHP运行方式'=>php_sapi_name(),
     'ThinkPHP版本'=>THINK_VERSION.' [ <a href="http://thinkphp.cn" target="_blank">查看最新版本</a> ]',
     '上传附件限制'=>ini_get('upload_max_filesize'),
     '执行时间限制'=>ini_get('max_execution_time').'秒',
     '服务器时间'=>date("Y年n月j日 H:i:s"),
     '北京时间'=>gmdate("Y年n月j日 H:i:s",time()+8*3600),
     '服务器域名/IP'=>$_SERVER['SERVER_NAME'].' [ '.gethostbyname($_SERVER['SERVER_NAME']).' ]',
     '剩余空间'=>round((@disk_free_space(".")/(1024*1024)),2).'M',
     'register_globals'=>get_cfg_var("register_globals")=="1" ? "ON" : "OFF",
     'magic_quotes_gpc'=>(1===get_magic_quotes_gpc())?'YES':'NO',
     'magic_quotes_runtime'=>(1===get_magic_quotes_runtime())?'YES':'NO',
   );
   $this->assign('info', $info);
   $this->display();  
  }

  public function password(){
    $this -> display(); 
  }

  public function changepwd(){
    if(empty($_POST['oldpassword'])){
      $this -> error(L('OLDPASSWORD_EMPTY'));
    }else if(empty($_POST['password'])){
      $this -> error(L('NEWPASSWORD_EMPTY'));
    }else if(empty($_POST['repassword'])){
      $this -> error(L('REPASSWORD_EMPTY'));
    }else if($_POST['password'] != $_POST['repassword']){
      $this -> error(L('PASSWORD_NEQ'));
    }else if(md5($_POST['verify']) != $_SESSION['verify']){
      $this -> error(L('VERIFY_ERROR'));
    }
    $cond = array();
    $cond['password'] = $this -> _post('oldpassword', 'md5');
    $cond['id'] = session(C('USER_AUTH_KEY'));
    $admin = M('Admin');
    if(!$admin -> field('id') -> where($cond) -> find()){
      $this -> error(L('OLDPASSWORD_ERROR'));
    }
    $cond['password'] = $this -> _post('password', 'md5');
    if($admin -> save($cond)){
      $this -> success(L('CHANGE_PWD_SUCCESS'));
    }else{
      $this -> error(L('CHANGE_PWD_ERROR'));
    }
  }

  public function logout(){
    session(C('USER_AUTH_KEY'), null);
    session(null);
    session('[destroy]');
    $this -> success(L('LOGOUT_SUCCESS'), U(C('USER_AUTH_GATEWAY')));
  }

  public function verify(){
    import('ORG.Util.Image');
    Image::buildImageVerify();
  }

  public function add($modelName){
    $model = D($modelName);
    if(!$model -> create()){
      $this -> error($model -> getError());
    }
    if($model -> add()){
      $this -> success(L('DATA_ADD_SUCCESS'));
    }else{
      $this -> error(L('DATA_ADD_ERROR'));
    }
  }

  public function del($modelName){
    $where_del = array();
    $where_del['id'] = array('in', $_POST['ids']);
    $model = D($modelName);
    if($model -> where($where_del) -> delete()){
      $this -> success(L('DATA_DELETE_SUCCESS'));
    }else{
      $this -> error(L('DATA_DELETE_ERROR'));
    }
  }

  public function edit($modelName, $field='', $id=''){
    $model = D($modelName);
    $result = $model -> field($field) -> find($id);
    $this -> assign('result', $result);
  }

  public function checkedit($modelName){
    $model = D($modelName);
    if(!$model -> create()){
      $this -> error($model -> getError());
    }
    if($model -> save()){
      $this -> success(L('DATA_UPDATE_SUCCESS'));
    }else{
      $this -> error(L('DATA_UPDATE_ERROR'));
    }
  }

  public function select($modelName, $field = '', $where = array(), $order = '', $join = '', $alias = ''){
    $model = M($modelName);
    import('ORG.Util.Page');
    $count = $model -> alias($alias) -> join($join) -> where($where)-> count();
    if(! empty ( $_REQUEST ['listRows'] )){
      $listRows = $_REQUEST ['listRows'];
    } else {
      $listRows = 15;
    }
    $page = new Page($count, $listRows);
    $pageNum = !empty($_REQUEST['pageNum']) ? $_REQUEST['pageNum'] : 1;
    $page -> firstRow = ($pageNum - 1) * $listRows;
    $result = $model -> alias($alias) -> field($field) -> where($where) -> limit($page -> firstRow . ',' . $page -> listRows) -> join($join) -> order($order) -> select();
    $this -> assign('result', $result);
    $this -> assign('listRows', $listRows);
    $this -> assign('currentPage', $pageNum);
    $this -> assign('count', $count);
    return array('count' => $count, 'result' => $result);
  }

    //批量数据导入
    public function excelinput(){
        //导入类库
        Vendor('PHPExcel.IOFactory');
        $path = './Public/input2.xls';
        $fileType = PHPExcel_IOFactory::identify($path); //文件名自动判断文件类型
        $objReader = PHPExcel_IOFactory::createReader($fileType);

        $objPHPExcel = $objReader->load($path);

        $currentSheet = $objPHPExcel->getSheet(0); //第一个工作簿
//        $allRow = $currentSheet->getHighestRow(); //行数

        //$sex_arr = array('男', '男', '男', '男', '女');

/*
       $json_ad = '[{"name":"江苏", "cityList":[
{"name":"南京市", "areaList":["市辖区","玄武区","白下区","秦淮区","建邺区","鼓楼区","下关区","浦口区","栖霞区","雨花台区","江宁区","六合区","溧水县","高淳县"]},
{"name":"无锡市", "areaList":["市辖区","崇安区","南长区","北塘区","锡山区","惠山区","滨湖区","江阴市","宜兴市"]},
{"name":"徐州市", "areaList":["市辖区","鼓楼区","云龙区","九里区","贾汪区","泉山区","铜山县","睢宁县","新沂市","邳州市"]},
{"name":"常州市", "areaList":["市辖区","天宁区","钟楼区","戚墅堰区","新北区","武进区","溧阳市","金坛市"]},
{"name":"苏州市", "areaList":["市辖区","沧浪区","平江区","金阊区","虎丘区","吴中区","相城区","常熟市","张家港市","昆山市","吴江市","太仓市"]},
{"name":"南通市", "areaList":["市辖区","崇川区","港闸区","海安县","如东县","启东市","如皋市","通州市","海门市"]},
{"name":"连云港市", "areaList":["市辖区","连云区","新浦区","海州区","赣榆县","东海县","灌云县","灌南县"]},
{"name":"淮安市", "areaList":["市辖区","清河区","楚州区","淮阴区","清浦区","涟水县","洪泽县","盱眙县","金湖县"]},
{"name":"盐城市", "areaList":["市辖区","亭湖区","盐都区","响水县","滨海县","阜宁县","射阳县","建湖县","东台市","大丰市"]},
{"name":"扬州市", "areaList":["市辖区","广陵区","邗江区","宝应县","仪征市","高邮市","江都市"]},
{"name":"镇江市", "areaList":["市辖区","京口区","润州区","丹徒区","丹阳市","扬中市","句容市"]},
{"name":"泰州市", "areaList":["市辖区","海陵区","高港区","兴化市","靖江市","泰兴市","姜堰市"]},
{"name":"宿迁市", "areaList":["市辖区","宿城区","宿豫区","沭阳县","泗阳县","泗洪县"]}
]},
{"name":"陕西", "cityList":[
{"name":"西安市", "areaList":["市辖区","新城区","碑林区","莲湖区","灞桥区","未央区","雁塔区","阎良区","临潼区","长安区","蓝田县","周至县","高陵县"]},
{"name":"铜川市", "areaList":["市辖区","王益区","印台区","耀州区","宜君县"]},
{"name":"宝鸡市", "areaList":["市辖区","渭滨区","金台区","陈仓区","凤翔县","岐山县","扶风县","千阳县","麟游县","太白县"]},
{"name":"咸阳市", "areaList":["市辖区","秦都区","杨凌区","渭城区","三原县","泾阳县","礼泉县","永寿县","长武县","旬邑县","淳化县","武功县","兴平市"]},
{"name":"渭南市", "areaList":["市辖区","临渭区","潼关县","大荔县","合阳县","澄城县","蒲城县","白水县","富平县","韩城市","华阴市"]},
{"name":"延安市", "areaList":["市辖区","宝塔区","延长县","延川县","子长县","安塞县","志丹县","吴旗县","甘泉县","洛川县","宜川县","黄龙县","黄陵县"]},
{"name":"汉中市", "areaList":["市辖区","汉台区","南郑县","城固县","西乡县","宁强县","略阳县","镇巴县","留坝县","佛坪县"]},
{"name":"榆林市", "areaList":["市辖区","榆阳区","神木县","府谷县","横山县","靖边县","定边县","绥德县","米脂县","吴堡县","清涧县","子洲县"]},
{"name":"安康市", "areaList":["市辖区","汉滨区","汉阴县","石泉县","宁陕县","紫阳县","岚皋县","平利县","镇坪县","旬阳县","白河县"]},
{"name":"商洛市", "areaList":["市辖区","商州区","洛南县","丹凤县","商南县","山阳县","镇安县","柞水县"]}
]},
{"name":"浙江", "cityList":[
{"name":"杭州市", "areaList":["市辖区","上城区","下城区","江干区","拱墅区","西湖区","滨江区","萧山区","余杭区","桐庐县","淳安县","建德市","富阳市","临安市"]},
{"name":"宁波市", "areaList":["市辖区","海曙区","江东区","江北区","北仑区","镇海区","鄞州区","象山县","宁海县","余姚市","慈溪市","奉化市"]},
{"name":"温州市", "areaList":["市辖区","鹿城区","龙湾区","瓯海区","洞头县","永嘉县","平阳县","苍南县","文成县","泰顺县","瑞安市","乐清市"]},
{"name":"嘉兴市", "areaList":["市辖区","秀城区","秀洲区","嘉善县","海盐县","海宁市","平湖市","桐乡市"]},
{"name":"湖州市", "areaList":["市辖区","吴兴区","南浔区","德清县","长兴县","安吉县"]},
{"name":"绍兴市", "areaList":["市辖区","越城区","绍兴县","新昌县","诸暨市","上虞市","嵊州市"]},
{"name":"金华市", "areaList":["市辖区","婺城区","金东区","武义县","浦江县","磐安县","兰溪市","义乌市","东阳市","永康市"]},
{"name":"衢州市", "areaList":["市辖区","柯城区","衢江区","常山县","开化县","龙游县","江山市"]},
{"name":"舟山市", "areaList":["市辖区","定海区","普陀区","岱山县","嵊泗县"]},
{"name":"台州市", "areaList":["市辖区","椒江区","黄岩区","路桥区","玉环县","三门县","天台县","仙居县","温岭市","临海市"]},
{"name":"丽水市", "areaList":["市辖区","莲都区","青田县","缙云县","遂昌县","松阳县","云和县","庆元县","景宁畲族自治县","龙泉市"]}
]}
]';
*/

//        $ad_arr = json_decode($json_ad, true);

        $User = M('User');
        $User_data = array();
        $success_arr = array();
        $error_arr = array();

        for($i = 1; $i <= 350; $i++){
            $User_data['name'] = $currentSheet -> getCell('B' . $i) -> getValue();
//            $User_data['sex'] = $sex_arr[rand(0,4)];
            $User_data['sex'] = $currentSheet -> getCell('C' . $i) -> getValue();
            $User_data['tel'] = $currentSheet -> getCell('D' . $i) -> getValue();
            //address
//            $add_1 = array_rand($ad_arr);
//            $add_2 = array_rand($ad_arr[$add_1]['cityList']);
//            $add_3 = array_rand($ad_arr[$add_1]['cityList'][$add_2]['areaList']);
//            $User_data['add_1'] = $ad_arr[$add_1]['name'];
//            $User_data['add_2'] = $ad_arr[$add_1]['cityList'][$add_2]['name'];
//            $User_data['add_3'] = $ad_arr[$add_1]['cityList'][$add_2]['areaList'][$add_3];
              $User_data['add_1'] = $currentSheet -> getCell('E' . $i) -> getValue();
              $User_data['add_2'] = $currentSheet -> getCell('F' . $i) -> getValue();
              $User_data['add_3'] = $currentSheet -> getCell('G' . $i) -> getValue();
            //time
            $User_data['applyTime'] = mktime(rand(6,22), rand(0,59), rand(0,59), 5, 26, 2014);
            $User_data['source'] = 'web页面';
            if($uid = $User -> add($User_data)){
                $success_arr[] = $uid;
            }else{
                $error_arr[] = $User_data['name'];
            }
        }

        dump($success_arr);
        dump($error_arr);

    }


    public function clearnopush(){
        $User = M('User');
        $result = $User -> field('id,name,sex,tel,add_1,add_2,add_3') -> where('status=1') -> select();

        $success_num = 0;
        $error_num = 0;


        foreach($result as $value){


            //自动推送
            $Offline = M('Offline');
            $where = array();
            $where['province'] = $value['add_1'];
            if($value['add_1'] == '北京' || $value['add_1'] == '天津' || $value['add_1'] == '上海' || $value['add_1'] == '重庆'){
                $where['city'] = $where['province'];
            }else{
                $where['city'] = str_replace('市', '', $value['add_2']);
            }
            $where['county'] = $value['add_3'];

            $result_offline = $Offline -> field('uid,name,province,regoin,city,county') -> where($where) -> select();


            if($result_offline){

                $check = $result_offline[array_rand($result_offline)];


                $post_data = array();
                $post_data['name'] = $value['name'];
                $post_data['sex'] = $value['sex'];
                $post_data['phone'] = $value['tel'];
                $post_data['province'] = $value['add_1'];
                $post_data['city'] = $value['add_2'];
                $post_data['address'] = $value['add_3'];
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
                    $data_update['id'] = $value['id'];
                    $data_update['status'] = 2;
                    $data_update['province'] = $check['uid'];
                    $User -> save($data_update);
                    $success_num ++;
                }else{
                    $error_num ++;
                }
            }
        }

        echo '成功:'.$success_num . '，失败：'.$error_num;
    }


}
