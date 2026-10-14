<?php

return [
    'require_evidence' => env('ATTENDANCE_REQUIRE_EVIDENCE', true),
    'office_latitude' => env('OFFICE_LATITUDE'),
    'office_longitude' => env('OFFICE_LONGITUDE'),
    'office_radius_m' => (float) env('OFFICE_RADIUS_M', 100),
    'office_max_accuracy_m' => (float) env('OFFICE_MAX_ACCURACY_M', 100),
];
