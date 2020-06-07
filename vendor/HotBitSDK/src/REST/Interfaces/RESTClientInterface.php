<?php
/**
 * Created by PhpStorm.
 * User: devacc
 * Date: 26.05.2020
 * Time: 17:03
 */

namespace HotBitSDK\REST\Interfaces;

use HotBitSDK\REST\RESTResponse;

interface RESTClientInterface
{
    public function getResponse(RESTRequestInterface $request):RESTResponse;
    public function getRequest():RESTRequestInterface;
}