<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\ProgressStatusRequest;
use App\Http\Controllers\Controller;
use App\Services\CourseService;

class CourseController extends Controller
{
    /**
     * Check the progress status on a course
     */
    public function progressStatus(ProgressStatusRequest $request, CourseService $service)
    {
        $params = $request->input();

        $timeAssignmentDate  = strtotime($params['assignment_date']);
        $timeDueDate = strtotime($params['due_date']);
        $differenceInSeconds = $timeDueDate - $timeAssignmentDate;

        if ($differenceInSeconds <= $params["course_duration"])
        {
            return response()->json([
                "message"    => "The given data was invalid.",
                "errors"     =>  [
                    "due_date" => 'The due date is too close to the assignment date!'
                ]
            ], 422);
        }
        else
        {
            $result = $service->getProgressStatus($params['course_duration'], $params['progress_percent'], $params['assignment_date'], $params['due_date']);

            return $result;
        }
    }
}
