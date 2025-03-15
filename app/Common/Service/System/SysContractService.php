<?php


namespace App\Common\Service\System;

use Upp\Basic\BaseService;
use App\Common\Logic\System\SysContractLogic;
use Upp\Exceptions\AppException;
use Upp\Bsctool\Tools\NodeClient;
use Upp\Bsctool\Tools\Credential;
use Upp\Bsctool\Tools\Kit;

class SysContractService extends BaseService
{

    /**
     * @var SysContractLogic
     */
    public function __construct(SysContractLogic $logic)
    {
        $this->logic = $logic;
    }
    
    /**

     * 检查key

     */
    public function checkName($key)
    {

        $res =  $this->logic->fieldExists('contract_name',$key);

        if($res){
            throw new AppException('合约已存在');
        }

    }
 
    /**
     * 查询搜索
     */
    public function search(array $where){

        $list = $this->logic->search($where)->get()->toArray();

        return $list;
    }

    
    /**
     * 实例化合约
     */
    public function initToken($coin,$pass=''){
        
        $kit = $this->initKit($pass);
        
        if(!$kit){
            return false;
        }
        
        $tokenAddr = $coin->contract_address;
        
        $tokenAbis = $coin->contract_abi;

        $token = $kit->bep20($tokenAddr,$tokenAbis);

        return $token;
    }
    
    /**
     * 实例化bnb
     */
    public function initKit($pass){
        
        $privateKey  = $pass ? $pass : 'cb6b3b03c2f7d2bc5c361a2281ced2482a5575e9e259ec4df052f208a01a6cf7';
        $kit = new Kit(
            NodeClient::create("mainNet"),
            Credential::fromKey($privateKey)
        );
       
        return $kit;
    }
    

}