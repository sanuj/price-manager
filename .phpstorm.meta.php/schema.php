<?php

namespace Illuminate\Database\Schema {

    /**
     * Class BlueprintReference
     *
     * @method BlueprintForeign references(string $column)
     * @method BlueprintForeign on(string $table)
     * @method BlueprintForeign onDelete(string $action)
     * @method BlueprintForeign onUpdate(string $action)
     */
    class BlueprintForeign
    {
    }

    /**
     * Class BlueprintColumn
     *
     * @method BlueprintColumn comment(string $comment)
     * @method BlueprintColumn default($value)
     * @method BlueprintColumn nullable()
     * @method BlueprintColumn unsigned()
     * @method BlueprintColumn unique()
     */
    class BlueprintColumn
    {
    }

    /**
     * Class Blueprint
     *
     * @method BlueprintColumn boolean(string $column)
     * @method BlueprintColumn date(string $column)
     * @method BlueprintColumn enum(string $column, array $values)
     * @method BlueprintForeign foreign(string|array $columns, string $name = null)
     * @method void index(string|array $columns, string $name = null)
     * @method BlueprintColumn integer(string  $column, bool $autoIncrement = false, bool $unsigned = false)
     * @method BlueprintColumn ipAddress(string $column)
     * @method BlueprintColumn json(string $column)
     * @method void primary(string|array $columns, string $name = null)
     * @method BlueprintColumn string(string  $column, int  $length = 255)
     * @method BlueprintColumn text(string  $column)
     * @method BlueprintColumn timestamp(string  $column)
     * @method void unique(string $column, string $name = null)
     * @method BlueprintColumn unsignedInteger(string $column)
     */
    class Blueprint
    {
    }
}
