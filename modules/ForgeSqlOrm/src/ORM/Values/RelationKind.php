<?php

declare(strict_types=1);

namespace App\Modules\ForgeSqlOrm\ORM\Values;

enum RelationKind: string
{
    case HasOne = 'hasOne';
    case HasMany = 'hasMany';
    case BelongsTo = 'belongsTo';
}
