<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class LlmClient
{
    public function suggestTopics(array $summary): array
    {
        $apiKey = config('services.gemini.key');
        $model  = config('services.gemini.model', 'gemini-2.0-flash');

        if (empty($apiKey)) {
            throw new RuntimeException(
                'Chave da API Gemini não configurada. '
                . 'Adicione GEMINI_API_KEY no .env. '
                . 'Obtenha gratuitamente em: https://aistudio.google.com'
            );
        }

        $prompt = 'Você é um editor-chefe experiente. '
            . 'Responda APENAS com JSON válido, sem texto adicional, sem blocos de código, sem explicações. '
            . 'O formato obrigatório é exatamente: '
            . '{"suggestions":[{"title":"...","hook":"...","seo_keywords":["...","...","..."]}]} '
            . 'Gere entre 3 e 5 sugestões. Cada seo_keywords deve ter exatamente 3 strings. '
            . 'Com base neste resumo editorial, sugira novas pautas alinhadas ao perfil do site: '
            . json_encode($summary, JSON_UNESCAPED_UNICODE);

        $http = Http::acceptJson()->timeout(30);

        if (config('services.gemini.verify_ssl', true) === false) {
            $http = $http->withoutVerifying();
        }

        $response = $http->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]
        );

        // Log completo para diagnóstico — aparece em storage/logs/laravel.log
        \Illuminate\Support\Facades\Log::debug('Gemini raw response', [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
        ]);

        if ($response->status() === 400) {
            $detail = data_get($response->json(), 'error.message', $response->body());
            throw new RuntimeException("Requisição inválida para o Gemini: {$detail}");
        }

        if ($response->status() === 403 || $response->status() === 401) {
            $detail = data_get($response->json(), 'error.message', 'sem detalhes');
            throw new RuntimeException(
                "Gemini recusou a requisição (status {$response->status()}): {$detail} — "
                . 'Possível causa: chave inválida, ou o Gemini free tier não está disponível na sua região. '
                . 'Tente acessar https://aistudio.google.com e confirme que consegue usar o modelo pelo navegador.'
            );
        }

        if ($response->status() === 429) {
            $detail = data_get($response->json(), 'error.message', 'sem detalhes');
            throw new RuntimeException(
                "Gemini retornou 429: {$detail} — "
                . 'Se for a primeira requisição, o problema pode ser bloqueio por região (Brasil). '
                . 'Verifique o arquivo storage/logs/laravel.log para ver a mensagem completa.'
            );
        }

        if (! $response->successful()) {
            $detail = data_get($response->json(), 'error.message', $response->body());
            throw new RuntimeException("Gemini retornou erro {$response->status()}: {$detail}");
        }

        $content = data_get($response->json(), 'candidates.0.content.parts.0.text', '');

        if (empty($content)) {
            $blockReason = data_get($response->json(), 'candidates.0.finishReason');
            if ($blockReason && $blockReason !== 'STOP') {
                throw new RuntimeException(
                    "Gemini bloqueou a resposta (motivo: {$blockReason})."
                );
            }
            throw new RuntimeException('Gemini retornou resposta vazia. Tente novamente.');
        }

        $clean   = $this->stripMarkdownFences((string) $content);
        $decoded = json_decode($clean, true);

        return $this->validate($decoded);
    }

    public function validate(?array $payload): array
    {
        if (! is_array($payload) || ! isset($payload['suggestions']) || ! is_array($payload['suggestions'])) {
            throw new RuntimeException('O modelo retornou JSON em formato inesperado. Tente novamente.');
        }

        return collect($payload['suggestions'])
            ->take(5)
            ->map(function (array $item): array {
                $keywords = isset($item['seo_keywords']) && is_array($item['seo_keywords'])
                    ? collect($item['seo_keywords'])
                        ->filter(fn ($kw): bool => is_string($kw) && trim($kw) !== '')
                        ->take(3)
                        ->values()
                        ->all()
                    : [];

                if (empty($item['title']) || empty($item['hook']) || count($keywords) < 1) {
                    throw new RuntimeException(
                        'Uma sugestão veio incompleta (título, gancho ou palavras-chave ausentes).'
                    );
                }

                while (count($keywords) < 3) {
                    $keywords[] = 'conteúdo';
                }

                return [
                    'title'        => (string) $item['title'],
                    'hook'         => (string) $item['hook'],
                    'seo_keywords' => $keywords,
                ];
            })
            ->values()
            ->all();
    }

    private function stripMarkdownFences(string $text): string
    {
        $text = trim($text);
        if (preg_match('/^```(?:json)?\s*([\s\S]*?)\s*```$/i', $text, $matches)) {
            return trim($matches[1]);
        }
        return $text;
    }
}
