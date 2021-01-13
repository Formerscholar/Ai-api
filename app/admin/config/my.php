<?php
/**
 * Created by PhpStorm.
 * User: 酸菜鱼
 * Date: 2020/12/22 0022
 * Time: 16:03
 */
return [
    'password_secrect' => '1111222!@#',            //密码加密秘钥

    //jwt鉴权配置
    'jwt_expire_time' => 2592000,                //token过期时间 默认一个月
    'jwt_secrect' => 'boTCfOGKwqTNKArT',    //签名秘钥
    'jwt_iss' => 'client.aictb',    //发送端
    'jwt_aud' => 'server.aictb',    //接收端

    //默认登录密码
    'default_password'  =>   '123456',
];