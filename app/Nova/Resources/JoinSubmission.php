<?php

namespace App\Nova\Resources;

use App\Models\JoinSubmission as JoinSubmissionModel;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Panel;
use Laravel\Nova\Fields\Heading;
use App\Nova\Actions\HandleJoinSubmission;

/**
 * Returns join requests
 *
 * @author Roelof Roos <github@roelof.io>
 * @license MPL-2.0
 */
class JoinSubmission extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = JoinSubmissionModel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'first_name',
        'last_name',
        'email',
        'postal_code',
        'street',
        'phone',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        $sixteenYears = today()->subYear(16)->format('Y-m-d');

        return [
            new Panel('Basisinformatie', [
                ID::make()->sortable(),

                // Heading in form
                Heading::make('Persoonsgegevens')->onlyOnForms(),

                // Name
                Text::make('Naam', 'name')
                    ->sortable()
                    ->onlyOnIndex(),

                // Full name
                Text::make('Voornaam', 'first_name')
                    ->hideFromIndex()
                    ->rules(['required', 'string', 'min:2']),
                Text::make('Tussenvoegsel', 'insert')
                    ->hideFromIndex()
                    ->rules(['required', 'string', 'min:2']),
                Text::make('Achternaam', 'last_name')
                    ->hideFromIndex()
                    ->rules(['required', 'string', 'min:2']),

                // Date of Brith
                Date::make('Geboortedatum', 'date-of-birth')
                    ->hideFromIndex()
                    ->format('DD-MM-YYYY')
                    ->rules(['required', "before:{$sixteenYears}"]),
            ]),
            new Panel('Adres informatie', [
                Text::make('Adres', function () {
                    return "{$this->street} {$this->number}";
                })->onlyOnDetail(),

                // Heading in form
                Heading::make('Adres')->onlyOnForms(),

                Text::make('Straat', 'street')
                    ->onlyOnForms()
                    ->rules(['required', 'string', 'regex:/\w+/']),

                Text::make('Huisnummer', 'number')
                    ->onlyOnForms()
                    ->rules(['required', 'string', 'regex:/^\d+/']),

                Text::make('Postcode', 'postal_code')
                    ->hideFromIndex()
                    ->rules(['required', 'string', 'regex:/^[0-9A-Z \.]+$/i']),

                Text::make('Plaats', 'city')
                    ->hideFromIndex()
                    ->rules(['required', 'string', 'min:2']),
            ]),

            new Panel('Contact informatie', [
                // Heading in form
                Heading::make('Communicatie')->onlyOnForms(),

                Text::make('E-mailadres', 'email')
                    ->withMeta(['type' => 'email'])
                    ->rules(['required', 'email']),

                Text::make('Telefoonnummer', 'phone')
                    ->withMeta(['type' => 'phone'])
                    ->hideFromIndex()
                    ->rules(['required', 'string', 'regex:/^\+?([\s-\.]?\d){8,}/']),
            ]),

            new Panel('Voorkeuren en inschrijvingsinformatie', [
                // Heading in form
                Heading::make('Voorkeuren en inschrijvingsinformatie')->onlyOnForms(),

                Boolean::make('Windesheim Student', 'windesheim_student')
                    ->hideFromIndex(),
                Boolean::make('Aanmelding Gumbode', 'newsletter')
                    ->hideFromIndex()
            ]),

        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [
            new HandleJoinSubmission(),
        ];
    }
}