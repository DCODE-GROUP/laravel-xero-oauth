<?php

namespace Dcodegroup\LaravelXeroOauth\Tests\Unit\Models;

use Dcodegroup\LaravelXeroOauth\Models\XeroToken;
use Illuminate\Support\Facades\Validator;
use League\OAuth2\Client\Token\AccessToken;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

// ==================== CRUD Tests ====================

describe('XeroToken Model - CRUD Operations', function () {
    it('can create a xero token', function () {
        $token = XeroToken::factory()->create();

        expect($token)->toBeInstanceOf(XeroToken::class);
        expect($token->id)->toBeInt();
        expect($token->id_token)->not->toBeEmpty();
        expect($token->access_token)->not->toBeEmpty();
        expect($token->refresh_token)->not->toBeEmpty();
    });

    it('can create multiple xero tokens', function () {
        $tokens = XeroToken::factory()->count(3)->create();

        expect($tokens)->toHaveCount(3);
        expect($tokens[0])->toBeInstanceOf(XeroToken::class);
    });

    it('can read a xero token from database', function () {
        XeroToken::factory()->create([
            'access_token' => 'test_access_token_123',
        ]);

        $token = XeroToken::where('access_token', 'test_access_token_123')->first();

        expect($token)->toBeInstanceOf(XeroToken::class);
        expect($token->access_token)->toBe('test_access_token_123');
    });

    it('can update a xero token', function () {
        $token = XeroToken::factory()->create([
            'current_tenant_id' => 'original_tenant',
        ]);

        $token->update([
            'current_tenant_id' => 'updated_tenant',
        ]);

        expect($token->current_tenant_id)->toBe('updated_tenant');
        assertDatabaseHas('xero_tokens', [
            'id' => $token->id,
            'current_tenant_id' => 'updated_tenant',
        ]);
    });

    it('can delete a xero token', function () {
        $token = XeroToken::factory()->create();
        $tokenId = $token->id;

        $token->delete();

        expect(XeroToken::find($tokenId))->toBeNull();
    });

    it('mass assigns fields except guarded ones', function () {
        $data = [
            'id_token' => 'new_id_token',
            'token_type' => 'Bearer',
            'access_token' => 'new_access_token',
            'refresh_token' => 'new_refresh_token',
            'scope' => 'openid email',
            'current_tenant_id' => 'tenant_123',
            'expires' => now()->addHours(1)->timestamp,
        ];

        $token = XeroToken::create($data);

        expect($token->id_token)->toBe($data['id_token']);
        expect($token->token_type)->toBe($data['token_type']);
        expect($token->access_token)->toBe($data['access_token']);
        expect($token->refresh_token)->toBe($data['refresh_token']);
    });

    it('protects id field from mass assignment', function () {
        // The 'id' field should be in guarded, so it shouldn't be mass assignable
        $token = XeroToken::factory()->create();
        $originalId = $token->id;

        // Try to update with guarded field - should be ignored
        $token->update(['id' => 9999]);

        expect($token->id)->toBe($originalId);
    });
});

// ==================== Model Attributes Tests ====================

describe('XeroToken Model - Attributes', function () {
    it('has correct fillable/guarded configuration', function () {
        $token = XeroToken::factory()->make();

        expect($token->getGuarded())->toContain('id');
    });

    it('stores timestamps automatically', function () {
        $token = XeroToken::factory()->create();

        expect($token->created_at)->not->toBeNull();
        expect($token->updated_at)->not->toBeNull();
    });

    it('updates timestamp on model update', function () {
        $token = XeroToken::factory()->create();
        $originalUpdatedAt = $token->updated_at;

        sleep(1); // Ensure time passes

        $token->update(['current_tenant_id' => 'new_tenant']);

        expect($token->updated_at)->not->toBe($originalUpdatedAt);
    });
});

// ==================== Query Scope Tests ====================

describe('XeroToken Model - Query Methods', function () {
    it('can get the latest token by latestToken method', function () {
        XeroToken::factory()->create();
        XeroToken::factory()->create();
        $latestToken = XeroToken::factory()->create([
            'access_token' => 'latest_token_access',
        ]);

        $retrieved = XeroToken::latestToken();

        expect($retrieved->id)->toBe($latestToken->id);
        expect($retrieved->access_token)->toBe('latest_token_access');
    });

    it('returns null when latestToken is called on empty table', function () {
        // Ensure table is empty
        XeroToken::query()->delete();

        $retrieved = XeroToken::latestToken();

        expect($retrieved)->toBeNull();
    });

    it('returns the token with highest id when multiple exist', function () {
        $token1 = XeroToken::factory()->create();
        $token2 = XeroToken::factory()->create();
        $token3 = XeroToken::factory()->create();

        $latest = XeroToken::latestToken();

        expect($latest->id)->toBe($token3->id);
    });
});

// ==================== Token Conversion Tests ====================

describe('XeroToken Model - Token Conversion Methods', function () {
    it('can convert to OAuth2 AccessToken', function () {
        $tokenData = [
            'id_token' => 'id_token_value',
            'token_type' => 'Bearer',
            'access_token' => 'access_token_value',
            'refresh_token' => 'refresh_token_value',
            'scope' => 'openid email profile',
            'expires' => now()->addHours(1)->timestamp,
        ];

        $token = XeroToken::factory()->create($tokenData);
        $oauth2Token = $token->toOAuth2Token();

        expect($oauth2Token)->toBeInstanceOf(AccessToken::class);
        expect($oauth2Token->getToken())->toBe('access_token_value');
    });

    it('oauth2 token contains access token', function () {
        $token = XeroToken::factory()->create();
        $oauth2Token = $token->toOAuth2Token();

        expect($oauth2Token->getToken())->not->toBeEmpty();
    });

    it('oauth2 token includes expires value', function () {
        $expiresTimestamp = now()->addHours(2)->timestamp;
        $token = XeroToken::factory()->create(['expires' => $expiresTimestamp]);
        $oauth2Token = $token->toOAuth2Token();

        expect($oauth2Token->getExpires())->toBe($expiresTimestamp);
    });
});

// ==================== Token Validation Tests ====================

describe('XeroToken Model - Token Validation', function () {
    it('validates token format with all required fields', function () {
        $tokenData = [
            'id_token' => 'id_token_value',
            'token_type' => 'Bearer',
            'access_token' => 'access_token_value',
            'refresh_token' => 'refresh_token_value',
            'expires' => now()->addHours(1)->timestamp,
            'scope' => 'openid',
        ];

        $token = new AccessToken($tokenData);

        expect(XeroToken::isValidTokenFormat($token))->toBeTrue();
    });

    it('rejects token missing id_token', function () {
        $tokenData = [
            'token_type' => 'Bearer',
            'access_token' => 'access_token_value',
            'refresh_token' => 'refresh_token_value',
            'expires' => now()->addHours(1)->timestamp,
            'scope' => 'openid',
        ];

        $token = new AccessToken($tokenData);

        expect(XeroToken::isValidTokenFormat($token))->toBeFalse();
    });

    it('rejects token missing token_type', function () {
        $tokenData = [
            'id_token' => 'id_token_value',
            'access_token' => 'access_token_value',
            'refresh_token' => 'refresh_token_value',
            'expires' => now()->addHours(1)->timestamp,
            'scope' => 'openid',
        ];

        $token = new AccessToken($tokenData);

        expect(XeroToken::isValidTokenFormat($token))->toBeFalse();
    });

    it('rejects token missing access_token', function () {
        // Access token requires the access_token field, so trying to create without it will throw
        // We need to handle this differently - we'll test that validation catches incomplete structures
        
        $this->expectException(\InvalidArgumentException::class);
        
        $tokenData = [
            'id_token' => 'id_token_value',
            'token_type' => 'Bearer',
            'refresh_token' => 'refresh_token_value',
            'expires' => now()->addHours(1)->timestamp,
            'scope' => 'openid',
        ];

        new AccessToken($tokenData);
    });

    it('rejects token missing refresh_token', function () {
        $tokenData = [
            'id_token' => 'id_token_value',
            'token_type' => 'Bearer',
            'access_token' => 'access_token_value',
            'expires' => now()->addHours(1)->timestamp,
            'scope' => 'openid',
        ];

        $token = new AccessToken($tokenData);

        expect(XeroToken::isValidTokenFormat($token))->toBeFalse();
    });

    it('rejects token missing expires', function () {
        $tokenData = [
            'id_token' => 'id_token_value',
            'token_type' => 'Bearer',
            'access_token' => 'access_token_value',
            'refresh_token' => 'refresh_token_value',
            'scope' => 'openid',
        ];

        $token = new AccessToken($tokenData);

        expect(XeroToken::isValidTokenFormat($token))->toBeFalse();
    });

    it('rejects token missing scope', function () {
        $tokenData = [
            'id_token' => 'id_token_value',
            'token_type' => 'Bearer',
            'access_token' => 'access_token_value',
            'refresh_token' => 'refresh_token_value',
            'expires' => now()->addHours(1)->timestamp,
        ];

        $token = new AccessToken($tokenData);

        expect(XeroToken::isValidTokenFormat($token))->toBeFalse();
    });
});

// ==================== Database Tests ====================

describe('XeroToken Model - Database Operations', function () {
    it('correctly persists to xero_tokens table', function () {
        $token = XeroToken::factory()->create();

        assertDatabaseCount('xero_tokens', 1);
        assertDatabaseHas('xero_tokens', [
            'id' => $token->id,
            'access_token' => $token->access_token,
        ]);
    });

    it('can query by access_token', function () {
        XeroToken::factory()->create(['access_token' => 'token_1']);
        XeroToken::factory()->create(['access_token' => 'token_2']);

        $token = XeroToken::where('access_token', 'token_1')->first();

        expect($token->access_token)->toBe('token_1');
    });

    it('can query by current_tenant_id', function () {
        XeroToken::factory()->create(['current_tenant_id' => 'tenant_a']);
        XeroToken::factory()->create(['current_tenant_id' => 'tenant_b']);

        $tokens = XeroToken::where('current_tenant_id', 'tenant_a')->get();

        expect($tokens)->toHaveCount(1);
        expect($tokens[0]->current_tenant_id)->toBe('tenant_a');
    });
});

// ==================== Relationship Tests ====================

describe('XeroToken Model - Relationships', function () {
    it('has tenant relationship method defined', function () {
        $token = XeroToken::factory()->make();
        
        expect(method_exists($token, 'tenant'))->toBeTrue();
    });

    it('can access protected table name correctly', function () {
        $token = XeroToken::factory()->make();
        
        expect($token->getTable())->toBe('xero_tokens');
    });

    it('model has correct primary key', function () {
        $token = XeroToken::factory()->make();
        
        expect($token->getKeyName())->toBe('id');
    });
});

// ==================== Model Casting Tests ====================

describe('XeroToken Model - Casting', function () {
    it('stores and retrieves timestamp fields', function () {
        $token = XeroToken::factory()->create();

        expect($token->expires)->toBeTruthy();
        expect(is_numeric($token->expires))->toBeTrue();
    });

    it('json serializes token data correctly', function () {
        $token = XeroToken::factory()->create([
            'id_token' => 'test_id_token',
            'token_type' => 'Bearer',
            'access_token' => 'test_access_token',
        ]);

        $json = json_decode(json_encode($token), true);

        expect($json['id_token'])->toBe('test_id_token');
        expect($json['token_type'])->toBe('Bearer');
        expect($json['access_token'])->toBe('test_access_token');
    });
});

// ==================== Edge Cases & Special Tests ====================

describe('XeroToken Model - Edge Cases', function () {
    it('can handle very long token strings', function () {
        $longToken = str_repeat('a', 1000);

        $token = XeroToken::factory()->create([
            'id_token' => $longToken,
            'access_token' => $longToken,
        ]);

        expect($token->id_token)->toBe($longToken);
        expect($token->access_token)->toBe($longToken);
    });

    it('can handle null values for nullable fields', function () {
        $token = XeroToken::factory()->create([
            'token_type' => null,
            'refresh_token' => null,
            'scope' => null,
            'current_tenant_id' => null,
            'expires' => null,
        ]);

        expect($token->token_type)->toBeNull();
        expect($token->refresh_token)->toBeNull();
        expect($token->scope)->toBeNull();
        expect($token->current_tenant_id)->toBeNull();
        expect($token->expires)->toBeNull();
    });

    it('can update token with partial data', function () {
        $token = XeroToken::factory()->create();
        $originalIdToken = $token->id_token;

        $token->update(['scope' => 'openid email']);

        expect($token->id_token)->toBe($originalIdToken);
        expect($token->scope)->toBe('openid email');
    });

    it('can refresh model from database', function () {
        $token = XeroToken::factory()->create(['scope' => 'original']);

        XeroToken::where('id', $token->id)->update(['scope' => 'updated']);

        $token->refresh();

        expect($token->scope)->toBe('updated');
    });
});
