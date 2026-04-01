<?php

namespace Dcodegroup\LaravelXeroOauth\Models;

use Dcodegroup\LaravelXeroOauth\Database\Factories\XeroTokenFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;

class XeroToken extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory()
    {
        return XeroTokenFactory::new();
    }

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

    public function tenant(): ?BelongsTo
    {
        $model = config('laravel-xero-oauth.multi_tenant_model');
        
        if ($model) {
            return $this->belongsTo($model, 'tenant_id');
        }

        return null;
    }
}
