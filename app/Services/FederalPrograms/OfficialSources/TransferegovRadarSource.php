<?php

namespace App\Services\FederalPrograms\OfficialSources;

use App\Models\Municipality;
use App\Services\FederalPrograms\Contracts\OfficialApiRadarSource;
use App\Services\FederalPrograms\TransferegovClient;

class TransferegovRadarSource implements OfficialApiRadarSource
{
    public function __construct(
        private readonly TransferegovClient $client,
    ) {}

    public function sourceKey(): string
    {
        return 'transferegov';
    }

    public function sourceName(): string
    {
        return 'Transferegov';
    }

    public function fetch(Municipality $municipality): array
    {
        return $this->client->fetchByMunicipality($municipality->ibge_code);
    }
}
