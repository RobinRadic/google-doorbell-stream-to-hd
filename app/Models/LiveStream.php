<?php

namespace App\Models;

use App\Google\DataModels\Device;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use League\Uri\Components\Query;
use League\Uri\Uri;

/**
 * App\Models\LiveStream
 *
 * @property int                     $id
 * @property Carbon|null             $created_at
 * @property Carbon|null             $updated_at
 * @property string                  $url
 * @property string                  $extension_token
 * @property string                  $token
 * @property string                  $project_id
 * @property string                  $device_id
 * @property string                  $expires_at
 * @property-read \App\Models\Google $google
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream query()
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream whereExtensionToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|LiveStream whereUrl($value)
 * @mixin \Eloquent
 */
class LiveStream extends Model
{
    protected $table = 'live_streams';

    protected $guarded = [];

    protected $observables = [ 'extending', 'extended' ];

    protected Device $device;

    use HasFactory;

    public function google()
    {
        return $this->belongsTo(Google::class, 'project_id', 'project_id');
    }

    public static function extending($callback)
    {
        static::registerModelEvent('extending', $callback);
    }

    public static function extended($callback)
    {
        static::registerModelEvent('extended', $callback);
    }

    public function extend(array $data)
    {
        $this->fireModelEvent('extending');
        $data                  = $data[ 'results' ] ?? $data;
        $this->expires_at      = $data[ 'expiresAt' ];
        $this->extension_token = $data[ 'streamExtensionToken' ];
        $this->token           = $data[ 'streamToken' ];
        $this->url             = $this->updateUrlWithToken($this->url, $this->token);
        $this->save();
        $this->fireModelEvent('extended');
        return $this;
    }

    protected function updateUrlWithToken($url, $token)
    {
        $uri      = Uri::createFromString($url);
        $newQuery = Query::createFromUri($uri)->withPair('auth', $token)->jsonSerialize();
        return $uri->withQuery($newQuery)->jsonSerialize();
    }

    public static function start(Device $device, array $data)
    {
        $data                = $data[ 'results' ] ?? $data;
        $ls                  = new static();
        $ls->device          = $device;
        $ls->project_id      = $device->getProjectId();
        $ls->device_id       = $device->getDeviceId();
        $ls->expires_at      = $data[ 'expiresAt' ];
        $ls->extension_token = $data[ 'streamExtensionToken' ];
        $ls->token           = $data[ 'streamToken' ];
        $ls->url             = $data[ 'streamUrls' ][ 'rtspUrl' ];
        $ls->save();
        return $ls;
    }

    public function stop()
    {
        return $this->delete();
    }

    public function setExpiresAtAttribute($value)
    {
        $expiresAt = Carbon::make($value);
        if(config('app.locale') === 'nl') {
            $expiresAt->addHours(2);
        }
        $value = $expiresAt->toDateTimeString();
        $this->attributes[ 'expires_at' ] = $value;
    }

    public function getExpiresAt()
    {
        return $this->expires_at;
    }

    public function getCarbonExpiresAt(): Carbon
    {
        return Carbon::make($this->expires_at);
    }

    public function getSecondsUntilExpires()
    {
        return now()->secondsUntil($this->getCarbonExpiresAt())->count();
    }

    public function isExpired()
    {
        return $this->getCarbonExpiresAt()->isPast();
    }

    public function getExtensionToken()
    {
        return $this->extension_token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getDevice()
    {
        return $this->device;
    }

}
