<?php

namespace Hiboutik\OAuth\Client;


function autoload($class_name) {
  $path =  __DIR__.'/../../'.str_replace('\\','/', $class_name).'.php';
  if(is_readable($path)) {
    require $path;
  }
}

spl_autoload_register("Hiboutik\OAuth\Client\autoload");
