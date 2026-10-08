<?php

return [
    'otp_length' => 6,
    'login_ttl_minutes' => 10,
    'step_up_ttl_minutes' => 20,
    'step_up_enabled' => env('MFA_STEP_UP_ENABLED', true),
    'expose_otp_for_e2e' => env('MFA_EXPOSE_OTP_FOR_E2E', false),
];
