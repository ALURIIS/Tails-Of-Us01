<?php
/* The list of travel packages. The server uses it to accept only real packages and to decide
   between "book" and "join the waitlist". Keep names/status in sync with travel-packages.html and index.html. */
function booking_packages() {
    return [
        'weekend-stay'      => ['name' => 'Weekend Stay',          'blurb' => 'A short stay with daily care and play.',               'status' => 'available', 'rating' => '4.9', 'reviews' => 28],
        'senior-comfort'    => ['name' => 'Senior Comfort',        'blurb' => 'Extra attention for older pets.',                      'status' => 'booked',    'rating' => '5.0', 'reviews' => 34],
        'medical-support'   => ['name' => 'Medical Support',       'blurb' => 'For pets that need medication or monitoring.',         'status' => 'available', 'rating' => '4.8', 'reviews' => 22],
        'holiday-week'      => ['name' => 'Holiday Week',          'blurb' => 'A longer stay for owners who are away on a trip.',     'status' => 'available', 'rating' => '4.9', 'reviews' => 28],
        'puppy-kitten-start'=> ['name' => 'Puppy and Kitten Start','blurb' => 'Gentle routines and supervision for young animals.',  'status' => 'booked',    'rating' => '5.0', 'reviews' => 34],
        'recovery-stay'     => ['name' => 'Recovery Stay',         'blurb' => 'Quiet rest and follow-up care after treatment.',       'status' => 'available', 'rating' => '4.8', 'reviews' => 22],
        'long-stay-care'    => ['name' => 'Long Stay Care',        'blurb' => 'Two weeks or more with regular updates to owners.',    'status' => 'available', 'rating' => '4.9', 'reviews' => 28],
        'play-exercise'     => ['name' => 'Play and Exercise',     'blurb' => 'Extra daily play and walks for active pets.',          'status' => 'booked',    'rating' => '5.0', 'reviews' => 34],
        'cat-calm-stay'     => ['name' => 'Cat Calm Stay',         'blurb' => 'A quiet room away from dogs, built for nervous cats.', 'status' => 'available', 'rating' => '4.8', 'reviews' => 22],
    ];
}
