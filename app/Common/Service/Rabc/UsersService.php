<?php


namespace App\Common\Service\Rabc;

use Upp\Basic\BaseService;
use App\Common\Logic\Rabc\UsersLogic;
use Upp\Exceptions\AppException;
use Hyperf\DbConnection\Db;
use PragmaRX\Google2FA\Google2FA;
use Upp\Service\ParseToken;

class UsersService extends BaseService
{

    /**
     * @var UsersLogic
     */
    public function __construct(UsersLogic $logic)
    {
        $this->logic = $logic;
    }

    /**

     * 检查用户名是否已存在

     */

    public function checkName($name, $id = null)
    {

        $res = $this->logic->fieldExists('manage_name', $name,$id);

        if($res){
            throw new AppException('用户名已被注册,请重新填写',400);
        }

    }

    /**

     * 生成加密盐

     */

    public function getPasswordSalt()

    {
        $salt_string = 'abcdefghijklmnopqrstuvwxyz0123456789';

        $saltLen = mt_rand(4, 10);

        $randStrLen = strlen($salt_string);

        $string = "";

        for ($i = 1; $i <= $saltLen; $i++) {

            $string .= $salt_string[$randStrLen - 1];

        }

        return $string;

    }



    /**

     * 加密函数

     */

    public function getPassword($password, $passwordSalt)

    {
        return md5(md5('admin'. $password) . $passwordSalt);

    }



    /**

     * 验证密码

     */

    public function checkPassword($entity, $password)
    {

        return $this->getPassword($password, $entity->password_salt) === $entity->password;

    }

    /**

     * 登录处理

     */

    public function doLogin($accout, $password,$secret='')

    {

        $userInfo =  $this->logic->findWhere('manage_name',$accout);
       
        if (!$userInfo) {

            throw new AppException('账号错误',400);

        }


        if (!$this->checkPassword($userInfo,$password)) {

            throw new AppException('密码错误',400);

        }
        
        //var_dump($userInfo->google2fa_secret);
        //var_dump($secret);
        //var_dump((new Google2FA())->generateSecretKey());

        $valid = (new Google2FA())->verifyKey($userInfo->google2fa_secret, $secret);
        if(!$valid){
            throw new AppException('验证码错误',400);
        }

        $token = $this->app(ParseToken::class)->toToken($userInfo->id,$userInfo->manage_name,'sys');


        return  ['adminId'=>$userInfo->id,'username'=>$userInfo->manage_name,'token'=>$token['token']];

    }




    /**

     * 添加用户

     */

    public function create($data){

        Db::beginTransaction();

        try {
            $record['manage_name'] = $data['manage_name'];

            $password_salt = $this->getPasswordSalt();

            $record['password_salt'] = $password_salt;

            $record['password'] = $this->getPassword($data['password'], $password_salt);
            
            $record['google2fa_secret'] = (new Google2FA())->generateSecretKey();

            $entity = $this->logic->create($record);

            foreach ($data['roleIds'] as $groupId) {

                $this->app(UserGroupService::class)->create($entity->id, $groupId);

            }

            Db::commit();

        } catch(\Throwable $e){

            Db::rollback();

            throw new AppException($e->getMessage(),400);

        }
    }

    /**

     * 编辑用户

     */

    public function update($id ,$data){


        Db::beginTransaction();

        try {
            
            $record['manage_name'] = $data['manage_name'];
            
            if ($data['password']) {

                $password_salt = $this->logic->idsField($id,'password_salt');

                $record['password'] = $this->getPassword($data['password'], $password_salt);

            }
 
            $this->logic->update($id,$record);
            
            //清除所有
            $this->app(UserGroupService::class)->getQuery()->where('user_id',$id)->delete();
            
            foreach ($data['roleIds'] as $groupId) {
                
                $insert = ['user_id'=>$id,'group_id'=>$groupId];
                
                $insertAttr[] = $insert;
                
            }
            
            $this->app(UserGroupService::class)->getQuery()->insert($insertAttr);

            Db::commit();

            return true;

        } catch(\Throwable $e){

            Db::rollBack();

            throw new AppException($e->getMessage(),400);
        }

    }
    
    /**

     * 重置密码

     */

    public function reset($id,$newPsw){

        $entity = $this->logic->find($id);

        $data['password'] = $this->getPassword($newPsw, $entity->password_salt);
        
        $data['google2fa_secret'] = (new Google2FA())->generateSecretKey();

        return $this->logic->update($id,$data);

    }


    /**

     * 修改密码

     */

    public function pass($id ,$oldPsw,$newPsw){

        $entity = $this->logic->find($id);

        if (!$this->checkPassword($entity,$oldPsw)) {

            throw new AppException('原密码错误',400);

        }

        $data['password'] = $this->getPassword($newPsw, $entity->password_salt);

        return $this->logic->update($id,$data);

    }

    /**
     * 查询构造
     */
    public function search(array $where,$page=1, $perPage = 10){

        $list = $this->logic->search($where)->with('roles')->paginate($perPage,['*'],'page',$page);

        return $list;

    }

    /**

     * 删除用户

     */
    public function remove($manageId){

        if($manageId == 1){
            throw new AppException('Admin禁止删除',400);
        }

        Db::beginTransaction();

        try {


            $this->logic->remove($manageId);


            $this->app(UserGroupService::class)->getQuery()->where('user_id',$manageId)->delete();

            Db::commit();


        } catch(\Throwable $e){

            Db::rollBack();

            throw new AppException($e->getMessage());
        }

    }

    /**

     * 批量删除用户

     */
    public function batch($manageIds){

        Db::beginTransaction();

        try {
            $this->logic->batch($manageIds);

            $this->app(UserGroupService::class)->getQuery()->whereIn('user_id',$manageIds)->delete();

            Db::commit();


        } catch(\Throwable $e){

            Db::rollBack();

            throw new AppException($e->getMessage());
        }

    }

    /**

     * 检查权限

     */

    public function auther($manageId,$path = '')
    {

        $menus = $this->getMenus($manageId);

        if (in_array('all', $menus)) {
            return true;
        }
        //截取前缀
        $paths = explode('/',substr($path,4, strlen($path) - 4));
        //组合控制器和方法为权限
        if(isset($paths[1])){
            $auth = $paths[0] . ':' . $paths[1];
        }else{
            $auth = $paths[0];
        }
        $power =  $this->app(PowerService::class)->findWhere('authority', $auth);
        if (!$power) {
            return false;
        }
        if (!in_array($power->id, $menus)) {
            return false;
        }

        return true;

    }

    /**
     *
     */
    public function getMenus(int $manageId)
    {

        $groupIds = $this->app(UserGroupService::class)->getQuery()->where('user_id',$manageId)->pluck('group_id');

        $menuIds = [];

        $groups = $this->app(GroupService::class)->getQuery()->whereIn('id', $groupIds)->get()->toArray();

        foreach ($groups as $group) {

            $menuIds = array_merge($menuIds, explode(',',$group['authIds']));

        }

        return array_unique($menuIds);

    }


}