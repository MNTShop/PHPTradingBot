<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:10 AM
 */

namespace HotBitSDK\WS;

use HotBitSDK\WS\Interfaces\WSResponseInterface;

class WSResponse implements WSResponseInterface
{
    /** Code
     * @var string
     */
    protected $code='';

    /** Message
     * @var string
     */
    protected $message='';

    /**  data
     * @var mixed
     */
    protected $data;

    /** Raw Response in Json
     * @var string
     */
    protected $raw='';

    /**
     * Topic response
     * @var string
     */
    protected $topic;

    protected $timestamp;


    public function __construct(string $raw)
    {
        $this->raw = $raw;
        $json = json_decode($raw);
        $this->code = $json->code;
        if($this->isNormal()){
            $this->data = $json->data;
            $this->timestamp = $json->timestamp;
            $this->topic = $json->topic;
        }else{
              if($this->code ==0){
                  $this->message = 'Pong';
              }
              if(isset($json->msg)){
                  $this->message = $json->msg;
              }
        }

    }


    public function getData(){
        return $this->data;
    }

    public function getCode(){
        return $this->code;
    }
    public function getRaw():string{
        return $this->raw;
    }
    public function getTopic():string{
        return $this->topic;
    }
    public function getTimestamp()
    {
        return $this->timestamp;
    }
    public function getMessage(): string
    {
        return $this->message;
    }

    public function isError():bool{
        if((int)$this->code>=10000){
            return true;
        }
        return false;
    }
    public function isAuthKeySuccess():bool{
        if($this->code=='00000'){
            return true;
        }
        return false;
    }
    public function isSubscribed():bool{
        if($this->code=='00001'){
            return true;
        }
        return false;
    }
    public function isConnected():bool{
        if($this->code=='00002'){
            return true;
        }
        return false;
    }
    public function isUnSubscribed():bool{
        if($this->code=='00003'){
            return true;
        }
        return false;
    }
    public function isInit():bool{
        if($this->code=='00006'){
            return true;
        }
        return false;
    }
    public function isNormal():bool{
        if($this->code=='00007'){
            return true;
        }
        return false;
    }


}