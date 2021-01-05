<?php
// 应用公共文件

//返回json数据格式
function my_json($data = [],$code=0,$msg = "OK",$httpCode = 200){
    $result = [
        'code'=>$code,
        'msg'=>$msg,
        'data'=>$data,
    ];
    return json($result,$httpCode);
}

function deldir($dir) {
//先删除目录下的文件：
    $dh=opendir($dir);
    while ($file=readdir($dh)) {
        if($file!="." && $file!="..") {
            $fullpath=$dir."/".$file;
            if(!is_dir($fullpath)) {
                unlink($fullpath);
            } else {
                deldir($fullpath);
            }
        }
    }

    closedir($dh);
    //删除当前文件夹：
    if(rmdir($dir)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 数据签名认证
 * @param  array  $data 被认证的数据
 * @return string       签名
 */
function data_auth_sign($data) {
    //数据类型检测
    if(!is_array($data)){
        $data = (array)$data;
    }
    ksort($data); //排序
    $code = http_build_query($data); //url编码并生成query字符串
    $sign = sha1($code); //生成签名
    return $sign;
}

//html代码输入
function html_in($str){
    $str=htmlspecialchars($str);
    $str=strip_tags($str);
    if(!get_magic_quotes_gpc()) {
        $str = addslashes($str);
    }

    return $str;
}

//上传文件黑名单过滤
function upload_replace($str){
    $farr = ["/php|php3|php4|php5|phtml|pht|/is"];
    $str = preg_replace($farr,'',$str);
    return $str;
}


/**
 * 过滤掉空的数组
 * @access protected
 * @param  array        $data     数据
 * @return array
 */
function filterEmptyArray($data = []){
    foreach( $data as $k=>$v){
        if( !$v && $v !== 0)
            unset( $data[$k] );
    }
    return $data;
}

//导出excel表头设置
function getTag($key3,$no=100){
    $data=[];
    $key = ord("A");//A--65
    $key2 = ord("@");//@--64
    for($n=1;$n<=$no;$n++){
        if($key>ord("Z")){
            $key2 += 1;
            $key = ord("A");
            $data[$n] = chr($key2).chr($key);//超过26个字母时才会启用
        }else{
            if($key2>=ord("A")){
                $data[$n] = chr($key2).chr($key);//超过26个字母时才会启用
            }else{
                $data[$n] = chr($key);
            }
        }
        $key += 1;
    }
    return $data[$key3];
}

//html代码输出
function html_out($str){
    if(is_string($str)){
        if(function_exists('htmlspecialchars_decode')){
            $str=htmlspecialchars_decode($str);
        }else{
            $str=html_entity_decode($str);
        }
        $str = stripslashes($str);
    }
    return $str;
}

function killword($str, $start=0, $length, $charset="utf-8", $suffix=true) {
    if(function_exists("mb_substr"))
        $slice = mb_substr($str, $start, $length, $charset);
    elseif(function_exists('iconv_substr')) {
        $slice = iconv_substr($str,$start,$length,$charset);
    }else{
        $re['utf-8']   = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("",array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice.'...' : $slice;
}

function killhtml($str, $length=0){
    if(is_array($str)){
        foreach($str as $k => $v) $data[$k] = killhtml($v, $length);
        return $data;
    }

    if(!empty($length)){
        $estr = htmlspecialchars( preg_replace('/(&[a-zA-Z]{2,5};)|(\s)/','',strip_tags(str_replace('[CHPAGE]','',$str))) );
        if($length<0) return $estr;
        return killword($estr,0,$length);
    }
    return htmlspecialchars( trim(strip_tags($str)) );
}


/**
 * 实例化数据库类
 * @param string        $name 操作的数据表名称（不含前缀）
 * @param array|string  $config 数据库配置参数
 * @param bool          $force 是否强制重新连接
 * @return \think\db\Query
 */
if (!function_exists('db')) {
    function db($name = '')
    {
        return \think\facade\Db::connect('mysql',false)->name($name);
    }
}
/**
 * 获得二维数据中第二维指定键对应的值，并组成新数组 (不支持二维数组)
 *
 * @param array
 * @param string
 * @return array
 */
function fetch_array_value($array, $key)
{
    if (!$array || !is_array($array)) {
        return array();
    }

    $data = array();

    foreach ($array as $_key => $val) {
        $data[] = $val[$key];
    }

    return $data;
}