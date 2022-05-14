<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgressStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'course_duration'    =>  ['required', 'integer', 'min:10'],
            'progress_percent'   =>  ['required', 'integer', 'min:0', 'max:100'],
            'assignment_date'    =>  ['required', 'date_format:'.DATE_RFC3339],
            'due_date'           =>  ['required', 'date_format:'.DATE_RFC3339, "after:assignment_date"]
        ];
    }

    public function messages()
    {
        return [
            'assignment_date.date_format'    => "The :attribute does not match the format :format.Ex.: 2020-01-30T00:00:01+00:00",
            'due_date.date_format'           => "The :attribute does not match the format :format.Ex.: 2020-01-30T00:00:01+00:00"
        ];
    }

}
