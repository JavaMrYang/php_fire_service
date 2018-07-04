<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/5/10
 * Time: 9:00
 */

namespace app\fire\model;


use app\fire\controller\Common;
use app\fire\controller\Errors;
use think\Exception;
use think\Model;

class RegionModel extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'tb_region';
    //设置主键ID
    protected $pk = 'id';

    function joinParentId(){
        return $this->hasOne('region','parentId','id');
    }

    static function getRegionById($id){
        try{
            $region = RegionModel::where('id',$id)
                ->field('id value, name lable ,level')
                ->find();
            return !empty($region) ? [true, $region->toArray()] : Errors::DATA_NOT_FIND;
        }catch (Exception $exception){
            return Errors::Error($exception->getMessage());
        }
    }

    static function getRegionByName($name){
        $region=RegionModel::where('name',$name)
            ->field('id regionId,name')->find();
        return !empty($region) ? [true, $region->toArray()] : Errors::DATA_NOT_FIND;
    }

    static function getRegionNameById($region){
        $r_name = self::alias('r')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left')
            ->where('r.id',$region)
            ->field('r4.name r4,r3.name r3,r2.name r2,r1.name r1,r.name this')
            ->find();
        if(empty($r_name)) return [false,'网络错误'];
        $region_name = $r_name['r4'].$r_name['r3'].$r_name['r2'].$r_name['r1'].$r_name['this'];
        $r_name['region_name'] = $region_name;
        $r_name = Common::removeEmpty($r_name);
        return [true,$r_name];
    }

    static function getRegionFullNameById($region)
    {
        $level = RegionModel::where('id',$region)
            ->field('level')
            ->find();
        if(empty($level)) return Errors::DATA_NOT_FIND;
        $dataRes = RegionModel::alias('r0');
        if($level["level"]==1) $dataRes->field('r0.name r0');
        if($level["level"]>1)
            $dataRes->join('tb_region r1', 'r0.parentId = r1.id ', 'left')
            ->field('r0.name r0,r1.name r1');
        if($level["level"]>2)
            $dataRes->join('tb_region r2', 'r1.parentId = r2.id', 'left')
            ->field('r0.name r0,r1.name r1,r2.name r2');
        if($level["level"]>3)
            $dataRes->join('tb_region r3', 'r2.parentId = r3.id', 'left')
            ->field('r0.name r0,r1.name r1,r2.name r2,r3.name r3');
        if($level["level"]>4)
            $dataRes->join('tb_region r4', 'r3.parentId = r4.id', 'left')
            ->field('r0.name r0,r1.name r1,r2.name r2,r3.name r3,r4.name r4');
        $dataRes = $dataRes->where('r0.id',$region)->find();
        if(empty($dataRes)) return Errors::DATA_NOT_FIND;
        $dataRes=$dataRes->toArray();
        $region_name = null;
        foreach ($dataRes as $value){
            $region_name = $region_name.$value;
        }
        $dataRes['region_name'] = $region_name;
        return [true, $dataRes];
    }
}