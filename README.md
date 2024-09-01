## Tube Video Backend v3

## Changelog:

### v3.2.3

Fixed:

- Fixed an issue with Temporary and Permanent Cache. e.g. related to DMCA Tool (Blocked URLs) and Trusted Session Storage

Improved:

- added API Key Cache that improves Performance and decrease the load on MySQL

Misc:

- several improvements and code refactoring

Full Changelog: [v3.2.2...v3.2.3](https://github.com/PureDevLabs/TubeVideoBackend/compare/v3.2.2...v3.2.3)

run after update

```bash
php artisan generate:trustedSession
```

### v3.2.2
- Added ability to use a "Trusted Session" for authenticating YouTube requests 
  - Choose your authentication method (Trusted Session or OAuth Tokens) in the admin section, at **Settings -> YouTube Authentication**
  - Trusted Sessions only work when [youtube-trusted-session](https://github.com/PureDevLabs/youtube-trusted-session) is also installed on the SAME server
  - Trusted Session does NOT require any YouTube accounts
  - Trusted Sessions automatically regenerate every 2 hours
  - Trusted Sessions should not require manual, regular maintenance (unlike OAuth Tokens)
- Added Disable buttons to the OAuth Management admin section to disable any given OAuth Token (without deleting it)

Full Changelog: [v3.2.1...v3.2.2](https://github.com/PureDevLabs/TubeVideoBackend/compare/v3.2.1...v3.2.2)

run after update

```bash
php artisan migrate
php artisan optimize:clear
php artisan generate:trustedSession
```

---

### v3.2.1
- Added OAuth login capability for YouTube requests 
- See https://github.com/PureDevLabs/TubeVideoBackend/discussions/8



Full Changelog: [v3.2.0...v3.2.1](https://github.com/PureDevLabs/TubeVideoBackend/compare/v3.2.0...v3.2.1)



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

run after update

```bash
php artisan optimize:clear
composer dump-autoload
```

---

### v3.1.2
Added:
- Added a function to bypass YouTube ip bans by refreshing IPv6 addresses all 2 hours. <br/>Requires a /64 IPv6 Subnet and it MUST be configured on your Server. 


### v3.1.1
Fixes:
- TikTok Extractor

### v3.1.0

- fixed Tiktok, Facebook, Instagram, X (Twitter) Extractor
- Added DMCA function in Admin Panel to Block videos. 
- added related videos to search api

### v3.0.2

- fixed Instagram, Soundcloud
- added Twitch
- minor bug fixes
- added Docker support (Experimental)


