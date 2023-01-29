<?php

namespace App\Model\Security;

interface SecurityLogInterface
{

  const SOURCE_WEB = 'web';
  const SOURCE_API = 'api';
  const SOURCE_CLI = 'cli';

  const ACTION_USER_LOGIN_SUCCESS = 'user.login_success';
  const ACTION_USER_LOGIN_FAILED = 'user.login_failed';
  const ACTION_USER_ADMIN_CREATED = 'user.admin_created';
  const ACTION_USER_ADMIN_REMOVED = 'user.admin_removed';
  const ACTION_USER_OPERATOR_CREATED = 'user.operator_created';
  const ACTION_USER_OPERATOR_REMOVED = 'user.operator_removed';
  const ACTION_USER_RESET_PASSWORD_REQUEST = 'user.reset_password_request';
  const ACTION_USER_RESET_PASSWORD_SUCCESS = 'user.reset_password_success';

  public function generateShortDescription(): void;

  public function generateMeta(): void;
}
