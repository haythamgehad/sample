<?php

namespace App\Constants;

/**
 * Class TranslationCode
 *
 * @package App\Constants
 */
class TranslationCode
{
    /* Misc */
    const ERROR_APPLICATION = 'errors.application';
    const ERROR_UNAUTHORIZED = 'error.unauthorized';
    const ERROR_FORBIDDEN = 'error.forbidden';
    const ERROR_NOT_FOUND = 'error.notFound';

    /* Offer */
    const OFFER_ERROR_NAME_REQUIRED='error.name.required';
    const OFFER_ERROR_ORGANIZATION_REQUIRED='error.organization.name.required';
    const OFFER_ERROR_EMAIL_REQUIRED='error.email.required';
    const OFFER_ERROR_EMAIL_INVALID='error.email.invalide';
    const OFFER_ERROR_MOBILE_REQUIRED='error.mobile.required';
    const OFFER_LISTED_SUCCESSFULLY='dddd';

    const HELP_LISTED_SUCCESSFULLY='dddd';

    const FEATURE_LISTED_SUCCESSFULLY='dddd';

    const ARTICLE_LISTED_SUCCESSFULLY='dddd';

    const OFFER_SAVED_SUCCESSFULLY='dddd';
    const OFFER_ERROR_NOT_FOUND='dddd';
    const OFFER_SHOW_SUCCESSFULLY='dddd';
    const OFFER_Deleted_SUCCESSFULLY='dddd';

    /* Register */
    const ERROR_REGISTER_NAME_REQUIRED = 'errors.registerName.required';
    const ERROR_REGISTER_NAME_ALPHA_SPACES = 'errors.registerName.alphaSpaces';
    const ERROR_REGISTER_EMAIL_REQUIRED = 'errors.registerEmail.required';
    const ERROR_REGISTER_EMAIL_INVALID = 'errors.registerEmail.invalid';
    const ERROR_REGISTER_EMAIL_REGISTERED = 'errors.registerEmail.registered';
    const ERROR_REGISTER_PASSWORD_REQUIRED = 'errors.registerPassword.required';
    const ERROR_REGISTER_PASSWORD_MIN6 = 'errors.registerPassword.min6';

    /* Activation/Resend activation/Email change */
    const ERROR_ACTIVATE_EMAIL_REQUIRED = 'errors.activateEmail.required';
    const ERROR_ACTIVATE_EMAIL_INVALID = 'errors.activateEmail.invalid';
    const ERROR_ACTIVATE_CODE_REQUIRED = 'errors.activateCode.required';
    const ERROR_ACTIVATE_EMAIL_NOT_REGISTERED = 'errors.activateEmail.notRegistered';
    const ERROR_ACTIVATE_ACCOUNT_ACTIVATED = 'errors.activateCode.activated';
    const ERROR_ACTIVATE_CODE_SEND_COOLDOWN = 'errors.activateCode.sendCooldown';
    const ERROR_ACTIVATE_CODE_WRONG = 'errors.activateCode.wrong';

    /* Forgot and change password */
    const ERROR_FORGOT_EMAIL_REQUIRED = 'errors.forgotEmail.required';
    const ERROR_FORGOT_EMAIL_INVALID = 'errors.forgotEmail.invalid';
    const ERROR_FORGOT_EMAIL_NOT_REGISTERED = 'errors.forgotEmail.notRegistered';
    const ERROR_FORGOT_CODE_REQUIRED = 'errors.forgotCode.required';
    const ERROR_FORGOT_PASSWORD_REQUIRED = 'errors.forgotPassword.required';
    const ERROR_FORGOT_PASSWORD_MIN6 = 'errors.forgotPassword.min6';
    const ERROR_FORGOT_ACCOUNT_UNACTIVATED = 'errors.forgotAccount.notActivated';
    const ERROR_FORGOT_CODE_SEND_COOLDOWN = 'errors.forgotCode.sendCooldown';
    const ERROR_FORGOT_CODE_INVALID = 'errors.forgotCode.invalid';
    const ERROR_FORGOT_PASSED_1H = 'errors.forgot.passed1H';

    /* Login */
    const ERROR_EMAIL_REQUIRED = 'errors.email.required';
    const ERROR_EMAIL_INVALID = 'errors.email.invalid';
    const ERROR_EMAIL_NOT_REGISTERED = 'errors.email.notRegistered';
    const ERROR_PASSWORD_REQUIRED = 'errors.password.required';
    const ERROR_CREDENTIALS_INVALID = 'errors.credentials.invalid';
    const ERROR_ACCOUNT_UNACTIVATED = 'errors.account.notActivated';
    const ERROR_REMEMBER_TOKEN_REQUIRED = 'errors.rememberToken.required';
    const ERROR_REMEMBER_TOKEN_INVALID = 'errors.rememberToken.invalid';



    /* Update profile */
    const ERROR_UPDATE_NAME_REQUIRED = 'errors.updateName.required';
    const ERROR_UPDATE_NAME_ALPHA_SPACES = 'errors.updateName.alphaSpaces';
    const ERROR_UPDATE_EMAIL_REQUIRED = 'errors.updateEmail.required';
    const ERROR_UPDATE_EMAIL_INVALID = 'errors.updateEmail.invalid';
    const ERROR_UPDATE_OLD_PASSWORD_REQUIRED = 'errors.updateNewPassword.requiredOldPassword';
    const ERROR_UPDATE_NEW_PASSWORD_MIN6 = 'errors.updateNewPassword.min6';
    const ERROR_UPDATE_LANGUAGE_REQUIRED = 'errors.updateLanguage.required';
    const ERROR_UPDATE_LANGUAGE_EXISTS = 'errors.updateLanguage.notExists';
    const ERROR_UPDATE_EMAIL_REGISTERED = 'errors.updateEmail.registered';
    const ERROR_UPDATE_OLD_PASSWORD_WRONG = 'errors.updateOldPassword.wrong';
    const ERROR_UPDATE_PICTURE_REQUIRED = 'errors.updatePicture.required';
    const ERROR_UPDATE_PICTURE_IMAGE = 'errors.updatePicture.image';

    /* Notifications */
    const NOTIFICATION_USER_TASK_EXPIRING = 'user.task.expiring';
    const NOTIFICATION_USER_TASK_EXPIRED = 'user.task.expired';
}
