<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/4
 * Time: 16:15
 */

namespace app\fire\logic;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\AuthModel;
use app\fire\model\RegionModel;
use app\fire\model\UserModel;
use MongoDB\Driver\Exception\Exception;
use think\Db;
use think\facade\Session;

class UserLogic
{
    /**
     * 添加用户
     * @param $data
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
     static function saveUser($data,$file,$auth){
        try {
            $permission = self::permission($data,$auth);
            if(!$permission[0]) return $permission;
            $addPermission = self::addPermission($data,$permission[1]);
            if(!$addPermission[0]) return $addPermission;
            $moldPermission = self::moldPermission($data['mids'],$permission[1]);
            if(!$moldPermission[0]) return $moldPermission;
            if(in_array(2,$data['mids']) && !Common::isWhere($data, 'position')) return [false, '护林员用户必须添加区域'];
            if(in_array(5,$data['mids']) && !Common::isWhere($data, 'airplane_model')) return [false, '载人机用户必须添加飞机型号'];
            $data['uid'] = Common::uniqStr();
            $data['salt'] = Common::getRandChar(6);
            $data['password'] = md5($data['password'] . $data['salt']);
            $data['examine'] = 1;
            Db::startTrans();
            $user = UserModel::where('tel',$data['tel'])->find();
            if (!empty($user)) return [false, '账号已存在'];
            if($file!=null){
                $path = Common::uploadImage($file, ROOT_PATH . 'public' . DS . 'uploads'.DS . 'user_image' , $data['uid']);
                if ($path[0] !== true) return $path;
                $data['imgHead'] = $path[1];
            }
            $rid = $data['rid'];
            unset($data['rid']);
            $mids = $data['mids'];
            unset($data['mids']);
            $user = new UserModel();
            $user->save($data);
            Db::table('tb_user_role')->insert(["uid" => $data['uid'], "rid" => $rid]);
            $time = date('Y-m-d H:i:s', time());
            foreach ($mids as $mid) {
                Db::table('tb_user_mold')->insert(["uid" => $data['uid'], "mid" => $mid, "examine" => 1, "create_time" => $time, "update_time" => $time] );
            }
            Db::commit();
            return [true , $data['uid']];
        } catch (Exception  $e) {
            try {
                Db::rollback();
            } catch (Exception $e) {
                return Errors::Error($e->getMessage());
            }
            return Errors::Error($e->getMessage());
        }
    }


    /**
     * 修改用户判断
     * @param $data
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    static function updateUserJudge($data,$file,$auth)
    {
        $editPermission = self::editPermission($data,$auth);
        if(!$editPermission[0]) return $editPermission;
        $moldPermission = self::moldPermission($data['mids'],$auth,$data['uid']);
        if(!$moldPermission[0]) return $moldPermission;
        if($file!=null){
            $path = Common::uploadImage($file, ROOT_PATH . 'public' . DS . 'uploads'.DS . 'user_image' , $data['uid']);
            if ($path[0] !== true) return $path;
            $data['imgHead'] = $path[1];
        }
        if($data['uid'] == $auth['s_uid'] ){
            if(!Common::isWhere($data,'password')){
                $data['password'] = md5($data['password'] . $editPermission['salt']);
            }else{
                unset($data['password']);
            }
        }else{
            unset($data['password']);
        }
        unset($data['tel']);
        $dbRes = self::updateUser($data,$moldPermission[1]);
        return $dbRes;
    }

    /**
     * 修改用户
     * @param $data
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    static function updateUser($data,$moldPermission=null){
        try {
            Db::startTrans();
            //delete
            Db::table('tb_user_role')->where("uid", $data['uid'])->setField("rid",  $data['rid']);
            $del_array = array_diff($moldPermission,$data['mids']);
            if(!empty($del_array)){
                foreach ($del_array as $value){
                    Db::table('tb_user_mold')->where('mid',$value)->where("uid", $data['uid'])->delete();
                }
            }
            $add_array = array_diff($data['mids'],$moldPermission);
            if(!empty($del_array)){
                foreach ($add_array as $value) {
                    Db::table('tb_user_mold')->insert(["uid" => $data['uid'], "mid" => $value, "create_time" => date('Y-m-d H:i:s', time()), "update_time" => date('Y-m-d H:i:s', time())]);
                }
            }
            //update
            unset($data['rid']);
            $mids = $data['mids'];
            unset($data['mids']);
            $user = new UserModel;
            $dbRes = $user->save($data,['uid' => $data['uid']]);
            if ($dbRes > 0) {
                foreach ($mids as $mid){
                    AuthLogic::deleteAuth($data['uid'],$mid);
                }
                Db::commit();
                return [true , $dbRes];
            }
        } catch (Exception $exception) {
            try {
                Db::rollback();
            } catch (Exception $exception) {
                return Errors::Error($exception->getMessage());
            }
            return Errors::Error($exception->getMessage());
        }
    }

    /**
     * 用户注册
     * @param $data
     * @return array
     */
    static function signUser($data){
        try {
            Db::startTrans();
            $user = UserModel::getUserByTel($data['tel']);
            if (!$user[0]) return $user;
            $data['uid'] = Common::uniqStr();
            $data['salt'] = Common::getRandChar(6);
            $data['password'] = md5($data['password'] . $data['salt']);
            $data['examine'] = 1;
            $mid = $data['mid'];
            unset($data['mids']);
            $user = new UserModel();
            $user->save($data);
            Db::table('tb_user_role')->insert(["uid" => $data['uid'], "rid" => 1]);
            $time = date('Y-m-d H:i:s', time());
            Db::table('tb_user_mold')->insert(["uid" => $data['uid'], "mid" => $mid, "create_time" => $time, "update_time" => $time]);
            Db::commit();
            return [true , $data['uid']];
        } catch (Exception  $e) {
            try {
                Db::rollback();
            } catch (Exception $e) {
                return Errors::Error($e->getMessage());
            }
            return Errors::Error($e->getMessage());
        }
    }
    static function updateUserStatus($data,$auth){
        try{
            //判断用户区域是否在查询范围内
            Db::startTrans();
            //判断等级
            $user_status = 0;
            foreach ($data['uids'] as $key=>$value){
                $level_result = self::delPermission($value,$auth);
                if(!$level_result[0]) return $level_result;
                $moldPermission = self::moldPermission($data['mid'],$auth);
                if(!$moldPermission[0]) return $moldPermission;
                $result = Db::table('tb_user_mold')
                    ->where('uid',$value)
                    ->where('mid',$data['mid'])
                    ->update(['status'=>$data['status']]);
                if($result != 1){
                    return [false, "找不到该用户"];
                }
                $user_status++;
            }
            if (count($data['uids']) == $user_status) {
                Db::commit();
                return [true , $user_status];
            }
            return [false ,"找不到该用户"];
        } catch (Exception  $e) {
            try {
                Db::rollback();
            } catch (Exception $e) {
                return Errors::Error($e->getMessage());
            }
            return Errors::Error($e->getMessage());
        }
    }

    static function updateUserExamine($data,$auth){
        try{
            //判断用户区域是否在查询范围内
            Db::startTrans();
            //判断等级
            $user_examine = 0;
            foreach ($data['uids'] as $key=>$value){
                $level_result = self::delPermission($value,$auth);
                if(!$level_result[0]) return $level_result;
                $moldPermission = self::moldPermission($data['mid'],$auth);
                if(!$moldPermission[0]) return $moldPermission;
                $result = Db::table('tb_user_mold')
                            ->where('uid',$value)
                            ->where('mid',$data['mid'])
                            ->update(['examine'=>$data['examine']]);
                if($result != 1){
                    return [false, "审核失败，找不到该用户身份"];
                }
                $user_examine++;
            }
            if (count($data['uids']) == $user_examine) {
                Db::commit();
                return [true , $user_examine];
            }
            return [false ,"更新失败,找不到该用户"];
        } catch (Exception  $e) {
            try {
                Db::rollback();
            } catch (Exception $e) {
                return Errors::Error($e->getMessage());
            }
            return Errors::Error($e->getMessage());
        }
    }


    static function signUserMold($data)
    {
        try{
            $user = UserModel::getUserByTel($data['tel']);
            if (!$user) return $user;
            if ($user['tel'] != $data['tel'])
                return Errors::LOGIN_ERROR;
            if ($user['password'] !== md5($data['password'] . $user['salt']))
                return Errors::LOGIN_ERROR;
            $mold = UserModel::getUserMoldByUid($user['uid']);
            if ($mold[0] != true) {
                return $mold;
            }
            $mold = $mold[1];
            foreach ($mold as $value) {
                if ($value['mid'] == $data['client']) {
                    if ($value['examine'] == 1) {
                        if ($value['status'] == 1){
                            return [true, '您的账号已通过审核，可以登录'];
                        }else{
                            return [false, '您的账号已通过审核，但已被禁用'];
                        }
                    }elseif ($value['examine'] == -1){
                        return [false, '你的账号审核已被拒绝'];
                    }else  {
                        return [false, '您的账号正在审核中，请耐心等候'];
                    }
                }
            }
            Db::startTrans();
            $result = Db::table('tb_user_mold')->insert($data);
            if($result == 1){
                Db::commit();
                return [true, '已提交身份审核，请耐心等耐'];
            }else{
                Db::rollback();
                return [false, '网络错误'];
            }
        }catch (Exception  $e) {
            return Errors::Error($e->getMessage());
        }
    }
    private static function permission($data,$auth){
        //不允许添加、修改超级管理员
        if($data['rid'] == 3){
            return Errors::AUTH_PREMISSION_EMPTY;
        }
        $adduser_level = RegionModel::getRegionById($data['region']);
        if (!$adduser_level[0]) return [ false  , "找不到该区域"];
        //添加、修改管理员，首先判断被添加的用户是否大于县级
        if ($data['rid'] == 2 && $adduser_level[1]['level'] > 3) {
            return Errors::AUTH_PREMISSION_LEVEL;
        }
        //判断添加、修改的对象区域是否属于自己以内
        $region_result = Common::authRegion($data['region'],$auth['s_region']);
        if(!$region_result) return Errors::AUTH_PREMISSION_REJECTED;
        $auth['level'] = $adduser_level[1]['level'];
        return [true,$auth];
    }

    static function listUser($data,$region){
        if(!Common::authRegion($data['region'],$region)) return Errors::AUTH_PREMISSION_REJECTED;
        $tbRes = UserModel::alias('u')
            ->join('tb_region r','r.id = u.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left')
            ->join('tb_user_role ur','ur.uid = u.uid')
            ->join('tb_role ro','ro.rid = ur.rid')
            ->join('tb_user_mold um','u.uid = um.uid')
            ->join('tb_mold m','um.mid = m.mid')
            ->where('um.examine',$data['examine'])
            ->where('um.mid',$data['mid'])
            ->whereLike('region',$data['region'].'%');
        if(Common::isWhere($data,'name'))
            $tbRes->where('u.name',$data['name'])->whereOr('u.tel',$data['name']);
        $tbRes->field('u.uid,u.imgHead,u.name,u.tel,u.region,u.job,r.level,ro.name role,um.create_time,
        r4.name r4,r3.name r3,r2.name r2,r1.name r1,r.name r,um.status')
            ->order('u.create_time', 'desc')->group('u.uid');
        $dataRes = $tbRes->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        foreach ($dataRes['data'] as $key => $value){
            $dataRes['data'][$key]['region_name'] = $value['r4'].$value['r3'].$value['r2'].$value['r1'].$value['r'];
            unset($dataRes['data'][$key]['r']);
            unset($dataRes['data'][$key]['r1']);
            unset($dataRes['data'][$key]['r2']);
            unset($dataRes['data'][$key]['r3']);
            unset($dataRes['data'][$key]['r4']);
        }
        return empty($dataRes) ? Errors::DATA_NOT_FIND : [true, Common::removeEmpty($dataRes)];
    }
    static function queryUserByNameOrTel($data,$auth){
        //区域检查，暂不做检查
        $query = UserModel::alias('u');
            if (Common::isWhere($data,'name'))
                $query->whereLike('u.name','%'.$data['name'].'%')
                    ->whereOr('u.tel','like',$data['name'].'%');
            if(Common::isWhere($data,'region')){
                if(!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
                $query->whereLike('u.region',$data['region'].'%');
            }else{
                $query->whereLike('u.region',$auth['s_region'].'%');
            }
            if (Common::isWhere($data,'mold_type'))
                $query->join('tb_user_mold um','u.uid = um.uid')
                    ->where('um.mid',$data['mold_type'])
                    ->where('um.status','1')
                    ->where('um.examine','1');
        $tbRes = $query->field('u.name,u.uid,u.tel')->select();
        return !empty($tbRes)?[true, $tbRes]:Errors::DATA_NOT_FIND;
        $tbRes = UserModel::whereLike('name','%'.$data.'%')
            ->whereOr('tel','like',$data.'%')
            ->field('name,tel')->group('tel')->select();
        return empty($mold)?[true, $tbRes]:[false,[]];
    }
    static function queryUser($data){
        //区域检查，暂不做检查
//        $region_result = Common::authRegion($data['region']);
//        if(!$region_result) return Errors::AUTH_PREMISSION_REJECTED;
        $tbRes = UserModel::alias('u')
            ->join('tb_region r','r.id = u.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left')
            ->join('tb_user_role ur','ur.uid = u.uid')
            ->join('tb_role ro','ro.rid = ur.rid')
            ->where('u.uid',$data['uid'])
            ->field('u.uid,u.imgHead,u.name,u.tel,u.password,u.region,u.job,u.position,u.airplane_model,u.create_time,r.level,ro.name role,
        r4.name r4,r3.name r3,r2.name r2,r1.name r1,r.name r')->find();
        if(empty($tbRes)) return Errors::DATA_NOT_FIND;
        $tbRes['region_name'] = $tbRes['r4'].$tbRes['r3'].$tbRes['r2'].$tbRes['r1'].$tbRes['r'];
        unset($tbRes['r']);
        unset($tbRes['r1']);
        unset($tbRes['r2']);
        unset($tbRes['r3']);
        unset($tbRes['r4']);
        $mold = Db::table('tb_user_mold')->alias('um')
            ->join('tb_mold m','um.mid = m.mid')
            ->field('um.mid,m.name')
            ->where('um.status','1')
            ->where('uid',$tbRes['uid'])->select();
        if (empty($mold)) return Errors::DATA_NOT_FIND;
        $tbRes['mold']= $mold;
        Common::removeEmpty($tbRes);
        return [true, $tbRes];
    }

    private static function addPermission($data,$auth){
        //当前用户是否为超级管理员，如是，则不处理
        if($auth['s_role'] != 3) {
            $user_detail = RegionModel::where('id',$auth['s_region'])->find()->toArray();
            //判断参数传入是否普通用户
            if ($data['rid'] != 1) {
                //添加管理员，判断添加用户权限等级是否小于添加角色前端
                if ($user_detail['level'] >= $auth['level']) {
                    //否，返回权限不足
                    return Errors::AUTH_PREMISSION_EMPTY;
                }
            }
        }
        return [true];
    }

    private static function editPermission($data,$auth){
        //当前用户是否为超级管理员，如是，则不处理
        $user_detail = RegionModel::where('id',$auth['s_region'])->find()->toArray();
        if (empty($user_detail)) return [ false  , "网络错误"];
        $edit_user_detail = UserModel::alias('u')->where('u.uid',$data['uid'])
            ->join('tb_user_role ur','u.uid = ur.uid')
            ->join('tb_region r','r.id = u.region')
            ->field('u.salt,u.tel,ur.rid,r.level')->find();
        if(empty($edit_user_detail)) return [false,"用戶不存在"];
        $edit_user_detail = $edit_user_detail->toArray();
        if($auth['s_role'] != 3) {
            //获得修改对象身份
            if($edit_user_detail['rid'] == 2){
                //修改管理员
                if ($user_detail['level'] >= $edit_user_detail['level']) {
                    //否，返回权限不足
                    return Errors::AUTH_PREMISSION_EMPTY;
                }
            }else if($edit_user_detail['rid'] == 1){
                if ($user_detail['level'] > $edit_user_detail['level']) {
                    //否，返回权限不足
                    return Errors::AUTH_PREMISSION_EMPTY;
                }
            }else{
                return Errors::AUTH_PREMISSION_EMPTY;
            }
        }
        return [true,$edit_user_detail];
    }

    private static function delPermission($uid,$auth){
        $user_detail = RegionModel::where('id',$auth['s_region'])->find();
        if (empty($user_detail)) return [ false  , "网络错误"];
        $edit_user_detail = UserModel::alias('u')->where('u.uid',$uid)
            ->join('tb_user_role ur','u.uid = ur.uid')
            ->join('tb_region r','r.id = u.region')
            ->field('u.tel,ur.rid,r.id,r.level')->find();
        if(empty($edit_user_detail)) return [false,"用戶不存在"];
        $user_detail = $user_detail->toArray();
        $edit_user_detail = $edit_user_detail->toArray();
        $region_result = Common::authRegion($edit_user_detail['id'],$auth['s_region']);
        if(!$region_result) return Errors::AUTH_PREMISSION_REJECTED;
        //获得修改对象身份

        if($auth['s_role'] != 3) {
            //修改管理员
            if ($edit_user_detail['rid'] == 2) {
                //等级大于修改者，返回权限不足
                if ($user_detail['level'] >= $edit_user_detail['level']) {
                    return Errors::AUTH_PREMISSION_EMPTY;
                }
            }
        }
        return [true];
    }

    static function moldPermission($mids,$auth,$uid=null){
        $edit_user_mold = null;
        if($uid != null){
            $edit_user_mold = Db::table('tb_user_mold')
                ->where('uid',$uid)
                ->where('status',1)
                ->column('mid');
        }
        $user_mold = [];
        foreach ($auth['s_mold'] as $value){
            array_push($user_mold,$value['mid']);
        }
        if(empty($user_mold)) return [false,'网络错误'];
        if(in_array(1,$user_mold)||$mids == array_intersect($mids,$user_mold)){
            if($edit_user_mold != null && (in_array(1,$user_mold) || $edit_user_mold == array_intersect($mids,$edit_user_mold))){
                return [true,$edit_user_mold];
            }
            return [true];
        }else{
            return [false,'你没有权限操作该身份下的用户'];
        };
    }

    /**
     * 获取用户类型总数
     */
    static function countByMidTotal($mid){
        try{
            $result=UserModel::alias('u')->join('tb_user_mold mu','mu.uid=u.uid','left')
                ->join('tb_mold m','m.mid=mu.mid')
                ->where('m.mid',$mid)
                ->field('count(u.mid) count_mid')->find();
            return empty($result)?Errors::DATA_NOT_FIND:[true,$result];
        }catch (\think\Exception $e){
            return Errors::Error($e->getMessage());
        }
    }

}