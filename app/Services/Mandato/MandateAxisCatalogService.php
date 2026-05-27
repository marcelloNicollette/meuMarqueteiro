<?php

namespace App\Services\Mandato;

use App\Models\MandateAxis;
use App\Models\Municipality;
use Illuminate\Support\Collection;

class MandateAxisCatalogService
{
    public function defaults(): array
    {
        return [
            ['icon' => '🏥', 'name' => 'Saúde', 'description' => 'UBS/UPA · Saúde da família · Saúde mental · Vigilância'],
            ['icon' => '🎓', 'name' => 'Educação', 'description' => 'Creches · Ensino fundamental · Valorização docente · Infraestrutura'],
            ['icon' => '🚌', 'name' => 'Mobilidade e Infraestrutura', 'description' => 'Transporte público · Pavimentação · Ciclovias · Iluminação pública'],
            ['icon' => '🌿', 'name' => 'Meio Ambiente e Saneamento', 'description' => 'Coleta seletiva · Áreas verdes · Saneamento · Arborização'],
            ['icon' => '💼', 'name' => 'Desenvolvimento Econômico', 'description' => 'Emprego · MEI · Qualificação · Turismo'],
            ['icon' => '🤝', 'name' => 'Assistência Social e Direitos', 'description' => 'CRAS/CREAS · Habitação · Direitos da mulher · Vulnerabilidade'],
            ['icon' => '🛡️', 'name' => 'Segurança Pública', 'description' => 'Guarda municipal · Câmeras · Prevenção · Iluminação em áreas de risco'],
            ['icon' => '💻', 'name' => 'Gestão, Tecnologia e Transparência', 'description' => 'Governo digital · Portal da transparência · Eficiência fiscal'],
            ['icon' => '🎭', 'name' => 'Cultura, Esporte e Lazer', 'description' => 'Centros culturais · Esporte amador · Praças e parques'],
        ];
    }

    public function ensureDefaultAxes(Municipality $municipality): Collection
    {
        $existing = MandateAxis::query()
            ->where('municipality_id', $municipality->id)
            ->orderBy('order')
            ->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        foreach ($this->defaults() as $index => $axis) {
            MandateAxis::create([
                ...$axis,
                'municipality_id' => $municipality->id,
                'order' => $index + 1,
            ]);
        }

        return MandateAxis::query()
            ->where('municipality_id', $municipality->id)
            ->orderBy('order')
            ->get();
    }

    public function axisIdBySuggestedName(Municipality $municipality, ?string $name): ?int
    {
        $normalized = $this->normalize($name);
        if ($normalized === '') {
            return null;
        }

        $axes = $this->ensureDefaultAxes($municipality);

        $exact = $axes->first(fn (MandateAxis $axis) => $this->normalize($axis->name) === $normalized);
        if ($exact) {
            return $exact->id;
        }

        $contains = $axes->first(function (MandateAxis $axis) use ($normalized) {
            $axisName = $this->normalize($axis->name);

            return str_contains($axisName, $normalized) || str_contains($normalized, $axisName);
        });

        return $contains?->id;
    }

    private function normalize(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = mb_strtolower($value);
        $replacements = [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ];

        return strtr($value, $replacements);
    }
}
