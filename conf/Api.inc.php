<?php
/**
 * UC乐园Rest接口 PHP SDK  - 应用配置文件
 *
 * @category   Api
 * @package    conf
 * @author     Jiuhong Deng <dengjiuhong@gmail.com>
 * @version    $Id:$
 * @copyright  Jiuhong Deng
 * @link       http://u.uc.cn/
 * @since      File available since Release 1.0.0
 */
return array(
    // 生产环境时关闭(true:打开  false:关闭)
    'debug'              => true,
    // 性能日志(true:打开  false:关闭)
    'time'               => true,
    //日志路径定义
    'logPath'            => dirname(__FILE__) . '/../logs/',
    //连接方式定义(file,curl,sock) 一般选择sock,默认为file
    'connectType'        =>'sock',
    //连接超时设置,单位秒
    'connectTimeOut'     => 2,
    //获取流超时设置,单位毫秒(只有连接方式为sock时才有效)
    'streamTimeOut'      => '2000',
    // 乐园分配的appKey,由乐园指定
    'appKey'             => 'sanguo',
    // 乐园分配的secret,由乐园指定
    'secret'             => '123456',
    // 乐园分配的rsa private.key,由乐园指定
    'privateKey'         => file_get_contents(dirname(__FILE__) . '/../../../ucsns/api/keys/private.key'),
    // 乐园的restserver地址,由乐园指定
    'restServer'         => 'http://api.u.uc.cn/restserver.php',
    // 乐园的单点登录地址,由乐园指定
    'ssoServer'          => 'http://u.uc.cn/index.php?r=sso/auth',
    // 乐园币的配置
    'lyb' => array(
        'withdrawReason' => '',
    ),
    // 接口支持的方法的列表
    'methodList' => array(),
);
