# YouTube Data API v3 — Setup

## 1. Criar chave de API (5 minutos)

1. Acesse [console.cloud.google.com](https://console.cloud.google.com)
2. Crie um projeto (ou use um existente)
3. Menu lateral → **APIs e Serviços** → **Biblioteca**
4. Busque **"YouTube Data API v3"** → Ativar
5. Menu lateral → **Credenciais** → **Criar credenciais** → **Chave de API**
6. Copie a chave gerada

> Recomendado: restrinja a chave por **IP do servidor** em "Restrições de aplicativo" para evitar uso não autorizado.

---

## 2. Adicionar no `.env`

```env
YOUTUBE_API_KEY=AIzaSy...sua-chave-aqui
```

Adicionar também no `.env.example`:

```env
# YouTube Data API v3 (monitoramento de menções)
# Obter em: console.cloud.google.com → APIs → YouTube Data API v3
YOUTUBE_API_KEY=
```

---

## 3. O que foi alterado no `SocialMonitorService.php`

### `monitor()` — loop por keyword
Após o bloco Nitter, adicionado bloco YouTube:
```php
// YouTube Data API v3
if (!empty($definition['allow_social']) && $this->youTubeApiKey() !== '') {
    $mentions = $this->fetchYouTube($term, $municipality);
    // ...
}
```
- Só executa se `YOUTUBE_API_KEY` estiver configurada
- Mesma condição `allow_social` do Nitter (keywords do tipo `topic` não buscam no YouTube)
- Erros são logados em `$errors[]` e retornados no resultado do `monitor()`

### `getScanTargets()` — painel de configuração
Adicionada entrada de preview da URL do YouTube quando a chave estiver configurada:
```
YouTube (Data API v3) → https://www.googleapis.com/youtube/v3/search?...
```
(A chave aparece como `***` no preview por segurança.)

### Novos métodos privados
- `fetchYouTube(string $keyword, Municipality $municipality): array`
- `youTubeApiKey(): string`

---

## 4. Comportamento

| Situação | O que acontece |
|---|---|
| `YOUTUBE_API_KEY` vazia | YouTube silenciosamente ignorado, sem erro |
| Chave inválida / quota 403 | Erro registrado em `$errors[]`, continua com outras fontes |
| Keyword do tipo `topic` | Não busca no YouTube (mesma regra do Nitter) |
| Vídeo já indexado | `external_id = md5(videoId + municipalityId)` bloqueia duplicata |

---

## 5. Quota do Google

- Cada chamada `search.list` = **100 unidades**
- Limite gratuito = **10.000 unidades/dia** = 100 buscas/dia por projeto
- Com 5 municípios e 4 keywords cada = 20 chamadas por ciclo de 2h = **240/dia** — dentro da quota

Se crescer muito: criar múltiplos projetos Google Cloud (um por cliente) ou implementar cache de resultados.

---

## 6. Modelo `SocialMention` — suporte já existente

Nenhuma migration necessária. O campo `platform` já aceita `'youtube'` e o accessor `getPlatformIconAttribute()` já retorna `'▶️'` para ele. O campo `source_label` retornará `'YouTube'` pelo fallback `Str::headline('youtube')`.

Para label explícito, adicionar no `getSourceLabelAttribute()` do model:
```php
'youtube' => 'YouTube',
```
