<?php

namespace Dcodegroup\LaravelXeroOauth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class XeroToken extends Model
{
    use HasFactory;

    /**
     * Fields that are not mass assignable
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * @return XeroToken
     */
    public static function latestToken()
    {
        return self::latest('id')->first();
    }

    /**
     * @return AccessToken
     */
    public function toOAuth2Token()
    {
        return new AccessToken($this->toArray());
    }

    /**
     * @param  AccessTokenInterface  $token
     * @return bool
     */
    public static function isValidTokenFormat(AccessTokenInterface $token)
    {
        return ! Validator::make($token->jsonSerialize(), [
            'id_token' => 'required',
            'token_type' => 'required',
            'access_token' => 'required',
            'refresh_token' => 'required',
            'expires' => 'required',
            'scope' => 'required',
        ])->fails();
    }
}
