<?php

namespace Dcodegroup\LaravelXeroOauth;

use Calcinai\OAuth2\Client\Provider\Xero;
use Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero;
use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Support\Facades\Schema;
use League\OAuth2\Client\Token\AccessToken;

class XeroTokenService
{
    /**
     * @return \League\OAuth2\Client\Token\AccessToken|mixed|null
     * @throws \Dcodegroup\LaravelXeroOauth\Exceptions\UnauthorizedXero
     */
    public static function getToken()
    {
        if (!Schema::hasTable((new XeroToken)->getTable())) {
            return null;
        }

        $token = XeroToken::latestToken();
        if (!$token) {
            return null;
        }

        $oauth2Token = $token->toOAuth2Token();

        if ($oauth2Token->hasExpired()) {
            $oauth2Token = self::getAccessTokenFromXero($oauth2Token);

            if (!XeroToken::isValidTokenFormat($oauth2Token)) {
                throw new UnauthorizedXero('Token is invalid or the provided token has invalid format!');
            }

            XeroToken::create(array_merge($oauth2Token->jsonSerialize(), ['current_tenant_id'=> $token->current_tenant_id]));
        }

        return $oauth2Token;
    }

    /**
     * @param  \League\OAuth2\Client\Token\AccessToken  $token
     *
     * @return mixed
     */
    private static function getAccessTokenFromXero(AccessToken $token)
    {
        return resolve(Xero::class)->getAccessToken('refresh_token', [
            'refresh_token' => $token->getRefreshToken(),
        ]);
    }
}
