<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/5
 * Time: 9:10
 */

namespace app\fire\logic;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\AuthModel;
use app\fire\model\RegionModel;
use app\fire\model\UserModel;
use think\cache\driver\Redis;
use think\Db;
use think\Exception;
use think\facade\Cache;
use think\facade\Session;

class AuthLogic
{

    static function auth($data){
        //核对账号，得到user
        $user = UserModel::where('tel',$data['tel'])->with('region')->find();
        if (empty($user)) return Errors::LOGIN_ERROR;
        if ($user['tel'] != $data['tel'])
            return Errors::LOGIN_ERROR;
        if ($user['password'] !== md5($data['password'] . $user['salt']))
            return Errors::LOGIN_ERROR;
        $mold = UserModel::getUserMoldByUid($user['uid']);
        if($mold[0] != true){
            return $mold;
        }
        $user["mold"] = $mold[1];
        $role = UserModel::getUserRoleByUid($user['uid']);
        if($role[0] != true){
            return $role;
        }
        $user["role"] = $role[1];
        //判断登录类型
        $count = 0;
        foreach ($user['mold'] as $value){
            if($value['mid'] == $data['client']  ){
                if($value['examine'] == 1){
                    if($value['status'] == 0){
                        return Errors::LOGIN_STATUS;
                    }
                    $count++;
                }elseif ($value['examine'] == 0){
                    return [false,'用户正在审核中'];
                }else{
                    return [false,'用户审核未通过'];
                }
            }
        }
        $region_name = RegionModel::getRegionNameById($user['region']);
        if (!$region_name[0]) return $region_name;
        $user['region_name'] = $region_name[1]['region_name'];
        unset($region_name[1]['region_name']);
        $user['region_info'] = $region_name[1];
        $user['client'] = $data['client'];
        if ($count==0) return Errors::AUTH_PREMISSION_EMPTY;
        $user = Common::removeEmpty($user);
        unset($user['password']);
        unset($user['salt']);
        $user['s_token'] = Common::uniqStr();
        $auth = self::resetAuth($user, $data['client']);
        if ($auth[0]) {
            $auth_cache = [
                's_uid' => $user['uid'],
                's_token' => $user['s_token'],
                's_region' => $user['region'],
                's_role' => $user['role'][0]['rid'],
                's_client' => $data['client'],
                's_mold' => $user['mold']

            ];
            Cache::store('redis')->set($user['s_token'],$auth_cache,24*3600);
            return [true ,$user];
        }
    }

    private static function resetAuth($user, $client)
    {
        $data = [
            'uid' => $user['uid'],
            's_token' => $user['s_token'],
            's_update_time' => date('Y-m-d H:i:s', time()),
            'client' => $client
        ];
        $auth = new AuthModel();
        Db::startTrans();
        $result = $auth->where('uid',$data['uid'])->find();
        if(empty($result)) $result = $auth->save($data);
        else $result = $auth->save($data,['uid' => $data['uid']]);
        if($result>0)Db::commit();
        else Db::rollback();
        return $result == 1 ? [true , $result] : Errors::DATA_NOT_FIND;
    }

    static function deleteAuth($uid, $client)
    {
        $dbRes = AuthModel::where('uid',$uid)
                ->where('client',$client)
                ->delete();
        return $dbRes == 1 ? [true ,$dbRes] : Errors::AUT_LOGIN;
    }
}