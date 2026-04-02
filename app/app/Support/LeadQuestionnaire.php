<?php

namespace App\Support;

class LeadQuestionnaire
{
    public static function definition(): array
    {
        return [
            [
                'key' => 'names',
                'label' => 'What are your names?',
                'type' => 'text',
                'required' => true,
            ],
            [
                'key' => 'nationality',
                'label' => 'What is your nationality?',
                'type' => 'text',
                'required' => true,
            ],
            [
                'key' => 'about_yourselves',
                'label' => 'Could you please let us know a little about yourselves (age, job, interests, hobbies, where, when, how you met)',
                'type' => 'textarea',
                'required' => true,
            ],
            [
                'key' => 'important_as_couple',
                'label' => "What's important to you as a couple?",
                'type' => 'textarea',
                'required' => true,
            ],
            [
                'key' => 'describe_yourselves',
                'label' => 'Would you describe yourselves as',
                'help' => 'Please select maximum 3 answers that mostly represent you.',
                'type' => 'checkboxes',
                'required' => true,
                'max' => 3,
                'options' => [
                    'Family oriented',
                    'Laid back',
                    'DIY',
                    'Romantic',
                    'Same sex',
                    'Detail oriented and organized',
                    'Indecisive and disorganized',
                    'Fusion',
                ],
            ],
            [
                'key' => 'wedding_period',
                'label' => 'When would you like to get married?',
                'type' => 'text',
                'required' => true,
            ],
            [
                'key' => 'estimated_guest_count',
                'label' => 'How many guests are you expecting?',
                'type' => 'text',
                'required' => true,
            ],
            [
                'key' => 'desired_region',
                'label' => 'In what Italian region would you like to get married?',
                'type' => 'text',
                'required' => true,
            ],
            [
                'key' => 'ceremony_type',
                'label' => 'What type of ceremony would you like to have?',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    'Symbolic',
                    'Civil',
                    'Religious',
                ],
            ],
            [
                'key' => 'wedding_vision',
                'label' => 'What does your ideal wedding feel like? Describe your wedding vision in terms of floral arrangements, lights, music and whatever you think is important for us to understand it',
                'type' => 'textarea',
                'required' => true,
            ],
            [
                'key' => 'pinterest_board',
                'label' => 'Have you created a Pinterest Board with a collection of images of weddings you like? If so, please share the link.',
                'type' => 'text',
                'required' => true,
            ],
            [
                'key' => 'venue_types',
                'label' => 'What type of venue are you imagining for your wedding?',
                'help' => 'Max 3 answers.',
                'type' => 'checkboxes',
                'required' => true,
                'max' => 3,
                'options' => [
                    'Castle',
                    'Villa',
                    '4 stars hotel',
                    '5 stars hotel',
                    'Country house',
                    'Farmhouse',
                    'Other',
                ],
            ],
            [
                'key' => 'booking_plan',
                'label' => 'Are you planning on booking:',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    'A venue where all or part of the guests could stay for few nights',
                    'A venue for the wedding day only with no accommodation',
                    'Still to be defined',
                    'We already booked our venue',
                ],
            ],
            [
                'key' => 'guest_accommodation_payment',
                'label' => 'Will you pay for your guests accommodation or will they pay themselves? Or other?',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    "We'll cover the cost for accommodation for everyone",
                    "We'll cover the cost for accommodation for part of the guests",
                    'Guests will pay themselves for their stay',
                    "Guests will pay a part of the accommodation and we'll pay the balance",
                ],
            ],
            [
                'key' => 'wedding_end_time',
                'label' => 'Until what time are you hoping the wedding to last?',
                'help' => 'Some venues in Italy have a music curfew at midnight due to municipality regulations.',
                'type' => 'text',
                'required' => true,
            ],
            [
                'key' => 'additional_events',
                'label' => 'Are you thinking of planning side events, in addition to the main day, such as a welcome dinner and/or farewell event? If so, what and how many guests do you expect?',
                'type' => 'textarea',
                'required' => true,
            ],
            [
                'key' => 'wedding_budget',
                'label' => "What's your wedding budget (excluding accommodation and pre/post wedding events)",
                'type' => 'textarea',
                'required' => true,
            ],
            [
                'key' => 'side_events_budget',
                'label' => "What's your wedding budget for side events?",
                'type' => 'textarea',
                'required' => true,
            ],
            [
                'key' => 'priority_services',
                'label' => "What's more important for you among these services? Choose max 3.",
                'type' => 'checkboxes',
                'required' => true,
                'max' => 3,
                'options' => [
                    'Music',
                    'Flowers',
                    'Lighting',
                    'Photography',
                    'Video',
                    'Hair and make-up',
                    'Food',
                    'Drinks',
                    'Content creation',
                ],
            ],
            [
                'key' => 'table_setup',
                'label' => 'For your table set up, you envision',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    'Country, wooden long table(s)',
                    'Long or round tables with tablecloth',
                    'Serpentine tables',
                ],
            ],
            [
                'key' => 'flower_palette',
                'label' => 'In terms of flowers, do you prefer:',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    'Green and white colour palette',
                    'Coloured palette',
                ],
            ],
            [
                'key' => 'can_travel_before_wedding',
                'label' => 'Will you be able to travel to Italy before the wedding for meetings with suppliers and tasting?',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    'Yes',
                    'No',
                ],
            ],
            [
                'key' => 'planner_expectations',
                'label' => 'What do you expect from your wedding planner?',
                'type' => 'textarea',
                'required' => true,
            ],
            [
                'key' => 'videographer_interest',
                'label' => 'Are you interested in hiring a videographer for your wedding day?',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    'Yes',
                    'No',
                ],
            ],
            [
                'key' => 'already_hired_suppliers',
                'label' => 'Have you already hired any supplier for your wedding? If so, who?',
                'type' => 'textarea',
                'required' => true,
            ],
            [
                'key' => 'social_media_consent',
                'label' => 'Would you allow us to post videos and pictures of your wedding on our social medias and website to promote our business (like what we post regularly on our public accounts)?',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    'Yes',
                    'No',
                    'Maybe',
                ],
            ],
            [
                'key' => 'discovery_source',
                'label' => 'Where did you find out about Irene and Fairytale Italy Weddings?',
                'type' => 'radio',
                'required' => true,
                'options' => [
                    'Google',
                    'Instagram',
                    'Facebook',
                    'Word of mouth',
                    'I was a guest at a past wedding',
                    'Referral',
                    'Facebook group',
                    'La Lista',
                    'Other',
                ],
            ],
            [
                'key' => 'additional_notes',
                'label' => "Please let us know anything you think it's important for us about you or your wedding (kosher food, guests with special needs, etc)",
                'type' => 'textarea',
                'required' => false,
            ],
        ];
    }

    public static function byKey(): array
    {
        return collect(static::definition())
            ->keyBy('key')
            ->all();
    }
}
