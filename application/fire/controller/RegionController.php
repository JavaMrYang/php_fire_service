<?php
/**
 * Created by PhpStorm.
 * User: XieLe
 * Date: 2018/6/1
 * Time: 9:37
 */

namespace app\fire\controller;


use app\fire\model\RegionModel;

class RegionController
{

    function getRegionList(){
        $auth = Common::auth();
        if (!$auth[0]) return Common::reJson($auth);
        $region = $auth[1]['s_region'];
        $dbRes=RegionModel::field("id as  value, name as label , parentId ")
            ->whereLike('id','43%')->select()->toArray();
        return json(self::list_to_tree($dbRes,$region));
    }

    private static function list_to_tree($list,$region, $pk='value',$pid = 'parentId',$child = 'children',$root='0') {
        // 创建Tree
        $tree = array();
        if(is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                if(strlen($data['value']) <= strlen($region)){
                    if ($data['value'] != substr($region,0,strlen($data['value']))){
                        unset($list[$key]);
                    }
                }else{
                    if ($region != substr($data['value'],0,strlen($region))){
                        unset($list[$key]);
                    }
                }
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                }else{
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent[$child][] =& $list[$key];
                    }
                }
                unset($list[$key]['parentId']);
            }
        }
        $tree = array_filter($tree);
        return $tree;
    }

    function getRegion(){
        $data = Common::getPostJson();
        $result = RegionModel::where('parentId',$data['parentId'])->field('id,name')->select();
        return empty($result) ? Common::reJson(Errors::DATA_NOT_FIND):Common::reJson([true,$result]);
    }
}