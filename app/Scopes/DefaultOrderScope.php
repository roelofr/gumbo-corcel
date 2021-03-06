<?php

declare(strict_types=1);

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * A default ordering scope, used to add a sort order if no order is specified yet.
 */
class DefaultOrderScope implements Scope
{
    /**
     * Order column.
     *
     * @var string
     */
    private $column;

    /**
     * Order direction.
     *
     * @var string
     */
    private $direction;

    /**
     * Add a default sorting order.
     */
    public function __construct(string $column, string $direction = 'asc')
    {
        $this->column = $column;
        $this->direction = $direction;
    }

    /**
     * Apply the default sort order.
     *
     * @return void
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    public function apply(Builder $builder, Model $model)
    {
        // Check if no orders are set yet
        if (! empty($builder->getQuery()->orders)) {
            return;
        }

        // Add our order
        $builder->orderBy($this->column, $this->direction);
    }
}
