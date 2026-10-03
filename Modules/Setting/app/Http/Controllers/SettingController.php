<?php

namespace Modules\Setting\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\User\app\Models\User;
use Modules\Setting\app\Http\Services\SettingService;

class SettingController extends Controller
{
    private SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }


    /**
     * Fetch Setting
     *
     * @return JsonResponse
     */

    public function fetchSetting(): JsonResponse
    {
        $user = auth()->user();
        if (empty($user) && auth()->user()->type=='Patient') {
            return response()->json([
                'res' => false,
                'msg' => 'User is not authorized !',
                'data' => ""
            ]);
        }
        $response = $this->settingService->fetchSetting();
        return response()->json($response);
    }

    /**
     * Update Setting
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateSetting(Request $request): JsonResponse
    {
        $user = auth()->user();
        if(empty($user) && auth()->user()->type=='Patient')
        {
            return response()->json([
                'res' => false,
                'msg' => 'User is not authorized !',
                'data' => ""
            ]);
        }
        $response = $this->settingService->updateSetting($request);

        return response()->json($response);
    }

    /**
     * Fetch Setting
     *
     * @return JsonResponse
     */

    public function fetchQuestion(): JsonResponse
    {
        $user = auth()->user();
        if (empty($user) && auth()->user()->type=='Patient') {
            return response()->json([
                'res' => false,
                'msg' => 'User is not authorized !',
                'data' => ""
            ]);
        }
        $response = $this->settingService->fetchQuestion();
        return response()->json($response);
    }

    /**
     * Visit Question
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function visitQuestion(Request $request): JsonResponse
    {
        $user = auth()->user();
        if(empty($user) && auth()->user()->type=='Patient')
        {
            return response()->json([
                'res' => false,
                'msg' => 'User is not authorized !',
                'data' => ""
            ]);
        }
        $response = $this->settingService->visitQuestion($request);

        return response()->json($response);
    }

    /**
     * Update/Add Question
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateQuestion(Request $request): JsonResponse
    {
        $user = auth()->user();
        if(empty($user) && auth()->user()->type=='Patient')
        {
            return response()->json([
                'res' => false,
                'msg' => 'User is not authorized !',
                'data' => ""
            ]);
        }
        $response = $this->settingService->updateQuestion($request);

        return response()->json($response);
    }


    /**
     * Delete Question
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteQuestion(Request $request): JsonResponse
    {
        $user = auth()->user();
        if(empty($user) && auth()->user()->type=='Patient')
        {
            return response()->json([
                'res' => false,
                'msg' => 'User is not authorized !',
                'data' => ""
            ]);
        }
        $response = $this->settingService->deleteQuestion($request);

        return response()->json($response);
    }


    /**
     * Delete Option
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteOption(Request $request): JsonResponse
    {
        $user = auth()->user();
        if(empty($user) && auth()->user()->type=='Patient')
        {
            return response()->json([
                'res' => false,
                'msg' => 'User is not authorized !',
                'data' => ""
            ]);
        }
        $response = $this->settingService->deleteOption($request);

        return response()->json($response);
    }

}
