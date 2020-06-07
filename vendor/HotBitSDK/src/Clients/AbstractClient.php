<?php
/**
 * Created by PhpStorm.
 * User: devacc
 * Date: 26.05.2020
 * Time: 19:29
 */

namespace HotBitSDK\Clients;

abstract class AbstractClient
{
    /**
     * BitHumbGlobal Api key required by authentication of request，apply for the key in website.
     * @var string
     */
    protected $apiKey;
    /**
     *  BitHumbGlobal Api secret key
     * @var string
     */
    protected $secretKey;

    /**
     * the paramter will be need in request which need to authenticate, if space of time too long between current and request will be reject.
     * @var int
     */
    protected $timestamp;

    public function __construct(string $apiKey = '', string $secretKey = '', int $timestamp = 0)
    {
        $this->apiKey = $apiKey;
        $this->timestamp = $timestamp;
        $this->secretKey = $secretKey;
    }

}