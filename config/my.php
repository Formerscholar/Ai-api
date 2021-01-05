<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/22 0022
 * Time: 16:03
 */
return [
    'upload_dir' => './uploads',            //文件上传根目录
    'upload_subdir' => 'Ym',                //文件上传二级目录 标准的日期格式

    'password_secrect' => '123456!@#',            //密码加密秘钥

    //api基本配置
    'api_input_log' => true,                //api参数输入记录日志(全局)
    'success_code' => '0',                //成功返回码
    'error_code' => '-1',                //错误返回码
    'jwt_expire_code' => '101',                //jwt过期
    'jwt_error_code' => '102',                //jwt无效

    //聚合短信配置
    'juhe_sms_key' => '',        //key
    'juhe_sms_tempCode' => '',                                    //短信验证码模板

    //极速短信配置
    'jisu_sms_key' => '',                            //key
    'jisu_sms_tempCode' => '',                                        //短信验证码模板

    //阿里云短信配置
    'ali_sms_accessKeyId' => '',                //阿里云短信 keyId
    'ali_sms_accessKeySecret' => '',    //阿里云短信 keysecret
    'ali_sms_signname' => '',                            //签名
    'ali_sms_tempCode' => '',                        //短信模板 Code

    //oss开启状态 以及配置指定oss
    'oss_status' => false,            //true启用  false 不启用
    'oss_default_type' => 'aliyun',            //oss使用类别 则使用ali的oss  qiniuyun 则使用七牛云oss

    //阿里云oss配置
    'ali_oss_accessKeyId' => '',                        //阿里云 keyId
    'ali_oss_accessKeySecret' => '',        //阿里云keysecret
    'ali_oss_endpoint' => '',    //建议填写自己绑定的域名
    'ali_oss_bucket' => '',                            //阿里bucket

    //七牛云oss配置
    'qny_oss_accessKey' => '',  //access_key
    'qny_oss_secretKey' => '',     //secret_key
    'qny_oss_bucket' => '',                            //bucket
    'qny_oss_domain' => '',        // 七牛云绑定图片访问域名 后缀加斜杠

    //jwt鉴权配置
    'jwt_expire_time' => 2592000,                //token过期时间 默认一个月
    'jwt_secrect' => 'boTCfOGKwqTNKArT',    //签名秘钥
    'jwt_iss' => 'client.aictb',    //发送端
    'jwt_aud' => 'server.aictb',    //接收端

    //api上传配置
    'api_upload_domain' => '',                        //如果做本地存储 请解析一个域名到/public/upload目录  也可以不解析
    'api_upload_ext' => 'jpg,png,gif,mp4',            //api允许上传文件
    'api_upload_max' => 200 * 1024 * 1024,            //默认2M

    'upload_hash_status' => false,    //检测是否存在已上传的图片并返回原来的图片路径 true 检测 false 不检测  默认为true如果不设置
    'filed_name_status' => false,        //true 设置字段时自动读取拼音作为字段名
    'reset_button_status' => false,    //列表搜索重置按钮状态 true开启 false关闭 需要重新生成
    'api_upload_auth' => true,    //api应用上传是否验证token  true 验证 false不验证 需要重新生成

    //题目难度等级
    'question_level' => [
        [
            'id' => 1,
            'name' => '容易'
        ],
        [
            'id' => 2,
            'name' => '较易'
        ],
        [
            'id' => 3,
            'name' => '中等'
        ],
        [
            'id' => 4,
            'name' => '较难'
        ],
        [
            'id' => 5,
            'name' => '困难'
        ]
    ],
    //老师默认登录密码
    'default_password'  =>   '123456',
    //试卷的题目容量
    'basket_max_question' => 40,
];