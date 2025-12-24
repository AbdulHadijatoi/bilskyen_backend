<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Builder;

class FilterHelper
{
    /**
     * Apply filters to query builder
     */
    public static function applyFilters(Builder $query, array $filters, string $joinOperator = 'or'): Builder
    {
        if (empty($filters)) {
            return $query;
        }

        $query->where(function ($q) use ($filters, $joinOperator) {
            foreach ($filters as $filter) {
                $field = $filter['id'] ?? null;
                $value = $filter['value'] ?? null;
                $operator = $filter['operator'] ?? 'equals';
                $variant = $filter['variant'] ?? 'text';

                if (!$field || $value === null) {
                    continue;
                }

                $method = $joinOperator === 'and' ? 'where' : 'orWhere';

                switch ($operator) {
                    case 'equals':
                        $q->{$method}($field, $value);
                        break;
                    case 'contains':
                        $q->{$method}($field, 'like', "%{$value}%");
                        break;
                    case 'gte':
                        $q->{$method}($field, '>=', $value);
                        break;
                    case 'lte':
                        $q->{$method}($field, '<=', $value);
                        break;
                    case 'gt':
                        $q->{$method}($field, '>', $value);
                        break;
                    case 'lt':
                        $q->{$method}($field, '<', $value);
                        break;
                    case 'in':
                        $q->{$method . 'In'}($field, is_array($value) ? $value : [$value]);
                        break;
                    case 'notIn':
                        $q->{$method . 'NotIn'}($field, is_array($value) ? $value : [$value]);
                        break;
                }
            }
        });

        return $query;
    }

    /**
     * Apply search to query builder
     */
    public static function applySearch(Builder $query, ?string $search, array $searchableFields): Builder
    {
        if (!$search || empty($searchableFields)) {
            return $query;
        }

        $query->where(function ($q) use ($search, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$search}%");
            }
        });

        return $query;
    }

    /**
     * Apply sorting to query builder
     */
    public static function applySorting(Builder $query, array $sort): Builder
    {
        if (empty($sort)) {
            return $query;
        }

        foreach ($sort as $sortItem) {
            $field = $sortItem['id'] ?? null;
            $direction = ($sortItem['desc'] ?? false) ? 'desc' : 'asc';

            if ($field) {
                $query->orderBy($field, $direction);
            }
        }

        return $query;
    }
}


