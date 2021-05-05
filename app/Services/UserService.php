<?php

namespace App\Services;

use App\Constants\TranslationCode ;
use App\Models\Language ;
use App\Models\CommitteeMember ;
use App\Models\CommitteeMemberLog ;
use App\Models\Attendee ;
use App\Models\Role ;
use App\Models\User ;
use App\Models\Committee ;
use App\Models\UserToken ;   
use App\Models\NotificationType ;      
use App\Models\UserPermission ;
use App\Models\RolePermission ;
use App\Models\Membership ;
use App\Models\Position ;
use App\Models\Account;
use Carbon\Carbon ;
use Illuminate\Contracts\Validation\Validator as ReturnedValidator ;
use Illuminate\Database\Eloquent\Builder ;
use Illuminate\Http\Request ;
use Illuminate\Support\Facades\Auth ;
use Illuminate\Support\Facades\File ;
use Illuminate\Support\Facades\Hash ;
use Illuminate\Support\Facades\Validator ;
use Illuminate\Support\Str ;
use IonGhitun\JwtToken\Jwt ;
use App\Repositories\UserRepository ;
use App\Repositories\UserTranslationRepository ;
use Ixudra\Curl\Facades\Curl;
use App\Services\EmailService;
use App\Services\SMSService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Lang;

/**
 * Class UserService
 *
 * @package App\Services
 */
class UserService extends BaseService
{
    /**
     * Validate request on login
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */


    public function validateLoginRequest(Request $request)
    {
        $rules = [
           // 'email' => 'required|email|exists_encrypted:users,email',
            //'email' => 'required|email|exists:users,email',
            'email-mobile' => 'required',
            'fcm' => 'required',
            'slug' => 'required|exists:accounts,slug',
            'password' => 'required'
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on login
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */


    public function validateOTPLoginRequest(Request $request)
    {
        $rules = [
           // 'email' => 'required|email|exists_encrypted:users,email',
            //'email' => 'required|email|exists:users,email',
            'otp' => 'required',
            'fcm' => 'required',
            'slug' => 'required|exists:accounts,slug',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    
    /**
     * Validate request on login with remember token
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateTokenLoginRequest(Request $request)
    {
        $rules = [
            'rememberToken' => 'required'
        ];

        $messages = [
            'rememberToken.required' => __('Remember Token is required')
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate Account request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateAccountCreateRequest(Request $request)
    {
        $rules = [
            'user.name' => 'required',
            'user.title' => 'required',
            'user.device_token' => 'required',
            //'user.email' => 'required|email|unique:users,email,',
            //'user.mobile' => 'required|unique:users,mobile',
            'user.password' => 'required',
            'user.password_confirmation' => 'required_with:user.password|same:user.password'
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate Account request on Create
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateEditPasswordRequest(Request $request)
    {
        $rules = [
            'current_password' => 'required',
            'password' => 'required',
            'password_confirmation' => 'required_with:password|same:password'
        ];

        $messages = [
         /*   'name.required' => TranslationCode::OFFER_ERROR_NAME_REQUIRED,
            'organization.required' => TranslationCode::OFFER_ERROR_ORGANIZATION_REQUIRED,
            'email.required' => TranslationCode::OFFER_ERROR_EMAIL_REQUIRED,
            'email.email' => TranslationCode::OFFER_ERROR_EMAIL_INVALID,
            'mobile.required' => TranslationCode::OFFER_ERROR_MOBILE_REQUIRED*/
        ];

        return Validator::make($request->all(), $rules, $messages);
    }



    /**
     * Get user from email and password
     *
     * @param array $credentials
     *
     * @return User|null
     */
    public function loginWithOTPUser($otp)
    {
        $builder = self::getUserBuilderForLogin();

        /** @var User|null $user */
        //$user = $builder->whereEncrypted('email', $credentials['email'])
        $user = $builder->where('otp', $otp)->first();

        if (!$user) {
            return null;
        }

        return $user;
    }
    /**
     * Get user from email and password
     *
     * @param array $credentials
     *
     * @return User|null
     */
    public function loginUser(array $credentials)
    {
        $builder = self::getUserBuilderForLogin();

        
        if($credentials['email-mobile']=='admin@admin.com'){
            $user = User::where('email', $credentials['email-mobile'])->with('translations','translation','role','nationality')->first();
        }else{
            $account = Account::where('slug', $credentials['slug'])->first();
            $user = $builder->where('account_id', $account->id)
            ->where('email', $credentials['email-mobile'])
            ->with('translations','translation','role','nationality','account')->first();

            if(!$user){
                $user = User::where('account_id', $account->id)
                ->where('mobile', $credentials['email-mobile'])
                ->with('translations','translation','role','nationality','account')->first();

            }
            
        }

        if (!$user) {
            return null;
        }

        $password = $user->password;

        if (app('hash')->check($credentials['password'], $password)) {

        return $user;
        }

        return null;
    }

    /**
     * Get user builder for login
     *
     * @return Builder
     */
    public static function getUserBuilderForLogin()
    {
        /** @var  Builder $userBuilder */
        $userBuilder = User::with(['role' => function ($query) {
            $query->select(['id', 'name'])
                ->with(['permissions']);
        }]);

        return $userBuilder;
    }

    /**
     * Generate returned data on login
     *
     * @param User $user
     * @param bool $remember
     *
     * @return array
     */
    public function generateLoginData(User $user, $remember = false)
    {
        $data = [
            'tenents' => null,
            'user' => $user,
            'token' => Jwt::generateToken([
                'id' => $user->id,
                'expiration' => Carbon::now()->addYear()->format('Y-m-d H:i:s')
            ])
        ];

        $data['user']['permissions'] = $this->getUserPermissions($user);

        if ($remember) {
            $data['rememberToken'] = $this->generateRememberMeToken($user->id);
        }

        return $data;
    }

    /**
     * Get user permissions
     *
     * @param User $user
     * @param bool $remember
     *
     * @return array
     */
    public function getUserPermissions(User $user)
    {

        $data = Role::with('permissions')->find($user->role_id);

        $indexOfMeetingPermission = null;
        $indexOfBoardPermission = null;
        $indexOfCommitteePermission = null;
        $indexOfUserPermission = null;
        $indexOfAgendaPermission = null;

        if(!isset($data['permissions']))
            return [];
        foreach($data['permissions'] as $key=>$value){
            switch($value['code']){
                case 'MEETING':
                    $indexOfMeetingPermission = $key;
                break;
            }
        }

        $data['permissions'][$indexOfMeetingPermission]['access']['create'] = $this->canCreateMeeting($user);

        return $data['permissions'];
    }


    public function canCreateMeeting(User $user){

        $isCommitteManager = $this->isCommitteeManager($user);
        
        //todo add more conditions for other case who can add meeting for this board
        if($isCommitteManager){
            return true;
        }

        return false;
    }

    public function isCommitteeManager(User $user){
        $committees = Committee::whereRaw(' (amanuensis_id = ? or secretary_id = ? ) and is_completed = 1',[$user->id,$user->id,$user->id])
        ->orWhereHas('governances',function($query) use($user){
            $query->whereHas('user', function ($query) use ($user){
                return $query->where('id', '=', $user->id);
            });
        })->first();

        if($committees)
            return true;
        return false;
    }

    /**
     * Generate remember me token
     *
     * @param $userId
     * @param $days
     *
     * @return string
     */
    public function generateRememberMeToken($userId, $days = 14)
    {
        $userToken = new UserToken();

        $userToken->user_id = $userId;
        $userToken->token = Str::random(64);
        $userToken->type = UserToken::TYPE_REMEMBER_ME;
        $userToken->expire_on = Carbon::now()->addDays($days)->format('Y/m/d H:i');

        $userToken->save();

        return $userToken->token;
    }

    /**
     * Login user with remembered token
     *
     * @param $token
     *
     * @return User|null
     */
    public function loginUserWithRememberToken($token)
    {
        $builder = self::getUserBuilderForLogin();

        /** @var User|null $user */
        $user = $builder->whereHas('userTokens', function ($query) use ($token) {
            $query->where('token', $token)
                ->where('expire_on', '>=', Carbon::now()->format('Y/m/d H:i'));
        })->first();

        return $user;
    }

    /**
     * Update remember token validity when used on login
     *
     * @param $token
     * @param int $days
     */
    public function updateRememberTokenValability($token, $days = 14)
    {
        /** @var UserToken $userToken */
        $userToken = UserToken::where('token', $token)
            ->where('type', UserToken::TYPE_REMEMBER_ME)
            ->first();

        if ($userToken) {
            $userToken->expire_on = Carbon::now()->addDays($days)->format('Y/m/d H:i');

            $userToken->save();
        }
    }
    
    /**
     * Validate request on register
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateRegisterRequest(Request $request)
    {
        $rules = [
            'email' => 'required|email',
//            'mobile'=> 'required|numeric|unique:users,mobile',
            'password' => 'required',
            'password_confirmation' => 'required_with:password|same:password',
            'title_ar' => 'nullable',
            'name_ar' => 'required',
            'title_en' => 'nullable',
//            'name_en' => 'required',
            'nationality_id'=>'required|numeric|exists:nationalities,id,status,1',
            'type_id'=>'numeric|exists:accounts_types,id,status,1',
            'role_id'=>'required|numeric|exists:roles,id,status,1',
            'account_en' => 'required',
            'account_ar' => 'required',
            'slug' => 'required|unique:accounts,slug|min:3',
            'activate_link' => 'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }
    /**
     * Validate request on register
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateCreateRequest(Request $request)
    {
        $rules = [
            'email' => 'required|email',
//            'mobile'=> 'required|numeric|unique:users,mobile',
            'mobile'=> [
                'required','numeric',Rule::unique('users', 'mobile')->where('account_id', $request->account_id)
            ],
            'title_ar' => 'nullable',
            'name_ar' => 'required',
            'title_en' => 'nullable',
            'name_en' => 'required',
            'role_id'=>'required|numeric|exists:roles,id,status,1',
            'nationality_id'=>'required|numeric|exists:nationalities,id,status,1',
            'activate_link'=> 'required',
            'committee_id'=>'numeric|exists:committees,id,status,1',
            'membership_id'=>'numeric|exists:memberships,id,status,1',
            'meeting_id'=>'numeric|exists:meetings,id,status,1',
            'position_id'=>'numeric|exists:positions,id,status,1',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }
    /**
     * Validate request on update user
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateUpdateUserRequest(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'mobile'=> 'required|numeric',
            'device_token' => 'required',
            'title_ar' => 'nullable',
            'name_ar' => 'required',
            'title_en' => 'nullable',
            'name_en' => 'required',
            'role_id' => 'required|numeric|exists:roles,id',
            'nationality_id'=>'required|numeric|exists:nationalities,id,status,1',
        ];

        $messages = [
            /*
            'email.required' => TranslationCode::ERROR_UPDATE_EMAIL_REQUIRED,
            'email.email' => TranslationCode::ERROR_UPDATE_EMAIL_INVALID,
            'oldPassword.required_with' => TranslationCode::ERROR_UPDATE_OLD_PASSWORD_REQUIRED,
            'newPassword.min' => TranslationCode::ERROR_UPDATE_NEW_PASSWORD_MIN6,
            'language.required' => TranslationCode::ERROR_UPDATE_LANGUAGE_REQUIRED,
            'language.exists' => TranslationCode::ERROR_UPDATE_LANGUAGE_EXISTS,
            */
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateUpdateCommitteeMemberRequest(Request $request)
    {
        $rules = [
            'shares' => 'required',
        ];

        $messages = [];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateCreateContributorRequest(Request $request)
    {
        $rules = [
            'email' => 'email',
            'mobile'=> [
                'required','numeric',Rule::unique('users', 'mobile')->where('account_id', $request->account_id)
            ],
            'title_ar' => 'nullable',
            'name_ar' => 'required',
            'title_en' => 'nullable',
            'name_en' => 'required',
            'committee_id'=>'numeric|exists:committees,id,status,1',
            'meeting_id'=>'numeric|exists:meetings,id,status,1',
            'shares'=>'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Update logged user
     *
     * @param User $user
     * @param Request $request
     * @param Language $language
     */
    public function updateLoggedUser(User &$user, Request $request, Language $language)
    {
        $email = $request->get('email');
        $confirmEmail = false;

        if ($user->email !== $email) {
            $user->email = $email;
            $user->status = User::STATUS_EMAIL_UNCONFIRMED;
            $user->activation_email_code = strtolower(Str::random(6));

            $confirmEmail = true;
        }

        if ($request->has('newPassword')) {
            $user->password = Hash::make($request->get('newPassword'));
        }

        $user->name = $request->get('name');

        $user->language_id = $language->id;

        if ($confirmEmail) {
            $emailService = new EmailService();
            $activate_link="";
            $emailService->sendEmailConfirmationCode($user,$activate_link, $language->code);
        }

        $user->save();
    }

    /**
     * Validate request on update user picture
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateUpdateUserPictureRequest(Request $request)
    {
        $rules = [
            'picture' => 'required|image',
        ];

        $messages = [
            'picture.required' => __('Picture field is required'),
            'picture.image' => __('Picture must be an image')
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Change logged user picture
     *
     * @param $user
     * @param $picture
     */
    public function updateLoggedUserPicture(&$user, $picture)
    {
        /** @var User $user */
        $user = Auth::user();

        $pictureExtension = $picture->getClientOriginalExtension();
        $generatedPictureName = str_replace(' ', '_', $user->name) . '_' . time() . '.' . $pictureExtension;

        $path = 'uploads/users/';
        File::makeDirectory($path, 0777, true, true);

        $baseService = new BaseService();

        $pictureData = $baseService->processImage($path, $picture, $generatedPictureName, true);

        if ($pictureData) {
            if ($user->picture) {
                foreach ($user->picture as $oldPicture) {
                    if ($oldPicture && file_exists($oldPicture)) {
                        unlink($oldPicture);
                    }
                }
            }

            $user->picture = $pictureData;
        }

        $user->save();
    }

    /**
     * Validate request on forgot password
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateForgotPasswordRequest(Request $request)
    {
        $rules = [
            //'email' => 'required|email|exists_encrypted:users,email'
            'email' => 'required|email|exists:users,email',
            // 'slug' => 'required|exists:accounts,slug',
            'activate_link'=> 'required',
        ];

        $messages = [
            'email.required' => __('Email is required'),
            'email.email' => __('Invalid Email'),
            'email.exists' => __('Email is not registered'),
            // 'slug.required' => 'Slug Required',
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function checkEmailExistsInAccount($account_id, $email){
        $user = User::where('email', $email)->where('account_id', $account_id)->first();
            if(isset($user->id) && $user->id){
                return 1;
            }else{
                return 0;
            }
    }

    /**
     * Send code on email for forgot password
     *
     * @param User $user
     * @param Language $language
     */
    public function sendForgotPasswordCode(User $user, $activate_link,  $language_code)
    {
        
        $forgotCode = Str::random(6);
        $forgotTime = Carbon::now()->format('Y/m/d H:i');

        $usersWithSameMail = User::where('email', $user->email)->orderBy('id', 'desc')->get();

        foreach($usersWithSameMail as $k=>$user) {
            $language_code = ($user->language_id == Language::ID_EN) ? Language::CODE_EN : Language::CODE_AR;

            $user->forgot_code = $forgotCode;
            $user->forgot_time = $forgotTime;

            if($k == 0) {
                $this->forgetPasswordNotification($user, $activate_link, $language_code);
            }

           
            $user->save();
        }
    }

    /**
     * Send code on email for forgot password
     *
     * @param User $user
     * @param Language $language
     */
    public function sendInvitationCode(User $user, $activate_link , $language_code)
    {
        $user->forgot_code = Str::random(6);
        $user->forgot_time = Carbon::now()->format('Y/m/d H:i');

        /** @var EmailService $emailService */
        $emailService = new EmailService();


        $emailService->sendInvitationCode($user, $activate_link, $language_code);

        $user->save();
    }

     /**
     * Send code on email for forgot password
     *
     * @param User $user
     * @param Language $language
     */
    public function sendActivationByEmailCode(User $user, $activate_link,  $language_code)
    {
        $user->activation_email_code = strtolower(Str::random(6));

        /** @var EmailService $emailService */
        $emailService = new EmailService();


        $emailService->sendActivationByEmailCode($user,$activate_link, $language_code);

        $user->save();
    }

         /**
     * Send code on email for forgot password
     *
     * @param User $user
     * @param Language $language
     */
    public function sendActivationByMobileCode(User $user,  $language_code)
    {
        $user->activation_mobile_code = $this->generateDigits(4);

        /** @var EmailService $emailService */
        $smsService = new SMSService();

        $text = "your Activation Code is ".$user->activation_mobile_code;
        $smsService->sendSMS($user->mobile, $text);

        $user->save();
    }

    /**
     * Validate request on forgot change password
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateChangePasswordRequest(Request $request)
    {
        $rules = [
            'email' => 'required|email|exists:users,email',
            'code' => 'required',
            'password' => 'required',
            'password_confirmation' => 'required_with:password|same:password'
        ];

        $messages = [
          /*  'email.required' => TranslationCode::ERROR_FORGOT_EMAIL_REQUIRED,
            'email.email' => TranslationCode::ERROR_FORGOT_EMAIL_INVALID,
            'email.exists_encrypted' => TranslationCode::ERROR_FORGOT_EMAIL_NOT_REGISTERED,
            'code.required' => TranslationCode::ERROR_FORGOT_CODE_REQUIRED,
            'password.required' => TranslationCode::ERROR_FORGOT_PASSWORD_REQUIRED,
            'password.min' => TranslationCode::ERROR_FORGOT_PASSWORD_MIN6*/
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate request on forgot change password
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateUpdateRequest(Request $request, User $user)
    {
        $rules = [
            'email' => 'required|email',
            'mobile'=> 'required',
            'title_ar' => 'nullable',
            'name_ar' => 'required',
            'title_en' => 'nullable',
            'name_en' => 'required',
            // 'role_id' => 'required|numeric|exists:roles,id,status,1',
            'nationality_id'=>'required|numeric|exists:nationalities,id,status,1',
        ];

        $messages = [
          /*  'email.required' => TranslationCode::ERROR_FORGOT_EMAIL_REQUIRED,
            'email.email' => TranslationCode::ERROR_FORGOT_EMAIL_INVALID,
            'email.exists_encrypted' => TranslationCode::ERROR_FORGOT_EMAIL_NOT_REGISTERED,
            'code.required' => TranslationCode::ERROR_FORGOT_CODE_REQUIRED,
            'password.required' => TranslationCode::ERROR_FORGOT_PASSWORD_REQUIRED,
            'password.min' => TranslationCode::ERROR_FORGOT_PASSWORD_MIN6*/
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    public function validateUpdateContributorRequest(Request $request)
    {
        $rules = [
            'email' => 'email',
            'mobile'=> [
                'required','numeric',Rule::unique('users', 'mobile')->where('account_id', $request->account_id)
            ],
            'title_ar' => 'nullable',
            'name_ar' => 'required',
            'title_en' => 'nullable',
            'name_en' => 'required',
            'shares' => 'required',
        ];

        $messages = [];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Update user password after reset
     *
     * @param User $user
     * @param $password
     */
    public function updatePassword(User $user, $password)
    {
        $usersWithSameMail = User::where('email', $user->email)
        ->where('forgot_code', $user->forgot_code)
        ->get();
        $hashedPassword = Hash::make($password);
        foreach($usersWithSameMail as $one) {
            $one->forgot_code = null;
            $one->forgot_time = null;
            $one->password = $hashedPassword;

            $one->save();
        }
    }

    /**
     * Register user
     *
     * @param Request $request
     * @param Language $language
     */
    public function registerUser(Request $request, Language $language)
    {
        $user = new User();

        $user->name = $request->get('name');
        $user->email = $request->get('email');
        $user->password = $request->get('password');
        $user->status = User::STATUS_UNCONFIRMED;
        $user->language_id = $language->id;
        $user->role_id = Role::IDS['ID_USER'];
        $user->activation_email_code = strtolower(Str::random(6));
        $user->activation_mobile_code =  $this->generateDigits(4);

        /** @var EmailService $emailService */
        $emailService = new EmailService();

        $emailService->sendActivationCode($user, $language->code);

        $user->save();
    }

    /**
     * Validate activate account
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateActivateAccountOrChangeEmailRequest(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'code' => 'required',
            'slug' => 'required|exists:accounts,slug',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Validate activate account
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateActivateAccountOrChangeMobileRequest(Request $request)
    {
        $rules = [
            'mobile' => 'required|numeric',
            'code' => 'required',
            'slug' => 'required|exists:accounts,slug',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

     /**
     * Validate activate account
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateCreateSecretaryRequest(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'name_ar' => 'required',
            'name_en' => 'required',
            'mobile' => 'required|numeric',
        ];

        $messages = [
           /* 'email.required' => TranslationCode::ERROR_ACTIVATE_EMAIL_REQUIRED,
            'email.email' => TranslationCode::ERROR_ACTIVATE_EMAIL_INVALID,
            'code.required' => TranslationCode::ERROR_ACTIVATE_CODE_REQUIRED*/
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    /**
     * Activate user account on register or on change email
     *
     * @param $email
     * @param $code
     *
     * @return bool
     */
    public function activateUserAccountByEmail($email, $code, $slug,$password = false)
    {
        /** @var User|null $user */
       // $user = User::whereEncrypted('email', $email)

       $account = Account::where('slug', $slug)->first();

       $user = User::where('email', $email)
            ->where('account_id', $account->id)
            ->where('activation_email_code', strtolower($code))
            ->first();

        if (!$user) {
            return false;
        }

        $user->status = User::STATUS_CONFIRMED;
        $user->activation_email_code = null;

        if($password)
            $user->password = Hash::make($password);

        $user->save();

        $admins = User::where('is_admin', 1)
            ->where('account_id', $account->id)->get();

        $notificationService = new NotificationService();
        $link = url('/activate-email-account/') ;
        foreach($admins as $admin){
            $notificationService->sendNotification(
                $admin->id,
                $admin->account_id ,
                ($user->translation) ? $user->translation->name : '' ,
                $link ,
                NotificationType::ACTIVATE_USER,
                array()
            );
        }

        return true;
    }

    /**
     * Activate user account on register or on change email
     *
     * @param $email
     * @param $code
     *
     * @return bool
     */
    public function activateUserAccountByMobile($mobile, $code, $slug)
    {
        /** @var User|null $user */
       // $user = User::whereEncrypted('email', $email)

       $account = Account::where('slug', $slug)->first();

       $user = User::where('mobile', $mobile)
            ->where('account_id', $account->id)
            ->where('activation_mobile_code', $code)
            ->first();

        if (!$user) {
            return false;
        }

        $user->status = User::STATUS_CONFIRMED;
        $user->activation_mobile_code = null;

        $user->save();

        return true;
    }  

    /**
     * Validate request on resend
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateResendActivationCodeRequest(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'slug' => 'required|exists:accounts,slug',
            'activate_link' => 'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

        /**
     * Validate request on resend
     *
     * @param Request $request
     *
     * @return ReturnedValidator
     */
    public function validateResendActivationCodeByMobileRequest(Request $request)
    {
        $rules = [
            'mobile' => 'required',
            'slug' => 'required|exists:accounts,slug',
            'activate_link' => 'required',
        ];

        $messages = [
        ];

        return Validator::make($request->all(), $rules, $messages);
    }

    
    
    public function resendRegisterMobile(Request $request)
    {
        /** @var User|null $user */
        //$user = User::whereEncrypted('email', $request->get('email'))->first();
        $account = Account::where('slug', $request->get('slug'))->first();

        $user = User::where('mobile', $request->get('mobile'))->where('account_id', $account->id)->first();

        if (!$user) {
            return ['mobile' => 'Mobile Error'];
        }

        if ($user->status === User::STATUS_CONFIRMED) {
            return ['account' => __('Account has been activated')];
        }

        //if ($user->updated_at->addMinute() > Carbon::now()) {
            if (false) {
            return ['code' => TranslationCode::ERROR_ACTIVATE_CODE_SEND_COOLDOWN];
        }

        /** @var EmailService $emailService */
        $emailService = new EmailService();
        
        $emailService->sendActivationByMobileCode($user, $user->language_id);

        $user->updated_at = Carbon::now()->format('Y/m/d H:i');
        $user->save();

        return false;
    }

    /**
     * Resend registration mail
     *
     * @param Request $request
     *
     * @return array|bool
     */
    public function resendRegisterMail(Request $request)
    {
        /** @var User|null $user */
        //$user = User::whereEncrypted('email', $request->get('email'))->first();
        $account = Account::where('slug', $request->get('slug'))->first();

        $user = User::where('email', $request->get('email'))->where('account_id', $account->id)->first();

        if (!$user) {
            return ['email' => __('Email is not registered')];
        }

        if ($user->status === User::STATUS_CONFIRMED) {
            return ['account' => __('Account has been activated')];
        }

        //if ($user->updated_at->addMinute() > Carbon::now()) {
            if (false) {
            return ['code' => TranslationCode::ERROR_ACTIVATE_CODE_SEND_COOLDOWN];
        }

        /** @var EmailService $emailService */
        $emailService = new EmailService();

        $activate_link = $request->get('activate_link');

        $emailService->sendActivationByEmailCode($user, $activate_link, $user->language_id);

        $user->updated_at = Carbon::now()->format('Y/m/d H:i');
        $user->save();

        return false;
    }

    public function saveUser(Request $request, UserRepository $userRepo, 
    UserTranslationRepository $userTranslationRepo){

        $this->userRepository = $userRepo;

        $this->userTranslationRepository = $userTranslationRepo;

        $request->merge(['language_id' => $this->getLangIdFromLocale()]);

        $request->merge(['activation_email_code' => strtolower(Str::random(6))]);

        $request->merge(['activation_mobile_code' => $this->generateDigits(4)]);
        
        $request->merge(['otp' => Str::random(6)]);

        $user = $this->userRepository->create($request->all());

        $request->merge(['language_id' => Language::ID_AR]);

        $input=$request->all();

        $input['user_id'] = $user->id;

        $input['name'] = $input['name_'.Language::CODE_AR];

        if(isset($input['title_'.Language::CODE_AR])){
            $input['title'] = $input['title_'.Language::CODE_AR];
        }
        

        // @todo check this ?
        $userTranslation = $this->userTranslationRepository->create($input);

        $input['language_id'] = Language::ID_EN;

        $input['name'] = $input['name_'.Language::CODE_EN];

        if(isset($input['title_'.Language::CODE_EN])){
            $input['title'] = $input['title_'.Language::CODE_EN];
        }

        $userTranslation = $this->userTranslationRepository->create($input);

        $user = $this->userRepository->with('translation')->find($user->id);

       $this->saveORUpdateNotification($user->id,$user->email,$user->mobile,$user->fcm);

        return $user;

    }

    public function deletePermissions($user_id){
        UserPermission::where('user_id', $user_id)->delete();
        return true;
    }

    public function secretaryPermissions($user_id){
       return  UserPermission::where('user_id', $user_id)->get();
    }

    public function createPermissions($secretary, $manager, $permissions){
        $this->secretaryPermissions($secretary->id);
        foreach($permissions as $permission){
           $managerPermission = RolePermission::where('role_id', $manager->role_id)->where('permission_id', $permission['permission_id'])->first();
           
           if(!$managerPermission->read && $permission['read']){
            $permission['read']=0;
           }

           if(!$managerPermission->read_mine && $permission['read_mine']){
            $permission['read_mine']=0;
           }

           if(!$managerPermission->create && $permission['create']){
            $permission['create']=0;
           }

           if(!$managerPermission->update && $permission['update']){
            $permission['update']=0;
           }

           if(!$managerPermission->delete && $permission['delete']){
            $permission['delete']=0;
           }

           if(!$managerPermission->list && $permission['list']){
            $permission['list']=0;
           }

           if(!$managerPermission->list_mine && $permission['list_mine']){
            $permission['list_mine']=0;
           }


           if(!$managerPermission->update_mine && $permission['update_mine']){
            $permission['update_mine']=0;
           }

           if(!$managerPermission->configuration && $permission['configuration']){
            $permission['configuration']=0;
           }

           if(!$managerPermission->setting && $permission['setting']){
            $permission['setting']=0;
           }


           if(!$managerPermission->log && $permission['log']){
            $permission['log']=0;
           }


           if(!$managerPermission->permission && $permission['permission']){
            $permission['permission']=0;
           }

           UserPermission::create($permission);
        }
    }

    public function NotifyManagerWithSecrtary($manager, $secretary){

        $notificationService = new NotificationService();
        
        $link = url('/secretaries/'.$secretary->id) ;
        
        $notificationService->sendNotification(
            $manager->id, 
            $manager->account_id , 
            'new Secretary' , 
            $link ,
            NotificationType::New_Secrtary_For_Manager_UPDATES,
            array()
        );
    }

    public function finish($user){

        $finish_status['status']= User::STATUS_FINISH;  
        User::where('id', $user->id)->update( $finish_status);

        $committee = CommitteeMember::where('member_id', $user->id)->first();

        CommitteeMember::where('committee_id', $committee->id)->where('member_id', $user->id)->update(array('status'=>CommitteeMember::STATUS_FINISH));

        CommitteeMemberLog::create(array('status'=>CommitteeMember::STATUS_FINISH, 'committee_id'=>$committee->id,'member_id'=>$user->id));


        Attendee::where('committee_id', $committee->id)->where('member_id', $user->id)->update(array('status'=>CommitteeMember::STATUS_FINISH));

        $link = url('/committees/'.$committee->id) ;

        $committeeMembers = CommitteeMember::where('committee_id', $committee->id)->where('status', CommitteeMember::STATUS_PUBLISHED)->get();

        $notificationService = new NotificationService();
        foreach($committeeMembers as $key=>$member){

            $notificationService->sendNotification(
                $user->id,
                $user->account_id , 
                'Finish Membership' , 
                $link ,
                NotificationType::FINISH_MEMBERSHIP_NOTIFICATION,
                array()
            );
        }
    }

    public function convertToJson($objects){

        foreach($objects as $key => $value) { 
            $newkey = sprintf('%s',$key);
            $newArray[$newkey] = $value; 
        } 

        return $newArray;
       // return json_encode($newArray);
    }

    public function saveORUpdateNotification($user_id, $email, $mobile, $fcm){
        $data=array(
            "ref_id"=>"$user_id",
            "email"=>$email,
            "mobile"=>$mobile,
            "fcm"=>$fcm
        );

        $data = $this->convertToJson($data);

        
        
        $url = env('NOTIFICATION_API_BASE_URL').'/notifications/update-user';
        
        Curl::to( $url)
            ->withData($data)
            ->asJson(true)
            ->post();

    }

   public function saveCommitteeMember($member_id, $committee_id, $membership_id, $position_id){
        CommitteeMember::create(array('member_id'=>$member_id, 'committee_id'=>$committee_id, 'membership_id'=>$membership_id, 'position_id'=>$position_id));
   }

    public function saveContributor($member_id, $committee_id, $shares){
        CommitteeMember::create(array('member_id'=>$member_id, 'committee_id'=>$committee_id, 'shares' => $shares, 'membership_id'=>Membership::INDEPENDENT_ID, 'position_id'=>Position::MEMBER));
    }

   public function saveMeetingAttendee($member_id, $meeting_id, $position_id){
        Attendee::create(array('member_id'=>$member_id, 'meeting_id'=>$meeting_id, 'position_id'=>$position_id));
   }

   public function saveMeetingAttendeeDelegate($member_id, $meeting_id, $delegated_to_id){
       Attendee::where('meeting_id', $meeting_id)->where('member_id', $member_id)->update(array('delegated_to_id'=>$delegated_to_id));
   }

    public function getUsersByEmail(string $email)
    {
        return User::where('email', $email)->get();
    }

    public function getUsersByMobile(string $mobile)
    {
        return User::where('mobile', $mobile)->orderBy('id','desc')->get();
    }

    public function getTenentsByIds(array $ids)
    {
        return Account::whereIn('id', $ids)->get();
    }

    function isEmail(string $email): bool
    {
        $find1 = strpos($email, '@');
        $find2 = strpos($email, '.');
        return ($find1 !== false && $find2 !== false);
    }

    public function getUsersByCriteria(array $criteria)
    {
       
        $password = null;
        $accountId = null;
        if(isset($criteria['password'])) {
            $password = $criteria['password'];
            unset($criteria['password']);
        }

        if(isset($criteria['account_id']) && $password) {
            $accountId = $criteria['account_id'];
            unset($criteria['account_id']);
        }
    
        $builder = self::getUserBuilderForLogin();
        
        $users = $builder->where($criteria)
            ->with('translations','translation','role','nationality','account')->get();
        
        if($users->count() > 0) {
            if($password) {
                $check = false;
                foreach($users as $k=>$user) {
                    $userPassword = $user->password;
                    if (app('hash')->check($password, $userPassword)) {
                        // unset($users[$k]);
                        $check = true;
                    }
                }

                if($accountId && $check){
                    $criteria['account_id'] = $accountId;
                    $users = $builder->where($criteria)
                        ->with('translations','translation','role','nationality','account')->get();
                    return $users;
                }

                if($check)
                    return $users;

                return [];
            }

            return $users;
        }

        return null;
    }

    public function registerNotification($user, $activationLink, $language='ar'){
        Lang::setLocale($language);
        $user->activation_email_code = strtolower(Str::random(6));

        $activationLink = $activationLink."?email=".$user->email."&code=".$user->activation_email_code."&slug=".$user->account->slug."&type=activate-user";
        $notificationService = new NotificationService();
//        $link = url('/activate-email-account/') ;
        $notificationService->sendNotification(
            $user->id,
            $user->account_id ,
            ($user->translation) ? $user->translation->name : '' ,
            $activationLink ,
            NotificationType::ACCOUNT_ACTIVATION,
            array(),
            $activationLink,
            __("Activate your account now")
        );

        $user->save();
    }

    public function invitationNotification($user, $activationLink, $language='ar'){
        Lang::setLocale($language);
        $user->activation_email_code = strtolower(Str::random(6));

        $activationLink = $activationLink."?email=".$user->email."&code=".$user->activation_email_code."&slug=".$user->account->slug."&type=activate-user";
        $notificationService = new NotificationService();
        $notificationService->sendNotification(
            $user->id,
            $user->account_id ,
            ($user->translation) ? $user->translation->name : '' ,
            $activationLink ,
            NotificationType::USER_INVITATION,
            array(),
            $activationLink,
            __("Activate your account now")
        );

        $user->save();
    }

    public function forgetPasswordNotification($user, $activationLink, $language='ar'){
        Lang::setLocale($language);

        $activationLink = $activationLink."?email=".$user->email."&code=".$user->forgot_code."&slug=".$user->account->slug."&type=forgot-password";
        $notificationService = new NotificationService();
        $link = url('/activate-email-account/') ;
        $notificationService->sendNotification(
            $user->id,
            $user->account_id ,
            ($user->translation) ? $user->translation->name : '' ,
            $link ,
            NotificationType::FORGET_PASSWORD,
            array(),
            $activationLink,
            __("Forgot Password Reset")
        );
    }

    public function addContributorNotification(User $user, $activationLink, $language='ar'): void
    {
        Lang::setLocale($language);
        $url = explode('/', $activationLink);
        array_pop($url);
         
        $link = implode('/', $url);
        $activationLink = $activationLink."?email=".$user->email."&code=".$user->activation_email_code."&slug=".$user->account->slug."&type=activate-user";
        $notificationService = new NotificationService();
        $notificationService->sendNotification(
            $user->id,
            $user->account_id,
            ($user->translation) ? $user->translation->name : '',
            $activationLink,
            NotificationType::CREATE_CONTRIBUTOR,
            array(),
            $activationLink,
            __("Activate your account now")
        );
    }
}
