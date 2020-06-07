<?php
/**
 * Created by PhpStorm.
 * User: qingyu.gou
 * Date: 2020/3/19
 * Time: 11:10 AM
 */

namespace HotBitSDK\REST;

use HotBitSDK\REST\Interfaces\RESTResponseInterface;

class RESTResponse implements RESTResponseInterface
{

    /** Message
     * @var string
     */
    protected $message='';

    /** Code
     * @var string
     */
    protected $code='';

    /**  data
     * @var mixed
     */
    protected $data;

    /** Raw Response in Json
     * @var string
     */
    protected $raw='';


    public function __construct(string $raw)
    {
        $this->raw = $raw;
        $json = json_decode($raw);
        $this->code = $json->code;
        $this->message = $json->msg;
        $this->data = $json->data;
    }


    public function getData(){
        return $this->data;
    }

    public function getMessage():string{
        return $this->message;
    }
    public function getCode(){
        return $this->code;
    }
    public function getRaw():string{
        return $this->raw;
    }
    public function isError():bool{
        if($this->code!=0){
            return true;
        }
        return false;
    }
}