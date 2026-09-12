<?php

namespace Cryonighter\ValidRequestBundle\Payload;

use Cryonighter\ValidRequestBundle\Attribute\ValidRequest;
use Cryonighter\ValidRequestBundle\Enum\RequestValueSource;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * High-level abstraction over all meaningful data of a single.
 *
 * Note: despite the name, this is NOT a wrapper around Symfony's request body
 * InputBag "payload". Here "payload" means "all useful data extracted from the
 * request". The concrete origin of that data is defined by {@see ValidRequest::$source}
 * (query, attributes, headers, server, files, cookies or the body payload).
 *
 * Primary source vs. alternative sources:
 *  - The primary source (matching {@see ValidRequest::$source}) is eagerly extracted
 *    into the internal $payload array. This is the hot path: value lookups and the
 *    recursive traversal over nested structures ({@see self::nested()}) operate on it.
 *  - Any other source is considered an alternative source. Values from alternative
 *    sources are read lazily and directly from the {@see Request} on demand
 *    (see {@see self::getValue()} / {@see self::hasValue()}). This path
 *    is rarely used and is triggered explicitly by the user via #[BindRequestParam].
 *
 * Nesting semantics:
 *  - {@see self::nested()} descends into the internal $payload array only. Iteration
 *    over nested structures always happens over the primary source.
 *  - A nested Payload still carries the original root {@see Request}. Therefore any
 *    access to an alternative source from a nested level is resolved against the ROOT
 *    request, not against the current nesting level. This is intentional: alternative
 *    sources are always addressed from the root and are declared explicitly by the
 *    user via #[BindRequestParam], so no scoping surprises are expected.
 */
readonly final class Payload
{
    public function __construct(
        private array $payload,
        public Request $request,
        public ValidRequest $validRequest,
    ) {
    }

    public static function createFromRequest(Request $request, ValidRequest $validRequest): static
    {
        $payload = self::getParameterBag($request, $validRequest->source)->all();

        // Обогащаем payload ключами в camelCase, это нужно для автоматического маппинга в объекты по имени
        // Исходные ключи оставляем, так как они могут быть нужны для ручного маппинга через #[BindRequestParam]
        if ($validRequest->source == RequestValueSource::server) {
            foreach ($payload as $key => $value) {
                $payload[static::camelCase($key)] = $value;
            }
        }

        // Это, конечно, не корректно, так как иметь несколько значений для одного имени - нормально для заголовков.
        // Но вся архитектура выстроена вокруг концепции "ключ=>значение" и я не знаю, как сделать по другому.
        // Порядок передачи заголовков все равно не гарантирован, поэтому беру первый в массиве
        // Сам Symfony так же делает, когда вызываешь $request->headers->get('header-name')
        if ($validRequest->source == RequestValueSource::headers) {
            foreach ($payload as $key => $values) {
                $value = $values[0] ?? null;

                $payload[$key] = $value;
                $payload[static::camelCase(str_replace('-', '_', $key))] = $value;
            }
        }

        return new static($payload, $request, $validRequest);
    }

    public function nested(string $name): static
    {
        return new static($this->payload[$name] ?? [], $this->request, $this->validRequest);
    }

    public function getValue(string $name, RequestValueSource $source): mixed
    {
        if ($source == $this->validRequest->source) {
            return $this->payload[$name] ?? null;
        }

        return self::getParameterBag($this->request, $source)->get($name);
    }

    public function hasValue(string $name, RequestValueSource $source): bool
    {
        if ($source == $this->validRequest->source) {
            return array_key_exists($name, $this->payload);
        }

        return self::getParameterBag($this->request, $source)->has($name);
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    public function empty(): bool
    {
        return empty($this->payload);
    }

    private static function getParameterBag(Request $request, RequestValueSource $source): ParameterBag|HeaderBag
    {
        return match ($source) {
            RequestValueSource::attributes => $request->attributes,
            RequestValueSource::cookies => $request->cookies,
            RequestValueSource::headers => $request->headers,
            RequestValueSource::files => $request->files,
            RequestValueSource::server => $request->server,
            RequestValueSource::payload => $request->getPayload(),
            RequestValueSource::query => $request->query,
        };
    }

    private static function camelCase(string $value): string
    {
        $words = explode('_', strtolower($value));

        $firstWord = array_shift($words);

        return $firstWord . implode('', array_map(ucfirst(...), $words));
    }
}
