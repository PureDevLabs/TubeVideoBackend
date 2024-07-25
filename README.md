## Tube Video Backend v3

## Changelog:

### v3.2.1
- Added OAuth login capability for YouTube requests 
- See https://github.com/PureDevLabs/TubeVideoBackend/discussions/8

#### Updated files
```
README.md
app/Console/Commands/RefreshOAuthTokens.php
app/Console/Kernel.php
app/Http/Livewire/OauthTokens.php
app/Models/OauthToken.php
database/migrations/2024_07_19_062641_create_oauth_tokens_table.php
lib/BackendApp.php
lib/Extractors/Youtube.php
lib/Extractors/YoutubeData.php
resources/views/admin/oauth-management.blade.php
resources/views/livewire/oauth-tokens.blade.php
resources/views/navigation-menu.blade.php
routes/web.php
```

run after update

```bash
php artisan migrate
php artisan optimize:clear
```

---

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

run after update

```bash
php artisan optimize:clear
composer dump-autoload
```

---

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

### v3.0.2

- fixed Instagram, Soundcloud
- added Twitch
- minor bug fixes
- added Docker support (Experimental)


