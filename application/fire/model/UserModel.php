<?php

namespace app\fire\model;

use app\fire\controller\Errors;
use think\Db;
use think\Exception;
use think\Model;

/**
 * 用户数据库操作
 * Created by xwpeng.
 */
class UserModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_user';
    //设置主键ID
    protected $pk = 'uid';
    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';

    public function region()
    {
        return $this->hasOne('RegionModel','id','region')
            ->bind('level');
    }

    static function getUserMoldByUid($uid){
        try{
            $mold = Db::table("tb_user_mold")->alias("um")
                ->where('um.uid',$uid)
                ->join('tb_mold m', 'm.mid = um.mid')
                ->field('m.mid,m.name,um.examine,um.status')
                ->select();
            return is_array($mold) ? [true, $mold] : Errors::DATA_NOT_FIND;
        }catch (Exception $exception){
            return Errors::Error($exception->getMessage());
        }
    }

    static function getUserByTel($tel){
        $user = UserModel::where('tel',$tel)->find();
        return empty($user) ? [true,'验证成功']:[false,'手机号已被注册'];
    }

    static function getUserRoleByUid($uid){
        try{
            $role = Db::table("tb_user_role")->alias('ur')
                ->where('ur.uid', $uid)
                ->join('tb_role r', 'r.rid = ur.rid')
                ->field('r.rid,r.name')
                ->select();
            return is_array($role) ? [true, $role] : Errors::DATA_NOT_FIND;
        }catch (Exception $exception){
            return Errors::Error($exception->getMessage());
        }
    }

    /**
     * 通过uid查询用户详细信息
     * @param $uid
     * @return array
     */
    static function getUserDetailByUid($uid){
        try{
            $user=Db::table('tb_user')->alias('u')
                ->where('u.uid',$uid)
                ->join('tb_region r','r.id=u.region')
                ->join('tb_user_mold um','um.uid = u.uid')
                ->join('tb_mold m','m.mid = um.mid')
                ->field('u.tel,m.mid,m.`name`,u.region')
                ->select();
            return is_array($user)?[true,$user]:Errors::DATA_NOT_FIND;
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
  }

    /**
     * 按手机号码查询用户详情
     * @param $tel
     * @return array
     */
    static function getUserDetailByTel($tel){
        try {
            $result = Db::table('tb_user')->alias('u')
                ->where('u.tel', $tel)
                ->join('tb_region r', 'r.id=u.region')
                ->join('tb_user_mold um', 'um.uid = u.uid')
                ->join('tb_mold m', 'm.mid = um.mid')
                ->join('tb_region r1', 'r1.id = r.parentId', 'left')
                ->join('tb_region r2', 'r2.id = r1.parentId', 'left')
                ->join('tb_region r3', 'r3.id = r2.parentId', 'left')
                ->join('tb_region r4', 'r4.id = r3.parentId', 'left')
                ->field('u.uid,u.tel,m.mid,m.`name`,u.region,r.name r,r1.name r1,r2.name r2,r3.name r3,r4.name r4,r.level')->find();
            if (empty($result)) return Errors::DATA_NOT_FIND;
            $result['region_name'] = $result['r4'] . $result['r3'] . $result['r2'] . $result['r1'] . $result['r'];
            unset($result['r']);
            unset($result['r1']);
            unset($result['r2']);
            unset($result['r3']);
            unset($result['r4']);
            return empty($result) ? Errors::DATA_NOT_FIND : [true, $result];
        }catch (Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

    /**
     * 通过用户身份和区域编号查询号码
     */
    static function getTelByRegionIdAndMid($regionId,$mid){
        $result=UserModel::alias('u')->join('tb_user_mold um','um.uid=u.uid')
            ->join('tb_mold m','m.mid=um.mid')
            ->whereLike('u.region',$regionId.'%')
            ->where('m.mid',$mid)->field('u.tel')->select();
        return empty($result)?Errors::DATA_NOT_FIND:[true,$result];
    }
}