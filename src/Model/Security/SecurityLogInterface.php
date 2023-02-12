<?php

namespace App\Model\Security;

use App\Model\Security\User\AdminCreatedSecurityLog;
use App\Model\Security\User\AdminRemovedSecurityLog;
use App\Model\Security\User\LoginFailedSecurityLog;
use App\Model\Security\User\LoginSuccessSecurityLog;
use App\Model\Security\User\OperatorCreatedSecurityLog;
use App\Model\Security\User\OperatorRemovedSecurityLog;
use App\Model\Security\User\ResetPasswordRequestSecurityLog;
use App\Model\Security\User\ResetPasswordSuccessSecurityLog;

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

  const ACTIONS_MAPPING = [
    self::ACTION_USER_LOGIN_SUCCESS => LoginSuccessSecurityLog::class,
    self::ACTION_USER_LOGIN_FAILED => LoginFailedSecurityLog::class,
    self::ACTION_USER_ADMIN_CREATED => AdminCreatedSecurityLog::class,
    self::ACTION_USER_ADMIN_REMOVED => AdminRemovedSecurityLog::class,
    self::ACTION_USER_OPERATOR_CREATED => OperatorCreatedSecurityLog::class,
    self::ACTION_USER_OPERATOR_REMOVED => OperatorRemovedSecurityLog::class,
    self::ACTION_USER_RESET_PASSWORD_REQUEST => ResetPasswordRequestSecurityLog::class,
    self::ACTION_USER_RESET_PASSWORD_SUCCESS => ResetPasswordSuccessSecurityLog::class,
  ];

  public function generateShortDescription(): void;

  public function generateMeta(): void;
}
