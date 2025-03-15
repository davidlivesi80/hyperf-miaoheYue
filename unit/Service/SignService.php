<?php

declare(strict_types=1);

namespace Upp\Service;

use  Binance\Utils;

class SignService
{
    /**
     * Return hash of the ethereum transaction with/without signature.
     *
     * @param bool $includeSignature hash with signature
     * @return string hex encoded hash of the ethereum transaction
     */

    public function signPower($order)
    {
        $txData[0] = $order['user_id'];
        $txData[1] = $order['order_id'];
        $num = Utils::toWei((string)$order['num'],'ether');
        $os = Utils::toWei((string)$order['os'],'ether');
        $txData[2] = $num->toString();
        $txData[3] = $os->toString();
        $txData[4] = $order['pay_type'];
        $txData[5] = "OsBnbThereum25#6";
        $hash = Utils::sha3(implode('&',$txData));
        return $hash;
    }

    public function signAfeo($order)
    {
        $txData[0] = $order['user_id'];
        $txData[1] = $order['order_id'];
        $num = Utils::toWei((string)$order['num'],'ether');
        $os = Utils::toWei((string)$order['os'],'ether');
        $txData[2] = $num->toString();
        $txData[3] = $os->toString();
        $txData[4] = $order['pay_type'];
        $txData[5] = "OsBnbThereum25#6";
        $hash = Utils::sha3(implode('&',$txData));
        return $hash;
    }

    /**
     * Return hash of the ethereum transaction with/without signature.
     *
     * @param bool $includeSignature hash with signature
     * @return string hex encoded hash of the ethereum transaction
     */

    public function signRuin($order)
    {
        $txData[0] = $order['user_id'];
        $txData[1] = $order['order_id'];
        $num = Utils::toWei((string)$order['num'],'ether');
        $os = Utils::toWei((string)$order['os'],'ether');
        $txData[2] = $num->toString();
        $txData[3] = $os->toString();
        $txData[4] = $order['pay_type'];
        $txData[5] = "OsBnbThereum25#6";
        $hash = Utils::sha3(implode('&',$txData));
        return $hash;
    }

    /**
     * Return hash of the ethereum transaction with/without signature.
     *
     * @param bool $includeSignature hash with signature
     * @return string hex encoded hash of the ethereum transaction
     */
    public function signWithd($order)
    {
        $txData[0] = $order['id'];
        $txData[1] = $order['user_id'];
        $txData[2] = $order['series_id'];
        $txData[3] = $order['amount'];
        $txData[4] = $order['to'];
        $txData[5] = $order['symbol'];
        $txData[6] = "OsBnbWithd25#6";
        $hash = Utils::sha3(implode('&',$txData));
        return $hash;
    }

    /**
     * Return hash of the ethereum transaction with/without signature.
     *
     * @param bool $includeSignature hash with signature
     * @return string hex encoded hash of the ethereum transaction
     */
    public function signCash($order)
    {
        $txData[0] = $order['user_id'];
        $txData[1] = $order['order_id'];
        $order_amount = Utils::toWei((string)$order['order_mone'],'ether');
        $txData[2] = $order_amount->toString();
        $order_rate = Utils::toWei((string)$order['order_rate'],'ether');
        $txData[3] = $order_rate->toString();
        $txData[4] = strtolower($order['bank_account']);
        $txData[5] = "OsBnbCash25#6";
        $hash = Utils::sha3(implode('&',$txData));
        return $hash;
    }
    
    /**
     * Return hash of the ethereum transaction with/without signature.
     *
     * @param bool $includeSignature hash with signature
     * @return string hex encoded hash of the ethereum transaction
     */
    public function signPadian($order)
    {
        $txData[0] = $order['user_id'];
        $txData[1] = $order['order_id'];
        $order_amount = Utils::toWei((string)$order['order_mone'],'ether');
        $txData[2] = $order_amount->toString();
        $order_rate = Utils::toWei((string)$order['order_rate'],'ether');
        $txData[3] = $order_rate->toString();
        $txData[4] = strtolower($order['bank_account']);
        $txData[5] = "OsBnbTixian25#6";
        $hash = Utils::sha3(implode('&',$txData));
        return $hash;
    }


}