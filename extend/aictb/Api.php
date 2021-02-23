<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2021/2/20 0020
 * Time: 11:15
 */
namespace aictb;

use think\exception\ValidateException;

class Api{
    protected $baseUrl = "http://api2.aictb.com";
    protected $token = "ed08cf3bda2290f5f69fd3805ba3dfd8";
    protected $error = "";
    protected $cache = true;
    protected $cacheTime = 300;//缓存时间，5分钟

    public function __construct($config=[])
    {
        if(!empty($config))
        {
            $this->token = isset($config['token'])?$config['token']:"";
        }
    }

    protected function curl_get($url,$data=[]){
        $api_url = $this->baseUrl.$url;

        if(is_array($data))
            $params = $data;
        else
            $params = [];
        $params['token'] = $this->token;
        $api_url .= "?".http_build_query($params);

        $ch = curl_init();
        $header = array(
            'Accept-Charset: utf-8',
            'Accept: application/json',
        );
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper("get"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if($error=curl_error($ch)){
            $this->error = $error;
            return false;
        }
        curl_close($ch);

        return json_decode($result,true);
    }
    protected function curl_post($url,$data=[]){
        $api_url = $this->baseUrl.$url;
        $api_url .= "?token=".$this->token;

        $post_data = $data;
        if(is_array($data))
            $post_data = http_build_query($data);

        $ch = curl_init();
        $header = array(
            'Accept-Charset: utf-8',
            'Accept: application/json',
        );
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper("post"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if($error=curl_error($ch)){
            return $error;
        }
        curl_close($ch);
        return json_decode($result,true);
    }

    protected function setCache($key,$value){
        return cache($key,$value,$this->cacheTime);
    }
    protected function getCache($key){
        return cache($key);
    }
    protected function getCacheKey($url,$params=[]){
        $api_url = $this->baseUrl.$url;
        $params['token'] = $this->token;
        $api_url .= "?".http_build_query($params);

        return md5($api_url);
    }
    protected function getData($url,$params = [],$method="get")
    {
        if(!$url)
        {
            $this->error = "url不能为空";
            return false;
        }
        if($this->cache)
        {
            $data = $this->getCache($this->getCacheKey($url,$params));
        }
        if(isset($data) && $data)
        {
            return $data;
        }
        else
        {
            if($method == "get")
                $data = $this->curl_get($url,$params);
            else
                $data = $this->curl_post($url,$params);

            if(!$data)
                return false;

            if(!isset($data['code']))
            {
                $this->error = "未知错误";
                return false;
            }
            if($data['code'] != 200)
            {
                $this->error = empty($data['msg'])?"远程接口调用失败":$data['msg'];
                return false;
            }

            if($this->cache)
            {
                $this->setCache($this->getCacheKey($url,$params),$data['data']);
            }

            return $data['data'];
        }
    }

    public function getError(){
        return $this->error;
    }

    //获得名校试卷分类
    public function getFamousCategory(){
        $url = "/pyzs/getBasedCategory";
        return $this->getData($url);
    }

    //获得名校试卷列表
    public function getFamousList($params = []){
        $p = [
            "province_id"   =>  "",
            "subject_id"    =>  "",
            "grade_id"  =>  "",
            "based_category_id" => "",
            "title" =>  "",
            "page"  =>  1
        ];
        if(empty($params['province_id']))
        {
            $this->error = "未设置province_id参数值";
            return false;
        }
        $p['province_id'] = $params['province_id'];
        $p['subject_id'] = isset($params['subject_id'])?$params['subject_id']:"";
        $p['grade_id'] = isset($params['grade_id'])?$params['grade_id']:"";
        $p['based_category_id'] = isset($params['category_id'])?$params['category_id']:"";
        $p['title'] = isset($params['keyword'])?$params['keyword']:"";
        $p['page'] = isset($params['page']) && $params['page'] > 1 ?intval($params['page']):1;

        $url = "/pyzs/getSchoolResourcesList";
        return $this->getData($url,$p);
    }

    //获得课件列表
    public function getMaterialList($params = []){
        $p = [
            "subject_id"    =>  "",
            "grade_id"  =>  "",
            "title" => "",
            "page"  =>  1
        ];
        $p['subject_id'] = isset($params['subject_id'])?$params['subject_id']:"";
        $p['grade_id'] = isset($params['grade_id'])?$params['grade_id']:"";
        $p['title'] = isset($params['keyword'])?$params['keyword']:"";
        $p['page'] = isset($params['page']) && $params['page'] > 1 ?intval($params['page']):1;

        $url = "/pyzs/coursewareList";

        return $this->getData($url,$p);
    }

    //获得题目详情
    public function getExercisesDetail($params = []){
        $p = [
            "exercises_id"    =>  [],
        ];
        $p['exercises_id'] = $params['ids'];

        $url = "/pyzs/getExercisesDetail";

        return $this->getData($url,$p);
    }

    //获取知识点
    public function getKnowledge($params = []){
        $url = "/pyzs/getKnowledge";
        try {
            validate([
                "subject_id"    =>  "require",
                "grade_id"  =>  "require",
            ],[])->check($params);
        }catch (ValidateException $e){
            $this->error = $e->getError();
            return false;
        }

        return $this->getData($url,$params);
    }

    //获得章节
    public function getChapter($params = [])
    {
        $url = "/pyzs/getChapter";
        try {
            validate([
                "subject_id"    =>  "require",
                "grade_id"  =>  "require",
                "province_id"  =>  "require",
                "city_id"  =>  "require",
                "semester"  =>  "in:1,2"
            ],[])->check($params);
        }catch (ValidateException $e){
            $this->error = $e->getError();
            return false;
        }

        return $this->getData($url,$params);
    }

    //获得题型
    public function getQuestionCategory($params = []){
        $url = "/pyzs/getSubjectQuestionCategory";

        try {
            validate([
                "subject_id"    =>  "require",
            ],[])->check($params);
        }catch (ValidateException $e){
            $this->error = $e->getError();
            return false;
        }

        return $this->getData($url,$params);
    }

    //获得题目列表
    public function getQuestionList($params = []){
        $url = "/pyzs/getExercises";
        try {
            validate([
                "subject_id"    =>  "require",
                "grade_id"    =>  "require",
                "type"    =>  "number",
                "level" =>  "number",
                "title" =>  "max:20",
                "knowledge_id"    =>  "array",
                "chapter_id"    =>  "array",
            ],[])->check($params);
        }catch (ValidateException $e){
            $this->error = $e->getError();
            return false;
        }

        return $this->getData($url,$params);
    }
}