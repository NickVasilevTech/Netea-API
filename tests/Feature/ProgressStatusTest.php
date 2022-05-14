<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ProgressStatusTest extends TestCase
{

    use RefreshDatabase;

    private const URL_PATH = '/api/v1/course/progress-status';
    private const COURSE_DURATION_MIN = 10;
    private const COURSE_DURATION_PLACEHOLDER = 30000;
    private const PROGRESS_PERCENT_MIN = 0;
    private const PROGRESS_PERCENT_MAX = 100;
    private const DATE_FORMAT = DATE_RFC3339;
    private const PROGRESS_STATUS_GOOD = "on track";
    private const PROGRESS_STATUS_BAD = "not on track";
    private const PROGRESS_STATUS_WORST = "overdue";

    private function createTestingUser()
    {
        $user = User::create([
            'name' => "Testing User",
            'email' => "testing@email.com",
            'password' => Hash::make("testing_passw0rd"),
        ]);

        return $user;
    }

    private function getExpectedHeaders(User $user)
    {
        $token = $user->createToken('TestToken')->plainTextToken;

        $headers = [];
        $headers['Accept'] = 'application/json';
        $headers['Authorization'] = 'Bearer '.$token;

        return $headers;
    }



    /**
     * @test
     *
     * I send an unauthenticated request to '/api/v1/course/progress-status'
     * I receive a 401 code and message "Unauthenticated"
     *
     */
    public function caseProgressStatusUnauthorized()
    {
        // $this->withExceptionHandling();
        // fwrite(STDERR, print_r($token, TRUE));
        $response = $this->get(self::URL_PATH, []);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' without any parameters
     * I receive a 422 code and JSON response
     * @response [
     *           "message" => "The given data was invalid.",
     *           "errors" => [
     *               "course_duration" => [
     *                   "The course duration field is required."
     *               ],
     *               "progress_percent" => [
     *                   "The progress percent field is required."
     *               ],
     *               "assignment_date" => [
     *                   "The assignment date field is required."
     *               ],
     *               "due_date" => [
     *                   "The due date field is required."
     *               ]
     *           ]
     *   ]
     */
    public function caseProgressStatusMissingParameters()
    {

        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [],
        $headers)
             ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertExactJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "course_duration" => [
                        "The course duration field is required."
                    ],
                    "progress_percent" => [
                        "The progress percent field is required."
                    ],
                    "assignment_date" => [
                        "The assignment date field is required."
                    ],
                    "due_date" => [
                        "The due date field is required."
                    ]
                ]
        ]);
    }

    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with a course duration that is not an integer
     * I receive a 422 code and JSON response
     * @response [
     *           "message" => "The given data was invalid.",
     *           "errors" => [
     *               "course_duration" => [
     *                   "The course duration must be an integer."
     *               ],
     *               "progress_percent" => [
     *                   "The progress percent field is required."
     *               ],
     *               "assignment_date" => [
     *                   "The assignment date field is required."
     *               ],
     *               "due_date" => [
     *                   "The due date field is required."
     *               ]
     *           ]
     *   ]
     */
    public function caseProgressStatusCourseDurationIsNotInt()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "course_duration"   => 15.5,
        ],
        $headers)
             ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertExactJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "course_duration" => [
                        "The course duration must be an integer."
                    ],
                    "progress_percent" => [
                        "The progress percent field is required."
                    ],
                    "assignment_date" => [
                        "The assignment date field is required."
                    ],
                    "due_date" => [
                        "The due date field is required."
                    ]
                ]
        ]);
    }


    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with course duration below the minimum
     * I receive a 422 code and JSON response
     * @response [
     *           "message" => "The given data was invalid.",
     *           "errors" => [
     *               "course_duration" => [
     *                   "The course duration must be at least [min]."
     *               ],
     *               "progress_percent" => [
     *                   "The progress percent field is required."
     *               ],
     *               "assignment_date" => [
     *                   "The assignment date field is required."
     *               ],
     *               "due_date" => [
     *                   "The due date field is required."
     *               ]
     *           ]
     *   ]
     */
    public function caseProgressStatusCourseDurationIsNotAboveMin()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "course_duration"   => self::COURSE_DURATION_MIN - 1,
        ],
        $headers)
             ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertExactJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "course_duration" => [
                        "The course duration must be at least ".self::COURSE_DURATION_MIN."."
                    ],
                    "progress_percent" => [
                        "The progress percent field is required."
                    ],
                    "assignment_date" => [
                        "The assignment date field is required."
                    ],
                    "due_date" => [
                        "The due date field is required."
                    ]
                ]
        ]);
    }



    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with progress percent that is not an integer
     * I receive a 422 code and JSON response
     * @response [
     *           "message" => "The given data was invalid.",
     *           "errors" => [
     *               "course_duration" => [
     *                   "The course duration field is required."
     *               ],
     *               "progress_percent" => [
     *                   "The progress percent must be an integer."
     *               ],
     *               "assignment_date" => [
     *                   "The assignment date field is required."
     *               ],
     *               "due_date" => [
     *                   "The due date field is required."
     *               ]
     *           ]
     *   ]
     */
    public function caseProgressStatusProgressPercentIsNotInt()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "progress_percent"   => 15.5,
        ],
        $headers)
             ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertExactJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "course_duration" => [
                        "The course duration field is required."
                    ],
                    "progress_percent" => [
                        "The progress percent must be an integer."
                    ],
                    "assignment_date" => [
                        "The assignment date field is required."
                    ],
                    "due_date" => [
                        "The due date field is required."
                    ]
                ]
        ]);
    }


    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with progress percent below the minimum
     * I receive a 422 code and JSON response
     * @response [
     *           "message" => "The given data was invalid.",
     *           "errors" => [
     *               "course_duration" => [
     *                   "The course duration field is required."
     *               ],
     *               "progress_percent" => [
     *                   "The progress percent must be at least [min]."
     *               ],
     *               "assignment_date" => [
     *                   "The assignment date field is required."
     *               ],
     *               "due_date" => [
     *                   "The due date field is required."
     *               ]
     *           ]
     *   ]
     */
    public function caseProgressStatusProgressPercentIsNotAboveMin()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "progress_percent"   => self::PROGRESS_PERCENT_MIN - 1,
        ],
        $headers)
             ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertExactJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "course_duration" => [
                        "The course duration field is required."
                    ],
                    "progress_percent" => [
                        "The progress percent must be at least ".self::PROGRESS_PERCENT_MIN."."
                    ],
                    "assignment_date" => [
                        "The assignment date field is required."
                    ],
                    "due_date" => [
                        "The due date field is required."
                    ]
                ]
        ]);
    }



    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with progress percent above the maximum
     * I receive a 422 code and JSON response
     * @response [
     *           "message" => "The given data was invalid.",
     *           "errors" => [
     *               "course_duration" => [
     *                   "The course duration field is required."
     *               ],
     *               "progress_percent" => [
     *                   "The progress percent must not be greater than [max]."
     *               ],
     *               "assignment_date" => [
     *                   "The assignment date field is required."
     *               ],
     *               "due_date" => [
     *                   "The due date field is required."
     *               ]
     *           ]
     *   ]
     */
    public function caseProgressStatusProgressPercentIsAboveMax()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "progress_percent"   => self::PROGRESS_PERCENT_MAX + 1,
        ],
        $headers)
             ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertExactJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "course_duration" => [
                        "The course duration field is required."
                    ],
                    "progress_percent" => [
                        "The progress percent must not be greater than ".self::PROGRESS_PERCENT_MAX."."
                    ],
                    "assignment_date" => [
                        "The assignment date field is required."
                    ],
                    "due_date" => [
                        "The due date field is required."
                    ]
                ]
        ]);
    }



    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with assignment date that is not in the expected format
     * I receive a 422 code and JSON response
     * @response [
     *           "message" => "The given data was invalid.",
     *           "errors" => [
     *               "course_duration" => [
     *                   "The course duration field is required."
     *               ],
     *               "progress_percent" => [
     *                   "The progress percent field is required."
     *               ],
     *               "assignment_date" => [
     *                   "The assignment date does not match the format Y-m-d\\TH:i:sP.Ex.: 2020-01-30T00:00:01+00:00"
     *               ],
     *               "due_date" => [
     *                   "The due date field is required."
     *               ]
     *           ]
     *   ]
     */
    public function caseProgressStatusAssignmentDateIsNotCorrectFormat()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "assignment_date"   => substr(Carbon::now()->format(self::DATE_FORMAT), 0, -1)
        ],
        $headers)
             ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertExactJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "course_duration" => [
                        "The course duration field is required."
                    ],
                    "progress_percent" => [
                        "The progress percent field is required."
                    ],
                    "assignment_date" => [
                        "The assignment date does not match the format Y-m-d\\TH:i:sP.Ex.: 2020-01-30T00:00:01+00:00"
                    ],
                    "due_date" => [
                        "The due date field is required."
                    ]
                ]
        ]);
    }


    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with due date that is not in the expected format
     * I receive a 422 code and JSON response
     * @response [
     *           "message" => "The given data was invalid.",
     *           "errors" => [
     *               "course_duration" => [
     *                   "The course duration field is required."
     *               ],
     *               "progress_percent" => [
     *                   "The progress percent field is required."
     *               ],
     *               "assignment_date" => [
     *                   "The assignment date field is required."
     *               ],
     *               "due_date" => [
     *                    "The due date does not match the format Y-m-d\\TH:i:sP.Ex.: 2020-01-30T00:00:01+00:00"
     *               ]
     *           ]
     *   ]
     */
    public function caseProgressStatusDueDateIsNotCorrectFormat()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "due_date"   => substr(Carbon::now()->format(self::DATE_FORMAT), 0, -1)
        ],
        $headers)
             ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertExactJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "course_duration" => [
                        "The course duration field is required."
                    ],
                    "progress_percent" => [
                        "The progress percent field is required."
                    ],
                    "assignment_date" => [
                        "The assignment date field is required."
                    ],
                    "due_date" => [
                        "The due date does not match the format Y-m-d\\TH:i:sP.Ex.: 2020-01-30T00:00:01+00:00"
                    ]
                ]
        ]);
    }


    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with due date that is before the assignment date
     * I receive a 422 code and JSON response
     * @response [
     *           "message" => "The given data was invalid.",
     *           "errors" => [
     *               "course_duration" => [
     *                   "The course duration field is required."
     *               ],
     *               "progress_percent" => [
     *                   "The progress percent field is required."
     *               ],
     *               "due_date" => [
     *                   "The due date must be a date after assignment date."
     *               ]
     *           ]
     *   ]
     */
    public function caseProgressStatusDueDateIsNotAfterAssignmentDate()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "assignment_date" => Carbon::now()->format(self::DATE_FORMAT),
            "due_date"   => Carbon::now()->subDays(1)->format(self::DATE_FORMAT)
        ],
        $headers)
             ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
             ->assertExactJson([
                "message" => "The given data was invalid.",
                "errors" => [
                    "course_duration" => [
                        "The course duration field is required."
                    ],
                    "progress_percent" => [
                        "The progress percent field is required."
                    ],
                    "due_date" => [
                        "The due date must be a date after assignment date."
                    ]
                ]
        ]);
    }




    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with progress percent of 100
     * I receive a 200 code and JSON response
     * @response [
     *            "progress_status"               =>  "on track",
     *            "expected_progress"             =>  50,
     *            "needed_daily_learning_time"    =>  0
     *   ]
     */
    public function caseProgressStatusProgressIsMax()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "course_duration"   => self::COURSE_DURATION_PLACEHOLDER,
            "progress_percent"  => self::PROGRESS_PERCENT_MAX,
            "assignment_date" => Carbon::now()->subDays(10)->format(self::DATE_FORMAT),
            "due_date"   => Carbon::now()->addDays(10)->format(self::DATE_FORMAT)
        ],
        $headers)
             ->assertStatus(Response::HTTP_OK)
             ->assertExactJson([
                "progress_status"               =>  self::PROGRESS_STATUS_GOOD,
                "expected_progress"             =>  (self::PROGRESS_PERCENT_MAX - self::PROGRESS_PERCENT_MIN)/2,
                "needed_daily_learning_time"    =>  0
        ]);
    }


    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with progress percent below 100 and a passed due date
     * I receive a 200 code and JSON response
     * @response [
     *            "progress_status"               =>  "overdue",
     *            "expected_progress"             =>  100,
     *            "needed_daily_learning_time"    =>  300
     *   ]
     */
    public function caseProgressStatusProgressPercentIsBelowMaxAndDueDatePassed()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "course_duration"   => self::COURSE_DURATION_PLACEHOLDER,
            "progress_percent"  => self::PROGRESS_PERCENT_MAX-1,
            "assignment_date" => Carbon::now()->subDays(20)->format(self::DATE_FORMAT),
            "due_date"   => Carbon::now()->subDays(10)->format(self::DATE_FORMAT)
        ],
        $headers)
             ->assertStatus(Response::HTTP_OK)
             ->assertExactJson([
                "progress_status"               =>  self::PROGRESS_STATUS_WORST,
                "expected_progress"             =>  self::PROGRESS_PERCENT_MAX,
                "needed_daily_learning_time"    =>  (1/100)*self::COURSE_DURATION_PLACEHOLDER
        ]);
    }



    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with an assignment date in the future
     * I receive a 200 code and JSON response
     * @response [
     *            "progress_status"               =>  "on track",
     *            "expected_progress"             =>  0,
     *            "needed_daily_learning_time"    =>  0
     *   ]
     */
    public function caseProgressStatusAssignmentDateIsInTheFuture()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "course_duration"   => self::COURSE_DURATION_PLACEHOLDER,
            "progress_percent"  => self::PROGRESS_PERCENT_MIN,
            "assignment_date" => Carbon::now()->addDays(10)->format(self::DATE_FORMAT),
            "due_date"   => Carbon::now()->addDays(20)->format(self::DATE_FORMAT)
        ],
        $headers)
             ->assertStatus(Response::HTTP_OK)
             ->assertExactJson([
                "progress_status"               =>  self::PROGRESS_STATUS_GOOD,
                "expected_progress"             =>  self::PROGRESS_PERCENT_MIN,
                "needed_daily_learning_time"    =>  0
        ]);
    }


    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with progress percent below expected
     * I receive a 200 code and JSON response
     * @response [
     *            "progress_status"               =>  "not on track",
     *            "expected_progress"             =>  50,
     *            "needed_daily_learning_time"    =>  1530
     *   ]
     */
    public function caseProgressStatusProgressPercentIsBelowExpected()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "course_duration"   => self::COURSE_DURATION_PLACEHOLDER,
            "progress_percent"  => (self::PROGRESS_PERCENT_MAX - self::PROGRESS_PERCENT_MIN)/2 - 1,
            "assignment_date" => Carbon::now()->subDays(10)->format(self::DATE_FORMAT),
            "due_date"   => Carbon::now()->addDays(10)->addSeconds(5)->format(self::DATE_FORMAT)
        ],
        $headers)
             ->assertStatus(Response::HTTP_OK)
             ->assertExactJson([
                "progress_status"               =>  self::PROGRESS_STATUS_BAD,
                "expected_progress"             =>  (self::PROGRESS_PERCENT_MAX - self::PROGRESS_PERCENT_MIN)/2,
                "needed_daily_learning_time"    =>  ((self::COURSE_DURATION_PLACEHOLDER/2) + ((1/100)*self::COURSE_DURATION_PLACEHOLDER))/10
        ]);
    }





    /**
     * @test
     *
     * I send an authenticated request to '/api/v1/course/progress-status' with progress percent above expected
     * I receive a 200 code and JSON response
     * @response [
     *            "progress_status"               =>  "on track",
     *            "expected_progress"             =>  50,
     *            "needed_daily_learning_time"    =>  1470
     *   ]
     */
    public function caseProgressStatusProgressPercentIsAboveExpected()
    {
        $headers = $this->getExpectedHeaders($this->createTestingUser());

        $this->json('GET', self::URL_PATH, [
            "course_duration"   => self::COURSE_DURATION_PLACEHOLDER,
            "progress_percent"  => (self::PROGRESS_PERCENT_MAX - self::PROGRESS_PERCENT_MIN)/2 + 1,
            "assignment_date" => Carbon::now()->subDays(10)->format(self::DATE_FORMAT),
            "due_date"   => Carbon::now()->addDays(10)->addSeconds(5)->format(self::DATE_FORMAT)
        ],
        $headers)
             ->assertStatus(Response::HTTP_OK)
             ->assertExactJson([
                "progress_status"               =>  self::PROGRESS_STATUS_GOOD,
                "expected_progress"             =>  (self::PROGRESS_PERCENT_MAX - self::PROGRESS_PERCENT_MIN)/2,
                "needed_daily_learning_time"    =>  ((self::COURSE_DURATION_PLACEHOLDER/2) - ((1/100)*self::COURSE_DURATION_PLACEHOLDER))/10
        ]);
    }
}
