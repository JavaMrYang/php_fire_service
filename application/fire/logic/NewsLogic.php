<?php
/**
 * Created by PhpStorm.
 * User: userYang
 * Date: 2018/7/3
 * Time: 15:10
 */
namespace app\fire\logic;

use app\fire\controller\Common;
use app\fire\controller\Errors;
use app\fire\model\office\NewsModel;
use app\fire\model\UserModel;

class NewsLogic{

    function addNews($data,$auth){
        if(Common::isWhere($data,'region')&&!Common::authRegion($data['region'],$auth['s_region']))
            return Errors::REGION_PREMISSION_REJECTED;
        if($auth['s_role']==3) return [false,'您是超级管理员,您不能发布'];
        if($auth['s_role']==1){ //如果角色是普通用户
            $data['is_assgin']=0; //设置为未指派
            $data['status']=0; //设置为待指派
        }
        if($auth['s_role']==2){
            $data['is_admin']=1; //设置为管理员发布
            $data['status']=2; //设置为已发布
        }
        $user=UserModel::getUserDetailByUid($auth['s_uid']);
        if($user[0]) {
            $data['publish_name'] = $user[1]['name'];
            if (!Common::isWhere($data, 'author')) $data['author'] = $user[1]['name'];
        }
        if(Common::isWhere($data,'content')){
            $path =  'news' . DS .strtotime('now').'_'.$auth['s_uid'].'.html';
            $data['url'] = Common::changeHtml($data['content'],$auth['s_uid'],$path);
            $data['content']=strip_tags($data['content']);
        }
        $news=NewsModel::create($data);
        return empty($news)?Errors::ADD_ERROR:[true,$news->id];
    }

    function deleteNews($id,$auth){
        $model=NewsModel::get($id);
        if(empty($model)) return Errors::DATA_NOT_FIND;
        if(!Common::authRegion($model->region,$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
        $result=$model->delete();
        return $result?[true,$model->id]:Errors::DELETE_ERROR;
    }

    function getNewListByCondition($data,$auth){
        $query=NewsModel::alias('n') ->join('tb_region r','r.id = n.region')
            ->join('tb_region r1','r1.id = r.parentId','left')
            ->join('tb_region r2','r2.id = r1.parentId','left')
            ->join('tb_region r3','r3.id = r2.parentId','left')
            ->join('tb_region r4','r4.id = r3.parentId','left')
            ->with('assign');
        if(Common::isWhere($data,'region')){
           if(!Common::authRegion($data['region'],$auth['s_region'])) return Errors::REGION_PREMISSION_REJECTED;
           $query->whereLike('n.region',$data['region'].'%');
        }else $query->whereLike('n.region',$auth['s_region'].'%');
        if(Common::isWhere($data,'start_time')) $query->whereTime('create_time','>=',$data['start_time']);
        if(Common::isWhere($data,'end_time')) $query->whereTime('create_time','<=',$data['end_time']);
        if(Common::isWhere($data,'news_type')) $query->where('news_type',$data['news_type']);
        if(Common::isWhere($data,'status')) $query->where('status',$data['status']);


    }
}