<?php

namespace DMA\LACMA\Classes;

class LacmaEventHandler {

  public function subscribe($events)
  {
    $events->listen('auth.preRegister', 'DMA\LACMA\Classes\LacmaEventHandler@onPreAuthRegister');
  }

  public function onPreAuthRegister(&$data, $rules)
  {
    if (empty($data['email'])) {
      $data['email'] = "lacma-dummy" . md5(microtime()) . "@email.com";
    }
  
    return $data;
  }
}
