<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

return [
    'name' => 'Booking',

    'working_hours' => [
        'monday' => ['start' => '09:00', 'end' => '17:00'],
        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
        'thursday' => ['start' => '09:00', 'end' => '17:00'],
        'friday' => ['start' => '09:00', 'end' => '17:00'],
        'saturday' => null,
        'sunday' => null,
    ],

    'min_notice_hours' => 48,
    'max_reschedules' => 1,
    'max_advance_days' => 60,
    'buffer_minutes' => 15,
    'slot_duration_minutes' => 30,
    'timezone' => 'America/Toronto',

    'sms' => [
        'enabled' => env('BOOKING_SMS_ENABLED', false),
        'provider' => env('BOOKING_SMS_PROVIDER', 'log'), // log|vonage|twilio
        'from' => env('BOOKING_SMS_FROM', ''),
        'vonage' => [
            'api_key' => env('VONAGE_KEY'),
            'api_secret' => env('VONAGE_SECRET'),
        ],
        'twilio' => [
            'sid' => env('TWILIO_SID'),
            'token' => env('TWILIO_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
        'templates' => [
            'confirmation' => 'Confirmé: {service} le {date} à {time}. OUI/NON pour confirmer. STOP=désabonner',
            'reminder' => 'Rappel: {service} demain à {time}. OUI pour confirmer. STOP=désabonner',
            'cancellation' => 'Annulé: {service} du {date}. Nouveau RV: {url}. STOP=désabonner',
        ],
    ],

    'email' => [
        'enabled' => true,
        'send_confirmation' => true,
        'send_reminder' => true,
        'reminder_hours_before' => [24, 1],
    ],

    'stripe' => [
        'enabled' => env('BOOKING_STRIPE_ENABLED', false),
        'secret_key' => env('STRIPE_SECRET'),
        'webhook_secret' => env('BOOKING_STRIPE_WEBHOOK_SECRET'),
        'currency' => env('BOOKING_STRIPE_CURRENCY', 'cad'),
    ],

    'google_calendar' => [
        'enabled' => false,
        'client_id' => env('GOOGLE_CALENDAR_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CALENDAR_CLIENT_SECRET'),
    ],

    'brand' => [
        'business_name' => env('APP_NAME', 'Mon entreprise'),
        'booking_page_title' => 'Prendre rendez-vous',
        'confirmation_message' => 'Votre rendez-vous a été confirmé. Vous recevrez un courriel de confirmation.',
    ],
];
