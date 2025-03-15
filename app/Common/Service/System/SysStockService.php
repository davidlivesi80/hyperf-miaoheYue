<?php

namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysStockLogic;
use Upp\Exceptions\AppException;
use Hyperf\DbConnection\Db;
use Upp\Bsctool\Tools\NodeClient;
use Upp\Bsctool\Tools\Credential;
use Upp\Bsctool\Tools\Kit;


class SysStockService extends BaseService
{
    
    
    /**
     * @var SysStockLogic
     */
    public function __construct(SysStockLogic $logic)
    {
        $this->logic = $logic;
    }


    /**
     * 查询搜索
     */
    public function search(array $where, $page=1, $perPage = 10){

        $list = $this->logic->search($where)->with(['card'=>function($query){
       
        	return $query->select('title','power','price','image','id');
        	
        }])->paginate($perPage,['*'],'page',$page);

        return $list;
    }
    
    /**
     * 查询搜索
     */
    public function searchApi(array $where,$page=1, $perPage = 10){

        $list = $this->logic->search($where)->with(['card'=>function($query){
       
        	return $query->select('title','power','price','image','id');
        	
        }])->get();

        return $list;
    }
    
     /**
     * 查询搜索
     */
    public function searchOne(array $where){

        $info = $this->logic->search($where)->with(['card'=>function($query){
       
        	return $query->select('title','power','price','image','id');
        	
        }])->first();

        return $info;
    }
    
    /**
     * 生成卡片
    */
    public function create($tokenId){
        
        $_order['token_id']   =  $tokenId;
        
        $group = Db::transaction(function () use ($_order){
            
            //创建卡片
            try {
                $order = $this->logic->create($_order);
            } catch (\Throwable $e) {
                throw new AppException('创建失败',400);
            }
            
            return true;
        });
        
        return $group;
     
    }
    
    
    
    /**
     * 实例化合约
     */
    public function initCard(){
        
        $contract = $this->app(SysContractService::class)->findWhere('contract_name',strtolower('nft'));
        
        $token =  $this->app(SysContractService::class)->initToken($contract);

        return $token;
    }
    
 
    
    /**
     * 单个生成
    */
    public function singleCreate(){
        
        $tokenTx = $this->initCard()->create();
        if($tokenTx){
            $tokenSupply = $this->initCard()->totalSupply();
            $tokenId = $this->initCard()->tokenByIndex($tokenSupply->toString() - 1);
            if($tokenId->toString()){
                $this->create($tokenId);
            }
        }
        
        return true;
    }

    /**
     * 批量生成
    */
    public function batchCreate(){
        
        $tokenIds = [];
        
        foreach ($tokenIds as $tokenId){
            $this->create($tokenId);
        }
        
        return true;
    }
    
    /**

     * 删除用户

     */
    public function remove($id){

        $card = $this->logic->find($id);

        if($card->status == 1){
            throw new AppException('已分配，不可删除',400);
        }
        
        Db::beginTransaction();

        try {

            $this->logic->remove($id);


            Db::commit();

        } catch(\Throwable $e){

            Db::rollback();

            throw new AppException($e->getMessage());

        }

    }
    
    public function getTokenIds($username)
    {
        $tokenIds = [];
        
        $token = $this->initCard();
        
        $tokenNums = $token->getPlayerItem($username);
  
        foreach ($tokenNums as $value){
            $tokenIds[] = $value->toString();
        }
        

        return $tokenIds;

    }
    
    public function getOwnerOf($token_id)
    {
        $token = $this->initCard();
        
        if($token_id){
            $owner = $token->ownerOf($token_id);
        }else {
            $owner = '';
        }
        
        
        return $owner;
    }
    
    public function isLock($token_id)
    {
        $token = $this->logic->find($token_id);
        
        if($token->locked){
            return true;
        }else {
            return false;
        }
    }
    
    public function getTokenAdmin()
    {
        
        return '0x459fb18d0566A4686A1B15CdB7f72aA5EA217300';
        
    }
   
}