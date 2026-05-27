<?php

namespace App\Services\FederalPrograms\OfficialSources;

use App\Models\Municipality;
use App\Services\FederalPrograms\Contracts\OfficialApiRadarSource;
use App\Services\FederalPrograms\TransparenciaClient;

class PortalTransparenciaRadarSource implements OfficialApiRadarSource
{
    public function __construct(
        private readonly TransparenciaClient $client,
    ) {}

    public function sourceKey(): string
    {
        return 'portal_transparencia';
    }

    public function sourceName(): string
    {
        return 'Portal da Transparencia Federal';
    }

    public function fetch(Municipality $municipality): array
    {
        return $this->client->fetchTransfers($municipality->ibge_code);
    }
}
