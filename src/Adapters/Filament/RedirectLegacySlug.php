<?php

declare(strict_types=1);

namespace Simtabi\Laranail\EnvKit\WebUI\Adapters\Filament;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Simtabi\Laranail\EnvKit\WebUI\Support\RegisteredNames;
use Simtabi\Laranail\EnvKit\WebUI\Support\DeprecationNotices;

/**
 * Answers the page's deprecated `{panel}/env-kit` URL with a redirect to
 * `{panel}/laranail-env-kit-webui`, keeping the query string.
 *
 * The target is the request's own path with its last segment replaced, so a
 * tenant or domain prefix the panel adds survives without this class knowing
 * about it. The page itself still decides access when the redirect lands.
 *
 * @deprecated exists only for the old slug; goes with it, no earlier than the
 *             next minor after 0.1.
 */
final class RedirectLegacySlug
{
    public function __invoke(Request $request): RedirectResponse
    {
        DeprecationNotices::once('filament-slug', sprintf(
            'laranail/env-kit-webui: the Filament page slug [%s] is deprecated and will be removed no earlier than the next minor after 0.1; use [%s].',
            RegisteredNames::LEGACY_FILAMENT_SLUG,
            RegisteredNames::FILAMENT_SLUG,
        ));

        $path = rtrim($request->getPathInfo(), '/');
        $legacy = '/' . RegisteredNames::LEGACY_FILAMENT_SLUG;

        if (str_ends_with($path, $legacy)) {
            $path = substr($path, 0, -strlen($legacy));
        }

        $query = $request->getQueryString();

        return new RedirectResponse(
            $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $path . '/' . RegisteredNames::FILAMENT_SLUG
            . ($query !== null && $query !== '' ? '?' . $query : ''),
        );
    }
}
