<?php

namespace Modules\DashBoard\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;

class MedicineRequest2 extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
//            'name' => [
//                'required',
//                'string',
//                Rule::unique('medicines')->where(function ($query) {
//                    return $query->where('brand', $this->brand);
//                })->ignore($this->id),
//            ],
            'name' => 'required',
            'brand' => 'required',
            'dosage' => 'required',
            'packaging' => 'required',
            //'speciality_image'=> 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5048',
        ];
    }


}
