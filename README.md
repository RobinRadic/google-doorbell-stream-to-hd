## WIP: Google doorbell camera feed stream directly to HD
- Initial release. Not a lot of configurables yet. 
- Created in PHP/Laravel. Perhaps not the most eloquent solution. 
- Uses [Google's Smart Device Manager](), primarily the [sdm.devices.traits.CameraLiveStream](https://developers.google.com/nest/device-access/traits/device/camera-live-stream) 
- Requires **ffmpeg** to be installed.


### Install
- Copy the `.env.example` to `.env`
- Ensure correct database settings
- Ensure correct settings for `.env`
```dotenv
GOOGLE_APPLICATION_NAME=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_DOORBELL_PROJECT_ID=
GOOGLE_DOORBELL_OUTPUT_DIRECTORY=/home/foo/doorcam
```

- `php artisan migrate`
- `php db:seed`
- `php artisan google:authorize` Starts the OAuth2 procedure to authorize this application.
- `php artisan google:doorbell:start` Starts the livestream recording to the specified directory. 
- `php artisan google:doorbell:status` Shows some nerdy stats
- `php artisan google:doorbell:stop` Stops the livestream recording



#### Nginx/apache
I'd suggest rather strict whitelisting  
```nginx
location / {
    allow 127.0.0.1;        # localhost
    allow 10.0.0.10;        # local network pc
    allow 10.0.0.0/24;      # local network range
    allow 85.277.273.751;   # external ip
    deny all;               # and no one else
}
```


### Todo
A lot...
