<?php

namespace App\Services\FederalPrograms\Contracts;

use App\Models\Municipality;

interface OfficialApiRadarSource
{
    public function sourceKey(): string;

    public function sourceName(): string;

    public function fetch(Municipality $municipality): array;
}
