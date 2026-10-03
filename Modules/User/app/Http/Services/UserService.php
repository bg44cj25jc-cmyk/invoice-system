<?php

namespace Modules\User\app\Http\Services;

use Illuminate\Http\Request;
use Modules\User\app\Http\Requests\UserRequest;
use Modules\User\app\Http\Requests\UpdatePasswordRequest;
use Modules\User\app\Http\Requests\ChangePasswordRequest;
use Illuminate\Support\Str;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth; 
use Modules\User\app\Models\User;
use Modules\Setting\app\Models\Question;
use Modules\Setting\app\Models\QuestionOption;
use DateTime;
use DateInterval;

class UserService
{
    protected User $user;

    public function __construct()
    {

    }

    /**
     * Registration
     *
     * @param UserRequest $request
     * @return array
     */
    public function signUp(UserRequest $request): array
    {
        $requestData = $request->all();

        $user = $this->createUser($requestData);
        $user['user_id'] = $user->id;
        $userUpdate = User::where('id', $user->id)->first();
        $token = Str::random(64);
        $webUrl = env('APP_URL');
        $emailUrl = env('EMAIL_URL');
        $appName = env('APP_NAME');
        $url = $emailUrl . '/confirm-account/' . $token;
        $mail = new PHPMailer(true);
        $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        $mail->addAddress($userUpdate->email);
        $mail->Subject = 'x-us::Confirm Your Account';
        $htmlContent = View::make('email.confirmAccount', ['url' => $url, 'site_url' => $webUrl, 'site_name' => $appName, 'name' => $userUpdate->name])->render();
        $mail->isHTML(true);
        $mail->Body = $htmlContent;
        $mail->send();

        $userUpdate->token = $token;
        $userUpdate->save();

        return [
            'res' => true,
            'msg' => 'Please verified your account from your email address!',
            'data' => ''
        ];
    }

    /**
     * Create a new User
     *
     * @param array $userData
     * @return User
     */
    public function createUser(array $userData): User
    {
        $userData["password"] = Hash::make($userData['password']);
        $user = new User();
        $user->fill($userData);
        $user->save();

        return $user;
    }

    /**
     * Sign In a user
     *
     * @param array $requestData
     * @return array
     */
    public function login(array $requestData): array
    {
        if (Auth::attempt(["type"=>'Doctor',"email" => $requestData['email'], "password" => $requestData['password']])) {

            $user = Auth::user();

            if($user->status==2){
                return [
                    'res' => false,
                    'msg' => 'Your account is deactivated! Please contact administrator',
                    'data' => ""
                ];
            }

            if($user->status==3){
                return [
                    'res' => false,
                    'msg' => 'Your account is deactivated! Please contact administrator',
                    'data' => ""
                ];
            }


				$currentDate = new DateTime(date('Y-m-d'));
				$compareDate = new DateTime($user->subscription_to_date);
				if ($currentDate > $compareDate) {
					return [
						'res' => false,
						'msg' => 'You subscription has been over. Please contact with administrator.',
						'data' => ""
					];

				}




			if($user->created_by_admin=='1'){
				$user->created_by_admin = '2';
				$user->save();
			}

            $token = $user->createToken('x-usAuth')->accessToken;
            $doctorLanguages = $user->doctorLanguages()->with('language')->get();

            $doctorFaqs = $user->doctorFaqs()
                ->with('faq') // Eager load the related FAQ details
                ->get();

            $count = Question::where('user_id', $user->id)->count();

            if ($count == 0) {
                // Create the first question
                $question1 = new Question();
                $question1->user_id = $user->id;
                $question1->question = 'Did you already visit this doctor?';
                $question1->label = 'Q1';
                $question1->ask = 1;
                $question1->shows = 1;
                $question1->options = 0;
                $question1->no = 0;
                $question1->disabled = 1;
                $question1->save();

                // Save options for the first question
                $options1 = ['Yes', 'No'];
                foreach ($options1 as $option) {
                    $newOption = new QuestionOption();
                    $newOption->options = $option;
                    $newOption->priority = 0;
                    $newOption->question_id = $question1->id;
                    $newOption->save();
                }

                // Create the second question
                $question2 = new Question();
                $question2->user_id = $user->id;
                $question2->question = 'What is the reason of the consultation';
                $question2->label = 'Q2';
                $question2->ask = 1;
                $question2->shows = 1;
                $question2->options = 1;
                $question2->no = 0;
                $question2->disabled = 0;
                $question2->save();

                // Save options for the second question
                $options2 = [
                    'Head problem pain',
                    'Big pain in my head',
                    'Cant breathe'
                ];
                foreach ($options2 as $option) {
                    $newOption = new QuestionOption();
                    $newOption->options = $option;
                    $newOption->priority = 0;
                    $newOption->question_id = $question2->id;
                    $newOption->save();
                }
            }


            $doctorExpertises = $user->doctorExperts;
            $doctorConsultation = $user->doctorConsultations;
            $doctorTransport = $user->doctorTransports;
            $doctorParking = $user->doctorParkings;
            $doctorInformation = $user->doctorInformations;
            $doctorDocuments = $user->doctorDocuments;
            $doctorDegrees = $user->doctorDegrees;
            $doctorTrainings = $user->doctorTrainings;
            $doctorExperiences = $user->doctorExperiences;
			$doctorPublications = $user->doctorPublications;



            $data = array(
                "users" => $user,
                "doctor_languages" => $doctorLanguages,
                "doctor_faqs" => $doctorFaqs,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            );
            return [
                'res' => true,
                'msg' => '',
                'data' => $data
            ];
        } else {
            return [
                'res' => false,
                'msg' => 'Your password is wrong!',
                'data' => ""
            ];
        }
    }

    /**
     * Activate Account
     *
     * @param Request $requestData
     * @return array
     */
    public function activateAccount(Request $requestData): array
    {

        $user = User::where('token', $requestData->token)->first();

        if(empty($user))
        {
            return [
                'res' => false,
                'msg' => 'Your token has been expired!',
                'data' => ""
            ];
        }

        $user->token = '';
        $user->status = 1;
        $user->email_verified_at = now()->format('Y-m-d h:i:s');
        $user->save();

        return [
        'res' => true,
        'msg' => 'Your account has been verified! Please login with email and password',
        'data' => ""
        ];
    }

    /**
     * Forgot Password
     *
     * @param Request $request
     * @return array
     */
    public function forgotPassword(Request $request): array
    {

        $user = User::where('email', $request->email)->first();


        if ($user) {
            if($user->status==0){

                $userUpdate = User::where('id', $user->id)->first();
                $token = Str::random(64);
                $webUrl = env('APP_URL');
                $appName = env('APP_NAME');
                $emailUrl = env('EMAIL_URL');
                $url = $emailUrl . '/confirm-account/' . $token;
                $mail = new PHPMailer(true);
                $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
                $mail->addAddress($userUpdate->email);
                $mail->Subject = 'x-us::Confirm Your Account';
                $htmlContent = View::make('email.confirmAccount', ['url' => $url, 'site_url' => $webUrl, 'site_name' => $appName, 'name' => $userUpdate->name])->render();
                $mail->isHTML(true);
                $mail->Body = $htmlContent;
                $mail->send();

                $userUpdate->token = $token;
                $userUpdate->save();

                return [
                    'res' => true,
                    'msg' => 'Please verified your account first from your email address!',
                    'data' => ""
                ];
            }
            if($user->status==2){
                return [
                    'res' => false,
                    'msg' => 'Your account is deactivated! Please contact administrator',
                    'data' => ""
                ];
            }
            $userUpdate = User::where('id', $user->id)->first();
            $token = Str::random(64);
            $webUrl = env('APP_URL');
            $emailUrl = env('EMAIL_URL');
            $appName = env('APP_NAME');
            $url = $emailUrl . '/recover-password/' . $token;
            $mail = new PHPMailer(true);
            $mail->setFrom(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
            $mail->addAddress($userUpdate->email);
            $mail->Subject = 'x-us::Recover your password';
            $htmlContent = View::make('email.forgotPassword', ['url' => $url, 'site_url' => $webUrl, 'site_name' => $appName, 'name' => $userUpdate->name])->render();
            $mail->isHTML(true);
            $mail->Body = $htmlContent;
            $mail->send();
            $userUpdate->token = $token;
            $userUpdate->save();

            return [
                'res' => true,
                'msg' => 'Please check email to recover your password!',
                'data' => ""
            ];
        } else {
            return [
                'res' => false,
                'msg' => 'Email address not found!',
                'data' => ""
            ];
        }

    }

    /**
     * Update Password
     *
     * @param UpdatePasswordRequest $requestData
     * @return array
     */
    public function updatePassword(UpdatePasswordRequest $requestData): array
    {

        $user = User::where('token', $requestData->token)->first();

        if(empty($user))
        {
            return [
                'res' => false,
                'msg' => 'Your token has been expired!',
                'data' => ""
            ];
        }
        $password = Hash::make($requestData->password);
        $user->token = '';
        $user->password = $password;
        $user->save();

        return [
            'res' => true,
            'msg' => 'Your password is updated!',
            'data' => ""
        ];
    }

    /**
     * Update Password
     *
     * @param ChangePasswordRequest $requestData
     * @return array
     */
    public function changePassword(ChangePasswordRequest $requestData): array
    {

        $user = auth()->user();
        if (!empty($requestData->new_password)) {
            if (Hash::check($requestData->old_password, $user->password)) {
                $user->password = Hash::make($requestData->new_password);
            } else {
                return ['res' => false, 'msg' => 'old password does not match our record.', 'data' => ""];
            }
        }
        $user->save();
        return ['res' => true, 'msg' => "Successfully updated your password", 'data' => ''];
    }

}
