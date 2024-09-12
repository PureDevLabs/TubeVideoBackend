## Tube Video Backend v3
![](https://img.shields.io/badge/Build-passing-brightgreen) ![](https://img.shields.io/badge/Version-3.2.5-blue)

### Recommended Requirements
- OS: Debian 12 / Ubuntu 22.04
- Web Panel: aaPanel
- Apache or Nginx
- mod_rewrite (URL rewriting)
- PHP 7.4.x - 8.1.x
- PHP Extensions: cURL, Fileinfo, and Redis (Optional: Opcache)
- PHP Functions enabled: proc_open, popen, putenv, exec, chown, pcntl_signal, and pcntl_alarm
- MySQL 8 / MariaDB 10.7
- Redis
- IPv6 /64 Subnet
- YouTube Auth method (Choose ONE in admin section, at **Settings -> YouTube Authentication**)
  - Trusted Session **_(recommended!)_**
    - Install [youtube-trusted-session](https://github.com/PureDevLabs/youtube-trusted-session)
    - See https://github.com/PureDevLabs/TubeVideoBackend?tab=readme-ov-file#v322
  - OAuth Tokens
    - Install [youtube-oauth](https://github.com/PureDevLabs/youtube-oauth)
    - See https://github.com/PureDevLabs/TubeVideoBackend/discussions/8

---

### Changelog:

### v3.2.5

Fixes:
- fixing recent YouTube changes

 Full Changelog: [v3.2.4...v3.2.5](https://github.com/PureDevLabs/TubeVideoBackend/compare/v3.2.4...v3.2.5)

 delete `software.json` from `storage/app/YouTube` folder after update.
 
---

### v3.2.4

Fixed:

- Fixed an issue with 403 Http Status caused through duplicated audio only streams.  [#15](https://github.com/PureDevLabs/TubeVideoBackend/issues/15)
- Fixed file owner permissions during generate of trusted session.  [#14](https://github.com/PureDevLabs/TubeVideoBackend/issues/14)


Full Changelog: [v3.2.3...v3.2.4](https://github.com/PureDevLabs/TubeVideoBackend/compare/v3.2.3...v3.2.4)

---

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
---

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


