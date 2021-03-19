<?php

namespace Hiboutik\OAuth;


interface ClientInterface
{
  public function setScope($scope);
  public function validSession();
  public function showToken();
  public function getRefreshToken($refresh_token, HttpRequest $hr = null);
  public function setTemplateInstall(Callable $template);
  public function setTemplateResult(Callable $template);
  public function run();
}
