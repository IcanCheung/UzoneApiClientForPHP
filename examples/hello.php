<?php
/**
 * UC乐园Rest接口 PHP SDK  - 应用例子, 入口
 *
 * @category   utils
 * @package    apis
 * @author     Jiuhong Deng <dengjiuhong@gmail.com>
 * @version    $Id:$
 * @copyright  Jiuhong Deng
 * @link       http://u.uc.cn/
 * @since      File available since Release 1.0.0
 */
require dirname(__FILE__) . '/../libs/UzoneRestApi.php';

// 获取当前入口页面的uzone_token
// 从乐园直接点击应用过来的时候就会存在
// 如果uzone_token有保存到本地，需要在这里在本地session里面获取到uzone_token
$uzone_token  = isset($_GET['uzone_token']) ? $_GET['uzone_token'] : '';

// 初始化sdk
$UzoneRestApi = new UzoneRestApi($uzone_token);

// 检测用户是否为合法用户
if (!$UzoneRestApi->checkIsAuth()){
    // 发现不是从乐园进入的用户，跳转到乐园单点登录接口，由乐园验证用户的身份
    // 当前页面的Url, 登录成功后，自动跳转回这个url, 并且在后面带uzone_token
    $backUrl      = 'http://localhost/ucsns/api/uzone_api_sdk/php/examples/hello.php';
    $UzoneRestApi->redirect2SsoServer($backUrl);
}
// TODO 把uzone_token保存至本地

// 当前用户的uid
echo "当前用户的uid\t" . $uid = $UzoneRestApi->getAuthUid() . "\n";

// 获取当前用户的基本信息
echo "user.getInfo  -  开始\n";
// 自定义请求接口的其他方法
$uids = array('1005386', '123456');
$res = $UzoneRestApi->callMethod('user.getInfo', array('uids' => implode(',', $uids)));
if ($UzoneRestApi->checkIsCallSuccess()){
    var_export($res);
} else {
    echo "user.getInfo 失败, 出错信息为" . $UzoneRestApi->getCallErrorMsg() . "\n";
}
echo "user.getInfo  -  结束\n";

echo "friends.get  -  开始\n";
// 自定义请求接口的其他方法
$res = $UzoneRestApi->callMethod('friends.get');
if ($UzoneRestApi->checkIsCallSuccess()){
    var_export($res);
} else {
    echo "friends.get 失败, 出错信息为" . $UzoneRestApi->getCallErrorMsg() . "\n";
}
echo "friends.get  -  结束\n";

echo "friends.getFriends  -  开始\n";
// 自定义请求接口的其他方法
$res = $UzoneRestApi->callMethod('friends.getFriends', array('page' => 1, 'pageSize' => 10));
if ($UzoneRestApi->checkIsCallSuccess()){
    var_export($res);
} else {
    echo "friends.getFriends, 出错信息为" . $UzoneRestApi->getCallErrorMsg() . "\n";
}
echo "friends.getFriends  -  结束\n";

echo "util.getUcParam  -  开始\n";
// 自定义请求接口的其他方法
$res = $UzoneRestApi->callMethod('util.getUcParam');
if ($UzoneRestApi->checkIsCallSuccess()){
    var_export($res);
} else {
    echo "util.getUcParam, 出错信息为" . $UzoneRestApi->getCallErrorMsg() . "\n";
}
echo "util.getUcParam -  结束\n";

// 查询乐园币
echo "pay.getBalance  -  开始\n";
$res = $UzoneRestApi->callMethod('pay.getBalance');
if ($UzoneRestApi->checkIsCallSuccess()){
    var_export($res);
} else {
    echo "查询乐园币 , 出错信息为" . $UzoneRestApi->getCallErrorMsg() . "\n";
}
echo "pay.getBalance  -  结束\n";

// 支付乐园币
echo "pay.withdraw  -  开始\n";
$config = require dirname(__FILE__) . '/../conf/Api.inc.php';
$reason = $config['lyb']['withdrawReason'];
// 在乐园那边登记的扣费原因为：你在wap三国中花费{amount}乐园币购买了{1}个{0}
$reason = $reason . ':10:加速卡';
$amount = 10;
$res = $UzoneRestApi->callMethod('pay.withdraw', array('reason' => $reason, 'amount' => $amount));
if ($UzoneRestApi->checkIsCallSuccess()){
    echo "支付乐园币成功, 余额为".$res."\n";
} else {
    echo "支付乐园币失败, 出错信息为" . $UzoneRestApi->getCallErrorMsg() . "\n";
}
echo "pay.withdraw  -  结束\n";

// 其他的自己添加

