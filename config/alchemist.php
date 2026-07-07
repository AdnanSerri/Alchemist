<?php

use Serri\Alchemist\Ingredients\FillableIngredient;
use Serri\Alchemist\Ingredients\GuardedIngredient;
use Serri\Alchemist\Ingredients\MutagenIngredient;
use Serri\Alchemist\Ingredients\RelationIngredient;

return [

    /*
    |--------------------------------------------------------------------------
    | Formulas Folder Path
    |--------------------------------------------------------------------------
    |
    | This value will determine where the Formulas folder lives in your application codebase.
    |
    */

    'formulas_folder_path' => app_path('Formulas'),

    /*
    |--------------------------------------------------------------------------
    | Formula Namespaces
    |--------------------------------------------------------------------------
    |
    | The namespaces searched (in order) when resolving a model's fallback
    | formula class ({Model}Formula, then the generic Formula). Add your
    | module namespaces here for modular / DDD codebases. The first entry
    | is also where make:formula generates classes, so keep it paired with
    | the formulas_folder_path above.
    |
    */

    'formula_namespaces' => [
        'App\\Formulas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ingredients
    |--------------------------------------------------------------------------
    |
    | Defines the model properties where the alchemist should search for the
    | requested formula ingredients.
    |
    | Usage Example:
    |   formula: ['field_1', 'field_2', 'field_3', 'relation1', ...]
    |   ingredients: ['fillable', 'related']
    |
    | In this example, the alchemist will attempt to locate 'field_1', 'field_2',
    | 'field_3', and 'relation1' within the model's 'fillable' and 'related' attributes.
    |
    | Extendability:
    | Additional ingredients can be incorporated by either utilizing the
    | pre-defined Ingredients provided by the package or by creating custom
    | Ingredients. Please refer to the documentation for guidance on defining
    | custom Ingredients.
    |
    */

    'ingredients' => [
        FillableIngredient::class,
        GuardedIngredient::class,
        MutagenIngredient::class,
        RelationIngredient::class,

        // Custom Ingredients goes here...
    ],

    /*
    |--------------------------------------------------------------------------
    | Respect $hidden
    |--------------------------------------------------------------------------
    |
    | When enabled (the default), fields listed in a model's $hidden property
    | are never exposed to formulas — matching Eloquent's own serialisation
    | contract. A formula referencing a hidden field throws with a clear
    | message. Disable only if you deliberately brew hidden attributes.
    |
    */

    'respect_hidden' => true,
];
