<?php

declare(strict_types=1);

namespace Michalsn\CodeIgniterNestedModel\Enums;

enum RelationTypes
{
    case hasOne;
    case hasMany;
    case belongsTo;
    case belongsToMany;
}
