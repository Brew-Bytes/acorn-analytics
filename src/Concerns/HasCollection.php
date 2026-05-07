<?php

declare(strict_types=1);

namespace BrewAndBytes\AcornAnalytics\Concerns;

use Illuminate\Support\Collection;

trait HasCollection
{
    protected function collect(mixed $value = []): Collection
    {
        return Collection::make($value);
    }
}
