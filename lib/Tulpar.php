<?php
namespace Tulpar;
class Tulpar
{
    public static function _ReturnSucces($message, $html, $data=[], $redirect=''){
        $data['status'] = 1;
        $data['message'] = $message;
        $data['html'] = $html;
        $data['redirect'] = $redirect;
        $data['data'] = $data;
        return self::_ReturnResponse($data);
    }
    public static function _ReturnError($message, $html, $errors=[]){
        $data['status'] = 0;
        $data['message'] = $message;
        $data['html'] = $html;
        $data['errors'] = implode('<br>', $errors);
        return self::_ReturnResponse($data);
    }
    public static  function _ReturnResponse($data){
        $data['status'] = isset($data['status'])?$data['status']:1;
        $data['date'] = date('Y-m-d H:i:s');
        $data['ver'] = env('VER','1.0.0');
        $data['data'] = isset($data['data'])?$data['data']:[];
        $data['message'] = isset($data['message'])?$data['message']:'';
        $data['html'] = isset($data['html'])?$data['html']:'';
        $data['errors'] = isset($data['errors'])?$data['errors']:'';
        $data['redirect'] = isset($data['redirect'])?$data['redirect']:'';
        return $data;
    }
}
