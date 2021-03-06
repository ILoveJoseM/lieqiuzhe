<?php
/**
 * Created by PhpStorm.
 * User: chenyu
 * Date: 2018/5/2
 * Time: 15:33
 */

namespace Logic;


use anerg\OAuth2\OAuth;
use Exception\UserException;
use Model\UserModel;
use Wxapp\Wxapp;

class LoginLogic extends BaseLogic
{

    public function login($login_type, $params)
    {
        switch ($login_type){
            case 0://手机登陆
                break;
            case 1://微信登录
                $uid = $this->wechat($params);
                break;
            case 2://QQ登录
                break;
            case 3://微博登录
                break;
            case 4://赛事比分小程序
                $uid = $this->wxapp($params);
                break;
            case 5://小程序登录
                $uid = $this->zuQiuBiSai1($params);
                break;
            default:
                break;
        }

        $token = $this->generateJWT($uid);

        return ["token" => $token];
    }

    /**
     * 赛事比分小程序
     * @param $params
     * @return int|string
     */
    private function wxapp($params)
    {
        $log = myLog("wxapp_login");
        $conf = config()->get("wxapp");

        $log->addDebug("conf:".json_encode($conf));

        $wxapp = new Wxapp($conf['app_id'], $conf['app_secret']);

        $code = $params['code'];

        $log->addDebug("code:".$code);
        $result = $wxapp->login($code);
        $log->addDebug("result:".json_encode($result));
        if(!isset($result['openid'])){
            UserException::LoginFail();
        }

        if(!isset($result['unionid'])||empty($result['unionid'])||!$user = UserModel::getUserByUnionId($result['unionid'])){
            $data = [
                "openid" => $result['openid'],
                "unionid" => isset($result['unionid'])?$result['unionid']:"",
                "openid_type" => 4,
            ];
            $my_user = UserModel::getUserByOpenId($data['openid']);
            if(!$my_user)
            {
                $my_user['id'] = UserModel::addUser($data);
            }

        }else{
            $my_user['id'] = $user['id'];
        }

        $log->addDebug("my_user:".json_encode($my_user));
        if(!$my_user['id']){
            UserException::LoginFail();
        }

        return $my_user['id'];

    }

   /**
    * 足球比赛1小程序
    * @param $params
    * @return int|string
    */
    private function zuQiuBiSai1($params)
    {
        $log = myLog("wxapp_login");
        $conf = config()->get("zuqiubisai1");

        $log->addDebug("conf:".json_encode($conf));

        $wxapp = new Wxapp($conf['app_id'], $conf['app_secret']);

        $code = $params['code'];

        $log->addDebug("code:".$code);
        $result = $wxapp->login($code);
        $log->addDebug("result:".json_encode($result));
        if(!isset($result['openid'])){
            UserException::LoginFail();
        }

        if(!isset($result['unionid'])||empty($result['unionid'])||!$user = UserModel::getUserByUnionId($result['unionid'])){
            $data = [
                "openid" => $result['openid'],
                "unionid" => isset($result['unionid'])?$result['unionid']:"",
                "openid_type" => 5,
            ];
            $my_user = UserModel::getUserByOpenId($data['openid']);
            if(!$my_user)
            {
                $my_user['id'] = UserModel::addUser($data);
            }

        }else{
            $my_user['id'] = $user['id'];
        }

        $log->addDebug("my_user:".json_encode($my_user));
        if(!$my_user['id']){
            UserException::LoginFail();
        }

        return $my_user['id'];

    }

    private function wechat($params)
    {
        $config = [
            'appid'         => $this->config['app_key'],
            'redirect_uri'  => $this->config['callback'],
            'response_type' => $this->config['response_type'],
            'scope'         => $this->config['scope'],
            'state'         => $this->timestamp,
        ];
        $OAuth = OAuth::getInstance($config, "weixin");
    }

}