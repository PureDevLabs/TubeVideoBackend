## Tube Video Backend v3

## Changelog:

### v3.2.0
- Rebranded
- Removed Licensing
- Removed Encoding

#### Updated files
```
.env.example
LICENSE (new)
README.md
app/Console/Commands/GenerateNewIPv6.php
app/Http/Controllers/ApiController.php
app/Http/Livewire/UrlBlacklist.php
composer.json
config/scribe.php
deploy_docker.sh (deleted)
docker-compose.single.yml (deleted)
docker-compose.standalone.yml (deleted)
docker/8.1-prod/ (entire folder deleted)
lib/BackendApp.php
lib/Core.php
lib/DMCA.php
lib/Extractors/ (entire folder updated)
lib/HttpClient.php
lib/Misc.php (deleted)
lib/Misc/ (entire folder updated)
lib/Parser.php
lib/ProxyDownload.php
lib/Utils.php
public/docs/index.html
resources/views/admin/settings.blade.php
resources/views/dashboard.blade.php
resources/views/installer/check.blade.php
resources/views/livewire/instagram-cookie.blade.php
routes/api.php
routes/web.php
```

### v3.1.2
Added:
- Added a function to bypass YouTube ip bans by refreshing IPv6 addresses all 2 hours. Requires a /64 IPv6 Subnet and it MUST be configured on your Server. 

#### Updated files
```
.env.example 
app/Console/Commands/GenerateNewIPv6.php (new)
app/Console/Kernel.php
lib/Extractors/Youtube.php
lib/Extractors/YoutubeData.php
lib/BackendApp.php
lib/ProxyDownload.php
lib/Misc/BaseHandler.php (new)
lib/Misc/Generator.php (new)
```

### v3.1.1
Fixes:
- TikTok Extractor

#### Updated files
```
lib/Extractors/Tiktok.php
lib/Extractors/Extractor.php
.env.example
config/app.php
lib/BackendApp.php
```

### v3.1.0

- fixed Tiktok, Facebook, Instagram, X (Twitter) Extractor
- Added DMCA function in Admin Panel to Block videos. 
- added related videos to search api

#### Updated files
> app/Http/Controllers/ApiController.php <br>
> app/Http/Livewire/UrlBlacklist.php <br>
> app/Models/BlacklistUrl.php <br>
> app/Models/Extractor.php <br>
> database/migrations/2023_03_11_005440_create_extractors_table.php <br>
> database/migrations/2023_03_12_230403_create_blacklist_urls_table.php <br>
> database/seeders/DatabaseSeeder.php <br>
> database/seeders/ExtractorSeeder.php <br>
> lib/BackendApp.php <br>
> lib/DMCA.php <br>
> lib/Extractors/Extractor.php <br>
> lib/Extractors/Facebook.php <br>
> lib/Extractors/Instagram.php <br>
> lib/Extractors/Tiktok.php <br>
> lib/Extractors/Twitter.php <br>
> lib/Extractors/YoutubeData.php <br>
> lib/Extractors/YoutubeSearch.php <br>
> resources/views/admin/url-blacklist.blade.php <br>
> resources/views/livewire/url-blacklist.blade.php <br>
> resources/views/navigation-menu.blade.php <br>
> routes/api.php <br>
> routes/web.php <br>

### v3.0.2

- fixed Instagram, Soundcloud
- added Twitch
- minor bug fixes
- added Docker support (Experimental)

#### Updated files
> .env.example <br>
>  app/Http/Controllers/ApiController.php <br>
>  app/Http/Livewire/ApiManagement.php <br>
>  app/Http/Livewire/InstagramCookie.php (✨ New ✨) <br>
>  app/Install/Checker.php (✨ New ✨) <br>
>  app/Install/Installer.php (✨ New ✨) <br>
>  app/Providers/RouteServiceProvider.php <br>
>  composer.json <br>
>  config/database.php <br>
>  deploy_docker.sh (✨ New ✨) <br>
>  docker-compose.single.yml (✨ New ✨) <br>
>  docker-compose.standalone.yml (✨ New ✨) <br>
>  docker-compose.yml <br>
>  docker/8.1-prod/.gitignore (✨ New ✨) <br>
>  docker/8.1-prod/Dockerfile (✨ New ✨) <br>
>  docker/8.1-prod/php/php.ini (✨ New ✨) <br>
>  lib/Core.php <br>
>  lib/Extractors/Extractor.php <br>
>  lib/Extractors/Instagram.php <br> 
>  lib/Extractors/SoundCloud.php <br>
>  lib/Extractors/Twitch.php (✨ New ✨) <br>
>  lib/Extractors/Youtube.php <br>
>  lib/Extractors/YoutubeSearch.php <br>
>  lib/HttpClient.php (✨ New ✨) <br>
>  lib/Misc.php (✨ New ✨) <br>
>  lib/ProxyDownload.php (✨ New ✨) <br>
>  public/css/app.css <br>
>  public/js/app.js <br>
>  public/mix-manifest.json <br>
>  resources/views/admin/cookie-management.blade.php (✨ New ✨) <br>
>  resources/views/installer/check.blade.php (✨ New ✨) <br>
>  resources/views/installer/index.blade.php (✨ New ✨) <br>
>  resources/views/livewire/instagram-cookie.blade.php (✨ New ✨) <br>
>  resources/views/livewire/manage-key.blade.php <br>
>  resources/views/navigation-menu.blade.php <br>
>  routes/api.php <br>
>  routes/installer.php (✨ New ✨) <br>
>  routes/web.php <br>
