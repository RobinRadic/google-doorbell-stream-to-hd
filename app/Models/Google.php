<?php

namespace App\Models;

use App\Google\Services\GoogleService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Google
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Google newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Google newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Google query()
 * @mixin \Eloquent
 * @property int                                                    $id
 * @property \Illuminate\Support\Carbon|null                        $created_at
 * @property \Illuminate\Support\Carbon|null                        $updated_at
 * @property string                                                 $application_name
 * @property string                                                 $client_id
 * @property string                                                 $client_secret
 * @property string                                                 $project_id
 * @property string|null                                            $authorization_code
 * @property string|null                                            $access_token
 * @property string|null                                            $expires_in
 * @property string|null                                            $refresh_token
 * @property mixed|null                                             $scopes
 * @property string                                                 $token_type
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereApplicationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereAuthorizationCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereClientId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereClientSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereExpiresIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereRefreshToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereScopes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereTokenType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Google whereUpdatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|Google[] $livestreams
 * @property-read int|null                                          $livestreams_count
 */
class Google extends Model
{
    protected $table = 'google';

    protected $guarded = [];

    protected $casts = [
        'scopes' => 'array',
    ];

    public function livestreams()
    {
        return $this->hasMany(Google::class, 'project_id', 'project_id');
    }

    use HasFactory;

    public static function createFromConfig($attributes = [])
    {
        return new static(array_replace([
            'application_name' => config('google.smd.application_name'),
            'client_id'        => config('google.smd.client_id'),
            'client_secret'    => config('google.smd.client_secret'),
            'scopes'           => config('google.smd.scopes'),
            'project_id'       => config('google.smd.project_id'),
        ], $attributes));
    }

    public function getGoogleClient()
    {
        return new \Google\Client([
            'base_path'        => config('google.smd.base_path')(config('google.smd.project_id')),
            'redirect_uri'     => config('google.smd.redirect_uri')(),
            'access_type'      => config('google.smd.access_type'),
            'prompt'           => config('google.smd.prompt'),
            'client_id'        => $this->client_id,
            'client_secret'    => $this->client_secret,
            'scopes'           => $this->scopes,
            'application_name' => $this->application_name,
            'response_type'    => config('google.smd.response_type'),
        ]);
    }

    public function getGoogleService()
    {
        return new GoogleService($this);
    }

    public function setToken($token)
    {
        $this->access_token  = $token[ 'access_token' ];
        $this->refresh_token = $token[ 'refresh_token' ];
        $this->expires_in    = $token[ 'expires_in' ];
        $this->token_type    = $token[ 'token_type' ];
        $this->save();
    }
}
