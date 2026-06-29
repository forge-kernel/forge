<?php

declare(strict_types=1);

namespace Modules\ForgeSqlOrm\ORM\Values;

enum RelationKind: string
{
    case HasOne = 'hasOne';
    case HasMany = 'hasMany';
    case BelongsTo = 'belongsTo';
    case BelongsToMany = 'belongsToMany';
}
