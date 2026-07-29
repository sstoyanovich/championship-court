<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Spotlight Program Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automated Spotlight program generation.
    |
    */

    /*
    | Yearly card limit per player
    | Default: 7 cards per year
    */
    'yearly_card_limit' => env('SPOTLIGHT_YEARLY_CARD_LIMIT', 7),

    /*
    | OVR spacing threshold
    | Minimum difference between cards of same player (default: 3 points)
    */
    'ovr_spacing_threshold' => env('SPOTLIGHT_OVR_SPACING_THRESHOLD', 3),

    /*
    | Exceptional performance thresholds
    */
    'exceptional_performance' => [
        'top_n_performers' => 2, // Top N performers are considered exceptional
        'composite_score_multiplier' => 1.5, // Composite score > average * multiplier
    ],

    /*
    | Reward tier structure
    | Stars thresholds for program rewards
    */
    'reward_tiers' => [5, 10, 15, 20, 25, 30, 35, 40, 45, 50],

    /*
    | Card art assignment rules
    | Probability of Spotlight vs Topps Now for pack players
    */
    'card_art_distribution' => [
        'spotlight' => 0.5, // 50% chance
        'topps_now' => 0.5, // 50% chance
    ],

    /*
    | Optional OVR progression schedule (for reference/suggestions only)
    | Actual max OVR is provided as a parameter
    */
    'ovr_progression' => [
        'november' => ['min' => 86, 'max' => 88],
        'december' => ['min' => 88, 'max' => 90],
        'january' => ['min' => 90, 'max' => 92],
        'february' => ['min' => 92, 'max' => 95],
        'march' => ['min' => 95, 'max' => 99],
    ],
];
