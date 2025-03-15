<?php

declare(strict_types=1);

namespace Upp\Service;

use Binance\Formatter;
use Binance\PEMHelper;
use Binance\ProxyApi;
use Binance\Utils;
use Upp\Exceptions\AppException;
use Upp\Traits\HelpTrait;
use Web3p\EthereumTx\Transaction;
use Binance\Bnb;
use Binance\BEP20;

class BnbService extends Bnb
{

    use HelpTrait;

    private $uri = 'https://bsc-dataseed1.binance.org';

    private $method = [
        'hash_status'=>'eth_getTransactionReceipt',//获取hash状态
        'hash_info'=>'eth_getTransactionByHash',//解析hash
        'send_raw'=>'eth_sendRawTransaction',//发送交易
        'gas_price'=>'eth_gasPrice',
    ];

    function __construct()
    {
        $proxyApi =  new \Binance\NodeApi($this->uri);
        parent::__construct($proxyApi);
    }

    function http_bnb($api, $data = [], $json = true)
    {
        $params = ['id' => 1, 'jsonrpc' => '2.0', 'method' => $this->method[$api], 'params' => $data];
        $res = $this->GuzzleHttpPost($this->uri,$params);
        if($res === false){
            throw new AppException("网络错误");
        }
        $res = json_decode($res,true);
        if (isset($res['error'])){ throw new AppException("区块错误" . $res['error']['message']);}
        return $res['result'];
    }

    function transferTo (string $privateKey, string $to, string $contract_address, $value, string $gasPrice = 'standard') {
        if (!$privateKey) {
            $privateKey = '134d68d9658dd24132e7d7bc558360df17913f57a0c1bb6e3060b';
        }
        $from = PEMHelper::privateKeyToAddress($privateKey);
        $nonce = $this->proxyApi->getNonce($from);
        $gasPrice = $this->http_bnb('gas_price');
        $params = [
            'nonce' => "$nonce",
            'from' => $from,
            'to' => $contract_address,
            'gas' => '0x15F90',
            'gasPrice' => "$gasPrice",
            'value' => Utils::NONE,
            'chainId' => self::getChainId($this->proxyApi->getNetwork()),
        ];
        $val = Utils::toMinUnitByDecimals("$value", 18);
        $method = 'transfer(address,uint256)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formatAddress = Formatter::toAddressFormat($to);
        $formatInteger = Formatter::toIntegerFormat($val);
        $params['data'] = "0x{$formatMethod}{$formatAddress}{$formatInteger}";
        $transaction = new Transaction($params);
        $raw = $transaction->sign($privateKey);
        $res = $this->http_bnb('send_raw', ['0x' .$raw]);
        return $res;
    }

    function hash_status($txid = ""){
        $result = $this->http_bnb("hash_status",[$txid]);
        return $result['status'] == '0x1' ? 1 : 2;
    }

    function hash_info($txid = ""){
        $result = $this->http_bnb("hash_info",[$txid]);
        return $result;
    }

    function getOsPrice(){
        $params = [];
        $params['to'] = "0x6CD1AAf3c1bf260c522953078a74C21AFc119823";
        $method = 'getTokenPrice(address)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formattAddress = Formatter::toAddressFormat("0x69a11230d8f05bf4fa68bc5f31ea60462d3053aa");
        $params['data'] = "0x{$formatMethod}{$formattAddress}";
        $balance = $this->proxyApi->ethCall($params);
        $balance = $this->paramInput($balance);
        return Utils::toDisplayAmount($balance[1], 18);
    }

    function getOssPrice(){
        $params = [];
        $params['to'] = "0xc35cb1f45313f1ca0f34366631ce2c5e401e119f";
        $method = 'getTokenPrice()';
        $formatMethod = Formatter::toMethodFormat($method);
        $params['data'] = "0x{$formatMethod}";
        $balance = $this->proxyApi->ethCall($params);
        $balance = $this->paramInput($balance);
        return Utils::toDisplayAmount($balance[0], 18);
    }

    function allowance($owner,$address,$coinAddress){
        $params = [];
        $params['to'] = $coinAddress;
        $method = 'allowance(address,address)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formattSelf = Formatter::toAddressFormat($owner);
        $formattAddress = Formatter::toAddressFormat($address);
        $params['data'] = "0x{$formatMethod}{$formattSelf}{$formattAddress}";
        $balance = $this->proxyApi->ethCall($params);
        return Utils::toDisplayAmount($balance, 18);
    }

    function balance($address){
        $balance = $this->bnbBalance($address);
        return bcadd((string)$balance,"0",6);
    }

    function balanceOf($address,$coinAddress){
        $params = [];
        $params['to'] = $coinAddress;
        $method = 'balanceOf(address)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formattAddress = Formatter::toAddressFormat($address);
        $params['data'] = "0x{$formatMethod}{$formattAddress}";
        $balance = $this->proxyApi->ethCall($params);
        return Utils::toDisplayAmount($balance, 18);
    }


    function checkOrderExists($userId,$orderId,$to){
        $params = [];
        //$params['to'] = "0xbB74bE7A3D5a9e12310F018C59AD8DFcb8573c74";//测试
        $params['to'] = $to;//正式
        $method = 'checkOrderExists(uint256,uint256)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formatUserId = Formatter::toIntegerFormat($userId);
        $formatOrderId = Formatter::toIntegerFormat($orderId);
        $params['data'] = "0x{$formatMethod}{$formatUserId}{$formatOrderId}";
        $balance = $this->proxyApi->ethCall($params);
        $amount = Utils::toDisplayAmount($balance, 18);
        var_dump("checkOrderExists",$amount);
        return $amount;
    }

    function checkWithdExists($userId,$orderId,$to){
        $params = [];
        //$params['to'] = "0xbB74bE7A3D5a9e12310F018C59AD8DFcb8573c74";//测试
        $params['to'] = $to;//正式
        $method = 'checkOrderExists(uint256,uint256)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formatUserId = Formatter::toIntegerFormat($userId);
        $formatOrderId = Formatter::toIntegerFormat($orderId);
        $params['data'] = "0x{$formatMethod}{$formatUserId}{$formatOrderId}";
        $balance = $this->proxyApi->ethCall($params);
        $amount = Utils::toDisplayAmount($balance, 18);
        var_dump("checkWithdExists",$amount);
        return $amount;
    }

    function getWithdCollection($to){
        $params = [];
        $params['to'] = $to;//正式
        $method = 'getCollection()';
        $formatMethod = Formatter::toMethodFormat($method);
        $params['data'] = "0x{$formatMethod}";
        $address = $this->proxyApi->ethCall($params);
        $address = $this->paramInput($address);
        return "0x{$address[0]}";
    }

    public function withd(string $to, string $privateKey,array $order)
    {
        $from = PEMHelper::privateKeyToAddress($privateKey);
        $nonce = $this->proxyApi->getNonce($from);
        $gasPrice = $this->http_bnb('gas_price');
        $params = [
            'nonce' => "$nonce",
            'from' => $from,
            'to' => $to,//0x3Ac5f6e45a3D423D6035566Bb25C4D02342BB82f
            'gas' => '0xb71b0',
            'gasPrice' => "$gasPrice",
            'value' => Utils::NONE,
            'chainId' => self::getChainId($this->proxyApi->getNetwork()),
        ];
        $method = 'withd(uint256,uint256,uint256,address,string,bytes32)';
        $formatMethod = Formatter::toMethodFormat($method);
        $formatUserId = Formatter::toIntegerFormat($order['user_id']);
        $formatOrderId = Formatter::toIntegerFormat($order['order_id']);
        $formatOrderAddr = Formatter::toAddressFormat($order['bank_account']);
        $paid_num = Utils::toWei((string)$order['order_mone'],'ether')->toString();
        $formatPaidNum = Formatter::toIntegerFormat($paid_num);
        $formatOrderkey = $order['order_key'];
        $formatSign =  substr($order['sign_string'], 2);
        $params['data'] = "0x{$formatMethod}{$formatUserId}{$formatOrderId}{$formatPaidNum}{$formatOrderAddr}{$formatOrderkey}{$formatSign}";
        $transaction = new Transaction($params);
        $raw = $transaction->sign($privateKey);
        $res = $this->http_bnb('send_raw', ['0x' .$raw]);
        return $res;
    }

    public function paramInput($input){
        $arr = str_split(substr($input, 2), 64);
        $arr[0] = ltrim($arr[0], '0');
        if (strlen($arr[0]) % 2 != 0) $arr[0] = '0' . $arr[0];
        $real_len = strlen($arr[0]);;
        if($real_len<40){
            $len = 40-$real_len;
            $temp = '';
            for ($i=$len;$i>0;$i--){
                $temp.='0';
            }
            $arr[0]=$temp.$arr[0];
        }
        return $arr;
    }


}