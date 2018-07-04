<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/9
 * Time: 17:01
 */

namespace app\fire\model\typical_case;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use think\Db;
use think\Exception;
use think\Model;
use think\model\concern\SoftDelete;

class TypicalCaseModel extends Model
{
    //设置表
    protected $table = 'tb_typical_case';
    //设置只读字段，防止作者信息被修改
    protected $readonly = 'user_id';
    //主键
    protected $pk = 'id';
    //开启时间戳
    protected $autoWriteTimestamp = 'datetime';
    //开启软删除
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function user()
    {
        return $this->belongsTo("app\\fire\\model\\UserModel",'user_id','uid')
                        ->bind(['user_name'=>'name']);
    }

    //查询详情
    public static function queryTypicalCaseInfo($id){
        $result = self::get($id,'user');
        return empty($result)? Errors::DATA_NOT_FIND:[true,$result->toArray()];
    }


    public static function updateTypicalCase($data){
        try {
            Db::startTrans();
            $fire_assess = self::save($data,['id'=>$data['id']]);
            Db::commit();
            return [true,$fire_assess->id];
        }catch (Exception $exception){
            Db::rollback();
            return Errors::Error($exception->getMessage());
        }
    }
    
    public static function queryTypicalCaseList($data,$auth){
        $query = self::with('user');
        if (Common::isWhere($data,'region')){
            if (!Common::authRegion($data['region'],$auth['s_region']))
                return Errors::REGION_PREMISSION_REJECTED;
            $query->whereLike('region',$data['region'].'%');
        }else{
            $query->whereLike('region',$auth['s_region'].'%');
        }
        if (Common::isWhere($data,'begin_time'))
            $query->where('create_time','>=',$data['begin_time']);
        if (Common::isWhere($data,'end_time'))
            $query->where('create_time','<=',$data['end_time']);
        $query = $query->order('create_time','desc');
        $dataRes = $query->paginate($data['per_page'], false, ['page' => $data['current_page']])->toArray();
        return  empty($dataRes)? Errors::DATA_NOT_FIND:[true,$dataRes];
    }

    public static function deleteTypicalCase($id,$auth){
        $query = self::get($id);
        if (empty($query)) return Errors::DATA_NOT_FIND;
        if (!Common::authRegion($query->region,$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        // 软删除
        $result = $query->delete();
        return $result > 0?[true,'删除成功']:[false,'删除失败'];
    }
}