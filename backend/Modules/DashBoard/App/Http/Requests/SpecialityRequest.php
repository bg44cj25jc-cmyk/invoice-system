<?php

namespace Modules\DashBoard\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;

class SpecialityRequest extends FormRequest
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
            'name' => 'required|string|unique:medicines,name,'.$this->id,
            'name_ar' => 'nullable|string|unique:medicines,name,'.$this->id,
            'speciality_image'=> 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:5048',
        ];
    }


}
