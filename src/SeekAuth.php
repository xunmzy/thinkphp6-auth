<?php
// +----------------------------------------------------------------------
// | Created by PHPstorm: 百度网盘管理 [ 百度网盘管理 ] 
// +----------------------------------------------------------------------
// | Copyright (c) 2023 [xunmzy] All rights reserved.
// +----------------------------------------------------------------------
// | SiteUrl: https://www.seekxm.com
// +----------------------------------------------------------------------
// | Author: 寻梦资源网 <seekxm@qq.com>
// +----------------------------------------------------------------------
// | Date: 2023/4/8-3:36
// +----------------------------------------------------------------------
// | Description:  
// +----------------------------------------------------------------------
namespace Seek;

use think\facade\Config;
use think\facade\Db;
use think\facade\Request;

class SeekAuth
{
    protected $config = [
        'auth_on' => true,                      // 认证开关
        'auth_type' => 2,                         // 认证方式，1为实时认证；2为缓存认证。
        'auth_group' => 'auth_group',        // 用户组数据表名
        'auth_group_access' => 'auth_group_access', // 用户-用户组关系表
        'auth_rule' => 'auth_rule',         // 权限规则表
        'auth_user' => 'auth_admin',             // 用户信息表
        'auth_white_list' => [          // auth白名单不会验证规则
            'index/index','index/home','index/logout','bdauth/addbdauthtoken','bdauthinfo/index','admin/bdlist'
        ]
    ];
    
    public function __construct()
    {
        if (Config::has('seek_auth')) {
            $this->config = Config::get('seek_auth');
        }
    }
    
    public function check($uid,$path = ''): bool
    {
        if (!$this->config['auth_on']) {
            return true;
        }
        if(empty($path)){
            $controller = Request::controller(); // 获取控制器名称
            $action = Request::action(); // 获取方法名称
            $path = strtolower($controller.'/'.$action);
        }
//        验证白名单
        if(in_array($path,$this->config['auth_white_list'])){
            return true;
        }
        
        $authList = $this->getAuthList($uid);
        if (!empty($authList)) {
            foreach ($authList as $list) {
                if($path == $list){
                    return true;
                }
            }
        }
        
        return false;
        
    }
    
    public function getAuthList($uid): array{
//        获取用户的用户组
        $group_id_arr = Db::view($this->config['auth_group_access'], 'uid,group_id')
            ->view($this->config['auth_group'], 'name,rules', "{$this->config['auth_group_access']}.group_id={$this->config['auth_group']}.id")
            ->where(['uid' => $uid, 'status' => 1])
            ->select()
            ->toArray();
        
        $gid_arr = []; // 保存用户组权限规则id
        foreach ($group_id_arr as $gid){
            $gid_arr = array_merge($gid_arr,explode(',',trim($gid['rules'],',')));
        }
        $gid_arr = array_unique($gid_arr);
        if(in_array('all',$gid_arr)){
            return [];
        }
        
        $where = [
            ['id','in',$gid_arr],
            'status' => 1,
        ];
        $rules_arr = Db::name($this->config['auth_rule'])->where($where)->field('path')->select()->toArray();
        return $rules_arr;
        
    }
}