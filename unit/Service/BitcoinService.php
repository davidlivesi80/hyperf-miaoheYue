<?php

declare(strict_types=1);

namespace Upp\Service;

use Upp\Traits\HelpTrait;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39Mnemonic;

class BitcoinService
{

    use HelpTrait;

    function generateMnemonic()
    {
        $random = new Random();
        // 生成随机数(initial entropy)
        $entropy = $random->bytes(Bip39Mnemonic::MIN_ENTROPY_BYTE_LEN); //
        $bip39 = MnemonicFactory::bip39();
        // 通过随机数生成助记词
        $mnemonic = $bip39->entropyToMnemonic($entropy);
        return $mnemonic;
    }

    /*加密助记词*/
    public function mnemonicEncrypt($mnemonic,$key){
        if(empty($mnemonic)){ return "";}
        $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
        $decrypted = openssl_encrypt($mnemonic, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
        return base64_encode($decrypted);
    }

    /**解密助记词
     * 旧数据key=ghqkl2019/gh_shop
     */
    public function mnemonicDecrypt($mnemonic,$key){
        if(empty($mnemonic)){ return "";}
        $key = substr(openssl_digest(openssl_digest($key, 'sha1', true), 'sha1', true), 0, 16);
        return openssl_decrypt(base64_decode($mnemonic), 'AES-128-ECB', $key, OPENSSL_RAW_DATA);
    }





}