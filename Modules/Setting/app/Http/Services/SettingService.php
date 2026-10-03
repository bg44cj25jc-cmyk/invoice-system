<?php

namespace Modules\Setting\app\Http\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Modules\User\app\Models\User;
use Carbon\Carbon;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Illuminate\Support\Facades\View;
use Modules\Setting\app\Models\Question;
use Modules\Setting\app\Models\QuestionOption;
use Modules\Home\app\Models\Appointment;
use Modules\Home\app\Models\AppointmentQuestion;
use Illuminate\Support\Facades\Auth;
use DateTime;
use DateInterval;


class SettingService
{

    /**
     * Fetch Setting
     *
     * @return array
     */

    public function fetchSetting(): array
    {
        $user = auth()->user();

        $fetchSetting = User::select('emergency_slot', 'videos', 'future_days')
                ->where('id', $user->id)
                ->first()
                ->toArray();


        return [
            'res' => true,
            'msg' => '',
            'data' => $fetchSetting
        ];
    }

    /**
     * Update Setting Info
     *
     * @param $request
     * @return array
     */
    public function updateSetting($request): array
    {


        $user = auth()->user();
        $user_id = auth()->user()->id;
        $column = $request->column;
        $value = $request->value;

        $updateUser = User::findOrFail($user_id);
        $updateUser->$column = $value;
        $updateUser->save();

        return [
            'res' => true,
            'msg' => 'Successfully Updated!',
            'data' => ''
        ];
    }

    /**
     * Fetch Question
     *
     * @return array
     */


    public function fetchQuestion(): array
    {
        $user = auth()->user();
        $questions = Question::where('user_id', $user->id)->get();
        foreach ($questions as $question) {
            $appointMent = AppointmentQuestion::where('question_id',$question->id)->first();
            if($appointMent)
            {
                $question->delete = 1;
            }
            else
            {
                $question->delete = 0;
            }

            $question_options = QuestionOption::select('question_options.*', DB::raw('IF(appointment_questions.id IS NOT NULL, 1, 0) as deleteOption'))
                ->leftJoin('appointment_questions', function($join) use ($question) {
                    $join->on('question_options.id', '=', 'appointment_questions.options_id')
                        ->where('appointment_questions.question_id', '=', $question->id);
                })
                ->where('question_options.question_id', $question->id)
                ->distinct()
                ->get();


            $question->options = $question_options;
        }

        return [
            'res' => true,
            'msg' => '',
            'data' => $questions
        ];
    }

    /**
     * Update Yes/No
     *
     * @param $request
     * @return array
     */
    public function visitQuestion($request): array
    {

        $updateUser = Question::findOrFail($request->id);
        $updateUser->no = $request->value;
        $updateUser->save();

        return [
            'res' => true,
            'msg' => 'Successfully Updated!',
            'data' => ''
        ];
    }


    /**
     * Delete Question
     *
     * @param $request
     * @return array
     */
    public function deleteQuestion($request): array
    {

        Question::where('id', $request->id)->delete();
        QuestionOption::where('question_id', $request->id)->delete();

        return [
            'res' => true,
            'msg' => 'Successfully deleted!',
            'data' => ''
        ];
    }


    /**
     * Delete Option
     *
     * @param $request
     * @return array
     */
    public function deleteOption($request): array
    {

        QuestionOption::where('id', $request->id)->delete();

        return [
            'res' => true,
            'msg' => 'Successfully deleted!',
            'data' => ''
        ];
    }


    /**
     * Update Question
     *
     * @param $request
     * @return array
     */
    public function updateQuestion($request): array
    {

        $user = auth()->user();
		$request = $request->all();
        $questions = $request['questions'];
        if (!empty($questions)) {
            foreach ($questions as $vars) {
                if (isset($vars['id']) && !empty($vars['id'])) {

                    $updateQuestion = Question::findOrFail($vars['id']);
                    $updateQuestion->question = $vars['question'];
                    $updateQuestion->ask = $vars['ask'];
                    $updateQuestion->shows = $vars['shows'];
                    $updateQuestion->disabled = $vars['disabled'];
                    $updateQuestion->save();


                    if (!empty($vars['options'])) {
                        foreach ($vars['options'] as $option) {
                            if (isset($option['id']) && !empty($option['id'])) {
                                $updateOption = QuestionOption::findOrFail($option['id']);
                                $updateOption->options = $option['options'];
                                $updateOption->priority = $option['priority'];
                                $updateOption->save();
                            } else {
                                $newOption = new QuestionOption();
                                $newOption->options = $option['options'];
                                $newOption->priority = $option['priority'];
                                $newOption->question_id = $vars['id'];
                                $newOption->save();
                            }
                        }
                    }

                } else {

                        $newQuestion = new Question();
                        $newQuestion->user_id = $user->id;
                        $newQuestion->question = $vars['question'];
                        $newQuestion->label = $vars['label'];
                        $newQuestion->shows = $vars['shows'];
                        $newQuestion->ask = $vars['ask'];
                        $newQuestion->options = 1;
                        $newQuestion->disabled = 0;
                        $newQuestion->no = 0;
                        $newQuestion->save();

                    if (!empty($vars['options'])) {
                        foreach ($vars['options'] as $option) {

                                $newOption = new QuestionOption();
                                $newOption->options = $option['options'];
                                $newOption->priority = $option['priority'];
                                $newOption->question_id = $newQuestion->id;
                                $newOption->save();

                        }
                    }

                }

            }
        }

        return [
            'res' => true,
            'msg' => 'Successfully Updated!',
            'data' => ''
        ];
    }


}
