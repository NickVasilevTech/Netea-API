<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class CourseService {

    /**
     * Get information about the status of the progress in a course
     *
     * @param int       $courseDuration     The entirety of the duration of the course's content in seconds
     * @param int       $progressPercent    The current progress in the course in percents. Can be between 0 and 100
     * @param string    $assignmentDate     The date at which the course was assigned. Can be in the future. In the RFC3339 date format
     * @param string    $dueDate            The date at which the course is due. Can be in the past. In the RFC3339 date format
     *
     * @return array    [
     *      "progress_status"               =>  enum[
     *          "on track",         // When the expected progress is less than or equal to the current progress
     *          "not on track",     // When the expected progress is more than the current progress
     *          "overdue"           // When the due date is in the past and the current progress is less than 100%
     *      ],
     *      "expected_progress"             =>  int,    // The expected progress as of the current date
     *      "needed_daily_learning_time"    =>  int     // Adjusted daily content duration that needs to be completed for the course to be completed on time
     * ]
     */
    public function getProgressStatus(int $courseDuration, int $progressPercent, string $assignmentDate, string $dueDate)
    {
        $dtAssignment = Carbon::createFromFormat(DATE_RFC3339, $assignmentDate);
        $dtDue = Carbon::createFromFormat(DATE_RFC3339, $dueDate);
        $dtNow = Carbon::now();

        if ($dtDue->isBefore($dtNow) && $progressPercent < 100)
        {
            //If the due date has passed and the current progress is not 100% the course is overdue, so the ideal case is for the remainder course to be completed in a single day
            $progressStatus = "overdue";
            $expectedProgressPercent = 100;
            $neededDailyProgress = $courseDuration*((100 - $progressPercent)/100);
        }
        else if($dtNow->isBefore($dtAssignment))
        {
            // If the assignment is yet to start in the future the expected progress is 0% and no daily progress is needed right now. The user is on track
            $progressStatus = "on track";
            $expectedProgressPercent = 0;
            $neededDailyProgress = 0;
        }
        else
        {
            $fullDaysBetweenDates = $dtDue->diffInDays($dtAssignment);

            //If the difference in days is 0 or 1, then all of the progress should be done in one day
            if ($fullDaysBetweenDates < 2)
            {
                $initDailyProgress = $courseDuration;
                $expectedProgressPercent = ($dtDue->isBefore($dtNow))? 100 : 0;
            }
            else
            {
                // The last day can have only LESS seconds than the standard day ue to the ceil() method, we don't want the last day to have MORE seconds than expected by the user.
                $initDailyProgress =   ceil($courseDuration/$fullDaysBetweenDates);

                $latestDate = ($dtDue->isBefore($dtNow)) ? $dtDue : $dtNow;
                $expectedProgressSeconds = ($dtAssignment->diffInDays($latestDate))*$initDailyProgress;
                $expectedProgressPercent = round((($expectedProgressSeconds)/$courseDuration)*100);
            }

            // If the course is already completed the user might still want to have the expected progress percent
            if ($progressPercent == 100 || ($expectedProgressPercent <= $progressPercent))
            {
                $progressStatus = "on track";
            }
            else
            {
                $progressStatus = "not on track";
            }

            $neededDailyProgress = ($progressPercent == 100)? 0 : ceil((((100 - $progressPercent)*$courseDuration)/100)/($dtDue->diffInDays($dtNow)));

        }


        return [
            "progress_status"               =>  $progressStatus,
            "expected_progress"             =>  $expectedProgressPercent,
            "needed_daily_learning_time"    =>  $neededDailyProgress
        ];
    }
}
